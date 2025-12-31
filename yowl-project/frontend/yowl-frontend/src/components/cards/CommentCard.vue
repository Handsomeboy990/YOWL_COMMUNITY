<template>
    <div class="mt-6 space-y-6 ">
        <div class="border-t pt-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-2">
                <p class="text-[12px] text-gray-500">
                    Commented by <span class="font-semibold">{{ comment.user.username }}</span> on {{ (new
                        Date(comment.created_at)).toString() }}
                </p>
                <div v-if="comment.user.id == user?.id" class="flex gap-x-2">
                    <button @click="toggleEdit"
                        class="cursor-pointer text-white text-[12px] p-2 rounded-full bg-[#1E2A38] hover:translate-y-[-4px] duration-200"><i
                            class="fa-solid fa-pen-to-square"></i></button>
                    <button @click="deleteComment"
                        class="cursor-pointer text-white text-[12px] rounded-full p-2 bg-red-500 hover:translate-y-[-4px] duration-200"><i
                            class="fa-solid fa-trash"></i></button>
                </div>
            </div>

            <p class="mt-2 text-lg">{{ comment.content }}</p>


            <!-- Actions -->
            <footer class="flex items-center justify-between pt-4 border-t border-gray-200">
                <div class="flex items-center space-x-4">
                    <!-- Like -->
                    <button @click="toggleReaction('like')" :class="[
                        'cursor-pointer hover:translate-y-[-3px] flex items-center space-x-1 transition-colors duration-200',
                        store.comments[index].nb_like
                            ? 'text-[#FF6B35]'
                            : 'text-gray-500 hover:text-[#FF6B35]'
                    ]">
                        <div class="w-8 h-8 bg-orange-primary mr-2 rounded-full flex items-center justify-center">
                            <i :class="[
                                store.comments[index].nb_like
                                    ? 'fa-solid fa-thumbs-up'
                                    : 'fa-regular fa-thumbs-up',
                                'w-4 h-4 text-white'
                            ]"></i>
                        </div>

                        {{ comment.nb_like }}
                    </button>

                    <!-- Dislike -->
                    <button @click="toggleReaction('dislike')" :class="[
                        'cursor-pointer hover:translate-y-[3px] flex items-center space-x-1 transition-colors duration-200',
                        store.comments[index].nb_dislike
                            ? 'text-blue-700'
                            : 'text-gray-500 hover:text-blue-500'
                    ]">
                        <div class="w-8 h-8 bg-[#1E2A38] mr-2 rounded-full flex items-center justify-center">
                            <i :class="[
                                store.comments[index].nb_dislike
                                    ? 'fa-solid fa-thumbs-down'
                                    : 'fa-regular fa-thumbs-down',
                                'w-4 h-4 text-white'
                            ]"></i>
                        </div>
                        {{ comment.nb_dislike }}
                    </button>

                    <!-- Reply -->
                    <button @click="toggleReply"
                        class="cursor-pointer flex items-center space-x-1 text-[12px] text-gray-700 hover:text-blue-night transition-colors">
                        <span class="font-roboto text-caption">
                            {{ isReplying ? 'Cancel' : 'Reply' }}
                        </span>
                        <i class="fa-solid fa-reply w-4 h-4 mr-1"></i>
                    </button>
                </div>

                <!-- Show / Hide replies -->
                <div v-if="store.comments.filter(reply => reply.parent_id == props.comment.id) && store.comments.filter(reply => reply.parent_id == props.comment.id).length > 0"
                    class="flex items-center space-x-2">
                    <button @click="toggleReplies"
                        class="cursor-pointer font-roboto text-caption text-blue-night hover:underline scale-[.9]">
                        {{showReplies ? 'Hide replies' : `Show ${store.comments.filter(reply => reply.parent_id ==
                            props.comment.id).length} replies`}}
                    </button>
                </div>
            </footer>

            <!-- Reply form -->
            <CommentForm v-if="isReplying" @submitComment="addReply" :content="content" />

            <!-- Edit form -->
            <CommentForm v-if="isEditing" @editComment="editComment" :content="content" :id="comment.id" />

            <!-- Nested replies (recursive rendering) -->
            <div v-if="showReplies" class="ml-10 mt-4 space-y-4">
                <CommentCard v-for="reply in store.comments.filter(reply => reply.parent_id == props.comment.id)"
                    :key="reply.id" @deleteComment="deleteComment" :comment="reply" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, watch } from "vue";
