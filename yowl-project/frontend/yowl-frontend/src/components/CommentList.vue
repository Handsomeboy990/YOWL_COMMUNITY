<template>
    <div>
        <!-- first comment form -->
        <CommentForm @submitComment="addComment" />

        <!-- comment list -->
        <div class="mt-6">
            <CommentCard v-for="comment in store.comments.filter(comment => ((comment.review_id == props.reviewId) && (!comment.parent_id)))" @deleteComment="deleteComment" @editComment="editComment" :key="comment.id" :comment="comment" />
        </div>

        <!-- Load more -->
        
    </div>
</template>

<script setup>
import { ref, watch, /* onBeforeMount */ } from "vue";
import CommentCard from "./cards/CommentCard.vue";
import CommentForm from "./layouts/CommentForm.vue";
import { useCommentStore } from "@/stores/comment";


const props = defineProps({
    "comments": Array,
    "reviewId": Number
}
)
const emit = defineEmits(["childChanged"]);

const store = useCommentStore();


const commentsArr = ref([]);
commentsArr.value = /* props.comments */ store.comments.filter(comment => ((comment.review_id == props.reviewId) && (!comment.parent_id)))

watch(store.comments, 
() => {
    
    commentsArr.value = /* props.comments */ store.comments.filter(comment => ((comment.review_id == props.reviewId) && (!comment.parent_id)))
    
}, {
    deep:true,
    immediate: true
})

// comments list
/* 
onBeforeMount(async () => {    
    await store.getCommentsByReview()
    comments.value = store.comments.filter(comment => comment.review_id == props.review_id)
    
}) */

// visible comments
/* const visibleCount = ref(5); */

// comment list
/* const visibleComments = computed(() => commentsArr.value.slice(0, visibleCount.value));


// show more comments
const showMore = () => {
    visibleCount.value += 5;
}; */

// add comment
const addComment = (commentContent) => {
    store.addComment({
        review_id: props.reviewId,
        content: commentContent
    }
    )

    
};


const editComment = (comment) => {
    
    store.updateComment({ content: comment.content }, comment.id)
    emit("childChanged")

    
};

const deleteComment = (commentId) => {
    store.deleteComment(commentId)
    
    emit("childChanged")
}

</script>
