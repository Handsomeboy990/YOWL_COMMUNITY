import { onMounted, watch } from 'vue';

/**
 * Garde un brouillon en cours de saisie, le temps d'un aller-retour.
 *
 * Un visiteur qui écrit un commentaire puis se voit renvoyer vers la connexion
 * revenait sur une page vide : son texte avait disparu avec le composant. Le
 * brouillon vit donc hors du composant, dans le stockage de session, qui
 * survit à la navigation comme à un rechargement complet mais s'efface à la
 * fermeture de l'onglet.
 *
 * @param {string} cle identifiant stable du brouillon, par exemple avis-42
 * @param {import('vue').Ref<string>} valeur le champ à surveiller
 * @param {boolean} actif faux pour une modification, qui part déjà remplie
 */
export function useDraft(cle, valeur, actif = true) {
  const nom = 'yowl.draft.' + cle;

  const lire = () => {
    try {
      return sessionStorage.getItem(nom) || '';
    } catch {
      // Navigation privée stricte : on se passe du brouillon.
      return '';
    }
  };

  const ecrire = (texte) => {
    try {
      if (texte && texte.trim()) {
        sessionStorage.setItem(nom, texte);
      } else {
        sessionStorage.removeItem(nom);
      }
    } catch {
      // Sans stockage, la saisie reste seulement en mémoire.
    }
  };

  const oublier = () => {
    try {
      sessionStorage.removeItem(nom);
    } catch {
      // Rien à faire.
    }
  };

  onMounted(() => {
    if (!actif || valeur.value) return;
    const garde = lire();
    if (garde) valeur.value = garde;
  });

  watch(valeur, (texte) => {
    if (actif) ecrire(texte);
  });

  return { oublier };
}
