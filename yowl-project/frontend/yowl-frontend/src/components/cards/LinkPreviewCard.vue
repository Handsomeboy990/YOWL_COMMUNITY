<template>
  <a :href="url" target="_blank" rel="noopener noreferrer"
    class="group block rounded-xl border border-gray-200 overflow-hidden hover:border-orange-primary transition-colors">
    <!-- Visuel de la page citee, quand elle en publie un -->
    <div v-if="preview?.image && !imageFailed" class="aspect-[1200/630] bg-gray-100 overflow-hidden">
      <img :src="preview.image" :alt="''" loading="lazy"
        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
        @error="imageFailed = true" />
    </div>

    <div class="flex items-start gap-3 p-3">
      <span v-if="!preview?.image || imageFailed"
        class="w-10 h-10 shrink-0 rounded-lg bg-orange-primary/10 grid place-items-center text-orange-primary">
        <i class="fa-solid fa-link"></i>
      </span>

      <span class="min-w-0 flex-1">
        <span class="block text-[11px] uppercase tracking-wide text-gray-400">{{ site }}</span>
        <span v-if="preview?.title" class="block font-medium text-blue-night leading-snug line-clamp-2 mt-0.5">
          {{ preview.title }}
        </span>
        <span v-else class="block text-sm text-blue-night truncate mt-0.5">{{ url }}</span>
        <span v-if="preview?.description" class="block text-sm text-gray-500 line-clamp-2 mt-1">
          {{ preview.description }}
        </span>
      </span>

      <i class="fa-solid fa-arrow-up-right-from-square text-gray-300 group-hover:text-orange-primary transition-colors mt-1"></i>
    </div>
  </a>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  url: { type: String, required: true },
  preview: { type: Object, default: null },
});

// L'image distante peut disparaitre apres la mise en cache de l'apercu :
// la carte retombe alors sur sa forme compacte au lieu d'un cadre vide.
const imageFailed = ref(false);

const site = computed(() => {
  if (props.preview?.site_name) return props.preview.site_name;
  try {
    return new URL(props.url).hostname.replace(/^www\./, '');
  } catch {
    return props.url;
  }
});
</script>
