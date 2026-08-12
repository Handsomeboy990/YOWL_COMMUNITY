<template>
  <div class="p-5 space-y-3" role="status" aria-label="Chargement en cours">
    <div v-for="row in rows" :key="row" class="flex items-center gap-4 animate-pulse">
      <div class="w-10 h-10 rounded-full bg-gray-200 shrink-0"></div>
      <div class="flex-1 space-y-2">
        <div class="h-3 bg-gray-200 rounded w-1/3"></div>
        <div class="h-3 bg-gray-100 rounded w-2/3"></div>
      </div>
      <div class="w-20 h-6 rounded-lg bg-gray-100 shrink-0"></div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  rows: { type: Number, default: 5 },
});
</script>
