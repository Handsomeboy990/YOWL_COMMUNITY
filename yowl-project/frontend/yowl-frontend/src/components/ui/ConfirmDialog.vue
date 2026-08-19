<template>
  <BaseModal :is-open="state.open" :title="state.title" size="sm" @close="reject">
    <p v-if="state.message" class="text-gray-600 leading-relaxed">{{ state.message }}</p>

    <template #footer>
      <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
        <BaseButton variant="ghost" @click="reject">{{ state.cancelLabel }}</BaseButton>
        <BaseButton :variant="state.tone === 'danger' ? 'danger' : 'primary'" @click="accept">
          {{ state.confirmLabel }}
        </BaseButton>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import BaseModal from '@/components/ui/BaseModal.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { useConfirmState } from '@/composables/useConfirm';

const { state, accept, reject } = useConfirmState();
</script>
