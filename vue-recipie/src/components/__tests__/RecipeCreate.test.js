import { describe, it, expect, vi, beforeEach } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import RecipeCreate from '../RecipeCreate.vue'

const mockRouter = {
  push: vi.fn()
}

const mockStore = {
  state: {
    token: 'test-token',
    user: { id: 1 }
  },
  getters: {
    isAuthenticated: () => true
  },
  dispatch: vi.fn().mockResolvedValue({})
}

vi.mock('vue-router', () => ({
  useRouter: () => mockRouter
}))

vi.mock('vuex', () => ({
  useStore: () => mockStore
}))

describe('RecipeCreate.vue', () => {
  let wrapper

  beforeEach(() => {
    vi.clearAllMocks()
    wrapper = shallowMount(RecipeCreate, {
      global: {
        stubs: {
          Navigation: true
        }
      }
    })
  })

  it('renders the basic form structure', () => {
    expect(wrapper.find('h1').text()).toBe('Create New Recipe')
    expect(wrapper.find('form').exists()).toBe(true)
    expect(wrapper.find('input#title').exists()).toBe(true)
    expect(wrapper.find('textarea#description').exists()).toBe(true)
  })

  it('adds and removes ingredients', async () => {
    await wrapper.vm.addIngredient()
    expect(wrapper.vm.recipe.ingredients).toHaveLength(2)
    
    await wrapper.vm.removeIngredient(0)
    expect(wrapper.vm.recipe.ingredients).toHaveLength(1)
  })

  it('adds and removes instructions', async () => {
    await wrapper.vm.addInstruction()
    expect(wrapper.vm.recipe.instructions).toHaveLength(2)
    
    await wrapper.vm.removeInstruction(0)
    expect(wrapper.vm.recipe.instructions).toHaveLength(1)
  })

  it('submits the form with valid data', async () => {
    const recipeData = {
      title: 'Test Recipe',
      description: 'A test recipe',
      cooking_time: '30',
      servings: '4',
      difficulty: 'easy',
      ingredients: ['Test ingredient'],
      instructions: ['Test instruction'],
      image_url: '',
      dietary_restrictions: []
    }
    
    wrapper.vm.recipe = { ...recipeData }
    await wrapper.vm.handleSubmit()
    
    expect(mockStore.dispatch).toHaveBeenCalledWith('createRecipe', recipeData)
    expect(mockRouter.push).toHaveBeenCalledWith('/recipes')
  })

  it('shows error when form submission fails', async () => {
    mockStore.dispatch.mockRejectedValueOnce({ 
      response: { data: { message: 'Failed to create recipe' } } 
    })
    
    const recipeData = {
      title: 'Test Recipe',
      description: 'A test recipe',
      cooking_time: '30',
      servings: '4',
      difficulty: 'easy',
      ingredients: ['Test ingredient'],
      instructions: ['Test instruction'],
      image_url: '',
      dietary_restrictions: []
    }
    
    wrapper.vm.recipe = { ...recipeData }
    await wrapper.vm.handleSubmit()
    expect(wrapper.vm.error).toBe('Failed to create recipe')
  })
}) 