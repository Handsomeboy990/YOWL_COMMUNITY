<template>
  <div class="rounded-xl border border-gray-200 overflow-hidden bg-white">
    <!-- Barre d'outils. Chaque bouton dit ce qu'il fait et montre s'il est
         actif : sans cet état, on ne sait pas si on est déjà en titre. -->
    <div v-if="editor" class="flex flex-wrap items-center gap-0.5 p-2 border-b border-gray-200 bg-gray-50">
      <button v-for="outil in outils" :key="outil.nom" type="button" :title="outil.nom"
        :aria-label="outil.nom" :aria-pressed="outil.actif ? outil.actif() : undefined"
        class="w-9 h-9 rounded-lg grid place-items-center text-sm transition-colors cursor-pointer"
        :class="outil.actif && outil.actif()
          ? 'bg-orange-primary text-white'
          : 'text-gray-600 hover:bg-gray-200'"
        @click="outil.action">
        <Icon :name="outil.icone" />
      </button>

      <span class="w-px h-6 bg-gray-300 mx-1" aria-hidden="true"></span>

      <button type="button" title="Insérer un lien" aria-label="Insérer un lien"
        class="w-9 h-9 rounded-lg grid place-items-center text-sm text-gray-600 hover:bg-gray-200 cursor-pointer"
        :class="editor.isActive('link') ? 'bg-orange-primary text-white' : ''"
        @click="setLink">
        <Icon name="link" aria-hidden="true" />
      </button>
      <button type="button" title="Insérer une image" aria-label="Insérer une image"
        class="w-9 h-9 rounded-lg grid place-items-center text-sm text-gray-600 hover:bg-gray-200 cursor-pointer"
        @click="pickImage">
        <Icon name="image" aria-hidden="true" />
      </button>
      <button type="button" title="Insérer une vidéo" aria-label="Insérer une vidéo"
        class="w-9 h-9 rounded-lg grid place-items-center text-sm text-gray-600 hover:bg-gray-200 cursor-pointer"
        @click="setVideo">
        <Icon name="youtube" aria-hidden="true" />
      </button>

      <span class="flex-1"></span>

      <button type="button" title="Annuler" aria-label="Annuler"
        class="w-9 h-9 rounded-lg grid place-items-center text-sm text-gray-600 hover:bg-gray-200 cursor-pointer disabled:opacity-40"
        :disabled="!editor.can().undo()" @click="editor.chain().focus().undo().run()">
        <Icon name="rotate-left" aria-hidden="true" />
      </button>
      <button type="button" title="Rétablir" aria-label="Rétablir"
        class="w-9 h-9 rounded-lg grid place-items-center text-sm text-gray-600 hover:bg-gray-200 cursor-pointer disabled:opacity-40"
        :disabled="!editor.can().redo()" @click="editor.chain().focus().redo().run()">
        <Icon name="rotate-right" aria-hidden="true" />
      </button>
    </div>

    <EditorContent :editor="editor" class="editeur" />

    <input ref="fileInput" type="file" accept="image/*" class="sr-only" @change="uploadImage" />
  </div>
</template>

<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import Youtube from '@tiptap/extension-youtube';
import api from '@/services/apiService';
import { useNotify, apiErrorMessage } from '@/composables/useNotify';

import Icon from '@/components/ui/Icon.vue';
const props = defineProps({
  modelValue: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

const notify = useNotify();
const fileInput = ref(null);
const editor = ref(null);

editor.value = new Editor({
  content: props.modelValue,
  extensions: [
    StarterKit.configure({ heading: { levels: [2, 3, 4] } }),
    Link.configure({ openOnClick: false, autolink: true }),
    Image.configure({ inline: false }),
    Youtube.configure({ controls: true, nocookie: true }),
  ],
  editorProps: {
    attributes: {
      class: 'prose-editeur focus:outline-none',
      role: 'textbox',
      'aria-multiline': 'true',
      'aria-label': 'Contenu de la page',
    },
  },
  onUpdate: ({ editor: instance }) => emit('update:modelValue', instance.getHTML()),
});

// Le contenu peut changer de l'extérieur, au chargement d'une autre page.
watch(
  () => props.modelValue,
  (value) => {
    if (editor.value && value !== editor.value.getHTML()) {
      editor.value.commands.setContent(value || '', { emitUpdate: false });
    }
  }
);

const outils = [
  { nom: 'Gras', icone: 'bold', action: () => editor.value.chain().focus().toggleBold().run(), actif: () => editor.value?.isActive('bold') },
  { nom: 'Italique', icone: 'italic', action: () => editor.value.chain().focus().toggleItalic().run(), actif: () => editor.value?.isActive('italic') },
  { nom: 'Barré', icone: 'strikethrough', action: () => editor.value.chain().focus().toggleStrike().run(), actif: () => editor.value?.isActive('strike') },
  { nom: 'Titre de section', icone: 'heading', action: () => editor.value.chain().focus().toggleHeading({ level: 2 }).run(), actif: () => editor.value?.isActive('heading', { level: 2 }) },
  { nom: 'Sous-titre', icone: 'h', action: () => editor.value.chain().focus().toggleHeading({ level: 3 }).run(), actif: () => editor.value?.isActive('heading', { level: 3 }) },
  { nom: 'Liste à puces', icone: 'list-ul', action: () => editor.value.chain().focus().toggleBulletList().run(), actif: () => editor.value?.isActive('bulletList') },
  { nom: 'Liste numérotée', icone: 'list-ol', action: () => editor.value.chain().focus().toggleOrderedList().run(), actif: () => editor.value?.isActive('orderedList') },
  { nom: 'Citation', icone: 'quote-left', action: () => editor.value.chain().focus().toggleBlockquote().run(), actif: () => editor.value?.isActive('blockquote') },
  { nom: 'Séparateur', icone: 'minus', action: () => editor.value.chain().focus().setHorizontalRule().run() },
];

function setLink() {
  const actuel = editor.value.getAttributes('link').href ?? '';
  const url = window.prompt('Adresse du lien', actuel);
  if (url === null) return;

  if (url === '') {
    editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
    return;
  }
  editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}

function setVideo() {
  const url = window.prompt('Adresse de la vidéo YouTube');
  if (!url) return;
  editor.value.commands.setYoutubeVideo({ src: url, width: 640, height: 360 });
}

const pickImage = () => fileInput.value?.click();

async function uploadImage(event) {
  const file = event.target.files?.[0];
  if (!file) return;

  const data = new FormData();
  data.append('image', file);

  try {
    const response = await api.post('/admin/legal-images', data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    editor.value.chain().focus().setImage({ src: response.data.data.url, alt: file.name }).run();
  } catch (err) {
    notify.error("L'image n'a pas pu être envoyée", apiErrorMessage(err));
  } finally {
    event.target.value = '';
  }
}

onBeforeUnmount(() => editor.value?.destroy());
</script>
