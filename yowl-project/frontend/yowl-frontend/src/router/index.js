import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/stores/user';
import LandingView from '@/views/LandingView.vue'
import HomeView from '../views/HomeView.vue'
import SignUp from '@/components/pages/Auth/SignUp.vue'
import Login from '@/components/pages/Auth/Login.vue'
import ForgotPassword from '@/components/pages/Auth/ForgotPassword.vue'
import ResetPassword from '@/components/pages/Auth/ResetPassword.vue'
import ReviewDetail from '@/components/pages/ReviewDetail.vue'
import Summary from '@/components/pages/profil/Summary.vue'
import Activity from '@/components/pages/profil/Activity.vue'
import MyPost from '@/components/pages/profil/MyPost.vue'
import Saved from '@/components/pages/profil/Saved.vue'
import Appeals from '@/components/pages/profil/Appeals.vue'
import Onboarding from '@/components/pages/Onboarding.vue'
import MemberProfile from '@/views/MemberProfile.vue'
import TagFeed from '@/views/TagFeed.vue'
import TagDirectory from '@/views/TagDirectory.vue'
import Suggestion from '@/components/pages/Suggestion.vue'
import LegalPage from '@/components/pages/LegalPage.vue'
import NotFound from '@/views/NotFound.vue'
import ShareView from '@/views/ShareView.vue'
import DashboardAdmin from '@/views/DashboardAdmin.vue';

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: '/',
            name: 'landing',
            component: LandingView,
        },
        {
            path: '/feed/:page?',
            name: 'home',
            component: HomeView,
        },
        {
            path: '/signup',
            name: 'signup',
            component: SignUp,
            meta: { requiresGuest: true }
        },
        {
            path: '/login',
            name: 'login',
            component: Login,
            meta: { requiresGuest: true }
        },
        {
            path: '/forgot-password',
            name: 'forgot-password',
            component: ForgotPassword,
            meta: { requiresGuest: true }
        },
        {
            path: '/password-reset/:token',
            name: 'password-reset',
            component: ResetPassword,
            meta: { requiresGuest: true }
        },
        {
            path: '/reviews/:id/:actualPage?',
            name: 'review-detail',
            component: ReviewDetail,
        },
        {
            path: '/share',
            name: 'share',
            component: ShareView,
        },
        {
            path: '/user/summary',
            name: 'summary',
            component: Summary,
            meta: { requiresAuth: true }
        },
        {
            path: '/user/activity',
            name: 'activity',
            component: Activity,
            meta: { requiresAuth: true }
        },
        {
            path: '/sujets',
            name: 'tag-directory',
            component: TagDirectory,
        },
        {
            path: '/sujets/:name',
            name: 'tag-feed',
            component: TagFeed,
        },
        {
            path: '/membres/:username',
            name: 'member-profile',
            component: MemberProfile,
        },
        {
            path: '/bienvenue',
            name: 'onboarding',
            component: Onboarding,
            meta: { requiresAuth: true }
        },
        {
            path: '/user/saved',
            name: 'saved',
            component: Saved,
            meta: { requiresAuth: true }
        },
        {
            path: '/user/contestations',
            name: 'appeals',
            component: Appeals,
            meta: { requiresAuth: true }
        },
        {
            path: '/user/my-reviews',
            name: 'my-reviews',
            component: MyPost,
            meta: { requiresAuth: true }
        },
        {
            // Les six pages éditables passent par le même composant, alimenté
            // par la base : les corriger ne demande plus de déploiement.
            path: '/about',
            name: 'about',
            component: LegalPage,
            meta: { slug: 'a-propos' },
        },
        {
            path: '/charte',
            name: 'charte',
            component: LegalPage,
            meta: { slug: 'charte' },
        },
        {
            path: '/confidentialite',
            name: 'confidentialite',
            component: LegalPage,
            meta: { slug: 'confidentialite' },
        },
        {
            path: '/conditions',
            name: 'conditions',
            component: LegalPage,
            meta: { slug: 'conditions' },
        },
        {
            path: '/mentions-legales',
            name: 'mentions-legales',
            component: LegalPage,
            meta: { slug: 'mentions-legales' },
        },
        {
            // L'ancienne adresse reste valable.
            path: '/policy',
            redirect: '/charte',
        },
        {
            path: '/faq',
            name: 'faq',
            component: LegalPage,
            meta: { slug: 'faq' },
        },
        {
            path: '/suggestion',
            name: 'suggestion',
            component: Suggestion,
        },
        {
            path: '/admin',
            name: 'admin-dashboard',
            component: DashboardAdmin,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/:pathMatch(.*)*',
            name: 'not-found',
            component: NotFound,
        },
    ],
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) return savedPosition;
        if (to.hash) return { el: to.hash, behavior: 'smooth' };
        return { top: 0 };
    },
})

router.beforeEach((to, from, next) => {
    const userStore = useUserStore();

    // Un utilisateur connecté arrive directement sur le fil d'actualité
    if (to.name === 'landing' && userStore.isAuthenticated) {
        return next({ name: 'home' });
    }

    // Routes protégées : redirection vers la connexion
    if (to.meta.requiresAuth && !userStore.isAuthenticated) {
        return next({ name: 'login', query: { redirect: to.fullPath } });
    }

    // Routes réservées aux visiteurs (login/signup)
    if (to.meta.requiresGuest && userStore.isAuthenticated) {
        return next({ name: 'home' });
    }

    // Routes réservées aux administrateurs
    if (to.meta.requiresAdmin && !userStore.isAdmin) {
        return next({ name: 'home' });
    }

    next();
});

export default router
