<template>
    <div class="w-full">
        <!-- Colonne centrale du fil.
             Elle occupe toute la place laissee par les rails : le max-w-2xl
             centre d'avant reduisait le fil a une bande etroite avec du vide
             de chaque cote. La longueur de ligne est bornee dans la carte,
             sur le texte seul, pas sur la carte entiere. -->
        <div class="w-full px-4 xl:px-6 py-6">
            <!-- Bascule du fil. Un fil algorithmique sans echappatoire est la
                 premiere chose qu'on reproche a un reseau : le fil complet
                 reste accessible en un clic. -->
            <div v-if="userStore.isAuthenticated" class="flex gap-1 mb-4 p-1 rounded-xl bg-white border border-gray-200 w-fit">
              <button v-for="mode in feedModes" :key="mode.value" type="button"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors cursor-pointer"
                :class="feed === mode.value
                  ? 'bg-orange-primary text-white shadow-sm'
                  : 'text-gray-500 hover:text-blue-night'"
                :aria-pressed="feed === mode.value" @click="switchFeed(mode.value)">
                <Icon :name="mode.icon" class="mr-1.5" />{{ mode.label }}
              </button>
            </div>

            <!-- Filtres (mobile / tablette : repliables) -->
            <details class="xl:hidden mb-4 bg-white rounded-xl border border-gray-200 overflow-hidden">
                <summary class="px-4 py-3 font-medium text-blue-night cursor-pointer select-none flex items-center gap-2">
                    <Icon name="sliders" class="text-orange-text" />
                    Filtres et tri
                </summary>
                <div class="p-4 pt-0">
                    <Sidebar />
                </div>
            </details>

            <!-- Chargement -->
            <div v-if="reviewStore.loading && reviews.length === 0" class="space-y-4 md:space-y-5">
                <div v-for="n in 3" :key="n"
                    class="bg-white rounded-xl border border-gray-200 p-4 md:p-6 animate-pulse">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-gray-200"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3 w-32 bg-gray-200 rounded"></div>
                            <div class="h-3 w-24 bg-gray-100 rounded"></div>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="h-3 bg-gray-200 rounded"></div>
                        <div class="h-3 bg-gray-100 rounded w-4/5"></div>
                    </div>
                    <div class="mt-4 h-40 bg-gray-100 rounded-lg"></div>
                </div>
            </div>

            <!-- Erreur -->
            <div v-else-if="reviewStore.error && reviews.length === 0"
                class="flex flex-col items-center justify-center text-center py-20 px-4">
                <Icon name="plug-circle-exclamation" :size="40" class="text-4xl text-gray-400" aria-hidden="true" />
                <h2 class="mt-5 text-xl font-semibold text-gray-800">{{ t('feed.loadError') }}</h2>
                <p class="mt-2 text-gray-600 text-sm max-w-md">{{ reviewStore.error }}</p>
                <BaseButton class="mt-5" variant="primary" size="sm" @click="reviewStore.fetchReviews()">
                    {{ t('common.retry') }}
                </BaseButton>
            </div>

            <!-- Aucun résultat pour la recherche ou les filtres -->
            <div v-else-if="reviews.length === 0 && reviewStore.hasActiveFilters"
                class="flex flex-col items-center justify-center text-center py-20 px-4">
                <Icon name="magnifying-glass" :size="40" class="text-4xl text-gray-400" aria-hidden="true" />
                <h2 class="mt-5 text-xl font-semibold text-gray-800">{{ t('feed.noResults') }}</h2>
                <p class="mt-2 text-gray-600 text-sm max-w-md">
                    {{ t('feed.noResultsHint') }}
                </p>
                <button type="button"
                    class="mt-5 px-4 py-2 rounded-xl bg-blue-night text-white text-sm font-medium hover:bg-blue-night/90 transition-colors cursor-pointer"
                    @click="reviewStore.resetQuery()">
                    {{ t('feed.clearFilters') }}
                </button>
            </div>

            <!-- Etat vide -->
            <div v-else-if="reviews.length === 0 && !reviewStore.hasActiveFilters"
                class="flex flex-col items-center justify-center text-center py-20 px-4 animate-fade-in">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-20 h-20 md:w-24 md:h-24 mb-6 text-gray-300 animate-bounce-slow" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18M8 3v18M16 3v18" />
                </svg>
                <h2 class="text-xl md:text-2xl font-semibold text-gray-800 mb-2">{{ t('feed.empty') }}</h2>
                <p class="mt-2 text-gray-600 text-sm md:text-base max-w-md">
                    {{ t('feed.emptyHint') }}
                </p>
            </div>

            <!-- Fil des reviews.
                 TransitionGroup n'accepte pas "mode" : Vue signalait un
                 attribut surnumeraire a chaque rendu. -->
            <TransitionGroup tag="div" name="feed"
                class="relative space-y-4 md:space-y-5 stagger">
                <ReviewCard v-for="review in reviews" :key="review.id" :review="review"
                    class="animate-fade-in-up" />
            </TransitionGroup>

            <!-- Pagination -->
            <div class="mt-8">
                <Pagination v-if="reviewStore.pagination.total > 10" :pagination="reviewStore.pagination"
                    @changePage="handlePageChange" />
            </div>
        </div>

        <!-- Rail droit : filtres + KPI.
             Projete dans la coquille par un Teleport : celle-ci est montee
             une fois pour toutes dans App.vue, donc au-dessus de cette vue et
             non plus autour d'elle. Un emplacement nomme ne peut plus la
             joindre. -->
        <Teleport to="#rail-lateral">
            <div class="space-y-5">
                <Sidebar />
                <div class="bg-gradient-to-br from-orange-50 to-white rounded-xl border border-orange-200 p-5">
                    <h2 class="text-lg font-poppins font-bold mb-4 text-gray-800">
                        La communauté en chiffres
                    </h2>
                    <KpiSideBar />
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import BaseButton from '@/components/ui/BaseButton.vue';
import { ref, watch, onBeforeMount } from 'vue'
import { useRail } from '@/composables/useRail'
import { useI18n } from 'vue-i18n'
import Sidebar from '@/components/layouts/Sidebar.vue'
import ReviewCard from '@/components/cards/ReviewCard.vue'
import Pagination from '@/components/layouts/Pagination.vue'
import KpiSideBar from '@/components/layouts/KpiSideBar.vue'
import { useReviewStore } from '@/stores/review'
import { useUserStore } from '@/stores/user'
import { useFollowStore } from '@/stores/follow'
import { useBookmarkStore } from '@/stores/bookmark'
import { useCommentStore } from '@/stores/comment'
import { useRoute } from 'vue-router'

import Icon from '@/components/ui/Icon.vue';
const { t } = useI18n()
// Cette vue occupe le rail lateral : la coquille lui reserve la place.
useRail();

const reviewStore = useReviewStore()
const userStore = useUserStore()
const followStore = useFollowStore()
const bookmarkStore = useBookmarkStore()

const feedModes = [
    { value: 'all', label: t('feed.all'), icon: 'globe' },
    { value: 'following', label: t('feed.following'), icon: 'user-group' },
]
const feed = ref('all')

const switchFeed = (mode) => {
    feed.value = mode
    reviewStore.setQuery({ feed: mode === 'following' ? 'following' : '' }, { immediate: true })
}
const commentStore = useCommentStore()

const reviews = ref([])
const route = useRoute()

// Synchroniser la liste locale avec le store
watch(
    () => reviewStore.reviews,
    (newReviews) => {
        reviews.value = newReviews
    },
    { immediate: true }
)

// Chargement initial
onBeforeMount(async () => {
    await reviewStore.getReviews(route.params.page ? route.params.page : 1)
    await commentStore.getComments()
    followStore.load()
    bookmarkStore.load()
})

// Changer de page
const handlePageChange = (page) => {
    reviewStore.goToPage(page)
}
</script>
