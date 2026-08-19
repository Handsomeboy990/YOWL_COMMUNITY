<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <AppShell>
  <div class="max-w-3xl mx-auto py-10 px-6">
    <h1 class="text-3xl font-bold text-blue-night mb-6">Foire aux questions</h1>

    <div class="space-y-6">
      <div>
        <h2 class="font-semibold text-lg text-orange-text">1. Qu'est-ce que YOWL Community ?</h2>
        <p class="text-gray-700">YOWL est une plateforme sociale interactive où les jeunes partagent leurs avis et leurs expériences tout en échangeant avec les autres dans un cadre sûr.</p>
      </div>

      <div>
        <h2 class="font-semibold text-lg text-orange-text">2. Qui peut rejoindre YOWL ?</h2>
        <p class="text-gray-700">YOWL est réservé aux personnes de 13 à 35 ans. Cet équilibre générationnel garantit des discussions pertinentes et engageantes.</p>
      </div>

      <div>
        <h2 class="font-semibold text-lg text-orange-text">3. Puis-je publier des liens, des images ou des vidéos ?</h2>
        <p class="text-gray-700">Oui, les membres peuvent partager des liens, ajouter des images et des tags. Tout contenu doit respecter notre
          <em>
            <router-link to="/policy" class="hover:underline hover:text-orange-text">charte</router-link>
          </em>.
        </p>
      </div>

      <div>
        <h2 class="font-semibold text-lg text-orange-text">4. Comment sont gérés les contenus inappropriés ?</h2>
        <p class="text-gray-700">Tout contenu signalé est examiné par notre équipe de modération. Les infractions peuvent entraîner un avertissement, la suppression du contenu ou une suspension définitive.</p>
      </div>

      <div>
        <h2 class="font-semibold text-lg text-orange-text">5. Comment signaler un membre ou une publication ?</h2>
        <p class="text-gray-700">Une option « Signaler » sera disponible sur chaque avis et commentaire ( <strong>V1.2</strong> ). Utilise-la pour prévenir les modérateurs de toute infraction.</p>
      </div>

      <div>
        <h2 class="font-semibold text-lg text-orange-text">6. Puis-je suggérer de nouvelles fonctionnalités ?</h2>
        <p class="text-gray-700">Bien sûr ! Rends-toi sur la page
          <em>
            <router-link to="/suggestion" class="hover:underline hover:text-orange-text">Une suggestion ?</router-link>
          </em>
          pour partager tes idées et nous aider à améliorer <strong>YOWL Community</strong>.</p>
      </div>
    </div>
  </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/components/layouts/AppShell.vue';
</script>
