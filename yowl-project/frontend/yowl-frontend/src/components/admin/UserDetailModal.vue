<template>
  <BaseModal :is-open="isOpen" :title="detail?.user?.fullname || 'Fiche membre'" size="lg" @close="close">
    <div v-if="loading" class="space-y-4">
      <div class="h-24 rounded-2xl skeleton"></div>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div v-for="n in 4" :key="n" class="h-20 rounded-xl skeleton"></div>
      </div>
      <div class="h-32 rounded-xl skeleton"></div>
    </div>

    <div v-else-if="error" class="py-10 text-center">
      <Icon name="plug-circle-exclamation" :size="32" class="text-3xl text-gray-400" aria-hidden="true" />
      <p class="mt-4 text-sm text-gray-600">{{ error }}</p>
      <BaseButton class="mt-4" size="sm" variant="primary" @click="load">Réessayer</BaseButton>
    </div>

    <div v-else-if="detail" class="space-y-6">
      <!-- Identite -->
      <section class="flex items-start gap-4">
        <img v-if="detail.user.picture" :src="getStorageUrl(detail.user.picture)" alt="Photo de profil"
          class="w-20 h-20 rounded-2xl object-cover border border-gray-200" />
        <span v-else
          class="w-20 h-20 rounded-2xl bg-blue-night grid place-items-center text-white text-2xl font-poppins font-bold">
          {{ initials }}
        </span>

        <div class="min-w-0 flex-1">
          <p class="text-sm text-gray-500">@{{ detail.user.username }} — #{{ detail.user.id }}</p>
          <p class="mt-1 text-sm text-gray-700 break-all">{{ detail.user.email }}</p>
          <div class="mt-2 flex flex-wrap gap-1.5">
            <span v-for="role in detail.user.roles" :key="role"
              class="px-2.5 py-0.5 rounded-full bg-orange-50 text-orange-text text-xs font-medium">{{ role }}</span>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium"
              :class="detail.user.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">
              {{ detail.user.is_active ? 'Actif' : 'Banni' }}
            </span>
            <span v-if="detail.user.anonymized_at"
              class="px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-500 text-xs font-medium">Compte supprimé</span>
            <span v-if="!detail.user.email_verified_at"
              class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-medium">Email non vérifié</span>
          </div>
        </div>
      </section>

      <!-- Donnees -->
      <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-3 text-sm">
        <div>
          <dt class="text-gray-500 text-xs">Inscrit le</dt>
          <dd class="text-blue-night">{{ formatDate(detail.user.created_at) }}</dd>
        </div>
        <div>
          <dt class="text-gray-500 text-xs">Date de naissance</dt>
          <dd class="text-blue-night">{{ detail.user.birthdate ? formatDate(detail.user.birthdate) : 'non renseignée' }}</dd>
        </div>
        <div>
          <dt class="text-gray-500 text-xs">Email vérifié le</dt>
          <dd class="text-blue-night">
            {{ detail.user.email_verified_at ? formatDate(detail.user.email_verified_at) : 'jamais' }}
          </dd>
        </div>
      </dl>

      <!-- Chiffres -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div v-for="tile in tiles" :key="tile.label" class="rounded-xl border border-gray-200 px-3 py-3">
          <p class="text-xs text-gray-500">{{ tile.label }}</p>
          <p class="mt-0.5 font-poppins font-bold text-xl text-blue-night">{{ tile.value }}</p>
        </div>
      </div>

      <!-- Derniers avis -->
      <section>
        <h3 class="font-semibold text-blue-night mb-2">Derniers avis</h3>
        <ul v-if="detail.recent_reviews.length" class="divide-y divide-gray-50 border border-gray-100 rounded-xl">
          <li v-for="review in detail.recent_reviews" :key="review.id" class="px-3 py-2.5 flex items-start gap-3">
            <span class="shrink-0 mt-0.5 px-2 py-0.5 rounded bg-gray-100 text-[11px] text-gray-500">#{{ review.id }}</span>
            <p class="flex-1 min-w-0 text-sm text-gray-700">{{ truncate(review.content, 90) }}</p>
            <span class="shrink-0 text-xs text-gray-500">
              <Icon name="thumbs-up" class="mr-1" />{{ review.nb_like }}
              <Icon name="eye-slash" class="ml-2 text-amber-500" v-if="!review.is_published" title="Retiré du fil" />
            </span>
          </li>
        </ul>
        <p v-else class="text-sm text-gray-500">Aucun avis publié.</p>
      </section>

      <!-- Derniers commentaires -->
      <section>
        <h3 class="font-semibold text-blue-night mb-2">Derniers commentaires</h3>
        <ul v-if="detail.recent_comments.length" class="divide-y divide-gray-50 border border-gray-100 rounded-xl">
          <li v-for="comment in detail.recent_comments" :key="comment.id" class="px-3 py-2.5 flex items-start gap-3">
            <p class="flex-1 min-w-0 text-sm text-gray-700">{{ truncate(comment.content, 90) }}</p>
            <span class="shrink-0 text-xs text-gray-500">{{ formatDate(comment.created_at) }}</span>
          </li>
        </ul>
        <p v-else class="text-sm text-gray-500">Aucun commentaire.</p>
      </section>

      <!-- Edition -->
      <section class="rounded-xl border border-gray-200 p-4">
        <h3 class="font-semibold text-blue-night mb-3">Modifier la fiche</h3>
        <div class="grid sm:grid-cols-2 gap-3">
          <BaseInput v-model="form.fullname" label="Nom complet" />
          <BaseInput v-model="form.username" label="Pseudo" />
          <BaseInput v-model="form.email" label="Adresse email" type="email" />
          <BaseInput v-model="form.birthdate" label="Date de naissance" type="date" />
        </div>
        <div class="mt-3 flex justify-end">
          <BaseButton size="sm" variant="primary" :loading="saving" :disabled="!isDirty" @click="save">
            Enregistrer
          </BaseButton>
        </div>
      </section>

      <!-- Mot de passe -->
      <section class="rounded-xl border border-gray-200 p-4">
        <h3 class="font-semibold text-blue-night">Mot de passe</h3>
        <p class="mt-1 text-sm text-gray-500">
          Génère un nouveau mot de passe et déconnecte toutes les sessions du membre.
          Il s'affiche une seule fois, transmets-le autrement que par cette page.
        </p>

        <div v-if="newPassword" class="mt-3 flex items-center gap-2 p-3 rounded-xl bg-orange-50 border border-orange-200">
          <code class="flex-1 font-mono text-sm text-blue-night break-all">{{ newPassword }}</code>
          <BaseButton size="sm" variant="ghost" icon="copy" @click="copyPassword">Copier</BaseButton>
        </div>

        <div class="mt-3 flex justify-end">
          <BaseButton size="sm" variant="night" :loading="regenerating" @click="regenerate">
            Régénérer le mot de passe
          </BaseButton>
        </div>
      </section>
    </div>

    <template #footer>
      <div class="flex justify-end">
        <BaseButton variant="ghost" @click="close">Fermer</BaseButton>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import api from '@/services/apiService';
