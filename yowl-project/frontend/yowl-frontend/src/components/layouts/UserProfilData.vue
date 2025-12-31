<template>
    <div class="flex items-center pt-15 mb-8">
        <!-- Avatar -->
        <div v-if="userStore.user.picture">
            <img class="w-20 h-20 rounded-full flex items-center justify-center cursor-pointer"
                :src="getStorageUrl(userStore.user.picture)" alt="profile picture">

        </div>
        <div v-else class="items-center gap-4 mr-4 rounded-full">
            <img :src="user.avatar" alt="Profile Avatar" class="w-24 h-24 rounded-full object-cover border" />
        </div>

        <!-- User Info -->
        <div class="pl-2">
            <h1 class="font-poppins font-bold text-[32px] text-blue-night leading-tight">
                {{ user.name }}
            </h1>
            <p class="font-roboto text-[14px] text-gray-500">
                Member since {{ user.memberSince }}
            </p>
        </div>

        <!-- Edit Button -->
        <button @click="openModal"
            class="cursor-pointer ml-auto bg-blue-night text-white px-6 py-2 rounded-lg font-roboto text-[14px] hover:bg-[#FF6B35] transition">
            Edit
        </button>
    </div>

    <!-- Edit modal -->
    <EditProfilModal :isOpen="isOpen" @close="isOpen = false" />
</template>

<script setup>
import { getStorageUrl } from '@/config';
import EditProfilModal from '../pages/profil/EditProfilModal.vue';
import defaultAvatar from '@/assets/logo.png';
import { ref, computed } from 'vue';
import { useUserStore } from '@/stores/user';

const userStore = useUserStore();
// modal state
const isOpen = ref(false)

// function to open edit modal
const openModal = () => {
    isOpen.value = true
}


const dateFormat = computed(() => {
    const date = new Date(userStore.user.created_at)
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
})

const user = ref({
  name: userStore.user?.fullname|| 'Unknown',
  memberSince:  userStore.user? dateFormat.value : '',
  avatar: defaultAvatar
});


</script>
