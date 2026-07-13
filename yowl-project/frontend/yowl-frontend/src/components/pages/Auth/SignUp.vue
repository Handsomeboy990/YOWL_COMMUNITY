<template>
  <div class="min-h-screen w-full flex bg-white">
    <!-- Panneau marque -->
    <div class="hidden lg:flex w-1/2 relative bg-blue-night items-center justify-center overflow-hidden">
      <div class="auth-blob auth-blob-1" aria-hidden="true"></div>
      <div class="auth-blob auth-blob-2" aria-hidden="true"></div>

      <div class="relative z-10 max-w-md px-12">
        <div
          class="w-20 h-20 rounded-3xl bg-gradient-to-br from-orange-primary to-[#ff8c5a] grid place-items-center text-white text-3xl shadow-2xl shadow-orange-primary/40 -rotate-3 animate-fade-in-up"
        >
          <i class="fa-solid fa-bullhorn"></i>
        </div>
        <h2 class="mt-8 font-poppins font-extrabold text-3xl text-white leading-snug animate-fade-in-up animation-delay-200">
          Exprime-toi librement
        </h2>
        <p class="mt-4 text-white/70 leading-relaxed animate-fade-in-up animation-delay-400">
          Partage tes avis sans filtre sur n'importe quel contenu du web,
          avec une communauté de 13 à 35 ans qui te ressemble.
        </p>

        <ul class="mt-10 space-y-4 animate-fade-in-up animation-delay-400">
          <li v-for="perk in perks" :key="perk" class="flex items-center gap-3 text-white/80">
            <span class="w-7 h-7 rounded-full bg-orange-primary/20 text-orange-primary grid place-items-center text-xs">
              <i class="fa-solid fa-check"></i>
            </span>
            {{ perk }}
          </li>
        </ul>
      </div>
    </div>

    <!-- Panneau formulaire -->
    <div class="w-full lg:w-1/2 flex flex-col justify-center px-6 sm:px-12 xl:px-24 py-10">
      <router-link to="/" class="inline-flex items-center gap-2.5 mb-8 w-max">
        <img src="@/assets/logo.png" alt="Logo YOWL" class="h-12" />
        <span class="font-poppins font-extrabold text-2xl text-blue-night">YOWL</span>
      </router-link>

      <div class="max-w-md w-full mx-auto lg:mx-0 animate-fade-in-up">
        <h1 class="font-poppins font-extrabold text-3xl md:text-4xl text-blue-night">
          Rejoins l'aventure
        </h1>
        <p class="text-gray-500 mt-3 mb-8">
          Déjà membre ?
          <router-link to="/login" class="text-orange-primary font-semibold hover:underline">
            Connecte-toi
          </router-link>
        </p>

        <form class="space-y-4" @submit.prevent="submitForm">
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

          <div class="grid sm:grid-cols-2 gap-4">
            <BaseInput
              v-model="form.firstname"
              label="Prénom"
              placeholder="Jean"
              icon="fa-regular fa-user"
              autocomplete="given-name"
              required
            />
            <BaseInput
              v-model="form.lastname"
              label="Nom"
              placeholder="Dupont"
              icon="fa-regular fa-user"
              autocomplete="family-name"
              required
            />
          </div>

          <BaseInput
            v-model="form.username"
            label="Pseudo"
            placeholder="Le_J0k3r"
            icon="fa-solid fa-at"
            hint="Visible par toute la communauté"
            required
          />

          <BaseInput
            v-model="form.email"
            label="Adresse email"
            type="email"
            placeholder="toi@exemple.com"
            icon="fa-regular fa-envelope"
            autocomplete="email"
            required
          />

          <BaseInput
            v-model="form.birthdate"
            label="Date de naissance"
            type="date"
            icon="fa-regular fa-calendar"
            hint="La communauté est réservée aux 13-35 ans"
            required
          />

          <div class="grid sm:grid-cols-2 gap-4">
            <BaseInput
              v-model="form.password"
              label="Mot de passe"
              type="password"
              placeholder="8 caractères min."
              icon="fa-solid fa-lock"
              autocomplete="new-password"
              required
            />
            <BaseInput
              v-model="form.password_confirmation"
              label="Confirmation"
              type="password"
              placeholder="Confirme-le"
              icon="fa-solid fa-lock"
              autocomplete="new-password"
              required
            />
          </div>

          <BaseCheckbox v-model="form.agreeTerms">
            J'ai lu et j'accepte la
            <router-link to="/policy" class="text-orange-primary font-semibold hover:underline">
              charte de la communauté
            </router-link>
          </BaseCheckbox>

          <BaseButton type="submit" variant="night" size="lg" block :loading="loading">
            Créer mon compte
          </BaseButton>
        </form>
      </div>
    </div>
  </div>

  <MailVerificationModal
    :isOpen="isMailModalOpen"
    :email="registeredEmail"
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
      Email vérifié avec succès ! Vous pouvez maintenant vous connecter.
    </div>
  </Transition>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useUserStore } from '@/stores/user';
