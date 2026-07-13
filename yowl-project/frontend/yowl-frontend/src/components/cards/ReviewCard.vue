<template>
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 md:p-6 transition-all duration-300 hover:shadow-lg hover:scale-[1.01] animate-fade-in-up">
    <!-- En-tête -->
    <header class="flex items-center justify-between mb-4">
      <div class="flex items-center space-x-3">
        <div v-if="review.user?.picture" class="relative group">
          <img class="w-10 h-10 md:w-12 md:h-12 rounded-full object-cover ring-2 ring-transparent group-hover:ring-orange-primary transition-all duration-300"
            :src="getStorageUrl(review.user.picture)" alt="Photo de profil">
        </div>
        <div v-else class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-gray-200 to-gray-300 rounded-full grid place-items-center">
          <i class="fa-solid fa-user text-gray-600"></i>
        </div>
        <div>
          <p class="font-roboto font-semibold text-gray-900 text-sm md:text-base">{{ review.user?.username }}</p>
          <p class="font-roboto text-xs text-gray-500">{{ dateFormatted }}</p>
        </div>
      </div>
      <div class="flex items-center gap-1 text-gray-500 text-xs md:text-sm">
        <i class="fa-regular fa-eye"></i>
        <span>{{ review.nb_views }}</span>
      </div>
    </header>

    <!-- Contenu -->
    <p class="font-roboto text-sm md:text-base text-gray-700 mb-3 line-clamp-3">
      {{ review.content }}
    </p>
    <router-link :to="{ name: 'review-detail', params: { id: review.id } }"
      class="text-orange-primary hover:text-orange-primary-dark font-medium text-sm transition-colors duration-200 inline-flex items-center gap-1">
      Voir plus
      <i class="fa-solid fa-arrow-right text-xs"></i>
    </router-link>

    <!-- Médias -->
    <div class="my-4">
      <ImageCarousel v-if="mediasArray.length && !review.link" :images="mediasArray" />

      <!-- Aperçu du lien -->
      <div v-if="review.link && !mediasArray.length" class="w-full rounded-lg overflow-hidden">
        <div class="border border-gray-200 rounded-lg overflow-hidden hover:border-orange-primary transition-colors duration-300">
          <iframe :src="review.link" class="w-full h-64 md:h-80 border-0" title="Aperçu du contenu"
            sandbox="allow-same-origin allow-scripts allow-popups" referrerpolicy="no-referrer"></iframe>
          <a :href="review.link" target="_blank" rel="noopener"
            class="text-orange-primary text-sm md:text-base flex items-center justify-center gap-2 py-3 bg-gray-50 hover:bg-orange-50 transition-colors duration-200">
            <i class="fa-solid fa-external-link"></i>
            Voir le contenu dont je parle
          </a>
        </div>
      </div>

      <div v-if="mediasArray.length && review.link">
        <ImageCarousel :images="mediasArray" />
        <a :href="review.link" target="_blank" rel="noopener"
          class="text-orange-primary text-sm md:text-base flex items-center justify-center gap-2 py-3 hover:underline mt-2">
          <i class="fa-solid fa-external-link"></i>
          Voir le contenu dont je parle
        </a>
      </div>
    </div>

    <!-- Actions -->
    <footer class="flex items-center justify-between pt-4 border-t border-gray-100">
      <div class="flex items-center space-x-3 md:space-x-4">
        <!-- J'aime -->
        <button :class="[
          'group flex items-center gap-2 transition-all duration-300 hover:scale-110 cursor-pointer',
          review.user_reaction === 'like'
            ? 'text-orange-primary'
            : 'text-gray-600 hover:text-orange-primary'
        ]" @click="toggleReaction('like')">
          <div class="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-br from-orange-primary to-[#ff8c5a] rounded-full grid place-items-center shadow-sm group-hover:shadow-md transition-all duration-300 group-active:scale-95">
            <i :class="[
              review.user_reaction === 'like' ? 'fa-solid fa-thumbs-up' : 'fa-regular fa-thumbs-up',
              'text-white text-sm'
            ]"></i>
          </div>
          <span class="font-medium text-sm md:text-base">{{ review.nb_like }}</span>
        </button>

        <!-- Je n'aime pas -->
        <button :class="[
          'group flex items-center gap-2 transition-all duration-300 hover:scale-110 cursor-pointer',
          review.user_reaction === 'dislike'
            ? 'text-blue-600'
            : 'text-gray-600 hover:text-blue-600'
        ]" @click="toggleReaction('dislike')">
          <div class="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-br from-blue-night to-blue-night-light rounded-full grid place-items-center shadow-sm group-hover:shadow-md transition-all duration-300 group-active:scale-95">
            <i :class="[
              review.user_reaction === 'dislike' ? 'fa-solid fa-thumbs-down' : 'fa-regular fa-thumbs-down',
              'text-white text-sm'
            ]"></i>
          </div>
          <span class="font-medium text-sm md:text-base">{{ review.nb_dislike }}</span>
        </button>

        <!-- Répondre -->
        <router-link :to="{ name: 'review-detail', params: { id: review.id } }"
          class="group flex items-center gap-2 text-gray-600 hover:text-orange-primary transition-all duration-300">
          <div class="w-8 h-8 md:w-10 md:h-10 bg-gray-100 group-hover:bg-orange-50 rounded-full grid place-items-center transition-all duration-300">
            <i class="fa-solid fa-reply text-sm"></i>
          </div>
          <span class="font-medium text-sm md:text-base hidden sm:inline">Répondre</span>
        </router-link>
      </div>

      <router-link :to="{ name: 'review-detail', params: { id: review.id } }"
        class="flex items-center gap-2 text-gray-600 hover:text-orange-primary transition-colors duration-200 text-sm md:text-base">
        <i class="fa-regular fa-comment"></i>
        <span class="font-medium">{{ review.comments?.length || 0 }}</span>
      </router-link>
    </footer>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useUserStore } from '@/stores/user'
import { useReviewStore } from '@/stores/review'
import Swal from 'sweetalert2';
import ImageCarousel from '../layouts/ImageCarousel.vue'
import { getStorageUrl } from '@/config'

const props = defineProps({
  review: {
    type: Object,
    required: true
  }
})

const reviewStore = useReviewStore()
const userStore = useUserStore()

// Date formatée en français
const dateFormatted = computed(() => {
  const d = new Date(props.review.created_at)
  return d.toLocaleDateString('fr-FR', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }) + ' à ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
})

// Décoder le champ medias (tableau ou chaîne JSON héritée)
const mediasArray = computed(() => {
  if (!props.review.medias) return [];
  try {
    if (Array.isArray(props.review.medias)) return props.review.medias;
    return JSON.parse(props.review.medias);
  } catch {
    return [];
  }
});

// J'aime / Je n'aime pas
const toggleReaction = async (reaction) => {
  if (!userStore.user?.id) {
    Swal.fire({
      icon: 'error',
      title: 'Connexion requise',
      text: 'Tu dois être connecté pour réagir.',
      confirmButtonColor: '#FF6B35',
    });
    return
  }

  try {
    // Le store met à jour la review concernée (le même objet que la prop)
    await reviewStore.reactToReview(props.review.id, reaction)
  } catch {
    Swal.fire({
      icon: 'error',
      title: 'Oups...',
      text: 'La réaction a échoué. Réessaie.',
      confirmButtonColor: '#FF6B35',
    });
  }
}
</script>
