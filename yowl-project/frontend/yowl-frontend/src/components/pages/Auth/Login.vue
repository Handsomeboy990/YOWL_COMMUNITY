<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <div>
    <div class="min-h-screen w-full flex bg-white">
      <!-- Panneau formulaire -->
      <div class="w-full lg:w-1/2 flex flex-col justify-center px-6 sm:px-12 xl:px-24 py-10">
        <router-link to="/" class="inline-flex items-center gap-2.5 mb-10 w-max">
          <img src="@/assets/logo.png" alt="Logo YOWL" class="h-12" />
          <span class="font-poppins font-extrabold text-2xl text-blue-night">YOWL</span>
        </router-link>

        <div class="max-w-md w-full mx-auto lg:mx-0 animate-fade-in-up">
          <h1 class="font-poppins font-extrabold text-3xl md:text-4xl text-blue-night">
            Bon retour parmi nous
          </h1>
          <p class="text-gray-500 mt-3 mb-8">
            Pas encore de compte ?
            <router-link to="/signup" class="text-orange-text font-semibold hover:underline">
              Inscris-toi maintenant
            </router-link>
          </p>

          <form class="space-y-5" @submit.prevent="submitForm">
            <Transition name="shake">
              <div
                v-if="errorMessage"
                class="flex items-start gap-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-600"
                role="alert"
              >
                <i class="fa-solid fa-circle-exclamation mt-0.5" aria-hidden="true"></i>
                <span>{{ errorMessage }}</span>
              </div>
            </Transition>

            <BaseInput
              v-model="identifier"
              label="Adresse email"
              type="email"
              placeholder="toi@exemple.com"
              icon="fa-regular fa-envelope"
              autocomplete="email"
              required
              @enter="submitForm"
            />

            <BaseInput
              v-model="password"
              label="Mot de passe"
              type="password"
              placeholder="Ton mot de passe"
              icon="fa-solid fa-lock"
              autocomplete="current-password"
              required
              @enter="submitForm"
            />

            <div class="flex items-center justify-between">
              <BaseCheckbox v-model="rememberMe" :label="t('auth.remember')" />
              <router-link to="/forgot-password" class="text-sm text-orange-text font-medium hover:underline">
                {{ t('auth.forgotTitle') }}
              </router-link>
            </div>

            <BaseButton type="submit" variant="primary" size="lg" block :loading="loading">
              Se connecter
            </BaseButton>
          </form>
        </div>
      </div>

      <!-- Panneau marque -->
      <div class="hidden lg:flex w-1/2 relative bg-blue-night items-center justify-center overflow-hidden">
        <div class="auth-blob auth-blob-1" aria-hidden="true"></div>
        <div class="auth-blob auth-blob-2" aria-hidden="true"></div>

        <div class="relative z-10 max-w-md px-12 text-center">
          <div
            class="mx-auto w-20 h-20 rounded-3xl bg-gradient-to-br from-orange-primary to-orange-primary-dark grid place-items-center text-white text-3xl shadow-2xl shadow-orange-primary/40 rotate-3 animate-fade-in-up"
          >
            <i class="fa-solid fa-comments"></i>
          </div>
          <h2 class="mt-8 font-poppins font-extrabold text-3xl text-white leading-snug animate-fade-in-up animation-delay-200">
            {{ t('auth.loginTitle') }}
          </h2>
          <p class="mt-4 text-white/70 leading-relaxed animate-fade-in-up animation-delay-400">
            {{ t('auth.loginPitch') }}
          </p>

          <blockquote class="mt-10 bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl p-6 text-left animate-fade-in-up animation-delay-400">
            <div class="flex gap-1 text-orange-text mb-3 text-xs" aria-hidden="true">
              <i v-for="s in 5" :key="s" class="fa-solid fa-star"></i>
            </div>
            <p class="text-white/80 text-sm italic leading-relaxed">
              {{ t('auth.loginQuote') }}
            </p>
            <footer class="mt-4 flex items-center gap-3">
              <span class="w-9 h-9 rounded-full bg-[#5B3FD4] grid place-items-center text-white text-xs font-poppins font-bold">SA</span>
              <span class="text-white/75 text-xs">Sarah, membre depuis 6 mois</span>
            </footer>
          </blockquote>
        </div>
      </div>
    </div>

    <MailVerificationModal
      :isOpen="isMailModalOpen"
      :email="identifier"
      :error="verificationError"
      :loading="verifying"
      @close="isMailModalOpen = false"
      @resend="handleResendCode"
      @verify="submitVerifyCode"
    />

    <Transition name="toast">
      <div
        v-if="verificationSuccess"
        class="fixed top-8 left-1/2 -translate-x-1/2 flex items-center gap-3 bg-emerald-500 text-white px-6 py-3 rounded-xl shadow-xl z-[110]"
      >
        <i class="fa-solid fa-circle-check"></i>
        {{ t('auth.verified') }}
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useUserStore } from '@/stores/user';
import { useFollowStore } from '@/stores/follow';
import MailVerificationModal from '@/components/layouts/MailVerificationModal.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseCheckbox from '@/components/ui/BaseCheckbox.vue';

const router = useRouter();
const route = useRoute();
const { t } = useI18n();
const userStore = useUserStore();
const followStore = useFollowStore();

const identifier = ref('');
const password = ref('');
const rememberMe = ref(false);
const errorMessage = ref('');
const loading = ref(false);

const submitForm = async () => {
  if (!identifier.value.trim()) {
    errorMessage.value = 'Renseigne ton adresse email pour continuer.';
    return;
  }
  if (!password.value.trim()) {
    errorMessage.value = 'Renseigne ton mot de passe pour continuer.';
    return;
  }

  loading.value = true;
  errorMessage.value = '';
  try {
    await userStore.loginUser({
      identifier: identifier.value,
      password: password.value,
      rememberMe: rememberMe.value,
    });
    // Un membre qui ne suit encore rien a un fil personnalisé vide : on
    // l'emmène choisir ses sujets plutôt que de le laisser devant du vide.
    if (!route.query.redirect && (await followStore.isEmpty())) {
      router.push('/bienvenue');
    } else {
      router.push(route.query.redirect || '/feed');
    }
  } catch (err) {
    errorMessage.value = err.message || 'Connexion impossible. Réessaie.';
    if (err.message === 'This account has not been verified yet.') {
      errorMessage.value = "Ton compte n'est pas encore vérifié. Saisis le code reçu par email.";
      isMailModalOpen.value = true;
    }
  } finally {
    loading.value = false;
  }
};

// Vérification de l'email par code OTP
const verifying = ref(false);
const verificationError = ref('');
const verificationSuccess = ref(false);
const isMailModalOpen = ref(false);

const submitVerifyCode = async (code) => {
  verificationError.value = '';
  verifying.value = true;
  try {
    await userStore.verifyEmailCode({ email: identifier.value, code });
    verificationSuccess.value = true;
    isMailModalOpen.value = false;
    errorMessage.value = '';
    setTimeout(() => {
      verificationSuccess.value = false;
    }, 2500);
  } catch (err) {
    verificationError.value = err.message || 'La vérification a échoué.';
  } finally {
    verifying.value = false;
  }
};

const handleResendCode = async () => {
  try {
    await userStore.resendVerificationCode(identifier.value);
  } catch {
    // Erreur silencieuse : la modale affiche déjà un feedback générique
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

.toast-enter-active,
.toast-leave-active {
  transition: all 0.4s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translate(-50%, -16px);
}
</style>
