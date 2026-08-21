<template>
  <AppShell>
  <div class="w-full py-6">
    <div class="container mx-auto px-4 max-w-4xl">

      <!-- Retour au fil -->
      <router-link to="/feed"
        class="inline-flex items-center gap-2 min-h-11 text-gray-600 hover:text-orange-text mb-6 transition-colors font-medium">
        <Icon name="arrow-left" />
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
              <Icon name="user" :size="22" class="text-gray-600 text-xl" />
            </div>
            <div>
              <p class="font-semibold text-gray-900 text-base md:text-lg">{{ review.user?.username }}</p>
              <p class="text-xs md:text-sm text-gray-500">{{ dateFormatted }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2 text-gray-500 text-sm">
            <Icon name="eye" />
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
                <router-link v-for="tag in review.tags" :key="tag.id"
                  :to="`/sujets/${tag.name}`" class="inline-flex items-center min-h-8 text-xs font-medium bg-orange-primary/10 text-orange-text rounded-full px-3 py-1 hover:bg-orange-primary/20 transition-colors">#{{ tag.name }}</router-link>
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
                <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-orange-primary to-orange-primary-dark rounded-full grid place-items-center shadow-sm group-hover:shadow-md transition-all group-active:scale-95">
                  <Icon name="thumbs-up" />
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
                  <Icon name="thumbs-down" />
                </div>
                <span class="font-semibold text-base md:text-lg">{{ review.nb_dislike }}</span>
              </button>
            </div>

            <!-- Nombre de commentaires -->
            <div class="flex items-center gap-2 text-gray-600 font-medium">
              <Icon name="comment" :size="20" class="text-lg" />
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
        <Icon name="file-lines" :size="56" class="text-6xl text-gray-300 mb-4" />
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
import { useI18n } from 'vue-i18n';
import { usePageMeta } from '@/composables/usePageMeta';
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

import Icon from '@/components/ui/Icon.vue';
const { t, locale } = useI18n();

// Une date lue dans une phrase anglaise ne reste pas au format francais.
const dateLocale = () => (locale.value === 'en' ? 'en-GB' : 'fr-FR');

const route = useRoute()
const userStore = useUserStore()
const notify = useNotify();;
const commentStore = useCommentStore()

const reviewId = parseInt(route.params.id)
const review = ref(null)

// L'avis est l'adresse que les membres partagent : elle doit se presenter.
usePageMeta(() => {
  if (!review.value) return {};
  const texte = (review.value.content ?? '').replace(/\s+/g, ' ').trim();
  const image = review.value.medias?.[0] ? getStorageUrl(review.value.medias[0]) : '';
  const titre = texte.slice(0, 60) || 'Un avis';

  return {
    title: titre,
    description: texte.slice(0, 155),
    image,
    // Un avis est un article, pas une page de site : le partage et le moteur
    // le presentent autrement, avec son auteur et sa date.
    type: 'article',
    // La pagination des commentaires produit /reviews/12/2, /reviews/12/3 :
    // autant d'adresses pour un seul contenu, qui se feraient concurrence
    // dans l'index sans cette declaration.
    canonical: `${window.location.origin}/reviews/${reviewId}`,
    // Donnees structurees. DiscussionForumPosting plutot que Review : le
    // vocabulaire Review de schema.org attend une note chiffree et un objet
    // note, ce que YOWL ne demande jamais. Declarer un type qu'on ne remplit
    // pas fait rejeter le bloc entier.
    jsonLd: {
      '@context': 'https://schema.org',
      '@type': 'DiscussionForumPosting',
      headline: titre,
      articleBody: texte.slice(0, 500),
      url: `${window.location.origin}/reviews/${reviewId}`,
      datePublished: review.value.created_at,
      dateModified: review.value.updated_at ?? review.value.created_at,
      author: review.value.user?.username
        ? { '@type': 'Person', name: review.value.user.username }
        : undefined,
      image: image || undefined,
      interactionStatistic: [
        {
          '@type': 'InteractionCounter',
          interactionType: 'https://schema.org/CommentAction',
          userInteractionCount: review.value.comments?.length ?? 0,
        },
        {
          '@type': 'InteractionCounter',
          interactionType: 'https://schema.org/LikeAction',
          userInteractionCount: review.value.nb_like ?? 0,
        },
      ],
    },
  };
});
const loading = ref(true)
const dateFormatted = ref('')

const commentCount = computed(() =>
  commentStore.comments.filter((c) => c.review_id === reviewId).length ||
  review.value?.comments?.length ||
  0
)

const formatDate = (value) => {
  const d = new Date(value)
  return d.toLocaleDateString(dateLocale(), {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }) + ` ${t('common.atTime')} ` + d.toLocaleTimeString(dateLocale(), { hour: '2-digit', minute: '2-digit' })
}

onBeforeMount(async () => {
  try {
    // Charger la review directement depuis l'API (accès par URL directe possible)
    const reviewRes = await api.get(`/reviews/${reviewId}`)
    review.value = reviewRes.data.data
    dateFormatted.value = formatDate(review.value.created_at)

    // Les commentaires arrivent avec l'avis, réponses comprises. On les pose
    // dans le magasin, que la liste et les cartes lisent déjà.
    //
    // L'appel précédent, getComments(), rapatriait les dix derniers
    // commentaires du site entier : sur une base qui en compte des milliers,
    // ceux de l'avis ouvert n'y figuraient pratiquement jamais, et la liste
    // restait vide sous un compteur pourtant juste.
    commentStore.setForReview(review.value.comments)
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
