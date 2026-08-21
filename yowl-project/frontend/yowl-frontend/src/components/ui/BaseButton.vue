<template>
  <component
    :is="tag"
    :type="tag === 'button' ? type : undefined"
    :disabled="disabled || loading"
    class="group relative inline-flex items-center justify-center gap-2 font-poppins font-semibold rounded-xl transition-all duration-300 cursor-pointer select-none focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 active:scale-[0.97] disabled:opacity-60 disabled:cursor-not-allowed disabled:active:scale-100"
    :class="[sizeClasses, variantClasses, block ? 'w-full' : '']"
  >
    <span
      v-if="loading"
      class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"
      aria-hidden="true"
    ></span>
    <Icon v-if="icon" :name="icon" :size="16" />
    <slot />
    <span
      v-if="shine"
      class="pointer-events-none absolute inset-0 rounded-xl overflow-hidden"
      aria-hidden="true"
    >
      <span
        class="absolute top-0 left-[-100%] h-full w-1/2 bg-gradient-to-r from-transparent via-white/25 to-transparent skew-x-[-20deg] transition-all duration-700 group-hover:left-[150%]"
      ></span>
    </span>
  </component>
</template>

<script setup>
import { computed } from 'vue';

import Icon from '@/components/ui/Icon.vue';
const props = defineProps({
  variant: { type: String, default: 'primary' }, // primary | night | ghost | danger | outline
  size: { type: String, default: 'md' }, // sm | md | lg | xl
  type: { type: String, default: 'button' },
  tag: { type: [String, Object], default: 'button' },
  icon: { type: String, default: '' },
  block: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  shine: { type: Boolean, default: true },
});

const sizeClasses = computed(
  () =>
    ({
      // La hauteur minimale n'est pas decorative : sous 44 pixels, un bouton
      // se rate au pouce. La mesure sur telephone donnait 36 pixels pour la
      // taille sm, qui sert partout dans les barres et les cartes.
      sm: 'px-4 py-2 text-sm min-h-11',
      md: 'px-6 py-2.5 text-sm md:text-base min-h-11',
      lg: 'px-8 py-3 text-base min-h-12',
      xl: 'px-10 py-4 text-lg min-h-14',
    })[props.size]
);

const variantClasses = computed(
  () =>
    ({
      primary:
        'bg-orange-primary hover:bg-orange-primary-dark text-white shadow-lg shadow-orange-brand/35 hover:shadow-xl hover:shadow-orange-brand/45 hover:-translate-y-0.5 focus-visible:ring-orange-primary',
      night:
        'bg-gradient-to-r from-blue-night to-blue-night-light text-white shadow-lg shadow-blue-night/30 hover:shadow-xl hover:shadow-blue-night/40 hover:-translate-y-0.5 focus-visible:ring-blue-night',
      ghost:
        'bg-transparent text-blue-night hover:bg-blue-night/5 focus-visible:ring-blue-night',
      outline:
        'bg-transparent border-2 border-white/80 text-white hover:bg-white hover:text-blue-night focus-visible:ring-white',
      danger:
        'bg-gradient-to-r from-red-500 to-red-600 text-white shadow-lg shadow-red-500/30 hover:shadow-xl hover:-translate-y-0.5 focus-visible:ring-red-500',
    })[props.variant]
);
</script>
