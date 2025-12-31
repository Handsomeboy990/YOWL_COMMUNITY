<template>
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 md:p-6 transition-all duration-300 hover:shadow-lg hover:scale-[1.01] animate-fade-in-up">
    <!-- review Header -->
    <header class="flex items-center justify-between mb-4">
      <div class="flex items-center space-x-3">
        <div v-if="review.user.picture" class="relative group">
          <img class="w-10 h-10 md:w-12 md:h-12 rounded-full object-cover ring-2 ring-transparent group-hover:ring-[#FF6B35] transition-all duration-300"
            :src="getStorageUrl(review.user.picture)" alt="profile picture">
          <div class="absolute inset-0 rounded-full bg-black opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
        </div>
        <div v-else class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-gray-200 to-gray-300 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
          <i class="fa-solid fa-user w-5 h-5 text-gray-600"></i>
        </div>
        <div>
          <p class="font-roboto font-semibold text-gray-900 text-sm md:text-base hover:text-[#FF6B35] transition-colors cursor-pointer">{{ review.user.username }}</p>
          <p class="font-roboto text-xs text-gray-500">{{ dateFormatted }}</p>
        </div>
      </div>
      <div class="flex items-center gap-1 text-gray-500 text-xs md:text-sm">
        <i class="fa-regular fa-eye"></i>
        <span>{{ review.nb_views }}</span>
      </div>
    </header>

    <!-- review Content -->
    <p class="font-roboto text-sm md:text-base text-gray-700 mb-3 line-clamp-3">
      {{ review.content }}
    </p>
    <router-link :to="{ name: 'review-detail', params: { id: review.id } }"
      class="text-[#FF6B35] hover:text-[#ff5522] font-medium text-sm transition-colors duration-200 inline-flex items-center gap-1">
      See more
      <i class="fa-solid fa-arrow-right text-xs"></i>
    </router-link>

    <!-- review Image -->
    <div class="my-4">
      <ImageCarousel v-if="mediasArray.length && !review.link" :images="mediasArray" />

      <!-- Link Preview -->
      <div v-if="review.link && !mediasArray.length" class="w-full rounded-lg overflow-hidden">
        <div class="border border-gray-200 rounded-lg overflow-hidden hover:border-[#FF6B35] transition-colors duration-300">
          <iframe :src="review.link" class="w-full h-64 md:h-80 border-0" referrerpolicy="no-referrer"></iframe>
          <a :href="review.link" target="_blank"
            class="text-[#FF6B35] text-sm md:text-base flex items-center justify-center gap-2 py-3 bg-gray-50 hover:bg-orange-50 transition-colors duration-200">
            <i class="fa-solid fa-external-link"></i>
            Click here to see what I'm telling
          </a>
        </div>
      </div>

      <div v-if="mediasArray.length && review.link">
        <ImageCarousel :images="mediasArray" />
        <a :href="review.link" target="_blank"
          class="text-[#FF6B35] text-sm md:text-base flex items-center justify-center gap-2 py-3 hover:underline mt-2">
          <i class="fa-solid fa-external-link"></i>
          Click here to see what I'm telling
        </a>
      </div>
    </div>

    <!-- review Actions -->
    <footer class="flex items-center justify-between pt-4 border-t border-gray-100">
      <div class="flex items-center space-x-3 md:space-x-4">
        <!-- Like -->
        <button @click="toggleReaction('like')" :class="[
          'group flex items-center gap-2 transition-all duration-300 hover:scale-110',
          reviewStore.reviews[index]?.user_reaction === 'like'
            ? 'text-[#FF6B35]'
            : 'text-gray-600 hover:text-[#FF6B35]'
        ]">
          <div class="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-br from-[#FF6B35] to-[#ff8c5a] rounded-full flex items-center justify-center shadow-sm group-hover:shadow-md transition-all duration-300 group-active:scale-95">
            <i :class="[
              reviewStore.reviews[index]?.user_reaction === 'like'
                ? 'fa-solid fa-thumbs-up'
                : 'fa-regular fa-thumbs-up',
              'w-4 h-4 text-white'
            ]"></i>
          </div>
          <span class="font-medium text-sm md:text-base">{{ review.nb_like }}</span>
        </button>

        <!-- Dislike -->
        <button @click="toggleReaction('dislike')" :class="[
          'group flex items-center gap-2 transition-all duration-300 hover:scale-110',
          reviewStore.reviews[index]?.user_reaction === 'dislike'
            ? 'text-blue-600'
            : 'text-gray-600 hover:text-blue-600'
        ]">
          <div class="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-br from-[#1E2A38] to-[#344155] rounded-full flex items-center justify-center shadow-sm group-hover:shadow-md transition-all duration-300 group-active:scale-95">
            <i :class="[
              reviewStore.reviews[index]?.user_reaction === 'dislike'
                ? 'fa-solid fa-thumbs-down'
                : 'fa-regular fa-thumbs-down',
              'w-4 h-4 text-white'
            ]"></i>
          </div>
          <span class="font-medium text-sm md:text-base">{{ review.nb_dislike }}</span>
        </button>

        <!-- Reply -->
        <router-link :to="{ name: 'review-detail', params: { id: review.id } }"
          class="group flex items-center gap-2 text-gray-600 hover:text-[#FF6B35] transition-all duration-300">
          <div class="w-8 h-8 md:w-10 md:h-10 bg-gray-100 group-hover:bg-orange-50 rounded-full flex items-center justify-center transition-all duration-300">
            <i class="fa-solid fa-reply w-4 h-4"></i>
          </div>
          <span class="font-medium text-sm md:text-base hidden sm:inline">Reply</span>
        </router-link>
      </div>

      <router-link :to="{ name: 'review-detail', params: { id: review.id } }"
        class="flex items-center gap-2 text-gray-600 hover:text-[#FF6B35] transition-colors duration-200 text-sm md:text-base">
        <i class="fa-regular fa-comment"></i>
        <span class="font-medium">{{ review.comments?.length || 0 }}</span>
      </router-link>
    </footer>
  </div>
