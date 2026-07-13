<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <footer class="bg-blue-night w-full text-white border-t-4 border-orange-primary">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-4">
      <!-- Gauche : suggestions -->
      <div class="text-sm">
        <router-link to="/suggestion" class="hover:text-orange-primary transition-colors">
          Une suggestion ?
        </router-link>
      </div>

      <!-- Milieu : réseaux sociaux -->
      <nav class="flex items-center gap-6 text-sm" aria-label="Réseaux sociaux">
        <a href="https://facebook.com" target="_blank" rel="noopener" class="hover:text-orange-primary transition-colors">Facebook</a>
        <a href="https://instagram.com" target="_blank" rel="noopener" class="hover:text-orange-primary transition-colors">Instagram</a>
        <a href="https://x.com" target="_blank" rel="noopener" class="hover:text-orange-primary transition-colors">Twitter</a>
      </nav>

      <!-- Droite : FAQ / Charte -->
      <div class="flex items-center gap-6 text-sm">
        <router-link to="/faq" class="hover:text-orange-primary transition-colors">FAQ</router-link>
        <router-link to="/policy" class="hover:text-orange-primary transition-colors">Charte</router-link>
      </div>
    </div>

    <!-- Copyright -->
    <div class="border-t border-white/10 py-2 text-center text-sm text-white/70">
      © {{ new Date().getFullYear() }} YOWL Community — Réalisé par LONG Corp
    </div>
  </footer>
</template>

<script setup>
</script>
