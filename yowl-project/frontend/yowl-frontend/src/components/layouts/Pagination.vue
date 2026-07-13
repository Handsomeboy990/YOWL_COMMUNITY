<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <nav class="flex justify-center mt-8 pb-8" aria-label="Pagination">
    <div class="flex items-center space-x-2">
      <!-- Précédent -->
      <button
        :disabled="pagination.current_page === 1"
        class="px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
        @click="$emit('changePage', pagination.current_page - 1)"
      >
        <i class="fa-solid fa-chevron-left text-xs mr-1" aria-hidden="true"></i>
        Précédent
      </button>

      <!-- Pages -->
      <button
        v-for="page in pages"
        :key="page"
        :class="[
          'w-9 h-9 text-sm font-medium rounded-lg cursor-pointer transition-all duration-200',
          page === pagination.current_page
            ? 'bg-orange-primary text-white shadow-md shadow-orange-primary/30'
            : 'text-gray-700 hover:bg-gray-100'
        ]"
        :aria-current="page === pagination.current_page ? 'page' : undefined"
        @click="$emit('changePage', page)"
      >
        {{ page }}
      </button>

      <!-- Suivant -->
      <button
        :disabled="pagination.current_page === pagination.last_page"
        class="px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
        @click="$emit('changePage', pagination.current_page + 1)"
      >
        Suivant
        <i class="fa-solid fa-chevron-right text-xs ml-1" aria-hidden="true"></i>
      </button>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  pagination: {
    type: Object,
    required: true
  }
})

defineEmits(['changePage'])

const pages = computed(() => {
  const total = props.pagination.last_page
  const current = props.pagination.current_page
  const start = Math.max(1, current - 2)
  const end = Math.min(total, current + 2)
  return Array.from({ length: end - start + 1 }, (_, i) => start + i)
})
</script>
