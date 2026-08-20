<template>
  <AppShell>
    <div class="w-full px-4 xl:px-6 py-6 pb-24">
      <div v-if="loading" class="space-y-4">
        <div class="h-36 rounded-2xl skeleton"></div>
        <div v-for="n in 3" :key="n" class="h-40 rounded-2xl skeleton"></div>
      </div>

      <div v-else-if="error"
        class="flex flex-col items-center text-center bg-white border border-gray-200 rounded-2xl py-16 px-4">
        <i class="fa-solid fa-hashtag text-5xl text-gray-400" aria-hidden="true"></i>
        <h1 class="mt-5 text-xl font-semibold text-gray-800">{{ t('tag.notFound') }}</h1>
        <p class="mt-2 text-gray-600 text-sm max-w-md">{{ error }}</p>
        <BaseButton class="mt-5" :tag="'router-link'" :to="'/sujets'" variant="primary">
          {{ t('tag.browseAll') }}
        </BaseButton>
      </div>

      <template v-else-if="tag">
        <!-- En-tête du sujet -->
        <header class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-night to-blue-night-light">
          <div class="relative px-5 py-6 sm:px-8 sm:py-7">
            <div class="flex flex-wrap items-start justify-between gap-4">
              <div class="min-w-0">
                <h1 class="font-poppins font-extrabold text-3xl sm:text-4xl text-white leading-none">
                  <span class="text-white/50">#</span>{{ tag.name }}
                </h1>
                <p class="mt-3 text-white/75 text-sm">
                  {{ t('tag.summary', { reviews: tag.stats.reviews, contributors: tag.stats.contributors }) }}
                </p>
              </div>
              <FollowButton v-if="userStore.isAuthenticated" type="tag" :id="tag.id" />
            </div>

            <dl class="mt-6 flex flex-wrap gap-x-8 gap-y-3">
              <div v-for="chiffre in chiffres" :key="chiffre.label">
                <dt class="text-white/60 text-xs uppercase tracking-wide">{{ chiffre.label }}</dt>
                <dd class="text-white font-poppins font-bold text-xl tabular-nums">{{ chiffre.value }}</dd>
              </div>
            </dl>
          </div>
        </header>

        <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1fr)_19rem]">
          <!-- Le fil du sujet -->
          <div class="min-w-0">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
              <div class="flex gap-1 p-1 rounded-xl bg-white border border-gray-200">
                <button v-for="option in tris" :key="option.value" type="button"
                  class="px-3.5 py-1.5 rounded-lg text-sm font-medium transition-colors cursor-pointer"
                  :class="sort === option.value ? 'bg-orange-primary text-white' : 'text-gray-500 hover:text-blue-night'"
                  :aria-pressed="sort === option.value" @click="changeSort(option.value)">
                  {{ option.label }}
                </button>
              </div>
            </div>

            <div v-if="loadingReviews" class="space-y-4">
              <div v-for="n in 3" :key="n" class="h-40 rounded-2xl skeleton"></div>
            </div>

            <div v-else-if="!reviews.length"
              class="flex flex-col items-center text-center bg-white border border-gray-200 rounded-2xl py-14 px-4">
              <i class="fa-regular fa-comments text-4xl text-gray-400" aria-hidden="true"></i>
              <h2 class="mt-4 text-lg font-semibold text-gray-800">{{ t('tag.empty') }}</h2>
              <p class="mt-2 text-sm text-gray-600 max-w-md">{{ t('tag.emptyHint') }}</p>
            </div>

            <div v-else class="space-y-4 stagger">
              <ReviewCard v-for="review in reviews" :key="review.id" :review="review" class="animate-fade-in-up" />
            </div>

            <Pagination v-if="pagination.last_page > 1" class="mt-8"
              :pagination="pagination" @changePage="loadReviews" />
          </div>

          <!-- Repères du sujet -->
          <aside class="space-y-4">
            <section v-if="tag.top_contributors.length" class="bg-white border border-gray-200 rounded-2xl p-5">
              <h2 class="font-poppins font-bold text-blue-night mb-3">{{ t('tag.contributors') }}</h2>
              <ul class="space-y-3">
                <li v-for="membre in tag.top_contributors" :key="membre.id" class="flex items-center gap-3">
                  <router-link :to="`/membres/${membre.username}`" class="flex items-center gap-3 min-w-0 flex-1">
                    <img v-if="membre.picture" :src="getStorageUrl(membre.picture)" alt=""
                      class="w-9 h-9 rounded-full object-cover shrink-0" />
                    <span v-else
                      class="w-9 h-9 rounded-full bg-blue-night grid place-items-center text-white text-xs font-bold shrink-0">
                      {{ (membre.username || '?').slice(0, 2).toUpperCase() }}
                    </span>
                    <span class="min-w-0">
                      <span class="block text-sm font-medium text-blue-night truncate">{{ membre.username }}</span>
                      <span class="block text-xs text-gray-500">
                        {{ t('feed.reviewCount', { count: membre.reviews_count }) }}
                      </span>
                    </span>
                  </router-link>
                  <FollowButton v-if="userStore.isAuthenticated && membre.id !== userStore.user?.id"
                    type="user" :id="membre.id" />
                </li>
              </ul>
            </section>

            <section v-if="tag.related.length" class="bg-white border border-gray-200 rounded-2xl p-5">
              <h2 class="font-poppins font-bold text-blue-night mb-3">{{ t('tag.related') }}</h2>
              <div class="flex flex-wrap gap-2">
                <router-link v-for="autre in tag.related" :key="autre.id" :to="`/sujets/${autre.name}`"
                  class="px-3 py-1.5 rounded-full border border-gray-200 text-sm text-gray-600 hover:border-orange-primary hover:text-orange-text transition-colors">
                  #{{ autre.name }}
                </router-link>
              </div>
            </section>
          </aside>
        </div>
      </template>
    </div>
  </AppShell>
