import { describe, it, expect, vi, beforeEach } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import Login from '../Login.vue'

const mockRouter = {
  push: vi.fn()
}

const mockStore = {
  dispatch: vi.fn()
}

vi.mock('vue-router', () => ({
  useRouter: () => mockRouter
}))

vi.mock('vuex', () => ({
  useStore: () => mockStore
}))

describe('Login.vue', () => {
  let wrapper

  beforeEach(() => {
    vi.clearAllMocks()
    wrapper = shallowMount(Login, {
      global: {
        stubs: {
          RouterLink: true
        }
      }
    })
  })

  it('renders login form', () => {
    expect(wrapper.find('form').exists()).toBe(true)
    expect(wrapper.find('input[type="email"]').exists()).toBe(true)
    expect(wrapper.find('input[type="password"]').exists()).toBe(true)
    expect(wrapper.find('button[type="submit"]').exists()).toBe(true)
  })

  it('shows error when submitting empty form', async () => {
    await wrapper.find('form').trigger('submit')
    expect(wrapper.vm.error).toBe('Please enter both email and password')
  })

  it('handles successful login', async () => {
    mockStore.dispatch.mockResolvedValueOnce({})
    wrapper.vm.email = 'test@example.com'
    wrapper.vm.password = 'password123'
    await wrapper.find('form').trigger('submit')
    expect(mockStore.dispatch).toHaveBeenCalledWith('login', {
      email: 'test@example.com',
      password: 'password123'
    })
    expect(mockRouter.push).toHaveBeenCalledWith('/')
  })

  it('handles login error', async () => {
    mockStore.dispatch.mockRejectedValueOnce({
      response: { data: { message: 'Invalid credentials' } }
    })
    wrapper.vm.email = 'test@example.com'
    wrapper.vm.password = 'wrongpassword'
    await wrapper.find('form').trigger('submit')
    expect(wrapper.vm.error).toBe('An error occurred during login')
  })
}) 