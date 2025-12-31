<template>
  <Header />
  
  <div class="min-h-screen bg-gray-50 pt-20 pb-8">
    <div class="container mx-auto px-4 max-w-5xl">
      
      <!-- Back link -->
      <router-link to="/" class="inline-flex items-center gap-2 text-gray-600 hover:text-[#FF6B35] mb-6 transition-colors font-medium">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Back to feed</span>
      </router-link>

      <!-- Review Card -->
      <div v-if="review" class="bg-white rounded-xl border border-gray-200 shadow-lg p-4 md:p-6 lg:p-8 animate-fade-in-up">
        
        <!-- Review Header -->
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 pb-4 border-b border-gray-100">
          <div class="flex items-center gap-3">
            <div v-if="review.user?.picture" class="relative group">
              <img class="w-12 h-12 md:w-14 md:h-14 rounded-full object-cover ring-2 ring-gray-200 group-hover:ring-[#FF6B35] transition-all"
                :src="getStorageUrl(review.user.picture)" alt="profile picture">
            </div>
            <div v-else class="w-12 h-12 md:w-14 md:h-14 bg-gradient-to-br from-gray-200 to-gray-300 rounded-full flex items-center justify-center">
              <i class="fa-solid fa-user text-gray-600 text-xl"></i>
            </div>
            <div>
              <p class="font-semibold text-gray-900 text-base md:text-lg">{{ review.user?.username }}</p>
              <p class="text-xs md:text-sm text-gray-500">{{ dateFormatted }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2 text-gray-500 text-sm">
            <i class="fa-regular fa-eye"></i>
            <span>{{ review.nb_views }} views</span>
          </div>
        </header>

        <!-- Content -->
        <div class="space-y-6">
          
          <!-- Title if exists -->
          <h1 v-if="review.title" class="text-2xl md:text-3xl font-bold text-gray-900">{{ review.title }}</h1>
          
          <!-- Media & Text Layout -->
          <div class="flex flex-col lg:flex-row gap-6">
            
            <!-- Images -->
            <div v-if="mediasArray.length && !review.link" class="w-full lg:w-1/2">
              <div class="rounded-xl overflow-hidden shadow-md">
                <ImageCarousel :images="mediasArray" />
              </div>
            </div>

            <!-- Link Preview -->
            <div v-if="review.link && !mediasArray.length" class="w-full lg:w-1/2">
              <div class="border border-gray-200 rounded-xl overflow-hidden shadow-md hover:border-[#FF6B35] transition-colors">
                <iframe :src="review.link" class="w-full h-64 md:h-80 lg:h-96 border-0" referrerpolicy="no-referrer"></iframe>
                <a :href="review.link" target="_blank"
                  class="text-[#FF6B35] text-sm md:text-base flex items-center justify-center gap-2 py-3 bg-gray-50 hover:bg-orange-50 transition-colors font-medium">
                  <i class="fa-solid fa-external-link"></i>
                  Click here to see what I'm telling
                </a>
              </div>
            </div>

            <!-- Both Images and Link -->
            <div v-if="review.link && mediasArray.length" class="w-full lg:w-1/2">
              <div class="rounded-xl overflow-hidden shadow-md mb-3">
                <ImageCarousel :images="mediasArray" />
              </div>
              <a :href="review.link" target="_blank"
                class="text-[#FF6B35] text-sm md:text-base flex items-center justify-center gap-2 hover:underline font-medium">
                <i class="fa-solid fa-external-link"></i>
                Click here to see what I'm telling
              </a>
            </div>

            <!-- Text Content -->
            <div class="flex-1">
              <div class="prose prose-sm md:prose-base max-w-none">
                <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">{{ review.content }}</p>
              </div>
            </div>
          </div>

          <!-- Review Actions -->
          <footer class="flex flex-wrap items-center justify-between gap-4 pt-6 border-t border-gray-100">
            <div class="flex items-center gap-3 md:gap-4">
              <!-- Like -->
              <button @click="toggleReaction('like')" :class="[
                'group flex items-center gap-2 transition-all duration-300 hover:scale-110',
                review.user_reaction === 'like'
                  ? 'text-[#FF6B35]'
                  : 'text-gray-600 hover:text-[#FF6B35]'
              ]">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-[#FF6B35] to-[#ff8c5a] rounded-full flex items-center justify-center shadow-sm group-hover:shadow-md transition-all group-active:scale-95">
                  <i :class="[
                    review.user_reaction === 'like'
                      ? 'fa-solid fa-thumbs-up'
                      : 'fa-regular fa-thumbs-up',
                    'w-5 h-5 text-white'
                  ]"></i>
                </div>
                <span class="font-semibold text-base md:text-lg">{{ review.nb_like }}</span>
              </button>

              <!-- Dislike -->
              <button @click="toggleReaction('dislike')" :class="[
                'group flex items-center gap-2 transition-all duration-300 hover:scale-110',
                review.user_reaction === 'dislike'
                  ? 'text-blue-600'
                  : 'text-gray-600 hover:text-blue-600'
              ]">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-[#1E2A38] to-[#344155] rounded-full flex items-center justify-center shadow-sm group-hover:shadow-md transition-all group-active:scale-95">
                  <i :class="[
                    review.user_reaction === 'dislike'
                      ? 'fa-solid fa-thumbs-down'
                      : 'fa-regular fa-thumbs-down',
                    'w-5 h-5 text-white'
                  ]"></i>
                </div>
                <span class="font-semibold text-base md:text-lg">{{ review.nb_dislike }}</span>
              </button>
            </div>

            <!-- Comments count -->
            <div class="flex items-center gap-2 text-gray-600 font-medium">
              <i class="fa-regular fa-comment text-lg"></i>
              <span class="text-base md:text-lg">{{ review.comments?.length || 0 }} Comments</span>
            </div>
          </footer>
        </div>

        <!-- Comments Section -->
        <div class="mt-8">
          <CommentList @child-changed="refresh" :comments="review.comments" :reviewId="reviewId" />
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="flex flex-col items-center justify-center py-20 animate-fade-in">
        <i class="fa-regular fa-file-lines text-6xl text-gray-300 mb-4"></i>
        <h2 class="text-xl font-semibold text-gray-700 mb-2">Review not found</h2>
        <p class="text-gray-500 mb-6">This review may have been deleted or doesn't exist.</p>
        <router-link to="/" class="px-6 py-3 bg-[#FF6B35] text-white rounded-lg hover:bg-[#ff5522] transition-colors font-medium">
          Go back to feed
        </router-link>
      </div>
    </div>
  </div>
  
  <Footer />
