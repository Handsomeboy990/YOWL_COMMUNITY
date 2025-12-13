<template>
      <div class="mt-10 text-right pb-20">
      <button class="cursor-pointer bg-red-600 text-white px-6 py-2 rounded-lg font-roboto text-[14px] hover:bg-red-700 transition" @click="leave">
        Leave YOWL community
      </button>
    </div>
</template>

<script setup>
  import router from '@/router';
import { useUserStore } from '@/stores/user';
import Swal from 'sweetalert2';

  const userStore = useUserStore();
  const leave = async () => {
     Swal.fire({
    title: "Confirm deletion",
    text: "Do you really want to leave YOWL Comunity ? Know that this action is irreversible and we'll regret you.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#FF6B35",
    cancelButtonColor: "#1E2A38",
    confirmButtonText: "Yes, I want to leave the community"
  }).then(async (result) => {
    if (result.isConfirmed) {
          await userStore.leaveCommunity();
    router.push('/')


      Swal.fire({
        title: "Leaved!",
        text: "We'll miss you.",
        icon: "success",
        confirmButtonColor: "#FF6B35"
      });
    }
  });


  }
</script>
