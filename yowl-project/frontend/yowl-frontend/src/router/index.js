import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { signalerVisite } from '@/services/audience';
import { appliquerMetaDeRoute } from '@/composables/usePageMeta';
import { useSiteStore } from '@/stores/site';
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

/**
 * Métadonnées par défaut, une entrée par route.
 *
 * Elles vivent ici plutôt que dans chaque vue parce qu'elles sont fixes :
 * une page de connexion dit la même chose à chaque ouverture. Les pages dont
 * le titre dépend de données chargées, un avis, un sujet, un profil, appellent
 * useSeo dans leur composant et écrasent ce qui suit.
 *
 * « noindex » n'est pas une punition : une page de connexion, un profil
 * personnel ou une contestation n'ont rien à faire dans un index, et les y
 * laisser dilue les pages qui, elles, doivent y être.
 */
const PRIVE = 'noindex, nofollow';

const SEO_PAR_ROUTE = {
    landing: {
        titre: 'La communauté qui donne son avis sur le web',
        description:
            "YOWL réunit les 13-35 ans autour des contenus découverts sur internet : "
            + 'articles, vidéos, jeux, musique. On partage un lien, on dit ce qu\'on en pense.',
    },
    home: {
        titre: 'Le fil',
        description:
            'Les derniers avis partagés par la communauté YOWL, tous sujets confondus.',
    },
    'tag-directory': {
        titre: 'Les sujets',
        description:
            'Tous les sujets dont parle la communauté YOWL, du cinéma au développement web.',
    },
    about: {
        titre: 'À propos',
        description: 'Ce qu\'est YOWL, à qui il s\'adresse et comment il fonctionne.',
    },
    faq: {
        titre: 'Foire aux questions',
        description: 'Les réponses aux questions les plus souvent posées sur YOWL.',
    },
    charte: {
        titre: 'Charte de la communauté',
        description: 'Les règles que chacun accepte en publiant sur YOWL.',
    },
    confidentialite: {
        titre: 'Politique de confidentialité',
        description: 'Quelles données YOWL collecte, pourquoi, et ce que vous pouvez en faire.',
    },
    conditions: {
        titre: 'Conditions générales d\'utilisation',
        description: 'Le cadre contractuel du service YOWL.',
    },
    'mentions-legales': {
        titre: 'Mentions légales',
        description: 'Éditeur, hébergeur et directeur de publication de YOWL.',
    },
    suggestion: {
        titre: 'Proposer une amélioration',
        description: 'Une idée, un défaut, une gêne : ce formulaire arrive directement à l\'équipe.',
    },
    signup: {
        titre: 'Créer un compte',
        description: 'Rejoindre YOWL et commencer à partager ses avis. Gratuit, en une minute.',
    },
    login: { titre: 'Se connecter', robots: PRIVE },
    'forgot-password': { titre: 'Mot de passe oublié', robots: PRIVE },
    'password-reset': { titre: 'Choisir un nouveau mot de passe', robots: PRIVE },
    share: { titre: 'Publier un avis', robots: PRIVE },
    summary: { titre: 'Mon profil', robots: PRIVE },
    activity: { titre: 'Mon activité', robots: PRIVE },
    saved: { titre: 'Mes enregistrements', robots: PRIVE },
    appeals: { titre: 'Mes contestations', robots: PRIVE },
    'my-reviews': { titre: 'Mes avis', robots: PRIVE },
    onboarding: { titre: 'Bienvenue', robots: PRIVE },
    unsubscribe: { titre: 'Désinscription', robots: PRIVE },
    'admin-dashboard': { titre: 'Administration', robots: PRIVE },
    'not-found': { titre: 'Page introuvable', robots: PRIVE },
};

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

/**
 * Mesure d'audience, apres la navigation et non pendant.
 *
 * afterEach plutot que beforeEach : une redirection compterait sinon deux
 * pages, celle demandee et celle atteinte, et une route refusee par un garde
 * serait comptee alors qu'elle n'a jamais ete affichee. Ici seule la
 * destination reellement atteinte est signalee.
 */
router.afterEach((to) => {
    signalerVisite(to.path);

    // Pose tout de suite, et non a la prochaine boucle : la vue qui arrive
    // se monte ensuite et ecrase ce qui la concerne, ce qui est l'ordre
    // voulu. Differer inverserait les deux et la route effacerait le titre
    // qu'une vue vient de calculer a partir de ses donnees.
    appliquerMetaDeRoute(SEO_PAR_ROUTE[to.name] ?? {}, useSiteStore());
});

export default router
