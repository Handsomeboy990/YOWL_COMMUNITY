<template>
  <div class="px-6 py-14 text-center">
    <span class="w-14 h-14 mx-auto rounded-2xl bg-gray-50 grid place-items-center">
      <i :class="[icon, 'text-2xl text-gray-300']"></i>
    </span>
    <p class="mt-4 font-semibold text-blue-night">{{ title }}</p>
    <p v-if="description" class="mt-1 text-sm text-gray-400 max-w-sm mx-auto">{{ description }}</p>
    <div v-if="$slots.action" class="mt-5">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  icon: { type: String, default: 'fa-regular fa-folder-open' },
  title: { type: String, required: true },
  description: { type: String, default: '' },
});
</script>
