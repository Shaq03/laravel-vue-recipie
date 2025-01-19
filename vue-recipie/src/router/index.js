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
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router 