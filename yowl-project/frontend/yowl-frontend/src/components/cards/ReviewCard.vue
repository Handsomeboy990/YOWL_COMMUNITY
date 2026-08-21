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
          <Icon name="user" class="text-gray-600" />
        </div>
        <div class="min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <p class="font-roboto font-semibold text-gray-900 text-sm md:text-base truncate">
              {{ review.user?.username }}
            </p>
            <FollowButton v-if="canFollow" type="user" :id="review.user_id" />
          </div>
          <p class="font-roboto text-xs text-gray-500">{{ dateFormatted }}</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <div class="flex items-center gap-1 text-gray-500 text-xs md:text-sm">
          <Icon name="eye" />
          <span>{{ review.nb_views }}</span>
        </div>

        <!-- Menu contextuel -->
        <div v-if="canReport" ref="menuRef" class="relative">
          <button type="button"
            class="w-8 h-8 rounded-full grid place-items-center text-gray-500 hover:text-blue-night hover:bg-gray-100 transition-colors cursor-pointer"
            aria-label="Options de l'avis" :aria-expanded="isMenuOpen" @click="isMenuOpen = !isMenuOpen">
            <Icon name="ellipsis" />
          </button>

          <div v-if="isMenuOpen"
            class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-lg shadow-blue-night/10 border border-gray-100 py-1 z-20">
            <button type="button"
              class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 cursor-pointer"
              @click="blockAuthor">
              <Icon name="ban" class="w-4" /> Bloquer {{ review.user?.username }}
            </button>
            <button type="button"
              class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-red-500 hover:bg-red-50 cursor-pointer"
              @click="openReport">
              <Icon name="flag" class="w-4" /> Signaler
            </button>
          </div>
        </div>
      </div>
    </header>

    <ReportModal :is-open="isReportOpen" type="review" :id="review.id" @close="isReportOpen = false" />

    <!-- Contenu.
         La carte s'etire avec sa colonne, le texte non : au dela d'environ
         quatre-vingts caracteres l'oeil perd la ligne suivante. -->
    <RichContent :text="review.content"
      classes="font-roboto text-sm md:text-base text-gray-700 mb-3 line-clamp-3 max-w-[80ch]" />

    <!-- Les tags n'étaient affichés nulle part dans le fil, alors qu'ils
         sont la porte d'entrée vers le sujet. -->
    <div v-if="review.tags?.length" class="flex flex-wrap gap-1.5 mb-3">
      <router-link v-for="tag in review.tags" :key="tag.id" :to="`/sujets/${tag.name}`"
        class="px-2.5 py-1 rounded-full bg-orange-primary/10 text-orange-text text-xs font-medium hover:bg-orange-primary/20 transition-colors"
        @click.stop>#{{ tag.name }}</router-link>
    </div>
    <router-link :to="{ name: 'review-detail', params: { id: review.id } }"
      class="text-orange-text hover:text-orange-primary-dark font-medium text-sm transition-colors duration-200 inline-flex items-center gap-1">
      Voir plus
      <Icon name="arrow-right" :size="14" class="text-xs" />
    </router-link>

    <!-- Médias -->
    <div class="my-4">
      <ImageCarousel v-if="mediasArray.length && !review.link" :images="mediasArray" />

      <div v-if="mediasArray.length && review.link">
        <ImageCarousel :images="mediasArray" />
      </div>

      <!-- Lien cité, avec l'aperçu publié par la page quand il existe -->
      <LinkPreviewCard v-if="safeLink" class="mt-2" :url="safeLink" :preview="review.link_preview" />
      <PollCard :poll="review.poll ? pollPayload : null" />
    </div>

    <HelpfulVote class="mt-4" :review="review" />

    <!-- Actions -->
    <footer class="flex items-center justify-between pt-4 border-t border-gray-100">
      <div class="flex items-center space-x-3 md:space-x-4">
        <!-- J'aime -->
        <button :class="[
          'group flex items-center gap-2 transition-all duration-300 hover:scale-110 cursor-pointer',
          review.user_reaction === 'like'
            ? 'text-orange-text'
            : 'text-gray-600 hover:text-orange-text'
        ]" @click="toggleReaction('like')">
          <div class="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-br from-orange-primary to-orange-primary-dark rounded-full grid place-items-center shadow-sm group-hover:shadow-md transition-all duration-300 group-active:scale-95">
            <Icon name="thumbs-up" :filled="review.user_reaction === 'like'" class="text-white" :size="16" />
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
            <Icon name="thumbs-down" :filled="review.user_reaction === 'dislike'" class="text-white" :size="16" />
          </div>
          <span class="font-medium text-sm md:text-base">{{ review.nb_dislike }}</span>
        </button>

        <!-- Répondre -->
        <router-link :to="{ name: 'review-detail', params: { id: review.id } }"
          class="group flex items-center gap-2 text-gray-600 hover:text-orange-text transition-all duration-300">
          <div class="w-8 h-8 md:w-10 md:h-10 bg-gray-100 group-hover:bg-orange-50 rounded-full grid place-items-center transition-all duration-300">
            <Icon name="reply" :size="16" class="text-sm" />
          </div>
          <span class="font-medium text-sm md:text-base hidden sm:inline">Répondre</span>
        </router-link>
      </div>

      <div class="flex items-center gap-3">
        <button v-if="userStore.isAuthenticated" type="button"
          class="w-9 h-9 rounded-full grid place-items-center transition-colors cursor-pointer"
          :class="saved ? 'text-orange-text bg-orange-50' : 'text-gray-500 hover:text-orange-text hover:bg-orange-50'"
          :aria-pressed="saved" :aria-label="saved ? 'Retirer des enregistrements' : 'Enregistrer cet avis'"
          @click="toggleBookmark">
          <Icon name="bookmark" :filled="saved" />
        </button>

        <router-link :to="{ name: 'review-detail', params: { id: review.id } }"
          class="flex items-center gap-2 text-gray-600 hover:text-orange-text transition-colors duration-200 text-sm md:text-base">
          <Icon name="comment" />
          <span class="font-medium">{{ review.comments?.length || 0 }}</span>
        </router-link>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useUserStore } from '@/stores/user'
