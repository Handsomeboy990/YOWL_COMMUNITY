<template>
  <section class="mt-8 bg-white border border-gray-200 rounded-2xl p-5">
    <div class="flex items-start gap-4">
      <span class="w-10 h-10 shrink-0 rounded-xl bg-blue-night/5 grid place-items-center text-blue-night">
        <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i>
      </span>
      <div class="min-w-0 flex-1">
        <h2 class="font-poppins font-bold text-blue-night">Mes données</h2>
        <p class="mt-1 text-sm text-gray-600">
          Tout ce que la plateforme conserve à ton sujet, dans un seul fichier
          que tu peux garder ou emporter ailleurs.
        </p>

        <dl v-if="summary" class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-3">
          <div v-for="row in rows" :key="row.label"
            class="rounded-xl bg-gray-50 px-3 py-2.5">
            <dt class="text-xs text-gray-500">{{ row.label }}</dt>
            <dd class="mt-0.5 font-poppins font-bold text-blue-night tabular-nums">{{ row.value }}</dd>
          </div>
        </dl>

        <div v-else-if="loading" class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-3">
          <div v-for="n in 6" :key="n" class="h-14 rounded-xl skeleton"></div>
        </div>

        <BaseButton class="mt-5" variant="night" size="sm" icon="fa-solid fa-download"
          :loading="downloading" @click="download">
          Télécharger mes données
        </BaseButton>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import api from '@/services/apiService';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';

const summary = ref(null);
const loading = ref(true);
const downloading = ref(false);
const notify = useNotify();

const rows = computed(() => [
  { label: 'Avis', value: summary.value.avis },
  { label: 'Commentaires', value: summary.value.commentaires },
  { label: 'Réactions', value: summary.value.reactions },
  { label: 'Abonnements', value: summary.value.abonnements },
  { label: 'Enregistrements', value: summary.value.enregistrements },
  { label: 'Notifications', value: summary.value.notifications },
]);

async function load() {
  try {
    const response = await api.get('/mes-donnees');
    summary.value = response.data.data;
  } catch {
    // Le compte reste exportable meme si le compte-rendu manque.
  } finally {
    loading.value = false;
  }
}

/**
 * Recupere le fichier puis le remet au navigateur.
 *
 * Un lien direct vers l'URL n'emporterait pas le jeton d'authentification,
 * qui vit dans un en-tete et non dans un cookie.
 */
async function download() {
  downloading.value = true;
  try {
    const response = await api.get('/mes-donnees/export', { responseType: 'blob' });
    const url = URL.createObjectURL(response.data);
    const lien = document.createElement('a');
    lien.href = url;
    lien.download = nomFichier(response.headers['content-disposition']);
    document.body.appendChild(lien);
    lien.click();
    lien.remove();
    URL.revokeObjectURL(url);
    notify.success('Export téléchargé', 'Le fichier est dans tes téléchargements.');
  } catch (err) {
    notify.error(apiErrorMessage(err, "L'export n'a pas pu être préparé."));
  } finally {
    downloading.value = false;
  }
}

function nomFichier(disposition) {
  const trouve = /filename="([^"]+)"/.exec(disposition || '');
  return trouve ? trouve[1] : 'yowl-mes-donnees.json';
}

onMounted(load);
</script>
