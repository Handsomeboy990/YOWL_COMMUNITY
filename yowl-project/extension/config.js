/**
 * Réglages de l'extension, avec des valeurs de développement par défaut.
 *
 * L'adresse du site et celle de l'API sont distinctes : en production le
 * premier est sur Vercel et la seconde sur Koyeb, sur deux domaines.
 */
export const PAR_DEFAUT = {
  siteUrl: 'http://localhost:5173',
  apiUrl: 'http://localhost:8000/api',
};

export async function reglages() {
  const stocke = await chrome.storage.sync.get(['siteUrl', 'apiUrl']);
  return {
    siteUrl: (stocke.siteUrl || PAR_DEFAUT.siteUrl).replace(/\/$/, ''),
    apiUrl: (stocke.apiUrl || PAR_DEFAUT.apiUrl).replace(/\/$/, ''),
  };
}

export async function jeton() {
  const { token } = await chrome.storage.local.get('token');
  return token || null;
}

/**
 * Appelle l'API avec le jeton du membre connecté.
 *
 * Renvoie null sur 401 plutôt que de jeter : une session expirée est un état
 * normal pour une extension qui vit dans le navigateur pendant des semaines,
 * pas une erreur à afficher en rouge.
 */
export async function appel(chemin, options = {}) {
  const { apiUrl } = await reglages();
  const token = await jeton();

  const reponse = await fetch(apiUrl + chemin, {
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(token ? { Authorization: 'Bearer ' + token } : {}),
      ...(options.headers || {}),
    },
  });

  if (reponse.status === 401) {
    await chrome.storage.local.remove('token');
    return null;
  }

  if (!reponse.ok) {
    const corps = await reponse.json().catch(() => ({}));
    throw new Error(corps.message || 'La requête a échoué.');
  }

  return reponse.json();
}

/**
 * Réduit une adresse à ce qui identifie la page.
 *
 * Même règle que côté serveur : sans elle, la même page vue avec un paramètre
 * de campagne passerait pour une autre et le badge resterait muet.
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
