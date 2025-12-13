<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <aside class="flex-shrink-0 w-full md:w-1/4 mb-6 md:mb-0">
    <div class="bg-blue-night text-white rounded-lg p-4 sm:p-6">
      <!-- Filter by -->
      <div class="mb-6">
        <h3 class="font-roboto font-medium text-white mb-4 text-lg sm:text-base">Filtered by</h3>
        <div class="space-y-3">
          <label class="flex items-center">
            <input type="checkbox" v-model="filters.noAnswers" class="rounded border-gray-300 cursor-pointer text-[#FF6B35] focus:ring-[#FF6B35]">
            <span class="cursor-pointer ml-2 text-sm font-roboto">No answers</span>
          </label>
          <label class="flex items-center">
            <input type="checkbox" v-model="filters.noViews" class="rounded border-gray-300 cursor-pointer text-[#FF6B35] focus:ring-[#FF6B35]">
            <span class="cursor-pointer ml-2 text-sm font-roboto">No views</span>
          </label>
          <label class="flex items-center">
            <input type="checkbox" v-model="filters.noLikes" class="rounded border-gray-300 cursor-pointer text-[#FF6B35] focus:ring-[#FF6B35]">
            <span class="cursor-pointer ml-2 text-sm font-roboto">No likes</span>
          </label>
        </div>
      </div>

      <!-- Sorted by -->
      <div class="mb-6">
        <h3 class="font-roboto font-medium text-white mb-4 text-lg sm:text-base">Sorted by</h3>
        <div class="space-y-3">
          <label class="flex items-center">
            <input type="radio" v-model="sortBy" value="newest" name="sort" class="cursor-pointer text-[#FF6B35] focus:ring-[#FF6B35]">
            <span class="cursor-pointer ml-2 text-sm font-roboto">Newest</span>
          </label>
          <label class="flex items-center">
            <input type="radio" v-model="sortBy" value="older" name="sort" class="cursor-pointer text-[#FF6B35] focus:ring-[#FF6B35]">
            <span class="cursor-pointer ml-2 text-sm font-roboto">Older</span>
          </label>
          <label class="flex items-center">
            <input type="radio" v-model="sortBy" value="highestLike" name="sort" class="cursor-pointer text-[#FF6B35] focus:ring-[#FF6B35]">
            <span class="cursor-pointer ml-2 text-sm font-roboto">Highest like</span>
          </label>
        </div>
      </div>

      <!-- Tagged with -->
      <div class="mb-6">
        <h3 class="font-roboto font-medium text-white mb-4 text-lg sm:text-base">Tagged with</h3>
        <input
          type="text"
          v-model="tagInput"
          placeholder="e.g. electronic, twitter"
          class="w-full px-3 py-2 bg-white rounded-md text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF6B35]"
        >
      </div>

      <!-- Apply Filter Button -->
      <div class="flex items-center justify-center">
        <button
          class="cursor-pointer hover:translate-y-[-4px] w-full sm:max-w-[80%] bg-white text-[#1E2A38] font-medium py-2 px-4 rounded-md hover:bg-gray-100 transition-colors"
          @click="applyFilter"
        >
          Apply filter
        </button>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { ref } from 'vue';
import { useReviewStore } from '@/stores/review';
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
