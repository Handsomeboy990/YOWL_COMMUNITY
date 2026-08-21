<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <aside class="flex-shrink-0 w-full">
    <div class="bg-blue-night text-white rounded-lg p-4 sm:p-6">
      <!-- Filtres -->
      <div class="mb-6">
        <h3 class="font-roboto font-medium text-white mb-4 text-lg sm:text-base">{{ t('filters.filterBy') }}</h3>
        <div class="space-y-3">
          <div><BaseCheckbox v-model="noAnswers" dark :label="t('filters.noAnswers')" /></div>
          <div><BaseCheckbox v-model="noViews" dark :label="t('filters.noViews')" /></div>
          <div><BaseCheckbox v-model="noLikes" dark :label="t('filters.noLikes')" /></div>
        </div>
      </div>

      <!-- Tri -->
      <div class="mb-6">
        <h3 class="font-roboto font-medium text-white mb-4 text-lg sm:text-base">{{ t('filters.sortBy') }}</h3>
        <div class="space-y-3">
          <div><BaseRadio v-model="sortBy" value="relevant" name="sort" dark :label="t('filters.relevant')" /></div>
          <div><BaseRadio v-model="sortBy" value="newest" name="sort" dark :label="t('filters.newest')" /></div>
          <div><BaseRadio v-model="sortBy" value="older" name="sort" dark :label="t('filters.oldest')" /></div>
          <div><BaseRadio v-model="sortBy" value="highestLike" name="sort" dark :label="t('filters.mostLiked')" /></div>
        </div>
      </div>

      <!-- Tags -->
      <div class="mb-6">
        <h3 class="font-roboto font-medium text-white mb-4 text-lg sm:text-base">{{ t('filters.withTags') }}</h3>
        <input
          v-model="tagInput"
          type="text"
          :placeholder="t('filters.tagsPlaceholder')"
          class="w-full px-3.5 py-2.5 bg-white/95 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-orange-primary transition-shadow"
        >
      </div>

      <!-- Les filtres s'appliquent en direct : ce bouton ne sert plus qu'a
           tout remettre a zero, et n'apparait que s'il y a quelque chose a
           remettre a zero. -->
      <BaseButton v-if="hasActiveFilters" variant="ghost" block :shine="false"
        icon="arrow-rotate-left" @click="resetAll">
        {{ t('filters.reset') }}
      </BaseButton>
      <p v-else class="text-white/70 text-xs text-center">
        {{ t('filters.live') }}
      </p>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useReviewStore } from '@/stores/review';
import BaseCheckbox from '@/components/ui/BaseCheckbox.vue';
import BaseRadio from '@/components/ui/BaseRadio.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const { t } = useI18n();
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
