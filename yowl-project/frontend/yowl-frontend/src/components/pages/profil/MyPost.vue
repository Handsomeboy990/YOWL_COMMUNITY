<template>
  <AppShell>
    <div class="w-full px-4 xl:px-6 py-6">
      <ProfileHeader />

      <div class="mt-6 flex items-center justify-between gap-4">
        <p class="text-sm text-gray-500">
          <span v-if="profileStore.pagination.total">
            {{ profileStore.pagination.total }} review<span v-if="profileStore.pagination.total > 1">s</span>
          </span>
        </p>
        <BaseButton variant="primary" size="sm" icon="fa-solid fa-plus" @click="openCreateModal">
          Publier un avis
        </BaseButton>
      </div>

      <!-- Chargement -->
      <div v-if="profileStore.loadingReviews" class="mt-4 grid gap-4 lg:grid-cols-2">
        <div v-for="n in 4" :key="n" class="bg-white border border-gray-200 rounded-2xl p-5">
          <div class="h-4 w-32 rounded skeleton"></div>
          <div class="mt-4 h-3 rounded skeleton"></div>
          <div class="mt-2 h-3 w-4/5 rounded skeleton"></div>
          <div class="mt-4 h-32 rounded-xl skeleton"></div>
        </div>
      </div>

      <!-- Erreur -->
      <div v-else-if="profileStore.reviewsError"
        class="mt-4 flex flex-col items-center text-center bg-white border border-gray-200 rounded-2xl py-14 px-4">
        <i class="fa-solid fa-plug-circle-exclamation text-4xl text-gray-400" aria-hidden="true"></i>
        <h2 class="mt-5 text-lg font-semibold text-gray-800">Tes avis n'ont pas pu être chargés</h2>
        <p class="mt-2 text-sm text-gray-600 max-w-md">{{ profileStore.reviewsError }}</p>
        <BaseButton class="mt-5" variant="primary" size="sm"
          @click="profileStore.fetchReviews(profileStore.pagination.current_page)">
          Réessayer
        </BaseButton>
      </div>

      <!-- Etat vide -->
      <div v-else-if="!profileStore.reviews.length"
        class="mt-4 flex flex-col items-center text-center bg-white border border-gray-200 rounded-2xl py-16 px-4">
        <i class="fa-regular fa-pen-to-square text-5xl text-gray-400" aria-hidden="true"></i>
        <h2 class="mt-5 text-xl font-semibold text-gray-800">Aucun avis pour le moment</h2>
        <p class="mt-2 text-gray-600 text-sm max-w-md">
          Tu n'as encore rien publié. Partage ton premier avis, la communauté t'attend.
        </p>
        <BaseButton class="mt-5" variant="primary" icon="fa-solid fa-plus" @click="openCreateModal">
          Publier mon premier avis
        </BaseButton>
      </div>

      <!-- Liste -->
      <div v-else class="mt-4 grid gap-4 lg:grid-cols-2 stagger">
        <article v-for="review in profileStore.reviews" :key="review.id"
          class="animate-fade-in-up bg-white border border-gray-200 rounded-2xl p-5 flex flex-col transition-shadow hover:shadow-md">
          <header class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="text-sm text-gray-500">{{ formatDate(review.created_at) }}</p>
              <!-- Deux etats partagent is_published a false, et les
                   confondre ferait lire « retire du fil » sur un texte qui
                   attend simplement son heure. -->
              <span v-if="isScheduled(review)"
                class="inline-flex items-center gap-1.5 mt-1.5 px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 text-xs font-medium">
                <i class="fa-regular fa-clock"></i>
                {{ t('compose.scheduledBadge', { date: formatDateTime(review.scheduled_for) }) }}
              </span>
              <span v-else-if="!review.is_published"
                class="inline-flex items-center gap-1.5 mt-1.5 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-medium">
                <i class="fa-solid fa-eye-slash"></i> Retiré du fil
              </span>
            </div>
            <div class="flex gap-2 shrink-0">
              <button type="button" aria-label="Modifier l'avis"
                class="w-9 h-9 rounded-full grid place-items-center text-gray-500 hover:text-blue-night hover:bg-gray-100 transition-colors cursor-pointer"
                @click="openEditModal(review)">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button type="button" aria-label="Supprimer l'avis"
                class="w-9 h-9 rounded-full grid place-items-center text-gray-500 hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer"
                @click="deletePost(review.id)">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </header>

          <p class="mt-3 text-gray-700 text-sm leading-relaxed line-clamp-4 max-w-[80ch]">
            {{ review.content }}
          </p>

          <div v-if="getMedias(review).length" class="mt-4">
            <ImageCarousel :images="getMedias(review)" />
          </div>

          <!-- Lien cite. Une iframe pointant vers une URL fournie par le
               membre, avec allow-same-origin et allow-scripts a la fois,
               laissait la page encadree retirer son propre bac a sable. -->
          <a v-if="safeLink(review)" :href="safeLink(review)" target="_blank" rel="noopener noreferrer"
            class="group mt-4 flex items-center gap-3 p-3 rounded-xl border border-gray-200 hover:border-orange-primary hover:bg-orange-50/40 transition-colors">
            <span class="w-9 h-9 shrink-0 rounded-lg bg-orange-primary/10 grid place-items-center text-orange-text">
              <i class="fa-solid fa-link"></i>
            </span>
            <span class="min-w-0 flex-1">
              <span class="block text-sm font-medium text-blue-night truncate">{{ linkHost(review) }}</span>
              <span class="block text-xs text-gray-500 truncate">{{ safeLink(review) }}</span>
            </span>
            <i class="fa-solid fa-arrow-up-right-from-square text-gray-300 group-hover:text-orange-text transition-colors"></i>
          </a>

          <!-- Une decision de moderation doit ouvrir une porte de sortie,
               sinon la seule reponse possible est de partir. -->
          <div v-if="!review.is_published && !isScheduled(review)"
            class="mt-4 rounded-xl bg-amber-50/70 border border-amber-100 p-3 flex items-center justify-between gap-3">
            <p class="text-xs text-amber-800">{{ t('appeal.mistake') }}</p>
            <button type="button"
              class="shrink-0 text-xs font-medium text-orange-text hover:underline cursor-pointer"
              @click="openAppeal(review.id)">
              {{ t('appeal.contest') }}
            </button>
          </div>

          <footer class="mt-auto pt-4 flex items-center justify-between border-t border-gray-100 text-sm">
            <div class="flex items-center gap-4 text-gray-600">
              <span class="flex items-center gap-1.5">
                <i class="fa-solid fa-thumbs-up text-orange-text"></i>{{ review.nb_like }}
              </span>
              <span class="flex items-center gap-1.5">
                <i class="fa-solid fa-thumbs-down"></i>{{ review.nb_dislike }}
              </span>
              <span class="flex items-center gap-1.5">
                <i class="fa-regular fa-eye"></i>{{ review.nb_views }}
              </span>
            </div>
            <router-link :to="{ name: 'review-detail', params: { id: review.id } }"
              class="text-blue-night hover:text-orange-text transition-colors">
              {{ review.comments_count ?? 0 }} commentaire<span v-if="(review.comments_count ?? 0) > 1">s</span>
            </router-link>
          </footer>
        </article>
      </div>

      <Pagination v-if="profileStore.pagination.last_page > 1" class="mt-8"
        :pagination="profileStore.pagination" @changePage="profileStore.fetchReviews" />

      <LeaveCommunity />
    </div>

    <AddReviewModal :isOpen="isModalOpen" :editedReview="selectedReview" @close="closeModal" @publish="addPost"
      @update="updatePost" />
  </AppShell>

  <AppealDialog v-model:open="appealOpen" :id="appealTarget" type="review" @sent="refresh" />
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppShell from '@/components/layouts/AppShell.vue';
import ProfileHeader from '@/components/layouts/ProfileHeader.vue';
import Pagination from '@/components/layouts/Pagination.vue';
import LeaveCommunity from '@/components/layouts/LeaveCommunity.vue';
import AddReviewModal from '@/components/layouts/AddReviewModal.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import ImageCarousel from '@/components/layouts/ImageCarouselMyPost.vue';
import AppealDialog from '@/components/ui/AppealDialog.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useReviewStore } from '@/stores/review';
import { useProfileStore } from '@/stores/profile';

