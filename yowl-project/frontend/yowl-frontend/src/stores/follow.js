import { ref } from 'vue';
import { defineStore } from 'pinia';
import api from '@/services/apiService';
import { useUserStore } from '@/stores/user';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';

/**
 * Abonnements du membre connecte.
 *
 * Les identifiants suivis sont gardes en memoire pour que chaque carte du fil
 * sache dans quel etat afficher son bouton sans interroger l'API par carte.
 */
export const useFollowStore = defineStore('follows', () => {
  const users = ref(new Set());
  const tags = ref(new Set());
  const suggestions = ref([]);
  const loaded = ref(false);
  const notify = useNotify();

  const isFollowingUser = (id) => users.value.has(id);
  const isFollowingTag = (id) => tags.value.has(id);

  async function load() {
    if (!useUserStore().isAuthenticated) return;
    try {
      const response = await api.get('/follows');
      users.value = new Set(response.data.data.users.map((u) => u.id));
      tags.value = new Set(response.data.data.tags.map((t) => t.id));
      loaded.value = true;
    } catch {
      // Le fil reste utilisable sans cette information.
    }
  }

  async function loadSuggestions() {
    if (!useUserStore().isAuthenticated) return;
    try {
      const response = await api.get('/follows/suggestions');
      suggestions.value = response.data.data;
    } catch {
      suggestions.value = [];
    }
  }

  async function toggle(type, id) {
    const set = type === 'user' ? users.value : tags.value;
    const following = set.has(id);

    // Bascule optimiste : le bouton repond au clic, et revient si l'appel
    // echoue, plutot que d'attendre l'aller-retour.
    following ? set.delete(id) : set.add(id);

    try {
      if (following) {
        await api.delete('/follows', { data: { type, id } });
      } else {
        await api.post('/follows', { type, id });
      }
      return !following;
    } catch (err) {
      following ? set.add(id) : set.delete(id);
      notify.error(apiErrorMessage(err, "L'abonnement a échoué."));
      return following;
    }
  }

  function reset() {
    users.value = new Set();
    tags.value = new Set();
    suggestions.value = [];
    loaded.value = false;
  }

  return { users, tags, suggestions, loaded, isFollowingUser, isFollowingTag, load, loadSuggestions, toggle, reset };
});
