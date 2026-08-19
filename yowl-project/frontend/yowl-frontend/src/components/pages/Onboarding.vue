<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <AppShell>
    <div class="w-full px-4 xl:px-6 py-8 pb-24">
      <div class="max-w-2xl">
        <!-- Progression : trois etapes, on sait toujours ou on en est -->
        <ol class="flex items-center gap-2 mb-8" aria-label="Progression">
          <li v-for="(label, index) in steps" :key="label" class="flex items-center gap-2">
            <span class="w-7 h-7 rounded-full grid place-items-center text-xs font-bold transition-colors"
              :class="index <= step ? 'bg-orange-primary text-white' : 'bg-gray-200 text-gray-500'">
              {{ index + 1 }}
            </span>
            <span class="text-sm" :class="index === step ? 'text-blue-night font-medium' : 'text-gray-400'">
              {{ label }}
            </span>
            <i v-if="index < steps.length - 1" class="fa-solid fa-chevron-right text-gray-300 text-xs ml-1"></i>
          </li>
        </ol>

        <!-- 1. Centres d'intérêt -->
        <section v-if="step === 0">
          <h1 class="font-poppins font-extrabold text-3xl text-blue-night">Qu'est-ce qui t'intéresse ?</h1>
          <p class="mt-3 text-gray-600 leading-relaxed">
            Choisis au moins trois sujets. Ton fil se remplira avec les avis qui parlent de ça,
            même si tu ne connais encore personne ici.
          </p>

          <div v-if="loadingTags" class="mt-6 flex flex-wrap gap-2">
            <span v-for="n in 12" :key="n" class="h-9 w-28 rounded-full skeleton"></span>
          </div>

          <div v-else class="mt-6 flex flex-wrap gap-2">
            <button v-for="tag in tags" :key="tag.id" type="button"
              class="px-4 py-2 rounded-full border text-sm transition-colors cursor-pointer"
              :class="chosenTags.has(tag.id)
                ? 'border-orange-primary bg-orange-50 text-orange-primary font-medium'
                : 'border-gray-200 text-gray-600 hover:border-gray-300'"
              :aria-pressed="chosenTags.has(tag.id)" @click="toggleTag(tag.id)">
              <i v-if="chosenTags.has(tag.id)" class="fa-solid fa-check mr-1.5"></i>#{{ tag.name }}
            </button>
          </div>

          <div class="mt-8 flex items-center gap-4">
            <BaseButton variant="primary" :disabled="chosenTags.size < 3" :loading="saving" @click="saveTags">
              Continuer
            </BaseButton>
            <span class="text-sm text-gray-500">{{ chosenTags.size }} / 3 minimum</span>
          </div>
        </section>

        <!-- 2. Membres à suivre -->
        <section v-else-if="step === 1">
          <h1 class="font-poppins font-extrabold text-3xl text-blue-night">Quelques membres à suivre</h1>
          <p class="mt-3 text-gray-600 leading-relaxed">
            Ceux qui publient le plus sur la plateforme. Tu pourras te désabonner quand tu veux.
          </p>

          <div v-if="loadingPeople" class="mt-6 space-y-3">
            <div v-for="n in 4" :key="n" class="h-16 rounded-xl skeleton"></div>
          </div>

          <ul v-else-if="people.length" class="mt-6 space-y-2">
            <li v-for="person in people" :key="person.id"
              class="flex items-center gap-3 p-3 rounded-xl border border-gray-200">
              <img v-if="person.picture" :src="getStorageUrl(person.picture)" alt=""
                class="w-11 h-11 rounded-full object-cover shrink-0" />
              <span v-else
                class="w-11 h-11 rounded-full bg-blue-night grid place-items-center text-white text-sm font-bold shrink-0">
                {{ (person.username || '?').slice(0, 2).toUpperCase() }}
              </span>
              <span class="min-w-0 flex-1">
                <span class="block font-medium text-blue-night truncate">{{ person.fullname }}</span>
                <span class="block text-sm text-gray-500 truncate">@{{ person.username }}</span>
              </span>
              <FollowButton type="user" :id="person.id" />
            </li>
          </ul>

          <p v-else class="mt-6 text-sm text-gray-500">
            Personne à suggérer pour l'instant. Ton fil se remplira avec les sujets choisis.
          </p>

          <div class="mt-8 flex items-center gap-3">
            <BaseButton variant="primary" @click="step = 2">Continuer</BaseButton>
            <BaseButton variant="ghost" @click="step = 2">Passer</BaseButton>
          </div>
        </section>

        <!-- 3. Premier avis -->
        <section v-else>
          <h1 class="font-poppins font-extrabold text-3xl text-blue-night">C'est prêt</h1>
          <p class="mt-3 text-gray-600 leading-relaxed">
            Ton fil est constitué. Il ne manque plus que ton premier avis : un lien, ce que tu en penses,
            et la conversation démarre.
          </p>

          <div class="mt-8 flex flex-col sm:flex-row gap-3">
            <BaseButton variant="primary" icon="fa-solid fa-plus" @click="publishFirst">
              Publier mon premier avis
            </BaseButton>
            <BaseButton :tag="'router-link'" :to="'/feed'" variant="ghost">Voir mon fil</BaseButton>
          </div>
        </section>
      </div>
    </div>

    <AddReviewModal :isOpen="isPublishOpen" @close="isPublishOpen = false" @publish="onPublished" />
  </AppShell>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import AppShell from '@/components/layouts/AppShell.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import FollowButton from '@/components/ui/FollowButton.vue';
import AddReviewModal from '@/components/layouts/AddReviewModal.vue';
import api from '@/services/apiService';
import { getStorageUrl } from '@/config';
import { useFollowStore } from '@/stores/follow';
import { useReviewStore } from '@/stores/review';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';

const router = useRouter();
const followStore = useFollowStore();
const reviewStore = useReviewStore();
const notify = useNotify();

const steps = ['Tes sujets', 'Qui suivre', 'Ton premier avis'];
const step = ref(0);

const tags = ref([]);
const chosenTags = ref(new Set());
const people = ref([]);
const loadingTags = ref(true);
const loadingPeople = ref(false);
const saving = ref(false);
const isPublishOpen = ref(false);

const toggleTag = (id) => {
  chosenTags.value.has(id) ? chosenTags.value.delete(id) : chosenTags.value.add(id);
  // Set n'est pas réactif en profondeur : on remplace la référence.
  chosenTags.value = new Set(chosenTags.value);
};

async function saveTags() {
  saving.value = true;
  try {
    // Suivre un tag est ce qui remplit le fil de quelqu'un qui ne connaît personne.
    await Promise.all(
      [...chosenTags.value].map((id) => api.post('/follows', { type: 'tag', id }))
    );
    await followStore.load();
    step.value = 1;
    loadPeople();
  } catch (err) {
    notify.error(apiErrorMessage(err, "Tes sujets n'ont pas pu être enregistrés."));
  } finally {
    saving.value = false;
  }
}

async function loadPeople() {
  loadingPeople.value = true;
  try {
    const response = await api.get('/follows/suggestions');
    people.value = response.data.data;
  } catch {
    people.value = [];
  } finally {
    loadingPeople.value = false;
  }
}

const publishFirst = () => {
  isPublishOpen.value = true;
};

async function onPublished(data) {
  await reviewStore.createReviews(data);
  isPublishOpen.value = false;
  router.push('/feed');
}

onMounted(async () => {
  try {
    const response = await api.get('/tags');
    tags.value = response.data.data.slice(0, 24);
  } catch {
    tags.value = [];
  } finally {
    loadingTags.value = false;
  }
});
</script>
