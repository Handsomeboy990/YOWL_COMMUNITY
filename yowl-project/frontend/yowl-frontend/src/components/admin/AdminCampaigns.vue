<template>
  <section class="space-y-5">
    <!-- ===== En-tête ===== -->
    <header class="bg-white rounded-2xl border border-gray-200 shadow-sm px-5 py-4 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="font-poppins font-bold text-blue-night">Campagnes email</h2>
        <p class="mt-0.5 text-sm text-gray-600">
          Écrire à la communauté, à une partie d'elle, ou à quelques membres.
          <span v-if="options">{{ options.reachable }} membre(s) joignable(s),
            {{ options.opted_out }} désinscrit(s).</span>
        </p>
      </div>
      <BaseButton v-if="!composer" variant="primary" size="sm" icon="fa-solid fa-pen-nib"
        @click="nouvelle">
        Nouvelle campagne
      </BaseButton>
      <BaseButton v-else variant="ghost" size="sm" @click="composer = false">
        Revenir à la liste
      </BaseButton>
    </header>

    <!-- ===== Composition ===== -->
    <form v-if="composer" class="bg-white rounded-2xl border border-gray-200 shadow-sm"
      @submit.prevent="enregistrer">

      <!-- 1. L'objet du message décide du gabarit -->
      <fieldset class="p-5 border-b border-gray-100">
        <legend class="font-poppins font-semibold text-blue-night mb-1">1. Pourquoi tu écris</legend>
        <p class="text-sm text-gray-600 mb-4">
          Le gabarit change avec l'objet. Il n'est qu'un point de départ, tout reste modifiable.
        </p>
        <div class="grid gap-2.5 sm:grid-cols-2 xl:grid-cols-3">
          <button v-for="(label, cle) in options?.purposes ?? {}" :key="cle" type="button"
            class="text-left px-4 py-3 rounded-xl border-2 transition-colors cursor-pointer"
            :class="form.purpose === cle
              ? 'border-orange-primary bg-orange-50'
              : 'border-gray-200 hover:border-gray-300'"
            @click="choisirGabarit(cle)">
            <span class="block text-sm font-medium text-blue-night">{{ label }}</span>
            <span class="block mt-0.5 text-xs text-gray-500 line-clamp-1">
              {{ options.templates[cle]?.subject }}
            </span>
          </button>
        </div>
      </fieldset>

      <!-- 2. Le message -->
      <fieldset class="p-5 border-b border-gray-100 space-y-4">
        <legend class="font-poppins font-semibold text-blue-night mb-1">2. Le message</legend>

        <BaseInput v-model="form.subject" label="Objet de l'email" :maxlength="150"
          hint="Ce que le membre lit dans sa boîte avant d'ouvrir." required />

        <div>
          <span class="block text-sm font-medium text-blue-night mb-1.5">Contenu</span>
          <RichTextEditor v-model="form.body" />
        </div>

        <div class="rounded-xl bg-gray-50 border border-gray-200 p-3.5">
          <p class="text-xs font-medium text-gray-600 uppercase tracking-wide">
            Champs remplacés à l'envoi
          </p>
          <ul class="mt-2 flex flex-wrap gap-2">
            <li v-for="(desc, jeton) in options?.placeholders ?? {}" :key="jeton">
              <button type="button" :title="desc"
                class="px-2.5 py-1 rounded-lg bg-white border border-gray-200 font-mono text-xs text-blue-night hover:border-orange-primary hover:text-orange-text transition-colors cursor-pointer"
                @click="copier(jeton)">
                {{ jeton }}
              </button>
            </li>
          </ul>
        </div>
      </fieldset>

      <!-- 3. À qui -->
      <fieldset class="p-5 border-b border-gray-100 space-y-4">
        <legend class="font-poppins font-semibold text-blue-night mb-1">3. À qui</legend>

        <div class="flex flex-wrap gap-2">
          <button v-for="choix in publics" :key="choix.value" type="button"
            class="px-3.5 py-2 rounded-xl text-sm font-medium transition-colors cursor-pointer"
            :class="form.audience === choix.value
              ? 'bg-orange-primary text-white'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
            @click="changerPublic(choix.value)">
            {{ choix.label }}
          </button>
        </div>

        <select v-if="form.audience === 'segment'" v-model="form.segment"
          class="w-full sm:w-auto px-3 py-2.5 rounded-xl border border-gray-200 focus:border-orange-primary focus:outline-none text-sm"
          @change="compterAudience">
          <option value="">Choisis un segment</option>
          <option v-for="(label, cle) in options?.segments ?? {}" :key="cle" :value="cle">
            {{ label }}
          </option>
        </select>

        <div v-if="form.audience === 'selected'">
          <BaseInput v-model="recherche" label="Chercher un membre" icon="fa-solid fa-magnifying-glass"
            placeholder="Pseudo" @update:modelValue="chercher" />

          <ul v-if="resultats.length" class="mt-2 space-y-1">
            <li v-for="membre in resultats" :key="membre.id">
              <button type="button"
                class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-left text-sm hover:bg-gray-100 transition-colors cursor-pointer"
                @click="ajouter(membre)">
                <img :src="getStorageUrl(membre.picture)" alt="" class="w-7 h-7 rounded-full object-cover" />
                <span class="text-blue-night">{{ membre.username }}</span>
              </button>
            </li>
          </ul>

          <ul v-if="selection.length" class="mt-3 flex flex-wrap gap-2">
            <li v-for="membre in selection" :key="membre.id"
              class="inline-flex items-center gap-2 pl-2.5 pr-1.5 py-1 rounded-full bg-orange-50 text-orange-text text-sm">
              {{ membre.username }}
              <button type="button" class="w-5 h-5 grid place-items-center rounded-full hover:bg-orange-100 cursor-pointer"
                :aria-label="'Retirer ' + membre.username" @click="retirer(membre.id)">
                <i class="fa-solid fa-xmark text-xs"></i>
              </button>
            </li>
          </ul>
        </div>

        <p class="text-sm" :class="audienceCount === 0 ? 'text-red-600' : 'text-gray-600'">
          <i class="fa-solid fa-users mr-1.5" aria-hidden="true"></i>
          Cette sélection touche <strong>{{ audienceCount }}</strong> membre(s).
          <span v-if="audienceCount === 0">L'envoi sera refusé.</span>
        </p>
      </fieldset>

      <!-- 4. Envoyer -->
      <div class="p-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-gray-500 max-w-md">
          Chaque email porte un lien de désinscription qui fonctionne sans connexion.
          Les membres désinscrits ne sont jamais comptés.
        </p>
        <div class="flex flex-wrap gap-3">
          <BaseButton variant="ghost" size="sm" type="submit" :loading="enregistrement">
            Enregistrer le brouillon
          </BaseButton>
          <BaseButton v-if="form.id" variant="outline" size="sm" type="button"
            :loading="test" @click="envoyerTest">
            M'envoyer un test
          </BaseButton>
          <BaseButton v-if="form.id" variant="primary" size="sm" type="button"
            :disabled="audienceCount === 0" :loading="envoi" @click="envoyer">
            Envoyer à {{ audienceCount }} membre(s)
          </BaseButton>
        </div>
      </div>
    </form>

    <!-- ===== Historique ===== -->
    <div v-else class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
      <div v-if="chargement" class="p-5 space-y-3">
        <div v-for="n in 3" :key="n" class="h-16 rounded-xl skeleton"></div>
      </div>

      <div v-else-if="!campagnes.length" class="px-5 py-16 text-center">
        <i class="fa-regular fa-envelope text-4xl text-gray-300" aria-hidden="true"></i>
        <p class="mt-4 text-gray-700">Aucune campagne pour l'instant.</p>
        <p class="mt-1 text-sm text-gray-500">
          Une annonce, une demande de retour, une reprise de contact : commence par choisir pourquoi tu écris.
        </p>
      </div>

      <ul v-else class="divide-y divide-gray-100">
        <li v-for="campagne in campagnes" :key="campagne.id"
          class="p-5 flex flex-wrap items-start justify-between gap-4">
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span class="font-medium text-blue-night">{{ campagne.subject }}</span>
              <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="pastille(campagne.status)">
                {{ etat(campagne.status) }}
              </span>
              <span class="rounded-full px-2.5 py-0.5 text-xs bg-gray-100 text-gray-600">
                {{ options?.purposes?.[campagne.purpose] ?? campagne.purpose }}
              </span>
            </div>
            <p class="mt-1.5 text-sm text-gray-600">
              <template v-if="campagne.status === 'sent'">
                {{ campagne.sent_count }} envoyé(s) sur {{ campagne.recipients_count }}
                <span v-if="campagne.failed_count">, {{ campagne.failed_count }} en échec</span>
                le {{ formatDate(campagne.sent_at) }}
              </template>
              <template v-else>
                Brouillon, créé le {{ formatDate(campagne.created_at) }}
              </template>
            </p>
          </div>

          <div class="flex gap-2 shrink-0">
            <BaseButton v-if="campagne.status === 'draft'" variant="ghost" size="sm"
              @click="reprendre(campagne)">
              Reprendre
            </BaseButton>
            <button type="button" aria-label="Supprimer la campagne"
              class="w-9 h-9 rounded-full grid place-items-center text-gray-500 hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer"
              @click="supprimer(campagne)">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        </li>
      </ul>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import RichTextEditor from '@/components/admin/RichTextEditor.vue';
