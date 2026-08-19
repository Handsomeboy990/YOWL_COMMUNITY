import { ref } from 'vue';
import { defineStore } from 'pinia';
import api from '@/services/apiService';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';

/**
 * Contestations d'une decision de moderation.
 *
 * Le membre en depose une, l'administrateur y repond par ecrit. Les deux
 * cotes vivent ici parce qu'ils portent sur la meme ressource : separer les
 * deux ferait diverger la forme des donnees entre le profil et la console.
 */
export const useAppealStore = defineStore('appeals', () => {
  const mine = ref([]);
  const queue = ref([]);
  const pendingCount = ref(0);
  const loading = ref(false);
  const submitting = ref(false);
  const notify = useNotify();

  async function loadMine() {
    loading.value = true;
    try {
      const response = await api.get('/appeals');
      mine.value = response.data.data.data ?? [];
    } catch (err) {
      notify.error(apiErrorMessage(err, 'Tes contestations sont introuvables pour le moment.'));
    } finally {
      loading.value = false;
    }
  }

  /**
   * @param {{type: string, id: number, message: string}} payload
   * @returns {Promise<boolean>} vrai si la contestation est partie
   */
  async function submit(payload) {
    submitting.value = true;
    try {
      const response = await api.post('/appeals', payload);
      notify.success('Contestation transmise', response.data.message);
      await loadMine();
      return true;
    } catch (err) {
      notify.error(apiErrorMessage(err, "La contestation n'a pas pu être transmise."));
      return false;
    } finally {
      submitting.value = false;
    }
  }

  async function loadQueue(status = '') {
    loading.value = true;
    try {
      const response = await api.get('/admin/appeals', { params: status ? { status } : {} });
      queue.value = response.data.data.data ?? [];
      pendingCount.value = response.data.pending_count ?? 0;
    } catch (err) {
      notify.error(apiErrorMessage(err, 'La file des contestations est indisponible.'));
    } finally {
      loading.value = false;
    }
  }

  async function resolve(id, status, response) {
    submitting.value = true;
    try {
      const result = await api.patch(`/admin/appeals/${id}`, { status, response });
      notify.success('Réponse envoyée', result.data.message);
      await loadQueue();
      return true;
    } catch (err) {
      notify.error(apiErrorMessage(err, "La réponse n'a pas pu être enregistrée."));
      return false;
    } finally {
      submitting.value = false;
    }
  }

  return { mine, queue, pendingCount, loading, submitting, loadMine, submit, loadQueue, resolve };
});
