<template>
  <AppShell>
    <div class="w-full px-4 xl:px-6 py-6 pb-24">
      <div v-if="loading" class="space-y-4">
        <div class="h-40 rounded-2xl skeleton"></div>
        <div class="h-24 rounded-2xl skeleton"></div>
      </div>

      <div v-else-if="error"
        class="flex flex-col items-center text-center bg-white border border-gray-200 rounded-2xl py-16 px-4">
        <i class="fa-regular fa-circle-question text-5xl text-gray-400" aria-hidden="true"></i>
        <h1 class="mt-5 text-xl font-semibold text-gray-800">{{ t('member.notFound') }}</h1>
        <p class="mt-2 text-gray-600 text-sm max-w-md">{{ error }}</p>
        <BaseButton class="mt-5" :tag="'router-link'" :to="'/feed'" variant="primary">Retour au fil</BaseButton>
      </div>

      <template v-else-if="member">
        <header class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-night to-blue-night-light">
          <div class="relative px-5 py-6 sm:px-8 sm:py-8 flex flex-col sm:flex-row sm:items-end gap-5">
            <img v-if="member.picture" :src="getStorageUrl(member.picture)" alt=""
              class="w-24 h-24 rounded-2xl object-cover ring-4 ring-white/20 shadow-xl" />
            <span v-else
              class="w-24 h-24 rounded-2xl bg-orange-primary grid place-items-center text-white text-3xl font-poppins font-bold ring-4 ring-white/20">
              {{ (member.username || '?').slice(0, 2).toUpperCase() }}
            </span>

            <div class="flex-1 min-w-0">
              <h1 class="font-poppins font-extrabold text-2xl sm:text-3xl text-white truncate">
                {{ member.fullname }}
              </h1>
              <p class="text-white/75 text-sm mt-0.5">@{{ member.username }}</p>
              <p class="text-white/70 text-sm mt-2">
                <i class="fa-regular fa-calendar mr-1.5" aria-hidden="true"></i>
                Membre depuis {{ memberSince }}
              </p>
            </div>

            <div v-if="!member.is_me" class="flex flex-wrap gap-2">
              <FollowButton v-if="!member.blocked" type="user" :id="member.id" />
              <button type="button"
                class="px-3 py-1.5 rounded-full border text-xs font-medium transition-colors cursor-pointer"
                :class="member.blocked
                  ? 'border-white/40 text-white hover:bg-white/10'
                  : 'border-white/30 text-white/80 hover:border-red-300 hover:text-red-200'"
                @click="toggleBlock">
                <i class="fa-solid fa-ban mr-1.5" aria-hidden="true"></i>
                {{ member.blocked ? t('member.unblock') : t('member.block') }}
              </button>
            </div>
          </div>
        </header>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4 mt-4">
          <div v-for="tile in tiles" :key="tile.label"
            class="bg-white border border-gray-200 rounded-xl px-4 py-4">
            <p class="text-xs sm:text-sm text-gray-500">{{ tile.label }}</p>
            <p class="mt-1 font-poppins font-bold text-2xl text-blue-night tabular-nums">{{ tile.value }}</p>
          </div>
        </div>

        <div v-if="member.blocked" class="mt-6 p-4 rounded-2xl bg-gray-100 text-center text-sm text-gray-600">
          {{ t('member.blocked') }}
        </div>

        <div v-else class="mt-6 space-y-4 stagger">
          <ReviewCard v-for="review in reviews" :key="review.id" :review="review" class="animate-fade-in-up" />
          <p v-if="!reviews.length" class="text-center text-sm text-gray-500 py-10">
            {{ t('member.nothing') }}
          </p>
        </div>

        <Pagination v-if="pagination.last_page > 1" class="mt-8" :pagination="pagination" @changePage="loadReviews" />
      </template>
    </div>
  </AppShell>
</template>

<script setup>
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
import { useNotify, apiErrorMessage } from '@/composables/useNotify';
import { useConfirm } from '@/composables/useConfirm';

const { t } = useI18n();
const route = useRoute();
const notify = useNotify();
const confirm = useConfirm();

const member = ref(null);
const reviews = ref([]);
const pagination = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(true);
const error = ref(null);

const memberSince = computed(() =>
  member.value?.created_at
    ? new Date(member.value.created_at).toLocaleDateString('fr-FR', { year: 'numeric', month: 'long' })
    : ''
);

const tiles = computed(() => {
  const s = member.value?.stats ?? {};
  return [
    { label: t('profile.reviews'), value: s.reviews ?? 0 },
    { label: t('profile.likes'), value: s.likes ?? 0 },
    { label: t('member.views'), value: (s.views ?? 0).toLocaleString('fr-FR') },
    { label: t('member.followers'), value: s.followers ?? 0 },
    { label: t('member.following'), value: s.following ?? 0 },
  ];
});

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get(`/membres/${route.params.username}`);
    member.value = response.data.data;
    if (!member.value.blocked) await loadReviews(1);
  } catch (err) {
    error.value = apiErrorMessage(err, "Ce pseudo ne correspond à aucun membre.");
  } finally {
    loading.value = false;
  }
}

async function loadReviews(page = 1) {
  try {
    const response = await api.get(`/membres/${route.params.username}/avis`, { params: { page } });
    const payload = response.data.data;
    reviews.value = payload.data ?? [];
    pagination.value = {
      current_page: payload.current_page,
      last_page: payload.last_page,
      total: payload.total,
    };
  } catch {
    reviews.value = [];
  }
}

async function toggleBlock() {
  if (member.value.blocked) {
    await api.delete(`/blocks/${member.value.id}`);
    member.value.blocked = false;
    notify.success('Membre débloqué');
    await loadReviews(1);
    return;
  }

  const confirmed = await confirm({
    title: `Bloquer ${member.value.username} ?`,
    message: 'Tu ne verras plus ses publications ni ses commentaires, et vos abonnements réciproques seront retirés.',
    confirmLabel: 'Bloquer',
    tone: 'danger',
  });
  if (!confirmed) return;

  try {
    await api.post(`/blocks/${member.value.id}`);
    member.value.blocked = true;
    reviews.value = [];
    notify.success('Membre bloqué');
  } catch (err) {
    notify.error(apiErrorMessage(err, 'Le blocage a échoué.'));
  }
}

// Passer d'un profil à un autre par une mention doit recharger la page.
watch(() => route.params.username, load, { immediate: true });
</script>
