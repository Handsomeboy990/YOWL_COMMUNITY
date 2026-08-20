<template>
  <AppShell>
    <div class="w-full px-4 xl:px-6 py-8 pb-24">
      <div class="w-full">
        <!-- Fil d'Ariane : on arrive souvent ici par un lien de pied de page,
             sans savoir où l'on est dans l'ensemble. -->
        <nav class="text-sm text-gray-500 mb-6" aria-label="Fil d'Ariane">
          <router-link to="/feed" class="hover:text-blue-night transition-colors">{{ t('nav.feed') }}</router-link>
          <span class="mx-2" aria-hidden="true">/</span>
          <span class="text-blue-night">{{ page.title || '…' }}</span>
        </nav>

        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_17rem] xl:grid-cols-[15rem_minmax(0,1fr)_17rem] lg:gap-10 xl:gap-12">
          <!-- ===== Le texte ===== -->
          <div class="min-w-0 order-2 lg:order-1 xl:order-2">
            <div v-if="loading" class="space-y-3">
              <div class="h-11 w-2/3 rounded skeleton"></div>
              <div class="h-4 w-40 rounded skeleton mb-8"></div>
              <div v-for="n in 10" :key="n" class="h-4 rounded skeleton" :style="{ width: (60 + (n * 7) % 40) + '%' }"></div>
            </div>

            <div v-else-if="error"
              class="flex flex-col items-center text-center bg-white border border-gray-200 rounded-2xl py-16 px-4">
              <i class="fa-regular fa-file-lines text-4xl text-gray-400" aria-hidden="true"></i>
              <h1 class="mt-5 text-lg font-semibold text-gray-800">{{ t('legal.unavailable') }}</h1>
              <p class="mt-2 text-sm text-gray-600 max-w-md">{{ error }}</p>
              <BaseButton class="mt-5" size="sm" variant="primary" @click="load">{{ t('common.retry') }}</BaseButton>
            </div>

            <article v-else>
              <header class="pb-6 border-b border-gray-200">
                <h1 class="font-poppins font-extrabold text-3xl sm:text-4xl text-blue-night leading-tight text-balance">
                  {{ page.title }}
                </h1>
                <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-gray-500">
                  <span v-if="page.updated_at" class="inline-flex items-center gap-1.5">
                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                    {{ t('legal.updated', { date: formatDate(page.updated_at) }) }}
                  </span>
                  <span class="inline-flex items-center gap-1.5">
                    <i class="fa-regular fa-hourglass" aria-hidden="true"></i>
                    {{ readingTime }}
                  </span>
                </div>
              </header>

              <!-- Le contenu vient de la base, reconstruit côté serveur à
                   partir d'une liste blanche de balises. -->
              <div ref="body" class="contenu-legal mt-8" v-html="page.body"></div>

              <footer class="mt-12 pt-6 border-t border-gray-200 flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm text-gray-500">
                  {{ t('legal.question') }}
                </p>
                <BaseButton :tag="'router-link'" :to="'/suggestion'" variant="ghost" size="sm"
                  icon="fa-regular fa-paper-plane">
                  {{ t('nav.suggestions') }}
                </BaseButton>
              </footer>
            </article>
          </div>

          <!-- ===== Rail de gauche : les quatre documents ===== -->
          <aside class="order-1 lg:order-2 xl:order-1 lg:sticky lg:top-24 lg:self-start space-y-6">
            <nav v-for="(groupe, g) in groupes" :key="groupe.titre" aria-label="Pages du site">
              <p class="text-[11px] uppercase tracking-wider text-gray-500 font-medium mb-3">
                {{ groupe.titre }}
              </p>
              <ul class="space-y-1">
                <li v-for="(item, index) in groupe.pages" :key="item.slug"
                  class="animate-fade-in-up" :style="{ animationDelay: (g * 80 + index * 40) + 'ms' }">
                  <router-link :to="item.route"
                    class="group flex items-start gap-2.5 px-3 py-2.5 rounded-xl text-sm transition-colors"
                    :class="item.slug === slug
                      ? 'bg-orange-50 text-orange-text font-medium'
                      : 'text-gray-600 hover:bg-gray-100'">
                    <i :class="[item.icon, 'mt-0.5 text-xs shrink-0',
                        item.slug === slug ? 'text-orange-text' : 'text-gray-400 group-hover:text-gray-600']"
                      aria-hidden="true"></i>
                    <span>{{ item.label }}</span>
                  </router-link>
                </li>
              </ul>
            </nav>

            <!-- Ce que la page couvre, en un chiffre -->
            <div v-if="sections.length" class="rounded-xl bg-gray-50 border border-gray-200 p-4">
              <p class="text-[11px] uppercase tracking-wider text-gray-500 font-medium">
                {{ t('legal.thisDocument') }}
              </p>
              <dl class="mt-2.5 space-y-1.5 text-sm">
                <div class="flex justify-between gap-3">
                  <dt class="text-gray-600">{{ t('legal.sections') }}</dt>
                  <dd class="font-medium text-blue-night tabular-nums">{{ topSections }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                  <dt class="text-gray-600">{{ t('legal.words') }}</dt>
                  <dd class="font-medium text-blue-night tabular-nums">{{ wordCount }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                  <dt class="text-gray-600">{{ t('legal.duration') }}</dt>
                  <dd class="font-medium text-blue-night">{{ readingTime }}</dd>
                </div>
              </dl>
            </div>
          </aside>

          <!-- ===== Rail de droite : le sommaire du document ===== -->
          <aside v-if="sections.length"
            class="order-3 hidden lg:block lg:sticky lg:top-24 lg:self-start">
            <p class="text-[11px] uppercase tracking-wider text-gray-500 font-medium mb-3">
              {{ t('legal.onThisPage') }}
            </p>

            <!-- Avancement de lecture : un texte long a besoin d'un repere -->
            <div class="h-1 rounded-full bg-gray-200 overflow-hidden mb-3">
              <div class="h-full rounded-full bg-orange-primary transition-[width] duration-200 ease-out"
                :style="{ width: progress + '%' }"></div>
            </div>

            <nav aria-label="Sommaire de la page"
              class="max-h-[calc(100vh-14rem)] overflow-y-auto pr-1">
              <ul class="space-y-0.5">
                <li v-for="section in sections" :key="section.id">
                  <a :href="`#${section.id}`"
                    class="block px-3 py-1.5 rounded-lg text-sm transition-colors border-l-2"
                    :class="[
                      section.level === 3 ? 'pl-6 text-[13px] text-gray-500' : 'text-gray-600',
                      active === section.id
                        ? 'border-orange-primary bg-orange-50/60 text-orange-text font-medium'
                        : 'border-transparent hover:bg-gray-100',
                    ]"
                    @click.prevent="goTo(section.id)">
                    {{ section.text }}
                  </a>
                </li>
              </ul>
            </nav>
          </aside>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import AppShell from '@/components/layouts/AppShell.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import api from '@/services/apiService';
import { apiErrorMessage } from '@/composables/useNotify';

const { t, locale } = useI18n();
const route = useRoute();

const slug = ref(route.params.slug ?? route.meta.slug);
const page = ref({});
const loading = ref(true);
const error = ref(null);
const body = ref(null);
const sections = ref([]);
const active = ref(null);
let observer = null;
let revelateur = null;

const groupes = computed(() => [
  {
    titre: t('legal.theService'),
    pages: [
      { slug: 'a-propos', route: '/about', label: t('legal.about'), icon: 'fa-solid fa-circle-info' },
      { slug: 'faq', route: '/faq', label: t('legal.faq'), icon: 'fa-solid fa-circle-question' },
    ],
  },
  {
    titre: t('legal.documents'),
    pages: [
      { slug: 'charte', route: '/charte', label: t('legal.charter'), icon: 'fa-solid fa-handshake-angle' },
      { slug: 'confidentialite', route: '/confidentialite', label: t('legal.privacy'), icon: 'fa-solid fa-shield-halved' },
      { slug: 'conditions', route: '/conditions', label: t('legal.terms'), icon: 'fa-solid fa-file-signature' },
      { slug: 'mentions-legales', route: '/mentions-legales', label: t('legal.notices'), icon: 'fa-solid fa-building-columns' },
    ],
  },
]);


const texteBrut = computed(() => (page.value.body ?? '').replace(/<[^>]+>/g, ' '));
const wordCount = computed(() =>
  texteBrut.value.split(/\s+/).filter(Boolean).length.toLocaleString(
    locale.value === 'en' ? 'en-GB' : 'fr-FR'
  )
);
const topSections = computed(() => sections.value.filter((s) => s.level === 2).length);

// Avancement de lecture, en part de la hauteur reellement defilable.
const progress = ref(0);
function onScroll() {
  const defilable = document.documentElement.scrollHeight - window.innerHeight;
  progress.value = defilable > 0 ? Math.min(100, Math.round((window.scrollY / defilable) * 100)) : 0;
}

const formatDate = (value) =>
  new Date(value).toLocaleDateString(locale.value === 'en' ? 'en-GB' : 'fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });

// Une estimation honnête vaut mieux qu'un texte sans repère de longueur.
const readingTime = computed(() => {
  const mots = texteBrut.value.split(/\s+/).filter(Boolean).length;
  const minutes = Math.max(1, Math.round(mots / 200));
  return t('legal.readingTime', { count: minutes });
});

/**
 * Construit le sommaire à partir des titres réellement présents, et leur
 * donne un identifiant : le contenu vient de la base, il n'y en a pas.
 */
function buildOutline() {
  if (!body.value) return;

  const titres = [...body.value.querySelectorAll('h2, h3')];
  sections.value = titres.map((titre, index) => {
    const id = 'section-' + index;
    titre.id = id;
    titre.setAttribute('tabindex', '-1');
    return { id, text: titre.textContent.trim(), level: titre.tagName === 'H3' ? 3 : 2 };
  });

  observer?.disconnect();
  revelateur?.disconnect();
  if (!titres.length) return;

  // Les titres apparaissent quand ils entrent dans le champ. La classe qui
  // les masque n'est posee qu'ici : sans JavaScript, tout reste visible.
  body.value.closest('article')?.classList.add('reveler-actif');
  revelateur = new IntersectionObserver(
    (entries) => {
      entries.forEach((entree) => {
        if (entree.isIntersecting) {
          entree.target.classList.add('vu');
          revelateur.unobserve(entree.target);
        }
      });
    },
    { rootMargin: '0px 0px -8% 0px', threshold: 0.1 }
  );
  titres.forEach((titre) => revelateur.observe(titre));

  // Le repère suit la lecture : sans lui, le sommaire indique toujours le
  // premier titre quelle que soit la position dans la page.
  observer = new IntersectionObserver(
    (entries) => {
      const visible = entries.filter((e) => e.isIntersecting);
      if (visible.length) active.value = visible[0].target.id;
    },
    { rootMargin: '-80px 0px -70% 0px' }
  );
  titres.forEach((titre) => observer.observe(titre));
}

function goTo(id) {
  const cible = document.getElementById(id);
  if (!cible) return;
  cible.scrollIntoView({ behavior: 'smooth', block: 'start' });
  // Le focus suit le saut, sinon la navigation au clavier reste en haut.
  cible.focus({ preventScroll: true });
  active.value = id;
}

async function load() {
  loading.value = true;
  error.value = null;
  sections.value = [];
  try {
    const response = await api.get(`/legal/${slug.value}`);
    page.value = response.data.data;

    // Baisser le drapeau avant de construire le sommaire : tant qu'il est
    // leve, le squelette occupe la place de l'article et la reference du
    // corps vaut null. Le sommaire restait donc vide, sans erreur.
    loading.value = false;
    await nextTick();
    buildOutline();
  } catch (err) {
    error.value = apiErrorMessage(err, t('legal.notPublished'));
  } finally {
    loading.value = false;
  }
}

watch(
  () => route.params.slug ?? route.meta.slug,
  (value) => {
    slug.value = value;
    load();
  },
  { immediate: true }
);

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
});

onBeforeUnmount(() => {
  observer?.disconnect();
  revelateur?.disconnect();
  window.removeEventListener('scroll', onScroll);
});
</script>
