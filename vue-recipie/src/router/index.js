import { createRouter, createWebHistory } from 'vue-router'
import Home from '../components/Home.vue'

const routes = [
  {
    path: '/',
    name: 'home',
    component: Home
  },
  {
    path: '/about',
    name: 'about',
    component: () => import('../components/About.vue')
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../components/Auth/Login.vue'),
    meta: { guest: true }
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../components/Auth/Register.vue'),
    meta: { guest: true }
  },
  {
    path: '/account',
    name: 'account',
    component: () => import('../components/Auth/Account.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/recipes',
    name: 'recipes',
    component: () => import('../components/RecipeList.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/recipes/create',
    name: 'recipes.create',
    component: () => import('../components/RecipeCreate.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/recipe/:recipeId',
    name: 'recipe.detail',
    component: () => import('../components/RecipeDetail.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/ai-recommendations',
    name: 'ai-recommendations',
    component: () => import('../components/AIRecommendations.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/web-recipes',
    name: 'web-recipes',
    component: () => import('../components/WebRecipeSearch.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/recipes/:recipeId/similar',
    name: 'recipes.similar',
    component: () => import('../components/MLRecommendations.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/favorites',
    name: 'favorites',
    component: () => import('../components/Favorites.vue'),
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const isAuthenticated = !!localStorage.getItem('token')
  
  if (to.meta.requiresAuth && !isAuthenticated) {
    next('/login')
  } else if (to.meta.guest && isAuthenticated) {
    next('/')
  } else {
    next()
  }
})

export default router 