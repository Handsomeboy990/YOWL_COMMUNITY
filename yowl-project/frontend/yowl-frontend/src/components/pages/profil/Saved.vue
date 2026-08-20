<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <AppShell>
    <div class="w-full px-4 xl:px-6 py-6 pb-24">
      <ProfileHeader />

      <div v-if="loading" class="mt-6 space-y-4">
        <div v-for="n in 3" :key="n" class="h-48 rounded-2xl skeleton"></div>
      </div>

      <div v-else-if="error"
        class="mt-6 flex flex-col items-center text-center bg-white border border-gray-200 rounded-2xl py-14 px-4">
        <i class="fa-solid fa-plug-circle-exclamation text-4xl text-gray-400" aria-hidden="true"></i>
        <h2 class="mt-5 text-lg font-semibold text-gray-800">{{ t('profile.savedError') }}</h2>
        <p class="mt-2 text-sm text-gray-600 max-w-md">{{ error }}</p>
        <BaseButton class="mt-5" variant="primary" size="sm" @click="load(pagination.current_page)">
          {{ t('common.retry') }}
        </BaseButton>
      </div>

      <div v-else-if="!reviews.length"
        class="mt-6 flex flex-col items-center text-center bg-white border border-gray-200 rounded-2xl py-16 px-4">
        <i class="fa-regular fa-bookmark text-5xl text-gray-400" aria-hidden="true"></i>
        <h2 class="mt-5 text-xl font-semibold text-gray-800">{{ t('profile.savedEmptyTitle') }}</h2>
        <p class="mt-2 text-gray-600 text-sm max-w-md">
          {{ t('profile.savedEmptyHelp') }}
        </p>
        <BaseButton class="mt-5" :tag="'router-link'" :to="'/feed'" variant="primary">
          Parcourir le fil
        </BaseButton>
      </div>

      <div v-else class="mt-6 space-y-4 stagger">
        <ReviewCard v-for="review in reviews" :key="review.id" :review="review" class="animate-fade-in-up" />
      </div>

      <Pagination v-if="pagination.last_page > 1" class="mt-8" :pagination="pagination" @changePage="load" />
    </div>
  </AppShell>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { onMounted, ref } from 'vue';
import AppShell from '@/components/layouts/AppShell.vue';
import ProfileHeader from '@/components/layouts/ProfileHeader.vue';
import ReviewCard from '@/components/cards/ReviewCard.vue';
import Pagination from '@/components/layouts/Pagination.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import api from '@/services/apiService';
import { apiErrorMessage } from '@/composables/useNotify';
import { useBookmarkStore } from '@/stores/bookmark';

const { t } = useI18n();

const reviews = ref([]);
const pagination = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(true);
const error = ref(null);
const bookmarkStore = useBookmarkStore();

async function load(page = 1) {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get('/bookmarks', { params: { page } });
    const payload = response.data.data;
    reviews.value = payload.data ?? [];
    pagination.value = {
      current_page: payload.current_page,
      last_page: payload.last_page,
      total: payload.total,
    };
  } catch (err) {
    error.value = apiErrorMessage(err, 'Impossible de charger tes enregistrements.');
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  bookmarkStore.load();
  load(1);
});
</script>
