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
      <i class="fa-solid fa-plug-circle-exclamation text-3xl text-gray-400" aria-hidden="true"></i>
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
              <span v-if="field.help" class="block text-xs text-gray-500 mt-1">{{ field.help }}</span>
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

            <!-- Image : on montre ce qui est en place, sinon on ne sait pas
                 ce qu'on remplace. Le fichier part tout de suite et le réglage
                 ne retient que son chemin. -->
            <div v-else-if="field.type === 'image'" class="w-full sm:w-72 shrink-0">
              <div class="flex items-center gap-3">
                <span
                  class="w-14 h-14 shrink-0 rounded-xl border border-gray-200 bg-gray-50 grid place-items-center overflow-hidden">
                  <img v-if="draft[field.key]" :src="getStorageUrl(draft[field.key])" alt=""
                    class="w-full h-full object-contain" />
                  <i v-else class="fa-regular fa-image text-gray-400" aria-hidden="true"></i>
                </span>

                <div class="min-w-0 flex-1">
                  <label
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-medium text-gray-700 hover:border-orange-primary hover:text-orange-text transition-colors cursor-pointer">
                    <i class="fa-solid fa-arrow-up-from-bracket" aria-hidden="true"></i>
                    <span>{{ uploading === field.key ? 'Envoi...' : 'Choisir un fichier' }}</span>
                    <input :id="field.key" type="file" class="hidden"
                      accept="image/png,image/jpeg,image/webp,image/svg+xml"
                      @change="uploadImage(field.key, $event)" />
                  </label>
                  <button v-if="draft[field.key]" type="button"
                    class="block mt-1.5 text-xs text-gray-500 hover:text-red-600 transition-colors cursor-pointer"
                    @click="draft[field.key] = ''">
                    Retirer
                  </button>
                </div>
              </div>
            </div>

            <!-- Texte long : une description de référencement ne tient pas
                 sur une ligne, et sa longueur restante compte. -->
            <div v-else-if="field.type === 'text'" class="w-full sm:w-72 shrink-0">
              <textarea :id="field.key" v-model="draft[field.key]" rows="3" maxlength="160"
                class="w-full px-3 py-2 rounded-xl border border-gray-200 focus:border-orange-primary focus:outline-none text-sm resize-none"></textarea>
              <p class="mt-1 text-xs" :class="restant(field.key) < 20 ? 'text-orange-text' : 'text-gray-500'">
                {{ restant(field.key) }} caractères restants
              </p>
            </div>

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
import { getStorageUrl } from '@/config';
import BaseButton from '@/components/ui/BaseButton.vue';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';

const notify = useNotify();

const fields = ref([]);
const draft = ref({});
const original = ref({});
const loading = ref(true);
const saving = ref(false);
const uploading = ref(null);
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

const restant = (cle) => 160 - String(draft.value[cle] ?? '').length;

/**
 * Envoie le fichier tout de suite et garde son chemin dans le brouillon.
 *
 * Attendre l'enregistrement du formulaire obligerait à porter le fichier dans
 * la requête des réglages, qui est du JSON : deux formats dans un seul appel.
 */
async function uploadImage(cle, evenement) {
  const fichier = evenement.target.files?.[0];
  if (!fichier) return;

  uploading.value = cle;
  try {
    const donnees = new FormData();
    donnees.append('key', cle);
    donnees.append('image', fichier);

    const { data } = await api.post('/admin/settings/image', donnees, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    draft.value[cle] = data.data.path;
    notify.success('Image envoyée', "Enregistre les réglages pour l'appliquer.");
  } catch (err) {
    notify.error("L'envoi a échoué", apiErrorMessage(err, 'Vérifie le format et le poids du fichier.'));
  } finally {
    uploading.value = null;
    // Rejouer le meme fichier doit redeclencher l'evenement.
    evenement.target.value = '';
  }
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
