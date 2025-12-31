<template>
    <!-- Modal -->
    <div v-if="isOpen" class="fixed inset-0 flex items-center justify-center z-50 ">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl p-6 relative ">
            <!-- Close button -->
            <button @click="closeModal"
                class="cursor-pointer absolute top-3 right-3 text-gray-600 hover:text-gray-900 text-xl">
                <i class="fas fa-times"></i>
            </button>

            <h2 class="text-2xl font-poppins font-bold text-center mb-6 text-[#1E2A38]">
                {{ form.id ? 'Edit Review' : 'Add a review' }}
            </h2>

            <!-- Form -->
            <form @submit.prevent="submitReview" class="space-y-5 text-[#1E2A38] h-120 overflow-y-scroll pr-0.5 pl-0.5">
                <!-- Content -->
                <div>
                    <label class="block text-sm font-medium mb-1" required>Content</label>
                    <textarea v-model="form.content"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-primary focus:outline-none"
                        rows="6" placeholder="Write your review..."></textarea>
                </div>


                <!-- Link -->
                <div>
                    <label class="block text-sm font-medium mb-1">Link (optional)</label>
                    <input v-model="form.link" type="url"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-primary focus:outline-none"
                        placeholder="https://example.com" />
                </div>

                <!-- Link Preview -->
                <div v-if="form.link" class="mt-3">
                    <div class="border rounded-lg overflow-hidden">
                        <iframe :src="form.link" class="w-full h-48 border-0"
                            sandbox="allow-same-origin allow-scripts allow-popups" referrerpolicy="no-referrer">
                        </iframe>
                    </div>
                </div>

                <!-- Media -->
                <div>
                    <label class="block text-sm font-medium mb-1">Upload Media (optional)</label>
                    <input type="file" multiple @change="submitMedia"
                        class="cursor-pointer w-full border border-gray-300 rounded-lg px-3 py-2" />
                    <!-- Previews des nouveaux fichiers -->
                    <div v-if="form.mediaPreview.length" class="mt-2 flex flex-wrap gap-3">
                        <div v-for="(src, index) in form.mediaPreview" :key="'new-' + index">
                            <img :src="src" alt="Preview" class="w-32 h-32 object-cover rounded-lg border" />
                        </div>
                    </div>
                    <!-- Previews des images existantes -->
                    <div v-if="form.existingMedias && form.existingMedias.length" class="mt-2 flex flex-wrap gap-3">
                        <div v-for="(media, idx) in form.existingMedias" :key="'existing-' + idx" class="relative">
                            <img :src="getStorageUrl(media)" alt="Existing"
                                class="w-32 h-32 object-cover rounded-lg border" />
                            <button type="button" @click="removeExistingMedia(idx)"
                                class="cursor-pointer absolute top-1 right-1 bg-white rounded-full p-1 shadow text-red-600 hover:bg-gray-100">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tags -->
                <div>
                    <label class="block text-sm font-medium mb-1">Tags</label>

                    <!-- Tags -->
                    <div class="flex flex-wrap gap-2 mb-2">
                        <span v-for="(tag, index) in form.tags" :key="'tag-' + index"
                            class="inline-flex items-center bg-gray-100 text-sm rounded-full px-3 py-1">
                            <span class="mr-2">{{ tag }}</span>
                            <button type="button" @click="removeTag(index)"
                                class="text-red-500 hover:text-red-700 text-xs hover:cursor-pointer">
                                &times;
                            </button>
                        </span>
                    </div>

                    <!-- input -->
                    <input :value="form.tagInput" @input="onTagInput" @keydown="onTagKeydown" type="text"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-primary focus:outline-none"
                        placeholder="Add a tag and press Enter or comma" />

                    <!-- suggestions -->
                    <ul v-if="showSuggestions && suggestions.length" class="mt-2 border rounded bg-white max-h-40 overflow-auto">
                        <li v-for="(suggest, index) in suggestions" :key="'sugg-' + index"
                            class="px-3 py-2 hover:bg-gray-100 cursor-pointer" @click="addTag(suggest)">
                            {{ suggest }}
                        </li>
                    </ul>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" @click="closeModal"
                        class="cursor-pointer px-6 py-2 bg-blue-night text-white rounded-lg hover:bg-opacity-90 transition">
                        Discard
                    </button>
                    <button type="submit"
                        class="cursor-pointer px-6 py-2 bg-orange-primary text-white rounded-lg hover:bg-[#e75a27] transition">
                        {{ form.id ? 'Update' : 'Publish' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { getStorageUrl } from '@/config';
import { ref, watch } from 'vue';
import { useUserStore } from '@/stores/user';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';

const props = defineProps({
    isOpen: Boolean,
    editedReview: Object,
});

const emit = defineEmits(['close', 'publish', 'update']);

const router = useRouter();
const userStore = useUserStore();

// Form state
const form = ref({
    content: '',
    link: '',
    media: [], // nouveaux fichiers (File)
    mediaPreview: [], // preview des nouveaux fichiers
    existingMedias: [], // chemins des images déjà présentes
    tags: [], // array of tag names
    tagInput: '', // current input in tag field
});

const suggestions = ref([]);
const showSuggestions = ref(false);
let suggestionsTimer = null;

import api from '@/services/apiService';

// Fetch tag suggestions from backend (debounced)
const fetchTagSuggestions = async (query) => {
    if (!query || query.trim().length === 0) {
        suggestions.value = [];
        return;
    }
    try {
        const res = await api.get(`/tags?search=${encodeURIComponent(query)}`);
        const data = res.data?.data || res.data || [];
        suggestions.value = data.map(tag => (typeof tag === 'string' ? tag : (tag.name || ''))).filter(Boolean);
    } catch {
        suggestions.value = [];
    }
};

watch(
    () => props.editedReview,
    (newReview) => {
        if (newReview) {
            form.value = {
                id: newReview.id,
                content: newReview.content || '',
                link: newReview.link || '',
                media: [],
                mediaPreview: [],
                existingMedias: Array.isArray(newReview.medias)
                    ? newReview.medias.filter(m => typeof m === 'string' && m.length > 0)
                    : (typeof newReview.medias === 'string' && newReview.medias.length > 0
                        ? JSON.parse(newReview.medias).filter(m => typeof m === 'string' && m.length > 0)
                        : []),
                tags: normalizeTags(newReview.tags),
                tagInput: '',
            };
        }
    },
    { immediate: true }
);

// Ajout de nouveaux fichiers
const submitMedia = (event) => {
    const files = event.target.files;
    if (files) {
        form.value.media = Array.from(files);
        form.value.mediaPreview = Array.from(files).map((file) => URL.createObjectURL(file));
    }
};

// Suppression d'une image existante
const removeExistingMedia = (idx) => {
    form.value.existingMedias.splice(idx, 1);
};

// Ajouter un tag
const addTag = (raw) => {
    if (!raw) return;
    const value = String(raw).trim();
    if (!value) return;
    const exists = form.value.tags.find(t => t.toLowerCase() === value.toLowerCase());
    if (!exists) form.value.tags.push(value);
    form.value.tagInput = '';
    suggestions.value = [];
    showSuggestions.value = false;
};

// Supprimer un tag
const removeTag = (index) => {
    form.value.tags.splice(index, 1);
};

// Rechercher des tags correspondants à l'input
const onTagInput = (e) => {
    const tag = e.target.value;
    form.value.tagInput = tag;
    clearTimeout(suggestionsTimer);
    suggestionsTimer = setTimeout(() => fetchTagSuggestions(tag), 300);
    showSuggestions.value = true;
};

// Modifier l'affichage quand on valide un tag
const onTagKeydown = (e) => {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        if (form.value.tagInput.trim()) addTag(form.value.tagInput);
    } else if (e.key === 'Backspace' && !form.value.tagInput) {
        if (form.value.tags.length) form.value.tags.pop();
    }
};

function normalizeTags(raw) {
  if (raw == null) return [];


  // Si c'est un tableau
  if (Array.isArray(raw)) {
    return raw
      .map(item => {
        if (typeof item === 'object') {
          const name = item.name;
          if (name != null) return name.trim();
        }
      })
  }

}

// Submit form
const submitReview = () => {
    //if user not loggedin
    if (!userStore.isAuthenticated) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: "You need to be logged before to do this",
            showConfirmButton: true,
            confirmButtonColor: "#FF6B35"
        });
        router.push('/login');
        return;
    }

    //if logged

    if (!form.value.content.trim()) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: "You can't publish an empty review",
        });
        return;
    }
    const reviewData = {
        content: form.value.content,
        link: form.value.link,
        medias: form.value.media, // nouveaux fichiers (File)
        existingMedias: form.value.existingMedias, // chemins des images à garder
        tags: form.value.tags, // array of strings
    };


    if (form.value.id) {
        emit('update', reviewData);
    } else {
        emit('publish', reviewData);
    }
    form.value = {
        content: '',
        link: '',
        media: [],
        mediaPreview: [],
        existingMedias: [],
        tags: [],
        tagInput: ''
    }
    Swal.fire({
        icon: 'success',
        title: form.value.id ? 'Review Updated!' : 'Review Published!',
        showConfirmButton: false,
        timer: 1500
    });

    closeModal()
}

const closeModal = () => {
    emit('close')
    form.value = {
        content: '',
        link: '',
        media: [],
        mediaPreview: [],
        existingMedias: [],
        tags: [],
        tagInput: ''
    }
    document.body.classList.remove("overflow-y-hidden");

}
</script>
