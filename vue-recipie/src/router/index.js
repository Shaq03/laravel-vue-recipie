import { createRouter, createWebHistory } from 'vue-router'
import Home from '../components/Home.vue'

const routes = [
  {
    path: '/',
    name: 'home',
    component: Home
  },
  {
    path: '/recipes',
    name: 'recipes',
    component: () => import('../components/RecipeList.vue')
  },
  {
    path: '/about',
    name: 'about',
    component: () => import('../components/About.vue')
  },
  {
    path: '/recipes/create',
    name: 'recipes.create',
    component: () => import('../components/RecipeCreate.vue')
  },
  {
    path: '/ai-recommendations',
    name: 'ai-recommendations',
    component: () => import('../components/AIRecommendations.vue')
  },
  {
    path: '/recipes/:recipeId/similar',
    name: 'recipes.similar',
    component: () => import('../components/MLRecommendations.vue')
  },
  {
    path: '/favorites',
    name: 'favorites',
    component: () => import('../components/Favorites.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router 