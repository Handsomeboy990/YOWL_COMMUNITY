<template>
  <section class="space-y-4">
    <!-- Choix de la page -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
      <header class="px-5 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-blue-night">Pages du site</h2>
        <p class="mt-1 text-sm text-gray-500">
          Le texte est enregistré en base : le modifier ici change la page en ligne, sans déploiement.
        </p>
      </header>

      <ul v-if="!loadingList" class="divide-y divide-gray-50">
        <li v-for="page in pages" :key="page.slug">
          <button type="button"
            class="w-full flex items-center justify-between gap-3 px-5 py-3 text-left transition-colors cursor-pointer"
            :class="current === page.slug ? 'bg-orange-50' : 'hover:bg-gray-50'"
            @click="open(page.slug)">
            <span class="min-w-0">
              <span class="block font-medium text-blue-night truncate">{{ page.title }}</span>
              <span class="block text-xs text-gray-500">/{{ page.slug }}</span>
            </span>
            <span class="flex items-center gap-2 shrink-0">
              <span v-if="page.has_draft"
                class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[11px] font-medium">brouillon</span>
              <span class="px-2 py-0.5 rounded-full text-[11px] font-medium"
                :class="page.published ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600'">
                {{ page.published ? 'en ligne' : 'non publiée' }}
              </span>
            </span>
          </button>
        </li>
      </ul>
      <div v-else class="p-5 space-y-2">
        <div v-for="n in 4" :key="n" class="h-12 rounded-xl skeleton"></div>
      </div>
    </div>

    <!-- Éditeur -->
    <div v-if="current" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
      <div v-if="loadingPage" class="space-y-3">
        <div class="h-10 rounded-xl skeleton"></div>
        <div class="h-96 rounded-xl skeleton"></div>
      </div>

      <template v-else>
        <BaseInput v-model="form.title" label="Titre de la page" />

        <div class="mt-4">
          <p class="text-sm font-medium text-blue-night mb-2">Contenu</p>
          <RichTextEditor v-model="form.content" />
        </div>

        <div v-if="hasDraft" class="mt-4 flex items-start gap-3 p-3 rounded-xl bg-amber-50 border border-amber-200">
          <Icon name="circle-info" class="text-amber-600 mt-0.5" aria-hidden="true" />
          <p class="text-sm text-gray-700 flex-1">
            Un brouillon non publié existe. Les visiteurs voient toujours la version précédente.
          </p>
          <button type="button" class="text-sm text-amber-700 underline cursor-pointer" @click="discard">
            Abandonner le brouillon
          </button>
        </div>

        <div class="mt-5 flex flex-col sm:flex-row sm:justify-end gap-3">
          <BaseButton variant="ghost" :loading="saving === 'draft'" @click="save('draft')">
            Enregistrer en brouillon
          </BaseButton>
          <BaseButton variant="primary" :loading="saving === 'publish'" @click="save('publish')">
            Publier
          </BaseButton>
        </div>
      </template>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue';
import api from '@/services/apiService';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import RichTextEditor from '@/components/admin/RichTextEditor.vue';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';
import { useConfirm } from '@/composables/useConfirm';

import Icon from '@/components/ui/Icon.vue';
const notify = useNotify();
const confirm = useConfirm();

const pages = ref([]);
const current = ref(null);
const form = ref({ title: '', content: '' });
const hasDraft = ref(false);
const loadingList = ref(true);
const loadingPage = ref(false);
const saving = ref(null);

async function loadList() {
  loadingList.value = true;
  try {
    const response = await api.get('/admin/legal');
    pages.value = response.data.data;
  } catch (err) {
    notify.error('Impossible de charger les pages', apiErrorMessage(err));
  } finally {
    loadingList.value = false;
  }
}

async function open(slug) {
  current.value = slug;
  loadingPage.value = true;
  try {
    const response = await api.get(`/admin/legal/${slug}`);
    form.value = { title: response.data.data.title, content: response.data.data.content };
    hasDraft.value = response.data.data.has_draft;
  } catch (err) {
    notify.error('Impossible de charger cette page', apiErrorMessage(err));
  } finally {
    loadingPage.value = false;
  }
}

async function save(action) {
  saving.value = action;
  try {
    const response = await api.put(`/admin/legal/${current.value}`, {
      title: form.value.title,
      content: form.value.content,
      action,
    });
    hasDraft.value = response.data.data.has_draft;
    notify.success(response.data.message);
    await loadList();
  } catch (err) {
    notify.error("L'enregistrement a échoué", apiErrorMessage(err));
  } finally {
    saving.value = null;
  }
}

async function discard() {
  const confirmed = await confirm({
    title: 'Abandonner le brouillon ?',
    message: 'Les modifications non publiées seront perdues.',
    confirmLabel: 'Abandonner',
    tone: 'danger',
  });
  if (!confirmed) return;

  await api.delete(`/admin/legal/${current.value}/draft`);
  await open(current.value);
  await loadList();
  notify.success('Brouillon abandonné');
}

loadList();
</script>
