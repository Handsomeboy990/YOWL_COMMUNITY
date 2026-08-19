<template>
    <div class="min-h-screen bg-gray-50 w-full">
        <!-- ===== HEADER ===== -->
        <header class="fixed top-0 left-0 w-full z-50 bg-white/95 backdrop-blur-md border-b border-gray-200 shadow-sm">
            <div class="w-full px-3 md:px-6 h-16 flex items-center gap-3 md:gap-6">
                <!-- Logo -->
                <router-link to="/feed" class="flex items-center gap-2 shrink-0" @click="refreshFeed">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-primary to-[#ff8c5a] grid place-items-center shadow-md shadow-orange-primary/30">
                        <img src="@/assets/logo.png" alt="Logo YOWL" class="w-7 h-7">
                    </span>
                    <span class="hidden sm:inline font-poppins font-extrabold text-xl text-blue-night">YOWL</span>
                </router-link>

                <!-- Recherche -->
                <div class="flex-1 max-w-xl mx-auto">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400">
                            <i class="fas fa-search text-sm"></i>
                        </span>
                        <input v-model="searchQuery" type="text" placeholder="Rechercher sur YOWL..."
                            class="pl-10 pr-4 py-2.5 w-full bg-gray-100 hover:bg-gray-200/70 focus:bg-white border border-transparent focus:border-orange-primary rounded-full text-gray-900 text-sm focus:outline-none transition-all duration-200"
                            @keyup.enter="handleSearch" />
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2 md:gap-3 shrink-0">
                    <BaseButton class="hidden md:inline-flex" variant="primary" size="sm" icon="fa-solid fa-plus" @click="openPublish">
                        Publier
                    </BaseButton>

                    <!-- Centre de notifications -->
                    <div v-if="userStore.isAuthenticated" ref="notificationsRef" class="relative">
                        <button
                            class="relative w-10 h-10 rounded-full grid place-items-center cursor-pointer transition-colors"
                            :class="isNotificationsOpen ? 'bg-orange-primary/10 text-orange-primary' : 'text-gray-400 hover:bg-gray-100 hover:text-blue-night'"
                            aria-label="Notifications" :aria-expanded="isNotificationsOpen"
                            @click="toggleNotifications">
                            <i :class="notificationStore.hasUnread ? 'fa-solid fa-bell' : 'fa-regular fa-bell'"></i>
                            <span v-if="notificationStore.hasUnread"
                                class="absolute top-0.5 right-0.5 min-w-4 h-4 px-1 rounded-full bg-orange-primary text-white text-[10px] font-bold grid place-items-center">
                                {{ notificationStore.unreadCount > 99 ? '99+' : notificationStore.unreadCount }}
                            </span>
                        </button>

                        <Transition name="dropdown">
                            <div v-if="isNotificationsOpen" class="absolute right-0 mt-3 z-50">
                                <NotificationPanel :push="push" @close="isNotificationsOpen = false"
                                    @toggle-push="togglePush" />
                            </div>
                        </Transition>
                    </div>

                    <!-- Profil ou connexion -->
                    <div v-if="userStore.isAuthenticated" ref="dropdownRef" class="relative">
                        <button class="flex items-center gap-1.5 cursor-pointer" :aria-expanded="isDropdownOpen" aria-label="Menu du profil" @click="isDropdownOpen = !isDropdownOpen">
                            <img v-if="userStore.user?.picture"
                                class="w-10 h-10 rounded-full object-cover ring-2 ring-orange-primary/40"
                                :src="getStorageUrl(userStore.user.picture)" alt="Photo de profil">
                            <span v-else
                                class="w-10 h-10 bg-blue-night rounded-full grid place-items-center text-white text-sm font-bold">
                                {{ userInitials }}
                            </span>
                        </button>

                        <Transition name="dropdown">
                            <div v-if="isDropdownOpen"
                                class="absolute right-0 mt-3 w-56 bg-white text-gray-800 rounded-2xl shadow-xl shadow-blue-night/10 py-2 border border-gray-100">
                                <div class="px-4 py-2.5 border-b border-gray-100">
                                    <p class="font-semibold text-blue-night text-sm truncate">{{ userStore.user?.fullname }}</p>
                                    <p class="text-xs text-gray-400 truncate">@{{ userStore.user?.username }}</p>
                                </div>
                                <router-link to="/user/activity" class="flex items-center gap-2.5 px-4 py-2.5 text-sm hover:bg-gray-50" @click="isDropdownOpen = false">
                                    <i class="fa-regular fa-user text-gray-400 w-4"></i> Mon profil
                                </router-link>
                                <router-link to="/user/summary" class="flex items-center gap-2.5 px-4 py-2.5 text-sm hover:bg-gray-50" @click="isDropdownOpen = false">
                                    <i class="fa-solid fa-chart-pie text-gray-400 w-4"></i> Mes statistiques
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

                    <BaseButton v-else :tag="'router-link'" :to="'/login'" variant="night" size="sm" :shine="false">
                        <span class="hidden sm:inline">Connexion</span>
                        <i class="sm:hidden fa-solid fa-right-to-bracket"></i>
                    </BaseButton>
                </div>
            </div>
        </header>

        <!-- ===== NAVIGATION GAUCHE (desktop) ===== -->
        <nav class="hidden lg:flex flex-col fixed left-0 top-16 bottom-0 w-60 xl:w-64 2xl:w-68 bg-white border-r border-gray-200 px-3 py-5 z-40"
            aria-label="Navigation principale">
            <div class="flex-1 space-y-1 overflow-y-auto">
                <router-link v-for="item in mainNav" :key="item.to" :to="item.to"
                    class="flex items-center gap-3.5 px-4 py-3 rounded-xl font-medium transition-all duration-200"
                    :class="isActive(item) ? 'bg-orange-primary/10 text-orange-primary' : 'text-blue-night hover:bg-gray-100'">
                    <i :class="[item.icon, 'w-5 text-center text-lg']"></i>
                    {{ item.label }}
                </router-link>

                <div class="pt-4">
                    <BaseButton variant="primary" block icon="fa-solid fa-plus" @click="openPublish">
                        Publier un avis
                    </BaseButton>
                </div>

                <!-- Invitation pour un visiteur. Sans elle, la colonne ne
                     porte qu'un lien et laisse un vide sur toute sa hauteur. -->
                <div v-if="!userStore.isAuthenticated"
                    class="mt-5 rounded-2xl border border-orange-200 bg-gradient-to-br from-orange-50 to-white p-4">
                    <p class="font-poppins font-bold text-blue-night">Rejoins YOWL</p>
                    <p class="mt-1 text-sm text-gray-600 leading-relaxed">
                        Publie tes avis, réagis et suis les sujets qui comptent pour toi.
                    </p>
                    <BaseButton class="mt-3" :tag="'router-link'" :to="'/signup'" variant="primary" size="sm" block>
                        Créer mon compte
                    </BaseButton>
                    <router-link to="/login"
                        class="mt-2 block text-center text-sm text-gray-500 hover:text-blue-night transition-colors">
                        J'ai déjà un compte
                    </router-link>
                </div>
            </div>

            <!-- Liens secondaires -->
            <div class="pt-4 border-t border-gray-100 space-y-0.5">
                <router-link v-for="item in secondaryNav" :key="item.to" :to="item.to"
                    class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm text-gray-500 hover:text-blue-night hover:bg-gray-50 transition-colors">
                    <i :class="[item.icon, 'w-4 text-center']"></i>
                    {{ item.label }}
                </router-link>
                <p class="px-4 pt-3 text-[11px] text-gray-400">
                    © {{ new Date().getFullYear() }} YOWL — LONG Corp
                </p>
            </div>
        </nav>

        <!-- ===== CONTENU =====
             Le decalage suit la largeur des rails, qui s'elargissent avec
             l'ecran. La colonne centrale prend tout ce qui reste : c'est ce
             qui supprime la gouttiere vide entre le fil et les options. -->
        <div class="pt-16 w-full lg:pl-60 xl:pl-64 2xl:pl-68"
            :class="$slots.rail ? 'xl:pr-80 2xl:pr-96' : ''">
            <main class="w-full min-h-[calc(100vh-4rem)] pb-24 lg:pb-8">
                <slot />
            </main>
        </div>

        <!-- ===== RAIL DROIT (optionnel) ===== -->
        <aside v-if="$slots.rail"
            class="hidden xl:block fixed right-0 top-16 bottom-0 w-80 2xl:w-96 border-l border-gray-200 bg-white overflow-y-auto p-5 z-40">
            <slot name="rail" />
        </aside>

        <!-- ===== NAVIGATION BASSE (mobile) ===== -->
        <nav class="lg:hidden fixed bottom-0 left-0 w-full z-50 bg-white/95 backdrop-blur-md border-t border-gray-200 pb-[env(safe-area-inset-bottom)]"
            aria-label="Navigation mobile">
            <div class="grid grid-cols-5 h-16">
                <router-link to="/feed" class="flex flex-col items-center justify-center gap-1 text-[11px]"
                    :class="route.name === 'home' ? 'text-orange-primary font-semibold' : 'text-gray-500'">
                    <i class="fa-solid fa-house text-lg"></i>
                    Fil
                </router-link>
                <router-link to="/user/my-reviews" class="flex flex-col items-center justify-center gap-1 text-[11px]"
                    :class="route.name === 'my-reviews' ? 'text-orange-primary font-semibold' : 'text-gray-500'">
                    <i class="fa-solid fa-newspaper text-lg"></i>
                    Reviews
                </router-link>
                <button class="flex flex-col items-center justify-center cursor-pointer" aria-label="Publier un avis" @click="openPublish">
                    <span class="w-12 h-12 -mt-5 rounded-2xl bg-gradient-to-br from-orange-primary to-[#ff8c5a] grid place-items-center text-white text-xl shadow-lg shadow-orange-primary/40 border-4 border-white">
                        <i class="fa-solid fa-plus"></i>
                    </span>
                </button>
                <router-link to="/user/activity" class="flex flex-col items-center justify-center gap-1 text-[11px]"
                    :class="route.name === 'activity' ? 'text-orange-primary font-semibold' : 'text-gray-500'">
                    <i class="fa-solid fa-clock-rotate-left text-lg"></i>
                    Activité
                </router-link>
                <router-link :to="userStore.isAuthenticated ? '/user/summary' : '/login'"
                    class="flex flex-col items-center justify-center gap-1 text-[11px]"
                    :class="route.name === 'summary' || route.name === 'login' ? 'text-orange-primary font-semibold' : 'text-gray-500'">
                    <i class="fa-regular fa-user text-lg"></i>
                    Profil
                </router-link>
            </div>
        </nav>

        <!-- Modale de publication globale -->
        <AddReviewModal :isOpen="isPublishOpen" @close="isPublishOpen = false" @publish="publishReview" />
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useNotify } from '@/composables/useNotify';
import { useConfirm } from '@/composables/useConfirm';
import { useUserStore } from '@/stores/user';
import { useReviewStore } from '@/stores/review';
import { getStorageUrl } from '@/config';
import BaseButton from '@/components/ui/BaseButton.vue';
import AddReviewModal from '@/components/layouts/AddReviewModal.vue';
import { usePushNotifications } from '@/composables/usePushNotifications';
import { useNotificationStore } from '@/stores/notification';
import NotificationPanel from '@/components/layouts/NotificationPanel.vue';

