<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <AppShell>
    <div class="w-full px-4 xl:px-6 py-8 pb-24">
      <div class="max-w-2xl">
        <!-- Etat de confirmation : la page dit ce qui vient de se passer,
             au lieu de le confier a un toast qui disparait. -->
        <div v-if="sent" class="bg-white border border-gray-200 rounded-2xl p-8 text-center animate-fade-in-up">
          <span class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 grid place-items-center mx-auto">
            <i class="fa-solid fa-check text-2xl"></i>
          </span>
          <h1 class="mt-5 font-poppins font-bold text-2xl text-blue-night">Message reçu</h1>
          <p class="mt-3 text-gray-600 leading-relaxed">
            Ta suggestion est arrivée dans la boîte de l'équipe. Elle sera lue, et si elle est retenue
            tu la verras apparaître dans une prochaine version.
          </p>
          <div class="mt-6 flex flex-col sm:flex-row justify-center gap-3">
            <BaseButton variant="primary" @click="reset">Envoyer une autre suggestion</BaseButton>
            <BaseButton :tag="'router-link'" :to="'/feed'" variant="ghost">Retour au fil</BaseButton>
          </div>
        </div>

        <template v-else>
          <header>
            <h1 class="font-poppins font-extrabold text-3xl text-blue-night">Proposer une idée</h1>
            <p class="mt-3 text-gray-600 leading-relaxed">
              Une fonctionnalité qui manque, quelque chose qui ne marche pas, un contenu à corriger :
              écris-le ici. C'est l'équipe qui gère YOWL qui te lit, pas la communauté.
            </p>
          </header>

          <!-- Ce que ce formulaire n'est pas : la confusion la plus frequente -->
          <aside class="mt-5 flex gap-3 p-4 rounded-2xl bg-orange-50 border border-orange-200">
            <i class="fa-solid fa-circle-info text-orange-primary mt-0.5"></i>
            <p class="text-sm text-gray-700 leading-relaxed">
              Pour signaler le message d'un membre, n'utilise pas ce formulaire :
              passe par le bouton <span class="font-medium">Signaler</span> sur l'avis ou le commentaire concerné,
              qui prévient directement la modération.
            </p>
          </aside>

          <form class="mt-7 space-y-6" @submit.prevent="submit">
            <!-- Sujet -->
            <fieldset>
              <legend class="text-sm font-medium text-blue-night mb-2">De quoi s'agit-il ?</legend>
              <div class="flex flex-wrap gap-2">
                <label v-for="option in subjects" :key="option.value"
                  class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border text-sm cursor-pointer transition-colors"
                  :class="form.subject === option.value
                    ? 'border-orange-primary bg-orange-50 text-orange-primary'
                    : 'border-gray-200 text-gray-600 hover:border-gray-300'">
                  <input v-model="form.subject" type="radio" :value="option.value" class="sr-only" />
                  <i :class="option.icon"></i>
                  {{ option.label }}
                </label>
              </div>
            </fieldset>

            <!-- Message -->
            <div>
              <BaseTextarea v-model="form.message" label="Ta suggestion" :rows="6" :maxlength="2000"
                placeholder="Décris ton idée. Un exemple concret aide beaucoup." required />
              <div class="mt-1.5 flex items-center justify-between text-xs">
                <span :class="tooShort ? 'text-red-500' : 'text-gray-400'">
                  {{ tooShort ? 'Au moins 5 caractères' : 'Sois aussi précis que possible' }}
                </span>
                <span class="text-gray-400">{{ form.message.length }} / 2000</span>
              </div>
            </div>

            <!-- Identite -->
            <div>
              <p class="text-sm font-medium text-blue-night">Pour te répondre, si besoin</p>
              <p class="text-xs text-gray-500 mt-0.5 mb-3">
                <template v-if="userStore.isAuthenticated">
                  Tu es connecté : ton compte est déjà rattaché à ce message, tu n'as rien à remplir.
                </template>
                <template v-else>
                  Facultatif. Sans adresse, ta suggestion sera lue mais restera sans réponse.
                </template>
              </p>
              <div v-if="!userStore.isAuthenticated" class="grid sm:grid-cols-2 gap-3">
                <BaseInput v-model="form.name" label="Ton nom" placeholder="Jean Dupont"
                  icon="fa-regular fa-user" />
                <BaseInput v-model="form.email" label="Ton email" type="email" placeholder="toi@exemple.com"
                  icon="fa-regular fa-envelope" />
              </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-2">
              <BaseButton type="submit" variant="primary" icon="fa-regular fa-paper-plane"
                :loading="isSending" :disabled="tooShort">
                Envoyer ma suggestion
              </BaseButton>
            </div>
          </form>
        </template>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { computed, ref } from 'vue';
import AppShell from '@/components/layouts/AppShell.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseTextarea from '@/components/ui/BaseTextarea.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';
import { useUserStore } from '@/stores/user';
import api from '@/services/apiService';

const notify = useNotify();
const userStore = useUserStore();

const subjects = [
  { value: 'feature', label: 'Une fonctionnalité', icon: 'fa-solid fa-wand-magic-sparkles' },
  { value: 'improvement', label: 'Une amélioration', icon: 'fa-solid fa-arrow-trend-up' },
  { value: 'bug', label: 'Quelque chose ne marche pas', icon: 'fa-solid fa-bug' },
  { value: 'content', label: 'Un contenu à corriger', icon: 'fa-regular fa-pen-to-square' },
  { value: 'other', label: 'Autre chose', icon: 'fa-regular fa-comment-dots' },
];

const blank = () => ({ name: '', email: '', subject: 'feature', message: '' });
const form = ref(blank());
const isSending = ref(false);
const sent = ref(false);

// L'API exige cinq caracteres : autant le dire avant l'envoi plutot que de
// renvoyer une erreur du serveur.
const tooShort = computed(() => form.value.message.trim().length > 0 && form.value.message.trim().length < 5);

const reset = () => {
  form.value = blank();
  sent.value = false;
};

const submit = async () => {
  if (form.value.message.trim().length < 5) {
    notify.warning('Suggestion trop courte', 'Écris au moins 5 caractères.');
    return;
  }

  isSending.value = true;
  try {
    await api.post('/suggestions', {
      name: form.value.name || null,
      email: form.value.email || null,
      subject: form.value.subject,
      message: form.value.message,
    });
    sent.value = true;
  } catch (err) {
    notify.error('Envoi impossible', apiErrorMessage(err, 'Réessaie dans un instant.'));
  } finally {
    isSending.value = false;
  }
};
</script>
