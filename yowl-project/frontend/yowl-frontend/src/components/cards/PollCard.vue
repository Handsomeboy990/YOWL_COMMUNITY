<template>
  <section v-if="poll" class="mt-4 rounded-xl border border-gray-200 p-4"
    :aria-labelledby="'poll-' + poll.id">
    <h3 :id="'poll-' + poll.id" class="font-medium text-blue-night">{{ poll.question }}</h3>

    <ul class="mt-3 space-y-2">
      <li v-for="option in poll.options" :key="option.id">
        <!-- Avant le vote : des choix. Apres : des resultats. Le meme
             element change de nature, pas de place. -->
        <button v-if="!state.revealed && !state.closed" type="button"
          class="w-full text-left px-4 py-2.5 rounded-lg border border-gray-200 text-sm transition-colors cursor-pointer hover:border-orange-primary hover:bg-orange-50 disabled:opacity-60 disabled:cursor-not-allowed"
          :disabled="voting" @click="vote(option.id)">
          {{ option.label }}
        </button>

        <div v-else class="relative rounded-lg border overflow-hidden"
          :class="option.id === state.my_option_id ? 'border-orange-primary' : 'border-gray-200'">
          <div class="absolute inset-y-0 left-0 transition-all duration-500"
            :class="option.id === state.my_option_id ? 'bg-orange-primary/15' : 'bg-gray-100'"
            :style="{ width: (option.share ?? 0) + '%' }" aria-hidden="true"></div>
          <div class="relative flex items-center justify-between gap-3 px-4 py-2.5 text-sm">
            <span class="min-w-0 truncate" :class="option.id === state.my_option_id ? 'font-medium text-blue-night' : 'text-gray-700'">
              <Icon name="check" class="text-orange-text mr-1.5" v-if="option.id === state.my_option_id" />
              {{ option.label }}
            </span>
            <span class="shrink-0 tabular-nums text-gray-500">{{ option.share ?? 0 }}%</span>
          </div>
        </div>
      </li>
    </ul>

    <p class="mt-3 text-xs text-gray-500">
      {{ state.total_votes }} vote<span v-if="state.total_votes > 1">s</span>
      <template v-if="state.closed"> &middot; sondage terminé</template>
      <template v-else-if="!state.revealed"> &middot; vote pour voir les résultats</template>
    </p>
  </section>
</template>

<script setup>
import { ref, watch } from 'vue';
import api from '@/services/apiService';
import { useUserStore } from '@/stores/user';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';

import Icon from '@/components/ui/Icon.vue';
const props = defineProps({
  poll: { type: Object, default: null },
});

const notify = useNotify();
const userStore = useUserStore();
const voting = ref(false);
const state = ref({ ...(props.poll ?? {}) });

watch(() => props.poll, (value) => { state.value = { ...(value ?? {}) }; });

async function vote(optionId) {
  if (!userStore.isAuthenticated) {
    notify.info('Connexion requise', 'Tu dois être connecté pour voter.');
    return;
  }

  voting.value = true;
  try {
    const response = await api.post(`/polls/${props.poll.id}/vote`, { option_id: optionId });
    state.value = response.data.data;
  } catch (err) {
    notify.error(apiErrorMessage(err, 'Le vote a échoué.'));
  } finally {
    voting.value = false;
  }
}
</script>
