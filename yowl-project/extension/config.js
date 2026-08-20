import { nav } from './browser.js';

/**
 * Adresses par défaut.
 *
 * Ce sont celles de la version publiée. Un membre ordinaire n'a jamais à les
 * connaître ni à les saisir : elles ne sont modifiables que dans la section
 * avancée des réglages, qui existe pour le développement.
 */
export const PAR_DEFAUT = {
  siteUrl: 'http://localhost:5173',
  apiUrl: 'http://localhost:8000/api',
};

export async function reglages() {
  const stocke = await nav.sync.lire(['siteUrl', 'apiUrl']);
  return {
    siteUrl: (stocke.siteUrl || PAR_DEFAUT.siteUrl).replace(/\/$/, ''),
    apiUrl: (stocke.apiUrl || PAR_DEFAUT.apiUrl).replace(/\/$/, ''),
  };
}

export async function jeton() {
  const { token } = await nav.local.lire('token');
  return token || null;
}

export async function membre() {
  const { user } = await nav.local.lire('user');
  return user || null;
}

export async function oublierSession() {
  await nav.local.effacer(['token', 'user']);
  nav.diffuser({ type: 'yowl-session-changee' });
}

/** Erreur portant le code HTTP, pour distinguer une saisie d'une panne. */
export class ErreurApi extends Error {
  constructor(message, statut, champs = null) {
    super(message);
    this.statut = statut;
    this.champs = champs;
  }
}

/**
 * Appelle l'API.
 *
 * Rend null sur 401 plutôt que de jeter : une session expirée est un état
 * normal pour une extension qui vit dans le navigateur pendant des semaines,
 * pas une panne à afficher en rouge.
 */
export async function appel(chemin, options = {}) {
  const { apiUrl } = await reglages();
  const token = await jeton();

  let reponse;
  try {
    reponse = await fetch(apiUrl + chemin, {
      ...options,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...(token ? { Authorization: 'Bearer ' + token } : {}),
        ...(options.headers || {}),
      },
    });
  } catch {
    // Coupure réseau, DNS, serveur éteint : rien ne distingue les trois du
    // point de vue du navigateur, et la personne n'y peut rien de plus.
    throw new ErreurApi('YOWL est injoignable. Vérifie ta connexion.', 0);
  }

  if (reponse.status === 401) {
    await oublierSession();
    return null;
  }

  const corps = await reponse.json().catch(() => ({}));

  if (!reponse.ok) {
    throw new ErreurApi(
      corps.message || 'La requête a échoué.',
      reponse.status,
      corps.errors ?? null
    );
  }

  return corps;
}

/**
 * Se connecte depuis l'extension, sans passer par le site.
 *
 * L'aller-retour vers une page du site demandait de quitter ce qu'on lisait,
 * de retrouver l'onglet de l'extension et de cliquer un second bouton. La
 * plupart des gens abandonnaient en route. Le mot de passe part directement à
 * l'API officielle et n'est conservé nulle part.
 */
export async function connexion(email, motDePasse) {
  const { apiUrl } = await reglages();

  let reponse;
  try {
    reponse = await fetch(apiUrl + '/login', {
      method: 'POST',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password: motDePasse, remember: true }),
    });
  } catch {
    throw new ErreurApi('YOWL est injoignable. Vérifie ta connexion.', 0);
  }

  const corps = await reponse.json().catch(() => ({}));

  if (!reponse.ok || !corps.token) {
    const champs = corps.errors ?? null;
    const message = champs?.email?.[0]
      || corps.message
      || (reponse.status === 429
        ? 'Trop de tentatives. Réessaie dans une minute.'
        : 'Adresse ou mot de passe incorrect.');
    throw new ErreurApi(message, reponse.status, champs);
  }

  await nav.local.ecrire({
    token: corps.token,
    user: {
      id: corps.user?.id,
      username: corps.user?.username,
      picture: corps.user?.picture ?? null,
    },
  });

  nav.diffuser({ type: 'yowl-session-changee' });

  return corps.user;
}

/**
 * Réduit une adresse à ce qui identifie la page.
 *
 * Même règle que côté serveur : sans elle, la même page vue avec un paramètre
 * de campagne passerait pour une autre et la pastille resterait muette.
 */
const SUIVI = [
  'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
  'fbclid', 'gclid', 'mc_cid', 'mc_eid', 'igshid', 'ref', 'ref_src',
  '_ga', 'yclid', 'msclkid', 'si',
];

export function adressePropre(url) {
  try {
    const adresse = new URL(url);
    if (!/^https?:$/.test(adresse.protocol)) return null;
    SUIVI.forEach((parametre) => adresse.searchParams.delete(parametre));
    adresse.hash = '';
    return adresse.toString();
  } catch {
    return null;
  }
}
