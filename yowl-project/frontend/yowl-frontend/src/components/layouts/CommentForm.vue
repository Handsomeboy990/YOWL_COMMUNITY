<template>
  <div class="mt-4 border rounded-md p-1 flex items-center gap-2 scale-[.9]">
    <input v-model="newComment" type="text" placeholder="Something to say?" class="flex-1 outline-none p-3" />
    <button @click="submit"
      class="cursor-pointer bg-orange-500 text-white p-2 rounded-full hover:translate-y-[-4px] duration-200">
      <i class="fa-solid fa-paper-plane "></i>
    </button>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useUserStore } from "@/stores/user";
import router from "@/router";
import Swal from "sweetalert2";

const props = defineProps({
  content: String,
  id: Number
})

const newComment = ref("");
newComment.value = props.content

const userStore = useUserStore()
// event to parent
const emit = defineEmits(["submitComment", "editComment"]);



const submit = () => {
  let errorMessage
  //if user not loggedin
  if (!userStore.isAuthenticated) {
    Swal.fire({
      icon: 'error',
      title: 'Oops...',
      text: "You need to be logged before to do this",
      showConfirmButton: true,
      confirmButtonColor: "#FF6B35"
    });
    router.push('/login')
    return;
  }

  //if logged

  if (!newComment.value.trim()) {
    errorMessage = "You can't publish an empty review";
    console.log(errorMessage);
    return;
  }

  if (newComment.value.trim() !== "") {

    if (!props.content) {
      emit("submitComment", newComment.value.trim());
      Swal.fire({
  icon: "success",
  title: "Comment added!",
  timer: 1500,
  showConfirmButton: false
})

      newComment.value = "";      
    } else {
      emit("editComment", { content: newComment.value.trim(), id: props.id });
      Swal.fire({
  icon: "success",
  title: "Comment updated!",
  timer: 1500,
  showConfirmButton: false
})

      newComment.value = "";
    }
  }
};
</script>
