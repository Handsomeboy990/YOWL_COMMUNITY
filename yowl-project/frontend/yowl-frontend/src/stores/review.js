import { computed, ref } from 'vue';
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

    /**
     * Etat unique de la requete du fil.
     *
     * La recherche, les filtres et le tri partaient auparavant dans trois
     * fonctions separees qui s'ecrasaient : chercher effacait les filtres,
     * filtrer effacait la recherche, et ni l'une ni l'autre ne mettait la
     * pagination a jour, si bien que le pied de page continuait d'annoncer
     * les pages de la liste precedente. Tout passe maintenant par un seul
     * objet, envoye tel quel a l'API, qui sait deja combiner ces criteres.
     */
    const query = ref({
      search: '',
      tags: '',
      sort: 'newest',
      noAnswers: false,
      noViews: false,
      noLikes: false,
      page: 1,
    });

    const hasActiveFilters = computed(
      () =>
        Boolean(query.value.search) ||
        Boolean(query.value.tags) ||
        query.value.noAnswers ||
        query.value.noViews ||
        query.value.noLikes ||
        query.value.sort !== 'newest'
    );

    function buildParams() {
      const params = { page: query.value.page };
      if (query.value.search.trim()) params.search = query.value.search.trim();
      if (query.value.tags.trim()) params.tags = query.value.tags.trim();
      if (query.value.sort) params.sort = query.value.sort;
      if (query.value.noAnswers) params.noAnswers = 1;
      if (query.value.noViews) params.noViews = 1;
      if (query.value.noLikes) params.noLikes = 1;
      return params;
    }

    // Une reponse arrivee apres une requete plus recente ne doit pas ecraser
    // l'affichage : chaque appel porte un numero, seul le dernier compte.
    let requestId = 0;

    async function fetchReviews() {
      const current = ++requestId;
      loading.value = true;
      error.value = null;
      try {
        const response = await api.get('/reviews', { params: buildParams() });
        if (current !== requestId) return;

        const payload = response.data.data;
        reviews.value = payload.data ?? [];
        pagination.value = {
          current_page: payload.current_page,
          last_page: payload.last_page,
          total: payload.total,
        };
        actualPage.value = payload.current_page;
        search.value = hasActiveFilters.value;
      } catch (err) {
        if (current !== requestId) return;
        error.value = extractErrorMessage(err, 'Impossible de charger le fil pour le moment.');
      } finally {
        if (current === requestId) loading.value = false;
      }
      getKPI();
    }

    // La frappe ne declenche pas un appel par caractere.
    let debounceTimer = null;
    function fetchDebounced(delay = 350) {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => fetchReviews(), delay);
    }

    /** Modifie un ou plusieurs criteres et repart de la premiere page. */
    function setQuery(patch, { immediate = false } = {}) {
      query.value = { ...query.value, ...patch, page: 1 };
      immediate ? fetchReviews() : fetchDebounced();
    }

    function goToPage(page) {
      query.value.page = page;
      fetchReviews();
    }

    function resetQuery() {
      query.value = {
        search: '',
        tags: '',
        sort: 'newest',
        noAnswers: false,
        noViews: false,
        noLikes: false,
        page: 1,
      };
      fetchReviews();
    }

    /** Compatibilite : le chargement initial passe encore par ici. */
    async function getReviews(page = 1) {
      query.value.page = Number(page) || 1;
      await fetchReviews();
    }

    //  Récupérer une review
    async function getReview(id) {
      try {
        const response = await api.get(`/reviews/${id}`);
        const index = reviews.value.findIndex((element) => element.id == id);
        // La review peut ne pas etre dans la page chargee : ecrire dans
        // reviews.value[-1] levait une erreur avalee par le catch.
        if (index !== -1) {
          reviews.value[index].nb_views = response.data.data.nb_views;
        }
      } catch {
        // Le compteur de vues reste sur sa derniere valeur connue.
      }
      getKPI();
    }

    //  Créer un avis
    async function createReviews(data) {
      try {
        await api.post('/reviews', data, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        notify.success('Avis publié');
        // On recharge depuis le serveur plutot que de bricoler la liste et la
        // pagination a la main, ce qui les laissait incoherentes des qu'un
        // filtre etait actif.
        await fetchReviews();
      } catch (err) {
        const message = apiErrorMessage(err, 'La publication a échoué.');
        notify.error(message);
        throw new Error(message);
      }
    }

    // Modifier un avis
    async function updateReviews(id, data) {
      try {
        const response = await api.post(`/reviews/${id}`, data, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        const index = reviews.value.findIndex((element) => element.id === id);
        if (index !== -1) reviews.value[index] = response.data.data;
        notify.success('Avis mis à jour');
      } catch (err) {
        const message = apiErrorMessage(err, 'La mise à jour a échoué.');
        notify.error(message);
        throw new Error(message);
      }
    }

    //  Supprimer un avis
    async function deleteReviews(id) {
      try {
        await api.delete(`/reviews/${id}`);
        await fetchReviews();
      } catch (err) {
        const message = apiErrorMessage(err, 'La suppression a échoué.');
        notify.error(message);
        throw new Error(message);
      }
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
      } catch (err) {
        notify.error(apiErrorMessage(err, 'La réaction a échoué.'));
        throw err;
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
      query,
      hasActiveFilters,
      fetchReviews,
      setQuery,
      goToPage,
      resetQuery,
    };
  },
  {
    persist: true,
  }
);
