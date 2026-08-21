import { onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * Le rail latéral droit, réservé au fil.
 *
 * La coquille applicative est montée une fois pour toutes dans App.vue, et
 * non plus par chaque vue : elle ne peut donc plus recevoir de contenu par un
 * emplacement nommé, puisque les vues sont ses descendantes et non ses
 * parentes. Le rail passe par un Teleport, et ce drapeau dit à la coquille
 * s'il faut lui réserver la place.
 *
 * Un simple ref de module suffit : il n'y a qu'un rail, et une seule vue à la
 * fois peut l'occuper.
 */
export const railActif = ref(false);

/** À appeler dans la vue qui remplit le rail. */
export function useRail() {
  onMounted(() => {
    railActif.value = true;
  });

  onBeforeUnmount(() => {
    railActif.value = false;
  });
}
