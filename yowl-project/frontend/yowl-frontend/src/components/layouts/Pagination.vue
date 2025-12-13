<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <nav class="flex justify-center mt-8 pb-20" aria-label="Pagination">
    <div class="flex items-center space-x-2">
      <!-- Previous -->
      <button
        :disabled="pagination.current_page === 1"
        @click="$emit('changePage', pagination.current_page - 1)"
        class="px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md transition-colors"
      >
        Previous
      </button>

      <!-- Pages -->
      <button
        v-for="page in pages"
        :key="page"
        :class="[
          'px-3 py-2 text-sm font-medium rounded-md cursor-pointer transition-colors',
          page === pagination.current_page
            ? 'bg-orange-primary text-white'
            : 'text-gray-700 hover:bg-gray-100'
        ]"
        @click="$emit('changePage', page)"
      >
        {{ page }}
      </button>

      <!-- Next -->
      <button
        :disabled="pagination.current_page === pagination.last_page"
        @click="$emit('changePage', pagination.current_page + 1)"
        class="px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md transition-colors cursor-pointer"
      >
        Next
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

const pages = computed(() => {
  const total = props.pagination.last_page
  const current = props.pagination.current_page
  const start = Math.max(1, current - 2)
  const end = Math.min(total, current + 2)
  return Array.from({ length: end - start + 1 }, (_, i) => start + i)
})
</script>
