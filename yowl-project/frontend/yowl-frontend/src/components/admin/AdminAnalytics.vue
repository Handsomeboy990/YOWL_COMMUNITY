<template>
  <section class="space-y-5">
    <header
      class="bg-white rounded-2xl border border-gray-200 shadow-sm px-5 py-4 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="font-poppins font-bold text-blue-night">Audience</h2>
        <p class="mt-0.5 text-sm text-gray-600">
          Qui arrive sur le site, par où et sur quelles pages. Recalculé au
          plus toutes les dix minutes.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <div class="inline-flex rounded-xl border border-gray-200 p-1" role="group" aria-label="Fenêtre d'observation">
          <button v-for="f in FENETRES" :key="f" type="button"
            class="min-h-9 px-3 rounded-lg text-sm font-medium transition-colors cursor-pointer"
            :class="fenetre === f ? 'bg-orange-primary text-white' : 'text-gray-600 hover:bg-gray-100'"
            :aria-pressed="fenetre === f" @click="changerFenetre(f)">
            {{ f }} j
          </button>
        </div>
        <BaseButton variant="night" size="sm" icon="file-csv" :loading="exporting" @click="exporter">
          Exporter en CSV
        </BaseButton>
      </div>
    </header>

    <!-- Ce que la mesure ne dit pas, dit avant les chiffres plutot qu'en note
         de bas de page : une personne qui lit « visites » en pensant
         « visiteurs » se trompe d'un facteur trois ou quatre. -->
    <div class="rounded-2xl border border-blue-200 bg-blue-50/60 px-5 py-4 flex items-start gap-3">
      <Icon name="circle-info" :size="18" class="mt-0.5 shrink-0 text-blue-700" aria-hidden="true" />
      <p class="text-sm text-blue-900 leading-relaxed">
        Ces chiffres comptent des <strong>visites</strong>, jamais des visiteurs
        uniques. Distinguer les deux demanderait de suivre les gens d'une page à
        l'autre, ce que cette mesure ne fait pas : aucune adresse IP, aucun
        cookie, aucun identifiant. C'est ce qui permet de mesurer l'audience
        sans bandeau de consentement.
      </p>
    </div>

    <div v-if="loading" class="grid gap-5 lg:grid-cols-2">
      <div v-for="n in 4" :key="n" class="h-72 rounded-2xl skeleton"></div>
    </div>

    <div v-else-if="error" class="bg-white rounded-2xl border border-gray-200 px-5 py-14 text-center">
      <Icon name="plug-circle-exclamation" :size="40" class="text-4xl text-gray-300" aria-hidden="true" />
      <p class="mt-4 text-gray-700">{{ error }}</p>
      <BaseButton class="mt-4" variant="primary" size="sm" @click="charger">Réessayer</BaseButton>
    </div>

    <template v-else-if="data">
      <!-- Aucune donnee : un etat a part, pas un tableau de zeros, qui se
           lirait comme une chute de frequentation. -->
      <div v-if="!data.total" class="bg-white rounded-2xl border border-gray-200 px-5 py-14 text-center">
        <Icon name="chart-line" :size="40" class="text-4xl text-gray-300" aria-hidden="true" />
        <p class="mt-4 font-medium text-blue-night">Aucune visite mesurée sur cette période.</p>
        <p class="mt-1 text-sm text-gray-600">
          La mesure démarre à la première page ouverte après sa mise en service.
          Si le site vient d'être déployé, revenez dans quelques heures.
        </p>
      </div>

      <template v-else>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <article v-for="carte in cartes" :key="carte.label"
            class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
              <span class="w-10 h-10 rounded-xl grid place-items-center" :class="carte.tone">
                <Icon :name="carte.icon" />
              </span>
              <p class="text-sm text-gray-600">{{ carte.label }}</p>
            </div>
            <p class="mt-3 font-poppins font-bold text-3xl text-blue-night tabular-nums">{{ carte.value }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ carte.hint }}</p>
          </article>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
          <h3 class="font-poppins font-semibold text-blue-night">Visites par jour</h3>
          <p class="mt-0.5 text-sm text-gray-600">
            Membres connectés et visiteurs, empilés.
          </p>
          <div class="mt-4 h-72">
            <LineChart :data="courbe" :options="optionsCourbe" />
          </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
          <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <h3 class="font-poppins font-semibold text-blue-night">Pages les plus vues</h3>
            <p class="mt-0.5 text-sm text-gray-600">
              Par motif de route : l'adresse exacte n'est pas conservée.
            </p>
            <ul class="mt-4 space-y-2.5">
              <li v-for="page in data.pages" :key="page.page">
                <div class="flex items-center justify-between gap-3 text-sm">
                  <code class="text-blue-night truncate">{{ page.page }}</code>
                  <span class="shrink-0 tabular-nums text-gray-600">
                    {{ page.visites.toLocaleString('fr-FR') }}
                    <span class="text-gray-400">· {{ page.part }} %</span>
                  </span>
                </div>
                <div class="mt-1 h-1.5 rounded-full bg-gray-100 overflow-hidden">
                  <div class="h-full rounded-full bg-orange-primary" :style="{ width: page.part + '%' }"></div>
                </div>
              </li>
            </ul>
          </div>

          <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <h3 class="font-poppins font-semibold text-blue-night">Provenances</h3>
            <p class="mt-0.5 text-sm text-gray-600">
              L'hôte d'origine seul, jamais la page ni la recherche tapée.
            </p>
            <ul class="mt-4 space-y-2.5 text-sm">
              <li class="flex items-center justify-between gap-3 py-1.5 border-b border-gray-100">
                <span class="flex items-center gap-2 text-blue-night">
                  <Icon name="arrow-right" :size="15" class="text-gray-400" aria-hidden="true" />
                  Accès direct
                </span>
                <span class="tabular-nums text-gray-600">{{ data.provenances.direct.toLocaleString('fr-FR') }}</span>
              </li>
              <li v-for="source in data.provenances.sources" :key="source.hote"
                class="flex items-center justify-between gap-3 py-1.5 border-b border-gray-100 last:border-0">
                <span class="truncate text-blue-night">{{ source.hote }}</span>
                <span class="shrink-0 tabular-nums text-gray-600">{{ source.visites.toLocaleString('fr-FR') }}</span>
              </li>
              <li v-if="!data.provenances.sources.length" class="py-3 text-gray-500">
                Aucune arrivée depuis un autre site sur cette période.
              </li>
            </ul>
          </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2 items-start">
          <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <h3 class="font-poppins font-semibold text-blue-night">Appareils</h3>
            <div class="mt-4 h-64">
              <DoughnutChart :data="appareils" :options="optionsAnneau" />
            </div>
          </div>

          <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <h3 class="font-poppins font-semibold text-blue-night">Avis les plus vus</h3>
            <p class="mt-0.5 text-sm text-gray-600">
              Compteur porté par chaque avis, lectures dans le fil comprises.
            </p>
            <ol class="mt-4 space-y-2 text-sm">
              <li v-for="(avis, rang) in data.contenus" :key="avis.id" class="flex items-start gap-3 py-1.5">
                <span class="w-6 shrink-0 tabular-nums text-gray-400">{{ rang + 1 }}.</span>
                <router-link :to="`/reviews/${avis.id}`" class="flex-1 text-blue-night hover:text-orange-text">
                  {{ avis.titre }}
                  <span v-if="avis.auteur" class="text-gray-500">— @{{ avis.auteur }}</span>
                </router-link>
                <span class="shrink-0 tabular-nums text-gray-600">{{ avis.vues.toLocaleString('fr-FR') }}</span>
              </li>
              <li v-if="!data.contenus.length" class="py-3 text-gray-500">Aucun avis publié.</li>
            </ol>
          </div>
        </div>
      </template>

      <p class="text-xs text-gray-500">
        Calculé le {{ dateCalcul }}<span v-if="dateDebut"> · mesure en place depuis le {{ dateDebut }}</span>.
        Les visites sont conservées cent vingt jours, puis supprimées.
      </p>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import {
  Chart as ChartJS,
  ArcElement,
  CategoryScale,
  Filler,
  Legend,
  LinearScale,
  LineElement,
  PointElement,
  Tooltip,
} from 'chart.js';
import { Doughnut, Line } from 'vue-chartjs';

