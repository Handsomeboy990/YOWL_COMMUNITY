<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <footer class="bg-blue-night w-full fixed left-0 bottom-0 text-white border-t-4 border-[#FF6B35] md:bg-blue-night md:w-full md:fixed md:left-0 md:bottom-0 md:text-white md:border-t-4 md:border-[#FF6B35]">

      <div class="max-w-7xl mx-auto flex items-center justify-between px-6 py-3">
        <!-- left: contact -->
        <div class="order-1 md:order-1 text-sm">
          <router-link to="/suggestion" class="hover:text-[#FF6B35] transition-colors">Any suggestion ?</router-link>
        </div>

        <!-- middle: social media -->
        <nav class="order-2 md:order-2 flex items-center gap-6 text-sm">
          <a href="#" class="hover:text-[#FF6B35] transition-colors">Facebook</a>
          <a href="#" class="hover:text-[#FF6B35] transition-colors">Instagram</a>
          <a href="#" class="hover:text-[#FF6B35] transition-colors">Twitter</a>
        </nav>

        <!-- right: FAQ / Policy -->
        <div class="order-3 md:order-3 flex items-center gap-6 text-sm">
          <router-link to="/faq" class="hover:text-[#FF6B35] transition-colors">FAQ</router-link>
          <router-link to="/policy" class="hover:text-[#FF6B35] transition-colors">Policy</router-link>
        </div>
      </div>

      <!-- copyright  -->
      <div class="border-t border-white/10 mt-2 pt-1 pb-1 text-center text-sm">
        © 2025 Made by <span class="text-white font-semibold">LONG Corp</span>
      </div>

  </footer>
</template>

<script setup>


</script>
