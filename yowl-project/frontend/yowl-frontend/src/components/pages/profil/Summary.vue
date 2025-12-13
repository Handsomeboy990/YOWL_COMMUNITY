<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <!-- header -->
  <Header />

  <!-- Profile Section -->
  <div class="container mx-auto p-6">
    <!-- User profile info -->
    <UserProfilData />

    <!-- Navigation Tabs -->
    <div class="flex space-x-4 mb-10">
      <router-link to="/user/summary"
        class="px-6 py-2 rounded-lg  text-white bg-orange-primary hover:bg-[#FF6B35] transition">
        Summary
      </router-link>
      <router-link to="/user/my-reviews"
        class="px-6 py-2 rounded-lg  text-white bg-blue-night hover:bg-[#FF6B35] transition">
        My reviews
      </router-link>
      <router-link to="/user/activity"
        class="px-6 py-2 rounded-lg  text-white bg-blue-night hover:bg-[#FF6B35] transition">
        Activity
      </router-link>
    </div>

    <!-- Statistics Section -->
    <div class="border-2 border-orange-primary rounded-xl p-8 bg-gray-100 h-120 justify-between items-center text-center flex lg:flex-row gap-6 lg:p-6 h-100 flex-wrap">

      <!-- Engagement by age (Bar chart) -->
      <div class="flex flex-col items-center justify-center w-[250px] h-[200px]">
        <BarChart :data="chartDataByAge" :options="chartOptions" class="w-full h-full" />
        <p class=" text-blue-night font-medium mt-2">
          Engagement by age
        </p>
      </div>

      <!-- Engagement type (Doughnut chart) -->
      <div class="flex flex-col items-center justify-center w-[250px] h-[200px]">
        <DoughnutChart :data="chartDataType" :options="chartOptions" class="w-full h-full" />
        <p class=" text-blue-night font-medium mt-2">
          Engagement type
        </p>
      </div>

      <!-- Number of views -->
      <div class="flex flex-col items-center justify-center w-[250px] h-[200px]">
        <span class="text-blue-night mb-2">
          {{ stats.numberOfViews.toLocaleString() }}
        </span>
        <p class=" text-blue-night font-medium">
          Number of views
        </p>
      </div>

      <!-- Audience last 90 days (Line chart) -->
      <div class="flex flex-col items-center justify-center w-[250px] h-[200px]">
        <LineChart :data="chartDataAudience" :options="chartOptions" class="w-full h-full" />
        <p class=" text-blue-night font-medium mt-2">
          Audience the last 90 days
        </p>
      </div>
    </div>

    <!-- Leave Community -->
    <LeaveCommunity />
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
