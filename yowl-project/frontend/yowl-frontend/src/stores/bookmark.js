import { ref } from 'vue';
import { defineStore } from 'pinia';
import api from '@/services/apiService';
import { useUserStore } from '@/stores/user';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';

/**
 * Avis enregistres par le membre connecte.
 *
 * Les identifiants sont charges une fois pour que chaque carte connaisse son
 * etat sans un appel par carte.
 */
export const useBookmarkStore = defineStore('bookmarks', () => {
  const ids = ref(new Set());
  const notify = useNotify();

  const has = (id) => ids.value.has(id);

  async function load() {
    if (!useUserStore().isAuthenticated) return;
    try {
      const response = await api.get('/bookmarks/ids');
      ids.value = new Set(response.data.data);
    } catch {
      // Le fil reste utilisable sans cette information.
    }
  }

  async function toggle(reviewId) {
    const saved = ids.value.has(reviewId);
    saved ? ids.value.delete(reviewId) : ids.value.add(reviewId);

    try {
      if (saved) {
        await api.delete(`/bookmarks/${reviewId}`);
      } else {
        await api.post(`/bookmarks/${reviewId}`);
        notify.success('Avis enregistré', 'Retrouve-le dans « Mes enregistrements ».');
      }
    } catch (err) {
      saved ? ids.value.add(reviewId) : ids.value.delete(reviewId);
      notify.error(apiErrorMessage(err, "L'enregistrement a échoué."));
    }
  }

  function reset() {
    ids.value = new Set();
  }

  return { ids, has, load, toggle, reset };
});
