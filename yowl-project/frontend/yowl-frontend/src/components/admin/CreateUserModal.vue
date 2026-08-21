<template>
  <BaseModal :is-open="isOpen" title="Créer un membre" size="md" @close="close">
    <form id="create-user-form" class="space-y-4" @submit.prevent="submit">
      <BaseInput v-model="form.fullname" label="Nom complet" placeholder="Jean Dupont" required />
      <BaseInput v-model="form.username" label="Pseudo" placeholder="jean_d" required />
      <BaseInput v-model="form.email" label="Adresse email" type="email" placeholder="jean@exemple.com" required />
      <BaseInput v-model="form.password" label="Mot de passe" type="password" placeholder="8 caractères min." required />
      <BaseInput v-model="form.password_confirmation" label="Confirmation" type="password" required />

      <div>
        <p class="text-sm font-medium text-blue-night mb-2">Rôles</p>
        <div class="flex flex-wrap gap-2">
          <label v-for="role in roles" :key="role"
            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border text-sm cursor-pointer transition-colors"
            :class="form.roles.includes(role)
              ? 'border-orange-primary bg-orange-50 text-orange-text'
              : 'border-gray-200 text-gray-500 hover:border-gray-300'">
            <input v-model="form.roles" type="checkbox" :value="role" class="sr-only" />
            <Icon name="check" :filled="form.roles.includes(role)" />
            {{ role }}
          </label>
        </div>
        <p class="mt-2 text-xs text-gray-500">
          Le compte est créé déjà vérifié. Transmets le mot de passe autrement que par cette page.
        </p>
      </div>
    </form>

    <template #footer>
      <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
        <BaseButton variant="ghost" @click="close">Annuler</BaseButton>
        <BaseButton variant="primary" type="submit" form="create-user-form" :loading="saving"
          :disabled="!form.roles.length">
          Créer le membre
        </BaseButton>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, watch } from 'vue';
import api from '@/services/apiService';
import BaseModal from '@/components/ui/BaseModal.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';

import Icon from '@/components/ui/Icon.vue';
const props = defineProps({
  isOpen: { type: Boolean, default: false },
});
const emit = defineEmits(['close', 'created']);

const notify = useNotify();
const saving = ref(false);
const roles = ref(['client', 'admin']);

const blank = () => ({
  fullname: '',
  username: '',
  email: '',
  password: '',
  password_confirmation: '',
  roles: ['client'],
});
const form = ref(blank());

watch(
  () => props.isOpen,
  async (open) => {
    if (!open) return;
    form.value = blank();
    try {
      const response = await api.get('/admin/roles');
      roles.value = response.data.data.roles.map((role) => role.name);
    } catch {
      // La liste par defaut suffit pour creer un membre.
    }
  }
);

const close = () => emit('close');

async function submit() {
  saving.value = true;
  try {
    await api.post('/admin/users', form.value);
    notify.success('Membre créé', `${form.value.username} peut se connecter.`);
    emit('created');
    close();
  } catch (err) {
    notify.error('Création impossible', apiErrorMessage(err, 'Vérifie les champs saisis.'));
  } finally {
    saving.value = false;
  }
}
</script>
