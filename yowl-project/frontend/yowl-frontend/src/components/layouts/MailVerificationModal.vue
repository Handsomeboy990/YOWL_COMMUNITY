<template>
  <div v-if="isOpen" class="fixed inset-0 flex items-center justify-center z-50 bg-gray-500/30">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6 relative">

      <!-- close modal -->
      <button @click="$emit('close')"
        class="cursor-pointer absolute top-3 right-3 text-gray-600 hover:text-gray-900 text-xl">
        <i class="fas fa-times"></i>
      </button>

      <h2 class="text-2xl font-poppins font-bold text-center mb-4 text-[#1E2A38]">
        Verify your email
      </h2>

      <p class="text-gray-600 text-center mb-6">
        Enter the <span class="font-semibold">6-digit code</span> sent to
        <span class="font-semibold text-[#FF6B35]">{{ email }}</span>.
      </p>

      <!-- number fields -->
      <div class="flex justify-center gap-2 mb-6">
        <input v-for="(digit, index) in codeInputs" :key="index" ref="otpInputs" type="text" maxlength="1"
          class="w-12 h-12 text-center text-xl border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E2A38]"
          v-model="codeInputs[index]" @input="moveToNext(index, $event)"
          @keydown.backspace="moveToPrev(index, $event)" />
      </div>

      <!-- Actions -->
      <div class="flex flex-col gap-3 items-center">
        <button @click="verifyCode"
          class="px-6 py-2 w-full bg-[#1E2A38] cursor-pointer text-white rounded-lg hover:bg-gray-800 transition">
          Verify
        </button>
        <button @click="resendCode"
          class="px-6 py-2 w-full cursor-pointer bg-[#FF6B35] text-white rounded-lg hover:bg-[#e75a27] transition">
          Resend email
        </button>
        <button @click="$emit('close')"
          class="px-6 py-2 w-full cursor-pointer bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick } from "vue";
import Swal from "sweetalert2";

defineProps({
  isOpen: Boolean,
  email: String,
});

const emit = defineEmits(["close", "resend", "verify"]);

// array to stock number
const codeInputs = ref(["", "", "", "", "", ""]);
const otpInputs = ref([]);

// next field dynamism
const moveToNext = (index, event) => {
  if (event.target.value && index < 5) {
    nextTick(() => otpInputs.value[index + 1].focus());
  }
};

// get back to previous field
const moveToPrev = (index, event) => {
  if (!event.target.value && index > 0) {
    nextTick(() => otpInputs.value[index - 1].focus());
  }
};

// code verification
const verifyCode = () => {
  const code = codeInputs.value.join("");
  if (code.length === 6) {
    emit("verify", code);
  } else {
    Swal.fire({
      icon: 'warning',
      title: 'Wrong submit',
      text: 'Please fill all the fields with the right code you receive in your box mail',
      showConfirmButton: true,
      confirmButtonColor: '#FF6B35'
    });
  }
};

const resendCode = () => {
  $emit('resend');
  Swal.fire({
    icon: 'success',
    title: 'Code resend successfully',
    text: 'The code ha been resend successfully. Please check your mail box.',
    showConfirmButton: true,
    confirmButtonColor: '#FF6B35'
  });
}
</script>
