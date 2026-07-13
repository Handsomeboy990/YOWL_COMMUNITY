<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <!-- header -->
  <Header />

  <!-- Profil -->
  <div class="min-h-screen bg-gray-50 pt-20 pb-8">
    <div class="container mx-auto px-4 max-w-7xl">

      <div class="animate-fade-in-up">
        <UserProfilData />
      </div>

      <!-- Onglets -->
      <div class="flex flex-wrap gap-3 mb-8 animate-fade-in-up animation-delay-200">
        <router-link to="/user/summary"
          class="px-4 md:px-6 py-2 md:py-3 rounded-lg text-white bg-orange-primary hover:bg-orange-primary-dark transition-all shadow-md hover:shadow-lg font-medium flex-1 sm:flex-none text-center">
          <i class="fa-solid fa-chart-line mr-2"></i>
          <span>Résumé</span>
        </router-link>
        <router-link to="/user/my-reviews"
          class="px-4 md:px-6 py-2 md:py-3 rounded-lg text-white bg-blue-night hover:bg-orange-primary transition-all shadow-md hover:shadow-lg font-medium flex-1 sm:flex-none text-center">
          <i class="fa-solid fa-newspaper mr-2"></i>
          <span>Mes reviews</span>
        </router-link>
        <router-link to="/user/activity"
          class="px-4 md:px-6 py-2 md:py-3 rounded-lg text-white bg-blue-night hover:bg-orange-primary transition-all shadow-md hover:shadow-lg font-medium flex-1 sm:flex-none text-center">
          <i class="fa-solid fa-clock-rotate-left mr-2"></i>
          <span>Activité</span>
        </router-link>
      </div>

      <!-- Statistiques -->
      <div class="bg-white border border-orange-200 rounded-xl p-4 md:p-6 lg:p-8 shadow-lg animate-fade-in-up animation-delay-400">
        <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
          <i class="fa-solid fa-chart-pie text-orange-primary"></i>
          <span>Tes statistiques</span>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

          <!-- Tuiles de compteurs -->
          <div class="bg-gradient-to-br from-orange-50 to-white rounded-lg p-6 flex flex-col items-center justify-center hover:shadow-md transition-shadow border border-orange-100">
            <i class="fa-solid fa-eye text-4xl md:text-5xl text-orange-primary mb-4"></i>
            <span class="text-3xl md:text-4xl font-bold text-blue-night mb-2">
              {{ totalViews.toLocaleString('fr-FR') }}
            </span>
            <p class="text-gray-600 font-medium text-sm md:text-base">Vues cumulées</p>
          </div>

          <div class="bg-gradient-to-br from-orange-50 to-white rounded-lg p-6 flex flex-col items-center justify-center hover:shadow-md transition-shadow border border-orange-100">
            <i class="fa-solid fa-newspaper text-4xl md:text-5xl text-orange-primary mb-4"></i>
            <span class="text-3xl md:text-4xl font-bold text-blue-night mb-2">
              {{ myReviews.length }}
            </span>
            <p class="text-gray-600 font-medium text-sm md:text-base">Reviews publiées</p>
          </div>

          <!-- Répartition des réactions (doughnut) -->
          <div class="bg-gray-50 rounded-lg p-4 flex flex-col items-center justify-center hover:shadow-md transition-shadow">
            <div class="w-full h-48 md:h-56">
              <DoughnutChart v-if="hasEngagement" :data="chartDataType" :options="chartOptions" class="w-full h-full" />
              <p v-else class="h-full grid place-items-center text-sm text-gray-400 text-center px-4">
                Pas encore de réactions sur tes reviews
              </p>
            </div>
            <p class="text-blue-night font-semibold mt-4 text-sm md:text-base">
              Réactions sur tes reviews
            </p>
          </div>

          <!-- Répartition par âge de la communauté (bar) -->
          <div class="bg-gray-50 rounded-lg p-4 flex flex-col items-center justify-center hover:shadow-md transition-shadow md:col-span-2 lg:col-span-1">
            <div class="w-full h-48 md:h-56">
              <BarChart :data="chartDataByAge" :options="chartOptions" class="w-full h-full" />
            </div>
            <p class="text-blue-night font-semibold mt-4 text-sm md:text-base">
              La communauté par tranche d'âge
            </p>
          </div>

          <!-- Publications sur 6 mois (line) -->
          <div class="bg-gray-50 rounded-lg p-4 flex flex-col items-center justify-center hover:shadow-md transition-shadow md:col-span-2">
            <div class="w-full h-48 md:h-64">
              <LineChart :data="chartDataTimeline" :options="chartOptions" class="w-full h-full" />
            </div>
            <p class="text-blue-night font-semibold mt-4 text-sm md:text-base">
              Tes publications sur les 6 derniers mois
            </p>
          </div>
        </div>
      </div>

      <!-- Quitter la communauté -->
      <div class="mt-8 animate-fade-in-up animation-delay-400">
        <LeaveCommunity />
      </div>
    </div>
  </div>

  <!-- footer -->
  <Footer />
