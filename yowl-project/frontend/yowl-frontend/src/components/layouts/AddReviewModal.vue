<template>
    <BaseModal :isOpen="isOpen" :title="form.id ? t('compose.editTitle') : t('compose.newTitle')" size="lg" @close="closeModal">
        <form class="space-y-5 text-blue-night" @submit.prevent="submitReview">
            <!-- Contenu -->
            <BaseTextarea
                v-model="form.content"
                :label="t('compose.contentLabel')"
                :placeholder="t('compose.contentPlaceholder')"
                :rows="5"
                required
            />

            <!-- Lien -->
            <BaseInput
                v-model="form.link"
                :label="t('compose.linkLabel')"
                type="url"
                placeholder="https://exemple.com"
                icon="fa-solid fa-link"
                :hint="t('compose.linkHint')"
            />

            <!-- Rappel de l'hôte cité. L'aperçu Open Graph complet est
                 récupéré côté serveur après publication : encadrer ici une
                 page arbitraire lui donnerait la main sur ce formulaire. -->
            <div v-if="linkHost" class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                <span class="w-9 h-9 shrink-0 rounded-lg bg-orange-primary/10 grid place-items-center text-orange-text">
                    <i class="fa-solid fa-link" aria-hidden="true"></i>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-medium text-blue-night truncate">{{ linkHost }}</span>
                    <span class="block text-xs text-gray-500">{{ t('compose.previewLater') }}</span>
                </span>
            </div>

            <!-- Doublon de lien. Une proposition, jamais un blocage : deux
                 personnes ont le droit d'ouvrir deux sujets sur un même
                 article, mais la plupart du temps elles veulent le même. -->
            <div v-if="duplicates.length"
                class="rounded-xl border border-sky-200 bg-sky-50/70 p-4 animate-fade-in-up">
                <p class="text-sm font-medium text-sky-900">
                    {{ duplicates.length > 1
                        ? t('compose.duplicateMany', { count: duplicates.length })
                        : t('compose.duplicateOne') }}
                </p>
                <p class="mt-1 text-xs text-sky-800">{{ t('compose.duplicateHint') }}</p>
                <ul class="mt-3 space-y-2">
                    <li v-for="existing in duplicates" :key="existing.id">
                        <a :href="'/reviews/' + existing.id" target="_blank" rel="noopener"
                            class="group flex items-start gap-3 rounded-lg bg-white border border-sky-100 p-3 hover:border-sky-300 transition-colors">
                            <img :src="getStorageUrl(existing.user?.picture)" alt=""
                                class="w-8 h-8 rounded-full object-cover shrink-0" />
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs font-medium text-blue-night">
                                    {{ existing.user?.fullname || existing.user?.username }}
                                </span>
                                <span class="block text-xs text-gray-600 line-clamp-2">{{ existing.content }}</span>
                                <span class="mt-1 block text-[11px] text-gray-500">
                                    {{ t('compose.replyCount', existing.comments_count, { count: existing.comments_count }) }}
                                </span>
                            </span>
                            <i class="fa-solid fa-arrow-right text-sky-300 group-hover:text-sky-600 transition-colors mt-1"></i>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Médias -->
            <div>
                <span class="block text-sm font-medium text-blue-night mb-1.5">{{ t('compose.imagesLabel') }}</span>
                <label
                    class="group flex flex-col items-center justify-center gap-2 w-full rounded-xl border-2 border-dashed border-gray-300 hover:border-orange-primary bg-gray-50 hover:bg-orange-50/50 px-4 py-6 cursor-pointer transition-colors"
                >
                    <i class="fa-solid fa-cloud-arrow-up text-2xl text-gray-500 group-hover:text-orange-text transition-colors"></i>
                    <span class="text-sm text-gray-500">{{ t('compose.imagesDrop') }}</span>
                    <input type="file" accept="image/*" multiple class="hidden" @change="submitMedia" />
                </label>

                <!-- Aperçu des nouveaux fichiers -->
                <div v-if="form.mediaPreview.length" class="mt-3 flex flex-wrap gap-3">
                    <div v-for="(src, index) in form.mediaPreview" :key="'new-' + index" class="relative group">
                        <img :src="src" alt="Aperçu" class="w-28 h-28 object-cover rounded-xl border border-gray-200" />
                        <button type="button"
                            class="absolute -top-2 -right-2 w-7 h-7 grid place-items-center bg-white rounded-full shadow text-red-500 hover:bg-red-50 cursor-pointer"
                            :aria-label="t('compose.removeImage')"
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
                            :aria-label="t('compose.removeImage')"
                            @click="removeExistingMedia(idx)">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tags -->
            <div>
                <span class="block text-sm font-medium text-blue-night mb-1.5">{{ t('compose.tagsLabel') }}</span>

                <div v-if="form.tags.length" class="flex flex-wrap gap-2 mb-2">
                    <span v-for="(tag, index) in form.tags" :key="'tag-' + index"
                        class="inline-flex items-center gap-2 bg-orange-primary/10 text-orange-text text-sm font-medium rounded-full pl-3.5 pr-2 py-1.5">
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
                        :placeholder="t('compose.tagsPlaceholder')"
                        icon="fa-solid fa-hashtag"
                        @update:modelValue="onTagInput"
                        @keydown="onTagKeydown"
                    />
                    <Transition name="select-pop">
                        <ul v-if="showSuggestions && suggestions.length"
                            class="absolute z-50 mt-2 w-full max-h-40 overflow-auto rounded-xl border border-gray-100 bg-white py-1.5 shadow-xl shadow-blue-night/10">
                            <li v-for="(suggest, index) in suggestions" :key="'sugg-' + index"
                                class="mx-1.5 rounded-lg px-3 py-2 text-sm text-blue-night hover:bg-orange-primary/10 hover:text-orange-text cursor-pointer transition-colors"
                                @click="addTag(suggest)">
                                #{{ suggest }}
                            </li>
                        </ul>
                    </Transition>
                </div>
            </div>

            <!-- Programmation -->
            <div class="rounded-xl border border-gray-200 overflow-hidden">
                <button type="button"
                    class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-gray-50 transition-colors cursor-pointer"
                    :aria-expanded="scheduling"
                    @click="toggleScheduling">
                    <span class="flex items-center gap-2.5 text-sm text-blue-night">
                        <i class="fa-regular fa-clock text-gray-500" aria-hidden="true"></i>
                        {{ form.scheduled_for
                            ? t('compose.scheduledFor', { date: formatSchedule(form.scheduled_for) })
                            : t('compose.schedule') }}
                    </span>
                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition-transform"
                        :class="scheduling ? 'rotate-180' : ''" aria-hidden="true"></i>
                </button>

                <div v-if="scheduling" class="border-t border-gray-100 px-4 py-4 space-y-3">
                    <label class="block text-sm font-medium text-blue-night" :for="scheduleId">
                        {{ t('compose.scheduleField') }}
                    </label>
                    <input :id="scheduleId" v-model="form.scheduled_for" type="datetime-local" :min="minSchedule"
                        class="w-full rounded-xl border-2 border-gray-200 px-4 py-2.5 text-blue-night outline-none focus:border-orange-primary transition-colors" />
                    <p class="text-xs text-gray-500">{{ t('compose.scheduleHint') }}</p>
                    <button v-if="form.scheduled_for" type="button"
                        class="text-xs font-medium text-orange-text hover:underline cursor-pointer"
                        @click="form.scheduled_for = ''">
                        {{ t('compose.publishNow') }}
                    </button>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-2">
                <BaseButton variant="ghost" :shine="false" @click="closeModal">{{ t('common.cancel') }}</BaseButton>
                <BaseButton type="submit" variant="primary" :loading="submitting">
                    {{ submitLabel }}
                </BaseButton>
            </div>
        </form>
    </BaseModal>
