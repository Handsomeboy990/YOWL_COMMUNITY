<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <AppShell>
  <div class="max-w-3xl mx-auto py-10 px-6">
    <h1 class="text-3xl font-bold text-blue-night mb-6">Une suggestion ?</h1>

    <p class="text-gray-700 mb-6">
      YOWL Community évolue en permanence et tes retours sont au cœur de nos progrès.
      Si tu as des idées, des demandes de fonctionnalités ou des pistes d'amélioration, on t'écoute.
    </p>

    <form class="space-y-4" @submit.prevent="submit">
      <BaseInput v-model="form.name" label="Ton nom (optionnel)" placeholder="Jean Dupont" icon="fa-regular fa-user" />
      <BaseInput v-model="form.email" label="Ton email (optionnel)" type="email" placeholder="toi@exemple.com"
        icon="fa-regular fa-envelope" />
      <BaseTextarea v-model="form.message" label="Ta suggestion" :rows="5" :maxlength="1000"
        placeholder="Écris ta suggestion ici..." required />

      <div class="flex justify-end">
        <BaseButton type="submit" variant="primary" icon="fa-regular fa-paper-plane" :disabled="isSending">
          {{ isSending ? 'Envoi en cours...' : 'Envoyer' }}
        </BaseButton>
      </div>
    </form>
  </div>
  </AppShell>
</template>

<script setup>
import AppShell from '@/components/layouts/AppShell.vue';
import { ref } from 'vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseTextarea from '@/components/ui/BaseTextarea.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import Swal from 'sweetalert2';
import api from '@/services/apiService';

const form = ref({
  name: '',
  email: '',
  message: '',
});

const isSending = ref(false);

const submit = async () => {
  if (!form.value.message.trim()) {
    Swal.fire({
      icon: 'error',
      title: 'Suggestion vide',
      text: 'Écris ta suggestion avant de l\'envoyer.',
      confirmButtonColor: '#FF6B35',
    });
    return;
  }

  isSending.value = true;
  try {
    const response = await api.post('/suggestions', {
      name: form.value.name || null,
      email: form.value.email || null,
      message: form.value.message,
    });

    Swal.fire({
      icon: 'success',
      title: 'Merci !',
      text: response.data?.message || 'Ta suggestion a bien été enregistrée.',
      timer: 2500,
      showConfirmButton: false,
    });
    form.value = { name: '', email: '', message: '' };
  } catch (err) {
    const errors = err.response?.data?.errors;
    Swal.fire({
      icon: 'error',
      title: 'Envoi impossible',
      text: errors
        ? Object.values(errors).flat().join(' ')
        : err.response?.data?.message || 'Réessaie dans un instant.',
      confirmButtonColor: '#FF6B35',
    });
  } finally {
    isSending.value = false;
  }
};
</script>