</template>

<script setup>
import { usePageMeta } from '@/composables/usePageMeta';
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import AppShell from '@/components/layouts/AppShell.vue';
import ReviewCard from '@/components/cards/ReviewCard.vue';
import Pagination from '@/components/layouts/Pagination.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import FollowButton from '@/components/ui/FollowButton.vue';
import api from '@/services/apiService';
import { getStorageUrl } from '@/config';
import { apiErrorMessage } from '@/composables/useNotify';
import { useUserStore } from '@/stores/user';

const { t } = useI18n();
const route = useRoute();
const userStore = useUserStore();

const tag = ref(null);

usePageMeta(() => (tag.value?.name
  ? { title: '#' + tag.value.name,
      description: 'Les avis de la communauté sur ' + tag.value.name + '.' }
  : {}));
const reviews = ref([]);
const pagination = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(true);
const loadingReviews = ref(false);
const error = ref(null);
const sort = ref('newest');

const tris = computed(() => [
  { value: 'relevant', label: t('filters.relevant') },
  { value: 'newest', label: t('filters.newest') },
  { value: 'older', label: t('filters.oldest') },
]);

const chiffres = computed(() => {
  const s = tag.value?.stats ?? {};
  return [
    { label: t('tag.reviews'), value: s.reviews ?? 0 },
    { label: t('tag.followers'), value: s.followers ?? 0 },
    { label: t('tag.contributors'), value: s.contributors ?? 0 },
    { label: t('tag.thisWeek'), value: s.this_week ?? 0 },
  ];
});

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get(`/sujets/${route.params.name}`);
    tag.value = response.data.data;
    await loadReviews(1);
  } catch (err) {
    error.value = apiErrorMessage(err, t('tag.notFoundHint'));
  } finally {
    loading.value = false;
  }
}

async function loadReviews(page = 1) {
  loadingReviews.value = true;
  try {
    const response = await api.get(`/sujets/${route.params.name}/avis`, {
      params: { page, sort: sort.value },
    });
    const payload = response.data.data;
    reviews.value = payload.data ?? [];
    pagination.value = {
      current_page: payload.current_page,
      last_page: payload.last_page,
      total: payload.total,
    };
  } catch {
    reviews.value = [];
  } finally {
    loadingReviews.value = false;
  }
}

const changeSort = (value) => {
  sort.value = value;
  loadReviews(1);
};

// Passer d'un sujet à un autre par les tags voisins recharge la page.
watch(() => route.params.name, load, { immediate: true });
</script>
