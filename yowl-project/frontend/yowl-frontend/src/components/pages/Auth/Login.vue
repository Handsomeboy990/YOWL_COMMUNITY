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
                <Icon name="circle-exclamation" class="mt-0.5" aria-hidden="true" />
                <span>{{ errorMessage }}</span>
              </div>
            </Transition>

            <BaseInput
              v-model="identifier"
              label="Adresse email"
              type="email"
              placeholder="toi@exemple.com"
              icon="envelope"
              autocomplete="email"
              required
              @enter="submitForm"
            />

            <BaseInput
              v-model="password"
              label="Mot de passe"
              type="password"
              placeholder="Ton mot de passe"
              icon="lock"
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
            <Icon name="comments" />
          </div>
          <h2 class="mt-8 font-poppins font-extrabold text-3xl text-white leading-snug animate-fade-in-up animation-delay-200">
            {{ t('auth.loginTitle') }}
          </h2>
          <p class="mt-4 text-white/70 leading-relaxed animate-fade-in-up animation-delay-400">
            {{ t('auth.loginPitch') }}
          </p>

          <blockquote class="mt-10 bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl p-6 text-left animate-fade-in-up animation-delay-400">
            <div class="flex gap-1 text-orange-text mb-3 text-xs" aria-hidden="true">
              <Icon name="star" v-for="s in 5" :key="s" />
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
      :reportable="verificationReportable"
      :restant="verificationRestant"
      @close="verificationReportable ? reporterVerification() : (isMailModalOpen = false)"
      @later="reporterVerification"
      @resend="handleResendCode"
      @verify="submitVerifyCode"
    />

    <Transition name="toast">
      <div
        v-if="verificationSuccess"
        class="fixed top-8 left-1/2 -translate-x-1/2 flex items-center gap-3 bg-emerald-500 text-white px-6 py-3 rounded-xl shadow-xl z-[110]"
      >
        <Icon name="circle-check" />
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

import Icon from '@/components/ui/Icon.vue';
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
    const reponse = await userStore.loginUser({
      identifier: identifier.value,
      password: password.value,
      rememberMe: rememberMe.value,
    });

    // Adresse pas encore vérifiée, mais le compte a le droit d'entrer : on
    // demande le code, avec la possibilité de le remettre à plus tard. La
    // navigation attend la réponse, sinon la fenêtre s'ouvrirait par-dessus
    // le fil et ressemblerait à une interruption plutôt qu'à une étape.
    const etat = reponse?.verification;
    if (etat && !etat.verifie) {
      verificationReportable.value = true;
      verificationRestant.value = etat.restant ?? 0;
      isMailModalOpen.value = true;
      return;
    }

    await entrer();
  } catch (err) {
    // Le message vient du serveur, qui sait ce qui s'est passé et le dit dans
    // la langue du membre. Le code, lui, sert à décider quoi ouvrir.
    //
    // Cette branche testait auparavant le texte anglais du message : traduire
    // ce message suffisait à ce que la fenêtre de saisie du code cesse de
    // s'ouvrir, sans erreur nulle part.
    errorMessage.value = err.message || 'La connexion a échoué. Réessayez dans un instant.';

    if (err.code === 'email_non_verifie') {
      // Refus ferme : le seuil est dépassé, il n'y a plus rien à reporter.
      verificationReportable.value = false;
      verificationRestant.value = 0;
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
const verificationReportable = ref(false);
const verificationRestant = ref(0);

/**
 * Entrer dans le site une fois la connexion acquise.
 *
 * Un membre qui ne suit encore rien a un fil personnalisé vide : on l'emmène
 * choisir ses sujets plutôt que de le laisser devant du vide.
 */
async function entrer() {
  if (!route.query.redirect && (await followStore.isEmpty())) {
    router.push('/bienvenue');
  } else {
    router.push(route.query.redirect || '/feed');
  }
}

/** Reporter la vérification : le compte entre, le rappel reste en haut. */
function reporterVerification() {
  isMailModalOpen.value = false;
  entrer();
}

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
  verificationError.value = '';
  try {
    const reponse = await userStore.resendVerificationCode(identifier.value);

    // Le serveur repond 202 avec delivered a faux quand le relais n'a pas
    // pris le message : le dire, plutot que de laisser attendre un code qui
    // ne viendra pas.
    if (reponse?.delivered === false) {
      verificationError.value = reponse.message;
    }
  } catch (err) {
    verificationError.value = err.message || "Le code n'a pas pu être renvoyé.";
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
