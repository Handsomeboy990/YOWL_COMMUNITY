<template>
  <div class="min-h-screen bg-blue-night flex items-center justify-center px-6 relative overflow-hidden">
    <div class="blob-404" aria-hidden="true"></div>
    <div class="relative text-center max-w-lg">
      <p class="font-poppins font-extrabold text-[9rem] leading-none gradient-text">404</p>
      <h1 class="font-poppins font-bold text-2xl text-white mt-2">Page introuvable</h1>
      <p class="mt-4 text-white/60 leading-relaxed">
        La page que vous cherchez n'existe pas ou a été déplacée.
        Retournez sur le fil pour continuer à explorer les avis de la communauté.
      </p>
      <div class="mt-8 flex justify-center gap-4">
        <BaseButton :tag="'router-link'" :to="'/'" variant="primary" size="lg" icon="fa-solid fa-house">
          Retour à l'accueil
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import BaseButton from '@/components/ui/BaseButton.vue';
</script>

<style scoped>
.blob-404 {
  position: absolute;
  width: 500px;
  height: 500px;
  border-radius: 9999px;
  background: rgba(255, 107, 53, 0.18);
  filter: blur(100px);
  animation: float404 10s ease-in-out infinite alternate;
}

@keyframes float404 {
  from {
    transform: translate(-60px, -40px);
  }
  to {
    transform: translate(60px, 40px);
  }
}
</style>
