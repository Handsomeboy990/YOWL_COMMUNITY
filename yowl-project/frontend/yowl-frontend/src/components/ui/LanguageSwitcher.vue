<template>
  <div class="relative" ref="root">
    <button type="button"
      class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-500 hover:text-blue-night hover:bg-gray-50 transition-colors cursor-pointer w-full"
      :aria-expanded="open" aria-haspopup="listbox" @click="open = !open">
      <Icon name="language" class="w-4 text-center" />
      <span class="flex-1 text-left">{{ current }}</span>
      <Icon name="chevron-down" :size="14" class="text-xs" />
    </button>

    <ul v-if="open" role="listbox" :aria-label="t('settings.language')"
      class="absolute bottom-full left-0 mb-2 w-full bg-white border border-gray-200 rounded-xl shadow-lg py-1 z-30">
      <li v-for="code in locales" :key="code">
        <button type="button" role="option" :aria-selected="locale === code"
          class="w-full text-left px-3 py-2 text-sm transition-colors cursor-pointer"
          :class="locale === code ? 'text-orange-text font-medium bg-orange-50' : 'text-gray-600 hover:bg-gray-50'"
          @click="choose(code)">
          {{ t('settings.' + (code === 'fr' ? 'french' : 'english')) }}
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { setLocale, supportedLocales } from '@/i18n';

import Icon from '@/components/ui/Icon.vue';
const { t, locale } = useI18n();
const open = ref(false);
const root = ref(null);
const locales = supportedLocales;

const current = computed(() => t('settings.' + (locale.value === 'fr' ? 'french' : 'english')));

const choose = (code) => {
  setLocale(code);
  open.value = false;
};

const onClickOutside = (event) => {
  if (root.value && !root.value.contains(event.target)) open.value = false;
};

onMounted(() => document.addEventListener('click', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside));
</script>
