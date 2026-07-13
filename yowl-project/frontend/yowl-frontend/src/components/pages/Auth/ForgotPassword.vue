<template>
  <div class="min-h-screen w-full flex items-center justify-center bg-blue-night relative overflow-hidden px-4">
    <div class="auth-blob auth-blob-1" aria-hidden="true"></div>
    <div class="auth-blob auth-blob-2" aria-hidden="true"></div>

    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl p-8 animate-fade-in-up">
      <router-link to="/" class="inline-flex items-center gap-2.5 mb-6">
        <img src="@/assets/logo.png" alt="Logo YOWL" class="h-10" />
        <span class="font-poppins font-extrabold text-xl text-blue-night">YOWL</span>
      </router-link>

      <template v-if="!sent">
        <h1 class="font-poppins font-extrabold text-2xl text-blue-night">Mot de passe oublié ?</h1>
        <p class="text-gray-500 mt-2 mb-6 text-sm leading-relaxed">
          Pas de panique. Donne-nous ton adresse email et nous t'enverrons un lien pour
          réinitialiser ton mot de passe.
        </p>

        <form class="space-y-5" @submit.prevent="submit">
          <Transition name="shake">
            <div v-if="errorMessage"
              class="flex items-start gap-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-600"
              role="alert">
              <i class="fa-solid fa-circle-exclamation mt-0.5" aria-hidden="true"></i>
              <span>{{ errorMessage }}</span>
            </div>
          </Transition>

          <BaseInput
            v-model="email"
            label="Adresse email"
            type="email"
            placeholder="toi@exemple.com"
            icon="fa-regular fa-envelope"
            autocomplete="email"
            required
            @enter="submit"
          />

          <BaseButton type="submit" variant="primary" size="lg" block :loading="loading">
            Envoyer le lien de réinitialisation
          </BaseButton>
        </form>
      </template>

      <template v-else>
        <div class="text-center py-4">
          <div class="mx-auto w-16 h-16 rounded-2xl bg-emerald-500/10 grid place-items-center text-emerald-500 text-2xl mb-5">
            <i class="fa-regular fa-paper-plane"></i>
          </div>
          <h1 class="font-poppins font-extrabold text-2xl text-blue-night">Email envoyé !</h1>
          <p class="text-gray-500 mt-3 text-sm leading-relaxed">
            Si un compte existe pour <span class="font-semibold text-orange-primary">{{ email }}</span>,
            un lien de réinitialisation vient de lui être envoyé. Pense à vérifier tes spams.
          </p>
        </div>
      </template>

      <p class="mt-6 text-center text-sm text-gray-500">
        <router-link to="/login" class="text-orange-primary font-semibold hover:underline">
          <i class="fa-solid fa-arrow-left text-xs mr-1"></i>
          Retour à la connexion
        </router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import api from '@/services/apiService';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const email = ref('');
const loading = ref(false);
const sent = ref(false);
const errorMessage = ref('');

const submit = async () => {
  if (!email.value.trim()) {
    errorMessage.value = 'Renseigne ton adresse email.';
    return;
  }
  loading.value = true;
  errorMessage.value = '';
  try {
    await api.post('/forgot-password', { email: email.value.trim() });
    sent.value = true;
  } catch (err) {
    // Ne pas révéler si l'email existe : on affiche le même écran de succès
    if (err.response?.status === 422 && err.response.data?.errors?.email) {
      sent.value = true;
    } else {
      errorMessage.value = "L'envoi a échoué. Réessaie dans un instant.";
    }
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.auth-blob {
  position: absolute;
  border-radius: 9999px;
  filter: blur(90px);
  animation: authBlobFloat 12s ease-in-out infinite alternate;
}
.auth-blob-1 {
  width: 420px;
  height: 420px;
  top: -120px;
  right: -100px;
  background: rgba(255, 107, 53, 0.3);
}
.auth-blob-2 {
  width: 360px;
  height: 360px;
  bottom: -100px;
  left: -110px;
  background: rgba(124, 92, 252, 0.28);
  animation-delay: 4s;
}

@keyframes authBlobFloat {
  from {
    transform: translate(0, 0) scale(1);
  }
  to {
    transform: translate(30px, -25px) scale(1.1);
  }
}

.shake-enter-active {
  animation: shakeIn 0.4s ease;
}
@keyframes shakeIn {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-6px); }
  50% { transform: translateX(5px); }
  75% { transform: translateX(-3px); }
}
</style>
