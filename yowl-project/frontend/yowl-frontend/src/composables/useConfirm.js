import { reactive, readonly } from 'vue';

/**
 * Dialogue de confirmation, appele comme une fonction et attendu.
 *
 *   if (await confirm({ title: 'Supprimer ?', tone: 'danger' })) { ... }
 *
 * Un seul dialogue vit a la fois, monte une fois pour toutes dans App.vue.
 * Une pile serait de la complexite pour un cas qui ne se presente pas : on ne
 * demande pas deux confirmations superposees.
 */
const state = reactive({
  open: false,
  title: '',
  message: '',
  confirmLabel: 'Confirmer',
  cancelLabel: 'Annuler',
  tone: 'primary', // primary | danger
  pending: false,
});

let resolver = null;

function close(answer) {
  state.open = false;
  state.pending = false;
  if (resolver) {
    resolver(answer);
    resolver = null;
  }
}

export function useConfirm() {
  /**
   * @param {{title: string, message?: string, confirmLabel?: string,
   *          cancelLabel?: string, tone?: 'primary'|'danger'}} options
   * @returns {Promise<boolean>}
   */
  return function confirm(options) {
    // Une demande qui en interrompt une autre repond non a la precedente,
    // pour ne jamais laisser une promesse en suspens.
    if (resolver) close(false);

    state.title = options.title ?? 'Confirmer';
    state.message = options.message ?? '';
    state.confirmLabel = options.confirmLabel ?? 'Confirmer';
    state.cancelLabel = options.cancelLabel ?? 'Annuler';
    state.tone = options.tone ?? 'primary';
    state.open = true;

    return new Promise((resolve) => {
      resolver = resolve;
    });
  };
}

/** Reserve au composant ConfirmDialog. */
export function useConfirmState() {
  return {
    state: readonly(state),
    accept: () => close(true),
    reject: () => close(false),
  };
}
