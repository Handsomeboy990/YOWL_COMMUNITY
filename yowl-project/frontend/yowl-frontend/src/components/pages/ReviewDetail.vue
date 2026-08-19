<template>
  <AppShell>
  <div class="w-full py-6">
    <div class="container mx-auto px-4 max-w-4xl">

      <!-- Retour au fil -->
      <router-link to="/feed"
        class="inline-flex items-center gap-2 text-gray-600 hover:text-orange-text mb-6 transition-colors font-medium">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Retour au fil</span>
      </router-link>

      <!-- Chargement -->
      <div v-if="loading" class="bg-white rounded-xl border border-gray-200 shadow-lg p-8 space-y-4">
        <div class="flex items-center gap-3">
          <div class="skeleton w-14 h-14 rounded-full"></div>
          <div class="space-y-2">
            <div class="skeleton h-4 w-40 rounded"></div>
            <div class="skeleton h-3 w-24 rounded"></div>
          </div>
        </div>
        <div class="skeleton h-4 w-full rounded"></div>
        <div class="skeleton h-4 w-3/4 rounded"></div>
        <div class="skeleton h-40 w-full rounded-xl"></div>
      </div>

      <!-- Review -->
      <div v-else-if="review" class="bg-white rounded-xl border border-gray-200 shadow-lg p-4 md:p-6 lg:p-8 animate-fade-in-up">

        <!-- En-tête -->
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 pb-4 border-b border-gray-100">
          <div class="flex items-center gap-3">
            <div v-if="review.user?.picture" class="relative group">
              <img class="w-12 h-12 md:w-14 md:h-14 rounded-full object-cover ring-2 ring-gray-200 group-hover:ring-orange-primary transition-all"
                :src="getStorageUrl(review.user.picture)" alt="Photo de profil">
            </div>
            <div v-else class="w-12 h-12 md:w-14 md:h-14 bg-gradient-to-br from-gray-200 to-gray-300 rounded-full grid place-items-center">
              <i class="fa-solid fa-user text-gray-600 text-xl"></i>
            </div>
            <div>
              <p class="font-semibold text-gray-900 text-base md:text-lg">{{ review.user?.username }}</p>
              <p class="text-xs md:text-sm text-gray-500">{{ dateFormatted }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2 text-gray-500 text-sm">
            <i class="fa-regular fa-eye"></i>
            <span>{{ review.nb_views }} vues</span>
          </div>
        </header>

        <!-- Contenu -->
        <div class="space-y-6">
          <div class="flex flex-col lg:flex-row gap-6">

            <!-- Images -->
            <div v-if="mediasArray.length" class="w-full lg:w-1/2">
              <div class="rounded-xl overflow-hidden shadow-md">
                <ImageCarousel :images="mediasArray" />
              </div>
              <LinkPreviewCard v-if="safeLink" class="mt-3" :url="safeLink" :preview="review.link_preview" />
            </div>

            <!-- Lien cité, seul.
                 L'iframe qui se trouvait ici portait allow-same-origin et
                 allow-scripts ensemble, ce qui laisse la page encadrée retirer
                 son propre bac à sable. -->
            <div v-else-if="safeLink" class="w-full lg:w-1/2">
              <LinkPreviewCard :url="safeLink" :preview="review.link_preview" />
            </div>

            <!-- Texte -->
            <div class="flex-1">
              <RichContent :text="review.content" classes="text-gray-700 leading-relaxed whitespace-pre-wrap" />

              <!-- Tags -->
              <div v-if="review.tags?.length" class="mt-5 flex flex-wrap gap-2">
                <span v-for="tag in review.tags" :key="tag.id"
                  class="text-xs font-medium bg-orange-primary/10 text-orange-text rounded-full px-3 py-1">
                  #{{ tag.name }}
                </span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <footer class="flex flex-wrap items-center justify-between gap-4 pt-6 border-t border-gray-100">
            <div class="flex items-center gap-3 md:gap-4">
              <!-- J'aime -->
              <button :class="[
                'group flex items-center gap-2 transition-all duration-300 hover:scale-110 cursor-pointer',
                review.user_reaction === 'like'
                  ? 'text-orange-text'
                  : 'text-gray-600 hover:text-orange-text'
              ]" @click="toggleReaction('like')">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-orange-primary to-[#ff8c5a] rounded-full grid place-items-center shadow-sm group-hover:shadow-md transition-all group-active:scale-95">
                  <i :class="[
                    review.user_reaction === 'like' ? 'fa-solid fa-thumbs-up' : 'fa-regular fa-thumbs-up',
                    'text-white'
                  ]"></i>
                </div>
                <span class="font-semibold text-base md:text-lg">{{ review.nb_like }}</span>
              </button>

              <!-- Je n'aime pas -->
              <button :class="[
                'group flex items-center gap-2 transition-all duration-300 hover:scale-110 cursor-pointer',
                review.user_reaction === 'dislike'
                  ? 'text-blue-600'
                  : 'text-gray-600 hover:text-blue-600'
              ]" @click="toggleReaction('dislike')">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-blue-night to-blue-night-light rounded-full grid place-items-center shadow-sm group-hover:shadow-md transition-all group-active:scale-95">
                  <i :class="[
                    review.user_reaction === 'dislike' ? 'fa-solid fa-thumbs-down' : 'fa-regular fa-thumbs-down',
                    'text-white'
                  ]"></i>
                </div>
                <span class="font-semibold text-base md:text-lg">{{ review.nb_dislike }}</span>
              </button>
            </div>

            <!-- Nombre de commentaires -->
            <div class="flex items-center gap-2 text-gray-600 font-medium">
              <i class="fa-regular fa-comment text-lg"></i>
              <span class="text-base md:text-lg">{{ commentCount }} commentaires</span>
            </div>
          </footer>
        </div>

        <!-- Commentaires -->
        <div class="mt-8">
          <CommentList :comments="review.comments" :reviewId="reviewId" @child-changed="refresh" />
        </div>
      </div>

      <!-- Introuvable -->
      <div v-else class="flex flex-col items-center justify-center py-20 animate-fade-in">
        <i class="fa-regular fa-file-lines text-6xl text-gray-300 mb-4"></i>
        <h2 class="text-xl font-semibold text-gray-700 mb-2">Review introuvable</h2>
        <p class="text-gray-500 mb-6">Cette review a peut-être été supprimée ou n'existe pas.</p>
        <router-link to="/feed"
          class="px-6 py-3 bg-orange-primary text-white rounded-lg hover:bg-orange-primary-dark transition-colors font-medium">
          Retour au fil
        </router-link>
      </div>
    </div>
  </div>

  </AppShell>
