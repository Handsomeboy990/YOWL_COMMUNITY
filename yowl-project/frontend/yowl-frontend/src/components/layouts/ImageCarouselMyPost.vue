<template>
  <div v-if="images.length" class="relative w-full">
    <!-- Image  -->
    <img :src="getStorageUrl(images[currentIndex])" alt="carousel image"
      class="w-full h-50 object-cover rounded-lg cursor-pointer" @click= "openModal = true"/>

    <!-- left button -->
    <button v-if="images.length > 1" @click="prev"
      class="cursor-pointer absolute top-1/2 left-2 transform -translate-y-1/2 bg-gray-800 text-white p-2 rounded-full hover:bg-[#FF6B35] transition">
      <i class="fa-solid fa-chevron-left"></i>
    </button>

    <!-- right button -->
    <button v-if="images.length > 1" @click="next"
      class="cursor-pointer absolute top-1/2 right-2 transform -translate-y-1/2 bg-gray-800 text-white p-2 rounded-full hover:bg-[#FF6B35] transition">
      <i class="fa-solid fa-chevron-right "></i>
    </button>

    <!-- indicators -->
    <div v-if="images.length > 1" class="absolute bottom-2 left-1/2 transform -translate-x-1/2 flex space-x-2">
      <span v-for="(img, idx) in images" :key="idx" class="w-3 h-3 rounded-full cursor-pointer"
        :class="idx === currentIndex ? 'bg-[#FF6B35]' : 'bg-gray-300'" @click="goTo(idx)"></span>
    </div>
  </div>

      <!-- preview modal -->
    <div v-if="openModal" class="fixed inset-0 bg-black/90 flex items-center justify-center z-50">
      <button @click="openModal = false" class="absolute top-4 right-4 text-white text-2xl">

        <i class="fa-solid fa-xmark cursor-pointer"></i>
      </button>

      <div class=" w-3/12 md:w-3/4 lg:w-1/2">
        <img :src="getStorageUrl(images[currentIndex])" class="w-full h-auto rounded-lg" />

        <!-- buttons -->
        <button v-if="images.length > 1" @click="prev"
          class="absolute top-1/2 left-2 transform -translate-y-1/2 bg-black/40 text-white p-3 rounded-full">
          ‹
        </button>
        <button v-if="images.length > 1" @click="next"
          class="absolute top-1/2 right-2 transform -translate-y-1/2 bg-black/40 text-white p-3 rounded-full">
          ›
        </button>
      </div>
    </div>
</template>

<script setup>
import { getStorageUrl } from '@/config';
import { ref } from "vue";

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
