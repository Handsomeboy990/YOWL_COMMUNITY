<template>
  <label class="group inline-flex items-center gap-3 cursor-pointer select-none relative">
    <input
      ref="control"
      type="checkbox"
      class="peer absolute left-0 top-1/2 -translate-y-1/2 w-5 h-5 opacity-0 cursor-pointer z-10"
      :checked="modelValue"
      @change="$emit('update:modelValue', $event.target.checked)"
    />

    <!-- La case est dessinee, pas native : le style natif ne suit pas la
         palette. Le fond coche vit dans la seule branche cochee, sinon deux
         utilitaires de fond se disputent la meme propriete et la case reste
         translucide avec une coche blanche invisible dessus. -->
    <span
      class="pointer-events-none grid place-items-center w-5 h-5 shrink-0 rounded-md border-2 transition-colors duration-150 group-hover:scale-105 peer-focus-visible:ring-2 peer-focus-visible:ring-orange-primary/40 peer-focus-visible:ring-offset-2"
      :class="
        modelValue
          ? 'bg-orange-primary border-orange-primary'
          : dark
            ? 'bg-transparent border-white/50 group-hover:border-white/80'
            : 'bg-white border-gray-400 group-hover:border-orange-primary'
      "
      aria-hidden="true"
    >
      <!-- Coche en SVG plutot qu'en police d'icones : elle doit apparaitre
           meme si la police tarde ou ne charge pas. -->
      <svg
        viewBox="0 0 16 16"
        class="w-3.5 h-3.5 transition-all duration-150"
        :class="modelValue ? 'opacity-100 scale-100' : 'opacity-0 scale-50'"
        fill="none"
        stroke="#ffffff"
        stroke-width="2.5"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="M3 8.5 L6.5 12 L13 4.5" />
      </svg>
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