const { t, locale } = useI18n();

// Une date lue dans une phrase anglaise ne doit pas rester au format francais.
const dateLocale = () => (locale.value === 'en' ? 'en-GB' : 'fr-FR');
const reviewStore = useReviewStore();
const profileStore = useProfileStore();
const confirm = useConfirm();

const appealOpen = ref(false);
const appealTarget = ref(0);
const isModalOpen = ref(false);
const selectedReview = ref(null);

onMounted(() => {
  profileStore.fetchReviews(1);
  profileStore.fetchStats();
});

const formatDate = (value) =>
  new Date(value).toLocaleDateString(dateLocale(), { day: 'numeric', month: 'short', year: 'numeric' });

const isScheduled = (review) =>
  !review.is_published && review.scheduled_for && new Date(review.scheduled_for) > new Date();

const formatDateTime = (value) =>
  new Date(value).toLocaleString(dateLocale(), {
    day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit',
  });

const openAppeal = (id) => {
  appealTarget.value = id;
  appealOpen.value = true;
};

const getMedias = (review) => {
  if (!review.medias) return [];
  if (Array.isArray(review.medias)) return review.medias;
  try {
    return JSON.parse(review.medias);
  } catch {
    return [];
  }
};

// Seuls http et https sont ouverts : un lien javascript: rendu tel quel
// s'executerait au clic.
const safeLink = (review) => {
  if (!review.link) return null;
  try {
    const url = new URL(review.link);
    return url.protocol === 'http:' || url.protocol === 'https:' ? url.href : null;
  } catch {
    return null;
  }
};

const linkHost = (review) => {
  const link = safeLink(review);
  return link ? new URL(link).hostname.replace(/^www\./, '') : '';
};

const openCreateModal = () => {
  selectedReview.value = null;
  isModalOpen.value = true;
};

const openEditModal = (post) => {
  selectedReview.value = { ...post };
  isModalOpen.value = true;
};

const closeModal = () => {
  selectedReview.value = null;
  isModalOpen.value = false;
};

const refresh = async () => {
  await profileStore.fetchReviews(profileStore.pagination.current_page);
  profileStore.fetchStats();
};

const addPost = async (reviewData) => {
  await reviewStore.createReviews(reviewData);
  closeModal();
  await refresh();
};

const updatePost = async (id, reviewData) => {
  await reviewStore.updateReviews(id, reviewData);
  closeModal();
  await refresh();
};

const deletePost = async (reviewId) => {
  const confirmed = await confirm({
    title: 'Supprimer cet avis ?',
    message: 'Il disparaîtra du fil, avec ses commentaires et ses réactions.',
    confirmLabel: 'Supprimer',
    tone: 'danger',
  });
  if (!confirmed) return;

  // Le store rapporte lui-meme l'echec eventuel.
  await reviewStore.deleteReviews(reviewId);
  profileStore.forget(reviewId);
  profileStore.fetchStats();
};
</script>
