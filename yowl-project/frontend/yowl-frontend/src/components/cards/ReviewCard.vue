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
      <div class="flex items-center gap-3">
        <div class="flex items-center gap-1 text-gray-500 text-xs md:text-sm">
          <i class="fa-regular fa-eye"></i>
          <span>{{ review.nb_views }}</span>
        </div>

        <!-- Menu contextuel -->
        <div v-if="canReport" ref="menuRef" class="relative">
          <button type="button"
            class="w-8 h-8 rounded-full grid place-items-center text-gray-400 hover:text-blue-night hover:bg-gray-100 transition-colors cursor-pointer"
            aria-label="Options de l'avis" :aria-expanded="isMenuOpen" @click="isMenuOpen = !isMenuOpen">
            <i class="fa-solid fa-ellipsis"></i>
          </button>

          <div v-if="isMenuOpen"
            class="absolute right-0 mt-2 w-44 bg-white rounded-xl shadow-lg shadow-blue-night/10 border border-gray-100 py-1 z-20">
            <button type="button"
              class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-red-500 hover:bg-red-50 cursor-pointer"
              @click="openReport">
              <i class="fa-solid fa-flag w-4"></i> Signaler
            </button>
          </div>
        </div>
      </div>
    </header>

    <ReportModal :is-open="isReportOpen" type="review" :id="review.id" @close="isReportOpen = false" />

    <!-- Contenu.
         La carte s'etire avec sa colonne, le texte non : au dela d'environ
         quatre-vingts caracteres l'oeil perd la ligne suivante. -->
    <p class="font-roboto text-sm md:text-base text-gray-700 mb-3 line-clamp-3 max-w-[80ch]">
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

      <div v-if="mediasArray.length && review.link">
        <ImageCarousel :images="mediasArray" />
      </div>

      <!-- Lien cité -->
      <a v-if="safeLink" :href="safeLink" target="_blank" rel="noopener noreferrer"
        class="group flex items-center gap-3 mt-2 p-3 rounded-xl border border-gray-200 hover:border-orange-primary hover:bg-orange-50/40 transition-colors duration-200">
        <span class="w-10 h-10 shrink-0 rounded-lg bg-orange-primary/10 grid place-items-center text-orange-primary">
          <i class="fa-solid fa-link"></i>
        </span>
        <span class="min-w-0 flex-1">
          <span class="block text-sm font-medium text-blue-night truncate">{{ linkHost }}</span>
          <span class="block text-xs text-gray-400 truncate">{{ safeLink }}</span>
        </span>
        <i class="fa-solid fa-arrow-up-right-from-square text-gray-300 group-hover:text-orange-primary transition-colors"></i>
      </a>
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
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useUserStore } from '@/stores/user'
import { useReviewStore } from '@/stores/review'
import { useNotify, apiErrorMessage } from '@/composables/useNotify'
import ImageCarousel from '../layouts/ImageCarousel.vue'
import ReportModal from '../layouts/ReportModal.vue'
import { getStorageUrl } from '@/config'

const notify = useNotify()

const props = defineProps({
  review: {
    type: Object,
    required: true
  }
})

const reviewStore = useReviewStore()
const userStore = useUserStore()

const isMenuOpen = ref(false)
const isReportOpen = ref(false)
const menuRef = ref(null)

// Un membre connecté signale le contenu des autres, jamais le sien
const canReport = computed(
  () => Boolean(userStore.user?.id) && userStore.user.id !== props.review.user_id
)

const openReport = () => {
  isMenuOpen.value = false
  isReportOpen.value = true
}

// N'ouvrir que des liens http(s) : un schéma exotique n'est pas rendu
const safeLink = computed(() => {
  if (!props.review.link) return ''
  try {
    const url = new URL(props.review.link)
    return ['http:', 'https:'].includes(url.protocol) ? url.href : ''
  } catch {
    return ''
  }
})

const linkHost = computed(() => {
  if (!safeLink.value) return ''
  return new URL(safeLink.value).hostname.replace(/^www\./, '')
})

const onClickOutside = (event) => {
  if (menuRef.value && !menuRef.value.contains(event.target)) isMenuOpen.value = false
}

onMounted(() => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))

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
    notify.info('Connexion requise', 'Tu dois être connecté pour réagir.');
    return
  }

  try {
    // Le store met à jour la review concernée (le même objet que la prop)
    await reviewStore.reactToReview(props.review.id, reaction)
  } catch (err) {
    notify.error(apiErrorMessage(err, 'La réaction a échoué. Réessaie.'));
  }
}
</script>
