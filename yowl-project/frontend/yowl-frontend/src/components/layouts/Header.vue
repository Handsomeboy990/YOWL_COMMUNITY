<!-- eslint-disable vue/multi-word-component-names -->
<template>
    <header class="bg-orange-primary text-white fixed top-0 left-0 w-full z-50 shadow-md">
        <div class="max-w-7xl mx-auto flex items-center justify-between px-4 md:px-6 py-3 gap-4">

            <!-- Logo -->
            <router-link to="/feed" class="flex items-center space-x-2 font-poppins font-bold text-xl" @click="refreshPage">
                <img src="@/assets/logo.png" alt="Logo YOWL" class="w-8 h-8 md:w-10 md:h-10">
                <span>YOWL</span>
            </router-link>

            <!-- Bouton burger mobile -->
            <button class="md:hidden text-2xl cursor-pointer" :aria-expanded="isOpen" aria-label="Menu" @click="isOpen = !isOpen">
                <i :class="isOpen ? 'fa-solid fa-xmark' : 'fa-solid fa-bars'"></i>
            </button>

            <!-- Navigation desktop -->
            <nav class="hidden md:flex items-center space-x-6 font-semibold">
                <router-link to="/feed" class="hover:text-yellow-200 transition-colors" @click="refreshPage">Fil</router-link>
                <router-link to="/about" class="hover:text-yellow-200 transition-colors">À propos</router-link>
            </nav>

            <!-- Recherche desktop -->
            <div class="hidden md:flex flex-1 max-w-lg">
                <div class="relative w-full">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fas fa-search"></i>
                    </span>
                    <input v-model="searchQuery" type="text" placeholder="Rechercher une review, un tag, un membre..."
                        class="pl-10 pr-4 py-2 w-full bg-white rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-night/40 transition-shadow"
                        @keyup.enter="handleSearch" />
                </div>
            </div>

            <!-- Actions desktop -->
            <div class="hidden md:flex items-center gap-3">
                <BaseButton variant="night" size="sm" icon="fa-solid fa-plus" @click="openModal">
                    Publier une review
                </BaseButton>

                <!-- Profil ou connexion -->
                <div v-if="userStore.isAuthenticated" ref="dropdownRef" class="relative">
                    <button class="flex items-center gap-1.5 cursor-pointer" :aria-expanded="isDropdownOpen" @click="toggleDropdown">
                        <img v-if="userStore.user?.picture"
                            class="w-10 h-10 rounded-full object-cover ring-2 ring-white/50"
                            :src="getStorageUrl(userStore.user.picture)" alt="Photo de profil">
                        <span v-else
                            class="w-10 h-10 bg-blue-night rounded-full grid place-items-center text-white text-sm font-bold">
                            {{ userInitials }}
                        </span>
                        <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200"
                            :class="isDropdownOpen ? 'rotate-180' : ''"></i>
                    </button>

                    <!-- Menu déroulant -->
                    <Transition name="dropdown">
                        <div v-if="isDropdownOpen"
                            class="absolute right-0 mt-3 w-48 bg-white text-gray-800 rounded-xl shadow-xl shadow-blue-night/10 py-2 border border-gray-100">
                            <router-link to="/user/activity" class="flex items-center gap-2.5 px-4 py-2.5 text-sm hover:bg-gray-50" @click="isDropdownOpen = false">
                                <i class="fa-regular fa-user text-gray-400 w-4"></i> Mon profil
                            </router-link>
                            <router-link v-if="userStore.isAdmin" to="/admin" class="flex items-center gap-2.5 px-4 py-2.5 text-sm hover:bg-gray-50" @click="isDropdownOpen = false">
                                <i class="fa-solid fa-gauge-high text-gray-400 w-4"></i> Administration
                            </router-link>
                            <button class="w-full flex items-center gap-2.5 text-left px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 cursor-pointer" @click="logout">
                                <i class="fa-solid fa-arrow-right-from-bracket w-4"></i> Déconnexion
                            </button>
                        </div>
                    </Transition>
                </div>

                <div v-else>
                    <BaseButton :tag="'router-link'" :to="'/login'" variant="night" size="sm" :shine="false">
                        Connexion / Inscription
                    </BaseButton>
                </div>
            </div>
        </div>

        <!-- Menu mobile -->
        <transition name="fade">
            <div v-if="isOpen" class="md:hidden bg-blue-night text-white px-6 py-4 flex flex-col gap-4">
                <router-link to="/feed" class="hover:text-orange-300" @click="isOpen = false">Fil</router-link>
                <router-link to="/about" class="hover:text-orange-300" @click="isOpen = false">À propos</router-link>

                <!-- Recherche mobile -->
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fas fa-search"></i>
                    </span>
                    <input v-model="searchQuery" type="text" placeholder="Rechercher..."
                        class="pl-10 pr-4 py-2 w-full bg-white rounded-xl text-gray-900 text-sm focus:outline-none"
                        @keyup.enter="handleSearch" />
                </div>

                <BaseButton variant="primary" icon="fa-solid fa-plus" block @click="openModal">
                    Publier une review
                </BaseButton>

                <div v-if="userStore.isAuthenticated" class="flex flex-col gap-1">
                    <router-link to="/user/activity" class="px-2 py-2 rounded-lg hover:bg-white/10" @click="isOpen = false">Mon profil</router-link>
                    <router-link v-if="userStore.isAdmin" to="/admin" class="px-2 py-2 rounded-lg hover:bg-white/10" @click="isOpen = false">Administration</router-link>
                    <button class="text-left px-2 py-2 rounded-lg text-red-300 hover:bg-white/10 cursor-pointer" @click="logout">Déconnexion</button>
                </div>
                <div v-else>
                    <BaseButton :tag="'router-link'" :to="'/login'" variant="night" block :shine="false">
                        Connexion / Inscription
                    </BaseButton>
                </div>
            </div>
        </transition>

        <!-- Modale de publication -->
        <AddReviewModal :isOpen="isModalOpen" @close="closeModal" @publish="publishReview" />
    </header>
