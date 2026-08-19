<template>
    <div class="mt-6 space-y-6">
        <div class="border-t border-gray-200 pt-4">
            <!-- En-tête -->
            <div class="flex justify-between items-center mb-2">
                <p class="text-[12px] text-gray-500">
                    Commenté par <span class="font-semibold">{{ comment.user?.username }}</span>
                    le {{ dateFormatted }}
                </p>
                <div v-if="comment.user?.id === user?.id" class="flex gap-x-2">
                    <button
                        class="cursor-pointer text-white text-[12px] p-2 rounded-full bg-blue-night hover:-translate-y-1 duration-200"
                        aria-label="Modifier le commentaire"
                        @click="toggleEdit">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button
                        class="cursor-pointer text-white text-[12px] rounded-full p-2 bg-red-500 hover:-translate-y-1 duration-200"
                        aria-label="Supprimer le commentaire"
                        @click="deleteComment">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>

                <button v-else-if="canReport" type="button"
                    class="cursor-pointer text-gray-400 text-[12px] rounded-full p-2 hover:text-red-500 hover:bg-red-50 duration-200"
                    aria-label="Signaler le commentaire" title="Signaler"
                    @click="isReportOpen = true">
                    <i class="fa-solid fa-flag"></i>
                </button>
            </div>

            <ReportModal :is-open="isReportOpen" type="comment" :id="comment.id" @close="isReportOpen = false" />

            <p class="mt-2 text-base text-gray-800">{{ comment.content }}</p>

            <!-- Actions -->
            <footer class="flex items-center justify-between pt-4">
                <div class="flex items-center space-x-4">
                    <!-- J'aime -->
                    <button :class="[
                        'cursor-pointer hover:-translate-y-0.5 flex items-center space-x-1 transition-all duration-200',
                        currentComment?.user_reaction === 'like'
                            ? 'text-orange-primary font-semibold'
                            : 'text-gray-500 hover:text-orange-primary'
                    ]" @click="toggleReaction('like')">
                        <span class="w-8 h-8 bg-orange-primary mr-2 rounded-full grid place-items-center">
                            <i :class="[
                                currentComment?.user_reaction === 'like'
                                    ? 'fa-solid fa-thumbs-up'
                                    : 'fa-regular fa-thumbs-up',
                                'text-white text-sm'
                            ]"></i>
                        </span>
                        {{ comment.nb_like }}
                    </button>

                    <!-- Je n'aime pas -->
                    <button :class="[
                        'cursor-pointer hover:translate-y-0.5 flex items-center space-x-1 transition-all duration-200',
                        currentComment?.user_reaction === 'dislike'
                            ? 'text-blue-700 font-semibold'
                            : 'text-gray-500 hover:text-blue-500'
                    ]" @click="toggleReaction('dislike')">
                        <span class="w-8 h-8 bg-blue-night mr-2 rounded-full grid place-items-center">
                            <i :class="[
                                currentComment?.user_reaction === 'dislike'
                                    ? 'fa-solid fa-thumbs-down'
                                    : 'fa-regular fa-thumbs-down',
                                'text-white text-sm'
                            ]"></i>
                        </span>
                        {{ comment.nb_dislike }}
                    </button>

                    <!-- Répondre -->
                    <button
                        class="cursor-pointer flex items-center space-x-1 text-[12px] text-gray-700 hover:text-blue-night transition-colors"
                        @click="toggleReply">
                        <span class="font-roboto text-caption">
                            {{ isReplying ? 'Annuler' : 'Répondre' }}
                        </span>
                        <i class="fa-solid fa-reply ml-1"></i>
                    </button>
                </div>

                <!-- Afficher / masquer les réponses -->
                <div v-if="replies.length > 0" class="flex items-center space-x-2">
                    <button
                        class="cursor-pointer font-roboto text-caption text-blue-night hover:underline scale-[.9]"
                        @click="toggleReplies">
                        {{ showReplies ? 'Masquer les réponses' : `Afficher ${replies.length} réponse${replies.length > 1 ? 's' : ''}` }}
                    </button>
                </div>
            </footer>

            <!-- Formulaire de réponse -->
            <CommentForm v-if="isReplying" :content="''" @submitComment="addReply" />

            <!-- Formulaire d'édition -->
            <CommentForm v-if="isEditing" :content="content" :id="comment.id" @editComment="editComment" />

            <!-- Réponses imbriquées (rendu récursif) -->
            <div v-if="showReplies" class="ml-10 mt-4 space-y-4">
                <CommentCard v-for="reply in replies" :key="reply.id" :comment="reply" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from "vue";
import CommentForm from "../layouts/CommentForm.vue";
import ReportModal from "../layouts/ReportModal.vue";
import { useCommentStore } from "@/stores/comment";
import { useUserStore } from "@/stores/user";
import { useNotify, apiErrorMessage } from '@/composables/useNotify';
import { useConfirm } from '@/composables/useConfirm';

const storeUser = useUserStore()
const user = storeUser.user

const props = defineProps({
    comment: {
        type: Object,
        required: true
    }
});

const store = useCommentStore();
const notify = useNotify();
const confirm = useConfirm();
const isReplying = ref(false);
const showReplies = ref(false);
const isEditing = ref(false)
const content = ref("")
const isReportOpen = ref(false)

// Un membre connecté signale le commentaire des autres, jamais le sien
const canReport = computed(
    () => Boolean(user?.id) && user.id !== props.comment.user?.id
);

// Toujours résoudre le commentaire courant dans le store (index jamais figé)
const currentComment = computed(() =>
    store.comments.find((c) => c.id === props.comment.id) || props.comment
);

const replies = computed(() =>
    store.comments.filter((reply) => reply.parent_id === props.comment.id)
);

const dateFormatted = computed(() => {
    const d = new Date(props.comment.created_at);
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' }) +
        ' à ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
});

const toggleReply = () => {
    isReplying.value = !isReplying.value;
};

const toggleEdit = () => {
    isEditing.value = !isEditing.value
    content.value = props.comment.content
};

const toggleReplies = () => {
    showReplies.value = !showReplies.value;
};

// Répondre au commentaire
const addReply = (replyContent) => {
    store.addComment({
        review_id: props.comment.review_id,
        parent_id: props.comment.id,
        content: replyContent
    })
    isReplying.value = false;
};

// Modifier le commentaire
const editComment = async (commentContent) => {
    await store.updateComment({ content: commentContent.content }, props.comment.id)
    notify.success('Commentaire modifié')
    isEditing.value = false;
}

// Supprimer le commentaire
const deleteComment = async () => {
    const confirmed = await confirm({
        title: 'Supprimer ce commentaire ?',
        message: 'Il disparaîtra de la discussion pour tout le monde.',
        confirmLabel: 'Supprimer',
        tone: 'danger',
    })
    if (!confirmed) return

    try {
        await store.deleteComment(props.comment.id)
        notify.success('Commentaire supprimé')
    } catch (err) {
        notify.error(apiErrorMessage(err, 'La suppression a échoué.'))
    }
}

// J'aime / Je n'aime pas
const toggleReaction = async (reaction) => {
    if (!storeUser.user?.id) {
        notify.info('Connexion requise', 'Tu dois être connecté pour réagir.')
        return
    }

    try {
        await store.reactToComment(props.comment.id, reaction)
    } catch {
        // Erreur silencieuse : les compteurs restent inchangés
    }
}
</script>
