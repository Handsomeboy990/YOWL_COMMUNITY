<template>
  <div ref="rootRef" class="w-full relative">
    <label v-if="label" class="block text-sm font-medium text-blue-night mb-1.5">
      {{ label }}
    </label>

    <button
      type="button"
      class="w-full flex items-center justify-between gap-3 rounded-xl border-2 bg-white px-4 py-3 text-left transition-all duration-300 cursor-pointer focus:outline-none"
      :class="[
        open
          ? 'border-orange-primary shadow-md shadow-orange-primary/10'
          : 'border-gray-200 hover:border-gray-300',
      ]"
      :aria-expanded="open"
      aria-haspopup="listbox"
      @click="toggle"
      @keydown.down.prevent="highlight(1)"
      @keydown.up.prevent="highlight(-1)"
      @keydown.enter.prevent="selectHighlighted"
      @keydown.esc="open = false"
    >
      <span class="flex items-center gap-2 truncate" :class="selected ? 'text-blue-night' : 'text-gray-500'">
        <i v-if="selected?.icon" :class="selected.icon" class="text-orange-text" aria-hidden="true"></i>
        {{ selected ? selected.label : placeholder }}
      </span>
      <Icon name="chevron-down" :size="14"
        class="text-gray-500 transition-transform duration-300"
        :class="open ? 'rotate-180 text-orange-text' : ''" />
    </button>

    <Transition name="select-pop">
      <ul
        v-if="open"
        class="absolute z-50 mt-2 w-full max-h-60 overflow-auto rounded-xl border border-gray-100 bg-white py-1.5 shadow-xl shadow-blue-night/10"
        role="listbox"
      >
        <li
          v-for="(option, index) in options"
          :key="option.value"
          class="mx-1.5 flex items-center gap-2.5 min-h-11 rounded-lg px-3 py-2.5 text-sm cursor-pointer transition-colors duration-150"
          :class="[
            option.value === modelValue
              ? 'bg-orange-primary/10 text-orange-text font-semibold'
              : index === highlighted
                ? 'bg-gray-50 text-blue-night'
                : 'text-blue-night hover:bg-gray-50',
          ]"
          role="option"
          :aria-selected="option.value === modelValue"
          @click="select(option)"
          @mouseenter="highlighted = index"
        >
          <i v-if="option.icon" :class="option.icon" class="w-4 text-center" aria-hidden="true"></i>
          <span class="flex-1">{{ option.label }}</span>
          <Icon name="check" :size="14" class="text-xs" v-if="option.value === modelValue" aria-hidden="true" />
        </li>
      </ul>
    </Transition>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

import Icon from '@/components/ui/Icon.vue';
const props = defineProps({
  modelValue: { type: [String, Number, null], default: null },
  // options: [{ value, label, icon? }]
  options: { type: Array, required: true },
  label: { type: String, default: '' },
  placeholder: { type: String, default: 'Sélectionner...' },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const highlighted = ref(-1);
const rootRef = ref(null);

const selected = computed(() => props.options.find((o) => o.value === props.modelValue) || null);

const toggle = () => {
  open.value = !open.value;
  if (open.value) {
    highlighted.value = props.options.findIndex((o) => o.value === props.modelValue);
  }
};

const select = (option) => {
  emit('update:modelValue', option.value);
  open.value = false;
};

const highlight = (dir) => {
  if (!open.value) {
    open.value = true;
    return;
  }
  const count = props.options.length;
  highlighted.value = (highlighted.value + dir + count) % count;
};

const selectHighlighted = () => {
  if (open.value && highlighted.value >= 0) {
    select(props.options[highlighted.value]);
  } else {
    toggle();
  }
};

const onClickOutside = (event) => {
  if (rootRef.value && !rootRef.value.contains(event.target)) {
    open.value = false;
  }
};

onMounted(() => document.addEventListener('click', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside));
</script>

<style scoped>
.select-pop-enter-active,
.select-pop-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}
.select-pop-enter-from,
.select-pop-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.98);
}
</style>
