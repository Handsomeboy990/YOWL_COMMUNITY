<template>
  <BaseModal :is-open="isOpen" title="Signaler ce contenu" size="sm" @close="$emit('close')">
    <p class="text-sm text-gray-500 mb-4">
      Le signalement est transmis à l'équipe de modération. Il reste anonyme pour l'auteur du contenu.
    </p>

    <form id="report-form" class="space-y-4" @submit.prevent="submit">
      <fieldset>
        <legend class="block text-sm font-medium text-blue-night mb-2">Motif</legend>
        <div class="space-y-1.5">
          <label v-for="option in reasons" :key="option.value"
            class="flex items-start gap-2.5 px-3 py-2 rounded-xl border cursor-pointer transition-colors"
            :class="reason === option.value
              ? 'border-orange-primary bg-orange-primary/5'
              : 'border-gray-200 hover:border-gray-300'">
            <input v-model="reason" type="radio" name="report-reason" :value="option.value"
              class="mt-1 accent-orange-primary">
            <span>
              <span class="block text-sm font-medium text-blue-night">{{ option.label }}</span>
              <span class="block text-xs text-gray-400">{{ option.hint }}</span>
            </span>
          </label>
        </div>
      </fieldset>

      <BaseTextarea v-model="details" label="Précisions (optionnel)" :rows="3" :maxlength="1000"
        placeholder="Ajoute un contexte utile à la modération" />
    </form>

    <template #footer>
      <div class="flex justify-end gap-2">
        <BaseButton variant="ghost" :disabled="isSending" @click="$emit('close')">Annuler</BaseButton>
        <BaseButton type="submit" form="report-form" variant="primary" :disabled="isSending || !reason">
          {{ isSending ? 'Envoi...' : 'Signaler' }}
        </BaseButton>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, watch } from 'vue';
import Swal from 'sweetalert2';
import api from '@/services/apiService';
import BaseModal from '@/components/ui/BaseModal.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseTextarea from '@/components/ui/BaseTextarea.vue';

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  // Le type de contenu vise : review ou comment
  type: { type: String, required: true },
  id: { type: [Number, String], required: true },
});

const emit = defineEmits(['close']);

const reasons = [
  { value: 'spam', label: 'Spam ou publicité', hint: 'Contenu répétitif ou promotionnel' },
  { value: 'harassment', label: 'Harcèlement', hint: 'Attaque ou intimidation envers une personne' },
  { value: 'hate', label: 'Haine ou discrimination', hint: 'Propos haineux visant un groupe' },
  { value: 'sexual', label: 'Contenu sexuel', hint: 'Contenu à caractère sexuel non sollicité' },
  { value: 'violence', label: 'Violence', hint: 'Menace ou apologie de la violence' },
  { value: 'misinformation', label: 'Fausse information', hint: 'Information trompeuse ou mensongère' },
  { value: 'other', label: 'Autre motif', hint: 'À préciser ci-dessous' },
];

const reason = ref('');
const details = ref('');
const isSending = ref(false);

// Repartir d'un formulaire vierge a chaque ouverture
watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      reason.value = '';
      details.value = '';
    }
  }
);

async function submit() {
  if (!reason.value || isSending.value) return;

  isSending.value = true;
  try {
    const response = await api.post('/reports', {
      type: props.type,
      id: props.id,
      reason: reason.value,
      details: details.value || null,
    });

    emit('close');
    Swal.fire({
      icon: 'success',
      title: 'Signalement envoyé',
      text: response.data?.message || 'La modération va examiner ce contenu.',
      timer: 2500,
      showConfirmButton: false,
    });
  } catch (err) {
    const errors = err.response?.data?.errors;
    Swal.fire({
      icon: 'error',
      title: 'Signalement impossible',
      text: errors
        ? Object.values(errors).flat().join(' ')
        : err.response?.data?.message || 'Réessaie dans un instant.',
      confirmButtonColor: '#FF6B35',
    });
  } finally {
    isSending.value = false;
  }
}
</script>
