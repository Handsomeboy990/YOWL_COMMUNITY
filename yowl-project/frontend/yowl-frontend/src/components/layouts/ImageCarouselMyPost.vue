<template>
  <div v-if="images.length" class="relative w-full">
    <!-- Image  -->
    <img :src="getStorageUrl(images[currentIndex])" alt="carousel image"
      class="w-full h-50 object-cover rounded-lg cursor-pointer" @click= "openModal = true"/>

    <!-- left button -->
    <button aria-label="Image précédente" v-if="images.length > 1" @click="prev"
      class="cursor-pointer absolute top-1/2 left-2 transform -translate-y-1/2 bg-gray-800 text-white p-2 rounded-full hover:bg-orange-primary transition">
      <Icon name="chevron-left" />
    </button>

    <!-- right button -->
    <button aria-label="Image suivante" v-if="images.length > 1" @click="next"
      class="cursor-pointer absolute top-1/2 right-2 transform -translate-y-1/2 bg-gray-800 text-white p-2 rounded-full hover:bg-orange-primary transition">
      <Icon name="chevron-right" />
    </button>

    <!-- indicators -->
    <div v-if="images.length > 1" class="absolute bottom-2 left-1/2 transform -translate-x-1/2 flex space-x-2">
      <span v-for="(img, idx) in images" :key="idx" class="w-3 h-3 rounded-full cursor-pointer"
        :class="idx === currentIndex ? 'bg-orange-primary' : 'bg-gray-300'" @click="goTo(idx)"></span>
    </div>
  </div>

      <!-- preview modal -->
    <div v-if="openModal" class="fixed inset-0 bg-black/90 flex items-center justify-center z-50">
      <button aria-label="Fermer l'aperçu" @click="openModal = false" class="absolute top-4 right-4 text-white text-2xl">

        <Icon name="xmark" class="cursor-pointer" aria-hidden="true" />
      </button>

      <div class=" w-3/12 md:w-3/4 lg:w-1/2">
        <img :src="getStorageUrl(images[currentIndex])" class="w-full h-auto rounded-lg" />

        <!-- buttons -->
        <button aria-label="Image précédente" v-if="images.length > 1" @click="prev"
          class="absolute top-1/2 left-2 transform -translate-y-1/2 bg-black/40 text-white p-3 rounded-full">
          ‹
        </button>
        <button aria-label="Image suivante" v-if="images.length > 1" @click="next"
          class="absolute top-1/2 right-2 transform -translate-y-1/2 bg-black/40 text-white p-3 rounded-full">
          ›
        </button>
      </div>
    </div>
</template>

<script setup>
import { getStorageUrl } from '@/config';
import { ref } from "vue";

import Icon from '@/components/ui/Icon.vue';
const openModal = ref(false);

const props = defineProps({
  images: {
    type: Array,
    required: true,
    default: () => []
  }
});

const currentIndex = ref(0);

const prev = () => {
  currentIndex.value = (currentIndex.value - 1 + props.images.length) % props.images.length;
};

const next = () => {
  currentIndex.value = (currentIndex.value + 1) % props.images.length;
};

const goTo = (idx) => {
  currentIndex.value = idx;
};
</script>
