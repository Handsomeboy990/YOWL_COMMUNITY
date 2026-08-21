<template>
  <div>
    <!-- La coquille est montee une seule fois, ici, et non par chaque vue.
         Quatorze vues l'enveloppaient chacune : a chaque navigation, l'en-tete,
         les navigations, la connexion temps reel et les signaux de presence
         etaient detruits puis reconstruits, et leurs appels refaits. D'un
         onglet a l'autre, cela se lisait comme un rechargement de page. -->
    <AppShell>
      <router-view v-slot="{ Component, route }">
        <Transition name="page" mode="out-in">
          <component :is="Component" :key="route.path" />
        </Transition>
      </router-view>
    </AppShell>

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
import AppShell from '@/components/layouts/AppShell.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import ConsentBanner from '@/components/ui/ConsentBanner.vue';
</script>

<style>
/* Transition entre les pages.
   Avec mode="out-in", la sortie et l'entree s'additionnent : deux fois 180 ms
   faisaient 360 ms avant que la page suivante ne commence a s'afficher, a
   chaque tape sur un onglet. La sortie est desormais plus courte que
   l'entree, ce qui rend la main plus vite sans donner l'impression que la
   page saute. */
.page-enter-active {
  transition:
    opacity 0.16s ease,
    transform 0.16s ease;
}
.page-leave-active {
  transition:
    opacity 0.08s ease,
    transform 0.08s ease;
}
.page-enter-from {
  opacity: 0;
  transform: translateY(6px);
}
.page-leave-to {
  opacity: 0;
}

/* Respect du reglage systeme : personne ne doit subir une animation. */
/* Sur un ecran etroit, le deplacement vertical se voit plus qu'il n'aide :
   on garde le fondu seul. */
@media (max-width: 640px) {
  .page-enter-from {
    transform: none;
  }
}

@media (prefers-reduced-motion: reduce) {
  .page-enter-active,
  .page-leave-active {
    transition: none;
  }
}
</style>
