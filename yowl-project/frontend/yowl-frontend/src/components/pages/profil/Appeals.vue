<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <AppShell>
    <div class="w-full px-4 xl:px-6 py-6 pb-24">
      <ProfileHeader />

      <div class="mt-6 max-w-3xl">
        <h1 class="text-xl font-semibold text-blue-night">Mes contestations</h1>
        <p class="mt-1.5 text-sm text-gray-600">
          Quand un de tes textes est masqué, tu peux demander un réexamen. Un
          modérateur relit et te répond par écrit.
        </p>

        <div v-if="store.loading" class="mt-6 space-y-4">
          <div v-for="n in 2" :key="n" class="h-40 rounded-2xl skeleton"></div>
        </div>

        <div v-else-if="!store.mine.length"
          class="mt-6 flex flex-col items-center text-center bg-white border border-gray-200 rounded-2xl py-16 px-4">
          <i class="fa-regular fa-comments text-5xl text-gray-400" aria-hidden="true"></i>
          <h2 class="mt-5 text-lg font-semibold text-gray-800">Aucune contestation</h2>
          <p class="mt-2 text-gray-600 text-sm max-w-md">
            C'est plutôt bon signe : rien de ce que tu as écrit n'a été masqué.
          </p>
        </div>

        <ul v-else class="mt-6 space-y-4 stagger">
          <li v-for="appeal in store.mine" :key="appeal.id"
            class="bg-white border border-gray-200 rounded-2xl p-5 animate-fade-in-up">
            <div class="flex items-start justify-between gap-4">
              <div>
                <span class="text-xs font-medium uppercase tracking-wide"
                  :class="statusStyle(appeal.status).text">
                  {{ statusStyle(appeal.status).label }}
                </span>
                <p class="mt-1 text-xs text-gray-500">
                  Déposée le {{ formatDate(appeal.created_at) }}
                </p>
              </div>
              <span class="shrink-0 rounded-full px-3 py-1 text-xs font-medium"
                :class="statusStyle(appeal.status).badge">
                {{ statusStyle(appeal.status).short }}
              </span>
            </div>

            <p class="mt-4 text-sm text-gray-700 whitespace-pre-line">{{ appeal.message }}</p>

            <div v-if="appeal.response"
              class="mt-4 rounded-xl border-l-4 border-orange-primary bg-orange-50/60 p-4">
              <p class="text-xs font-semibold text-orange-text uppercase tracking-wide">
                Réponse de la modération
              </p>
              <p class="mt-1.5 text-sm text-gray-700 whitespace-pre-line">{{ appeal.response }}</p>
            </div>

            <p v-else class="mt-4 text-sm text-gray-500 flex items-center gap-2">
              <i class="fa-regular fa-clock" aria-hidden="true"></i>
              En attente de relecture. Les contestations sont traitées dans l'ordre d'arrivée.
            </p>
          </li>
        </ul>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { onMounted } from 'vue';
import AppShell from '@/components/layouts/AppShell.vue';
import ProfileHeader from '@/components/layouts/ProfileHeader.vue';
import { useAppealStore } from '@/stores/appeal';

const store = useAppealStore();

/**
 * Trois etats, trois lectures. « Maintenue » n'est pas un echec de procedure :
 * la demande a bien ete examinee, et le libelle doit le dire.
 */
function statusStyle(status) {
  return {
    pending: {
      label: 'En cours d\'examen', short: 'En attente',
      text: 'text-gray-500', badge: 'bg-gray-100 text-gray-700',
    },
    granted: {
      label: 'Contestation acceptée', short: 'Acceptée',
      text: 'text-emerald-600', badge: 'bg-emerald-50 text-emerald-700',
    },
    upheld: {
      label: 'Décision maintenue', short: 'Maintenue',
      text: 'text-gray-600', badge: 'bg-amber-50 text-amber-700',
    },
  }[status] ?? { label: status, short: status, text: 'text-gray-500', badge: 'bg-gray-100 text-gray-700' };
}

function formatDate(value) {
  return new Date(value).toLocaleDateString('fr-FR', {
    day: 'numeric', month: 'long', year: 'numeric',
  });
}

onMounted(() => store.loadMine());
</script>
