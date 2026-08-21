<template>
  <div class="animate-fade-in-up">
    <!-- Banniere et identite -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-night to-blue-night-light">
      <div class="absolute inset-0 opacity-20"
        style="background-image: radial-gradient(circle at 20% 20%, var(--color-orange-brand) 0, transparent 45%), radial-gradient(circle at 80% 0, var(--color-orange-glow) 0, transparent 40%)">
      </div>

      <div class="relative px-5 py-6 sm:px-8 sm:py-8 flex flex-col sm:flex-row sm:items-end gap-5">
        <img v-if="user?.picture" :src="getStorageUrl(user.picture)" alt="Photo de profil"
          class="w-24 h-24 rounded-2xl object-cover ring-4 ring-white/20 shadow-xl" />
        <span v-else
          class="w-24 h-24 rounded-2xl bg-orange-primary grid place-items-center text-white text-3xl font-poppins font-bold ring-4 ring-white/20 shadow-xl">
          {{ initials }}
        </span>

        <div class="flex-1 min-w-0">
          <h1 class="font-poppins font-extrabold text-2xl sm:text-3xl text-white leading-tight truncate">
            {{ user?.fullname || t('profile.defaultName') }}
          </h1>
          <p class="text-white/75 text-sm mt-0.5">@{{ user?.username }}</p>
          <p class="text-white/70 text-sm mt-2">
            <Icon name="calendar" class="mr-1.5" />
            Membre depuis {{ memberSince }}
          </p>
        </div>

        <BaseButton variant="primary" size="sm" icon="pen" @click="isEditOpen = true">
          Modifier le profil
        </BaseButton>
      </div>
    </div>

    <!-- Chiffres cles -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mt-4">
      <div v-for="tile in tiles" :key="tile.label"
        class="bg-white border border-gray-200 rounded-xl px-4 py-4 transition-shadow hover:shadow-md">
        <div class="flex items-center gap-2 text-gray-500 text-xs sm:text-sm">
          <Icon :name="tile.icon" class="text-orange-text" />
          {{ tile.label }}
        </div>
        <p v-if="loading" class="mt-2 h-8 w-16 rounded skeleton"></p>
        <p v-else class="mt-1 font-poppins font-bold text-2xl sm:text-3xl text-blue-night">
          {{ tile.value }}
        </p>
      </div>
    </div>

    <!-- Onglets
         Sept onglets ne tiennent pas sur 360 pixels : le bandeau defile, et
         le degrade du bord droit dit qu'il reste des sections a atteindre. -->
    <div class="mt-6 defilement-indique" :style="{ '--reste-a-defiler': resteADefiler }">
      <nav ref="bandeauOnglets"
        class="flex gap-1 border-b border-gray-200 overflow-x-auto defilement-horizontal snap-x"
        aria-label="Sections du profil">
        <router-link v-for="tab in tabs" :key="tab.to" :to="tab.to"
          class="relative px-4 py-3 min-h-11 flex items-center text-sm font-medium whitespace-nowrap snap-start transition-colors"
          :class="route.name === tab.name
            ? 'text-orange-text'
            : 'text-gray-500 hover:text-blue-night'">
          <Icon :name="tab.icon" class="mr-2" />{{ tab.label }}
          <span v-if="route.name === tab.name"
            class="absolute left-3 right-3 bottom-0 h-0.5 rounded-full bg-orange-primary"></span>
        </router-link>
      </nav>
    </div>

    <EditProfilModal :isOpen="isEditOpen" @close="isEditOpen = false" />
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';
import { getStorageUrl } from '@/config';
import { useDefilementHorizontal } from '@/composables/useDefilementHorizontal';
import BaseButton from '@/components/ui/BaseButton.vue';
import EditProfilModal from '@/components/pages/profil/EditProfilModal.vue';
import { useUserStore } from '@/stores/user';
import { useProfileStore } from '@/stores/profile';

import Icon from '@/components/ui/Icon.vue';
const { t, locale } = useI18n();

const route = useRoute();

// Le bandeau d'onglets deborde sous 500 pixels : on suit sa position pour
// afficher ou effacer le degrade qui signale la suite.
const { element: bandeauOnglets, resteADefiler } = useDefilementHorizontal();
const userStore = useUserStore();
const profileStore = useProfileStore();
const isEditOpen = ref(false);

const user = computed(() => userStore.user);
const loading = computed(() => profileStore.loadingStats);

const initials = computed(() => {
  const name = user.value?.username || user.value?.fullname || '';
  return name
    .split(' ')
    .map((part) => part[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
});

const memberSince = computed(() => {
  if (!user.value?.created_at) return '';
  return new Date(user.value.created_at).toLocaleDateString(
    locale.value === 'en' ? 'en-GB' : 'fr-FR', {
    year: 'numeric',
    month: 'long',
  });
});

const format = (value) => (value ?? 0).toLocaleString(locale.value === 'en' ? 'en-GB' : 'fr-FR');

const tiles = computed(() => {
  const stats = profileStore.stats;
  return [
    { label: t('profile.statReviews'), icon: 'newspaper', value: format(stats?.reviews) },
    { label: t('profile.statViews'), icon: 'eye', value: format(stats?.views) },
    { label: t('profile.statLikes'), icon: 'thumbs-up', value: format(stats?.likes) },
    { label: t('profile.statComments'), icon: 'comment', value: format(stats?.comments_received) },
  ];
});

const tabs = computed(() => [
  { to: '/user/summary', name: 'summary', label: t('profile.tabSummary'), icon: 'chart-pie' },
  { to: '/user/my-reviews', name: 'my-reviews', label: t('profile.tabReviews'), icon: 'newspaper' },
  { to: '/user/saved', name: 'saved', label: t('profile.tabSaved'), icon: 'bookmark' },
  { to: '/user/activity', name: 'activity', label: t('profile.tabActivity'), icon: 'clock-rotate-left' },
  { to: '/user/contestations', name: 'appeals', label: t('profile.tabAppeals'), icon: 'scale-balanced' },
]);
</script>
