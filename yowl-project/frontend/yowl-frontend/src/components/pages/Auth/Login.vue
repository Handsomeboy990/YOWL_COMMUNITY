<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <div class="flex min-h-screen container mx-auto">
    <!-- Partie gauche : Formulaire -->
    <div class="w-1/2 p-12 bg-white">
      <div class="mb-6">
        <img src="../../../assets/logo.png" alt="YOWL Logo" class="h-20 mb-8">
        <h1 class="text-3xl font-bold mb-4">Welcome Back</h1>
        <p class="text-gray-500 mb-6">
          Don’t have an account?
          <router-link to="/signup" class="text-[#FF6B35] underline hover:underline">Signup now</router-link>
        </p>
      </div>

      <form @submit.prevent="submitForm" class="space-y-4">
        <!-- error message -->
        <p v-if="errorMessage" class="text-red-500 text-sm">{{ errorMessage }}</p>

        <!-- email or username -->
        <div>
          <label>Email</label>
          <input type="text" v-model="identifier" placeholder="Enter your email"
            class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-yowl-orange">
        </div>

        <!-- password -->
        <div class="relative">
          <label>Password</label>
          <input :type="showPassword ? 'text' : 'password'" v-model="password" placeholder="********"
            class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-[#1E2A38]">
          <button type="button" @click="togglePasswordVisibility" class="absolute right-3 inset-y-0 flex items-center">
            <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="mt-5"></i>
          </button>
        </div>

        <!-- remember me -->
        <div class="flex items-center space-x-2">
          <input type="checkbox" class="cursor-pointer" v-model="rememberMe">
          <label>Remember me</label>
        </div>

        <!-- submit -->
        <button type="submit"
          class="cursor-pointer w-full bg-[#FF6B35] text-white py-3 rounded-md hover:bg-[#FF6B35] transition duration-300">
          Sign in
        </button>
      </form>
    </div>

    <!-- Right section -->
    <div class="w-1/2 bg-[#FF6B35] flex items-center justify-center">
      <div class="bg-white bg-opacity-20 p-8 rounded-lg text-center">
        <h3 class="text-2xl font-bold mb-4">Reconnect with your community</h3>
        <p class="mb-6">Your opinions, your voice — join the YOWL community today.</p>
        <button class="cursor-pointer bg-[#1E2A38] text-white px-6 py-2 rounded-md hover:bg-black hover:text-white">
          Learn more
        </button>
      </div>
    </div>
  </div>
  <MailVerificationModal
    :isOpen="isMailModalOpen"
    :email="registeredEmail"
    @close="isMailModalOpen = false"
    @resend="handleResendCode"
    @verify="submitVerifyCode"
  />

  <div v-if="verificationSuccess" class="fixed top-8 left-1/2 -translate-x-1/2 bg-green-500 text-white px-6 py-3 rounded shadow-lg z-50">
    Email vérifié avec succès ! Vous pouvez maintenant vous connecter.
  </div>
</template>

<script setup>
import { ref } from "vue"
import { useRouter } from "vue-router"
import { useUserStore } from "@/stores/user"
// import Swal from "sweetalert2"
import MailVerificationModal from "@/components/layouts/MailVerificationModal.vue"

const router = useRouter()
const userStore = useUserStore()

// form fielsd
const identifier = ref("")
const password = ref("")
const rememberMe = ref(false)
const showPassword = ref(false)

// error message
const errorMessage = ref("")

// toggle password visibilityt
const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

// submit form
const submitForm = async () => {

  if (!identifier.value.trim()) {
    errorMessage.value = "You need to enter your email or your username to continue";
    return;
  }
  if (!password.value.trim()) {
    errorMessage.value = "You need to enter your password to continue ";
    return;
  }
  try {
    await userStore.loginUser({
      identifier: identifier.value,
      password: password.value,
      rememberMe: rememberMe.value
    })
    // Swal.fire({
    //   icon: 'success',
    //   title: 'Logged successfull',
    //   text: 'Welcome back ',
    //   showConfirmButton: false,
    //   timer: 1500,
    // });
    router.push("/") // redirection to homepage after login
  } catch (err) {

    // Swal.fire({
    //   icon: 'error',
    //   title: 'Ooooops',
    //   text: 'Something went wrong. Please try again',
    //   showConfirmButton: false,
    // });
    errorMessage.value = err.message || "Login failed";
    if(errorMessage.value == "This account has not been verified yet.") {
      isMailModalOpen.value = true;
    }
  }
}

// Logique pour le modal de vérification
const verifying = ref(false);
const verificationError = ref("");
const verificationSuccess = ref(false);
const isMailModalOpen = ref(false);

const submitVerifyCode = async (code) => {
  verificationError.value = "";
  verifying.value = true;
  try {
    await userStore.verifyEmailCode({ email: identifier.value, code });

    verificationSuccess.value = true;
    isMailModalOpen.value = false;
    // Option: redirect to login après 2s
    setTimeout(() => {
      verificationSuccess.value = false;
      // router.push('/login?verified=1');
    }, 2000);
  } catch (err) {
    verificationError.value = err.message || 'Verification failed';
  } finally {
    verifying.value = false;
  }
};

const handleResendCode = async () => {
  try {
    await userStore.resendVerificationCode(identifier.value);

  } catch {
        // Silent error handling
    }
};
</script>
