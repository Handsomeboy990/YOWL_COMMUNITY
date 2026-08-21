<template>
  <AppShell>
    <div class="w-full px-4 xl:px-6 py-8 pb-24">
      <div class="max-w-4xl">
        <h1 class="font-poppins font-extrabold text-3xl text-blue-night">{{ t('tag.directory') }}</h1>
        <p class="mt-3 text-gray-600 max-w-2xl leading-relaxed">{{ t('tag.directoryHint') }}</p>

        <input v-model="filtre" type="search" :placeholder="t('tag.searchPlaceholder')"
          class="mt-6 w-full sm:w-80 px-4 py-2.5 min-h-11 rounded-xl border border-gray-200 focus:border-orange-primary focus:outline-none text-sm" />

        <div v-if="loading" class="mt-6 flex flex-wrap gap-2">
          <span v-for="n in 20" :key="n" class="h-10 w-32 rounded-xl skeleton"></span>
        </div>

        <div v-else class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <router-link v-for="tag in visibles" :key="tag.id" :to="`/sujets/${tag.name}`"
            class="flex items-center justify-between gap-3 p-4 rounded-xl border border-gray-200 bg-white hover:border-orange-primary transition-colors">
            <span class="min-w-0">
              <span class="block font-medium text-blue-night truncate">
                <span class="text-gray-400">#</span>{{ tag.name }}
              </span>
              <span class="block text-xs text-gray-500 mt-0.5">
                {{ t('feed.reviewCount', { count: tag.reviews_count }) }}
                <template v-if="tag.followers_count"> &middot; {{ t('tag.followerCount', { count: tag.followers_count }) }}</template>
              </span>
            </span>
            <Icon name="arrow-right" class="text-gray-300" aria-hidden="true" />
          </router-link>
        </div>

        <p v-if="!loading && !visibles.length" class="mt-8 text-sm text-gray-500">{{ t('tag.noMatch') }}</p>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppShell from '@/components/layouts/AppShell.vue';
import api from '@/services/apiService';

import Icon from '@/components/ui/Icon.vue';
const { t } = useI18n();
const tags = ref([]);
const loading = ref(true);
const filtre = ref('');

const visibles = computed(() => {
  const terme = filtre.value.trim().toLowerCase();
  return terme ? tags.value.filter((tag) => tag.name.includes(terme)) : tags.value;
});

onMounted(async () => {
  try {
    const response = await api.get('/sujets');
    tags.value = response.data.data;
  } finally {
    loading.value = false;
  }
});
</script>
