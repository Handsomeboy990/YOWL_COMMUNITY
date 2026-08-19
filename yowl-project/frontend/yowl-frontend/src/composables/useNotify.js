import { toast } from 'vue-sonner';

/**
 * Notifications transitoires de l'application.
 *
 * Un seul point de passage, pour que le ton et la duree restent les memes
 * partout, et pour que remplacer la brique sous-jacente ne demande pas de
 * repasser sur chaque appel.
 */
export function useNotify() {
  return {
    /** Une action a abouti. */
    success(message, description) {
      return toast.success(message, { description });
    },

    /** Une action a echoue. Laisse plus de temps a la lecture. */
    error(message, description) {
      return toast.error(message, { description, duration: 6000 });
    },

    /** Une information neutre. */
    info(message, description) {
      return toast(message, { description });
    },

    /** Un avertissement, sans echec. */
    warning(message, description) {
      return toast.warning(message, { description });
    },

    /**
     * Suit une promesse : chargement, puis succes ou echec.
     * @param {Promise} promise
     * @param {{loading: string, success: string, error: string}} messages
     */
    promise(promise, messages) {
      return toast.promise(promise, messages);
    },

    dismiss(id) {
      toast.dismiss(id);
    },
  };
}

/**
 * Extrait le message d'erreur le plus utile d'une reponse d'API.
 *
 * L'API repond soit { message }, soit { error: { champ: [messages] } }.
 */
export function apiErrorMessage(err, fallback = 'Une erreur est survenue.') {
  const data = err?.response?.data;
  if (data?.message) return data.message;

  const firstField = data?.error && Object.values(data.error)[0];
  if (Array.isArray(firstField) && firstField.length) return firstField[0];
  if (typeof firstField === 'string') return firstField;

  if (err?.message) return err.message;
  return fallback;
}
