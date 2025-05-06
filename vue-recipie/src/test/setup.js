import { vi } from 'vitest'
import { config } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'

// Create a mock router
const router = createRouter({
  history: createWebHistory(),
  routes: [
    { 
      path: '/', 
      name: 'home',
      component: { template: '<div>Home</div>' }
    },
    { 
      path: '/recipes', 
      name: 'recipes',
      component: { template: '<div>Recipes</div>' }
    },
    { 
      path: '/recipes/create', 
      name: 'recipe-create',
      component: { template: '<div>Create Recipe</div>' }
    },
    { 
      path: '/recipes/:id', 
      name: 'recipe-detail',
      component: { template: '<div>Recipe Detail</div>' }
    },
    { 
      path: '/favorites', 
      name: 'favorites',
      component: { template: '<div>Favorites</div>' }
    },
    { 
      path: '/signup', 
      name: 'signup',
      component: { template: '<div>Sign Up</div>' }
    }
  ]
})

// Mock window.matchMedia
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
})

// Global test configuration
config.global.plugins = [router]
config.global.stubs = {
  'router-link': {
    template: '<a :href="to"><slot /></a>',
    props: ['to']
  },
  'router-view': true,
  'Navigation': true,
  'ChefHat': true,
  'PlusCircle': true,
  'Utensils': true,
  'Search': true,
  'Filter': true,
  'Star': true,
  'RecipeDetailModal': true
} 