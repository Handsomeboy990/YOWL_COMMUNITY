<template>
  <div class="mt-10 text-right pb-10">
    <BaseButton variant="danger" icon="door-open" :shine="false" @click="ouvrir">
      {{ t('profile.leave') }}
    </BaseButton>

    <BaseModal :isOpen="ouverte" size="md" @close="fermer">
      <div class="text-left">
        <div class="flex items-start gap-3">
          <span class="w-11 h-11 shrink-0 rounded-xl bg-red-50 text-red-600 grid place-items-center">
            <Icon name="triangle-exclamation" :size="20" aria-hidden="true" />
          </span>
          <div>
            <h2 class="font-poppins font-bold text-xl text-blue-night">Supprimer votre compte</h2>
            <p class="mt-1 text-sm text-gray-600">Cette action ne se défait pas.</p>
          </div>
        </div>

        <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50/70 p-4 text-sm text-gray-700 space-y-1.5">
          <p class="font-medium text-blue-night">Ce qui va se passer</p>
          <p>Votre adresse email, votre pseudo, votre nom, votre photo et votre date de naissance sont effacés.</p>
          <p>
            Vos avis et commentaires restent en ligne, rattachés à un compte anonyme : les retirer trouerait
            les discussions des autres membres.
          </p>
          <p>Vous ne pourrez pas récupérer ce compte, ni le pseudo qui lui était associé.</p>
        </div>

        <form class="mt-5 space-y-4" @submit.prevent="confirmer">
          <BaseInput v-model="motDePasse" type="password" label="Votre mot de passe actuel"
            placeholder="Pour prouver que c'est bien vous" autocomplete="current-password" />

          <div>
            <BaseInput v-model="phrase" label="Recopiez cette phrase pour confirmer"
              :placeholder="phraseAttendue" autocomplete="off" />
            <!-- La phrase est affichee en toutes lettres a cote du champ :
                 la recopier oblige a la lire, ce qu'un bouton ne fait pas. -->
            <p class="mt-1.5 text-sm text-gray-600">
              Saisissez exactement : <strong class="text-blue-night">{{ phraseAttendue }}</strong>
            </p>
          </div>

          <p v-if="erreur" class="flex items-start gap-2 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <Icon name="circle-exclamation" :size="16" class="mt-0.5 shrink-0" aria-hidden="true" />
            <span>{{ erreur }}</span>
          </p>

          <div class="flex flex-col sm:flex-row gap-2.5 pt-1">
            <BaseButton type="submit" variant="danger" :loading="envoi" :disabled="!pretASupprimer" class="sm:flex-1">
              Supprimer définitivement
            </BaseButton>
            <BaseButton type="button" variant="ghost" :shine="false" class="sm:flex-1" @click="fermer">
              Rester dans la communauté
            </BaseButton>
          </div>
        </form>
      </div>
    </BaseModal>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

import router from '@/router';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import Icon from '@/components/ui/Icon.vue';
import { useNotify } from '@/composables/useNotify';
import { useSiteStore } from '@/stores/site';
import { useUserStore } from '@/stores/user';

const { t } = useI18n();
const userStore = useUserStore();
const siteStore = useSiteStore();
const notify = useNotify();

const ouverte = ref(false);
const motDePasse = ref('');
const phrase = ref('');
const erreur = ref('');
const envoi = ref(false);

// Construite a partir du nom regle dans l'administration, comme cote serveur :
// les deux doivent attendre exactement la meme phrase.
const phraseAttendue = computed(() => `Oui, je veux quitter la communauté ${siteStore.name}`);

const pretASupprimer = computed(
  () =>
    motDePasse.value.length > 0 &&
    phrase.value.trim().toLowerCase() === phraseAttendue.value.toLowerCase()
);

function ouvrir() {
  motDePasse.value = '';
  phrase.value = '';
  erreur.value = '';
  ouverte.value = true;
}

function fermer() {
  ouverte.value = false;
}

async function confirmer() {
  if (!pretASupprimer.value) return;

  erreur.value = '';
  envoi.value = true;

  try {
    await userStore.leaveCommunity({
      password: motDePasse.value,
      confirmation: phrase.value.trim(),
    });
    ouverte.value = false;
    router.push('/');
    notify.success('Compte supprimé', 'Vos données personnelles ont été effacées.');
  } catch (err) {
    erreur.value = err.message;
  } finally {
    envoi.value = false;
  }
}
</script>