const route = useRoute();
const router = useRouter();
const userStore = useUserStore();
const notify = useNotify();
const confirm = useConfirm();
const reviewStore = useReviewStore();

const notificationStore = useNotificationStore();

const searchQuery = ref('');
const isDropdownOpen = ref(false);
const isPublishOpen = ref(false);
const isNotificationsOpen = ref(false);
const dropdownRef = ref(null);
const notificationsRef = ref(null);

const mainNav = computed(() => {
    const items = [
        { to: '/feed', icon: 'fa-solid fa-house', label: 'Fil', name: 'home' },
    ];
    if (userStore.isAuthenticated) {
        items.push(
            { to: '/user/my-reviews', icon: 'fa-solid fa-newspaper', label: 'Mes avis', name: 'my-reviews' },
            { to: '/user/activity', icon: 'fa-solid fa-clock-rotate-left', label: 'Activité', name: 'activity' },
            { to: '/user/summary', icon: 'fa-solid fa-chart-pie', label: 'Statistiques', name: 'summary' },
        );
    }
    if (userStore.isAdmin) {
        items.push({ to: '/admin', icon: 'fa-solid fa-gauge-high', label: 'Administration', name: 'admin-dashboard' });
    }
    return items;
});

const secondaryNav = [
    { to: '/about', icon: 'fa-regular fa-circle-question', label: 'À propos' },
    { to: '/faq', icon: 'fa-regular fa-comments', label: 'FAQ' },
    { to: '/policy', icon: 'fa-solid fa-shield-halved', label: 'Charte' },
    { to: '/suggestion', icon: 'fa-regular fa-lightbulb', label: 'Suggestions' },
];

