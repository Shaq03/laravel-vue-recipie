import { describe, it, expect, vi, beforeEach } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import Register from '../Register.vue'

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

describe('Register.vue', () => {
  let wrapper

  beforeEach(() => {
    vi.clearAllMocks()
    wrapper = shallowMount(Register, {
      global: {
        stubs: {
          RouterLink: true
        }
      }
    })
  })

  it('renders registration form', () => {
    expect(wrapper.find('form').exists()).toBe(true)
    expect(wrapper.find('input[name="username"]').exists()).toBe(true)
    expect(wrapper.find('input[name="email"]').exists()).toBe(true)
    expect(wrapper.find('input[name="password"]').exists()).toBe(true)
    expect(wrapper.find('input[name="confirm-password"]').exists()).toBe(true)
    expect(wrapper.find('button[type="submit"]').exists()).toBe(true)
  })

  it('shows error when passwords do not match', async () => {
    wrapper.vm.password = 'password123'
    wrapper.vm.confirmPassword = 'password456'
    await wrapper.find('form').trigger('submit')
    expect(wrapper.vm.error).toBe('Passwords do not match')
  })

  it('handles successful registration', async () => {
    mockStore.dispatch.mockResolvedValueOnce({})
    wrapper.vm.username = 'testuser'
    wrapper.vm.email = 'test@example.com'
    wrapper.vm.password = 'password123'
    wrapper.vm.confirmPassword = 'password123'
    await wrapper.find('form').trigger('submit')
    expect(mockStore.dispatch).toHaveBeenCalledWith('register', {
      username: 'testuser',
      email: 'test@example.com',
      password: 'password123',
      password_confirmation: 'password123'
    })
    expect(mockRouter.push).toHaveBeenCalledWith('/')
  })

  it('handles registration error', async () => {
    mockStore.dispatch.mockRejectedValueOnce({
      message: 'Email already taken'
    })
    wrapper.vm.username = 'testuser'
    wrapper.vm.email = 'test@example.com'
    wrapper.vm.password = 'password123'
    wrapper.vm.confirmPassword = 'password123'
    await wrapper.find('form').trigger('submit')
    expect(wrapper.vm.error).toBe('Email already taken')
  })
}) 