</template>

<script setup>
import AppShell from '@/components/layouts/AppShell.vue';
import { getStorageUrl } from '@/config';
import { computed, onBeforeMount, ref } from 'vue'
import { useRoute } from 'vue-router'
import CommentList from '../CommentList.vue'
import ImageCarousel from '@/components/layouts/ImageCarousel.vue'
import LinkPreviewCard from '@/components/cards/LinkPreviewCard.vue'
import RichContent from '@/components/ui/RichContent.vue'
import { useCommentStore } from '@/stores/comment'
import { useNotify, apiErrorMessage } from '@/composables/useNotify';
import { useUserStore } from '@/stores/user'
import api from '@/services/apiService'

const route = useRoute()
const userStore = useUserStore()
const notify = useNotify();;
const commentStore = useCommentStore()

const reviewId = parseInt(route.params.id)
const review = ref(null)
const loading = ref(true)
const dateFormatted = ref('')

const commentCount = computed(() =>
  commentStore.comments.filter((c) => c.review_id === reviewId).length ||
  review.value?.comments?.length ||
  0
)

const formatDate = (value) => {
  const d = new Date(value)
  return d.toLocaleDateString('fr-FR', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }) + ' à ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

onBeforeMount(async () => {
  try {
    // Charger la review directement depuis l'API (accès par URL directe possible)
    const [reviewRes] = await Promise.all([
      api.get(`/reviews/${reviewId}`),
      commentStore.getComments(),
    ])
    review.value = reviewRes.data.data
    dateFormatted.value = formatDate(review.value.created_at)
  } catch {
    review.value = null
  } finally {
    loading.value = false
  }
})

const refresh = () => {
  // Le compteur de commentaires est recalculé automatiquement via le store
}

// Seuls http et https sont ouverts : un lien javascript: rendu tel quel
// s'exécuterait au clic.
const safeLink = computed(() => {
  const raw = review.value?.link
  if (!raw) return null
  try {
    const url = new URL(raw)
    return url.protocol === 'http:' || url.protocol === 'https:' ? url.href : null
  } catch {
    return null
  }
})

const mediasArray = computed(() => {
  if (!review.value?.medias) return [];
  try {
    if (Array.isArray(review.value.medias)) return review.value.medias;
    return JSON.parse(review.value.medias);
  } catch {
    return [];
  }
});

const toggleReaction = async (reaction) => {
  if (!userStore.user?.id) {
    notify.info('Connexion requise', 'Tu dois être connecté pour réagir.')
    return
  }

  try {
    const response = await api.post(`/reviews/${review.value.id}/react`, { reaction })
    review.value.nb_like = response.data.nb_like
    review.value.nb_dislike = response.data.nb_dislike
    review.value.user_reaction = response.data.user_reaction
  } catch (err) {
    notify.error(apiErrorMessage(err, 'La réaction a échoué. Réessaie.'))
  }
}
</script>
