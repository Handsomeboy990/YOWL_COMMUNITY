import { ref } from 'vue';
import { defineStore } from 'pinia';
import api, { fetchToken } from '@/services/apiService';
import Swal from 'sweetalert2';

export const useReviewStore = defineStore(
  'reviews',
  () => {
    const reviews = ref([]);
    const error = ref(null);
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
        /* ageRangeArray.forEach(element => {

        });
        ageRange.value = Math.max.apply(
          Math,
          ageRangeArray.map(function (event) {
            return event.y;
          })
        ); */
      } catch (error) {
        console.log(error);
      }
    }

    //  Récupérer les reviews
    async function getReviews(page = 1) {
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

      } catch (error) {
        console.log(error);
      }
      getKPI();
    }

    //  Récupérer une review
    async function getReview(id) {
      try {
        await fetchToken();
        const response = await api.get(`/reviews/${id}`);
        const index = reviews.value.findIndex((element) => element.id == id);
        reviews.value[index].nb_views = response.data.data.nb_views
        console.log(response.data.data.nb_views)


      } catch (error) {
        console.log(error);
      }
      getKPI();
    }

    //  Créer une review
    async function createReviews(data) {
      try {
        await fetchToken();
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
        Swal.fire({
          icon: 'success',
          title: 'Review Published!',
          showConfirmButton: false,
          timer: 1500,
        })
        pagination.value.total ++
      } catch (error) {
        let message = 'Review creation failed';
        if (error.response?.data?.message) message = error.response.data.message;
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Review creation failed',
        });
        throw new Error(message);
      }

      getKPI();
    }

    // 🟩 Modifier une review
    async function updateReviews(id, data) {
      try {
        await fetchToken();
        const response = await api.post(`/reviews/${id}`, data, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });

        const index = reviews.value.findIndex((element) => element.id === id);
        if (index !== -1) reviews.value[index] = response.data.data;
        Swal.fire({
          icon: 'success',
          title: 'Review Updated!',
          showConfirmButton: false,
          timer: 1500,
        })
      } catch (error) {
        let message = 'Review update failed';
        if (error.response?.data?.message) message = error.response.data.message;
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Review update failed',
        });
        throw new Error(message);
      }
    }

    //  Supprimer une review
    async function deleteReviews(id) {
      try {
        await fetchToken();
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
      } catch (error) {
        let message = 'Review deletion failed';
        if (error.response?.data?.message) message = error.response.data.message;
        throw new Error(message);
      }
      getKPI();
    }

    async function reactToReview(reviewId, reaction) {
      try {
        await fetchToken();
        const response = await api.post(`/reviews/${reviewId}/react`, { reaction });

        const index = reviews.value.findIndex((r) => r.id === reviewId);
        if (index !== -1) {
          reviews.value[index].nb_like = response.data.nb_like;
          reviews.value[index].nb_dislike = response.data.nb_dislike;
          reviews.value[index].user_reaction = response.data.user_reaction;
        }

        return response;
      } catch (error) {
        console.error(' Erreur lors de la réaction :', error);
      }
    }

    /**
     * Rechercher un élément depuis le backend
     * Avantage : recherche facile et filtres faciles àa appliquer avec pagination
     * @param {*} query
     */
    async function searchReviews(query) {
      try {
        const response = await api.get(`/reviews?search=${encodeURIComponent(query)}`);
        reviews.value = response.data.data.data || [];
        error.value = null;
        search.value = true;

      } catch (err) {
        error.value = extractErrorMessage(
          err,
          'Unable to retrieve reviews. Please try again later.'
        );
        console.error(error.value);
      }
    }

    /**
     * Filtrer les reviews depuis le backend
     * @param {Object} filters - { noAnswers, noViews, noLikes, sortBy, tags }
     */
    async function filterReviews({ noAnswers, noViews, noLikes, sortBy, tags }) {
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
      } catch (err) {
        error.value = extractErrorMessage(err, 'Unable to filter reviews. Please try again later.');
        console.error(error.value);
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
