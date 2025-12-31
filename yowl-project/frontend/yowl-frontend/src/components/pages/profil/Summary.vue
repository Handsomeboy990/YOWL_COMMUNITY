<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <!-- header -->
  <Header />

  <!-- Profile Section -->
  <div class="min-h-screen bg-gray-50 pt-20 pb-8">
    <div class="container mx-auto px-4 max-w-7xl">
      
      <!-- User profile info -->
      <div class="animate-fade-in-up">
        <UserProfilData />
      </div>

      <!-- Navigation Tabs -->
      <div class="flex flex-wrap gap-3 mb-8 animate-fade-in-up animation-delay-200">
        <router-link to="/user/summary"
          class="px-4 md:px-6 py-2 md:py-3 rounded-lg text-white bg-orange-primary hover:bg-[#ff5522] transition-all shadow-md hover:shadow-lg font-medium flex-1 sm:flex-none text-center">
          <i class="fa-solid fa-chart-line mr-2"></i>
          <span>Summary</span>
        </router-link>
        <router-link to="/user/my-reviews"
          class="px-4 md:px-6 py-2 md:py-3 rounded-lg text-white bg-blue-night hover:bg-[#FF6B35] transition-all shadow-md hover:shadow-lg font-medium flex-1 sm:flex-none text-center">
          <i class="fa-solid fa-newspaper mr-2"></i>
          <span>My Reviews</span>
        </router-link>
        <router-link to="/user/activity"
          class="px-4 md:px-6 py-2 md:py-3 rounded-lg text-white bg-blue-night hover:bg-[#FF6B35] transition-all shadow-md hover:shadow-lg font-medium flex-1 sm:flex-none text-center">
          <i class="fa-solid fa-clock-rotate-left mr-2"></i>
          <span>Activity</span>
        </router-link>
      </div>

      <!-- Statistics Section -->
      <div class="bg-white border border-orange-200 rounded-xl p-4 md:p-6 lg:p-8 shadow-lg animate-fade-in-up animation-delay-400">
        <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
          <i class="fa-solid fa-chart-pie text-[#FF6B35]"></i>
          <span>Your Statistics</span>
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

          <!-- Engagement by age (Bar chart) -->
          <div class="bg-gray-50 rounded-lg p-4 flex flex-col items-center justify-center hover:shadow-md transition-shadow">
            <div class="w-full h-48 md:h-56">
              <BarChart :data="chartDataByAge" :options="chartOptions" class="w-full h-full" />
            </div>
            <p class="text-blue-night font-semibold mt-4 text-sm md:text-base">
              Engagement by Age
            </p>
          </div>

          <!-- Engagement type (Doughnut chart) -->
          <div class="bg-gray-50 rounded-lg p-4 flex flex-col items-center justify-center hover:shadow-md transition-shadow">
            <div class="w-full h-48 md:h-56">
              <DoughnutChart :data="chartDataType" :options="chartOptions" class="w-full h-full" />
            </div>
            <p class="text-blue-night font-semibold mt-4 text-sm md:text-base">
              Engagement Type
            </p>
          </div>

          <!-- Number of views -->
          <div class="bg-gradient-to-br from-orange-50 to-white rounded-lg p-6 flex flex-col items-center justify-center hover:shadow-md transition-shadow border border-orange-100">
            <i class="fa-solid fa-eye text-4xl md:text-5xl text-[#FF6B35] mb-4"></i>
            <span class="text-3xl md:text-4xl font-bold text-blue-night mb-2">
              {{ stats.numberOfViews.toLocaleString() }}
            </span>
            <p class="text-gray-600 font-medium text-sm md:text-base">
              Total Views
            </p>
          </div>

          <!-- Audience last 90 days (Line chart) -->
          <div class="bg-gray-50 rounded-lg p-4 flex flex-col items-center justify-center hover:shadow-md transition-shadow md:col-span-2 lg:col-span-3">
            <div class="w-full h-48 md:h-64">
              <LineChart :data="chartDataAudience" :options="chartOptions" class="w-full h-full" />
            </div>
            <p class="text-blue-night font-semibold mt-4 text-sm md:text-base">
              Audience Over the Last 90 Days
            </p>
          </div>
        </div>
      </div>

      <!-- Leave Community -->
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
import { ref, onMounted, computed } from 'vue';


// Import Chart.js libraries
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

// saving chart.js components
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


const stats = ref({
  numberOfViews: 0,
  engagementByAge: [],
  engagementType: [],
  audienceLast90Days: [],
});

// Fonction qui génère des données aléatoires pour simuler une API
const generateRandomData = () => {
  // Number of views
  stats.value.numberOfViews = Math.floor(Math.random() * 1_000) + 0;

  // Engagement per age
  stats.value.engagementByAge = [
    { range: '13–17', value: Math.floor(Math.random() * 100) },
    { range: '18–22', value: Math.floor(Math.random() * 100) },
    { range: '25–29', value: Math.floor(Math.random() * 100) },
    { range: '30–35', value: Math.floor(Math.random() * 100) },
  ];

  // Engagement (likes, dislikes, comments)
  stats.value.engagementType = [
    { type: 'Likes', value: Math.floor(Math.random() * 1000) },
    { type: 'Dislikes', value: Math.floor(Math.random() * 1000) },
    { type: 'Comments', value: Math.floor(Math.random() * 500) },
  ];

  // Audience of  last 90 days
  stats.value.audienceLast90Days = Array.from({ length: 90 }, () =>
    Math.floor(Math.random() * 1000)
  );
};

// graphic bar data (age)
const chartDataByAge = computed(() => ({
  labels: stats.value.engagementByAge.map((d) => d.range),
  datasets: [
    {
      label: 'Age group',
      data: stats.value.engagementByAge.map((d) => d.value),
      backgroundColor: '#FF6B35',
    },
  ],
}));

// grph doughnut data (engagement)
const chartDataType = computed(() => ({
  labels: stats.value.engagementType.map((d) => d.type),
  datasets: [
    {
      data: stats.value.engagementType.map((d) => d.value),
      backgroundColor: ['#FF6B35', '#1E2A38', '#FDBA74'],
    },
  ],
}));

// graph line data (audience)
const chartDataAudience = computed(() => ({
  labels: Array.from({ length: 90 }, (_, i) => i + 1),
  datasets: [
    {
      label: 'Daily Audience',
      data: stats.value.audienceLast90Days,
      borderColor: '#FF6B35',
      backgroundColor: '#FF6B35',
      tension: 0.4,
      pointRadius: 0,
    },
  ],
}));

// graph globale option
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
  },
  scales: {
    x: { grid: { display: false } },
    y: { grid: { color: '#E5E7EB' }, ticks: { stepSize: 20 } },
  },
};


onMounted(() => {
  generateRandomData();
});
</script>
