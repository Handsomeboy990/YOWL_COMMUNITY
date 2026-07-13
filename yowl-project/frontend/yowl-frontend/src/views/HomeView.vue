<template>
    <!-- header -->
    <Header />

    <!-- main -->
    <div class="min-h-screen bg-gray-50 pt-20 pb-8">
        <div class="container mx-auto px-4 max-w-7xl">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- Sidebar gauche : filtres (masquée sur mobile) -->
                <aside class="hidden lg:block lg:col-span-3">
                    <div class="sticky top-24 animate-fade-in-up">
                        <Sidebar />
                    </div>
                </aside>

                <!-- Contenu principal -->
                <main class="col-span-1 lg:col-span-6">
                    <!-- Etat vide -->
                    <div v-if="reviews.length === 0 && reviewStore.search == false"
                        class="flex flex-col items-center justify-center text-center py-20 px-4 animate-fade-in">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-20 h-20 md:w-24 md:h-24 mb-6 text-gray-300 animate-bounce-slow" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18M8 3v18M16 3v18" />
                        </svg>
                        <h2 class="text-xl md:text-2xl font-semibold text-gray-800 mb-2">Aucune review pour le moment</h2>
                        <p class="mt-2 text-gray-600 text-sm md:text-base max-w-md">
                            Il n'y a encore rien à afficher. Dès qu'un membre publiera, tu le verras ici.
                        </p>
                    </div>

                    <!-- Fil des reviews -->
                    <div class="space-y-4 md:space-y-6">
                        <TransitionGroup name="feed" mode="out-in">
                            <ReviewCard v-for="review in reviews" :key="review.id" :review="review"
                                class="animate-fade-in-up" />
                        </TransitionGroup>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-8">
                        <Pagination v-if="reviewStore.pagination.total > 10" :pagination="reviewStore.pagination"
                            @changePage="handlePageChange" />
                    </div>
                </main>

                <!-- Sidebar droite : KPI (masquée sur mobile) -->
                <aside class="hidden lg:block lg:col-span-3">
                    <div class="sticky top-24 bg-gradient-to-br from-orange-50 to-white rounded-lg border border-orange-200 shadow-sm p-6 animate-fade-in-up animation-delay-200">
                        <h2 class="text-xl md:text-2xl font-poppins font-bold mb-4 text-gray-800">
                            Bienvenue dans la communauté
                        </h2>
                        <KpiSideBar />
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <!-- footer -->
    <Footer />
</template>

<script setup>
import { ref, watch, onBeforeMount } from 'vue'
import Header from '@/components/layouts/Header.vue'
import Footer from '@/components/layouts/Footer.vue'
import Sidebar from '@/components/layouts/Sidebar.vue'
import ReviewCard from '@/components/cards/ReviewCard.vue'
import Pagination from '@/components/layouts/Pagination.vue'
import KpiSideBar from '@/components/layouts/KpiSideBar.vue'
import { useReviewStore } from '@/stores/review'
import { useCommentStore } from '@/stores/comment'
import { useRoute } from 'vue-router'

const reviewStore = useReviewStore()
const commentStore = useCommentStore()

const reviews = ref([])
const route = useRoute()

// Synchroniser la liste locale avec le store
watch(
    () => reviewStore.reviews,
    (newReviews) => {
        reviews.value = newReviews
    },
    { immediate: true }
)

// Chargement initial
onBeforeMount(async () => {
    await reviewStore.getReviews(route.params.page ? route.params.page : 1)
    await commentStore.getComments()
})

// Changer de page
const handlePageChange = async (page) => {
    await reviewStore.getReviews(page)
}
</script>
