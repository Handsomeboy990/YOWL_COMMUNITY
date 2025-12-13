<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <Header />
  <div class="max-w-4xl mx-auto py-10 px-6">
    <h1 class="text-3xl font-bold text-[#1E2A38] mb-6">Any Suggestion ?</h1>

    <p class="text-gray-700 mb-6">
      YOWL Community is constantly evolving, and your feedback is at the heart of our progress.
      If you have ideas, feature requests, or suggestions for improvement, we’d love to hear from you.
    </p>

    <form class="space-y-4">
      <div>
        <label class="block text-sm font-medium mb-1">Your Name (optional)</label>
        <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-primary focus:outline-none" placeholder="John Doe" />
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Your Email (optional)</label>
        <input type="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-primary focus:outline-none" placeholder="you@example.com" />
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Your Suggestion</label>
        <textarea rows="5" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-primary focus:outline-none" placeholder="Write your suggestion here..."></textarea>
      </div>

      <div class="flex justify-end">
        <button type="submit" class="px-6 py-2 bg-orange-primary text-white rounded-lg hover:bg-[#e75a27] transition" required>
          Submit
        </button>
      </div>
    </form>
  </div>
  <Footer />
</template>


<script setup>
import Footer from '../layouts/Footer.vue';
import Header from '../layouts/Header.vue';

</script>
