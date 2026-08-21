<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <div class="min-h-screen grid place-items-center bg-gray-50 px-4">
    <div class="w-full max-w-md bg-white border border-gray-200 rounded-2xl p-8 text-center">
      <span class="mx-auto w-14 h-14 rounded-2xl grid place-items-center text-2xl"
        :class="etat === 'fait' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500'">
        <Icon name="check" />
      </span>

      <h1 class="mt-5 font-poppins font-bold text-xl text-blue-night">
        {{ etat === 'fait' ? 'Désinscription enregistrée' : 'Désinscription' }}
      </h1>

      <p class="mt-2.5 text-sm text-gray-600">{{ message }}</p>

      <BaseButton v-if="etat === 'attente'" class="mt-6" variant="primary" :loading="envoi"
        block @click="confirmer">
        Confirmer ma désinscription
      </BaseButton>

      <BaseButton class="mt-3" :tag="'router-link'" :to="'/feed'" variant="ghost" block>
        Retour au fil
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRoute } from 'vue-router';
import BaseButton from '@/components/ui/BaseButton.vue';
import api from '@/services/apiService';
import { apiErrorMessage } from '@/composables/useNotify';

import Icon from '@/components/ui/Icon.vue';
const route = useRoute();
const etat = ref('attente');
const envoi = ref(false);
const message = ref(
  "Tu ne recevras plus les emails de la communauté. Les messages liés à ton compte, comme une réinitialisation de mot de passe, continueront d'arriver."
);

/**
 * L'action demande une confirmation plutôt que de partir au chargement.
 *
 * Certains clients mail visitent les liens d'un message pour les analyser :
 * désinscrire sur un simple GET reviendrait à obéir à un robot.
 */
async function confirmer() {
  envoi.value = true;
  try {
    const { data } = await api.post(`/campagnes/desinscription/${route.params.token}`);
    etat.value = 'fait';
    message.value = data.message;
  } catch (err) {
    message.value = apiErrorMessage(err, "Ce lien n'est plus valable.");
  } finally {
    envoi.value = false;
  }
}
</script>
