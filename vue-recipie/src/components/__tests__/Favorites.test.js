import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createStore } from 'vuex'
import Favorites from '../Favorites.vue'

const mockFavorites = [
  {
    id: 1,
    title: 'Favorite Recipe 1',
    description: 'A delicious favorite recipe',
    cooking_time: 30,
    servings: 4,
    difficulty: 'medium',
    ingredients: ['ingredient 1', 'ingredient 2'],
    instructions: ['Step 1', 'Step 2'],
    image_url: '/test-image.jpg'
  },
  {
    id: 2,
    title: 'Favorite Recipe 2',
    description: 'Another favorite recipe',
    cooking_time: 45,
    servings: 6,
    difficulty: 'hard',
    ingredients: ['ingredient 3', 'ingredient 4'],
    instructions: ['Step 1', 'Step 2'],
    source: 'web',
    source_url: 'https://example.com/recipe'
  }
]

const mockStore = createStore({
  getters: {
    allFavorites: () => mockFavorites
  },
  actions: {
    fetchFavorites: vi.fn(),
    toggleFavorite: vi.fn()
  }
})

describe('Favorites.vue', () => {
  let wrapper

  beforeEach(() => {
    wrapper = mount(Favorites, {
      global: {
        plugins: [mockStore],
        stubs: {
          Navigation: true,
          Star: true,
          Clock: true,
          Users: true,
          ChefHat: true,
          ExternalLink: true
        }
      }
    })
  })

  it('renders the component correctly', () => {
    expect(wrapper.find('h1').text()).toBe('Your Favorite Recipes')
    expect(wrapper.find('p').text()).toBe('All your saved recipes in one place')
  })

  it('displays empty state when no favorites', () => {
    const emptyStore = createStore({
      getters: {
        allFavorites: () => []
      },
      actions: {
        fetchFavorites: vi.fn(),
        toggleFavorite: vi.fn()
      }
    })

    const emptyWrapper = mount(Favorites, {
      global: {
        plugins: [emptyStore],
        stubs: {
          Navigation: true,
          Star: true,
          Clock: true,
          Users: true,
          ChefHat: true,
          ExternalLink: true
        }
      }
    })

    expect(emptyWrapper.text()).toContain('No favorites yet')
    expect(emptyWrapper.text()).toContain('Start adding recipes to your favorites')
  })

  it('displays favorite recipes', () => {
    const recipes = wrapper.findAll('.bg-white')
    expect(recipes.length).toBeGreaterThan(0)
    expect(wrapper.text()).toContain('Favorite Recipe 1')
    expect(wrapper.text()).toContain('Favorite Recipe 2')
  })

  it('displays recipe details correctly', () => {
    const firstRecipe = wrapper.find('.bg-white')
    expect(firstRecipe.text()).toContain('A delicious favorite recipe')
    expect(firstRecipe.text()).toContain('30 mins')
    expect(firstRecipe.text()).toContain('4 servings')
    expect(firstRecipe.text()).toContain('medium')
  })

  it('displays external link for web recipes', () => {
    const externalLink = wrapper.find('a[href="https://example.com/recipe"]')
    expect(externalLink.exists()).toBe(true)
    expect(externalLink.text()).toContain('View Original Recipe')
  })
}) 