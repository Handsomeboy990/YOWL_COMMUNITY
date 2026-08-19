<template>
  <div
    class="mt-4 flex items-center gap-2 rounded-xl border-2 bg-white p-1 transition-all duration-300"
    :class="focused ? 'border-orange-primary shadow-md shadow-orange-primary/10' : 'border-gray-200'"
  >
    <input
      v-model="newComment"
      type="text"
      placeholder="Quelque chose à dire ?"
      class="flex-1 outline-none px-3 py-2.5 text-blue-night placeholder-gray-400 bg-transparent"
      @focus="focused = true"
      @blur="focused = false"
      @keyup.enter="submit"
    />
    <button
      class="cursor-pointer bg-orange-primary text-white w-10 h-10 grid place-items-center rounded-lg hover:bg-orange-primary-dark hover:-translate-y-0.5 transition-all duration-200"
      aria-label="Envoyer le commentaire"
      @click="submit"
    >
      <i class="fa-solid fa-paper-plane"></i>
    </button>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useNotify } from '@/composables/useNotify';
import { useUserStore } from "@/stores/user";
import router from "@/router";

const props = defineProps({
  content: String,
  id: Number
})

const newComment = ref(props.content || "");
const focused = ref(false);

const userStore = useUserStore()
const notify = useNotify();
const emit = defineEmits(["submitComment", "editComment"]);

const submit = () => {
  // Connexion obligatoire
  if (!userStore.isAuthenticated) {
    notify.info('Connexion requise', "Tu dois être connecté pour commenter.");
    router.push('/login')
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
};
</script>
