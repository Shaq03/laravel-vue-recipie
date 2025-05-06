import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import Account from '../Account.vue'
import { useRouter } from 'vue-router'
import axios from '../../../axios'

const mockRouter = {
  push: vi.fn()
}

vi.mock('vue-router', () => ({
  useRouter: () => mockRouter
}))

vi.mock('../../../axios', () => ({
  default: {
    put: vi.fn()
  }
}))

describe('Account.vue', () => {
  let wrapper
  const mockUser = {
    username: 'testuser',
    email: 'test@example.com'
  }

  beforeEach(() => {
    vi.clearAllMocks()
    
    const localStorageMock = {
      getItem: vi.fn(() => JSON.stringify(mockUser)),
      setItem: vi.fn(),
      removeItem: vi.fn()
    }
    Object.defineProperty(window, 'localStorage', {
      value: localStorageMock
    })

    wrapper = mount(Account, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })
  })

  it('loads user data from localStorage on mount', () => {
    expect(wrapper.vm.username).toBe(mockUser.username)
    expect(wrapper.vm.email).toBe(mockUser.email)
  })

  it('updates profile successfully', async () => {
    const updatedUser = { ...mockUser, username: 'newusername' }
    axios.put.mockResolvedValueOnce({ data: { user: updatedUser } })

    wrapper.vm.username = 'newusername'
    await wrapper.vm.updateProfile()

    expect(axios.put).toHaveBeenCalledWith('/api/v1/user/profile', {
      username: 'newusername',
      email: mockUser.email
    })
    expect(wrapper.vm.success).toBe('Profile updated successfully')
    expect(wrapper.vm.error).toBe('')
  })

  it('handles profile update error', async () => {
    const errorMessage = 'Update failed'
    axios.put.mockRejectedValueOnce({ 
      response: { data: { message: errorMessage } }
    })

    await wrapper.vm.updateProfile()

    expect(wrapper.vm.error).toBe(errorMessage)
    expect(wrapper.vm.success).toBe('')
  })

  it('updates password successfully', async () => {
    axios.put.mockResolvedValueOnce({ data: {} })

    wrapper.vm.currentPassword = 'oldpass'
    wrapper.vm.newPassword = 'newpass'
    wrapper.vm.confirmPassword = 'newpass'
    await wrapper.vm.updatePassword()

    expect(axios.put).toHaveBeenCalledWith('/api/v1/user/password', {
      current_password: 'oldpass',
      new_password: 'newpass',
      new_password_confirmation: 'newpass'
    })
    expect(wrapper.vm.success).toBe('Password updated successfully')
    expect(wrapper.vm.error).toBe('')
    expect(wrapper.vm.currentPassword).toBe('')
    expect(wrapper.vm.newPassword).toBe('')
    expect(wrapper.vm.confirmPassword).toBe('')
  })

  it('shows error when passwords do not match', async () => {
    wrapper.vm.currentPassword = 'oldpass'
    wrapper.vm.newPassword = 'newpass'
    wrapper.vm.confirmPassword = 'differentpass'
    await wrapper.vm.updatePassword()

    expect(wrapper.vm.error).toBe('New passwords do not match')
    expect(axios.put).not.toHaveBeenCalled()
  })

  it('handles password update error', async () => {
    const errorMessage = 'Password update failed'
    axios.put.mockRejectedValueOnce({ 
      response: { data: { message: errorMessage } }
    })

    wrapper.vm.currentPassword = 'oldpass'
    wrapper.vm.newPassword = 'newpass'
    wrapper.vm.confirmPassword = 'newpass'
    await wrapper.vm.updatePassword()

    expect(wrapper.vm.error).toBe(errorMessage)
    expect(wrapper.vm.success).toBe('')
  })

  it('logs out user successfully', async () => {
    await wrapper.vm.logout()

    expect(localStorage.removeItem).toHaveBeenCalledWith('token')
    expect(localStorage.removeItem).toHaveBeenCalledWith('user')
    expect(mockRouter.push).toHaveBeenCalledWith('/login')
  })
}) 