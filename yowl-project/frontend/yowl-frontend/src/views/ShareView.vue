<template>
  <div class="min-h-screen w-full bg-gray-50 flex flex-col">
    <!-- Mini en-tête -->
    <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
      <router-link to="/feed" class="flex items-center gap-2">
        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-orange-primary to-[#ff8c5a] grid place-items-center">
          <img src="@/assets/logo.png" alt="Logo YOWL" class="w-6 h-6">
        </span>
        <span class="font-poppins font-extrabold text-lg text-blue-night">YOWL</span>
      </router-link>
      <span class="text-sm text-gray-400 font-medium">Partage rapide</span>
    </header>

    <!-- Composeur -->
    <main class="flex-1 flex items-start justify-center px-4 py-6">
      <div class="w-full max-w-xl bg-white rounded-2xl shadow-lg border border-gray-100 p-6 animate-fade-in-up">
        <h1 class="font-poppins font-bold text-xl text-blue-night mb-1">
          Partager sur YOWL
        </h1>
        <p class="text-sm text-gray-400 mb-5">
          Ton avis part sur le fil en deux clics.
        </p>

        <!-- Aperçu du lien partagé -->
        <div v-if="form.link" class="mb-5 flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
          <span class="w-10 h-10 shrink-0 rounded-lg bg-orange-primary/10 text-orange-primary grid place-items-center">
            <i class="fa-solid fa-link"></i>
          </span>
          <div class="min-w-0">
            <p v-if="sharedTitle" class="text-sm font-semibold text-blue-night truncate">{{ sharedTitle }}</p>
            <p class="text-xs text-gray-400 truncate">{{ form.link }}</p>
          </div>
          <button type="button" class="ml-auto text-gray-300 hover:text-red-400 cursor-pointer"
            aria-label="Retirer le lien" @click="form.link = ''">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <form class="space-y-4" @submit.prevent="publish">
          <BaseTextarea
            v-model="form.content"
            label="Ton avis"
            placeholder="Qu'en penses-tu ?"
            :rows="4"
            required
          />

          <BaseInput
            v-if="!form.link"
            v-model="form.link"
            label="Lien (optionnel)"
            type="url"
            placeholder="https://exemple.com"
            icon="fa-solid fa-link"
          />

          <!-- Tags -->
          <div>
            <span class="block text-sm font-medium text-blue-night mb-1.5">Tags</span>
            <div v-if="form.tags.length" class="flex flex-wrap gap-2 mb-2">
              <span v-for="(tag, index) in form.tags" :key="tag"
                class="inline-flex items-center gap-2 bg-orange-primary/10 text-orange-primary text-sm font-medium rounded-full pl-3.5 pr-2 py-1.5">
                #{{ tag }}
                <button type="button" class="w-5 h-5 grid place-items-center rounded-full hover:bg-orange-primary/20 cursor-pointer"
                  :aria-label="`Retirer le tag ${tag}`" @click="form.tags.splice(index, 1)">
                  <i class="fa-solid fa-xmark text-xs"></i>
                </button>
              </span>
            </div>
            <BaseInput
              :modelValue="tagInput"
              placeholder="Ajoute un tag puis Entrée"
              icon="fa-solid fa-hashtag"
              @update:modelValue="tagInput = $event"
              @keydown.enter.prevent="addTag"
            />
          </div>

          <BaseButton type="submit" variant="primary" size="lg" block :loading="publishing">
            Publier sur le fil
          </BaseButton>
        </form>
      </div>
    </main>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useUserStore } from '@/stores/user';
import { useReviewStore } from '@/stores/review';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseTextarea from '@/components/ui/BaseTextarea.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const route = useRoute();
const router = useRouter();
const userStore = useUserStore();
const reviewStore = useReviewStore();

const sharedTitle = ref('');
const tagInput = ref('');
const publishing = ref(false);

const form = ref({
  content: '',
  link: '',
  tags: [],
});

onMounted(() => {
  // Paramètres transmis par le partage natif (PWA), le bookmarklet ou l'extension
  const { url, title, text } = route.query;
  // Certains partages Android mettent l'URL dans "text"
  const sharedUrl = url || (typeof text === 'string' && text.startsWith('http') ? text : '');
  form.value.link = sharedUrl || '';
  sharedTitle.value = title || '';
  if (title && !form.value.content) {
    form.value.content = '';
  }
});

const addTag = () => {
  const value = tagInput.value.trim().replace(/^#/, '');
  if (value && !form.value.tags.some((t) => t.toLowerCase() === value.toLowerCase())) {
    form.value.tags.push(value);
  }
  tagInput.value = '';
};

const publish = async () => {
  if (!userStore.isAuthenticated) {
    router.push({ name: 'login', query: { redirect: route.fullPath } });
    return;
  }
  if (!form.value.content.trim()) return;

  publishing.value = true;
  try {
    await reviewStore.createReviews({
      content: form.value.content,
      link: form.value.link,
      medias: [],
      existingMedias: [],
      tags: form.value.tags,
    });
    router.push('/feed');
  } catch {
    // Le store affiche déjà l'erreur
  } finally {
    publishing.value = false;
  }
};
</script>
