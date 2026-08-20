<template>
  <AppShell>
    <div class="w-full px-4 xl:px-6 py-10 pb-24">
      <div class="max-w-xl">
        <div class="bg-white border border-gray-200 rounded-2xl p-7">
          <span class="w-12 h-12 rounded-2xl grid place-items-center text-xl"
            :class="etatClasse">
            <i :class="etatIcone" aria-hidden="true"></i>
          </span>

          <h1 class="mt-5 font-poppins font-bold text-xl text-blue-night">{{ titre }}</h1>
          <p class="mt-2 text-sm text-gray-600">{{ message }}</p>

          <div v-if="etat === 'pret'" class="mt-6">
            <BaseButton variant="primary" :loading="envoi" @click="autoriser">
              Autoriser l'extension
            </BaseButton>
            <p class="mt-3 text-xs text-gray-500">
              L'extension recevra un jeton d'accès révocable, pas ton mot de passe.
              Tu peux le retirer à tout moment depuis ses réglages.
            </p>
          </div>

          <div v-else-if="etat === 'fait'" class="mt-6">
            <BaseButton :tag="'router-link'" :to="'/feed'" variant="primary">
              Revenir au fil
            </BaseButton>
          </div>

          <div v-else-if="etat === 'anonyme'" class="mt-6">
            <BaseButton :tag="'router-link'" :to="loginPath" variant="primary">
              Me connecter
            </BaseButton>
          </div>
        </div>

        <!-- Ce que l'extension apporte, pour qui arrive ici sans l'avoir installée -->
        <div class="mt-6 bg-white border border-gray-200 rounded-2xl p-7">
          <h2 class="font-poppins font-bold text-blue-night">Ce qu'elle fait</h2>
          <ul class="mt-4 space-y-3.5">
            <li v-for="point in points" :key="point.titre" class="flex gap-3.5">
              <span class="w-9 h-9 shrink-0 rounded-xl bg-orange-50 grid place-items-center text-orange-text">
                <i :class="point.icone" aria-hidden="true"></i>
              </span>
              <span class="min-w-0">
                <span class="block text-sm font-medium text-blue-night">{{ point.titre }}</span>
                <span class="block text-sm text-gray-600">{{ point.texte }}</span>
              </span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import AppShell from '@/components/layouts/AppShell.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { useUserStore } from '@/stores/user';

const route = useRoute();
const userStore = useUserStore();

const etat = ref('chargement');
const envoi = ref(false);
const message = ref('');

const points = [
  {
    icone: 'fa-solid fa-bell',
    titre: 'Elle te dit quand la communauté a un avis',
    texte: "Une pastille sur l'icône signale que la page que tu lis est déjà discutée.",
  },
  {
    icone: 'fa-solid fa-pen-nib',
    titre: 'Elle te laisse répondre sur place',
    texte: 'Écris ton avis depuis le panneau, sans quitter la page ni ouvrir un onglet.',
  },
  {
    icone: 'fa-solid fa-quote-left',
    titre: 'Elle cite ce que tu sélectionnes',
    texte: 'Un passage surligné, un clic droit, et il ouvre ton avis.',
  },
];

const idExtension = computed(() => route.query.id || '');
const loginPath = computed(() => '/login?redirect=' + encodeURIComponent(route.fullPath));

const titre = computed(() => ({
  chargement: 'Un instant',
  anonyme: 'Connecte-toi d\'abord',
  pret: 'Connecter l\'extension',
  fait: 'Extension connectée',
  echec: 'La connexion a échoué',
}[etat.value] ?? ''));

const etatClasse = computed(() => ({
  fait: 'bg-emerald-50 text-emerald-600',
  echec: 'bg-red-50 text-red-600',
}[etat.value] ?? 'bg-orange-50 text-orange-text'));

const etatIcone = computed(() => ({
  fait: 'fa-solid fa-check',
  echec: 'fa-solid fa-triangle-exclamation',
}[etat.value] ?? 'fa-solid fa-puzzle-piece'));

/**
 * Remet le jeton à l'extension, par le canal prévu pour ça.
 *
 * L'extension déclare ce domaine dans externally_connectable, ce qui laisse
 * une page de ce site lui parler. C'est le navigateur qui garantit l'origine :
 * aucun autre site ne peut se faire passer pour celui-ci.
 */
async function autoriser() {
  envoi.value = true;
  try {
    const reponse = await new Promise((resoudre, rejeter) => {
      window.chrome?.runtime?.sendMessage(
        idExtension.value,
        {
          type: 'yowl-connect',
          token: userStore.token,
          user: { id: userStore.user?.id, username: userStore.user?.username },
        },
        (retour) => {
          const souci = window.chrome.runtime.lastError;
          if (souci) rejeter(new Error(souci.message));
          else resoudre(retour);
        }
      );
    });

    if (reponse?.ok) {
      etat.value = 'fait';
      message.value = 'C\'est bon. Referme cet onglet et navigue : la pastille apparaîtra sur les pages dont on parle ici.';
    } else {
      throw new Error('L\'extension a refusé la connexion.');
    }
  } catch (erreur) {
    etat.value = 'echec';
    message.value = erreur.message
      + " Vérifie que l'extension YOWL est bien installée et activée, puis reviens par son bouton « Me connecter ».";
  } finally {
    envoi.value = false;
  }
}

onMounted(() => {
  if (!userStore.isAuthenticated) {
    etat.value = 'anonyme';
    message.value = "L'extension a besoin de ton compte pour savoir quoi t'afficher. Connecte-toi, tu reviendras ici.";
    return;
  }

  if (!idExtension.value) {
    etat.value = 'echec';
    message.value = "Cette page s'ouvre depuis l'extension, qui indique son identifiant. Passe par son bouton « Me connecter ».";
    return;
  }

  if (!window.chrome?.runtime?.sendMessage) {
    etat.value = 'echec';
    message.value = "Ce navigateur ne permet pas à une page de parler à une extension. Utilise Chrome, Edge ou Brave.";
    return;
  }

  etat.value = 'pret';
  message.value = 'Tu es connecté en tant que ' + (userStore.user?.username ?? 'membre')
    + ". Autorise l'extension à utiliser ce compte.";
});
</script>
