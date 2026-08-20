<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <AppShell>
  <div class="w-full px-4 xl:px-6 py-6">
    <ProfileHeader />

    <div class="mt-6 bg-white border border-gray-200 rounded-2xl p-5 sm:p-6 min-h-[200px] animate-fade-in-up">
      <div v-if="loading" class="space-y-3">
        <div v-for="n in 5" :key="n" class="h-5 rounded skeleton"></div>
      </div>
      <div v-else>
        <template v-if="!groups.length">
          <div class="flex flex-col items-center text-center py-12">
            <i class="fa-regular fa-clock text-4xl text-gray-400" aria-hidden="true"></i>
            <h2 class="mt-4 text-lg font-semibold text-gray-800">{{ t('profile.activityEmpty') }}</h2>
            <p class="mt-2 text-sm text-gray-600 max-w-md">
              {{ t('profile.activityEmptyHelp') }}
            </p>
          </div>
        </template>
        <template v-else>
          <div v-for="(group, groupIndex) in groups" :key="group.label">
            <hr v-if="groupIndex > 0" class="border-gray-300 my-4" />
            <h2 class="font-bold text-lg mb-4 text-blue-night">{{ group.label }}</h2>
            <ul class="space-y-3 mb-6">
              <li v-for="(activity, index) in group.items" :key="group.label + '-' + index"
                class="flex justify-between items-start gap-4 py-2 border-b border-gray-50 last:border-0">
                <span class="text-gray-700">
                  <span v-if="activity.type"
                    class="font-medium text-xs px-2 py-0.5 rounded-full bg-orange-50 text-orange-700 mr-2 capitalize">
                    {{ activity.type }}
                  </span>
                  {{ activity.text }}
                </span>
                <span class="text-gray-500 text-sm whitespace-nowrap">{{ activity.timeAgo }}</span>
              </li>
            </ul>
          </div>
        </template>
        <div v-if="error" class="text-red-500 text-center mt-4">{{ error }}</div>
      </div>
    </div>

    <LeaveCommunity class="mt-6" />
  </div>
  </AppShell>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import AppShell from '@/components/layouts/AppShell.vue';
import ProfileHeader from '@/components/layouts/ProfileHeader.vue';
import LeaveCommunity from '@/components/layouts/LeaveCommunity.vue';
import { ref, onMounted } from 'vue';
import { useUserStore } from '@/stores/user';
import api from '@/services/apiService';

const { t } = useI18n();

const groups = ref([]);
const loading = ref(true);
const error = ref('');

const userStore = useUserStore();

const timeAgo = (created) => {
  const seconds = Math.floor((Date.now() - created.getTime()) / 1000);
  if (seconds < 60) return `il y a ${seconds} s`;
  if (seconds < 3600) return `il y a ${Math.floor(seconds / 60)} min`;
  if (seconds < 86400) return `il y a ${Math.floor(seconds / 3600)} h`;
  const days = Math.floor(seconds / 86400);
  return `il y a ${days} jour${days > 1 ? 's' : ''}`;
};

const fetchActivities = async () => {
  loading.value = true;
  error.value = '';
  try {
    if (!userStore.user) return;

    const res = await api.get(`/users/${userStore.user.id}/activity`);

    const today = [];
    const yesterday = [];
    const older = [];
    const now = new Date();
    const yesterdayDate = new Date(now);
    yesterdayDate.setDate(now.getDate() - 1);

    (res.data || []).forEach((act) => {
      const created = new Date(act.created_at);
      const activity = {
        text: act.text,
        timeAgo: timeAgo(created),
        type: act.type || null,
      };

      if (created.toDateString() === now.toDateString()) {
        today.push(activity);
      } else if (created.toDateString() === yesterdayDate.toDateString()) {
        yesterday.push(activity);
      } else {
        older.push(activity);
      }
    });

    groups.value = [
      { label: "Aujourd'hui", items: today },
      { label: 'Hier', items: yesterday },
      { label: 'Plus ancien', items: older },
    ].filter((group) => group.items.length > 0);
  } catch {
    error.value = 'Impossible de charger les activités.';
  } finally {
    loading.value = false;
  }
};

onMounted(fetchActivities);
</script>
