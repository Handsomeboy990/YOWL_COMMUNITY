import { nav } from './browser.js';
import { appel, adressePropre } from './config.js';

/**
 * Le badge dit combien de discussions existent sur la page ouverte.
 *
 * C'est l'idée derrière cette version : plutôt qu'un bouton de publication de
 * plus, l'extension rend la communauté ambiante. On lit un article, et
 * l'icône signale que trois personnes en ont déjà parlé, sans rien demander.
 */

/** Résultats gardés le temps d'une session de navigation. */
const cache = new Map();
const DUREE_CACHE = 5 * 60 * 1000;

async function compterDiscussions(url) {
  const propre = adressePropre(url);
  if (!propre) return null;

  const garde = cache.get(propre);
  if (garde && Date.now() - garde.a < DUREE_CACHE) {
    return garde.n;
  }

  try {
    const reponse = await appel('/liens/existant?link=' + encodeURIComponent(propre));
    // null veut dire session expirée : le badge se tait plutôt que d'afficher
    // un zéro qui ferait croire que personne n'en parle.
    if (reponse === null) return null;

    const nombre = (reponse.data || []).length;
    cache.set(propre, { n: nombre, a: Date.now() });
    return nombre;
  } catch {
    return null;
  }
}

async function rafraichirBadge(tabId, url) {
  const nombre = await compterDiscussions(url);

  if (!nombre) {
    nav.badge.texte({ tabId, text: '' });
    nav.badge.infobulle({ tabId, title: 'YOWL' });
    return;
  }

  nav.badge.texte({ tabId, text: String(Math.min(nombre, 9)) });
  nav.badge.fond({ tabId, color: '#cc4a15' });
  nav.badge.infobulle({
    tabId,
    title: nombre > 1
      ? nombre + ' discussions sur cette page'
      : 'Une discussion sur cette page',
  });
}

nav.onglets.onUpdated.addListener((tabId, info, tab) => {
  // Sur "complete" seulement : l'adresse change plusieurs fois pendant un
  // chargement, et interroger l'API à chaque étape ne servirait à rien.
  if (info.status === 'complete' && tab.url) {
    rafraichirBadge(tabId, tab.url);
  }
});

nav.onglets.onActivated.addListener(async ({ tabId }) => {
  const onglet = await nav.lireOnglet(tabId);
  if (onglet?.url) rafraichirBadge(tabId, onglet.url);
});

// ---------------------------------------------------------------------------
// Menus contextuels
// ---------------------------------------------------------------------------

nav.runtime.onInstalled.addListener(() => {
  nav.menus.create({
    id: 'yowl-page',
    title: 'Donner mon avis sur cette page',
    contexts: ['page'],
  });
  nav.menus.create({
    id: 'yowl-lien',
    title: 'Donner mon avis sur ce lien',
    contexts: ['link'],
  });
  nav.menus.create({
    id: 'yowl-citation',
    title: 'Citer ce passage sur YOWL',
    contexts: ['selection'],
  });
});

nav.menus.onClicked.addListener(async (info, tab) => {
  const cible = info.menuItemId === 'yowl-lien' ? info.linkUrl : info.pageUrl || tab?.url;

  // Le passage sélectionné devient le début de l'avis : citer d'abord, réagir
  // ensuite, c'est l'ordre dans lequel les gens écrivent.
  const brouillon = info.menuItemId === 'yowl-citation' && info.selectionText
    ? '« ' + info.selectionText.trim().slice(0, 500) + ' »\n\n'
    : '';

  await nav.local.ecrire({
    prefill: { url: cible, title: tab?.title || '', draft: brouillon },
  });

  // openPopup n'existe pas partout et Firefox exige un geste direct de
  // l'utilisateur. Quand il refuse, le brouillon reste en attente : la
  // personne clique l'icône et le retrouve.
  const action = nav.runtime.getManifest().manifest_version === 3
    ? (globalThis.browser?.action ?? globalThis.chrome?.action)
    : null;

  try {
    await action?.openPopup?.();
  } catch {
    // Rien à faire : le brouillon est enregistré, l'icône suffit.
  }
});

// ---------------------------------------------------------------------------
// Messages internes
// ---------------------------------------------------------------------------

nav.runtime.onMessage.addListener((message) => {
  // Une publication, une connexion ou une déconnexion périment ce qui est en
  // mémoire : le compte de discussions comme le droit de le demander.
  if (message?.type === 'yowl-invalider-cache' || message?.type === 'yowl-session-changee') {
    cache.clear();
  }
});
