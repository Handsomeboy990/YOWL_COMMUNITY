<template>
  <div class="relative">
    <slot :on-input="handleInput" :on-keydown="handleKeydown" />

    <!-- Suggestions de pseudos. La liste suit la frappe après un @ et se
         ferme dès que la mention est complète ou abandonnée. -->
    <ul v-if="open && matches.length" role="listbox" aria-label="Membres à mentionner"
      class="absolute left-0 bottom-full mb-2 w-64 max-h-56 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-lg py-1 z-40">
      <li v-for="(membre, index) in matches" :key="membre.id">
        <button type="button" role="option" :aria-selected="index === active"
          class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-sm transition-colors cursor-pointer"
          :class="index === active ? 'bg-orange-50 text-orange-text' : 'text-gray-700 hover:bg-gray-50'"
          @mousedown.prevent="choose(membre)">
          <img v-if="membre.picture" :src="getStorageUrl(membre.picture)" alt=""
            class="w-7 h-7 rounded-full object-cover shrink-0" />
          <span v-else class="w-7 h-7 rounded-full bg-blue-night grid place-items-center text-white text-[10px] font-bold shrink-0">
            {{ (membre.username || '?').slice(0, 2).toUpperCase() }}
          </span>
          <span class="min-w-0">
            <span class="block font-medium truncate">@{{ membre.username }}</span>
            <span class="block text-xs text-gray-500 truncate">{{ membre.fullname }}</span>
          </span>
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import api from '@/services/apiService';
import { getStorageUrl } from '@/config';

const props = defineProps({
  modelValue: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const matches = ref([]);
const active = ref(0);
let debounce = null;
let start = -1;

/** Le fragment tapé depuis le dernier @, s'il y en a un en cours. */
function currentTerm(value, caret) {
  const avant = value.slice(0, caret);
  const at = avant.lastIndexOf('@');
  if (at === -1) return null;
  // Un @ collé à un mot est une adresse email, pas une mention.
  if (at > 0 && /[\w]/.test(avant[at - 1])) return null;
  const terme = avant.slice(at + 1);
  if (!/^[a-zA-Z0-9._-]*$/.test(terme) || terme.length > 30) return null;
  start = at;
  return terme;
}

async function search(terme) {
  try {
    const response = await api.get('/members/search', { params: { q: terme } });
    matches.value = response.data.data;
    active.value = 0;
    open.value = matches.value.length > 0;
  } catch {
    open.value = false;
  }
}

function handleInput(event) {
  const value = event.target.value;
  emit('update:modelValue', value);

  const terme = currentTerm(value, event.target.selectionStart ?? value.length);
  if (terme === null || terme.length < 1) {
    open.value = false;
    return;
  }

  clearTimeout(debounce);
  debounce = setTimeout(() => search(terme), 200);
}

function handleKeydown(event) {
  if (!open.value || !matches.value.length) return;

  if (event.key === 'ArrowDown') {
    event.preventDefault();
    active.value = (active.value + 1) % matches.value.length;
  } else if (event.key === 'ArrowUp') {
    event.preventDefault();
    active.value = (active.value - 1 + matches.value.length) % matches.value.length;
  } else if (event.key === 'Enter' || event.key === 'Tab') {
    event.preventDefault();
    choose(matches.value[active.value]);
  } else if (event.key === 'Escape') {
    open.value = false;
  }
}

function choose(membre) {
  const value = props.modelValue;
  const apres = value.slice(start).replace(/^@[a-zA-Z0-9._-]*/, '');
  emit('update:modelValue', value.slice(0, start) + '@' + membre.username + ' ' + apres.trimStart());
  open.value = false;
}
</script>
