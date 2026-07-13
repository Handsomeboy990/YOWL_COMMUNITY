<template>
  <div class="w-full">
    <label v-if="label" :for="textareaId" class="block text-sm font-medium text-blue-night mb-1.5">
      {{ label }}
      <span v-if="required" class="text-orange-primary">*</span>
    </label>

    <div
      class="relative rounded-xl border-2 bg-white transition-all duration-300"
      :class="[
        error
          ? 'border-red-400 shadow-sm shadow-red-100'
          : focused
            ? 'border-orange-primary shadow-md shadow-orange-primary/10'
            : 'border-gray-200 hover:border-gray-300',
      ]"
    >
      <textarea
        :id="textareaId"
        :value="modelValue"
        :placeholder="placeholder"
        :rows="rows"
        :maxlength="maxlength"
        :disabled="disabled"
        class="w-full bg-transparent px-4 py-3 text-blue-night placeholder-gray-400 outline-none resize-none disabled:cursor-not-allowed"
        @input="$emit('update:modelValue', $event.target.value)"
        @focus="focused = true"
        @blur="focused = false"
      ></textarea>

      <span
        v-if="maxlength"
        class="absolute bottom-2 right-3 text-xs"
        :class="remaining < 20 ? 'text-orange-primary' : 'text-gray-300'"
      >
        {{ remaining }}
      </span>
    </div>

    <p v-if="error" class="mt-1.5 text-sm text-red-500 flex items-center gap-1.5">
      <i class="fa-solid fa-circle-exclamation text-xs" aria-hidden="true"></i>
      {{ error }}
    </p>
  </div>
</template>

<script setup>
import { computed, ref, useId } from 'vue';

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
const focused = ref(false);
const remaining = computed(() =>
  props.maxlength ? props.maxlength - (props.modelValue?.length || 0) : 0
);
</script>