</template>

<script setup>
import { onMounted, ref, computed, watch } from 'vue'
import { useUserStore } from '@/stores/user'
import { useReviewStore } from '@/stores/review'
import Swal from 'sweetalert2';
import ImageCarousel from '../layouts/ImageCarousel.vue'
import { getStorageUrl } from '@/config'

const dateFormatted = ref("")



// Format de date

onMounted(() => {
  const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]
  const days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"]
  const d = new Date(localReview.value.created_at)
  dateFormatted.value = `${days[d.getDay()]} ${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()} at ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`
})

// Décoder le champ medias (JSON string -> array)
const mediasArray = computed(() => {
  if (!props.review.medias) return [];
  try {
    if (Array.isArray(props.review.medias)) return props.review.medias;
    return JSON.parse(props.review.medias);
  } catch {
    return [];
  }
});


// Props
const props = defineProps({
  review: {
    type: Object,
    required: true
  }
})

// Stores
const reviewStore = useReviewStore()
const userStore = useUserStore()

const index = reviewStore.reviews.findIndex(review => review.id == props.review.id)



// Local copy pour la réactivité
const localReview = ref({ ...props.review })

// Watch pour mettre à jour si props change
watch(
  props.review,
  (newReview) => {
    localReview.value = { ...newReview }
  }
)



// Fonction Like / Dislike
const toggleReaction = async (reaction) => {
  if (!userStore.user?.id) {
    Swal.fire({
      icon: 'error',
      title: 'Oops...',
      text: "You must be logged in to react.",
      showConfirmButton: true,
      confirmButtonColor: "#FF6B35"
    });    
    return
  }

  try {
    const response = await reviewStore.reactToReview(localReview.value.id, reaction)
    // Mise à jour instantanée
    reviewStore.reviews[index].nb_like = response.data.nb_like
    reviewStore.reviews[index].nb_dislike = response.data.nb_dislike
    reviewStore.reviews[index].user_reaction = response.data.user_reaction

    /* localReview.value = {
      ...localReview.value,
      nb_like: response.data.nb_like,
      nb_dislike: response.data.nb_dislike,
      user_reaction: response.data.user_reaction
    } */
  } catch {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Failed to react to this review. Please try again.',
      confirmButtonColor: "#FF6B35"
    });
  }
}
</script>