</template>

<script setup>
import Header from '@/components/layouts/Header.vue';
import Footer from '@/components/layouts/Footer.vue';
import { computed, onMounted } from 'vue';

import {
  Chart as ChartJS,
  BarElement,
  ArcElement,
  LineElement,
  CategoryScale,
  LinearScale,
  PointElement,
  Tooltip,
  Legend,
} from 'chart.js';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import UserProfilData from '@/components/layouts/UserProfilData.vue';
import LeaveCommunity from '@/components/layouts/LeaveCommunity.vue';
import { useReviewStore } from '@/stores/review';
import { useUserStore } from '@/stores/user';

ChartJS.register(
  BarElement,
  ArcElement,
  LineElement,
  CategoryScale,
  LinearScale,
  PointElement,
  Tooltip,
  Legend
);

const BarChart = Bar;
const DoughnutChart = Doughnut;
const LineChart = Line;

const reviewStore = useReviewStore();
const userStore = useUserStore();

onMounted(() => {
  reviewStore.getReviews();
  reviewStore.getKPI();
});

// Reviews de l'utilisateur connecté (données réelles)
const myReviews = computed(() =>
  reviewStore.reviews.filter((review) => review.user_id === userStore.user?.id)
);

const totalViews = computed(() =>
  myReviews.value.reduce((sum, review) => sum + (review.nb_views || 0), 0)
);

const totalLikes = computed(() =>
  myReviews.value.reduce((sum, review) => sum + (review.nb_like || 0), 0)
);

const totalDislikes = computed(() =>
  myReviews.value.reduce((sum, review) => sum + (review.nb_dislike || 0), 0)
);

const totalComments = computed(() =>
  myReviews.value.reduce((sum, review) => sum + (review.comments?.length || 0), 0)
);

const hasEngagement = computed(
  () => totalLikes.value + totalDislikes.value + totalComments.value > 0
);

// Doughnut : réactions sur mes reviews
const chartDataType = computed(() => ({
  labels: ["J'aime", "Je n'aime pas", 'Commentaires'],
  datasets: [
    {
      data: [totalLikes.value, totalDislikes.value, totalComments.value],
      backgroundColor: ['#FF6B35', '#1E2A38', '#FDBA74'],
    },
  ],
}));

// Bar : répartition par âge de la communauté (KPI global)
const chartDataByAge = computed(() => {
  const ranges = reviewStore.kpi?.nbUsersByAgeRange || {};
  return {
    labels: Object.keys(ranges),
    datasets: [
      {
        label: 'Membres',
        data: Object.values(ranges),
        backgroundColor: '#FF6B35',
        borderRadius: 6,
      },
    ],
  };
});

// Line : mes publications par mois (6 derniers mois)
const chartDataTimeline = computed(() => {
  const now = new Date();
  const months = [];
  const counts = [];
  for (let i = 5; i >= 0; i--) {
    const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
    months.push(d.toLocaleDateString('fr-FR', { month: 'short' }));
    counts.push(
      myReviews.value.filter((review) => {
        const created = new Date(review.created_at);
        return created.getFullYear() === d.getFullYear() && created.getMonth() === d.getMonth();
      }).length
    );
  }
  return {
    labels: months,
    datasets: [
      {
        label: 'Reviews publiées',
        data: counts,
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

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
  },
  scales: {
    x: { grid: { display: false } },
    y: { grid: { color: '#E5E7EB' }, ticks: { precision: 0 } },
  },
};
</script>
