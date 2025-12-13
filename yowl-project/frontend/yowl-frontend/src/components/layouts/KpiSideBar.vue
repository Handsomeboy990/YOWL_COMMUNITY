<template>
  <aside class="w-full md:w-1/4 flex-shrink-0">
    <div class="bg-blue-night text-white rounded-lg p-4 sm:p-6 shadow-lg flex flex-col h-full justify-between">
      <div>
        <h2 class="font-poppins font-bold text-xl sm:text-2xl mb-4 text-[#FF6B35] text-center sm:text-left">Our Impact</h2>

        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <span class="font-roboto text-body">Active Users</span>
            <span class="font-poppins text-xl text-[#FF6B35]">{{ store.kpi.nbUsers }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="font-roboto text-body">Reviews Posted</span>
            <span class="font-poppins text-xl text-[#FF6B35]">{{ store.kpi.nbReviews }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="font-roboto text-body">Comments Posted</span>
            <span class="font-poppins text-xl text-[#FF6B35]">{{ store.kpi.nbComments }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="font-roboto text-body">Mean Reviews Posted Daily </span>
            <span class="font-poppins text-xl text-[#FF6B35]">{{ store.kpi.nbMeanReviewsPerDay }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="font-roboto text-body">Age range with most users </span>
            <span class="font-poppins text-xl text-[#FF6B35]">{{ store.maxRange["range"] }}</span>
          </div>
        </div>
      </div>

      <div class="mt-6">
        <p class="text-center mb-3">{{ userStore.isAuthenticated ? "See your impact in our community" : "Be part of the growing community!" }}</p>
        <router-link :to="userStore.isAuthenticated ? '/user/summary' : '/signup'">
          <button class=" cursor-pointer w-full bg-orange-primary hover:bg-orange-600 text-white font-semibold py-2 sm:py-3 px-4 rounded-lg transition">
            {{ userStore.isAuthenticated ? "My impact" : "Join Now" }}
          </button>
        </router-link>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { onMounted } from 'vue'
import { useUserStore } from '@/stores/user';
import { useReviewStore } from '@/stores/review';

const store = useReviewStore()
const userStore = useUserStore();

onMounted(async() => {
    store.getKPI()
    
})


</script>