import api from '@/services/apiService';
import { getStorageUrl } from '@/config';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';
import { useConfirm } from '@/composables/useConfirm';

const notify = useNotify();
const confirm = useConfirm();

const options = ref(null);
const campagnes = ref([]);
const chargement = ref(true);
const composer = ref(false);
const enregistrement = ref(false);
const envoi = ref(false);
const test = ref(false);
const audienceCount = ref(0);

const form = ref(formeVide());
const recherche = ref('');
const resultats = ref([]);
const selection = ref([]);
let minuteurRecherche = null;

const publics = [
  { value: 'all', label: 'Toute la communauté' },
  { value: 'segment', label: 'Un segment' },
  { value: 'selected', label: 'Des membres choisis' },
];

function formeVide() {
  return { id: null, subject: '', body: '', purpose: 'announcement', audience: 'all', segment: '' };
}

const formatDate = (valeur) =>
  new Date(valeur).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });

const etat = (statut) =>
  ({ draft: 'Brouillon', sending: 'Envoi en cours', sent: 'Envoyée' })[statut] ?? statut;

const pastille = (statut) =>
  ({
    draft: 'bg-gray-100 text-gray-700',
    sending: 'bg-sky-50 text-sky-700',
    sent: 'bg-emerald-50 text-emerald-700',
  })[statut] ?? 'bg-gray-100 text-gray-700';

