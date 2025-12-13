<template>
  <!-- header -->
  <Header />

  <!-- Profile Section -->
  <div class="container mx-auto p-6">
    <!-- User profile info -->
    <UserProfilData />

    <!-- Navigation Tabs -->
    <div class="flex space-x-4 mb-10">
      <router-link to="/user/summary"
        class="px-6 py-2 rounded-lg font-roboto text-[14px] text-white bg-blue-night hover:bg-[#FF6B35] transition">
        Summary
      </router-link>
      <router-link to="/user/my-reviews"
        class="px-6 py-2 rounded-lg font-roboto text-[14px] text-white bg-orange-primary hover:bg-[#FF6B35] transition">
        My reviews
      </router-link>
      <router-link to="/user/activity"
        class="px-6 py-2 rounded-lg font-roboto text-[14px] text-white bg-blue-night hover:bg-[#FF6B35] transition">
        Activity
      </router-link>
    </div>

    <div v-if="reviews.length === 0" class="flex flex-col items-center justify-center text-center pb-15 ml-5 ">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-24 h-24 mb-6 text-gray-400" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18M8 3v18M16 3v18" />
      </svg>
      <h2 class="text-2xl font-semibold text-gray-800">Aucun post pour le moment</h2>
      <p class="mt-2 text-gray-700 text-md max-w-md">
        Il n'y a encore aucun post à afficher. Dès qu'un utilisateur publiera, vous le verrez ici.
      </p>
    </div>
    <!-- posts lists -->
    <div class="grid lg:grid-cols-3 md:grid-cols-1 gap-6">
      <div v-for="review in posts.slice(0, posts.length > 10 ? 10 : posts.length + 1)" :key="review.id"
        class="bg-gray-100 rounded-lg border-4 border-[#FF6B35] p-6">
        <!-- posts Header -->
        <header>
          <div class="flex justify-between items-center mb-2">
            <span class="text-[#1E2A38] text-sm">{{ review.timeAgo }}</span>
            <div class="flex gap-x-2">
              <button @click="openEditModal(review)"
                class="cursor-pointer text-white p-2 rounded-full bg-[#1E2A38] transition-colors hover:translate-y-[-4px] duration-200">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button @click="deletePost(review.id)"
                class="cursor-pointer text-white rounded-full p-2 bg-red-500 transition-colors hover:translate-y-[-4px] duration-200">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </div>

        </header>



        <!-- Content -->
        <div class="flex flex-row md:flex-row gap-4">
          <div v-if="getMedias(review).length && !review.link" class="w-1/2 h-50 object-cover rounded-lg">
            <ImageCarousel :images="getMedias(review)" />
          </div>

          <div v-if="review.link && !getMedias(review).length" class="w-1/2 h-50 object-cover rounded-lg">
            <div class="border rounded-lg overflow-hidden">
              <iframe :src="review.link" class="w-full h-45 border-0" referrerpolicy="no-referrer"></iframe>
              <a :href="review.link" target="_blank"
                class="text-[#FF6B35] text-[10px] flex items-center justify-center hover:underline ml-1">click here to see what i'm telling
              </a>
            </div>
          </div>

          <div v-if="getMedias(review).length && review.link" class="w-1/2 h-50 object-cover rounded-lg">
            <ImageCarousel :images="getMedias(review)" />
            <a :href="review.link" target="_blank"
              class="text-[#FF6B35] text-[10px] flex items-center justify-center hover:underline ml-1">click here to see
              what i'm telling
            </a>
          </div>

          <div class="flex-1 max-h-50 overflow-y-auto h-50">
            <p class="text-gray-900">{{ review.content }}</p>
          </div>
        </div>

        <!-- posts Actions -->
        <footer class="flex items-center justify-between pt-4 border-t border-gray-200">
          <div class="flex items-center space-x-4">
            <!-- Like -->
            <button @click="reviewStore.reactToReview(review.id, 'like')" :class="[
              'cursor-pointer hover:translate-y-[3px] flex items-center space-x-1 transition-colors duration-200',
              review.nb_like
                ? 'text-[#FF6B35]'
                : 'text-gray-500 hover:text-[#FF6B35]'
            ]">
              <div class="w-8 h-8 bg-orange-primary mr-2 rounded-full flex items-center justify-center">
                <i :class="[
                  review.nb_like
                    ? 'fa-solid fa-thumbs-up'
                    : 'fa-regular fa-thumbs-up',
                  'w-4 h-4 text-white'
                ]"></i>
              </div>{{ review.nb_like }}
            </button>

            <!-- Dislike -->
            <button @click="reviewStore.reactToReview(review.id, 'dislike')" :class="[
              'cursor-pointer hover:translate-y-[3px] flex items-center space-x-1 transition-colors duration-200',
              review.nb_dislike
                ? 'text-blue-700'
                : 'text-gray-500 hover:text-blue-500'
            ]">
              <div class="w-8 h-8 bg-[#1E2A38] rounded-full flex items-center mr-2 justify-center">
                <i :class="[
                  review.nb_dislike
                    ? 'fa-solid fa-thumbs-down'
                    : 'fa-regular fa-thumbs-down',
                  'w-4 h-4 text-white'
                ]"></i>
              </div>{{ review.nb_dislike }}
            </button>
          </div>

          <!-- views and comments -->
          <div class="flex items-center space-x-50">
            <p class="text-[#1E2A38]">{{ review.views }}</p>
            <router-link :to="{ name: 'review-detail', params: { id: review.id } }"
              class="text-[#1E2A38] hover:underline">
              {{ review.comments?.length }} Comments
            </router-link>
          </div>
        </footer>
      </div>
    </div>

    <!-- PAGINATION -->
    <Pagination v-if="pagination.total > 10" :pagination="reviewStore.pagination" @changePage="getPage" />

    <!-- Leave Community -->
    <LeaveCommunity />
  </div>

  <!-- Add/Edit Review Modal -->
  <AddReviewModal :isOpen="isModalOpen" :editedReview="selectedReview" @close="closeModal" @publish="addPost"
    @update="updatePost" />

  <!-- footer -->
  <Footer />
