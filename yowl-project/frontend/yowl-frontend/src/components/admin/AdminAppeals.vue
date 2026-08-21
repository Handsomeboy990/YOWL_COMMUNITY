<template>
  <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <header class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="font-poppins font-bold text-blue-night">Contestations</h2>
        <p class="mt-0.5 text-sm text-gray-600">
          Un membre dont un texte a été masqué demande un réexamen. La réponse
          écrite lui est envoyée, quelle que soit la décision.
        </p>
      </div>
      <div class="flex gap-2">
        <button v-for="filtre in filtres" :key="filtre.value" type="button"
          class="px-3 py-1.5 rounded-full text-sm font-medium transition-colors cursor-pointer"
          :class="statut === filtre.value
            ? 'bg-orange-primary text-white'
            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          @click="charger(filtre.value)">
          {{ filtre.label }}
        </button>
      </div>
    </header>

    <div v-if="store.loading" class="p-5 space-y-4">
      <div v-for="n in 3" :key="n" class="h-32 rounded-xl skeleton"></div>
    </div>

    <div v-else-if="!store.queue.length" class="px-5 py-16 text-center">
      <Icon name="circle-check" :size="40" class="text-4xl text-gray-300" aria-hidden="true" />
      <p class="mt-4 text-gray-600">
        {{ statut === 'pending' ? 'Aucune contestation en attente.' : 'Rien à afficher pour ce filtre.' }}
      </p>
    </div>

    <ul v-else class="divide-y divide-gray-100">
      <li v-for="appeal in store.queue" :key="appeal.id" class="p-5">
        <div class="flex items-start gap-3">
          <img :src="getStorageUrl(appeal.user?.picture)" alt=""
            class="w-10 h-10 rounded-full object-cover shrink-0" />
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
              <span class="font-medium text-blue-night">{{ appeal.user?.username ?? 'Compte supprimé' }}</span>
              <span class="text-xs text-gray-500">{{ formatDate(appeal.created_at) }}</span>
              <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="badge(appeal.status)">
                {{ libelle(appeal.status) }}
              </span>
            </div>

            <p class="mt-2.5 text-sm text-gray-700 whitespace-pre-line">{{ appeal.message }}</p>

            <!-- Le contenu concerne, pour decider sans changer d'onglet -->
            <div v-if="appeal.appealable"
              class="mt-3 rounded-xl bg-gray-50 border border-gray-100 p-3">
              <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Contenu concerné</p>
              <p class="mt-1 text-sm text-gray-700 line-clamp-4">
                {{ appeal.appealable.content }}
              </p>
            </div>

            <div v-if="appeal.response"
              class="mt-3 rounded-xl border-l-4 border-gray-300 bg-gray-50 p-3">
              <p class="text-xs font-medium text-gray-500">
                Répondu par {{ appeal.handler?.username ?? 'un modérateur' }}
              </p>
              <p class="mt-1 text-sm text-gray-700 whitespace-pre-line">{{ appeal.response }}</p>
            </div>

            <div v-else-if="ouvert === appeal.id" class="mt-4 space-y-3">
              <BaseTextarea v-model="reponse" label="Ta réponse au membre" :rows="4" :maxlength="2000"
                :error="erreur"
                placeholder="Explique ce que tu as vérifié et ce que tu décides. Dix caractères minimum." />
              <div class="flex flex-wrap gap-2">
                <BaseButton variant="primary" size="sm" :loading="store.submitting"
                  @click="repondre(appeal.id, 'granted')">
                  Accepter et remettre en ligne
                </BaseButton>
                <BaseButton variant="outline" size="sm" :loading="store.submitting"
                  @click="repondre(appeal.id, 'upheld')">
                  Maintenir la décision
                </BaseButton>
                <BaseButton variant="ghost" size="sm" @click="ouvert = null">Annuler</BaseButton>
              </div>
            </div>

            <BaseButton v-else class="mt-3" variant="night" size="sm" @click="ouvrir(appeal.id)">
              Répondre
            </BaseButton>
          </div>
        </div>
      </li>
    </ul>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseTextarea from '@/components/ui/BaseTextarea.vue';
import { getStorageUrl } from '@/config';
import { useAppealStore } from '@/stores/appeal';

import Icon from '@/components/ui/Icon.vue';
const store = useAppealStore();
const statut = ref('pending');
const ouvert = ref(null);
const reponse = ref('');
const erreur = ref('');

const filtres = [
  { value: 'pending', label: 'En attente' },
  { value: 'granted', label: 'Acceptées' },
  { value: 'upheld', label: 'Maintenues' },
  { value: '', label: 'Toutes' },
];

function charger(valeur) {
  statut.value = valeur;
  ouvert.value = null;
  store.loadQueue(valeur);
}

function ouvrir(id) {
  ouvert.value = id;
  reponse.value = '';
  erreur.value = '';
}

async function repondre(id, decision) {
  if (reponse.value.trim().length < 10) {
    erreur.value = 'Une décision sans motif écrit est exactement ce qui fait partir les gens.';
    return;
  }

  erreur.value = '';
  const envoye = await store.resolve(id, decision, reponse.value.trim());
  if (envoye) {
    ouvert.value = null;
  }
}

function badge(status) {
  return {
    pending: 'bg-gray-100 text-gray-700',
    granted: 'bg-emerald-50 text-emerald-700',
    upheld: 'bg-amber-50 text-amber-700',
  }[status] ?? 'bg-gray-100 text-gray-700';
}

function libelle(status) {
  return { pending: 'En attente', granted: 'Acceptée', upheld: 'Maintenue' }[status] ?? status;
}

function formatDate(value) {
  return new Date(value).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
}

onMounted(() => store.loadQueue('pending'));
</script>