</template>

<script setup>
import { getStorageUrl } from '@/config';
import { computed, ref, useId, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useNotify } from '@/composables/useNotify';
import { useUserStore } from '@/stores/user';
import { useRouter } from 'vue-router';
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

const { t, locale } = useI18n();
const router = useRouter();
const userStore = useUserStore();
const notify = useNotify();
const submitting = ref(false);

const emptyForm = () => ({
    content: '',
    link: '',
    media: [],
    mediaPreview: [],
    existingMedias: [],
    tags: [],
    tagInput: '',
    scheduled_for: '',
});

const form = ref(emptyForm());

const suggestions = ref([]);
const showSuggestions = ref(false);
let suggestionsTimer = null;

const duplicates = ref([]);
const scheduling = ref(false);
const scheduleId = useId();
let linkTimer = null;

const linkHost = computed(() => {
    try {
        return new URL(form.value.link).hostname.replace(/^www\./, '');
    } catch {
        return '';
    }
});

// L'attribut min du champ attend le format local sans fuseau, pas un ISO.
const minSchedule = computed(() => {
    const dans5min = new Date(Date.now() + 5 * 60 * 1000);
    dans5min.setMinutes(dans5min.getMinutes() - dans5min.getTimezoneOffset());
    return dans5min.toISOString().slice(0, 16);
});

