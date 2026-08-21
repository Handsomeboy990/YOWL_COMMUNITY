<template>
  <div class="w-full">
    <label v-if="label" :for="textareaId" class="block text-sm font-medium text-blue-night mb-1.5">
      {{ label }}
      <span v-if="required" class="text-orange-text">*</span>
    </label>

    <div
      class="relative rounded-xl border-2 bg-white transition-all duration-200"
      :class="[
        error
          ? 'border-red-400 ring-2 ring-red-100'
          : focused
            ? 'border-orange-primary ring-2 ring-orange-primary/20'
            : 'border-gray-200 hover:border-gray-300',
      ]"
    >
      <textarea
        :id="textareaId"
        :aria-invalid="error ? 'true' : undefined"
        :aria-describedby="describedBy"
        :value="modelValue"
        :placeholder="placeholder"
        :rows="rows"
        :maxlength="maxlength"
        :disabled="disabled"
        class="w-full bg-transparent px-4 py-3 text-blue-night placeholder-gray-400 outline-none focus-visible:outline-none resize-none disabled:cursor-not-allowed"
        @input="$emit('update:modelValue', $event.target.value)"
        @focus="focused = true"
        @blur="focused = false"
      ></textarea>

      <span
        v-if="maxlength"
        class="absolute bottom-2 right-3 text-xs"
        :class="remaining < 20 ? 'text-orange-text' : 'text-gray-500'"
      >
        {{ remaining }}
      </span>
    </div>

    <p v-if="error" :id="textareaId + '-error'" role="alert"
      class="mt-1.5 text-sm text-red-500 flex items-center gap-1.5">
      <Icon name="circle-exclamation" :size="14" class="text-xs" aria-hidden="true" />
      {{ error }}
    </p>
  </div>
</template>

<script setup>
import { computed, ref, useId } from 'vue';

import Icon from '@/components/ui/Icon.vue';
const props = defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  rows: { type: Number, default: 4 },
  maxlength: { type: Number, default: undefined },
  error: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);

const textareaId = useId();

// Le champ pointe vers son message d'erreur, sinon vers son aide.
const describedBy = computed(() => {
  if (props.error) return textareaId + '-error';
  if (props.hint) return textareaId + '-hint';
  return undefined;
});
const focused = ref(false);
const remaining = computed(() =>
  props.maxlength ? props.maxlength - (props.modelValue?.length || 0) : 0
);
</script>
