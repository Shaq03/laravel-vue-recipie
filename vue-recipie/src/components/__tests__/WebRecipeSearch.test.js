import { describe, it, expect, vi, beforeEach } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import WebRecipeSearch from '../WebRecipeSearch.vue'

const mockStore = {
  state: {
    token: 'test-token',
    user: { id: 1 }
  },
  getters: {
    isAuthenticated: () => true,
    webSearchResults: () => [],
    isFavorite: () => (id) => false
  },
  commit: vi.fn(),
  dispatch: vi.fn()
}

describe('WebRecipeSearch.vue', () => {
  let wrapper

  beforeEach(() => {
    vi.clearAllMocks()
    wrapper = shallowMount(WebRecipeSearch, {
      global: {
        provide: {
          store: mockStore
        },
        stubs: {
          Navigation: true
        }
      }
    })
  })

  it('renders the basic component structure', () => {
    expect(wrapper.find('h1').text()).toBe('Find Online Recipies')
    expect(wrapper.find('input[type="text"]').exists()).toBe(true)
  })

  it('adds a valid ingredient', async () => {
    wrapper.vm.currentIngredient = 'chicken'
    await wrapper.vm.addIngredient()
    expect(wrapper.vm.selectedIngredients).toContain('chicken')
  })

  it('shows error for invalid ingredient', async () => {
    wrapper.vm.currentIngredient = 'x'
    await wrapper.vm.addIngredient()
    expect(wrapper.vm.error).toBeTruthy()
    expect(wrapper.vm.selectedIngredients).toHaveLength(0)
  })

  it('shows error for duplicate ingredient', async () => {
    wrapper.vm.currentIngredient = 'chicken'
    await wrapper.vm.addIngredient()
    wrapper.vm.currentIngredient = 'chicken'
    await wrapper.vm.addIngredient()
    expect(wrapper.vm.error).toBe('This ingredient is already added')
  })

  it('removes an ingredient', async () => {
    wrapper.vm.selectedIngredients = ['chicken']
    await wrapper.vm.removeIngredient(0)
    expect(wrapper.vm.selectedIngredients).toHaveLength(0)
  })

  it('clears all ingredients', async () => {
    wrapper.vm.selectedIngredients = ['chicken', 'pasta']
    await wrapper.vm.clearIngredients()
    expect(wrapper.vm.selectedIngredients).toHaveLength(0)
    expect(mockStore.commit).toHaveBeenCalledWith('CLEAR_WEB_SEARCH_RESULTS')
  })

  it('shows error when searching without ingredients', async () => {
    await wrapper.vm.searchRecipes()
    expect(wrapper.vm.error).toBe('Please add at least one ingredient')
  })

  it('searches recipes with valid ingredients', async () => {
    wrapper.vm.selectedIngredients = ['chicken']
    await wrapper.vm.searchRecipes()
    expect(mockStore.dispatch).toHaveBeenCalledWith('searchWebRecipes', ['chicken'])
  })
}) 