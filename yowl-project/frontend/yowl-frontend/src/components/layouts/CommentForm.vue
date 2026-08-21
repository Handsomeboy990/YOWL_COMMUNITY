<template>
  <div
    class="mt-4 flex items-center gap-2 rounded-xl border-2 bg-white p-1 transition-all duration-300"
    :class="focused ? 'border-orange-primary shadow-md shadow-orange-primary/10' : 'border-gray-200'"
  >
    <input
      v-model="newComment"
      type="text"
      placeholder="Quelque chose à dire ?"
      class="flex-1 outline-none px-3 py-2.5 min-h-11 text-blue-night placeholder-gray-400 bg-transparent"
      @focus="focused = true"
      @blur="focused = false"
      @keyup.enter="submit"
    />
    <button
      class="cursor-pointer bg-orange-primary text-white w-11 h-11 grid place-items-center rounded-lg hover:bg-orange-primary-dark hover:-translate-y-0.5 transition-all duration-200"
      aria-label="Envoyer le commentaire"
      @click="submit"
    >
      <Icon name="paper-plane" />
    </button>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useNotify } from '@/composables/useNotify';
import { useDraft } from '@/composables/useDraft';
import { useUserStore } from "@/stores/user";

import Icon from '@/components/ui/Icon.vue';
const props = defineProps({
  content: String,
  id: Number,
  // Identifie le brouillon. Sans elle, deux avis ouverts dans deux onglets
  // se partageraient le meme texte en attente.
  draftKey: { type: String, default: '' },
})

const newComment = ref(props.content || "");
const focused = ref(false);

const route = useRoute();
const router = useRouter();
const userStore = useUserStore()
const notify = useNotify();
const emit = defineEmits(["submitComment", "editComment"]);

// Une modification n'est pas un brouillon : elle part deja remplie.
const brouillon = useDraft(props.draftKey || 'commentaire', newComment, !props.content);

const submit = () => {
  // Connexion obligatoire. Le texte est mis de cote et l'adresse courante
  // voyage avec la redirection, pour revenir exactement ici apres coup.
  if (!userStore.isAuthenticated) {
    notify.info('Connexion requise', "Connecte-toi pour publier. Ton texte est gardé.");
    router.push({ name: 'login', query: { redirect: route.fullPath } });
    return;
  }

  if (!newComment.value.trim()) {
    notify.warning('Commentaire vide', "Impossible de publier un commentaire vide.");
    return;
  }

  if (!props.content) {
    emit("submitComment", newComment.value.trim());
  } else {
    emit("editComment", { content: newComment.value.trim(), id: props.id });
  }
  newComment.value = "";
  brouillon.oublier();
};
</script>
