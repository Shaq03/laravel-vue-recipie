import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createStore } from 'vuex'
import RecipeList from '../RecipeList.vue'

const mockRecipes = [
  {
    id: 1,
    title: 'Spaghetti Carbonara',
    description: 'Classic Italian pasta dish',
    cuisines: ['Italian'],
    tags: ['Pasta'],
    difficulty: 'Medium',
    cooking_time: 30,
    ingredients: ['spaghetti', 'eggs'],
    source: 'user'
  },
  {
    id: 2,
    title: 'Chicken Tikka Masala',
    description: 'Grilled chicken in curry sauce',
    cuisines: ['Indian'],
    tags: ['Chicken'],
    difficulty: 'Medium',
    cooking_time: 45,
    ingredients: ['chicken', 'spices'],
    source: 'user'
  },
  {
    id: 3,
    title: 'Vegetable Stir Fry',
    description: 'Quick and healthy vegetable stir fry',
    cuisines: ['Chinese'],
    tags: ['Vegetarian'],
    difficulty: 'Easy',
    cooking_time: 20,
    ingredients: ['tofu', 'vegetables'],
    source: 'web'
  }
]

const createMockStore = () => {
  return createStore({
    state: {
      loading: false,
      error: null,
      recipes: [...mockRecipes],
      favorites: [1]
    },
    getters: {
      allRecipes: (state) => state.recipes,
      isFavorite: (state) => (recipeId) => state.favorites.includes(recipeId),
      isAuthenticated: () => true
    },
    actions: {
      fetchRecipes: vi.fn(),
      fetchFavorites: vi.fn(),
      toggleFavorite: vi.fn()
    }
  })
}

describe('RecipeList.vue', () => {
  let wrapper
  let store

  beforeEach(() => {
    store = createMockStore()
    wrapper = mount(RecipeList, {
      global: {
        plugins: [store],
        stubs: {
          'router-link': true,
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
      }
    })
  })

  it('renders the component title and description', () => {
    const title = wrapper.find('h1')
    const description = wrapper.find('p')
    expect(title.exists()).toBe(true)
    expect(description.exists()).toBe(true)
    expect(title.text()).toContain('Your Recipes')
    expect(description.text()).toContain('Discover recipes')
  })

  it('displays the search input and filters', () => {
    const searchInput = wrapper.find('input[type="text"]')
    const cuisineSelect = wrapper.find('select')
    expect(searchInput.exists()).toBe(true)
    expect(cuisineSelect.exists()).toBe(true)
  })

  it('displays only user recipes in the grid', async () => {
    await wrapper.vm.$nextTick()
    const recipeCards = wrapper.findAll('.bg-white.rounded-xl')
    expect(recipeCards.length).toBeGreaterThan(0)
  })

  it('shows loading state when loading is true', async () => {
    store.state.loading = true
    await wrapper.vm.$nextTick()
    const loadingSpinner = wrapper.find('.animate-spin')
    expect(loadingSpinner.exists()).toBe(true)
  })

  it('shows error state when error exists', async () => {
    store.state.error = 'Failed to load recipes'
    await wrapper.vm.$nextTick()
    const errorMessage = wrapper.find('.text-red-600')
    expect(errorMessage.exists()).toBe(true)
    expect(errorMessage.text()).toContain('Failed to load recipes')
  })

  it('filters recipes based on search query', async () => {
    const searchInput = wrapper.find('input[type="text"]')
    await searchInput.setValue('Carbonara')
    await wrapper.vm.$nextTick()
    const recipeCards = wrapper.findAll('.bg-white.rounded-xl')
    expect(recipeCards.length).toBeGreaterThan(0)
  })

  it('filters recipes based on cuisine selection', async () => {
    const cuisineSelect = wrapper.find('select')
    await cuisineSelect.setValue('Italian')
    await wrapper.vm.$nextTick()
    const recipeCards = wrapper.findAll('.bg-white.rounded-xl')
    expect(recipeCards.length).toBeGreaterThan(0)
  })

  it('resets filters when reset button is clicked', async () => {
    const searchInput = wrapper.find('input[type="text"]')
    const cuisineSelect = wrapper.find('select')
    const resetButton = wrapper.find('button')

    await searchInput.setValue('Carbonara')
    await cuisineSelect.setValue('Italian')
    await resetButton.trigger('click')
    await wrapper.vm.$nextTick()

    expect(searchInput.element.value).toBe('')
    expect(cuisineSelect.element.value).toBe('')
  })
}) 