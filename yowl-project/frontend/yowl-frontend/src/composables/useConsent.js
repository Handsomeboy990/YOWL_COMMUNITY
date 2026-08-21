import { ref } from 'vue';

/**
 * Accord pour la mesure détaillée d'audience.
 *
 * Deux niveaux, et le premier ne se refuse pas parce qu'il n'y a rien à
 * refuser. La mesure de base ne dépose rien sur l'appareil : elle compte des
 * pages ouvertes, sans identifiant, sans adresse IP, sans moyen de relier
 * deux visites entre elles. Elle continue quel que soit le choix fait ici.
 *
 * Le second niveau dépose un identifiant tiré au hasard, qui permet de savoir
 * combien de personnes distinctes viennent, lesquelles reviennent, et combien
 * de pages elles ouvrent d'affilée. Cela suppose de garder une trace d'un
 * passage à l'autre, donc un accord explicite, révocable, et aussi facile à
 * refuser qu'à donner.
 *
 * Refuser ne dégrade rien pour la personne : aucune fonctionnalité n'en
 * dépend, et le site se comporte exactement pareil.
 */

const CHOIX = 'yowl_mesure';
const VISITEUR = 'yowl_visiteur';
const SESSION = 'yowl.session';

/**
 * Treize mois, la durée maximale que la CNIL admet pour un consentement de
 * mesure d'audience. Au-delà, l'accord est redemandé.
 */
const DUREE_JOURS = 395;

/** Trente minutes sans page ouverte ferment la session. */
const INACTIVITE_MS = 30 * 60 * 1000;

/** 'oui' | 'non' | null tant que la personne n'a pas répondu. */
export const choix = ref(lireCookie(CHOIX));

function lireCookie(nom) {
  if (typeof document === 'undefined') return null;
  const trouve = document.cookie
    .split('; ')
    .find((paire) => paire.startsWith(`${nom}=`));
  return trouve ? decodeURIComponent(trouve.slice(nom.length + 1)) : null;
}

function ecrireCookie(nom, valeur, jours) {
  // SameSite=Lax : le cookie ne part jamais vers un autre site, ce qui est
  // exactement la garantie annoncée dans le bandeau. Secure hors du poste de
  // développement, où le site est servi en clair.
  const secure = window.location.protocol === 'https:' ? '; Secure' : '';
  const expire = new Date(Date.now() + jours * 86400000).toUTCString();
  document.cookie = `${nom}=${encodeURIComponent(valeur)}; Path=/; Max-Age=${jours * 86400}; Expires=${expire}; SameSite=Lax${secure}`;
}

function effacerCookie(nom) {
  document.cookie = `${nom}=; Path=/; Max-Age=0; SameSite=Lax`;
}

function identifiant() {
  // randomUUID n'existe pas sur les contextes non sécurisés anciens : le
  // repli produit la même chose, en moins élégant.
  if (globalThis.crypto?.randomUUID) return globalThis.crypto.randomUUID();

  return '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, (c) =>
    (c ^ (globalThis.crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (c / 4)))).toString(16)
  );
}

/** La personne accepte : un identifiant est posé, pas avant. */
export function accepter() {
  choix.value = 'oui';
  ecrireCookie(CHOIX, 'oui', DUREE_JOURS);
  if (!lireCookie(VISITEUR)) {
    ecrireCookie(VISITEUR, identifiant(), DUREE_JOURS);
  }
}

/** La personne refuse, ou revient sur son accord : tout est retiré. */
export function refuser() {
  choix.value = 'non';
  ecrireCookie(CHOIX, 'non', DUREE_JOURS);
  effacerCookie(VISITEUR);
  try {
    sessionStorage.removeItem(SESSION);
  } catch {
    // Navigation privée sur certains navigateurs : rien à retirer.
  }
}

/**
 * Les identifiants à joindre à une visite, vides sans accord.
 *
 * @returns {{visitor?: string, session?: string}}
 */
export function identifiants() {
  if (choix.value !== 'oui') return {};

  const visiteur = lireCookie(VISITEUR);
  if (!visiteur) return {};

  return { visitor: visiteur, session: sessionCourante() };
}

/**
 * La session en cours, renouvelée après une demi-heure sans page ouverte.
 *
 * La coupe se fait ici et non côté serveur : le navigateur est le seul à
 * savoir quand la personne a cessé de naviguer, le serveur ne voit que des
 * requêtes espacées.
 */
function sessionCourante() {
  let etat = null;

  try {
    etat = JSON.parse(sessionStorage.getItem(SESSION) ?? 'null');
  } catch {
    etat = null;
  }

  const maintenant = Date.now();

  if (!etat?.id || maintenant - (etat.dernier ?? 0) > INACTIVITE_MS) {
    etat = { id: identifiant(), dernier: maintenant };
  } else {
    etat.dernier = maintenant;
  }

  try {
    sessionStorage.setItem(SESSION, JSON.stringify(etat));
  } catch {
    // Sans stockage de session, la visite part sans identifiant de session
    // plutôt que d'échouer : une page comptée vaut mieux qu'une page perdue.
    return undefined;
  }

  return etat.id;
}
