import { createRouter, createWebHistory } from 'vue-router';
import CookingHistoryPage from '../pages/CookingHistoryPage.vue';

const routes = [
    {
        path: '/cooking-history',
        name: 'cooking-history',
        component: CookingHistoryPage,
        meta: {
            requiresAuth: true
        }
    }
    // ... other routes ...
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

// Navigation guard for authenticated routes
router.beforeEach((to, from, next) => {
    if (to.matched.some(record => record.meta.requiresAuth)) {
        // Check if user is authenticated
        if (!localStorage.getItem('token')) {
            next({
                path: '/login',
                query: { redirect: to.fullPath }
            });
        } else {
            next();
        }
    } else {
        next();
    }
});

export default router; 