import BaseButton from '@/components/ui/BaseButton.vue';
import Icon from '@/components/ui/Icon.vue';
import api from '@/services/apiService';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';

ChartJS.register(ArcElement, CategoryScale, Filler, Legend, LinearScale, LineElement, PointElement, Tooltip);

const LineChart = Line;
const DoughnutChart = Doughnut;

const FENETRES = [7, 30, 90];

const notify = useNotify();
const data = ref(null);
const loading = ref(true);
const exporting = ref(false);
const error = ref(null);
const fenetre = ref(30);

const LIBELLES_APPAREIL = { mobile: 'Téléphone', tablet: 'Tablette', desktop: 'Ordinateur' };

const cartes = computed(() => {
  if (!data.value) return [];

  const total = data.value.total;
  const membres = data.value.par_jour.reduce((n, j) => n + j.membres, 0);
  const mobile = data.value.appareils.find((a) => a.appareil === 'mobile')?.part ?? 0;
  // Arrondi a l'entier, huit visites sur trente jours donnent zero, et une
  // moyenne nulle sous un total non nul se lit comme une panne de la mesure.
  // Une decimale sous dix, l'entier au-dela, ou personne ne la lit.
  const brute = data.value.fenetre ? total / data.value.fenetre : 0;
  const parJour = brute >= 10 ? Math.round(brute) : Math.round(brute * 10) / 10;

  return [
    {
      label: 'Visites', icon: 'eye', tone: 'bg-orange-primary/10 text-orange-text',
      value: total.toLocaleString('fr-FR'),
      hint: `sur ${data.value.fenetre} jours`,
    },
    {
      label: 'Moyenne par jour', icon: 'chart-line', tone: 'bg-blue-100 text-blue-700',
      value: parJour.toLocaleString('fr-FR'),
      hint: 'toutes pages confondues',
    },
    {
      label: 'Part des membres', icon: 'users', tone: 'bg-emerald-100 text-emerald-700',
      value: total ? Math.round((membres / total) * 100) + ' %' : '0 %',
      hint: 'le reste vient de visiteurs',
    },
    {
      label: 'Part mobile', icon: 'mobile-screen-button', tone: 'bg-violet-100 text-violet-700',
      value: mobile + ' %',
      hint: 'téléphones seuls, hors tablettes',
    },
  ];
});

