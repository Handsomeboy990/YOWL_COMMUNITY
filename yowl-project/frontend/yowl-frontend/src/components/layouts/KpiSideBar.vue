<template>
  <aside class="w-full">
    <div class="text-white rounded-lg flex flex-col h-full justify-between">
      <div>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <span class="font-roboto text-body text-gray-700">Membres actifs</span>
            <span class="font-poppins text-xl text-orange-primary">{{ store.kpi.nbUsers }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="font-roboto text-body text-gray-700">Avis publiés</span>
            <span class="font-poppins text-xl text-orange-primary">{{ store.kpi.nbReviews }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="font-roboto text-body text-gray-700">Commentaires</span>
            <span class="font-poppins text-xl text-orange-primary">{{ store.kpi.nbComments }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="font-roboto text-body text-gray-700">Avis par jour</span>
            <span class="font-poppins text-xl text-orange-primary">{{ store.kpi.nbMeanReviewsPerDay }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="font-roboto text-body text-gray-700">Tranche d'âge la plus active</span>
            <span class="font-poppins text-xl text-orange-primary">{{ store.maxRange["range"] }}</span>
          </div>
        </div>
      </div>

      <div class="mt-6">
        <p class="text-center mb-3 text-gray-700">
          {{ userStore.isAuthenticated ? "Découvre ton impact dans la communauté" : "Rejoins la communauté qui grandit !" }}
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
import { onMounted } from 'vue'
import { useUserStore } from '@/stores/user';
import { useReviewStore } from '@/stores/review';
import BaseButton from '@/components/ui/BaseButton.vue';

const store = useReviewStore()
const userStore = useUserStore();

onMounted(async () => {
    store.getKPI()
})
</script>
