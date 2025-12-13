<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <Header />
  <div class="container mx-auto p-6">
    <UserProfilData />
    <div class="flex space-x-4 mb-10">
      <router-link
        to="/user/summary"
        class="px-6 py-2 rounded-lg font-roboto text-[14px] text-white bg-blue-night hover:bg-[#FF6B35] transition"
        >Summary</router-link
      >
      <router-link
        to="/user/my-reviews"
        class="px-6 py-2 rounded-lg font-roboto text-[14px] text-white bg-blue-night hover:bg-[#FF6B35] transition"
        >My reviews</router-link
      >
      <router-link
        to="/user/activity"
        class="px-6 py-2 rounded-lg font-roboto text-[14px] text-white bg-orange-primary hover:bg-[#FF6B35] transition"
        >Activity</router-link
      >
    </div>

    <div class="bg-gray-100 rounded-lg border-4 border-[#FF6B35] p-6 min-h-[200px]">
      <div v-if="loading" class="flex items-center justify-center h-32">
        <span class="text-gray-500">Chargement des activités...</span>
      </div>
      <div v-else>
        <template v-if="!todayActivities.length && !yesterdayActivities.length">
          <div class="text-center text-gray-500 py-8">Aucune activité récente.</div>
        </template>
        <template v-else>
          <h2 class="font-bold text-lg mb-4 text-[#1E2A38]">Today</h2>
          <ul class="space-y-3 mb-6">
            <li
              v-for="(activity, index) in todayActivities"
              :key="'today-' + index"
              class="flex justify-between items-center"
            >
              <span class="text-gray-700">
                <span
                  v-if="activity.type"
                  class="font-bold text-xs px-2 py-1 rounded bg-orange-100 text-orange-700 mr-2"
                  >{{ activity.type }}</span
                >
                {{ activity.text }}
              </span>
              <span class="text-gray-500 text-sm">{{ activity.timeAgo }}</span>
            </li>
          </ul>
          <hr class="border-gray-400 my-4" />
          <h2 class="font-bold text-lg mb-4 text-[#1E2A38]">Yesterday</h2>
          <ul class="space-y-3">
            <li
              v-for="(activity, index) in yesterdayActivities"
              :key="'yesterday-' + index"
              class="flex justify-between items-center"
            >
              <span class="text-gray-700">
                <span
                  v-if="activity.type"
                  class="font-bold text-xs px-2 py-1 rounded bg-orange-100 text-orange-700 mr-2"
                  >{{ activity.type }}</span
                >
                {{ activity.text }}
              </span>
              <span class="text-gray-500 text-sm">{{ activity.timeAgo }}</span>
            </li>
          </ul>
        </template>
        <div v-if="error" class="text-red-500 text-center mt-4">{{ error }}</div>
        <div class="flex justify-center gap-4 mt-6">
          <button
            @click="prevPage"
            :disabled="page === 1"
            class="px-4 py-2 rounded bg-gray-300 text-gray-700 hover:bg-[#FF6B35] disabled:opacity-50"
          >
            Previous
          </button>
          <span class="px-4 py-2">Page {{ page }}</span>
          <button
            @click="nextPage"
            class="px-4 py-2 rounded bg-gray-300 text-gray-700 hover:bg-[#FF6B35]"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <LeaveCommunity class="mt-6" />
  </div>
  <Footer />
</template>

<script setup>
import Header from '@/components/layouts/Header.vue';
import Footer from '@/components/layouts/Footer.vue';
import UserProfilData from '@/components/layouts/UserProfilData.vue';
import LeaveCommunity from '@/components/layouts/LeaveCommunity.vue';
import { ref, onMounted, watch } from 'vue';
import { useUserStore } from '@/stores/user';
import api from '@/services/apiService';
import { useReviewStore } from '@/stores/review';

const todayActivities = ref([]);
const yesterdayActivities = ref([]);
const loading = ref(true);
const error = ref('');
const page = ref(1);
const perPage = 10;

const userStore = useUserStore();
const reviewStore = useReviewStore();

const fetchActivities = async () => {
  loading.value = true;
  error.value = '';
  try {
    if (userStore.user) {
      const res = await api.get(
        `/users/${userStore.user.id}/activity?page=${page.value}&perPage=${perPage}`,
        {
          headers: { Authorization: `Bearer ${userStore.token}` },
        }
      );

      todayActivities.value = [];
      yesterdayActivities.value = [];
      if (!res.data.length) {
        loading.value = false;
        return;
      }
      res.data.forEach((act) => {
        const created = new Date(act.created_at);
        const now = new Date();
        const seconds = Math.floor((now - created) / 1000);

        let timeAgo;
        if (seconds < 60) {
          timeAgo = `${seconds} sec ago`;
        } else if (seconds < 3600) {
          timeAgo = `${Math.floor(seconds / 60)} min ago`;
        } else if (seconds < 86400) {
          timeAgo = `${Math.floor(seconds / 3600)} h ago`;
        } else {
          timeAgo = `${Math.floor(seconds / 86400)} day ago`;
        }

        const activity = {
          text: act.text,
          timeAgo: timeAgo,
          type: act.type || null,
        };

        // Classement par jour
        if (created.toDateString() === now.toDateString()) {
          todayActivities.value.push(activity);
        } else {
          const yesterday = new Date(now);
          yesterday.setDate(now.getDate() - 1);
          if (created.toDateString() === yesterday.toDateString()) {
            yesterdayActivities.value.push(activity);
          }
        }
      });
    }
  } catch (e) {
    error.value = 'Impossible de charger les activités.';
    console.error('Erreur fetch activity', e);
  } finally {
    loading.value = false;
  }
};

onMounted(fetchActivities);
watch(() => reviewStore.reviews, fetchActivities, { deep: true });
const nextPage = () => {
  page.value++;
  fetchActivities();
};
const prevPage = () => {
  if (page.value > 1) {
    page.value--;
    fetchActivities();
  }
};
</script>
