<template>
  <label class="group inline-flex items-center gap-3 cursor-pointer select-none relative">
    <input
      type="checkbox"
      class="absolute left-0 top-1/2 -translate-y-1/2 w-5 h-5 opacity-0 cursor-pointer z-10"
      :checked="modelValue"
      @change="$emit('update:modelValue', $event.target.checked)"
    />
    <span
      class="pointer-events-none grid place-items-center w-5 h-5 rounded-md border-2 transition-all duration-200 bg-white/10 group-hover:scale-105"
      :class="
        modelValue
          ? 'bg-orange-primary border-orange-primary shadow-sm shadow-orange-primary/40'
          : dark
            ? 'border-white/40 group-hover:border-white/70'
            : 'border-gray-300 group-hover:border-orange-primary/60'
      "
      aria-hidden="true"
    >
      <i
        class="fa-solid fa-check text-[10px] text-white transition-all duration-200"
        :class="modelValue ? 'opacity-100 scale-100' : 'opacity-0 scale-50'"
      ></i>
    </span>
    <span class="text-sm" :class="dark ? 'text-white/90' : 'text-blue-night'">
      <slot>{{ label }}</slot>
    </span>
  </label>
</template>

<script setup>
defineProps({
  modelValue: { type: Boolean, default: false },
  label: { type: String, default: '' },
  dark: { type: Boolean, default: false },
});
defineEmits(['update:modelValue']);
</script>