import BaseModal from '@/components/ui/BaseModal.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { getStorageUrl } from '@/config';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';
import { useConfirm } from '@/composables/useConfirm';

import Icon from '@/components/ui/Icon.vue';
const props = defineProps({
  isOpen: { type: Boolean, default: false },
  userId: { type: [Number, String], default: null },
});
const emit = defineEmits(['close', 'updated']);

const notify = useNotify();
const confirm = useConfirm();

const detail = ref(null);
const loading = ref(false);
const saving = ref(false);
const regenerating = ref(false);
const error = ref(null);
const newPassword = ref('');
const form = ref({ fullname: '', username: '', email: '', birthdate: '' });
const original = ref({});

const isDirty = computed(() => JSON.stringify(form.value) !== JSON.stringify(original.value));

const initials = computed(() => {
  const name = detail.value?.user?.username ?? '';
  return name.slice(0, 2).toUpperCase();
});

const tiles = computed(() => {
  const s = detail.value?.stats ?? {};
  return [
    { label: 'Avis', value: s.reviews ?? 0 },
    { label: 'Vues cumulées', value: (s.views ?? 0).toLocaleString('fr-FR') },
    { label: "J'aime reçus", value: s.likes_received ?? 0 },
    { label: 'Commentaires', value: s.comments_written ?? 0 },
    { label: 'Réactions données', value: s.reactions_given ?? 0 },
    { label: "Je n'aime pas reçus", value: s.dislikes_received ?? 0 },
    { label: 'Signalements émis', value: s.reports_filed ?? 0 },
    { label: 'Signalements reçus', value: s.reports_received ?? 0 },
  ];
});

const formatDate = (value) =>
  value ? new Date(value).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) : '';

const truncate = (text, max) =>
  !text ? '' : text.length > max ? text.slice(0, max).trimEnd() + '…' : text;

async function load() {
  if (!props.userId) return;
  loading.value = true;
  error.value = null;
  newPassword.value = '';
  try {
    const response = await api.get(`/admin/users/${props.userId}`);
    detail.value = response.data.data;
    form.value = {
      fullname: detail.value.user.fullname ?? '',
      username: detail.value.user.username ?? '',
      email: detail.value.user.email ?? '',
      birthdate: detail.value.user.birthdate ?? '',
    };
    original.value = { ...form.value };
  } catch (err) {
    error.value = apiErrorMessage(err, 'Impossible de charger cette fiche.');
  } finally {
    loading.value = false;
  }
}

async function save() {
  saving.value = true;
  try {
    const payload = { ...form.value };
    if (!payload.birthdate) payload.birthdate = null;
    await api.patch(`/admin/users/${props.userId}`, payload);
    original.value = { ...form.value };
    notify.success('Fiche mise à jour');
    emit('updated');
    await load();
  } catch (err) {
    notify.error('Modification impossible', apiErrorMessage(err, 'Vérifie les champs saisis.'));
  } finally {
    saving.value = false;
  }
}

async function regenerate() {
  const confirmed = await confirm({
    title: 'Régénérer le mot de passe ?',
    message: 'Le membre sera déconnecté de toutes ses sessions et devra utiliser le nouveau mot de passe.',
    confirmLabel: 'Régénérer',
    tone: 'danger',
  });
  if (!confirmed) return;

  regenerating.value = true;
  try {
    const response = await api.post(`/admin/users/${props.userId}/password`);
    newPassword.value = response.data.data.password;
    notify.success('Mot de passe régénéré', 'Il est affiché une seule fois.');
  } catch (err) {
    notify.error('Régénération impossible', apiErrorMessage(err));
  } finally {
    regenerating.value = false;
  }
}

async function copyPassword() {
  try {
    await navigator.clipboard.writeText(newPassword.value);
    notify.success('Copié');
  } catch {
    notify.warning('Copie impossible', 'Sélectionne le texte à la main.');
  }
}

const close = () => emit('close');

watch(() => [props.isOpen, props.userId], ([open]) => {
  if (open) load();
  else detail.value = null;
});
</script>