const submitLabel = computed(() => {
    if (form.value.id) return t('compose.update');
    return form.value.scheduled_for ? t('compose.scheduleAction') : t('common.publish');
});

const formatSchedule = (value) =>
    new Date(value).toLocaleString(locale.value === 'en' ? 'en-GB' : 'fr-FR', {
        day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit',
    });

const toLocalInput = (value) => {
    const date = new Date(value);
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().slice(0, 16);
};

const toggleScheduling = () => {
    scheduling.value = !scheduling.value;
};

/**
 * Cherche les discussions deja ouvertes sur la meme adresse.
 *
 * Attend que la saisie se pose : interroger a chaque frappe enverrait une
 * requete par caractere pour une adresse encore incomplete.
 */
const lookForDuplicates = async (link) => {
    if (!link || !linkHost.value) {
        duplicates.value = [];
        return;
    }

    try {
        const response = await api.get('/liens/existant', { params: { link } });
        duplicates.value = response.data.data ?? [];
    } catch {
        // Une proposition manquante n'empeche pas de publier.
        duplicates.value = [];
    }
};

watch(() => form.value.link, (link) => {
    clearTimeout(linkTimer);
    linkTimer = setTimeout(() => lookForDuplicates(link), 600);
});

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
                // Le champ datetime-local n'accepte pas le fuseau : on lui
                // donne les seize premiers caracteres du format local.
                scheduled_for: newReview.scheduled_for
                    ? toLocalInput(newReview.scheduled_for)
                    : '',
            };
            scheduling.value = Boolean(newReview.scheduled_for);
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
        notify.info('Connexion requise', 'Tu dois être connecté pour publier un avis.');
        router.push('/login');
        return;
    }

    if (!form.value.content.trim()) {
        notify.warning('Avis vide', 'Impossible de publier un avis sans contenu.');
        return;
    }

    const isEdit = Boolean(form.value.id);
    const reviewData = {
        content: form.value.content,
        link: form.value.link,
        medias: form.value.media,
        existingMedias: form.value.existingMedias,
        tags: form.value.tags,
        // Le champ rend une heure locale sans fuseau. L'envoyer telle quelle
        // la ferait lire dans le fuseau du serveur, soit un decalage silencieux
        // pour tout membre qui n'est pas a l'heure UTC.
        scheduled_for: form.value.scheduled_for
            ? new Date(form.value.scheduled_for).toISOString()
            : '',
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
    scheduling.value = false;
    duplicates.value = [];
    clearTimeout(linkTimer);
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
