import { ref } from 'vue';
import { defineStore } from 'pinia';
import api from '@/services/apiService';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';

export const useReviewStore = defineStore(
  'reviews',
  () => {
    const notify = useNotify();
    const reviews = ref([]);
    const error = ref(null);
    const loading = ref(false);
    const search = ref(false)
    const pagination = ref({
      current_page: 1,
      last_page: 1,
      total: 0,
    });
    const kpi = ref({});
    const maxRange = ref({
      count: 0,
      range: 'empty',
    });
    const actualPage = ref(1)

    async function getKPI() {
      try {
        const response = await api.get('/kpi');
        kpi.value = response.data.data;
        const ageRange = kpi.value.nbUsersByAgeRange;

        for (const key in ageRange) {
          const nb = ageRange[key];
          if (maxRange.value['count'] < nb) {
            maxRange.value['count'] = nb;
            maxRange.value['range'] = key;
          }
        }

      } catch (err) {
        // Silent error handling
    }
    }

    //  Récupérer les reviews
    async function getReviews(page = 1) {
      loading.value = true;
      try {
        const response = await api.get(`/reviews?page=${page}`);
        reviews.value = response.data.data.data;
        pagination.value = {
          current_page: response.data.data.current_page,
          last_page: response.data.data.last_page,
          total: response.data.data.total,
        };
        actualPage.value = page
        search.value = false

      } catch (err) {
        error.value = extractErrorMessage(err, 'Impossible de charger le fil pour le moment.');
      } finally {
        loading.value = false;
      }
      getKPI();
    }

    //  Récupérer une review
    async function getReview(id) {
      try {
        const response = await api.get(`/reviews/${id}`);
        const index = reviews.value.findIndex((element) => element.id == id);
        reviews.value[index].nb_views = response.data.data.nb_views


      } catch (err) {
        // Silent error handling
    }
      getKPI();
    }

    //  Créer une review
    async function createReviews(data) {
      try {
        const response = await api.post('/reviews', data, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        response.data.data.comments = [];
        response.data.data.nb_like = 0;
        response.data.data.nb_dislike = 0;
        response.data.data.nb_views = 0;
        reviews.value.unshift(response.data.data);
        if (reviews.value.length > pagination.value.last_page * 10) {
          pagination.value.last_page++;
        }
        notify.success('Review publiée');
        pagination.value.total ++
      } catch (err) {
        const message = apiErrorMessage(err, 'La publication a échoué.');
        notify.error(message);
        throw new Error(message);
      }

      getKPI();
    }

    // Modifier une review
    async function updateReviews(id, data) {
      try {
        const response = await api.post(`/reviews/${id}`, data, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });

        const index = reviews.value.findIndex((element) => element.id === id);
        if (index !== -1) reviews.value[index] = response.data.data;
        notify.success('Review mise à jour');
      } catch (err) {
        const message = apiErrorMessage(err, 'La mise à jour a échoué.');
        notify.error(message);
        throw new Error(message);
      }
    }

    //  Supprimer une review
    async function deleteReviews(id) {
      try {
        await api.delete(`/reviews/${id}`);
        reviews.value = reviews.value.filter((element) => element.id !== id);
        if (reviews.value.length < pagination.value.last_page * 10) {
          pagination.value.last_page--;
          if (pagination.value.current_page > pagination.value.last_page) {
            pagination.value.current_page = pagination.value.last_page;
          }
        }
        getReviews(pagination.value.current_page);
        // pagination.value.total --
      } catch (err) {
        const message = apiErrorMessage(err, 'La suppression a échoué.');
        notify.error(message);
        throw new Error(message);
      }
      getKPI();
    }

    async function reactToReview(reviewId, reaction) {
      try {
        const response = await api.post(`/reviews/${reviewId}/react`, { reaction });

        const index = reviews.value.findIndex((r) => r.id === reviewId);
        if (index !== -1) {
          reviews.value[index].nb_like = response.data.nb_like;
          reviews.value[index].nb_dislike = response.data.nb_dislike;
          reviews.value[index].user_reaction = response.data.user_reaction;
        }

        return response;
      } catch (_err) {
        // Silent error handling
      }
    }

    /**
     * Rechercher un élément depuis le backend
     * Avantage : recherche facile et filtres faciles àa appliquer avec pagination
     * @param {*} query
     */
    async function searchReviews(query) {
      loading.value = true;
      try {
        const response = await api.get(`/reviews?search=${encodeURIComponent(query)}`);
        reviews.value = response.data.data.data || [];
        error.value = null;
        search.value = true;

      } catch (err) {
        error.value = extractErrorMessage(
          err,
          'Impossible de lancer la recherche pour le moment.'
        );
      } finally {
        loading.value = false;
      }
    }

    /**
     * Filtrer les reviews depuis le backend
     * @param {Object} filters - { noAnswers, noViews, noLikes, sortBy, tags }
     */
    async function filterReviews({ noAnswers, noViews, noLikes, sortBy, tags }) {
      loading.value = true;
      try {
        // Construction des paramètres de requête
        const params = {};
        if (noAnswers) params.noAnswers = 1;
        if (noViews) params.noViews = 1;
        if (noLikes) params.noLikes = 1;
        if (sortBy) params.sort = sortBy;
        if (tags && tags.trim() !== '') params.tags = tags;

        const queryString = new URLSearchParams(params).toString();
        const response = await api.get(`/reviews${queryString ? '?' + queryString : ''}`);
        reviews.value = response.data.data.data || [];
        error.value = null;
        search.value = true;
      } catch (err) {
        error.value = extractErrorMessage(err, 'Impossible d\'appliquer les filtres pour le moment.');
      } finally {
        loading.value = false;
      }
    }

    function clearError() {
      error.value = null;
    }

    /**
     * Fonction pour factoriser la gestion des messages d'erreur
     * @param {*} err
     * @param {*} default_message
     * @returns
     */
    function extractErrorMessage(err, default_message) {
      if (err.response && err.response.data && err.response.data.message) {
        return err.response.data.message;
      }
      if (err.message) {
        return err.message;
      }
      return default_message;
    }

    return {
      createReviews,
      updateReviews,
      deleteReviews,
      getReviews,
      getReview,
      searchReviews,
      filterReviews,
      reviews,
      error,
      loading,
      actualPage,
      kpi,
      maxRange,
      search,
      getKPI,
      clearError,
      reactToReview,
      pagination,
    };
  },
  {
    persist: true,
  }
);
