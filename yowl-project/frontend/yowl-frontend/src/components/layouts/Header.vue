<!-- eslint-disable vue/multi-word-component-names -->
<template>
    <header class="bg-orange-primary text-white fixed top-0 left-0 w-full z-50 shadow-md">
        <div class="max-w-7xl mx-auto flex items-center justify-between px-4 md:px-6 py-3">

            <!-- Logo -->
            <router-link to="/" class="flex items-center space-x-2 font-poppins font-bold text-xl px-3">
                <img src="../../assets/logo.png" alt="yowl Logo" class="w-8 h-8 md:w-10 md:h-10">
                <span>YOWL</span>
            </router-link>

            <!-- Burger button mobile -->
            <button @click="isOpen = !isOpen" class="md:hidden text-2xl">
                <i :class="isOpen ? 'fa-solid fa-xmark' : 'fa-solid fa-bars'"></i>
            </button>

            <!-- Desktop nav -->
            <nav class="sm:hidden md:flex items-center space-x-6 font-semibold">
                <router-link to="/" @click="refreshPage" class="hover:text-yellow-200">Home</router-link>
                <router-link to="/about" class="hover:text-yellow-200">About</router-link>
            </nav>

            <!-- Desktop search -->
            <div class="sm:hidden md:flex flex-1 max-w-lg mx-6">
                <div class="relative w-full">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" placeholder="Search..." v-model="searchQuery" @keyup.enter="handleSearch"
                        class="pl-10 pr-4 py-2 w-full bg-white rounded-md text-gray-900 text-sm focus:outline-none" />
                </div>
            </div>

            <!-- Desktop actions -->
            <div class="sm:hidden md:flex items-center gap-4">
                <!-- Add review -->
                <button @click="openModal"
                    class="cursor-pointer font-semibold bg-blue-night px-5 py-2 rounded-md hover:bg-black transition-colors">
                    Add a review
                </button>

                <!-- Profile or Login -->
                <div class="flex ml-2 relative" v-if="userStore.isAuthenticated">
                    <div v-if="userStore.user.picture" @click="toggleDropdown" class="dropDown">
                        <img class=" dropDown w-10 h-10 rounded-full flex items-center justify-center cursor-pointer"
                            :src="`http://localhost:8000/storage/${userStore.user.picture}`" alt="profile picture">

                    </div>
                    <div v-else @click="toggleDropdown"
                        class="dropDown w-10 h-10 bg-blue-night rounded-full flex items-center justify-center cursor-pointer">
                        <span @click="toggleDropdown" class="text-white text-sm font-bold dropDown">
                            {{ userInitials }}
                        </span>
                    </div>
                    <i class="dropDown fa-solid fa-chevron-down mt-4 ml-1 text-[#1A2E38] cursor-pointer"
                        @click="toggleDropdown"></i>

                    <!-- Dropdown -->
                    <div v-if="isDropdownOpen"
                        class="absolute right-0 mt-12 w-40 bg-white text-gray-800 rounded-lg shadow-lg py-2">
                        <router-link to="/user/activity" class="block px-4 py-2 hover:bg-gray-100">Profile</router-link>
                        <router-link v-if="isAdmin" to="/admin" class="dropDown block px-4 py-2 hover:bg-gray-100"
                            @click="toggleDropdown">Admin</router-link>
                        <button @click="logout"
                            class="cursor-pointer w-full text-left px-4 py-2 hover:bg-gray-100">Logout</button>
                    </div>
                </div>

                <!-- If not logged in -->
                <div v-else>
                    <router-link to="/login"
                        class="font-semibold bg-blue-night px-5 py-2 rounded-md hover:bg-black transition-colors">
                        Login / Join Us
                    </router-link>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <transition name="fade">
            <div v-if="isOpen" class="md:hidden bg-blue-night text-white px-6 py-4 flex flex-col gap-4">
                <router-link to="/" class="hover:text-orange-300" @click="isOpen = false">Home</router-link>
                <router-link to="/about" class="hover:text-orange-300" @click="isOpen = false">About</router-link>

                <!-- Search mobile -->
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" placeholder="Search..."
                        class="pl-10 pr-4 py-2 w-full bg-white rounded-md text-gray-900 text-sm focus:outline-none" />
                </div>

                <!-- Add review -->
                <button @click="openModal"
                    class="bg-orange-primary px-5 py-2 rounded-md hover:bg-[#e75a27] transition-colors">
                    Add a review
                </button>

                <!-- Profile / Login -->
                <div v-if="userStore.isAuthenticated">
                    <router-link to="/user/activity"
                        class="dropDown cursor-pointer block px-4 py-2 hover:bg-orange-300">Profile</router-link>
                    <router-link v-if="isAdmin" to="/admin" class="dropDown block px-4 py-2 hover:bg-gray-100"
                        @click="toggleDropdown">Admin</router-link>
                    <button @click="logout"
                        class="cursor-pointer w-full text-left px-4 py-2 hover:bg-orange-300">Logout</button>
                </div>
                <div v-else>
                    <router-link to="/login"
                        class="block text-center font-semibold bg-blue-night px-5 py-2 rounded-md hover:bg-black transition-colors">
                        Login / Join Us
                    </router-link>
                </div>
            </div>
        </transition>

        <!-- AddReviewModal -->
        <AddReviewModal :isOpen="isModalOpen" @close="isModalOpen = !isModalOpen" @publish="publishReview"
            class="absolute z-1000 top-20 left-1/2 transform -translate-x-1/2" />
        <div v-if="isModalOpen" :isOpen="isModalOpen" @click="isModalOpen = !isModalOpen"
            class=" absolute w-full h-full bg-black opacity-10 top-0 z-999 left-0"></div>
    </header>
