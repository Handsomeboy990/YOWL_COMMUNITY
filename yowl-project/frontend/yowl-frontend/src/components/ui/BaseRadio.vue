<template>
  <label class="group inline-flex items-center gap-3 cursor-pointer select-none relative">
    <input
      type="radio"
      class="absolute left-0 top-1/2 -translate-y-1/2 w-5 h-5 opacity-0 cursor-pointer z-10"
      :checked="modelValue === value"
      :name="name"
      @change="$emit('update:modelValue', value)"
    />
    <span
      class="pointer-events-none grid place-items-center w-5 h-5 rounded-full border-2 transition-all duration-200 group-hover:scale-105"
      :class="
        modelValue === value
          ? 'border-orange-primary'
          : dark
            ? 'border-white/40 group-hover:border-white/70'
            : 'border-gray-300 group-hover:border-orange-primary/60'
      "
      aria-hidden="true"
    >
      <span
        class="w-2.5 h-2.5 rounded-full bg-orange-primary transition-all duration-200"
        :class="modelValue === value ? 'opacity-100 scale-100' : 'opacity-0 scale-0'"
      ></span>
    </span>
    <span class="text-sm" :class="dark ? 'text-white/90' : 'text-blue-night'">
      <slot>{{ label }}</slot>
    </span>
  </label>
</template>

<script setup>
defineProps({
  modelValue: { type: [String, Number, Boolean], default: null },
  value: { type: [String, Number, Boolean], required: true },
  label: { type: String, default: '' },
  name: { type: String, default: '' },
  dark: { type: Boolean, default: false },
});
defineEmits(['update:modelValue']);
</script>
