<template>
  <section class="space-y-5">
    <header class="bg-white rounded-2xl border border-gray-200 shadow-sm px-5 py-4 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="font-poppins font-bold text-blue-night">Croissance</h2>
        <p class="mt-0.5 text-sm text-gray-600">
          Les cinq indicateurs suivis par la plateforme, plus les inscriptions
          hebdomadaires. Recalculés au plus toutes les quinze minutes.
        </p>
      </div>
      <BaseButton variant="night" size="sm" icon="fa-solid fa-file-csv" :loading="exporting" @click="exporter">
        Exporter en CSV
      </BaseButton>
    </header>

    <div v-if="loading" class="grid gap-5 lg:grid-cols-2">
      <div v-for="n in 4" :key="n" class="h-72 rounded-2xl skeleton"></div>
    </div>

    <div v-else-if="error" class="bg-white rounded-2xl border border-gray-200 px-5 py-14 text-center">
      <i class="fa-solid fa-plug-circle-exclamation text-4xl text-gray-300" aria-hidden="true"></i>
      <p class="mt-4 text-gray-700">{{ error }}</p>
      <BaseButton class="mt-4" variant="primary" size="sm" @click="charger">Réessayer</BaseButton>
    </div>

    <template v-else-if="data">
      <!-- Les quatre chiffres qui se lisent d'un coup d'oeil -->
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article v-for="carte in cartes" :key="carte.label"
          class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
          <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl grid place-items-center" :class="carte.tone">
              <i :class="carte.icon" aria-hidden="true"></i>
            </span>
            <p class="text-sm text-gray-600">{{ carte.label }}</p>
          </div>
          <p class="mt-3 font-poppins font-bold text-3xl text-blue-night tabular-nums">
            {{ carte.value }}
          </p>
          <p class="mt-1 text-xs text-gray-500">{{ carte.hint }}</p>
        </article>
      </div>

      <div class="grid gap-5 xl:grid-cols-2">
        <!-- 1. Membres actifs mensuels -->
        <article class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
          <h3 class="font-poppins font-bold text-blue-night">Membres actifs mensuels</h3>
          <p class="mt-0.5 text-xs text-gray-500">
            Un membre est actif s'il a lu, publié, commenté ou réagi dans le mois.
          </p>
          <div class="mt-4 h-64">
            <LineChart :data="mauData" :options="lineOptions" />
          </div>
        </article>

        <!-- Inscriptions par semaine -->
        <article class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
          <h3 class="font-poppins font-bold text-blue-night">Nouveaux membres par semaine</h3>
          <p class="mt-0.5 text-xs text-gray-500">Douze dernières semaines.</p>
          <div class="mt-4 h-64">
            <BarChart :data="signupData" :options="barOptions" />
          </div>
        </article>

        <!-- 2. Commentaires par membre -->
        <article class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
          <h3 class="font-poppins font-bold text-blue-night">Commentaires par membre</h3>
          <p class="mt-0.5 text-xs text-gray-500">
            Moyenne {{ data.commentaires.moyenne }}, médiane {{ data.commentaires.mediane }}. La
            répartition dit ce que la moyenne cache.
          </p>
          <div class="mt-4 h-64">
            <BarChart :data="commentData" :options="barOptions" />
          </div>
        </article>

        <!-- 3. Taux d'engagement -->
        <article class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
          <h3 class="font-poppins font-bold text-blue-night">Taux d'engagement</h3>
          <p class="mt-0.5 text-xs text-gray-500">
            {{ data.engagement.interactions }} interactions pour
            {{ format(data.engagement.vues) }} vues sur trente jours.
          </p>
          <div v-if="data.engagement.interactions" class="mt-4 h-64">
            <DoughnutChart :data="engagementData" :options="doughnutOptions" />
          </div>
          <p v-else class="mt-10 text-center text-sm text-gray-500">
            Aucune interaction sur la période.
          </p>
        </article>
      </div>

      <!-- 5. Retention par cohorte -->
      <article class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
          <h3 class="font-poppins font-bold text-blue-night">Rétention par cohorte d'inscription</h3>
          <p class="mt-0.5 text-xs text-gray-500">
            Part d'une semaine d'inscrits encore présente un jour, sept jours et
            trente jours plus tard. Une case vide veut dire que la fenêtre n'est
            pas encore fermée, pas que personne n'est revenu.
          </p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
              <tr>
                <th scope="col" class="px-5 py-3 font-medium">Semaine</th>
                <th scope="col" class="px-5 py-3 font-medium">Inscrits</th>
                <th v-for="jour in [1, 7, 30]" :key="jour" scope="col" class="px-5 py-3 font-medium">
                  D{{ jour }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="ligne in data.retention" :key="ligne.cohorte">
                <th scope="row" class="px-5 py-3 font-medium text-blue-night text-left">{{ ligne.cohorte }}</th>
                <td class="px-5 py-3 tabular-nums text-gray-700">{{ ligne.membres }}</td>
                <td v-for="jour in [1, 7, 30]" :key="jour" class="px-5 py-3">
                  <span v-if="ligne['d' + jour] === null" class="text-gray-400">en cours</span>
                  <span v-else class="inline-flex items-center gap-2">
                    <span class="w-16 h-1.5 rounded-full bg-gray-100 overflow-hidden">
                      <span class="block h-full rounded-full bg-orange-primary"
                        :style="{ width: ligne['d' + jour] + '%' }"></span>
                    </span>
                    <span class="tabular-nums text-gray-700">{{ ligne['d' + jour] }} %</span>
                  </span>
                </td>
              </tr>
              <tr v-if="!data.retention.length">
                <td colspan="5" class="px-5 py-10 text-center text-gray-500">
                  Pas encore de cohorte complète.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <p class="text-xs text-gray-500">
        Chiffres calculés le {{ formatDateTime(data.calcule_le) }}.
        <template v-if="!data.sessions.mesure_depuis">
          Le temps par session se mesure à partir des visites, il apparaîtra dès
          les premières journées de fréquentation.
        </template>
      </p>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import {
  Chart as ChartJS,
  ArcElement,
  BarElement,
  CategoryScale,
  Filler,
  Legend,
  LinearScale,
  LineElement,
  PointElement,
  Tooltip,
} from 'chart.js';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import BaseButton from '@/components/ui/BaseButton.vue';
import api from '@/services/apiService';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';

ChartJS.register(
  ArcElement, BarElement, CategoryScale, Filler, Legend,
  LinearScale, LineElement, PointElement, Tooltip
);

const LineChart = Line;
const BarChart = Bar;
const DoughnutChart = Doughnut;

const data = ref(null);
const loading = ref(true);
const exporting = ref(false);
const error = ref(null);
const notify = useNotify();

const format = (value) => (value ?? 0).toLocaleString('fr-FR');

const cartes = computed(() => {
  const dernier = data.value.mau.at(-1);
  return [
    {
      label: 'Actifs ce mois', value: format(dernier?.actifs),
      hint: dernier?.libelle ?? '', icon: 'fa-solid fa-users text-blue-600', tone: 'bg-blue-50',
    },
    {
      label: 'Commentaires par membre', value: data.value.commentaires.moyenne,
      hint: 'médiane ' + data.value.commentaires.mediane,
      icon: 'fa-regular fa-comments text-emerald-600', tone: 'bg-emerald-50',
    },
    {
      label: "Taux d'engagement", value: data.value.engagement.taux + ' %',
      hint: 'sur trente jours', icon: 'fa-solid fa-bolt text-orange-text', tone: 'bg-orange-50',
    },
    {
      label: 'Temps par session', value: data.value.sessions.moyenne_minutes + ' min',
      hint: data.value.sessions.sessions + ' session(s) mesurée(s)',
      icon: 'fa-regular fa-clock text-violet-600', tone: 'bg-violet-50',
    },
  ];
});

const mauData = computed(() => ({
  labels: data.value.mau.map((point) => point.libelle),
  datasets: [{
    label: 'Membres actifs',
    data: data.value.mau.map((point) => point.actifs),
    borderColor: '#E86A33',
    backgroundColor: 'rgba(232, 106, 51, 0.12)',
    fill: true,
    tension: 0.35,
    pointRadius: 3,
  }],
}));

const signupData = computed(() => ({
  labels: data.value.inscriptions.map((point) => point.libelle),
  datasets: [{
    label: 'Inscriptions',
    data: data.value.inscriptions.map((point) => point.inscriptions),
    backgroundColor: '#263A56',
    borderRadius: 6,
  }],
}));

const commentData = computed(() => {
  const repartition = data.value.commentaires.repartition;
  return {
    labels: Object.keys(repartition),
    datasets: [{
      label: 'Membres',
      data: Object.values(repartition),
      backgroundColor: '#E86A33',
      borderRadius: 6,
    }],
  };
});

const engagementData = computed(() => {
  const repartition = data.value.engagement.repartition;
  return {
    labels: ['Réactions', 'Commentaires', 'Enregistrements', 'Marqués utiles'],
    datasets: [{
      data: [
        repartition.reactions, repartition.commentaires,
        repartition.enregistrements, repartition.utiles,
      ],
      backgroundColor: ['#E86A33', '#263A56', '#10B981', '#8B5CF6'],
      borderWidth: 0,
    }],
  };
});

const lineOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
};

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
};

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '62%',
  plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16 } } },
};

const formatDateTime = (value) =>
  new Date(value).toLocaleString('fr-FR', {
    day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit',
  });

async function charger() {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get('/admin/croissance');
    data.value = response.data.data;
  } catch (err) {
    error.value = apiErrorMessage(err, 'Les indicateurs de croissance sont indisponibles.');
  } finally {
    loading.value = false;
  }
}

async function exporter() {
  exporting.value = true;
  try {
    const response = await api.get('/admin/croissance/export', { responseType: 'blob' });
    const url = URL.createObjectURL(response.data);
    const lien = document.createElement('a');
    lien.href = url;
    lien.download = 'yowl-croissance.csv';
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
