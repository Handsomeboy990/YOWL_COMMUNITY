<template>
  <Teleport to="body">
    <Transition name="dialog">
      <div v-if="open" class="fixed inset-0 z-[70] flex items-center justify-center p-4"
        role="dialog" aria-modal="true" :aria-labelledby="titleId">
        <div class="absolute inset-0 bg-blue-night/50 backdrop-blur-sm" @click="close"></div>

        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden">
          <div class="px-6 pt-6">
            <h2 :id="titleId" class="text-lg font-semibold text-blue-night">
              Contester cette décision
            </h2>
            <p class="mt-1.5 text-sm text-gray-600">
              Explique pourquoi tu penses que ce texte devait rester en ligne. Un
              modérateur le relit et te répond, quelle que soit sa conclusion.
            </p>
          </div>

          <div class="px-6 py-5">
            <BaseTextarea v-model="message" label="Ton explication" :rows="6" :maxlength="2000"
              :error="error" required
              placeholder="Par exemple : le passage signalé cite un article, il ne reprend pas le propos à mon compte." />
            <p class="mt-2 text-xs text-gray-500">
              Vingt caractères minimum. Une contestation par contenu.
            </p>
          </div>

          <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <BaseButton variant="ghost" size="sm" @click="close">Annuler</BaseButton>
            <BaseButton variant="primary" size="sm" :loading="store.submitting" @click="send">
              Envoyer la contestation
            </BaseButton>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, useId, watch } from 'vue';
import BaseTextarea from '@/components/ui/BaseTextarea.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { useAppealStore } from '@/stores/appeal';

const props = defineProps({
  open: { type: Boolean, default: false },
  type: { type: String, default: 'review' },
  id: { type: Number, required: true },
});

const emit = defineEmits(['update:open', 'sent']);

const store = useAppealStore();
const message = ref('');
const error = ref('');
const titleId = useId();

// La longueur minimale est verifiee ici aussi : renvoyer une erreur 422 pour
// un texte trop court fait perdre la saisie a qui ecrit vite.
async function send() {
  if (message.value.trim().length < 20) {
    error.value = 'Vingt caractères au minimum, pour que la relecture ait de quoi se faire une idée.';
    return;
  }

  error.value = '';
  const sent = await store.submit({ type: props.type, id: props.id, message: message.value.trim() });
  if (sent) {
    emit('sent');
    close();
  }
}

function close() {
  emit('update:open', false);
}

watch(() => props.open, (ouvert) => {
  if (ouvert) {
    message.value = '';
    error.value = '';
  }
});
</script>

<style scoped>
.dialog-enter-active,
.dialog-leave-active {
  transition: opacity 0.2s ease;
}

.dialog-enter-from,
.dialog-leave-to {
  opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
  .dialog-enter-active,
  .dialog-leave-active {
    transition: none;
  }
}
</style>