</template>

<script setup>
import AddReviewModal from './AddReviewModal.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useUserStore } from '@/stores/user';
import { useReviewStore } from '@/stores/review';
import { useRouter } from 'vue-router';
import { getStorageUrl } from '@/config';
import Swal from 'sweetalert2';

const reviewStore = useReviewStore();
const userStore = useUserStore();
const isModalOpen = ref(false);
const isDropdownOpen = ref(false);
const searchQuery = ref('');
const isOpen = ref(false);
const dropdownRef = ref(null);
const router = useRouter();

const refreshPage = () => {
    if (router.currentRoute.value.name === 'home') {
        reviewStore.getReviews();
    }
};

// Initiales pour l'avatar par défaut
const userInitials = computed(() => {
    if (!userStore.user?.username) return '';
    return userStore.user.username
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
});

const toggleDropdown = () => {
    isDropdownOpen.value = !isDropdownOpen.value;
};

const publishReview = async (reviewData) => {
    await reviewStore.createReviews(reviewData);
};

const logout = () => {
    isOpen.value = false;
    Swal.fire({
        title: 'Confirmer la déconnexion',
        text: 'Veux-tu vraiment te déconnecter ?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#FF6B35',
        cancelButtonColor: '#1E2A38',
        confirmButtonText: 'Oui, me déconnecter',
        cancelButtonText: 'Annuler',
    }).then(async (result) => {
        if (result.isConfirmed) {
            await userStore.logoutUser();
            isDropdownOpen.value = false;
            router.push('/');

            Swal.fire({
                title: 'Déconnecté !',
                text: 'À très vite !',
                icon: 'success',
                timer: 1800,
                showConfirmButton: false,
            });
        }
    });
};

const handleSearch = async () => {
    if (searchQuery.value.trim()) {
        await reviewStore.searchReviews(searchQuery.value.trim());
        if (reviewStore.reviews.length === 0) {
            Swal.fire({
                title: 'Aucun résultat',
                text: 'Aucune review ne correspond à ta recherche pour le moment.',
                icon: 'info',
                confirmButtonColor: '#FF6B35',
            });
            searchQuery.value = '';
            reviewStore.getReviews();
        }
        isOpen.value = false;
        router.push('/feed');
    } else {
        reviewStore.getReviews();
    }
};

const openModal = () => {
    isModalOpen.value = true;
    isOpen.value = false;
};

const closeModal = () => {
    isModalOpen.value = false;
};

// Fermer le menu déroulant au clic à l'extérieur
const onClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isDropdownOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside));
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
