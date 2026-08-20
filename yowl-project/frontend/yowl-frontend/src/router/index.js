import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/stores/user';
import LandingView from '@/views/LandingView.vue'
import HomeView from '../views/HomeView.vue'

/**
 * Vues chargées à la demande.
 *
 * Tout partait dans un seul fichier de 1,2 Mo : un visiteur qui ouvre le fil
 * téléchargeait la console d'administration, l'éditeur de texte enrichi et les
 * graphiques avant de voir un seul avis. Chaque vue devient un morceau à part,
 * récupéré au moment où la route y mène.
 *
 * L'accueil et le fil restent chargés d'emblée : ce sont les deux portes
 * d'entrée, et les différer ajouterait un aller-retour avant le premier rendu.
 */
const Activity = () => import('@/components/pages/profil/Activity.vue');
const Appeals = () => import('@/components/pages/profil/Appeals.vue');
const DashboardAdmin = () => import('@/views/DashboardAdmin.vue');
const ForgotPassword = () => import('@/components/pages/Auth/ForgotPassword.vue');
const LegalPage = () => import('@/components/pages/LegalPage.vue');
const Login = () => import('@/components/pages/Auth/Login.vue');
const MemberProfile = () => import('@/views/MemberProfile.vue');
const MyPost = () => import('@/components/pages/profil/MyPost.vue');
const NotFound = () => import('@/views/NotFound.vue');
const Onboarding = () => import('@/components/pages/Onboarding.vue');
const ResetPassword = () => import('@/components/pages/Auth/ResetPassword.vue');
const ReviewDetail = () => import('@/components/pages/ReviewDetail.vue');
const Saved = () => import('@/components/pages/profil/Saved.vue');
const ShareView = () => import('@/views/ShareView.vue');
const SignUp = () => import('@/components/pages/Auth/SignUp.vue');
const Suggestion = () => import('@/components/pages/Suggestion.vue');
const Summary = () => import('@/components/pages/profil/Summary.vue');
const TagDirectory = () => import('@/views/TagDirectory.vue');
const TagFeed = () => import('@/views/TagFeed.vue');
const Unsubscribe = () => import('@/components/pages/Unsubscribe.vue');

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
            // Atteinte depuis un email, sans connexion.
            path: '/desinscription/:token',
            name: 'unsubscribe',
            component: Unsubscribe,
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