</template>

<script setup>
import Header from '@/components/layouts/Header.vue';
import Footer from '@/components/layouts/Footer.vue';
import { computed, ref, onMounted, watch } from 'vue';
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

// Posts for the logged-in user
const posts = computed(() => reviewStore.reviews.filter(review => review.user_id === userStore.user.id));

const isModalOpen = ref(false);
const selectedReview = ref(null);
const totalNb = ref(0)

const pagination = ref({})
const reviews = ref([])


// Watch pour synchroniser automatiquement la liste et la pagination
watch(
  () => reviewStore.reviews,
  (newReviews) => {
    reviews.value = newReviews
    pagination.value = reviewStore.pagination
  },
  { immediate: true }
)

totalNb.value = posts.value.length

watch(reviewStore.reviews, () => {
  totalNb.value = posts.value.length
})

onMounted(async () => {
  await reviewStore.getReviews();

});

// Modal controls
const openEditModal = (post) => {
  selectedReview.value = { ...post };
  isModalOpen.value = true;
};

const closeModal = () => {
  selectedReview.value = null;
  isModalOpen.value = false;
};

// Add, Update, Delete
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
    title: "Confirm deletion",
    text: "Do you really want to delete this review ?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#FF6B35",
    cancelButtonColor: "#1E2A38",
    confirmButtonText: "Yes, delete"
  }).then(async (result) => {
    if (result.isConfirmed) {
      await reviewStore.deleteReviews(reviewId)

      Swal.fire({
        title: "Deleted!",
        text: "The comment has been deleted.",
        icon: "success",
        confirmButtonColor: "#FF6B35"
      });
    }
  });


}

const getMedias = (review) => {
  if (!review.medias) return [];
  try {
    if (Array.isArray(review.medias)) return review.medias;
    return JSON.parse(review.medias);
  } catch {
    return [];
  }
};


// Pagination handler
const getPage = async (page) => {
  await reviewStore.getReviews(page);
};
</script>
