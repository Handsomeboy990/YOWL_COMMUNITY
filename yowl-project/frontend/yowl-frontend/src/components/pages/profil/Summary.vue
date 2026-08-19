<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <AppShell>
    <div class="w-full px-4 xl:px-6 py-6">
      <ProfileHeader />

      <!-- Erreur de chargement -->
      <div v-if="profileStore.statsError"
        class="mt-6 flex flex-col items-center text-center bg-white border border-gray-200 rounded-2xl py-14 px-4">
        <i class="fa-solid fa-plug-circle-exclamation text-4xl text-gray-300"></i>
        <h2 class="mt-5 text-lg font-semibold text-gray-800">Statistiques indisponibles</h2>
        <p class="mt-2 text-sm text-gray-600 max-w-md">{{ profileStore.statsError }}</p>
        <BaseButton class="mt-5" variant="primary" size="sm" @click="profileStore.fetchStats()">
          Réessayer
        </BaseButton>
      </div>

      <div v-else class="mt-6 grid grid-cols-1 xl:grid-cols-3 gap-4 animate-fade-in-up">
        <!-- Publications sur six mois -->
        <section class="xl:col-span-2 bg-white border border-gray-200 rounded-2xl p-5">
          <header class="flex items-center justify-between mb-4">
            <h2 class="font-poppins font-bold text-blue-night">Tes publications</h2>
            <span class="text-xs text-gray-400">6 derniers mois</span>
          </header>
          <div class="h-64">
            <LineChart v-if="!profileStore.loadingStats" :data="timelineData" :options="lineOptions" />
            <div v-else class="h-full rounded-xl skeleton"></div>
          </div>
        </section>

        <!-- Reactions recues -->
        <section class="bg-white border border-gray-200 rounded-2xl p-5">
          <h2 class="font-poppins font-bold text-blue-night mb-4">Réactions reçues</h2>
          <div class="h-64 grid place-items-center">
            <DoughnutChart v-if="hasEngagement" :data="reactionData" :options="doughnutOptions" />
            <p v-else class="text-sm text-gray-400 text-center px-4">
              Personne n'a encore réagi à tes reviews. Publie, ça viendra.
            </p>
          </div>
        </section>

        <!-- Detail chiffre -->
        <section class="xl:col-span-3 bg-white border border-gray-200 rounded-2xl p-5">
          <h2 class="font-poppins font-bold text-blue-night mb-4">Le détail</h2>
          <dl class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
            <div v-for="row in details" :key="row.label" class="min-w-0">
              <dt class="text-xs text-gray-500 truncate">{{ row.label }}</dt>
              <dd class="mt-1 font-poppins font-bold text-xl text-blue-night">{{ row.value }}</dd>
            </div>
          </dl>
        </section>
      </div>

      <LeaveCommunity />
    </div>
  </AppShell>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import {
  Chart as ChartJS,
  ArcElement,
  LineElement,
  CategoryScale,
  LinearScale,
  PointElement,
  Filler,
  Tooltip,
} from 'chart.js';
import { Doughnut, Line } from 'vue-chartjs';
import AppShell from '@/components/layouts/AppShell.vue';
import ProfileHeader from '@/components/layouts/ProfileHeader.vue';
import LeaveCommunity from '@/components/layouts/LeaveCommunity.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { useProfileStore } from '@/stores/profile';

ChartJS.register(ArcElement, LineElement, CategoryScale, LinearScale, PointElement, Filler, Tooltip);

const LineChart = Line;
const DoughnutChart = Doughnut;

const profileStore = useProfileStore();

onMounted(() => profileStore.fetchStats());

const format = (value) => (value ?? 0).toLocaleString('fr-FR');

const hasEngagement = computed(() => {
  const stats = profileStore.stats;
  if (!stats) return false;
  return stats.likes + stats.dislikes + stats.comments_received > 0;
});

const details = computed(() => {
  const stats = profileStore.stats;
  return [
    { label: 'Reviews publiées', value: format(stats?.reviews) },
    { label: 'Vues cumulées', value: format(stats?.views) },
    { label: "J'aime reçus", value: format(stats?.likes) },
    { label: "Je n'aime pas reçus", value: format(stats?.dislikes) },
    { label: 'Commentaires reçus', value: format(stats?.comments_received) },
    { label: 'Commentaires écrits', value: format(stats?.comments_written) },
  ];
});

// Le libelle vient du serveur au format AAAA-MM, affiche en mois court.
const monthLabel = (key) => {
  const [year, month] = key.split('-');
  return new Date(Number(year), Number(month) - 1, 1)
    .toLocaleDateString('fr-FR', { month: 'short' });
};

const timelineData = computed(() => {
  const series = profileStore.stats?.reviews_per_month ?? [];
  return {
    labels: series.map((point) => monthLabel(point.month)),
    datasets: [
      {
        label: 'Reviews publiées',
        data: series.map((point) => point.count),
        borderColor: '#FF6B35',
        backgroundColor: 'rgba(255, 107, 53, 0.15)',
        tension: 0.4,
        fill: true,
        pointRadius: 4,
        pointBackgroundColor: '#FF6B35',
      },
    ],
  };
});

const reactionData = computed(() => {
  const stats = profileStore.stats;
  return {
    labels: ["J'aime", "Je n'aime pas", 'Commentaires'],
    datasets: [
      {
        data: [stats?.likes ?? 0, stats?.dislikes ?? 0, stats?.comments_received ?? 0],
        backgroundColor: ['#FF6B35', '#1E2A38', '#FDBA74'],
        borderWidth: 0,
      },
    ],
  };
});

const lineOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false } },
    y: { grid: { color: '#E5E7EB' }, ticks: { precision: 0 }, beginAtZero: true },
  },
};

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '62%',
  plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16 } } },
};
</script>
