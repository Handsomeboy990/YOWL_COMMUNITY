<template>
    <BaseModal :isOpen="isOpen" :title="form.id ? 'Modifier la review' : 'Publier une review'" size="lg" @close="closeModal">
        <form class="space-y-5 text-blue-night" @submit.prevent="submitReview">
            <!-- Contenu -->
            <BaseTextarea
                v-model="form.content"
                label="Ton avis"
                placeholder="Partage ton avis avec la communauté..."
                :rows="5"
                required
            />

            <!-- Lien -->
            <BaseInput
                v-model="form.link"
                label="Lien (optionnel)"
                type="url"
                placeholder="https://exemple.com"
                icon="fa-solid fa-link"
                hint="Le contenu du web dont tu parles"
            />

            <!-- Aperçu du lien -->
            <div v-if="form.link" class="rounded-xl border border-gray-200 overflow-hidden">
                <iframe :src="form.link" class="w-full h-48 border-0" title="Aperçu du lien"
                    sandbox="allow-same-origin allow-scripts allow-popups" referrerpolicy="no-referrer">
                </iframe>
            </div>

            <!-- Médias -->
            <div>
                <span class="block text-sm font-medium text-blue-night mb-1.5">Images (optionnel)</span>
                <label
                    class="group flex flex-col items-center justify-center gap-2 w-full rounded-xl border-2 border-dashed border-gray-300 hover:border-orange-primary bg-gray-50 hover:bg-orange-50/50 px-4 py-6 cursor-pointer transition-colors"
                >
                    <i class="fa-solid fa-cloud-arrow-up text-2xl text-gray-400 group-hover:text-orange-primary transition-colors"></i>
                    <span class="text-sm text-gray-500">Clique ou dépose tes images ici</span>
                    <input type="file" accept="image/*" multiple class="hidden" @change="submitMedia" />
                </label>

                <!-- Aperçu des nouveaux fichiers -->
                <div v-if="form.mediaPreview.length" class="mt-3 flex flex-wrap gap-3">
                    <div v-for="(src, index) in form.mediaPreview" :key="'new-' + index" class="relative group">
                        <img :src="src" alt="Aperçu" class="w-28 h-28 object-cover rounded-xl border border-gray-200" />
                        <button type="button"
                            class="absolute -top-2 -right-2 w-7 h-7 grid place-items-center bg-white rounded-full shadow text-red-500 hover:bg-red-50 cursor-pointer"
                            aria-label="Retirer cette image"
                            @click="removeNewMedia(index)">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Aperçu des images existantes -->
                <div v-if="form.existingMedias && form.existingMedias.length" class="mt-3 flex flex-wrap gap-3">
                    <div v-for="(media, idx) in form.existingMedias" :key="'existing-' + idx" class="relative">
                        <img :src="getStorageUrl(media)" alt="Image existante"
                            class="w-28 h-28 object-cover rounded-xl border border-gray-200" />
                        <button type="button"
                            class="absolute -top-2 -right-2 w-7 h-7 grid place-items-center bg-white rounded-full shadow text-red-500 hover:bg-red-50 cursor-pointer"
                            aria-label="Retirer cette image"
                            @click="removeExistingMedia(idx)">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tags -->
            <div>
                <span class="block text-sm font-medium text-blue-night mb-1.5">Tags</span>

                <div v-if="form.tags.length" class="flex flex-wrap gap-2 mb-2">
                    <span v-for="(tag, index) in form.tags" :key="'tag-' + index"
                        class="inline-flex items-center gap-2 bg-orange-primary/10 text-orange-primary text-sm font-medium rounded-full pl-3.5 pr-2 py-1.5">
                        #{{ tag }}
                        <button type="button"
                            class="w-5 h-5 grid place-items-center rounded-full hover:bg-orange-primary/20 cursor-pointer"
                            :aria-label="`Retirer le tag ${tag}`"
                            @click="removeTag(index)">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </span>
                </div>

                <div class="relative">
                    <BaseInput
                        :modelValue="form.tagInput"
                        placeholder="Ajoute un tag puis Entrée ou virgule"
                        icon="fa-solid fa-hashtag"
                        @update:modelValue="onTagInput"
                        @keydown="onTagKeydown"
                    />
                    <Transition name="select-pop">
                        <ul v-if="showSuggestions && suggestions.length"
                            class="absolute z-50 mt-2 w-full max-h-40 overflow-auto rounded-xl border border-gray-100 bg-white py-1.5 shadow-xl shadow-blue-night/10">
                            <li v-for="(suggest, index) in suggestions" :key="'sugg-' + index"
                                class="mx-1.5 rounded-lg px-3 py-2 text-sm text-blue-night hover:bg-orange-primary/10 hover:text-orange-primary cursor-pointer transition-colors"
                                @click="addTag(suggest)">
                                #{{ suggest }}
                            </li>
                        </ul>
                    </Transition>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-2">
                <BaseButton variant="ghost" :shine="false" @click="closeModal">Annuler</BaseButton>
                <BaseButton type="submit" variant="primary" :loading="submitting">
                    {{ form.id ? 'Mettre à jour' : 'Publier' }}
                </BaseButton>
            </div>
        </form>
    </BaseModal>
</template>