import CommentForm from "../layouts/CommentForm.vue";
import { useCommentStore } from "@/stores/comment";
import { useUserStore } from "@/stores/user";
import Swal from "sweetalert2";


const storeUser = useUserStore()
const user = storeUser.user
// props : a comment with comments
const props = defineProps({
    comment: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(["editComment", "deleteComment"]);

const store = useCommentStore();
const isReplying = ref(false);
const showReplies = ref(false);
const isEditing = ref(false)
const content = ref("")
const replies = ref([])
const index = store.comments.findIndex(comment => comment.id == props.comment.id)

onMounted(async () => {
    replies.value = store.comments.filter(reply => reply.parent_id == props.comment.id)
})

watch(store.comments, () => {

    replies.value = store.comments.filter(reply => reply.parent_id == props.comment.id)
})

// toggle reply form
const toggleReply = () => {
    isReplying.value = !isReplying.value;
    content.value = ""
};

const toggleEdit = () => {
    isEditing.value = !isEditing.value
    content.value = props.comment.content

};



// toggle show/hide replies
const toggleReplies = () => {
    showReplies.value = !showReplies.value;
};


// reply to comment

const addReply = (replyContent) => {

    store.addComment({
        review_id: props.comment.review_id,
        parent_id: props.comment.id,
        content: replyContent
    })
    toggleReply()

};

const editComment = (commentContent) => {

    Swal.fire({
        icon: "success",
        title: "Comment edited!",
        timer: 1200,
        showConfirmButton: false
    })

    if (showReplies.value) {
        emit("editComment", commentContent)
    } else {
        store.updateComment({ content: commentContent.content }, props.comment.id)

    }
    toggleEdit()
}
/*   store.updateComment({ content: commentContent }, props.comment.id)
  toggleEdit() */


const deleteComment = () => {
    if (showReplies.value) {
        Swal.fire({
            title: "Confirm deletion",
            text: "Do you really want to delete this comment ?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#FF6B35",
            cancelButtonColor: "#1E2A38",
            confirmButtonText: "Yes, delete"
        }).then(async (result) => {
            if (result.isConfirmed) {
                emit("deleteComment", props.comment.id)

                Swal.fire({
                    title: "Deleted!",
                    text: "The comment has been deleted.",
                    icon: "success",
                    confirmButtonColor: "#FF6B35"
                });
            }
        });
    } else {
        Swal.fire({
            title: "Confirm deletion",
            text: "Do you really want to delete this comment ?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#FF6B35",
            cancelButtonColor: "#1E2A38",
            confirmButtonText: "Yes, delete"
        }).then(async (result) => {
            if (result.isConfirmed) {
                store.deleteComment(props.comment.id)


                Swal.fire({
                    title: "Deleted!",
                    text: "The comment has been deleted.",
                    icon: "success",
                    confirmButtonColor: "#FF6B35"
                });
            }
        });


    }
    /* store.deleteComment(props.comment.id) */

}

// Fonction Like / Dislike
const toggleReaction = async (reaction) => {
    if (!storeUser.user?.id) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: "You must be logged in to react.",
            showConfirmButton: true,
            confirmButtonColor: "#FF6B35"
        });
        return
    }

    try {
        const response = await store.reactToComment(props.comment.id, reaction)

        

        // Mise à jour instantanée
        store.comments[index].nb_like = response.data.nb_like
        store.comments[index].nb_dislike = response.data.nb_dislike
        store.comments[index].user_reaction = response.data.user_reaction

        /* localReview.value = {
          ...localReview.value,
          nb_like: response.data.nb_like,
          nb_dislike: response.data.nb_dislike,
          user_reaction: response.data.user_reaction
        } */
    } catch {
        // Silent error handling
    }
}
</script>