</template>

<script setup>
import AddReviewModal from './AddReviewModal.vue';
import { ref, computed } from 'vue';
import { useUserStore } from '@/stores/user';
import { useReviewStore } from '@/stores/review';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';

const reviewStore = useReviewStore();
const userStore = useUserStore();
const isModalOpen = ref(false);
const isDropdownOpen = ref(false);
const searchQuery = ref('');
const isOpen = ref(false);
const router = useRouter();



const refreshPage = () => {

    if (router.currentRoute.value.path == '/') {        
        reviewStore.getReviews()
    }
}



//role
const isAdmin = computed(() => {

    return userStore.user.roles[0].name.includes('admin');
});

// avatar initials
const userInitials = computed(() => {
    if (!userStore.user?.username) return '';
    return userStore.user.username.split(' ').map(n => n[0]).join('').toUpperCase();
});

const toggleDropdown = () => {
    isDropdownOpen.value = !isDropdownOpen.value;
}

const publishReview = async (reviewData) => {
    await reviewStore.createReviews(reviewData)

}

const logout = () => {

    Swal.fire({
        title: "Confirm logout",
        text: "Do you really want to logout ?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#FF6B35",
        cancelButtonColor: "#1E2A38",
        confirmButtonText: "Yes, logout"
    }).then(async (result) => {
        if (result.isConfirmed) {
            userStore.logoutUser();
            isDropdownOpen.value = false;
            router.push('/');

            Swal.fire({
                title: "Logged out!",
                text: "See you later !",
                icon: "success",
                showConfirmButton: false,
            });
        }
    });

}

const handleSearch = async() => {
  if (searchQuery.value.trim()) {
    await reviewStore.searchReviews(searchQuery.value.trim());
    console.log(reviewStore.reviews.length)
    if (reviewStore.reviews.length == 0) {
        Swal.fire({
          title: "Oooops",
          text: "It's seem like there is nothing like that now",
          icon: "error",
          confirmButtonColor: "#FF6B35"
        });
        searchQuery.value = ""
        router.push('/');
        reviewStore.getReviews();
    }
    router.push('/');
  } else {
    reviewStore.getReviews();
  }
}

const openModal = () => {
    isModalOpen.value = true;
    document.body.classList.add("overflow-y-hidden");
}


window.onclick = function (event) {
    let modal = document.getElementsByClassName("dropDown")
    let close = false
    for (const element of modal) {
        if (event.target == element) {
            close = true
        }
        if ((event.target == element) && (isDropdownOpen.value == false)) {
            close = false
        }
    }
    
    isDropdownOpen.value = close

}

</script>