<script setup>
import { getStorageUrl } from '@/config';
import { ref, watch } from 'vue';
import { useUserStore } from '@/stores/user';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';
import api from '@/services/apiService';
import BaseModal from '@/components/ui/BaseModal.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseTextarea from '@/components/ui/BaseTextarea.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const props = defineProps({
    isOpen: Boolean,
    editedReview: Object,
});

const emit = defineEmits(['close', 'publish', 'update']);

const router = useRouter();
const userStore = useUserStore();
const submitting = ref(false);

const emptyForm = () => ({
    content: '',
    link: '',
    media: [],
    mediaPreview: [],
    existingMedias: [],
    tags: [],
    tagInput: '',
});

const form = ref(emptyForm());

const suggestions = ref([]);
const showSuggestions = ref(false);
let suggestionsTimer = null;

// Suggestions de tags depuis le backend (avec debounce)
const fetchTagSuggestions = async (query) => {
    if (!query || query.trim().length === 0) {
        suggestions.value = [];
        return;
    }
    try {
        const res = await api.get(`/tags?search=${encodeURIComponent(query)}`);
        const data = res.data?.data || res.data || [];
        suggestions.value = data
            .map((tag) => (typeof tag === 'string' ? tag : tag.name || ''))
            .filter(Boolean);
    } catch {
        suggestions.value = [];
    }
};

// Normaliser les tags reçus (objets {name} ou chaînes)
function normalizeTags(raw) {
    if (raw == null) return [];
    if (!Array.isArray(raw)) return [];
    return raw
        .map((item) => {
            if (typeof item === 'string') return item.trim();
            if (item && typeof item === 'object' && item.name) return String(item.name).trim();
            return '';
        })
        .filter(Boolean);
}

watch(
    () => props.editedReview,
    (newReview) => {
        if (newReview) {
            let existing = [];
            if (Array.isArray(newReview.medias)) {
                existing = newReview.medias;
            } else if (typeof newReview.medias === 'string' && newReview.medias.length > 0) {
                try {
                    existing = JSON.parse(newReview.medias);
                } catch {
                    existing = [];
                }
            }
            form.value = {
                id: newReview.id,
                content: newReview.content || '',
                link: newReview.link || '',
                media: [],
                mediaPreview: [],
                existingMedias: (existing || []).filter((m) => typeof m === 'string' && m.length > 0),
                tags: normalizeTags(newReview.tags),
                tagInput: '',
            };
        } else {
            form.value = emptyForm();
        }
    },
    { immediate: true }
);

// Ajout de nouveaux fichiers
const submitMedia = (event) => {
    const files = event.target.files;
    if (files) {
        const list = Array.from(files);
        form.value.media = [...form.value.media, ...list];
        form.value.mediaPreview = [
            ...form.value.mediaPreview,
            ...list.map((file) => URL.createObjectURL(file)),
        ];
    }
    event.target.value = '';
};

const removeNewMedia = (index) => {
    URL.revokeObjectURL(form.value.mediaPreview[index]);
    form.value.media.splice(index, 1);
    form.value.mediaPreview.splice(index, 1);
};

const removeExistingMedia = (idx) => {
    form.value.existingMedias.splice(idx, 1);
};

// Gestion des tags
const addTag = (raw) => {
    if (!raw) return;
    const value = String(raw).trim().replace(/^#/, '');
    if (!value) return;
    const exists = form.value.tags.find((t) => t.toLowerCase() === value.toLowerCase());
    if (!exists) form.value.tags.push(value);
    form.value.tagInput = '';
    suggestions.value = [];
    showSuggestions.value = false;
};

const removeTag = (index) => {
    form.value.tags.splice(index, 1);
};

const onTagInput = (value) => {
    form.value.tagInput = value;
    clearTimeout(suggestionsTimer);
    suggestionsTimer = setTimeout(() => fetchTagSuggestions(value), 300);
    showSuggestions.value = true;
};

const onTagKeydown = (e) => {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        if (form.value.tagInput.trim()) addTag(form.value.tagInput);
    } else if (e.key === 'Backspace' && !form.value.tagInput) {
        if (form.value.tags.length) form.value.tags.pop();
    }
};

// Soumission
const submitReview = async () => {
    if (!userStore.isAuthenticated) {
        Swal.fire({
            icon: 'error',
            title: 'Connexion requise',
            text: 'Tu dois être connecté pour publier une review.',
            confirmButtonColor: '#FF6B35',
        });
        router.push('/login');
        return;
    }

    if (!form.value.content.trim()) {
        Swal.fire({
            icon: 'error',
            title: 'Review vide',
            text: 'Impossible de publier une review sans contenu.',
            confirmButtonColor: '#FF6B35',
        });
        return;
    }

    const isEdit = Boolean(form.value.id);
    const reviewData = {
        content: form.value.content,
        link: form.value.link,
        medias: form.value.media,
        existingMedias: form.value.existingMedias,
        tags: form.value.tags,
    };

    submitting.value = true;
    try {
        // Le parent et le store gèrent l'appel API et le feedback succès/erreur
        if (isEdit) {
            emit('update', reviewData);
        } else {
            emit('publish', reviewData);
        }
        closeModal();
    } finally {
        submitting.value = false;
    }
};

const closeModal = () => {
    emit('close');
    form.value = emptyForm();
    showSuggestions.value = false;
};
</script>

<style scoped>
.select-pop-enter-active,
.select-pop-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.select-pop-enter-from,
.select-pop-leave-to {
    opacity: 0;
    transform: translateY(-6px) scale(0.98);
}
</style>
