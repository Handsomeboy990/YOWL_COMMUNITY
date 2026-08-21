<template>
  <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <header class="px-5 py-4 border-b border-gray-100">
      <h2 class="font-semibold text-blue-night">Journal d'administration</h2>
      <p class="mt-1 text-sm text-gray-500">
        Qui a changé quoi, et quand. Écrit automatiquement, non modifiable.
      </p>
    </header>

    <div v-if="loading" class="p-5 space-y-3">
      <div v-for="n in 6" :key="n" class="h-12 rounded-xl skeleton"></div>
    </div>

    <div v-else-if="error" class="p-8 text-center">
      <Icon name="plug-circle-exclamation" :size="32" class="text-3xl text-gray-400" aria-hidden="true" />
      <p class="mt-4 text-sm text-gray-600">{{ error }}</p>
      <BaseButton class="mt-4" size="sm" variant="primary" @click="load()">Réessayer</BaseButton>
    </div>

    <div v-else-if="!entries.length" class="p-12 text-center">
      <Icon name="clipboard" :size="40" class="text-4xl text-gray-400" aria-hidden="true" />
      <p class="mt-4 text-sm text-gray-600">Aucune action enregistrée pour le moment.</p>
    </div>

    <ul v-else class="divide-y divide-gray-50">
      <li v-for="entry in entries" :key="entry.id" class="px-5 py-4 flex items-start gap-4">
        <span class="w-9 h-9 shrink-0 rounded-xl grid place-items-center bg-gray-100 text-gray-500">
          <Icon :name="iconFor(entry.action)" />
        </span>
        <div class="min-w-0 flex-1">
          <p class="text-sm text-blue-night">
            <span class="font-medium">{{ entry.user?.username ?? 'compte supprimé' }}</span>
            <span class="text-gray-500"> — {{ labelFor(entry.action) }}</span>
          </p>
          <p v-if="entry.context" class="mt-1 text-xs text-gray-500 font-mono break-all">
            {{ summarise(entry.context) }}
          </p>
        </div>
        <time class="text-xs text-gray-500 whitespace-nowrap shrink-0">{{ formatDate(entry.created_at) }}</time>
      </li>
    </ul>

    <div v-if="pagination.last_page > 1" class="px-5 py-4 border-t border-gray-100">
      <Pagination :pagination="pagination" @changePage="load" />
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '@/services/apiService';
import BaseButton from '@/components/ui/BaseButton.vue';
import Pagination from '@/components/layouts/Pagination.vue';
import { apiErrorMessage } from '@/composables/useNotify';

import Icon from '@/components/ui/Icon.vue';
const entries = ref([]);
const pagination = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(true);
const error = ref(null);

const LABELS = {
  'settings.updated': 'a modifié les réglages',
  'role.created': 'a créé un rôle',
  'role.deleted': 'a supprimé un rôle',
  'role.permissions_updated': 'a modifié les droits d\'un rôle',
  'permission.created': 'a créé un droit',
  'user.created': 'a créé un membre',
  'user.roles_updated': 'a modifié les rôles d\'un membre',
};

const ICONS = {
  'settings.updated': 'sliders',
  'role.created': 'user-shield',
  'role.deleted': 'user-slash',
  'role.permissions_updated': 'key',
  'permission.created': 'key',
  'user.created': 'user-plus',
  'user.roles_updated': 'user-gear',
};

const labelFor = (action) => LABELS[action] ?? action;
const iconFor = (action) => ICONS[action] ?? 'circle-info';

const formatDate = (value) =>
  new Date(value).toLocaleString('fr-FR', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });

// Le contexte varie selon l'action : un resume court vaut mieux qu'un JSON brut.
const summarise = (context) =>
  Object.entries(context)
    .map(([key, value]) => `${key}: ${JSON.stringify(value)}`)
    .join('  ');

async function load(page = 1) {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get('/admin/audit-log', { params: { page } });
    const payload = response.data.data;
    entries.value = payload.data ?? [];
    pagination.value = {
      current_page: payload.current_page,
      last_page: payload.last_page,
      total: payload.total,
    };
  } catch (err) {
    error.value = apiErrorMessage(err, 'Impossible de charger le journal.');
  } finally {
    loading.value = false;
  }
}

onMounted(() => load());
</script>
