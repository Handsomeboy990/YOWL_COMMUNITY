<template>
  <div class="min-h-screen w-full flex items-center justify-center bg-blue-night relative overflow-hidden px-4">
    <div class="auth-blob auth-blob-1" aria-hidden="true"></div>
    <div class="auth-blob auth-blob-2" aria-hidden="true"></div>

    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl p-8 animate-fade-in-up">
      <router-link to="/" class="inline-flex items-center gap-2.5 mb-6">
        <img src="@/assets/logo.png" alt="Logo YOWL" class="h-10" />
        <span class="font-poppins font-extrabold text-xl text-blue-night">YOWL</span>
      </router-link>

      <template v-if="!done">
        <h1 class="font-poppins font-extrabold text-2xl text-blue-night">Nouveau mot de passe</h1>
        <p class="text-gray-500 mt-2 mb-6 text-sm leading-relaxed">
          Choisis un nouveau mot de passe pour
          <span class="font-semibold text-orange-text break-all">{{ email }}</span>.
        </p>

        <form class="space-y-5" @submit.prevent="submit">
          <Transition name="shake">
            <div v-if="errorMessage"
              class="flex items-start gap-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-600"
              role="alert">
              <Icon name="circle-exclamation" class="mt-0.5" aria-hidden="true" />
              <span>{{ errorMessage }}</span>
            </div>
          </Transition>

          <BaseInput
            v-model="password"
            label="Nouveau mot de passe"
            type="password"
            placeholder="8 caractères minimum"
            icon="lock"
            autocomplete="new-password"
            required
          />

          <BaseInput
            v-model="passwordConfirmation"
            label="Confirmation"
            type="password"
            placeholder="Confirme ton mot de passe"
            icon="lock"
            autocomplete="new-password"
            required
            @enter="submit"
          />

          <BaseButton type="submit" variant="primary" size="lg" block :loading="loading">
            {{ t('auth.resetTitle') }}
          </BaseButton>
        </form>
      </template>

      <template v-else>
        <div class="text-center py-4">
          <div class="mx-auto w-16 h-16 rounded-2xl bg-emerald-500/10 grid place-items-center text-emerald-500 text-2xl mb-5">
            <Icon name="circle-check" />
          </div>
          <h1 class="font-poppins font-extrabold text-2xl text-blue-night">{{ t('auth.resetDone') }}</h1>
          <p class="text-gray-500 mt-3 text-sm">
            Tu peux maintenant te connecter avec ton nouveau mot de passe.
          </p>
          <BaseButton class="mt-6" :tag="'router-link'" :to="'/login'" variant="primary" block>
            Me connecter
          </BaseButton>
        </div>
      </template>

      <p v-if="!done" class="mt-6 text-center text-sm text-gray-500">
        <router-link to="/login" class="text-orange-text font-semibold hover:underline">
          <Icon name="arrow-left" :size="14" class="text-xs mr-1" />
          {{ t('auth.backToLogin') }}
        </router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/services/apiService';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

import Icon from '@/components/ui/Icon.vue';
const { t } = useI18n();

const route = useRoute();

const token = computed(() => route.params.token || '');
const email = computed(() => route.query.email || '');

const password = ref('');
const passwordConfirmation = ref('');
const loading = ref(false);
const done = ref(false);
const errorMessage = ref('');

const submit = async () => {
  if (!password.value || password.value.length < 8) {
    errorMessage.value = 'Le mot de passe doit contenir au moins 8 caractères.';
    return;
  }
  if (password.value !== passwordConfirmation.value) {
    errorMessage.value = 'Les mots de passe ne correspondent pas.';
    return;
  }

  loading.value = true;
  errorMessage.value = '';
  try {
    await api.post('/reset-password', {
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });
    done.value = true;
  } catch (err) {
    errorMessage.value =
      err.response?.data?.message ||
      'Le lien de réinitialisation est invalide ou a expiré. Refais une demande.';
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
