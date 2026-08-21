<template>
  <BaseModal :isOpen="isOpen" size="sm" @close="emit('close')">
    <div class="text-center">
      <div
        class="mx-auto w-16 h-16 rounded-2xl bg-gradient-to-br from-orange-primary to-orange-primary-dark grid place-items-center text-white text-2xl shadow-lg shadow-orange-primary/30 mb-5"
      >
        <Icon name="envelope-open" />
      </div>

      <h2 class="text-2xl font-poppins font-bold text-blue-night mb-2">{{ t('auth.verifyTitle') }}</h2>

      <p class="text-gray-500 mb-6 text-sm leading-relaxed">
        Saisis le <span class="font-semibold text-blue-night">code à 6 chiffres</span> envoyé à
        <span class="font-semibold text-orange-text break-all">{{ email }}</span>
      </p>

      <!-- Champs du code -->
      <div class="flex justify-center gap-2 mb-2" @paste.prevent="onPaste">
        <input
          v-for="(digit, index) in codeInputs"
          :key="index"
          ref="otpInputs"
          v-model="codeInputs[index]"
          type="text"
          inputmode="numeric"
          maxlength="1"
          class="w-11 h-13 sm:w-12 sm:h-14 text-center text-xl font-poppins font-bold text-blue-night border-2 border-gray-200 rounded-xl transition-all duration-200 focus:outline-none focus:border-orange-primary focus:shadow-md focus:shadow-orange-primary/10"
          :class="error ? 'border-red-300' : ''"
          @input="moveToNext(index, $event)"
          @keydown.backspace="moveToPrev(index, $event)"
        />
      </div>

      <p v-if="error" class="text-sm text-red-500 mb-2 flex items-center justify-center gap-1.5">
        <Icon name="circle-exclamation" :size="14" class="text-xs" aria-hidden="true" />
        {{ error }}
      </p>

      <div class="flex flex-col gap-3 mt-5">
        <BaseButton variant="night" block :loading="loading" @click="verifyCode">
          {{ t('auth.verifyAction') }}
        </BaseButton>
        <BaseButton variant="primary" block :disabled="resendCooldown > 0" :shine="false" @click="resendCode">
          {{ resendCooldown > 0 ? `Renvoyer le code (${resendCooldown}s)` : 'Renvoyer le code' }}
        </BaseButton>
      </div>

      <p class="mt-4 text-xs text-gray-500">
        {{ t('auth.verifyHint') }}
      </p>
    </div>
  </BaseModal>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { useNotify } from '@/composables/useNotify';
import BaseModal from '@/components/ui/BaseModal.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

import Icon from '@/components/ui/Icon.vue';
const { t } = useI18n();

const props = defineProps({
  isOpen: Boolean,
  email: String,
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'resend', 'verify']);

const codeInputs = ref(['', '', '', '', '', '']);
const otpInputs = ref([]);
const resendCooldown = ref(0);
const notify = useNotify();
let cooldownTimer = null;

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      codeInputs.value = ['', '', '', '', '', ''];
      nextTick(() => otpInputs.value[0]?.focus());
    }
  }
);

const moveToNext = (index, event) => {
  // Ne garder que les chiffres
  const value = event.target.value.replace(/\D/g, '');
  codeInputs.value[index] = value;
  if (value && index < 5) {
    nextTick(() => otpInputs.value[index + 1]?.focus());
  }
};

const moveToPrev = (index, event) => {
  if (!event.target.value && index > 0) {
    nextTick(() => otpInputs.value[index - 1]?.focus());
  }
};

// Permettre de coller le code complet d'un coup
const onPaste = (event) => {
  const pasted = (event.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, 6);
  if (!pasted) return;
  pasted.split('').forEach((digit, i) => {
    codeInputs.value[i] = digit;
  });
  nextTick(() => otpInputs.value[Math.min(pasted.length, 5)]?.focus());
};

const verifyCode = () => {
  const code = codeInputs.value.join('');
  if (code.length === 6) {
    emit('verify', code);
  } else {
    notify.warning('Code incomplet', 'Merci de saisir les 6 chiffres du code reçu par email.');
  }
};

const resendCode = () => {
  emit('resend');
  resendCooldown.value = 60;
  cooldownTimer = setInterval(() => {
    resendCooldown.value--;
    if (resendCooldown.value <= 0) clearInterval(cooldownTimer);
  }, 1000);
  notify.success('Code renvoyé', 'Un nouveau code vient de partir. Vérifie ta boîte mail.');
};

onBeforeUnmount(() => clearInterval(cooldownTimer));
</script>