const isActive = (item) => route.name === item.name;

const userInitials = computed(() => {
    if (!userStore.user?.username) return '';
    return userStore.user.username
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
});

const refreshFeed = () => {
    if (route.name === 'home') reviewStore.getReviews();
};

const openPublish = () => {
    if (!userStore.isAuthenticated) {
        router.push({ name: 'login', query: { redirect: route.fullPath } });
        return;
    }
    isPublishOpen.value = true;
};

const publishReview = async (reviewData) => {
    await reviewStore.createReviews(reviewData);
    if (route.name !== 'home') router.push('/feed');
};

const handleSearch = async () => {
    if (searchQuery.value.trim()) {
        await reviewStore.searchReviews(searchQuery.value.trim());
        if (reviewStore.reviews.length === 0) {
            notify.info('Aucun résultat', 'Aucun avis ne correspond à ta recherche pour le moment.');
            searchQuery.value = '';
            reviewStore.getReviews();
        }
        router.push('/feed');
    } else {
        reviewStore.getReviews();
    }
};

const logout = async () => {
    isDropdownOpen.value = false;
    const confirmed = await confirm({
        title: 'Se déconnecter ?',
        message: 'Tu devras te reconnecter pour publier ou réagir.',
        confirmLabel: 'Me déconnecter',
    });
    if (!confirmed) return;

    await userStore.logoutUser();
    router.push('/');
    notify.success('Déconnecté', 'À très vite.');
};

const onClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isDropdownOpen.value = false;
    }
    if (notificationsRef.value && !notificationsRef.value.contains(event.target)) {
        isNotificationsOpen.value = false;
    }
};

const toggleNotifications = () => {
    isNotificationsOpen.value = !isNotificationsOpen.value;
    if (isNotificationsOpen.value) {
        isDropdownOpen.value = false;
        notificationStore.fetchNotifications();
    }
};

// Notifications push
const push = usePushNotifications();

const togglePush = async () => {
    if (push.isSubscribed.value) {
        await push.unsubscribe();
        notify.info('Notifications désactivées');
    } else {
        const ok = await push.subscribe();
        if (ok) {
            notify.success('Notifications activées', 'Tu seras prévenu des réactions et commentaires sur tes reviews.');
        } else {
            notify.warning('Notifications refusées', "Autorise les notifications dans ton navigateur pour les activer.");
        }
    }
};

// Rafraichissement de la pastille : toutes les 60 s et au retour sur l'onglet
const UNREAD_POLL_MS = 60000;
let unreadTimer = null;

const refreshUnread = () => {
    if (userStore.isAuthenticated) notificationStore.fetchUnreadCount();
};

const onVisibilityChange = () => {
    if (document.visibilityState === 'visible') refreshUnread();
};

onMounted(() => {
    document.addEventListener('click', onClickOutside);
    document.addEventListener('visibilitychange', onVisibilityChange);
    push.refreshState();
    refreshUnread();
    unreadTimer = setInterval(refreshUnread, UNREAD_POLL_MS);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onClickOutside);
    document.removeEventListener('visibilitychange', onVisibilityChange);
    if (unreadTimer) clearInterval(unreadTimer);
});
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
