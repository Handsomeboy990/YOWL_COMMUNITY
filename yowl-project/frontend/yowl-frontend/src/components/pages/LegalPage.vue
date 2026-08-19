<template>
  <AppShell>
    <div class="w-full px-4 xl:px-6 py-8 pb-24">
      <div class="max-w-3xl">
        <!-- Navigation entre les textes : on arrive rarement sur la bonne
             page du premier coup. -->
        <nav class="flex flex-wrap gap-1 mb-8" aria-label="Pages légales">
          <router-link v-for="page in pages" :key="page.slug" :to="`/${page.slug}`"
            class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="page.slug === slug
              ? 'bg-blue-night text-white'
              : 'text-gray-600 hover:bg-gray-100'">
            {{ page.label }}
          </router-link>
        </nav>

        <div v-if="loading" class="space-y-3">
          <div class="h-10 w-2/3 rounded skeleton"></div>
          <div v-for="n in 8" :key="n" class="h-4 rounded skeleton"></div>
        </div>

        <div v-else-if="error"
          class="flex flex-col items-center text-center bg-white border border-gray-200 rounded-2xl py-14 px-4">
          <i class="fa-regular fa-file-lines text-4xl text-gray-400" aria-hidden="true"></i>
          <h1 class="mt-5 text-lg font-semibold text-gray-800">{{ t('legal.unavailable') }}</h1>
          <p class="mt-2 text-sm text-gray-600 max-w-md">{{ error }}</p>
          <BaseButton class="mt-5" size="sm" variant="primary" @click="load">Réessayer</BaseButton>
        </div>

        <article v-else>
          <h1 class="font-poppins font-extrabold text-3xl sm:text-4xl text-blue-night leading-tight">
            {{ page.title }}
          </h1>
          <p v-if="page.updated_at" class="mt-3 text-sm text-gray-500">
            {{ t('legal.updated', { date: formatDate(page.updated_at) }) }}
          </p>

          <!-- Le contenu vient de la base, nettoyé côté serveur à partir
               d'une liste blanche de balises avant d'y être écrit. -->
          <div class="contenu-legal mt-8" v-html="page.body"></div>
        </article>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import AppShell from '@/components/layouts/AppShell.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import api from '@/services/apiService';
import { apiErrorMessage } from '@/composables/useNotify';

const { t } = useI18n();
const route = useRoute();
const slug = ref(route.params.slug ?? route.meta.slug);
const page = ref({});
const loading = ref(true);
const error = ref(null);

const pages = computed(() => [
  { slug: 'charte', label: t('legal.charter') },
  { slug: 'confidentialite', label: t('legal.privacy') },
  { slug: 'conditions', label: t('legal.terms') },
  { slug: 'mentions-legales', label: t('legal.notices') },
]);

const formatDate = (value) =>
  new Date(value).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get(`/legal/${slug.value}`);
    page.value = response.data.data;
  } catch (err) {
    error.value = apiErrorMessage(err, t('legal.notPublished'));
  } finally {
    loading.value = false;
  }
}

watch(
  () => route.params.slug ?? route.meta.slug,
  (value) => {
    slug.value = value;
    load();
  },
  { immediate: true }
);
</script>
