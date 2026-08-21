<template>
  <section class="space-y-4">
    <!-- Creation d'un role -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
      <h2 class="font-semibold text-blue-night">Créer un rôle</h2>
      <p class="mt-1 text-sm text-gray-500">
        En minuscules, sans espace. Les droits cochés sont attribués à sa création.
      </p>

      <form class="mt-4 flex flex-col sm:flex-row gap-3" @submit.prevent="createRole">
        <input v-model="newRole" type="text" placeholder="moderateur"
          class="flex-1 px-3 py-2 rounded-xl border border-gray-200 focus:border-orange-primary focus:outline-none text-sm" />
        <BaseButton variant="primary" type="submit" :loading="creating" :disabled="!newRole.trim()">
          Créer le rôle
        </BaseButton>
      </form>
    </div>

    <!-- Creation d'un droit -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
      <h2 class="font-semibold text-blue-night">Créer un droit</h2>
      <p class="mt-1 text-sm text-gray-500">
        Un droit est un nom que le code interroge, par exemple <code class="text-xs">moderate.reports</code>.
      </p>

      <form class="mt-4 flex flex-col sm:flex-row gap-3" @submit.prevent="createPermission">
        <input v-model="newPermission" type="text" placeholder="moderate.reports"
          class="flex-1 px-3 py-2 rounded-xl border border-gray-200 focus:border-orange-primary focus:outline-none text-sm" />
        <BaseButton variant="night" type="submit" :loading="creatingPermission" :disabled="!newPermission.trim()">
          Créer le droit
        </BaseButton>
      </form>
    </div>

    <!-- Liste des roles -->
    <div v-if="loading" class="space-y-3">
      <div v-for="n in 3" :key="n" class="h-32 rounded-2xl skeleton"></div>
    </div>

    <div v-else-if="error" class="bg-white rounded-2xl border border-gray-200 p-8 text-center">
      <Icon name="plug-circle-exclamation" :size="32" class="text-3xl text-gray-400" aria-hidden="true" />
      <p class="mt-4 text-sm text-gray-600">{{ error }}</p>
      <BaseButton class="mt-4" size="sm" variant="primary" @click="load">Réessayer</BaseButton>
    </div>

    <article v-for="role in roles" v-else :key="role.id"
      class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
      <header class="flex items-center justify-between gap-3">
        <div>
          <h3 class="font-semibold text-blue-night flex items-center gap-2">
            {{ role.name }}
            <span v-if="role.protected"
              class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 text-[11px] font-medium">
              nécessaire
            </span>
          </h3>
          <p class="text-sm text-gray-500 mt-0.5">
            {{ role.users_count }} membre<span v-if="role.users_count > 1">s</span>
          </p>
        </div>
        <button v-if="!role.protected" type="button"
          class="w-9 h-9 rounded-full grid place-items-center text-gray-500 hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer"
          aria-label="Supprimer le rôle" @click="removeRole(role)">
          <Icon name="trash" />
        </button>
      </header>

      <div v-if="permissions.length" class="mt-4 flex flex-wrap gap-2">
        <label v-for="permission in permissions" :key="permission"
          class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border text-sm cursor-pointer transition-colors"
          :class="role.permissions.includes(permission)
            ? 'border-orange-primary bg-orange-50 text-orange-text'
            : 'border-gray-200 text-gray-500 hover:border-gray-300'">
          <input type="checkbox" class="sr-only" :checked="role.permissions.includes(permission)"
            @change="togglePermission(role, permission)" />
          <Icon name="check" />
          {{ permission }}
        </label>
      </div>
      <p v-else class="mt-4 text-sm text-gray-500">
        Aucun droit défini pour le moment.
      </p>
    </article>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '@/services/apiService';
import BaseButton from '@/components/ui/BaseButton.vue';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';
import { useConfirm } from '@/composables/useConfirm';

import Icon from '@/components/ui/Icon.vue';
const notify = useNotify();
const confirm = useConfirm();

const roles = ref([]);
const permissions = ref([]);
const loading = ref(true);
const error = ref(null);
const newRole = ref('');
const newPermission = ref('');
const creating = ref(false);
const creatingPermission = ref(false);

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get('/admin/roles');
    roles.value = response.data.data.roles;
    permissions.value = response.data.data.permissions;
  } catch (err) {
    error.value = apiErrorMessage(err, 'Impossible de charger les rôles.');
  } finally {
    loading.value = false;
  }
}

async function createRole() {
  creating.value = true;
  try {
    await api.post('/admin/roles', { name: newRole.value.trim(), permissions: [] });
    newRole.value = '';
    notify.success('Rôle créé');
    await load();
  } catch (err) {
    notify.error('Création impossible', apiErrorMessage(err, 'Vérifie le nom saisi.'));
  } finally {
    creating.value = false;
  }
}

async function createPermission() {
  creatingPermission.value = true;
  try {
    await api.post('/admin/permissions', { name: newPermission.value.trim() });
    newPermission.value = '';
    notify.success('Droit créé');
    await load();
  } catch (err) {
    notify.error('Création impossible', apiErrorMessage(err, 'Vérifie le nom saisi.'));
  } finally {
    creatingPermission.value = false;
  }
}

async function togglePermission(role, permission) {
  const next = role.permissions.includes(permission)
    ? role.permissions.filter((name) => name !== permission)
    : [...role.permissions, permission];

  // Optimiste : la case suit le clic, et revient si le serveur refuse.
  const previous = role.permissions;
  role.permissions = next;
  try {
    await api.patch(`/admin/roles/${role.id}`, { permissions: next });
  } catch (err) {
    role.permissions = previous;
    notify.error('Modification impossible', apiErrorMessage(err));
  }
}

async function removeRole(role) {
  const confirmed = await confirm({
    title: `Supprimer le rôle ${role.name} ?`,
    message: 'Les membres qui le portent le perdront.',
    confirmLabel: 'Supprimer',
    tone: 'danger',
  });
  if (!confirmed) return;

  try {
    await api.delete(`/admin/roles/${role.id}`);
    notify.success('Rôle supprimé');
    await load();
  } catch (err) {
    notify.error('Suppression impossible', apiErrorMessage(err));
  }
}

onMounted(load);
</script>