const courbe = computed(() => ({
  labels: data.value.par_jour.map((j) => j.libelle),
  datasets: [
    {
      label: 'Visiteurs',
      data: data.value.par_jour.map((j) => j.visiteurs),
      borderColor: '#cc4a15',
      backgroundColor: 'rgba(204, 74, 21, .18)',
      fill: true,
      tension: 0.35,
      pointRadius: 0,
      pointHoverRadius: 4,
    },
    {
      label: 'Membres',
      data: data.value.par_jour.map((j) => j.membres),
      borderColor: '#1e2a38',
      backgroundColor: 'rgba(30, 42, 56, .18)',
      fill: true,
      tension: 0.35,
      pointRadius: 0,
      pointHoverRadius: 4,
    },
  ],
}));

const optionsCourbe = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: { legend: { position: 'bottom' } },
  scales: {
    // Empilees : la hauteur totale se lit comme le trafic du jour, ce qui est
    // la question posee, et chaque bande garde sa part.
    y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
    x: { stacked: true, grid: { display: false } },
  },
};

const appareils = computed(() => ({
  labels: data.value.appareils.map((a) => LIBELLES_APPAREIL[a.appareil] ?? a.appareil),
  datasets: [{
    data: data.value.appareils.map((a) => a.visites),
    backgroundColor: ['#cc4a15', '#ff8552', '#1e2a38'],
    borderWidth: 0,
  }],
}));

const optionsAnneau = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } },
};

const formatDate = (iso) =>
  iso ? new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) : null;

const dateCalcul = computed(() =>
  data.value?.calcule_le
    ? new Date(data.value.calcule_le).toLocaleString('fr-FR', { dateStyle: 'long', timeStyle: 'short' })
    : '');
const dateDebut = computed(() => formatDate(data.value?.mesure_depuis));

function changerFenetre(valeur) {
  if (fenetre.value === valeur) return;
  fenetre.value = valeur;
  charger();
}

async function charger() {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get('/admin/analytique', { params: { jours: fenetre.value } });
    data.value = response.data.data;
  } catch (err) {
    error.value = apiErrorMessage(err, "Les chiffres d'audience sont indisponibles.");
  } finally {
    loading.value = false;
  }
}

async function exporter() {
  exporting.value = true;
  try {
    const response = await api.get('/admin/analytique/export', {
      params: { jours: fenetre.value },
      responseType: 'blob',
    });
    const url = URL.createObjectURL(response.data);
    const lien = document.createElement('a');
    lien.href = url;
    lien.download = `yowl-audience-${fenetre.value}j.csv`;
    document.body.appendChild(lien);
    lien.click();
    lien.remove();
    URL.revokeObjectURL(url);
  } catch (err) {
    notify.error(apiErrorMessage(err, "L'export n'a pas pu être préparé."));
  } finally {
    exporting.value = false;
  }
}

onMounted(charger);
</script>
