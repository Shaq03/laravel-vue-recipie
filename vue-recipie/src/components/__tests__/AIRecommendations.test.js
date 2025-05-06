import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createStore } from 'vuex'
import AIRecommendations from '../AIRecommendations.vue'
import { ChefHat, Search, Loader, X, Star, Clock, Users, Settings } from 'lucide-vue-next'

const mockStore = createStore({
  state: {
    token: 'test-token',
    user: { id: 1 }
  },
  getters: {
    isAuthenticated: () => true,
    aiSearchResults: () => [],
    isFavorite: () => (id) => false
  },
  mutations: {
    CLEAR_AI_SEARCH_RESULTS: vi.fn(),
    SET_AI_SEARCH_RESULTS: vi.fn()
  },
  actions: {
    searchAIRecipes: vi.fn(),
    toggleFavorite: vi.fn(),
    logout: vi.fn()
  }
})

describe('AIRecommendations.vue', () => {
  let wrapper

  beforeEach(() => {
    vi.clearAllMocks()
    wrapper = mount(AIRecommendations, {
      global: {
        plugins: [mockStore],
        stubs: {
          Navigation: true,
          RecipeDetailModal: true,
          ChefHat,
          Search,
          Loader,
          X,
          Star,
          Clock,
          Users,
          Settings
        }
      }
    })
  })

  it('renders the basic component structure', () => {
    expect(wrapper.find('h1').text()).toBe('AI Recipe Recommendations')
    expect(wrapper.find('input[type="text"]').exists()).toBe(true)
    expect(wrapper.find('form').exists()).toBe(true)
  })

  it('adds an ingredient when pressing enter', async () => {
    const input = wrapper.find('input[type="text"]')
    await input.setValue('tomato')
    await wrapper.vm.addIngredient()
    await wrapper.vm.$nextTick()
    
    expect(wrapper.vm.selectedIngredients).toContain('tomato')
  })

  it('shows error when trying to search without ingredients', async () => {
    const form = wrapper.find('form')
    await form.trigger('submit')
    await wrapper.vm.$nextTick()
    
    expect(wrapper.vm.error).toBe('Please add at least one ingredient')
  })

  it('clears ingredients when clear button is clicked', async () => {
    // First add an ingredient
    const input = wrapper.find('input[type="text"]')
    await input.setValue('tomato')
    await wrapper.vm.addIngredient()
    await wrapper.vm.$nextTick()
    
    // Then clear it
    const clearButton = wrapper.find('button.bg-gray-200')
    await clearButton.trigger('click')
    await wrapper.vm.$nextTick()
    
    expect(wrapper.vm.selectedIngredients).toHaveLength(0)
  })

  it('toggles preferences panel', async () => {
    const preferencesButton = wrapper.find('button.inline-flex.items-center.px-4.py-2')
    await preferencesButton.trigger('click')
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.showPreferences).toBe(true)
    
    await preferencesButton.trigger('click')
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.showPreferences).toBe(false)
  })
}) 