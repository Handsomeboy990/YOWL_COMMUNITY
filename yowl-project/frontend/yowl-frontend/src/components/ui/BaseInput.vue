<template>
  <div class="w-full">
    <label v-if="label" :for="inputId" class="block text-sm font-medium text-blue-night mb-1.5">
      {{ label }}
      <span v-if="required" class="text-orange-primary">*</span>
    </label>

    <div
      class="group relative flex items-center rounded-xl border-2 bg-white transition-all duration-300"
      :class="[
        error
          ? 'border-red-400 shadow-sm shadow-red-100'
          : focused
            ? 'border-orange-primary shadow-md shadow-orange-primary/10'
            : 'border-gray-200 hover:border-gray-300',
      ]"
    >
      <span
        v-if="icon"
        class="pl-4 text-sm transition-colors duration-300"
        :class="focused ? 'text-orange-primary' : 'text-gray-400'"
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
        :min="min"
        :max="max"
        class="peer w-full bg-transparent px-4 py-3 text-blue-night placeholder-gray-400 outline-none disabled:cursor-not-allowed disabled:text-gray-400 read-only:text-gray-500"
        @input="$emit('update:modelValue', $event.target.value)"
        @focus="focused = true"
        @blur="focused = false"
        @keyup.enter="$emit('enter')"
      />

      <button
        v-if="isPassword"
        type="button"
        class="pr-4 text-gray-400 hover:text-blue-night transition-colors cursor-pointer"
        :aria-label="revealed ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
        @click="revealed = !revealed"
      >
        <i :class="revealed ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
      </button>
    </div>

    <p v-if="error" class="mt-1.5 text-sm text-red-500 flex items-center gap-1.5">
      <i class="fa-solid fa-circle-exclamation text-xs" aria-hidden="true"></i>
      {{ error }}
    </p>
    <p v-else-if="hint" class="mt-1.5 text-xs text-gray-400">{{ hint }}</p>
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
const focused = ref(false);
const revealed = ref(false);
const inputRef = ref(null);

const isPassword = computed(() => props.type === 'password');

defineExpose({ focus: () => inputRef.value?.focus() });
</script>
