<template>
  <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <header class="px-5 py-4 border-b border-gray-100">
      <h2 class="font-semibold text-blue-night">Réglages de la plateforme</h2>
      <p class="mt-1 text-sm text-gray-500">
        Ces valeurs sont lues par l'application à chaque requête. Les modifier ne demande aucun déploiement.
      </p>
    </header>

    <div v-if="loading" class="p-5 space-y-4">
      <div v-for="n in 5" :key="n" class="h-12 rounded-xl skeleton"></div>
    </div>

    <div v-else-if="error" class="p-8 text-center">
      <i class="fa-solid fa-plug-circle-exclamation text-3xl text-gray-300"></i>
      <p class="mt-4 text-sm text-gray-600">{{ error }}</p>
      <BaseButton class="mt-4" size="sm" variant="primary" @click="load">Réessayer</BaseButton>
    </div>

    <form v-else class="p-5 space-y-8" @submit.prevent="save">
      <fieldset v-for="(fields, group) in grouped" :key="group">
        <legend class="font-poppins font-semibold text-blue-night mb-3">{{ group }}</legend>

        <div class="space-y-4">
          <div v-for="field in fields" :key="field.key"
            class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-6 py-3 border-b border-gray-50 last:border-0">
            <label :for="field.key" class="flex-1 text-sm text-gray-700">
              {{ field.label }}
              <span class="block text-xs text-gray-400 mt-0.5 font-mono">{{ field.key }}</span>
            </label>

            <!-- Interrupteur -->
            <button v-if="field.type === 'bool'" :id="field.key" type="button" role="switch"
              :aria-checked="Boolean(draft[field.key])"
              class="relative w-12 h-7 rounded-full transition-colors cursor-pointer shrink-0"
              :class="draft[field.key] ? 'bg-orange-primary' : 'bg-gray-300'"
              @click="draft[field.key] = !draft[field.key]">
              <span class="absolute top-1 w-5 h-5 rounded-full bg-white shadow transition-all"
                :class="draft[field.key] ? 'left-6' : 'left-1'"></span>
            </button>

            <!-- Nombre, vide autorise quand la borne est facultative -->
            <input v-else-if="field.type === 'int'" :id="field.key" v-model="draft[field.key]" type="number"
              class="w-full sm:w-40 px-3 py-2 rounded-xl border border-gray-200 focus:border-orange-primary focus:outline-none text-sm"
              placeholder="aucune limite" />

            <input v-else :id="field.key" v-model="draft[field.key]" type="text"
              class="w-full sm:w-72 px-3 py-2 rounded-xl border border-gray-200 focus:border-orange-primary focus:outline-none text-sm" />
          </div>
        </div>
      </fieldset>

      <div class="flex items-center justify-end gap-3 pt-2">
        <BaseButton variant="ghost" type="button" :disabled="!isDirty" @click="reset">Annuler</BaseButton>
        <BaseButton variant="primary" type="submit" :loading="saving" :disabled="!isDirty">
          Enregistrer
        </BaseButton>
      </div>
    </form>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '@/services/apiService';
import BaseButton from '@/components/ui/BaseButton.vue';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';

const notify = useNotify();

const fields = ref([]);
const draft = ref({});
const original = ref({});
const loading = ref(true);
const saving = ref(false);
const error = ref(null);

const grouped = computed(() =>
  fields.value.reduce((acc, field) => {
    (acc[field.group] ??= []).push(field);
    return acc;
  }, {})
);

const isDirty = computed(
  () => JSON.stringify(draft.value) !== JSON.stringify(original.value)
);

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get('/admin/settings');
    fields.value = response.data.data;
    const values = {};
    fields.value.forEach((field) => {
      values[field.key] = field.value;
    });
    draft.value = { ...values };
    original.value = { ...values };
  } catch (err) {
    error.value = apiErrorMessage(err, 'Impossible de charger les réglages.');
  } finally {
    loading.value = false;
  }
}

function reset() {
  draft.value = { ...original.value };
}

async function save() {
  saving.value = true;
  try {
    // Un champ numerique vide vaut "aucune limite", pas zero.
    const payload = {};
    fields.value.forEach((field) => {
      const value = draft.value[field.key];
      if (field.type === 'int') {
        payload[field.key] = value === '' || value === null ? null : Number(value);
      } else if (field.type === 'bool') {
        payload[field.key] = Boolean(value);
      } else {
        payload[field.key] = value;
      }
    });

    await api.patch('/admin/settings', { settings: payload });
    original.value = { ...draft.value };
    notify.success('Réglages enregistrés');
  } catch (err) {
    notify.error('Enregistrement impossible', apiErrorMessage(err, 'Réessaie dans un instant.'));
  } finally {
    saving.value = false;
  }
}

onMounted(load);
</script>
