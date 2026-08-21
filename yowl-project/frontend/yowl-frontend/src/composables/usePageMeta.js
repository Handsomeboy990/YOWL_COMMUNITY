import { onBeforeUnmount, watchEffect } from 'vue';
import { useSiteStore } from '@/stores/site';

const ID_DONNEES_STRUCTUREES = 'yowl-donnees-structurees';

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
 * @param {() => {title?: string, description?: string, image?: string,
 *          canonical?: string, robots?: string, type?: string,
 *          jsonLd?: object}} source
 */
export function usePageMeta(source) {
  const site = useSiteStore();

  const arret = watchEffect(() => {
    const { title, description, image, canonical, robots, type, jsonLd } = source() ?? {};
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

    // L'adresse canonique dit au moteur quelle URL fait foi. Sans elle, le
    // meme avis atteint par /reviews/12 et /reviews/12/2 compte comme deux
    // pages qui se font concurrence.
    poser('link[rel="canonical"]', 'href', canonical || window.location.origin + window.location.pathname);

    // Une page utile a un visiteur ne l'est pas forcement dans un index.
    if (robots) poser('meta[name="robots"]', 'content', robots);

    // og:type par defaut vaut website, pose par le magasin du site. Un avis
    // est un article, ce qui change la facon dont un partage est presente.
    if (type) poser('meta[property="og:type"]', 'content', type);

    poserDonneesStructurees(jsonLd);
  });

  function poserDonneesStructurees(objet) {
    document.getElementById(ID_DONNEES_STRUCTUREES)?.remove();
    if (!objet) return;

    const balise = document.createElement('script');
    balise.id = ID_DONNEES_STRUCTUREES;
    balise.type = 'application/ld+json';
    balise.textContent = JSON.stringify(objet);
    document.head.appendChild(balise);
  }

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

    balise.setAttribute(attribut, valeur);
  }

  onBeforeUnmount(() => {
    arret();
    // Seules les données structurées partent : elles décrivent ce contenu-ci
    // et rien d'autre. Les balises restent telles quelles.
    //
    // Elles étaient auparavant restaurées à leur valeur d'avant le montage.
    // Ce n'est plus nécessaire depuis que le routeur repose des valeurs par
    // défaut à chaque navigation, et c'était devenu nuisible : la
    // restauration se déclenche après le passage du routeur, et remettait
    // donc la description de la page quittée sur celle qui arrive.
    poserDonneesStructurees(null);
  });
}

/**
 * Métadonnées d'une route qui n'a rien à charger avant de savoir quoi dire.
 *
 * Le routeur l'appelle à chaque navigation. Une vue qui appelle usePageMeta
 * s'exécute ensuite, au montage, et écrase ce qui est posé ici : les deux ne
 * se disputent rien, l'ordre est toujours celui-là.
 *
 * Pas de restauration en quittant, contrairement à usePageMeta : la
 * navigation suivante repose tout, et remettre des valeurs entre deux routes
 * ne ferait que produire un état intermédiaire que personne ne lit.
 *
 * @param {{titre?: string, description?: string, robots?: string}} donnees
 * @param {ReturnType<typeof useSiteStore>} site
 */
export function appliquerMetaDeRoute(donnees, site) {
  if (typeof document === 'undefined') return;

  const url = window.location.origin + window.location.pathname;
  const nomDuSite = site.name;

  // Le titre appartient désormais toujours à la route. Sans ce drapeau,
  // applyHead, appelé à la fin du chargement de l'identité du site, gagne la
  // course et repose le nom du site sur toutes les pages publiques.
  site.pageOwnsTitle = true;

  // Le nom du site ferme le titre plutôt que de l'ouvrir : dans un onglet
  // étroit comme dans une liste de résultats, c'est le début qui reste
  // lisible, et c'est la page qui doit s'y trouver.
  document.title = donnees.titre ? `${donnees.titre} · ${nomDuSite}` : nomDuSite;

  const poserSimple = (selecteur, attribut, valeur) => {
    if (!valeur) return;
    let balise = document.head.querySelector(selecteur);
    if (!balise) {
      balise = document.createElement(selecteur.startsWith('link') ? 'link' : 'meta');
      const identifiant = /\[([\w-]+)="([^"]+)"\]/.exec(selecteur);
      if (identifiant) balise.setAttribute(identifiant[1], identifiant[2]);
      document.head.appendChild(balise);
    }
    balise.setAttribute(attribut, valeur);
  };

  poserSimple('meta[name="description"]', 'content', donnees.description);
  poserSimple('meta[property="og:title"]', 'content', document.title);
  poserSimple('meta[property="og:description"]', 'content', donnees.description);
  poserSimple('meta[property="og:url"]', 'content', url);
  poserSimple('meta[name="twitter:title"]', 'content', document.title);
  poserSimple('meta[name="twitter:description"]', 'content', donnees.description);
  poserSimple('link[rel="canonical"]', 'href', url);
  // og:type revient à website : une vue qui décrit un article le repose
  // elle-même au montage, et sans cette remise à zéro le type d'un avis
  // resterait collé à la page suivante.
  poserSimple('meta[property="og:type"]', 'content', 'website');

  // La consigne de route ne peut que restreindre. Si le site entier est
  // déclaré non indexable dans les réglages, aucune page ne passe outre.
  const siteIndexable = site.seo?.indexable !== false;
  poserSimple(
    'meta[name="robots"]',
    'content',
    !siteIndexable || donnees.robots ? (donnees.robots ?? 'noindex, nofollow') : 'index, follow'
  );

  document.getElementById(ID_DONNEES_STRUCTUREES)?.remove();
}
