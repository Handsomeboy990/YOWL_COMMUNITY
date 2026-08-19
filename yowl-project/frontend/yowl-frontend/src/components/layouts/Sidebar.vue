<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <aside class="flex-shrink-0 w-full">
    <div class="bg-blue-night text-white rounded-lg p-4 sm:p-6">
      <!-- Filtres -->
      <div class="mb-6">
        <h3 class="font-roboto font-medium text-white mb-4 text-lg sm:text-base">Filtrer par</h3>
        <div class="space-y-3">
          <div><BaseCheckbox v-model="noAnswers" dark label="Sans réponses" /></div>
          <div><BaseCheckbox v-model="noViews" dark label="Sans vues" /></div>
          <div><BaseCheckbox v-model="noLikes" dark label="Sans likes" /></div>
        </div>
      </div>

      <!-- Tri -->
      <div class="mb-6">
        <h3 class="font-roboto font-medium text-white mb-4 text-lg sm:text-base">Trier par</h3>
        <div class="space-y-3">
          <div><BaseRadio v-model="sortBy" value="relevant" name="sort" dark label="Les plus pertinentes" /></div>
          <div><BaseRadio v-model="sortBy" value="newest" name="sort" dark label="Plus récentes" /></div>
          <div><BaseRadio v-model="sortBy" value="older" name="sort" dark label="Plus anciennes" /></div>
          <div><BaseRadio v-model="sortBy" value="highestLike" name="sort" dark label="Plus aimées" /></div>
        </div>
      </div>

      <!-- Tags -->
      <div class="mb-6">
        <h3 class="font-roboto font-medium text-white mb-4 text-lg sm:text-base">Avec les tags</h3>
        <input
          v-model="tagInput"
          type="text"
          placeholder="ex : gaming, musique"
          class="w-full px-3.5 py-2.5 bg-white/95 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-orange-primary transition-shadow"
        >
      </div>

      <!-- Les filtres s'appliquent en direct : ce bouton ne sert plus qu'a
           tout remettre a zero, et n'apparait que s'il y a quelque chose a
           remettre a zero. -->
      <BaseButton v-if="hasActiveFilters" variant="ghost" block :shine="false"
        icon="fa-solid fa-arrow-rotate-left" @click="resetAll">
        Réinitialiser
      </BaseButton>
      <p v-else class="text-white/50 text-xs text-center">
        Les filtres s'appliquent au fur et à mesure.
      </p>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useReviewStore } from '@/stores/review';
import BaseCheckbox from '@/components/ui/BaseCheckbox.vue';
import BaseRadio from '@/components/ui/BaseRadio.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const reviewStore = useReviewStore();

/**
 * Chaque controle ecrit directement dans l'etat de la requete du fil, qui
 * declenche le rechargement. Avant, il fallait valider par un bouton, et
 * valider effacait la recherche en cours.
 */
const bind = (key, { immediate = true } = {}) =>
  computed({
    get: () => reviewStore.query[key],
    set: (value) => reviewStore.setQuery({ [key]: value }, { immediate }),
  });

const noAnswers = bind('noAnswers');
const noViews = bind('noViews');
const noLikes = bind('noLikes');
const sortBy = bind('sort');
// La saisie de tags est temporisee, elle se tape caractere par caractere.
const tagInput = bind('tags', { immediate: false });

const hasActiveFilters = computed(() => reviewStore.hasActiveFilters);

function resetAll() {
  reviewStore.resetQuery();
}
</script>
