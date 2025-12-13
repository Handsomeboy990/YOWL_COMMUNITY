import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import SignUp from '@/components/pages/Auth/SignUp.vue'
import Login from '@/components/pages/Auth/Login.vue'
import ReviewDetail from '@/components/pages/ReviewDetail.vue'
import Summary from '@/components/pages/profil/Summary.vue'
import Activity from '@/components/pages/profil/Activity.vue'
import MyPost from '@/components/pages/profil/MyPost.vue'
import About from '@/components/pages/About.vue'
import Faq from '@/components/pages/Faq.vue'
import Suggestion from '@/components/pages/Suggestion.vue'
import Policy from '@/components/pages/Policy.vue'



const router = createRouter({
    history: createWebHistory(
        import.meta.env.BASE_URL),
    routes: [{
            path: '/:page?',
            name: 'home',
            component: HomeView,
        },
        {
            path: '/signup',
            name: 'signup',
            component: SignUp,
        },
        {
            path: '/login',
            name: 'login',
            component: Login,
        },
        {
            path: '/reviews/:id/:actualPage?',
            name: 'review-detail',
            component: ReviewDetail,
        },
        {
            path: '/user/summary',
            name: 'summary',
            component: Summary,
        },
        {
            path: '/user/activity',
            name: 'activity',
            component: Activity,
        },
        {
            path: '/user/my-reviews',
            name: 'my-reviews',
            component: MyPost,
        },
        {
            path: '/about',
            name: 'about',
            component: About,
        },
        {
            path: '/policy',
            name: 'policy',
            component: Policy,
        },
        {
            path: '/faq',
            name: 'faq',
            component: Faq,
        },
        {
            path: '/suggestion',
            name: 'suggestion',
            component: Suggestion,
        },

      
    ],
    })

    import { useUserStore } from '@/stores/user';
    import DashboardAdmin from '@/views/DashboardAdmin.vue';

    router.addRoute({
        path: '/admin',
        name: 'admin-dashboard',
        component: DashboardAdmin,
        meta: { requiresAdmin: true }
    });

    router.beforeEach((to, from, next) => {
        const userStore = useUserStore();
        if (to.meta.requiresAdmin) {
            // Vérifie le rôle admin
            if (!userStore.user || !userStore.user.roles[0] || !userStore.user.roles[0].name.includes('admin')) {
                return next({ name: 'home' });
            }
        }
        next();
    });

    export default router
