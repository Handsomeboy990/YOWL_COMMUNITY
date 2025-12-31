import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/stores/user';
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
import DashboardAdmin from '@/views/DashboardAdmin.vue';

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: '/:page?',
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
            path: '/reviews/:id/:actualPage?',
            name: 'review-detail',
            component: ReviewDetail,
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
            path: '/user/my-reviews',
            name: 'my-reviews',
            component: MyPost,
            meta: { requiresAuth: true }
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
        {
            path: '/admin',
            name: 'admin-dashboard',
            component: DashboardAdmin,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
    ],
})

router.beforeEach((to, from, next) => {
    const userStore = useUserStore();
    
    // Check if route requires authentication
    if (to.meta.requiresAuth && !userStore.isAuthenticated) {
        return next({ name: 'login', query: { redirect: to.fullPath } });
    }
    
    // Check if route requires guest (already logged in users shouldn't access)
    if (to.meta.requiresGuest && userStore.isAuthenticated) {
        return next({ name: 'home' });
    }
    
    // Check if route requires admin
    if (to.meta.requiresAdmin) {
        const isAdmin = userStore.user?.roles?.some(role => 
            typeof role === 'string' ? role === 'admin' : role.name === 'admin'
        );
        
        if (!isAdmin) {
            return next({ name: 'home' });
        }
    }
    
    next();
});

export default router
