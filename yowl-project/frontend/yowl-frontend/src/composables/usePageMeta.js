import { onBeforeUnmount, watchEffect } from 'vue';
import { useSiteStore } from '@/stores/site';

/**
 * Titre et description propres à une page.
 *
 * Une application d'une seule page garde le titre de la précédente sinon, ce
 * que l'historique du navigateur et les favoris rendent visible tout de suite.
 *
 * Limite à connaître : les robots des réseaux sociaux ne lancent pas le
 * JavaScript. Ces balises servent aux moteurs qui le font et à l'onglet du
 * navigateur ; un aperçu correct lors d'un partage sur un réseau demanderait
 * un rendu côté serveur, qui n'est pas en place.
 *
 * @param {() => {title?: string, description?: string, image?: string}} source
 */
export function usePageMeta(source) {
  const site = useSiteStore();
  const posees = [];

  const arret = watchEffect(() => {
    const { title, description, image } = source() ?? {};
    if (!title) return;

    site.pageOwnsTitle = true;
    document.title = `${title} · ${site.name}`;

    poser('meta[name="description"]', 'content', description);
    poser('meta[property="og:title"]', 'content', title);
    poser('meta[property="og:description"]', 'content', description);
    poser('meta[property="og:url"]', 'content', window.location.href);
    poser('meta[name="twitter:title"]', 'content', title);
    poser('meta[name="twitter:description"]', 'content', description);
    if (image) {
      poser('meta[property="og:image"]', 'content', image);
      poser('meta[name="twitter:image"]', 'content', image);
    }
  });

  function poser(selecteur, attribut, valeur) {
    if (!valeur) return;

    let balise = document.head.querySelector(selecteur);
    if (!balise) {
      // La page peut se monter avant que l'identité du site ait posé ses
      // balises : on les crée plutôt que de renoncer en silence.
      balise = document.createElement(selecteur.startsWith('link') ? 'link' : 'meta');
      const identifiant = /\[([\w-]+)="([^"]+)"\]/.exec(selecteur);
      if (identifiant) balise.setAttribute(identifiant[1], identifiant[2]);
      document.head.appendChild(balise);
    }

    // On garde la valeur d'origine pour la remettre en quittant la page.
    if (!posees.some((p) => p.balise === balise)) {
      posees.push({ balise, attribut, avant: balise.getAttribute(attribut) });
    }
    balise.setAttribute(attribut, valeur);
  }

  onBeforeUnmount(() => {
    arret();
    site.pageOwnsTitle = false;
    // La page suivante ne doit pas hériter de la description de celle-ci.
    posees.forEach(({ balise, attribut, avant }) => {
      if (avant === null) balise.removeAttribute(attribut);
      else balise.setAttribute(attribut, avant);
    });
    site.applyHead();
  });
}
