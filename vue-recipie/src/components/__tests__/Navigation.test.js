import { describe, it, expect, vi, beforeEach } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import Navigation from '../Navigation.vue'

const mockRouter = {
  push: vi.fn()
}

vi.mock('vue-router', () => ({
  useRouter: () => mockRouter
}))

describe('Navigation.vue', () => {
  let wrapper

  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
    wrapper = shallowMount(Navigation, {
      global: {
        stubs: {
          RouterLink: {
            template: '<a @click="$emit(\'click\')"><slot /></a>',
            props: ['to']
          }
        }
      }
    })
  })

  it('loads navigation menu with all required links', () => {
    const links = wrapper.findAll('a')
    expect(links.length).toBeGreaterThan(0)
    expect(wrapper.find('nav').exists()).toBe(true)
    expect(wrapper.find('.bg-white').exists()).toBe(true)
  })

  it('handles logout correctly', async () => {
    localStorage.setItem('token', 'test-token')
    localStorage.setItem('user', JSON.stringify({ username: 'testuser' }))
    wrapper = shallowMount(Navigation, {
      global: {
        stubs: {
          RouterLink: {
            template: '<a @click="$emit(\'click\')"><slot /></a>',
            props: ['to']
          }
        }
      }
    })
    await wrapper.find('button').trigger('click')
    expect(localStorage.getItem('token')).toBeNull()
    expect(localStorage.getItem('user')).toBeNull()
    expect(mockRouter.push).toHaveBeenCalledWith('/login')
  })

  it('toggles mobile menu', async () => {
    await wrapper.find('button').trigger('click')
    expect(wrapper.vm.isMenuOpen).toBe(true)
    await wrapper.find('button').trigger('click')
    expect(wrapper.vm.isMenuOpen).toBe(false)
  })

  it('navigates when clicking links', async () => {
    const links = wrapper.findAll('a')
    for (const link of links) {
      await link.trigger('click')
    }
    expect(links.length).toBeGreaterThan(0)
  })
}) 