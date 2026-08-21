import { onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * Signale qu'un bandeau horizontal continue au-dela du bord droit.
 *
 * Une rangee d'onglets plus large que l'ecran defile deja d'elle-meme, mais
 * sur telephone rien ne le dit : le dernier onglet est coupe net et se lit
 * comme un defaut d'affichage plutot que comme une invitation a glisser.
 *
 * Le composable renvoie la reference a poser sur l'element defilant et une
 * valeur entre 0 et 1, a lier a --reste-a-defiler sur son conteneur. La
 * feuille de style s'occupe du degrade, qui s'efface une fois la fin atteinte.
 *
 * @returns {{ element: import('vue').Ref, resteADefiler: import('vue').Ref<number> }}
 */
export function useDefilementHorizontal() {
  const element = ref(null);
  const resteADefiler = ref(0);

  // Quelques pixels de marge : les navigateurs arrondissent scrollLeft, et
  // sans cette tolerance le degrade clignote en bout de course.
  const TOLERANCE = 4;

  const mesurer = () => {
    const el = element.value;
    if (!el) return;
    const reste = el.scrollWidth - el.clientWidth - el.scrollLeft;
    resteADefiler.value = reste > TOLERANCE ? 1 : 0;
  };

  let observateur = null;

  onMounted(() => {
    const el = element.value;
    if (!el) return;
    el.addEventListener('scroll', mesurer, { passive: true });

    // La largeur du contenu change avec la police, la langue et la rotation
    // de l'appareil : un seul calcul au montage vieillirait mal.
    if (typeof ResizeObserver !== 'undefined') {
      observateur = new ResizeObserver(mesurer);
      observateur.observe(el);
    }
    mesurer();
  });

  onBeforeUnmount(() => {
    element.value?.removeEventListener('scroll', mesurer);
    observateur?.disconnect();
  });

  return { element, resteADefiler };
}
