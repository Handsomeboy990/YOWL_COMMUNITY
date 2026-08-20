import { appel, adressePropre, reglages } from './config.js';

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
    await chrome.action.setBadgeText({ tabId, text: '' });
    return;
  }

  await chrome.action.setBadgeText({ tabId, text: String(Math.min(nombre, 9)) });
  await chrome.action.setBadgeBackgroundColor({ tabId, color: '#cc4a15' });
  await chrome.action.setTitle({
    tabId,
    title: nombre > 1
      ? nombre + ' discussions sur cette page'
      : 'Une discussion sur cette page',
  });
}

chrome.tabs.onUpdated.addListener((tabId, info, tab) => {
  // Sur "complete" seulement : l'adresse change plusieurs fois pendant un
  // chargement, et interroger l'API à chaque étape ne servirait à rien.
  if (info.status === 'complete' && tab.url) {
    rafraichirBadge(tabId, tab.url);
  }
});

chrome.tabs.onActivated.addListener(async ({ tabId }) => {
  const onglet = await chrome.tabs.get(tabId).catch(() => null);
  if (onglet?.url) rafraichirBadge(tabId, onglet.url);
});

// ---------------------------------------------------------------------------
// Menus contextuels
// ---------------------------------------------------------------------------

chrome.runtime.onInstalled.addListener(() => {
  chrome.contextMenus.create({
    id: 'yowl-page',
    title: 'Donner mon avis sur cette page',
    contexts: ['page'],
  });
  chrome.contextMenus.create({
    id: 'yowl-lien',
    title: 'Donner mon avis sur ce lien',
    contexts: ['link'],
  });
  chrome.contextMenus.create({
    id: 'yowl-citation',
    title: 'Citer ce passage sur YOWL',
    contexts: ['selection'],
  });
});

chrome.contextMenus.onClicked.addListener(async (info, tab) => {
  const cible = info.menuItemId === 'yowl-lien' ? info.linkUrl : info.pageUrl || tab?.url;

  // Le passage sélectionné devient le début de l'avis : citer d'abord, réagir
  // ensuite, c'est l'ordre dans lequel les gens écrivent.
  const brouillon = info.menuItemId === 'yowl-citation' && info.selectionText
    ? '« ' + info.selectionText.trim().slice(0, 500) + ' »\n\n'
    : '';

  await chrome.storage.local.set({
    prefill: { url: cible, title: tab?.title || '', draft: brouillon },
  });

  await chrome.action.openPopup().catch(async () => {
    // openPopup n'est pas disponible partout : on retombe sur un onglet.
    const { siteUrl } = await reglages();
    const params = new URLSearchParams({ url: cible || '', title: tab?.title || '' });
    chrome.tabs.create({ url: siteUrl + '/share?' + params.toString() });
  });
});

// ---------------------------------------------------------------------------
// Connexion depuis le site
// ---------------------------------------------------------------------------

/**
 * Le site remet le jeton, l'extension ne le demande jamais.
 *
 * Un champ où coller un jeton à la main est une invitation à en fabriquer un
 * faux et un obstacle pour tout le monde. Ici la page /extension du site, une
 * fois la personne connectée, envoie le jeton que le navigateur détient déjà.
 */
chrome.runtime.onMessageExternal.addListener((message, expediteur, repondre) => {
  if (message?.type !== 'yowl-connect' || !message.token) {
    repondre({ ok: false });
    return true;
  }

  chrome.storage.local.set({ token: message.token, user: message.user ?? null }).then(() => {
    cache.clear();
    repondre({ ok: true });
  });

  return true;
});

chrome.runtime.onMessage.addListener((message, expediteur, repondre) => {
  if (message?.type === 'yowl-invalider-cache') {
    cache.clear();
    repondre({ ok: true });
  }
  return true;
});
