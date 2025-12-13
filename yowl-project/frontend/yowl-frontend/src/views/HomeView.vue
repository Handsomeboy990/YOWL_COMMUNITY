<template>
    <!-- header -->
    <Header />

    <!-- main -->
    <div class="max-w-mobile mx-10 max-w-full py-15 ">
        <div class="flex lg:flex-row gap-6 p-4 lg:p-6">

            <!-- Sidebar -->
            <div class="w-2/12 fixed bg-gray-100 rounded-lg border-2 mx-4 border-[#1E2A38] h-140  p-6 ">
                <Sidebar />
            </div>

            <div v-if="reviews.length === 0 && reviewStore.search == false"
                class="flex flex-col items-center justify-center text-center py-20 ml-95 px-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-24 h-24 mb-6 text-gray-400" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18M8 3v18M16 3v18" />
                </svg>
                <h2 class="text-2xl font-semibold text-gray-800">Aucun post pour le moment</h2>
                <p class="mt-2 text-gray-700 text-md max-w-md">
                    Il n'y a encore aucun post à afficher. Dès qu'un utilisateur publiera, vous le verrez ici.
                </p>
            </div>

            <!-- ReviewCard + Pagination -->
            <div class="w-6/12 m-auto pr-26  ">
                <div class="space-y-6 space-x-3">
                    <ReviewCard v-for="review in reviews.slice(0, reviews.length > 10 ? 9 : reviews.length)"
                        :key="review.id" :review="review" />
                </div>

                <!-- pagination -->
                <Pagination v-if="reviewStore.pagination.total > 10" :pagination="reviewStore.pagination"
                    @changePage="handlePageChange" />
            </div>

            <!-- Signup button -->
            <div
                class="w-3/12 bg-gray-100 fixed right-15 rounded-lg border-2 border-[#1E2A38] h-140 p-6 flex flex-col items-center justify-center">
                <h2 class="text-[26px] font-poppins font-bold mb-4">
                    Welcome into the community
                </h2>
                <KpiSideBar />
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
const pagination = ref({})
const route = useRoute()

// Watch pour synchroniser automatiquement la liste et la pagination
watch(
    () => reviewStore.reviews,
    (newReviews) => {
        reviews.value = newReviews
        pagination.value = reviewStore.pagination
    },
    { immediate: true }
)

// Chargement initial
/* onBeforeMount(async () => {

    await reviewStore.getReviews()

    await commentStore.getComments()
    reviews.value = reviewStore.reviews
    pagination.value = reviewStore.pagination

}) */

onBeforeMount(async () => {
    await reviewStore.getReviews(route.params.page? route.params.page : 1)
    await commentStore.getComments()
})

/* watch(
    reviewStore.reviews,
    () => {
        reviews.value = reviewStore.reviews
        pagination.value = reviewStore.pagination
    },
    { immediate: true }
) */


// Changer de page
const handlePageChange = async (page) => {
    await reviewStore.getReviews(page)
}

</script>
