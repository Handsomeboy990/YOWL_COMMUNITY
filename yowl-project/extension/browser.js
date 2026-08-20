/**
 * Une seule façon d'appeler le navigateur.
 *
 * Chrome, Edge, Brave et Opera exposent `chrome`. Firefox expose `browser`,
 * avec des promesses là où Chrome utilise des rappels. Plutôt que de tirer une
 * bibliothèque entière pour ça, on prend l'objet disponible et on enveloppe les
 * quelques méthodes dont l'extension se sert.
 */
const natif = globalThis.browser ?? globalThis.chrome;

/** Vrai quand l'API rend déjà des promesses, ce qui est le cas de Firefox. */
const promet = typeof globalThis.browser !== 'undefined';

/**
 * Enveloppe un appel à rappel dans une promesse, quand le navigateur n'en
 * rend pas lui-même.
 */
function promettre(objet, methode, ...arguments_) {
  if (promet) {
    return objet[methode](...arguments_);
  }

  return new Promise((resoudre, rejeter) => {
    objet[methode](...arguments_, (resultat) => {
      const souci = natif.runtime.lastError;
      if (souci) rejeter(new Error(souci.message));
      else resoudre(resultat);
    });
  });
}

export const nav = {
  /** Stockage. `local` garde le jeton, `sync` suit le compte du navigateur. */
  local: {
    lire: (cles) => promettre(natif.storage.local, 'get', cles),
    ecrire: (valeurs) => promettre(natif.storage.local, 'set', valeurs),
    effacer: (cles) => promettre(natif.storage.local, 'remove', cles),
  },

  sync: {
    lire: (cles) => promettre(natif.storage.sync, 'get', cles),
    ecrire: (valeurs) => promettre(natif.storage.sync, 'set', valeurs),
  },

  ongletActif: async () => {
    const onglets = await promettre(natif.tabs, 'query', { active: true, currentWindow: true });
    return onglets?.[0] ?? null;
  },

  ouvrirOnglet: (url) => promettre(natif.tabs, 'create', { url }),

  lireOnglet: (id) => promettre(natif.tabs, 'get', id).catch(() => null),

  /** La pastille de l'icône. Le nom de l'espace diffère selon le navigateur. */
  badge: {
    texte: (options) => (natif.action ?? natif.browserAction).setBadgeText(options),
    fond: (options) => (natif.action ?? natif.browserAction).setBadgeBackgroundColor(options),
    infobulle: (options) => (natif.action ?? natif.browserAction).setTitle(options),
  },

  menus: natif.contextMenus,
  runtime: natif.runtime,
  onglets: natif.tabs,

  ouvrirReglages: () => natif.runtime.openOptionsPage(),

  /** Diffuse un message aux autres pages de l'extension, sans exiger d'écoute. */
  diffuser: (message) => {
    try {
      const retour = natif.runtime.sendMessage(message);
      // Chrome jette quand personne n'écoute ; Firefox rejette la promesse.
      if (retour && typeof retour.catch === 'function') retour.catch(() => {});
    } catch {
      // Personne n'écoute, ce qui est un état normal.
    }
  },
};

export default nav;
