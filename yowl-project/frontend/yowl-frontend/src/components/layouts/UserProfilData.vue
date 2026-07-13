<template>
    <div class="flex items-center pt-15 mb-8">
        <!-- Avatar -->
        <img v-if="userStore.user?.picture"
            class="w-20 h-20 rounded-full object-cover border-2 border-orange-primary/30"
            :src="getStorageUrl(userStore.user.picture)" alt="Photo de profil">
        <span v-else
            class="w-20 h-20 rounded-full bg-blue-night grid place-items-center text-white text-2xl font-poppins font-bold">
            {{ initials }}
        </span>

        <!-- Informations -->
        <div class="pl-4">
            <h1 class="font-poppins font-bold text-2xl md:text-[32px] text-blue-night leading-tight">
                {{ userStore.user?.fullname || 'Membre YOWL' }}
            </h1>
            <p class="font-roboto text-[14px] text-gray-500">
                Membre depuis {{ memberSince }}
            </p>
        </div>

        <!-- Bouton de modification -->
        <BaseButton class="ml-auto" variant="night" size="sm" icon="fa-solid fa-pen" @click="isOpen = true">
            Modifier
        </BaseButton>
    </div>

    <!-- Modale d'édition -->
    <EditProfilModal :isOpen="isOpen" @close="isOpen = false" />
</template>

<script setup>
import { getStorageUrl } from '@/config';
import EditProfilModal from '../pages/profil/EditProfilModal.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { computed, ref } from 'vue';
import { useUserStore } from '@/stores/user';

const userStore = useUserStore();
const isOpen = ref(false);

const initials = computed(() => {
    const name = userStore.user?.username || userStore.user?.fullname || '';
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
});

const memberSince = computed(() => {
    if (!userStore.user?.created_at) return '';
    return new Date(userStore.user.created_at).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});
</script>
