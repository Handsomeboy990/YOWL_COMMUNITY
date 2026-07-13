<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <aside class="flex-shrink-0 w-full">
    <div class="bg-blue-night text-white rounded-lg p-4 sm:p-6">
      <!-- Filtres -->
      <div class="mb-6">
        <h3 class="font-roboto font-medium text-white mb-4 text-lg sm:text-base">Filtrer par</h3>
        <div class="space-y-3">
          <div><BaseCheckbox v-model="filters.noAnswers" dark label="Sans réponses" /></div>
          <div><BaseCheckbox v-model="filters.noViews" dark label="Sans vues" /></div>
          <div><BaseCheckbox v-model="filters.noLikes" dark label="Sans likes" /></div>
        </div>
      </div>

      <!-- Tri -->
      <div class="mb-6">
        <h3 class="font-roboto font-medium text-white mb-4 text-lg sm:text-base">Trier par</h3>
        <div class="space-y-3">
          <div><BaseRadio v-model="sortBy" value="newest" name="sort" dark label="Plus récentes" /></div>
          <div><BaseRadio v-model="sortBy" value="older" name="sort" dark label="Plus anciennes" /></div>
          <div><BaseRadio v-model="sortBy" value="highestLike" name="sort" dark label="Plus aimées" /></div>
        </div>
      </div>

      <!-- Tags -->
      <div class="mb-6">
        <h3 class="font-roboto font-medium text-white mb-4 text-lg sm:text-base">Avec les tags</h3>
        <input
          v-model="tagInput"
          type="text"
          placeholder="ex : gaming, musique"
          class="w-full px-3.5 py-2.5 bg-white/95 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-orange-primary transition-shadow"
        >
      </div>

      <!-- Bouton d'application -->
      <BaseButton variant="primary" block :shine="false" icon="fa-solid fa-filter" @click="applyFilter">
        Appliquer les filtres
      </BaseButton>
    </div>
  </aside>
</template>

<script setup>
import { ref } from 'vue';
import { useReviewStore } from '@/stores/review';
import BaseCheckbox from '@/components/ui/BaseCheckbox.vue';
import BaseRadio from '@/components/ui/BaseRadio.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const reviewStore = useReviewStore();

const filters = ref({
  noAnswers: false,
  noViews: false,
  noLikes: false,
});

const sortBy = ref('newest');
const tagInput = ref('');

function applyFilter() {
  reviewStore.filterReviews({
    noAnswers: filters.value.noAnswers,
    noViews: filters.value.noViews,
    noLikes: filters.value.noLikes,
    sortBy: sortBy.value,
    tags: tagInput.value,
  });
}
</script>