</template>

<script setup>
import { getStorageUrl } from '@/config';
import { onBeforeMount, ref, computed, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import Header from '../layouts/Header.vue'
import Footer from '../layouts/Footer.vue'
import CommentList from '../CommentList.vue'
import { useCommentStore } from '@/stores/comment'
import { useReviewStore } from '@/stores/review'
import ImageCarousel from '../layouts/ImageCarousel.vue'
import { useUserStore } from '@/stores/user'
import Swal from 'sweetalert2'
const route = useRoute()
const userStore = useUserStore();
const reviewStore = useReviewStore()
const dateFormatted = ref("")
// Irouter id
const reviewId = parseInt(route.params.id)
const review = ref({})
// const found = ref("false")

onBeforeMount(async() => {
    const store = useCommentStore()
    await store.getComments()

    review.value = reviewStore.reviews.filter(review => reviewId == review.id)[0]

    const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    const days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
    const d = new Date(review.value.created_at);
    dateFormatted.value = days[d.getDay()] + " " + d.getDate() + " " + months[d.getMonth()] + " " + d.getFullYear() + " at " + d.getHours() + ":" + d.getMinutes();
})

onBeforeUnmount(() => {
    reviewStore.getReview(review.value.id)    
})

const refresh = () => {
    review.value = reviewStore.reviews.filter(review => reviewId == review.id)[0]
}

const mediasArray = computed(() => {
    if (!review.value.medias) return [];
    try {
        if (Array.isArray(review.value.medias)) return review.value.medias;
        return JSON.parse(review.value.medias);
    } catch {
        return [];
    }
});

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
        const response = await reviewStore.reactToReview(review.value.id, reaction)

        // Mise à jour instantanée
        review.value.nb_like = response.data.nb_like
        review.value.nb_dislike = response.data.nb_dislike
        review.value.user_reaction = response.data.user_reaction

    } catch {
        // Silent error handling
    }
}

</script>
