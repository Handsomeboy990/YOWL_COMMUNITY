<template>
  <div>
    <router-view v-slot="{ Component, route }">
      <Transition name="page" mode="out-in">
        <component :is="Component" :key="route.path" />
      </Transition>
    </router-view>

    <!-- Notifications transitoires, montees une seule fois pour toute l'app -->
    <Toaster
      position="bottom-right"
      rich-colors
      close-button
      :duration="4000"
      :toast-options="{ class: 'yowl-toast' }"
    />

    <!-- Dialogue de confirmation pilote par useConfirm() -->
    <ConfirmDialog />

    <!-- Accord pour la mesure detaillee. Charge a la demande : il ne
         s'affiche qu'une fois par appareil et n'a rien a peser dans le
         paquet initial. -->
    <ConsentBanner />
  </div>
</template>

<script setup>
import { Toaster } from 'vue-sonner';
// vue-sonner v2 ne charge pas sa feuille de style toute seule : sans cet
// import les toasts s'empilent sans mise en forme, en haut de la page.
import 'vue-sonner/style.css';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import ConsentBanner from '@/components/ui/ConsentBanner.vue';
</script>

<style>
/* Transition entre les pages. Discrete : une page qui glisse trop donne
   l'impression que l'application rame. */
.page-enter-active,
.page-leave-active {
  transition:
    opacity 0.18s ease,
    transform 0.18s ease;
}
.page-enter-from {
  opacity: 0;
  transform: translateY(6px);
}
.page-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

/* Respect du reglage systeme : personne ne doit subir une animation. */
@media (prefers-reduced-motion: reduce) {
  .page-enter-active,
  .page-leave-active {
    transition: none;
  }
}
</style>
