<template>
  <AppShell>
    <div class="w-full px-4 xl:px-6 py-8 pb-24">
      <div class="max-w-6xl">
        <!-- Fil d'Ariane : on arrive souvent ici par un lien de pied de page,
             sans savoir où l'on est dans l'ensemble. -->
        <nav class="text-sm text-gray-500 mb-6" aria-label="Fil d'Ariane">
          <router-link to="/feed" class="hover:text-blue-night transition-colors">{{ t('nav.feed') }}</router-link>
          <span class="mx-2" aria-hidden="true">/</span>
          <span class="text-blue-night">{{ page.title || '…' }}</span>
        </nav>

        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_16rem] lg:gap-12">
          <!-- ===== Le texte ===== -->
          <div class="min-w-0 order-2 lg:order-1">
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

          <!-- ===== Repères ===== -->
          <aside class="order-1 lg:order-2 lg:sticky lg:top-24 lg:self-start space-y-6">
            <!-- Les quatre textes -->
            <nav aria-label="Pages légales">
              <p class="text-[11px] uppercase tracking-wider text-gray-500 font-medium mb-3">
                {{ t('legal.documents') }}
              </p>
              <ul class="space-y-0.5">
                <li v-for="item in pages" :key="item.slug">
                  <router-link :to="`/${item.slug}`"
                    class="block px-3 py-2 rounded-lg text-sm transition-colors border-l-2"
                    :class="item.slug === slug
                      ? 'border-orange-primary bg-orange-50 text-orange-text font-medium'
                      : 'border-transparent text-gray-600 hover:bg-gray-100'">
                    {{ item.label }}
                  </router-link>
                </li>
              </ul>
            </nav>

            <!-- Sommaire du document, construit à partir de ses titres -->
            <nav v-if="sections.length" aria-label="Sommaire de la page">
              <p class="text-[11px] uppercase tracking-wider text-gray-500 font-medium mb-3">
                {{ t('legal.onThisPage') }}
              </p>
              <ul class="space-y-0.5">
                <li v-for="section in sections" :key="section.id">
                  <a :href="`#${section.id}`"
                    class="block px-3 py-1.5 rounded-lg text-sm transition-colors border-l-2 border-transparent hover:bg-gray-100"
                    :class="[
                      section.level === 3 ? 'pl-6 text-gray-500' : 'text-gray-600',
                      active === section.id ? 'border-orange-primary text-orange-text font-medium' : '',
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
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
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

const pages = computed(() => [
  { slug: 'charte', label: t('legal.charter') },
  { slug: 'confidentialite', label: t('legal.privacy') },
  { slug: 'conditions', label: t('legal.terms') },
  { slug: 'mentions-legales', label: t('legal.notices') },
]);

const formatDate = (value) =>
  new Date(value).toLocaleDateString(locale.value === 'en' ? 'en-GB' : 'fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });

// Une estimation honnête vaut mieux qu'un texte sans repère de longueur.
const readingTime = computed(() => {
  const mots = (page.value.body ?? '').replace(/<[^>]+>/g, ' ').split(/\s+/).filter(Boolean).length;
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
  if (!titres.length) return;

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

onBeforeUnmount(() => observer?.disconnect());
</script>
