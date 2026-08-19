import { ref } from 'vue';
import { defineStore } from 'pinia';
import api from '@/services/apiService';
import { useUserStore } from '@/stores/user';
import { apiErrorMessage } from '@/composables/useNotify';

/**
 * Donnees du profil du membre connecte.
 *
 * Elles viennent de l'API et non du fil : la page les calculait auparavant en
 * filtrant le store des reviews, qui ne contient qu'une page de dix. Un membre
 * ayant publie davantage voyait des compteurs faux et une liste tronquee.
 */
export const useProfileStore = defineStore('profile', () => {
  const stats = ref(null);
  const reviews = ref([]);
  const pagination = ref({ current_page: 1, last_page: 1, total: 0 });

  const loadingStats = ref(false);
  const loadingReviews = ref(false);
  const statsError = ref(null);
  const reviewsError = ref(null);

  function currentUserId() {
    return useUserStore().user?.id ?? null;
  }

  async function fetchStats() {
    const id = currentUserId();
    if (!id) return;

    loadingStats.value = true;
    statsError.value = null;
    try {
      const response = await api.get(`/users/${id}/stats`);
      stats.value = response.data.data;
    } catch (err) {
      statsError.value = apiErrorMessage(err, 'Impossible de charger tes statistiques.');
    } finally {
      loadingStats.value = false;
    }
  }

  async function fetchReviews(page = 1) {
    const id = currentUserId();
    if (!id) return;

    loadingReviews.value = true;
    reviewsError.value = null;
    try {
      const response = await api.get(`/users/${id}/reviews`, { params: { page } });
      const payload = response.data.data;
      reviews.value = payload.data ?? [];
      pagination.value = {
        current_page: payload.current_page,
        last_page: payload.last_page,
        total: payload.total,
      };
    } catch (err) {
      reviewsError.value = apiErrorMessage(err, 'Impossible de charger tes reviews.');
    } finally {
      loadingReviews.value = false;
    }
  }

  /** Retire une review de la liste locale apres suppression. */
  function forget(reviewId) {
    reviews.value = reviews.value.filter((review) => review.id !== reviewId);
    pagination.value.total = Math.max(0, pagination.value.total - 1);
  }

  function reset() {
    stats.value = null;
    reviews.value = [];
    pagination.value = { current_page: 1, last_page: 1, total: 0 };
    statsError.value = null;
    reviewsError.value = null;
  }

  return {
    stats,
    reviews,
    pagination,
    loadingStats,
    loadingReviews,
    statsError,
    reviewsError,
    fetchStats,
    fetchReviews,
    forget,
    reset,
  };
});
