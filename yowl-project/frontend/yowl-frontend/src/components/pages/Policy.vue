<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <AppShell>
  <div class="max-w-3xl mx-auto py-10 px-6">
    <h1 class="text-3xl font-bold text-blue-night mb-6">Charte de la communauté</h1>

    <p class="mb-4 text-gray-700">
      YOWL Community est un espace numérique sûr, réservé aux personnes de <strong>13 à 35 ans</strong>.
      En rejoignant la plateforme, tu <strong>t'engages</strong> à respecter les règles ci-dessous.
      Tout manquement peut entraîner une <strong>suspension ou une suppression définitive du compte</strong>.
    </p>

    <h2 class="font-semibold text-lg text-orange-text mt-6">1. Limite d'âge</h2>
    <p class="text-gray-700 mb-4">
      La plateforme est strictement réservée aux 13-35 ans. Les membres qui mentent sur leur âge ou
      dépassent la limite seront retirés sans préavis. Cette règle garantit des conversations pertinentes
      pour notre public.
    </p>

    <h2 class="font-semibold text-lg text-orange-text mt-6">2. Communication respectueuse</h2>
    <p class="text-gray-700 mb-4">
      Les discours haineux, le harcèlement, la discrimination et tout contenu offensant sont interdits.
      Chaque membre doit échanger avec respect et contribuer à un environnement accueillant.
    </p>

    <h2 class="font-semibold text-lg text-orange-text mt-6">3. Partage de contenu</h2>
    <div class="text-gray-700 mb-4">
      Les membres peuvent publier des reviews, images, vidéos et liens externes. Tout contenu doit :
      <ul class="list-disc ml-6 mt-2">
        <li>Respecter le droit d'auteur.</li>
        <li>Convenir à une communauté jeune (pas de nudité, de contenu explicite ou violent).</li>
        <li>Rester pertinent pour une discussion constructive et le partage de connaissances.</li>
      </ul>
    </div>

    <h2 class="font-semibold text-lg text-orange-text mt-6">4. Vie privée et sécurité</h2>
    <p class="text-gray-700 mb-4">
      Chaque membre doit protéger ses informations personnelles. Le partage de données sensibles
      (adresse, numéro de téléphone, coordonnées bancaires) est strictement interdit. YOWL ne te
      demandera jamais ce type d'informations via les reviews ou les publications.
    </p>

    <h2 class="font-semibold text-lg text-orange-text mt-6">5. Modération</h2>
    <div class="text-gray-700 mb-4">
      YOWL se réserve le droit de :
      <ul class="list-disc ml-6 mt-2">
        <li>Supprimer tout contenu qui enfreint cette charte.</li>
        <li>Adresser des avertissements aux membres concernés.</li>
        <li>Exclure définitivement les membres en cas de récidive.</li>
      </ul>
    </div>

    <h2 class="font-semibold text-lg text-orange-text mt-6">6. Le mot de la fin</h2>
    <p class="text-gray-700">
      En participant à YOWL Community, tu t'engages à maintenir un environnement sûr et inspirant pour
      tout le monde. Notre objectif : une plateforme dynamique, respectueuse et enrichissante pour les jeunes.
    </p>
  </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/components/layouts/AppShell.vue';
</script>
