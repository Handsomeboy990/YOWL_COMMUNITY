<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <Header />
  <div class="max-w-4xl mx-auto py-10 px-6 pt-24 min-h-screen">
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
        <BaseButton type="submit" variant="primary" icon="fa-regular fa-paper-plane">
          Envoyer
        </BaseButton>
      </div>
    </form>
  </div>
  <Footer />
</template>

<script setup>
import { ref } from 'vue';
import Footer from '../layouts/Footer.vue';
import Header from '../layouts/Header.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseTextarea from '@/components/ui/BaseTextarea.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import Swal from 'sweetalert2';

const form = ref({
  name: '',
  email: '',
  message: '',
});

const submit = () => {
  if (!form.value.message.trim()) {
    Swal.fire({
      icon: 'error',
      title: 'Suggestion vide',
      text: 'Écris ta suggestion avant de l\'envoyer.',
      confirmButtonColor: '#FF6B35',
    });
    return;
  }

  // Pas encore d'endpoint dédié : la suggestion part par email au support
  const subject = encodeURIComponent('Suggestion YOWL Community');
  const body = encodeURIComponent(
    `Nom : ${form.value.name || 'Anonyme'}\nEmail : ${form.value.email || 'Non renseigné'}\n\n${form.value.message}`
  );
  window.location.href = `mailto:support@yowl.community?subject=${subject}&body=${body}`;

  Swal.fire({
    icon: 'success',
    title: 'Merci !',
    text: 'Ta messagerie va s\'ouvrir pour finaliser l\'envoi de ta suggestion.',
    timer: 3000,
    showConfirmButton: false,
  });
  form.value = { name: '', email: '', message: '' };
};
</script>
