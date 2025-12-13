<template>
  <div class="bg-gray-100 rounded-lg border-2 border-[#FF6B35] p-6">
    <!-- review Header -->
    <header class="flex items-center justify-between mb-4">
      <div class="flex items-center space-x-3">
        <div v-if="review.user.picture">
          <img class="w-10 h-10 rounded-full flex items-center justify-center"
            :src="`http://localhost:8000/storage/${review.user.picture}`" alt="profile picture">

        </div>
        <div v-else class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
          <i fill-rule="evenodd" class="fa-solid fa-user w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20"
            clip-rule="evenodd"></i>

        </div>
        <div>
          <p class="font-roboto font-medium text-gray-900">{{ review.user.username }}</p>
          <p class="font-roboto text-caption text-[12px] text-gray-500">{{ dateFormatted }}</p>
        </div>
      </div>
      <p class="font-roboto text-caption text-gray-500">{{ review.nb_views }} views</p>
    </header>

    <!-- review Content -->
    <p class="font-roboto text-body text-gray-700 mb-4 line-clamp-3">
      {{ review.content }}

    </p>
    <router-link :to="{ name: 'review-detail', params: { id: review.id } }"
      class="text-[#FF6B35] hover:underline ml-1">See
      more</router-link>

    <!-- review Image -->
    <div class="mb-4">
      <ImageCarousel v-if="mediasArray.length && !review.link" :images="mediasArray" />

      <!-- Link Preview -->
      <div v-if="review.link && !mediasArray.length" class="w-full h-100 object-cover rounded-lg">
        <div class="border rounded-lg overflow-hidden">
          <iframe :src="review.link" class="w-full h-100 border-0" referrerpolicy="no-referrer"></iframe>
          <a :href="review.link" target="_blank"
            class="text-[#FF6B35] text-[17px] flex items-center justify-center hover:underline ml-1">click here to see
            what i'm telling
          </a>
        </div>
      </div>

      <div v-if="mediasArray.length && review.link" :images="mediasArray">
        <ImageCarousel  :images="mediasArray" />
        <a :href="review.link" target="_blank"
          class="text-[#FF6B35] text-[17px] flex items-center justify-center hover:underline ml-1">click here to see
          what i'm telling
        </a>
      </div>
    </div>

    <!-- review Actions -->
    <footer class="flex items-center justify-between pt-4 border-t border-gray-200">
      <div class="flex items-center space-x-4">
        <!-- Like -->
        <button @click="toggleReaction('like')" :class="[
          'cursor-pointer hover:translate-y-[-3px] flex items-center space-x-1 transition-colors duration-200',
          reviewStore.reviews[index].nb_like
            ? 'text-[#FF6B35]'
            : 'text-gray-500 hover:text-[#FF6B35]'
        ]">
          <div class="w-8 h-8 bg-orange-primary mr-2 rounded-full flex items-center justify-center">
            <i :class="[
              reviewStore.reviews[index].nb_like
                ? 'fa-solid fa-thumbs-up'
                : 'fa-regular fa-thumbs-up',
              'w-4 h-4 text-white'
            ]"></i>
          </div>

          {{ review.nb_like }}
        </button>

        <!-- Dislike -->
        <button @click="toggleReaction('dislike')" :class="[
          'cursor-pointer hover:translate-y-[3px] flex items-center space-x-1 transition-colors duration-200',
          reviewStore.reviews[index].nb_dislike
            ? 'text-blue-700'
            : 'text-gray-500 hover:text-blue-500'
        ]">
          <div class="w-8 h-8 bg-[#1E2A38] mr-2 rounded-full flex items-center justify-center">
            <i :class="[
              reviewStore.reviews[index].nb_dislike
                ? 'fa-solid fa-thumbs-down'
                : 'fa-regular fa-thumbs-down',
              'w-4 h-4 text-white'
            ]"></i>
          </div>
          {{ review.nb_dislike }}
        </button>

        <!-- Reply .-->
        <router-link :to="{ name: 'review-detail', params: { id: review.id } }"
          class="cursor-pointer flex items-center space-x-1 text-gray-500 hover:translate-y-[2px] transition-colors">
          <span class="font-roboto text-caption">Reply</span>
          <i fill-rule="evenodd" class="fa-solid fa-reply w-4 h-4" fill="currentColor" viewBox="0 0 20 20"
            clip-rule="evenodd"></i>
        </router-link>
      </div>

      <div class="flex items-center space-x-4">
        <router-link :to="{ name: 'review-detail', params: { id: review.id } }"
          class="cursor-pointer font-roboto text-caption text-blue-night hover:underline">
          <span class="font-roboto text-caption text-gray-500">Show </span>{{ review.comments?.length }}
          Comments
        </router-link>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { onMounted, ref, computed, watch } from 'vue'
import { useUserStore } from '@/stores/user'
import { useReviewStore } from '@/stores/review'
import Swal from 'sweetalert2';
import ImageCarousel from '../layouts/ImageCarousel.vue'

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
  } catch (error) {
    console.error("Erreur lors de la réaction :", error)
  }
}
</script>
