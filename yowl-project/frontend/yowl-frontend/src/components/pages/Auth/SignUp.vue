<template>
  <div class="flex min-h-screen container mx-auto">
    <!-- Left section (form) -->
    <div class="w-1/2 p-12 bg-white">
      <div class="mb-6">
        <img src="../../../assets/logo.png" alt="YOWL Logo" class="h-20 mb-8" />
        <h1 class="text-3xl font-bold mb-4">Join Us</h1>
        <p class="text-gray-500 mb-6">
          Already have an account?
          <router-link to="/login" class="text-[#FF6B35] underline hover:underline">
            Signin now
          </router-link>
        </p>
      </div>

      <form @submit.prevent="submitForm" class="space-y-4">
        <!-- firstName -->
        <!-- error message -->
        <p v-if="errorMessage" class="text-red-500 text-sm">{{ errorMessage }}</p>

        <div>
          <label>First Name</label>
          <input
            type="text"
            v-model="form.firstname"
            placeholder="John"
            class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-[#1E2A38]"
            required
          />
        </div>

        <!-- lastName -->
        <div>
          <label>Last Name</label>
          <input
            type="text"
            v-model="form.lastname"
            placeholder="Doe"
            class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-[#1E2A38]"
            required
          />
        </div>

        <!-- username -->
        <div>
          <label>Username</label>
          <input
            type="text"
            v-model="form.username"
            placeholder="The_J0k3r"
            class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-[#1E2A38]"
            required
          />
        </div>

        <!-- email -->
        <div>
          <label>Email</label>
          <input
            type="email"
            v-model="form.email"
            placeholder="john@doe.com"
            class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-[#1E2A38]"
            required
          />
        </div>

        <!-- birthdate -->
        <div>
          <label>Birthdate</label>
          <input
            v-model="form.birthdate"
            type="date"
            class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-[#1E2A38]"
            required
          />
        </div>

        <!-- password -->
        <div class="relative">
          <label>Password</label>
          <input
            :type="showPassword ? 'text' : 'password'"
            v-model="form.password"
            placeholder="Password"
            class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-[#1E2A38]"
            required
          />
          <button
            type="button"
            @click="togglePasswordVisibility"
            class="absolute right-3 top-1/2 -translate-y-1/2"
          >
            <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="mt-5"></i>
          </button>
        </div>

        <!-- confirm password -->
        <div class="relative">
          <label>Confirm Password</label>
          <input
            :type="showConfirmPassword ? 'text' : 'password'"
            v-model="form.password_confirmation"
            placeholder="Confirm password"
            class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-[#1E2A38]"
            required
          />
          <button
            type="button"
            @click="toggleConfirmPasswordVisibility"
            class="absolute right-3 top-1/2 -translate-y-1/2"
          >
            <i
              :class="showConfirmPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"
              class="mt-5"
            ></i>
          </button>
        </div>

        <!-- policy acceptation -->
        <div class="flex items-center space-x-2">
          <input type="checkbox" class="cursor-pointer" v-model="form.agreeTerms" required />
          <span
            >I have read and agree to the
            <a href="#" class="cursor-pointer text-[#FF6B35] underline">terms</a></span
          >
        </div>

        <!-- submit -->
        <button
          type="submit"
          class="cursor-pointer w-full bg-[#1E2A38] text-white py-3 rounded-md hover:bg-[#1E2A38] transition duration-300"
        >
          Join us
        </button>
      </form>
    </div>

    <!-- Right section -->
    <div class="w-1/2 bg-[#FF6B35] flex items-center justify-center">
      <div class="bg-white bg-opacity-20 p-8 rounded-lg text-center">
        <h3 class="text-2xl font-bold mb-4">Express yourself freely</h3>
        <p class="mb-6">
          Unpack your unfiltered opinions on any content on the web with the YOWL community
        </p>
        <button
          class="cursor-pointer bg-[#1E2A38] text-white px-6 py-2 rounded-md hover:bg-[#1E2A38] hover:text-white"
        >
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

  <div
    v-if="verificationSuccess"
    class="fixed top-8 left-1/2 -translate-x-1/2 bg-green-500 text-white px-6 py-3 rounded shadow-lg z-50"
  >
    Email vérifié avec succès ! Vous pouvez maintenant vous connecter.
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useUserStore } from '@/stores/user';
// import { useRouter } from "vue-router";
import MailVerificationModal from '@/components/layouts/MailVerificationModal.vue';
// import Swal from 'sweetalert2';

const userStore = useUserStore();
// const router = useRouter();
const isMailModalOpen = ref(false);
const registeredEmail = ref('');

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

const showPassword = ref(false);
const showConfirmPassword = ref(false);
const errorMessage = ref('');

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};
const toggleConfirmPasswordVisibility = () => {
  showConfirmPassword.value = !showConfirmPassword.value;
};

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

const submitForm = async () => {
  // Validation
  if (!form.value.firstname.trim() || form.value.firstname.length < 3) {
    errorMessage.value = 'First name must be at least 3 characters.';
    return;
  }
  if (!form.value.lastname.trim() || form.value.lastname.length < 3) {
    errorMessage.value = 'Last name must be at least 3 characters.';
    return;
  }
  if (!form.value.username.trim() || form.value.username.length < 4) {
    errorMessage.value = 'Username must be at least 6 characters.';
    return;
  }
  if (!form.value.password || form.value.password.length < 6) {
    errorMessage.value = 'Password must be at least 6 characters.';
    return;
  }
  if (form.value.password !== form.value.password_confirmation) {
    errorMessage.value = 'Passwords do not match!';
    return;
  }
  if (!form.value.birthdate) {
    errorMessage.value = 'Birthdate is required.';
    return;
  } else {
    const age = calculateAge(form.value.birthdate);
    if (age < 13) {
      errorMessage.value =
        "Sorry, you're too young to be part of the Yowl community. Come back when you're 13.";
      return;
    }
    if (age > 35) {
      errorMessage.value = 'Sorry you are too old to be part of the yowl community.';
      return;
    }
  }
  if (!form.value.agreeTerms) {
    errorMessage.value = 'You must accept the terms!';
    return;
  }

  try {
    errorMessage.value = '';
    await userStore.registerUser(form.value);
    registeredEmail.value = form.value.email;
    form.value = {
      firstname: '',
      lastname: '',
      username: '',
      email: '',
      birthdate: '',
      password: '',
      password_confirmation: '',
      agreeTerms: false,
    };
    isMailModalOpen.value = true;
    // router.push("/login");
  } catch (error) {
    // Swal.fire({
    //   icon: 'error',
    //   title: 'Oooops',
    //   text: 'Something went wrong. Please try again ',
    //   showConfirmButton: false,
    //   timer: 1500,
    // });
    console.log('Registration fail', error);
    errorMessage.value = 'The email has already been taken.';
  }
};

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
    // Option: redirect to login après 2s
    setTimeout(() => {
      verificationSuccess.value = false;
      // router.push('/login?verified=1');
    }, 2000);
  } catch (e) {
    verificationError.value = e.message || 'Verification failed';
  } finally {
    verifying.value = false;
  }
};

const handleResendCode = async () => {
  try {
    await userStore.resendVerificationCode(registeredEmail.value);
  } catch (e) {
    console.error('Resend failed', e.message);
  }
};
</script>