function nouvelle() {
  form.value = formeVide();
  selection.value = [];
  composer.value = true;
  choisirGabarit('announcement');
}

/**
 * Le gabarit remplit l'éditeur, sauf si quelque chose y a déjà été écrit :
 * changer d'objet ne doit pas effacer un texte en cours de rédaction.
 */
function choisirGabarit(cle) {
  form.value.purpose = cle;
  const modele = options.value?.templates?.[cle];
  if (!modele) return;

  const vierge = !form.value.subject && !form.value.body;
  const inchange = Object.values(options.value.templates).some(
    (m) => m.subject === form.value.subject && m.body === form.value.body
  );

  if (vierge || inchange) {
    form.value.subject = modele.subject;
    form.value.body = modele.body;
  }
}

function changerPublic(valeur) {
  form.value.audience = valeur;
  if (valeur !== 'segment') form.value.segment = '';
  if (valeur !== 'selected') selection.value = [];
  compterAudience();
}

async function compterAudience() {
  try {
    const { data } = await api.post('/admin/campagnes/audience', {
      audience: form.value.audience,
      segment: form.value.segment || null,
      user_ids: selection.value.map((m) => m.id),
    });
    audienceCount.value = data.data.count;
  } catch {
    audienceCount.value = 0;
  }
}

