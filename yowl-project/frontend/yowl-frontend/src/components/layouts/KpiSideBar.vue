<template>
  <aside class="w-full">
    <div class="text-white rounded-lg flex flex-col h-full justify-between">
      <div>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <span class="font-roboto text-body text-gray-700">{{ t('kpi.members') }}</span>
            <span class="font-poppins text-xl text-orange-text">{{ store.kpi.nbUsers }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="font-roboto text-body text-gray-700">{{ t('kpi.reviews') }}</span>
            <span class="font-poppins text-xl text-orange-text">{{ store.kpi.nbReviews }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="font-roboto text-body text-gray-700">{{ t('kpi.comments') }}</span>
            <span class="font-poppins text-xl text-orange-text">{{ store.kpi.nbComments }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="font-roboto text-body text-gray-700">{{ t('kpi.perDay') }}</span>
            <span class="font-poppins text-xl text-orange-text">{{ store.kpi.nbMeanReviewsPerDay }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="font-roboto text-body text-gray-700">{{ t('kpi.topAge') }}</span>
            <span class="font-poppins text-xl text-orange-text">{{ store.maxRange.range ?? t('kpi.none') }}</span>
          </div>
        </div>
      </div>

      <div class="mt-6">
        <p class="text-center mb-3 text-gray-700">
          {{ userStore.isAuthenticated ? t('kpi.impactPrompt') : t('kpi.joinPrompt') }}
        </p>
        <BaseButton
          :tag="'router-link'"
          :to="userStore.isAuthenticated ? '/user/summary' : '/signup'"
          variant="primary"
          block
        >
          {{ userStore.isAuthenticated ? "Mon impact" : "Rejoindre" }}
        </BaseButton>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { onMounted } from 'vue'
import { useUserStore } from '@/stores/user';
import { useReviewStore } from '@/stores/review';
import BaseButton from '@/components/ui/BaseButton.vue';

const { t } = useI18n();

const store = useReviewStore()
const userStore = useUserStore();

onMounted(async () => {
    store.getKPI()
})
</script>
