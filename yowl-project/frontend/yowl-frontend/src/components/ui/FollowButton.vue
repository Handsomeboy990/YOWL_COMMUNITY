<template>
  <button type="button"
    class="shrink-0 px-3 py-1.5 rounded-full text-xs font-medium border transition-colors cursor-pointer"
    :class="following
      ? 'border-gray-200 text-gray-500 hover:border-red-200 hover:text-red-600 hover:bg-red-50'
      : 'border-orange-primary text-orange-text hover:bg-orange-50'"
    :aria-pressed="following"
    @click.stop.prevent="toggle">
    <!-- Au survol d'un abonnement actif, le libelle annonce le desabonnement,
         pour qu'on ne clique pas sans savoir ce que ca fait. -->
    <span v-if="following" class="inline-flex items-center gap-1.5">
      <i class="fa-solid fa-check"></i>{{ hovered ? t('review.unfollow') : t('review.following') }}
    </span>
    <span v-else class="inline-flex items-center gap-1.5">
      <i class="fa-solid fa-plus"></i>{{ t('review.follow') }}
    </span>
  </button>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useFollowStore } from '@/stores/follow';

const props = defineProps({
  type: { type: String, default: 'user' },
  id: { type: Number, required: true },
});

const { t } = useI18n();
const followStore = useFollowStore();
const hovered = ref(false);

const following = computed(() =>
  props.type === 'user' ? followStore.isFollowingUser(props.id) : followStore.isFollowingTag(props.id)
);

const toggle = () => followStore.toggle(props.type, props.id);
</script>
