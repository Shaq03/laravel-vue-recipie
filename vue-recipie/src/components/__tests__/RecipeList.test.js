import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import RecipeList from '../RecipeList.vue'
import axios from '../../axios'

vi.mock('../../axios')

const mockRecipes = [
  {
    id: 1,
    title: 'Test Recipe',
    description: 'A test recipe',
    image_url: '',
    servings: 2,
    cooking_time: 30,
    ingredients: ['Egg', 'Flour'],
    instructions: ['Step 1', 'Step 2'],
    difficulty: 'easy',
    cuisines: ['Italian'],
    source: 'user',
  },
  {
    id: 2,
    title: 'Another Recipe',
    description: 'Another test',
    image_url: '',
    servings: 4,
    cooking_time: 45,
    ingredients: ['Milk', 'Sugar'],
    instructions: ['Step 1', 'Step 2'],
    difficulty: 'medium',
    cuisines: ['French'],
    source: 'user',
  }
]

const createMockStore = () => {
  return createStore({
    state: {
      loading: false,
      error: null,
      recipes: mockRecipes,
      favorites: []
    },
    getters: {
      allRecipes: (state) => state.recipes,
      isFavorite: () => () => false,
      isAuthenticated: () => true
    },
    actions: {
      fetchRecipes: vi.fn(),
      fetchFavorites: vi.fn()
    }
  })
}

describe('RecipeList.vue', () => {
  let wrapper
  let store

  beforeEach(() => {
    store = createMockStore()
    vi.clearAllMocks()
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
    expect(title.text()).toContain('Recipe Collection')
    expect(description.text()).toContain('Discover and explore our curated recipes')
  })

  it('displays the search input and filters', () => {
    const searchInput = wrapper.find('input[type="text"]')
    const cuisineSelect = wrapper.find('select')
    expect(searchInput.exists()).toBe(true)
    expect(cuisineSelect.exists()).toBe(true)
  })

  it('displays only user recipes in the grid', async () => {
    await wrapper.vm.$nextTick()
    const recipeTitles = wrapper.findAll('h3')
    expect(recipeTitles.length).toBe(2)
    expect(recipeTitles[0].text()).toBe('Test Recipe')
    expect(recipeTitles[1].text()).toBe('Another Recipe')
  })

  it('shows loading state when loading is true', async () => {
    store.state.loading = true
    await wrapper.vm.$nextTick()
    const spinner = wrapper.find('.animate-spin')
    expect(spinner.exists()).toBe(true)
  })

  it('shows error state when error exists', async () => {
    store.state.error = 'Failed to load recipes'
    await wrapper.vm.$nextTick()
    const errorMessage = wrapper.find('p.text-red-600')
    expect(errorMessage.exists()).toBe(true)
    expect(errorMessage.text()).toBe('Failed to load recipes')
  })

  it('filters recipes based on search query', async () => {
    const searchInput = wrapper.find('input[type="text"]')
    await searchInput.setValue('Another')
    await wrapper.vm.$nextTick()
    const recipeTitles = wrapper.findAll('h3')
    expect(recipeTitles.length).toBe(1)
    expect(recipeTitles[0].text()).toBe('Another Recipe')
  })

  it('filters recipes based on cuisine selection', async () => {
    const cuisineSelect = wrapper.find('select')
    await cuisineSelect.setValue('French')
    await wrapper.vm.$nextTick()
    const recipeTitles = wrapper.findAll('h3')
    expect(recipeTitles.length).toBe(1)
    expect(recipeTitles[0].text()).toBe('Another Recipe')
  })

  it('resets filters when reset button is clicked', async () => {
    // Set some filters
    const searchInput = wrapper.find('input[type="text"]')
    await searchInput.setValue('Another')
    await wrapper.vm.$nextTick()
    
    // Click reset
    const resetBtn = wrapper.find('button.bg-gray-600')
    await resetBtn.trigger('click')
    await wrapper.vm.$nextTick()
    
    // Check if all recipes are visible
    const recipeTitles = wrapper.findAll('h3')
    expect(recipeTitles.length).toBe(2)
    expect(recipeTitles[0].text()).toBe('Test Recipe')
    expect(recipeTitles[1].text()).toBe('Another Recipe')
  })
}) 