import MailVerificationModal from '@/components/layouts/MailVerificationModal.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseCheckbox from '@/components/ui/BaseCheckbox.vue';

const userStore = useUserStore();
const router = useRouter();

const perks = [
  'Inscription gratuite en 2 minutes',
  'Publie des avis illimités',
  'Une communauté modérée et bienveillante',
];

const isMailModalOpen = ref(false);
const registeredEmail = ref('');
const loading = ref(false);
const errorMessage = ref('');

const form = ref({
  firstname: '',
  lastname: '',
  username: '',
  email: '',
  birthdate: '',
  password: '',
  password_confirmation: '',
  agreeTerms: false,
});

function calculateAge(dateString) {
  const today = new Date();
  const birthDate = new Date(dateString);
  let age = today.getFullYear() - birthDate.getFullYear();
  const months = today.getMonth() - birthDate.getMonth();
  if (months < 0 || (months === 0 && today.getDate() < birthDate.getDate())) {
    age--;
  }
  return age;
}

const validate = () => {
  if (!form.value.firstname.trim() || form.value.firstname.trim().length < 2) {
    return 'Le prénom doit contenir au moins 2 caractères.';
  }
  if (!form.value.lastname.trim() || form.value.lastname.trim().length < 2) {
    return 'Le nom doit contenir au moins 2 caractères.';
  }
  if (!form.value.username.trim() || form.value.username.trim().length < 3) {
    return 'Le pseudo doit contenir au moins 3 caractères.';
  }
  if (!form.value.email.trim()) {
    return "L'adresse email est obligatoire.";
  }
  if (!form.value.birthdate) {
    return 'La date de naissance est obligatoire.';
  }
  const age = calculateAge(form.value.birthdate);
  if (age < 13) {
    return "Désolé, tu es trop jeune pour rejoindre la communauté YOWL. Reviens à tes 13 ans !";
  }
  if (age > 35) {
    return 'Désolé, la communauté YOWL est réservée aux 13-35 ans.';
  }
  if (!form.value.password || form.value.password.length < 8) {
    return 'Le mot de passe doit contenir au moins 8 caractères.';
  }
  if (form.value.password !== form.value.password_confirmation) {
    return 'Les mots de passe ne correspondent pas.';
  }
  if (!form.value.agreeTerms) {
    return 'Tu dois accepter la charte de la communauté.';
  }
  return '';
};

const submitForm = async () => {
  errorMessage.value = validate();
  if (errorMessage.value) return;

  loading.value = true;
  try {
    await userStore.registerUser(form.value);
    registeredEmail.value = form.value.email;
    isMailModalOpen.value = true;
  } catch (err) {
    errorMessage.value = translateError(err.message);
  } finally {
    loading.value = false;
  }
};

// Traduire les messages d'erreur les plus courants du backend
function translateError(message) {
  const map = {
    'The email has already been taken.': 'Cette adresse email est déjà utilisée.',
    'The username has already been taken.': 'Ce pseudo est déjà pris.',
  };
  return map[message] || message || "L'inscription a échoué. Réessaie.";
}

const verifying = ref(false);
const verificationError = ref('');
const verificationSuccess = ref(false);

const submitVerifyCode = async (code) => {
  verificationError.value = '';
  verifying.value = true;
  try {
    await userStore.verifyEmailCode({ email: registeredEmail.value, code });
    verificationSuccess.value = true;
    isMailModalOpen.value = false;
    setTimeout(() => {
      verificationSuccess.value = false;
      router.push('/login');
    }, 2000);
  } catch (err) {
    verificationError.value = err.message || 'La vérification a échoué.';
  } finally {
    verifying.value = false;
  }
};

const handleResendCode = async () => {
  try {
    await userStore.resendVerificationCode(registeredEmail.value);
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
  left: -100px;
  background: rgba(255, 107, 53, 0.3);
}
.auth-blob-2 {
  width: 360px;
  height: 360px;
  bottom: -100px;
  right: -110px;
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
