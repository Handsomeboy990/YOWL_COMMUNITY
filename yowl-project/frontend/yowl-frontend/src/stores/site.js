import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import api from '@/services/apiService';
import { getStorageUrl } from '@/config';

/**
 * Identité du site, réglée depuis la console d'administration.
 *
 * Chargée une fois au démarrage, avant toute connexion : le logo et la
 * mention de pied de page sont peints pour un visiteur qui n'a pas de compte.
 * Les valeurs par défaut ci-dessous sont celles du registre côté serveur, pour
 * que la première peinture ne soit pas vide si l'appel tarde.
 */
export const useSiteStore = defineStore('site', () => {
  const identity = ref({
    logo: '',
    favicon: '',
    tagline: 'Ton avis sur le web, sans filtre',
    footer: '© 2026 YOWL — LONG Corp',
    contact_email: '',
  });
  const community = ref({ name: 'YOWL Community' });
  const seo = ref({ description: '', share_image: '', indexable: true });
  const loaded = ref(false);

  const name = computed(() => community.value.name || 'YOWL');
  const logoUrl = computed(() => (identity.value.logo ? getStorageUrl(identity.value.logo) : ''));
  const shareImageUrl = computed(() =>
    seo.value.share_image ? getStorageUrl(seo.value.share_image) : ''
  );

  async function load() {
    try {
      const { data } = await api.get('/site');
      identity.value = { ...identity.value, ...(data.data.identity ?? {}) };
      community.value = { ...community.value, ...(data.data.community ?? {}) };
      seo.value = { ...seo.value, ...(data.data.seo ?? {}) };
      loaded.value = true;
      applyHead();
    } catch {
      // Les valeurs par defaut suffisent a afficher le site.
    }
  }

  /**
   * Pose ce qui vit dans l'en-tete du document et non dans un composant :
   * titre, description, icone d'onglet, balises de partage.
   */
  function applyHead() {
    document.title = name.value;

    poserBalise('meta[name="description"]', 'content', seo.value.description);
    poserBalise('meta[name="robots"]', 'content', seo.value.indexable ? 'index,follow' : 'noindex,nofollow');

    poserBalise('meta[property="og:site_name"]', 'content', name.value);
    poserBalise('meta[property="og:title"]', 'content', name.value);
    poserBalise('meta[property="og:description"]', 'content', seo.value.description);
    poserBalise('meta[property="og:type"]', 'content', 'website');
    poserBalise('meta[property="og:url"]', 'content', window.location.origin);

    poserBalise('meta[name="twitter:card"]', 'content', shareImageUrl.value ? 'summary_large_image' : 'summary');
    poserBalise('meta[name="twitter:title"]', 'content', name.value);
    poserBalise('meta[name="twitter:description"]', 'content', seo.value.description);

    if (shareImageUrl.value) {
      poserBalise('meta[property="og:image"]', 'content', shareImageUrl.value);
      poserBalise('meta[name="twitter:image"]', 'content', shareImageUrl.value);
    }

    if (identity.value.favicon) {
      poserBalise('link[rel="icon"]', 'href', getStorageUrl(identity.value.favicon));
    }
  }

  /**
   * Écrit une balise d'en-tête, en la créant si le document ne l'a pas.
   *
   * @param {string} selecteur sélecteur CSS de la balise
   * @param {string} attribut attribut porteur de la valeur
   * @param {string} valeur valeur à écrire, ignorée si vide
   */
  function poserBalise(selecteur, attribut, valeur) {
    if (!valeur) return;

    let balise = document.head.querySelector(selecteur);
    if (!balise) {
      balise = document.createElement(selecteur.startsWith('link') ? 'link' : 'meta');
      // Le sélecteur porte le nom de l'attribut identifiant : on le rejoue.
      const identifiant = /\[([\w-]+)="([^"]+)"\]/.exec(selecteur);
      if (identifiant) balise.setAttribute(identifiant[1], identifiant[2]);
      document.head.appendChild(balise);
    }
    balise.setAttribute(attribut, valeur);
  }

  return { identity, community, seo, loaded, name, logoUrl, shareImageUrl, load, applyHead };
});
