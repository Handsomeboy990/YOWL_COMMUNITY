<template>

  <Header />
  <div class="w-3/4 ml-50 pt-20 p-4 pb-50">
    <!-- Back link -->
    <router-link to="/" class="flex items-center gap-2 text-gray-600 hover:text-black mb-4">
      <i class="fa-solid fa-arrow-left"></i>Back to feed
    </router-link>

    <div v-if="review" class="bg-gray-100 rounded-lg border-2 border-[#FF6B35] p-6">
      <!-- review Header -->
      <header class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-3">
          <div v-if="review.user?.picture">
            <img class="w-10 h-10 rounded-full flex items-center justify-center"
              :src="`http://localhost:8000/storage/${review.user.picture}`" alt="profile picture">

          </div>
          <div v-else class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
            <i fill-rule="evenodd" class="fa-solid fa-user w-5 h-5 text-gray-600" fill="currentColor"
              viewBox="0 0 20 20" clip-rule="evenodd"></i>

          </div>
          <div>
            <p class="font-roboto font-medium text-gray-900">{{ review.user?.username }}</p>
            <p class="font-roboto text-caption text-[12px] text-gray-500">{{ dateFormatted }}</p>
          </div>
        </div>
        <p class="font-roboto text-caption text-gray-500">{{ review.nb_views }} views</p>
      </header>

      <!-- Content -->
      <div class="flex flex-row md:flex-row gap-4">
        <!-- Images -->
        <div v-if="mediasArray.length && !review.link" class="w-1/2 h-100 rounded-lg">
          <ImageCarousel :images="mediasArray" />
        </div>

        <!-- Link Preview -->
        <div v-if="review.link && !mediasArray.length" class="w-1/2 h-100 object-cover rounded-lg">
          <div class="border rounded-lg overflow-hidden">
            <iframe :src="review.link" class="w-full h-95 border-0" referrerpolicy="no-referrer"></iframe>
            <a :href="review.link" target="_blank"
              class="text-[#FF6B35] text-[17px] flex items-center justify-center hover:underline ml-1">click here to see
              what I'm telling
            </a>
          </div>
        </div>

        <div v-if="review.link && mediasArray.length" class="w-1/2 h-100 object-cover rounded-lg">
          <ImageCarousel :images="mediasArray" />
          <a :href="review.link" target="_blank"
            class="text-[#FF6B35] text-[17px] flex items-center justify-center hover:underline ml-1">click here to see
            what I'm telling
          </a>
        </div>

        <div class="flex-1 max-h-100 overflow-y-auto">
          <h1 class="font-bold mb-2">{{ review.title }}</h1>
          <p class="text-gray-900">{{ review.content }}</p>
        </div>
      </div>

      <!-- review Actions -->
      <footer class="flex items-center justify-between pt-4 border-t border-gray-200">
        <div class="flex items-center space-x-4">
          <!-- Like -->
          <button @click="toggleReaction('like')" :class="[
            'cursor-pointer hover:translate-y-[-3px] flex items-center space-x-1 transition-colors duration-200',
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
            </div>

                        {{ review.nb_like }}
                    </button>

                    <!-- Dislike -->
                    <button @click="toggleReaction('dislike')" :class="[
                        'cursor-pointer hover:translate-y-[3px] flex items-center space-x-1 transition-colors duration-200',
                        review.nb_dislike
                            ? 'text-blue-700'
                            : 'text-gray-500 hover:text-blue-500'
                    ]">
                        <div class="w-8 h-8 bg-[#1E2A38] mr-2 rounded-full flex items-center justify-center">
                            <i :class="[
                                review.nb_dislike
                                    ? 'fa-solid fa-thumbs-down'
                                    : 'fa-regular fa-thumbs-down',
                                'w-4 h-4 text-white'
                            ]"></i>
                        </div>
                        {{ review.nb_dislike }}
                    </button>
                </div>
            </footer>

            <!-- Comments -->
            <CommentList @child-changed="refresh" :comments="review.comments" :reviewId="reviewId" />
        </div>
    </div>
    <Footer />
</template>

<script setup>
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

    } catch (error) {
        console.error("Erreur lors de la réaction :", error)
    }
}

</script>
