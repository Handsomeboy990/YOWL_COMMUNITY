<template>
  <!-- header -->
  <Header />

  <!-- Profil -->
  <div class="container mx-auto p-6 pt-20">
    <UserProfilData />

    <!-- Onglets -->
    <div class="flex space-x-4 mb-10">
      <router-link to="/user/summary"
        class="px-6 py-2 rounded-lg font-roboto text-[14px] text-white bg-blue-night hover:bg-orange-primary transition">
        Résumé
      </router-link>
      <router-link to="/user/my-reviews"
        class="px-6 py-2 rounded-lg font-roboto text-[14px] text-white bg-orange-primary hover:bg-orange-primary-dark transition">
        Mes reviews
      </router-link>
      <router-link to="/user/activity"
        class="px-6 py-2 rounded-lg font-roboto text-[14px] text-white bg-blue-night hover:bg-orange-primary transition">
        Activité
      </router-link>
    </div>

    <!-- Etat vide -->
    <div v-if="posts.length === 0" class="flex flex-col items-center justify-center text-center py-16">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-24 h-24 mb-6 text-gray-400" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18M8 3v18M16 3v18" />
      </svg>
      <h2 class="text-2xl font-semibold text-gray-800">Aucune review pour le moment</h2>
      <p class="mt-2 text-gray-700 text-md max-w-md">
        Tu n'as encore rien publié. Lance-toi et partage ton premier avis !
      </p>
    </div>

    <!-- Liste des reviews -->
    <div class="grid lg:grid-cols-3 md:grid-cols-1 gap-6">
      <div v-for="review in posts" :key="review.id"
        class="bg-gray-100 rounded-lg border-4 border-orange-primary p-6">
        <!-- En-tête -->
        <header>
          <div class="flex justify-between items-center mb-2">
            <span class="text-blue-night text-sm">{{ formatDate(review.created_at) }}</span>
            <div class="flex gap-x-2">
              <button
                class="cursor-pointer text-white p-2 rounded-full bg-blue-night transition-all hover:-translate-y-1 duration-200"
                aria-label="Modifier la review"
                @click="openEditModal(review)">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button
                class="cursor-pointer text-white rounded-full p-2 bg-red-500 transition-all hover:-translate-y-1 duration-200"
                aria-label="Supprimer la review"
                @click="deletePost(review.id)">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </div>
        </header>

        <!-- Contenu -->
        <div class="flex flex-row md:flex-row gap-4">
          <div v-if="getMedias(review).length && !review.link" class="w-1/2 h-50 object-cover rounded-lg">
            <ImageCarousel :images="getMedias(review)" />
          </div>

          <div v-if="review.link && !getMedias(review).length" class="w-1/2 h-50 object-cover rounded-lg">
            <div class="border rounded-lg overflow-hidden">
              <iframe :src="review.link" class="w-full h-45 border-0" title="Aperçu du contenu"
                sandbox="allow-same-origin allow-scripts allow-popups" referrerpolicy="no-referrer"></iframe>
              <a :href="review.link" target="_blank" rel="noopener"
                class="text-orange-primary text-[10px] flex items-center justify-center hover:underline ml-1">
                Voir le contenu dont je parle
              </a>
            </div>
          </div>

          <div v-if="getMedias(review).length && review.link" class="w-1/2 h-50 object-cover rounded-lg">
            <ImageCarousel :images="getMedias(review)" />
            <a :href="review.link" target="_blank" rel="noopener"
              class="text-orange-primary text-[10px] flex items-center justify-center hover:underline ml-1">
              Voir le contenu dont je parle
            </a>
          </div>

          <div class="flex-1 max-h-50 overflow-y-auto h-50">
            <p class="text-gray-900">{{ review.content }}</p>
          </div>
        </div>

        <!-- Statistiques -->
        <footer class="flex items-center justify-between pt-4 border-t border-gray-200">
          <div class="flex items-center space-x-4 text-blue-night text-sm">
            <span class="flex items-center gap-1.5">
              <i class="fa-solid fa-thumbs-up text-orange-primary"></i> {{ review.nb_like }}
            </span>
            <span class="flex items-center gap-1.5">
              <i class="fa-solid fa-thumbs-down text-blue-night"></i> {{ review.nb_dislike }}
            </span>
            <span class="flex items-center gap-1.5">
              <i class="fa-regular fa-eye"></i> {{ review.nb_views }}
            </span>
          </div>

          <router-link :to="{ name: 'review-detail', params: { id: review.id } }"
            class="text-blue-night hover:underline text-sm">
            {{ review.comments?.length || 0 }} commentaires
          </router-link>
        </footer>
      </div>
    </div>

    <!-- Pagination -->
    <Pagination v-if="reviewStore.pagination.total > 10" :pagination="reviewStore.pagination" @changePage="getPage" />

    <!-- Quitter la communauté -->
    <LeaveCommunity />
  </div>

  <!-- Modale d'ajout / édition -->
  <AddReviewModal :isOpen="isModalOpen" :editedReview="selectedReview" @close="closeModal" @publish="addPost"
    @update="updatePost" />

  <!-- footer -->
  <Footer />
</template>

<script setup>
import Header from '@/components/layouts/Header.vue';
import Footer from '@/components/layouts/Footer.vue';
import { computed, ref, onMounted } from 'vue';
import Pagination from '@/components/layouts/Pagination.vue';
import UserProfilData from '@/components/layouts/UserProfilData.vue';
import LeaveCommunity from '@/components/layouts/LeaveCommunity.vue';
import AddReviewModal from '@/components/layouts/AddReviewModal.vue';
import { useReviewStore } from '@/stores/review';
import { useUserStore } from '@/stores/user';
import ImageCarousel from '@/components/layouts/ImageCarouselMyPost.vue';
import Swal from 'sweetalert2';

const reviewStore = useReviewStore();
const userStore = useUserStore();

// Reviews de l'utilisateur connecté
const posts = computed(() =>
  reviewStore.reviews.filter((review) => review.user_id === userStore.user?.id)
);

const isModalOpen = ref(false);
const selectedReview = ref(null);

const formatDate = (value) =>
  new Date(value).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });

onMounted(async () => {
  await reviewStore.getReviews();
});

// Contrôles de la modale
const openEditModal = (post) => {
  selectedReview.value = { ...post };
  isModalOpen.value = true;
};

const closeModal = () => {
  selectedReview.value = null;
  isModalOpen.value = false;
};

// Ajouter, modifier, supprimer
const addPost = async (reviewData) => {
  await reviewStore.createReviews(reviewData);
  closeModal();
};

const updatePost = async (updatedReview) => {
  if (!selectedReview.value) return;
  await reviewStore.updateReviews(selectedReview.value.id, updatedReview);
  closeModal();
};

const deletePost = async (reviewId) => {
  Swal.fire({
    title: 'Confirmer la suppression',
    text: 'Veux-tu vraiment supprimer cette review ?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#FF6B35',
    cancelButtonColor: '#1E2A38',
    confirmButtonText: 'Oui, supprimer',
    cancelButtonText: 'Annuler',
  }).then(async (result) => {
    if (result.isConfirmed) {
      await reviewStore.deleteReviews(reviewId);
      Swal.fire({
        title: 'Supprimée !',
        text: 'La review a été supprimée.',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false,
      });
    }
  });
};

const getMedias = (review) => {
  if (!review.medias) return [];
  try {
    if (Array.isArray(review.medias)) return review.medias;
    return JSON.parse(review.medias);
  } catch {
    return [];
  }
};

// Pagination
const getPage = async (page) => {
  await reviewStore.getReviews(page);
};
</script>
