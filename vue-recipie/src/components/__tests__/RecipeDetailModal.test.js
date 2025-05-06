import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import RecipeDetailModal from '../RecipeDetailModal.vue'

const mockRecipe = {
  id: 1,
  title: 'Test Recipe',
  description: 'A delicious test recipe',
  cooking_time: 30,
  servings: 4,
  difficulty: 'Medium',
  cuisines: ['Italian'],
  tags: ['Pasta'],
  ingredients: ['ingredient 1', 'ingredient 2'],
  instructions: ['Step 1', 'Step 2'],
  dietary_restrictions: ['Vegetarian'],
  image_url: '/test-image.jpg'
}

describe('RecipeDetailModal.vue', () => {
  it('renders recipe details correctly', () => {
    const onClose = vi.fn()
    const wrapper = mount(RecipeDetailModal, {
      props: {
        recipe: mockRecipe,
        onClose
      }
    })

    expect(wrapper.find('h1').text()).toBe('Test Recipe')
    expect(wrapper.text()).toContain('A delicious test recipe')
    expect(wrapper.text()).toContain('30')
    expect(wrapper.text()).toContain('4 servings')
    expect(wrapper.text()).toContain('Medium')
  })

  it('displays ingredients list', () => {
    const wrapper = mount(RecipeDetailModal, {
      props: {
        recipe: mockRecipe,
        onClose: vi.fn()
      }
    })

    const ingredients = wrapper.findAll('ul li')
    expect(ingredients).toHaveLength(2)
    expect(ingredients[0].text()).toBe('ingredient 1')
    expect(ingredients[1].text()).toBe('ingredient 2')
  })

  it('displays instructions list', () => {
    const wrapper = mount(RecipeDetailModal, {
      props: {
        recipe: mockRecipe,
        onClose: vi.fn()
      }
    })

    const instructions = wrapper.findAll('ol li')
    expect(instructions).toHaveLength(2)
    expect(instructions[0].text()).toBe('Step 1')
    expect(instructions[1].text()).toBe('Step 2')
  })

  it('displays dietary restrictions', () => {
    const wrapper = mount(RecipeDetailModal, {
      props: {
        recipe: mockRecipe,
        onClose: vi.fn()
      }
    })

    expect(wrapper.text()).toContain('Vegetarian')
  })

  it('displays cuisine and tag badges', () => {
    const wrapper = mount(RecipeDetailModal, {
      props: {
        recipe: mockRecipe,
        onClose: vi.fn()
      }
    })

    expect(wrapper.text()).toContain('Italian')
    expect(wrapper.text()).toContain('Pasta')
  })

  it('calls onClose when close button is clicked', async () => {
    const onClose = vi.fn()
    const wrapper = mount(RecipeDetailModal, {
      props: {
        recipe: mockRecipe,
        onClose
      }
    })

    await wrapper.find('button').trigger('click')
    expect(onClose).toHaveBeenCalled()
  })

  it('handles missing optional recipe data', () => {
    const minimalRecipe = {
      id: 1,
      title: 'Minimal Recipe',
      description: 'A recipe with minimal data',
      ingredients: ['ingredient 1'],
      instructions: ['Step 1']
    }

    const wrapper = mount(RecipeDetailModal, {
      props: {
        recipe: minimalRecipe,
        onClose: vi.fn()
      }
    })

    expect(wrapper.find('h1').text()).toBe('Minimal Recipe')
    expect(wrapper.text()).toContain('A recipe with minimal data')
    expect(wrapper.findAll('ul li')).toHaveLength(1)
    expect(wrapper.findAll('ol li')).toHaveLength(1)
  })
}) 