import { useReviewStore } from '@/stores/review'
import { useNotify, apiErrorMessage } from '@/composables/useNotify'
import ImageCarousel from '../layouts/ImageCarousel.vue'
import ReportModal from '../layouts/ReportModal.vue'
import LinkPreviewCard from './LinkPreviewCard.vue'
import PollCard from './PollCard.vue'
import HelpfulVote from './HelpfulVote.vue'
import FollowButton from '@/components/ui/FollowButton.vue'
import RichContent from '@/components/ui/RichContent.vue'
import { useBookmarkStore } from '@/stores/bookmark'
import { useConfirm } from '@/composables/useConfirm'
import api from '@/services/apiService'
import { getStorageUrl } from '@/config'

import Icon from '@/components/ui/Icon.vue';
const { t, locale } = useI18n();

// Une date lue dans une phrase anglaise ne reste pas au format francais.
const dateLocale = () => (locale.value === 'en' ? 'en-GB' : 'fr-FR');

const notify = useNotify()
const bookmarkStore = useBookmarkStore()
const confirm = useConfirm()

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
// On ne s'abonne pas a soi-meme, et un visiteur n'a personne a suivre.
const canFollow = computed(
  () => Boolean(userStore.user?.id) && userStore.user.id !== props.review.user_id
)

// L'API renvoie le sondage brut avec la review : on lui donne la forme que
// la carte attend, resultats masques tant que la personne n'a pas vote.
const pollPayload = computed(() => {
  const poll = props.review.poll
  if (!poll) return null
  const total = (poll.options || []).reduce((sum, o) => sum + (o.votes || 0), 0)
  return {
    id: poll.id,
    question: poll.question,
    closed: Boolean(poll.closes_at) && new Date(poll.closes_at) < new Date(),
    total_votes: total,
    my_option_id: null,
    revealed: false,
    options: (poll.options || []).map((o) => ({ id: o.id, label: o.label, share: null })),
  }
})

const canReport = computed(
  () => Boolean(userStore.user?.id) && userStore.user.id !== props.review.user_id
)

// L'etat local suit le store, qui bascule de facon optimiste.
const saved = computed(() => bookmarkStore.has(props.review.id))
const toggleBookmark = () => bookmarkStore.toggle(props.review.id)

const blockAuthor = async () => {
  isMenuOpen.value = false
  const confirmed = await confirm({
    title: `Bloquer ${props.review.user?.username} ?`,
    message: "Tu ne verras plus ses publications ni ses commentaires, et vos abonnements réciproques seront retirés.",
    confirmLabel: 'Bloquer',
    tone: 'danger',
  })
  if (!confirmed) return

  try {
    await api.post(`/blocks/${props.review.user_id}`)
    notify.success('Membre bloqué', 'Actualise le fil pour ne plus voir ses publications.')
  } catch (err) {
    notify.error(apiErrorMessage(err, 'Le blocage a échoué.'))
  }
}

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

const onClickOutside = (event) => {
  if (menuRef.value && !menuRef.value.contains(event.target)) isMenuOpen.value = false
}

onMounted(() => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))

// Date formatée en français
const dateFormatted = computed(() => {
  const d = new Date(props.review.created_at)
  return d.toLocaleDateString(dateLocale(), {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }) + ` ${t('common.atTime')} ` + d.toLocaleTimeString(dateLocale(), { hour: '2-digit', minute: '2-digit' })
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
