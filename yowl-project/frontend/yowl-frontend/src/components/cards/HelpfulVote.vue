<template>
  <div v-if="visible" class="flex items-center gap-2 text-sm">
    <span class="text-gray-500">Cet avis t'a aidé ?</span>
    <button type="button"
      class="px-2.5 py-1 rounded-full border text-xs transition-colors cursor-pointer"
      :class="vote === true
        ? 'border-emerald-400 bg-emerald-50 text-emerald-700'
        : 'border-gray-200 text-gray-500 hover:border-emerald-300'"
      :aria-pressed="vote === true" @click="send(true)">
      <i class="fa-regular fa-thumbs-up mr-1"></i>Oui<span v-if="counts.helpful"> {{ counts.helpful }}</span>
    </button>
    <button type="button"
      class="px-2.5 py-1 rounded-full border text-xs transition-colors cursor-pointer"
      :class="vote === false
        ? 'border-gray-400 bg-gray-100 text-gray-700'
        : 'border-gray-200 text-gray-500 hover:border-gray-300'"
      :aria-pressed="vote === false" @click="send(false)">
      Pas vraiment
    </button>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import api from '@/services/apiService';
import { useUserStore } from '@/stores/user';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';

const props = defineProps({
  review: { type: Object, required: true },
});

const notify = useNotify();
const userStore = useUserStore();

const vote = ref(props.review.user_helpful ?? null);
const counts = ref({
  helpful: props.review.nb_helpful ?? 0,
  unhelpful: props.review.nb_unhelpful ?? 0,
});

// On ne juge pas l'utilité de son propre avis.
const visible = computed(
  () => userStore.isAuthenticated && userStore.user?.id !== props.review.user_id
);

async function send(helpful) {
  try {
    const response = await api.post(`/reviews/${props.review.id}/helpful`, { helpful });
    vote.value = response.data.data.user_helpful;
    counts.value = {
      helpful: response.data.data.nb_helpful,
      unhelpful: response.data.data.nb_unhelpful,
    };
  } catch (err) {
    notify.error(apiErrorMessage(err, 'Ton retour n\'a pas pu être enregistré.'));
  }
}
</script>
