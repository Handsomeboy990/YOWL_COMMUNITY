<template>
  <div v-if="aVerifier">
    <!-- Rappel permanent, sous la barre du haut. Il ne se ferme pas : le
         compte a un nombre de connexions compté avant que la vérification
         devienne obligatoire, et une personne qui masque le rappel se
         retrouverait dehors sans avoir été prévenue. -->
    <div class="w-full bg-amber-50 border-b border-amber-200">
      <div class="w-full px-3 md:px-6 py-2.5 flex flex-wrap items-center justify-between gap-2.5">
        <p class="flex items-start gap-2.5 text-sm text-amber-900">
          <Icon name="circle-exclamation" :size="16" class="mt-0.5 shrink-0" aria-hidden="true" />
          <span>
            Votre adresse email n'est pas encore vérifiée.
            <template v-if="restant > 0">
              Encore <strong>{{ restant }}</strong> connexion<template v-if="restant > 1">s</template>
              avant que ce soit demandé.
            </template>
            <template v-else>
              La vérification sera demandée à la prochaine connexion.
            </template>
          </span>
        </p>

        <BaseButton variant="night" size="sm" :shine="false" @click="ouvrir">
          Vérifier maintenant
        </BaseButton>
      </div>
    </div>

    <MailVerificationModal
      :isOpen="ouverte"
      :email="userStore.user?.email"
      :error="erreur"
      :loading="envoi"
      :reportable="true"
      :restant="restant"
      @close="ouverte = false"
      @later="ouverte = false"
      @resend="renvoyer"
      @verify="verifier"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

import BaseButton from '@/components/ui/BaseButton.vue';
import Icon from '@/components/ui/Icon.vue';
import MailVerificationModal from '@/components/layouts/MailVerificationModal.vue';
import { apiErrorMessage, useNotify } from '@/composables/useNotify';
import { useUserStore } from '@/stores/user';

const userStore = useUserStore();
const notify = useNotify();

const ouverte = ref(false);
const erreur = ref('');
const envoi = ref(false);

const aVerifier = computed(
  () => userStore.isAuthenticated && !userStore.user?.email_verified_at
);

/**
 * Connexions restantes avant que la vérification devienne obligatoire.
 *
 * Le compte le porte, calculé par le serveur : les réglages d'inscription
 * restent privés, et le décompte suit ce que l'administration a réglé sans
 * qu'aucune constante ne soit figée ici.
 */
const restant = computed(() => Number(userStore.user?.verification?.restant ?? 0));

function ouvrir() {
  erreur.value = '';
  ouverte.value = true;
}

async function renvoyer() {
  erreur.value = '';
  try {
    await userStore.resendVerificationCode(userStore.user.email);
    notify.success('Un nouveau code vient de partir.');
  } catch (err) {
    erreur.value = apiErrorMessage(err, "Le code n'a pas pu être renvoyé.");
  }
}

async function verifier(code) {
  erreur.value = '';
  envoi.value = true;
  try {
    await userStore.verifyEmailCode({ email: userStore.user.email, code });
    ouverte.value = false;
    notify.success('Votre adresse est vérifiée. Merci.');
  } catch (err) {
    erreur.value = apiErrorMessage(err, 'Ce code ne correspond pas.');
  } finally {
    envoi.value = false;
  }
}
</script>
