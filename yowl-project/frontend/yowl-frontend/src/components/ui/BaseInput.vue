<template>
  <div class="w-full">
    <label v-if="label" :for="inputId" class="block text-sm font-medium text-blue-night mb-1.5">
      {{ label }}
      <span v-if="required" class="text-orange-text">*</span>
    </label>

    <div
      class="group relative flex items-center rounded-xl border-2 bg-white transition-all duration-200"
      :class="[
        error
          ? 'border-red-400 ring-2 ring-red-100'
          : focused
            ? 'border-orange-primary ring-2 ring-orange-primary/20'
            : 'border-gray-200 hover:border-gray-300',
      ]"
    >
      <span
        v-if="icon"
        class="pl-4 text-sm transition-colors duration-300"
        :class="focused ? 'text-orange-text' : 'text-gray-500'"
        aria-hidden="true"
      >
        <i :class="icon"></i>
      </span>

      <input
        :id="inputId"
        ref="inputRef"
        :type="isPassword && revealed ? 'text' : type"
        :value="modelValue"
        :placeholder="placeholder"
        :autocomplete="autocomplete"
        :disabled="disabled"
        :readonly="readonly"
        :aria-invalid="error ? 'true' : undefined"
        :aria-describedby="describedBy"
        :min="min"
        :max="max"
        class="peer w-full bg-transparent px-4 py-3 text-blue-night placeholder-gray-400 outline-none focus-visible:outline-none disabled:cursor-not-allowed disabled:text-gray-500 read-only:text-gray-500"
        @input="$emit('update:modelValue', $event.target.value)"
        @focus="focused = true"
        @blur="focused = false"
        @keyup.enter="$emit('enter')"
      />

      <button
        v-if="isPassword"
        type="button"
        class="pr-4 text-gray-500 hover:text-blue-night transition-colors cursor-pointer"
        :aria-label="revealed ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
        @click="revealed = !revealed"
      >
        <i :class="revealed ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
      </button>
    </div>

    <p v-if="error" :id="inputId + '-error'" role="alert"
      class="mt-1.5 text-sm text-red-500 flex items-center gap-1.5">
      <i class="fa-solid fa-circle-exclamation text-xs" aria-hidden="true"></i>
      {{ error }}
    </p>
    <p v-else-if="hint" :id="inputId + '-hint'" class="mt-1.5 text-xs text-gray-500">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed, ref, useId } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  type: { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  icon: { type: String, default: '' },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  autocomplete: { type: String, default: 'off' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  min: { type: [String, Number], default: undefined },
  max: { type: [String, Number], default: undefined },
});

defineEmits(['update:modelValue', 'enter']);

const inputId = useId();

// Le champ pointe vers son message d'erreur, sinon vers son aide : sans ce
// lien, un lecteur d'écran annonce le champ sans dire ce qui ne va pas.
const describedBy = computed(() => {
  if (props.error) return inputId + '-error';
  if (props.hint) return inputId + '-hint';
  return undefined;
});
const focused = ref(false);
const revealed = ref(false);
const inputRef = ref(null);

const isPassword = computed(() => props.type === 'password');

defineExpose({ focus: () => inputRef.value?.focus() });
</script>