function chercher(terme) {
  clearTimeout(minuteurRecherche);
  if (!terme || terme.length < 2) {
    resultats.value = [];
    return;
  }
  minuteurRecherche = setTimeout(async () => {
    try {
      const { data } = await api.get('/members/search', { params: { q: terme } });
      const dejaPris = new Set(selection.value.map((m) => m.id));
      resultats.value = data.data.filter((m) => !dejaPris.has(m.id));
    } catch {
      resultats.value = [];
    }
  }, 300);
}

function ajouter(membre) {
  selection.value.push(membre);
  resultats.value = resultats.value.filter((m) => m.id !== membre.id);
  recherche.value = '';
  compterAudience();
}

function retirer(id) {
  selection.value = selection.value.filter((m) => m.id !== id);
  compterAudience();
}

async function copier(jeton) {
  try {
    await navigator.clipboard.writeText(jeton);
    notify.info('Copié', `${jeton} est dans le presse-papier.`);
  } catch {
    notify.info('À recopier', jeton);
  }
}

function corps() {
  return {
    subject: form.value.subject,
    body: form.value.body,
    purpose: form.value.purpose,
    audience: form.value.audience,
    segment: form.value.segment || null,
    user_ids: form.value.audience === 'selected' ? selection.value.map((m) => m.id) : null,
  };
}

async function enregistrer() {
  enregistrement.value = true;
  try {
    if (form.value.id) {
      await api.put(`/admin/campagnes/${form.value.id}`, corps());
      notify.success('Brouillon mis à jour');
    } else {
      const { data } = await api.post('/admin/campagnes', corps());
      form.value.id = data.data.id;
      notify.success('Brouillon enregistré', 'Tu peux maintenant t\'envoyer un test.');
    }
    await charger();
  } catch (err) {
    notify.error(apiErrorMessage(err, "L'enregistrement a échoué."));
  } finally {
    enregistrement.value = false;
  }
}

async function envoyerTest() {
  test.value = true;
  try {
    const { data } = await api.post(`/admin/campagnes/${form.value.id}/test`);
    notify.success('Test envoyé', data.message);
  } catch (err) {
    notify.error(apiErrorMessage(err, "Le test n'est pas parti."));
  } finally {
    test.value = false;
  }
}

async function envoyer() {
  const accepte = await confirm.ask({
    title: `Envoyer à ${audienceCount.value} membre(s) ?`,
    message: "Un email envoyé ne se rappelle pas. Vérifie l'objet et le contenu avant.",
    confirmLabel: 'Envoyer maintenant',
    tone: 'primary',
  });
  if (!accepte) return;

  envoi.value = true;
  try {
    const { data } = await api.post(`/admin/campagnes/${form.value.id}/envoi`);
    notify.success('Envoi lancé', data.message);
    composer.value = false;
    await charger();
  } catch (err) {
    notify.error(apiErrorMessage(err, "L'envoi a échoué."));
  } finally {
    envoi.value = false;
  }
}

function reprendre(campagne) {
  form.value = {
    id: campagne.id,
    subject: campagne.subject,
    body: campagne.body,
    purpose: campagne.purpose,
    audience: campagne.audience,
    segment: campagne.segment ?? '',
  };
  selection.value = [];
  composer.value = true;
  compterAudience();
}

async function supprimer(campagne) {
  const accepte = await confirm.ask({
    title: 'Supprimer cette campagne ?',
    message: campagne.subject,
    confirmLabel: 'Supprimer',
    tone: 'danger',
  });
  if (!accepte) return;

  try {
    await api.delete(`/admin/campagnes/${campagne.id}`);
    notify.success('Campagne supprimée');
    await charger();
  } catch (err) {
    notify.error(apiErrorMessage(err, 'La suppression a échoué.'));
  }
}

async function charger() {
  chargement.value = true;
  try {
    const [liste, choix] = await Promise.all([
      api.get('/admin/campagnes'),
      api.get('/admin/campagnes/options'),
    ]);
    campagnes.value = liste.data.data.data ?? [];
    options.value = choix.data.data;
    audienceCount.value = choix.data.data.reachable;
  } catch (err) {
    notify.error(apiErrorMessage(err, 'Les campagnes sont indisponibles.'));
  } finally {
    chargement.value = false;
  }
}

onMounted(charger);
</script>
