<template>
  <div class="animate-fade-in-up">
    <!-- Banniere et identite -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-night to-blue-night-light">
      <div class="absolute inset-0 opacity-20"
        style="background-image: radial-gradient(circle at 20% 20%, #ff6b35 0, transparent 45%), radial-gradient(circle at 80% 0, #ff8c5a 0, transparent 40%)">
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
            {{ user?.fullname || 'Membre YOWL' }}
          </h1>
          <p class="text-white/60 text-sm mt-0.5">@{{ user?.username }}</p>
          <p class="text-white/50 text-sm mt-2">
            <i class="fa-regular fa-calendar mr-1.5"></i>
            Membre depuis {{ memberSince }}
          </p>
        </div>

        <BaseButton variant="primary" size="sm" icon="fa-solid fa-pen" @click="isEditOpen = true">
          Modifier le profil
        </BaseButton>
      </div>
    </div>

    <!-- Chiffres cles -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mt-4">
      <div v-for="tile in tiles" :key="tile.label"
        class="bg-white border border-gray-200 rounded-xl px-4 py-4 transition-shadow hover:shadow-md">
        <div class="flex items-center gap-2 text-gray-500 text-xs sm:text-sm">
          <i :class="[tile.icon, 'text-orange-primary']"></i>
          {{ tile.label }}
        </div>
        <p v-if="loading" class="mt-2 h-8 w-16 rounded skeleton"></p>
        <p v-else class="mt-1 font-poppins font-bold text-2xl sm:text-3xl text-blue-night">
          {{ tile.value }}
        </p>
      </div>
    </div>

    <!-- Onglets -->
    <nav class="flex gap-1 mt-6 border-b border-gray-200 overflow-x-auto" aria-label="Sections du profil">
      <router-link v-for="tab in tabs" :key="tab.to" :to="tab.to"
        class="relative px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors"
        :class="route.name === tab.name
          ? 'text-orange-primary'
          : 'text-gray-500 hover:text-blue-night'">
        <i :class="[tab.icon, 'mr-2']"></i>{{ tab.label }}
        <span v-if="route.name === tab.name"
          class="absolute left-3 right-3 -bottom-px h-0.5 rounded-full bg-orange-primary"></span>
      </router-link>
    </nav>

    <EditProfilModal :isOpen="isEditOpen" @close="isEditOpen = false" />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';
import { getStorageUrl } from '@/config';
import BaseButton from '@/components/ui/BaseButton.vue';
import EditProfilModal from '@/components/pages/profil/EditProfilModal.vue';
import { useUserStore } from '@/stores/user';
import { useProfileStore } from '@/stores/profile';

const route = useRoute();
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
  return new Date(user.value.created_at).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
  });
});

const format = (value) => (value ?? 0).toLocaleString('fr-FR');

const tiles = computed(() => {
  const stats = profileStore.stats;
  return [
    { label: 'Reviews', icon: 'fa-solid fa-newspaper', value: format(stats?.reviews) },
    { label: 'Vues cumulées', icon: 'fa-regular fa-eye', value: format(stats?.views) },
    { label: "J'aime reçus", icon: 'fa-regular fa-thumbs-up', value: format(stats?.likes) },
    { label: 'Commentaires reçus', icon: 'fa-regular fa-comment', value: format(stats?.comments_received) },
  ];
});

const tabs = [
  { to: '/user/summary', name: 'summary', label: 'Résumé', icon: 'fa-solid fa-chart-pie' },
  { to: '/user/my-reviews', name: 'my-reviews', label: 'Mes reviews', icon: 'fa-solid fa-newspaper' },
  { to: '/user/activity', name: 'activity', label: 'Activité', icon: 'fa-solid fa-clock-rotate-left' },
];
</script>
