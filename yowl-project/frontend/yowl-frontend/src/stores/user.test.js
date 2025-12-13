import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useUserStore } from './user'

// Mock apiService
vi.mock('@/services/apiService', () => ({
  default: {
    post: vi.fn(async (url, payload) => {
      if (url === '/login') {
        if (payload.email === 'banned@example.com') {
          throw { response: { data: { message: 'This account has been banned (age limit).' } } }
        }
        return { data: { user: { email: payload.email, roles: [] }, token: 'fake-token' } }
      }
      if (url === '/register') {
        return { data: { success: true, user: { email: payload.email } } }
      }
      if (url === '/logout') {
        return { data: { success: true } }
      }
      return { data: {} }
    }),
  get: vi.fn(async () => ({ data: { email: 'user@example.com', roles: [] } }))
  }
}))

describe('UserStore', () => {
  let store
  beforeEach(() => {
    setActivePinia(createPinia())
    store = useUserStore()
    store.user = null
    store.token = null
    localStorage.clear()
  })

  it('should login a valid user', async () => {
    const result = await store.loginUser({ identifier: 'user@example.com', password: 'pass' })
    expect(result.user.email).toBe('user@example.com')
    expect(store.token).toBe('fake-token')
    expect(store.isAuthenticated).toBe(true)
  })

  it('should handle banned user', async () => {
    await expect(store.loginUser({ identifier: 'banned@example.com', password: 'pass' }))
      .rejects.toThrow('This account has been banned (age limit).')
    expect(store.token).toBeNull()
    expect(store.isAuthenticated).toBe(false)
  })

  it('should logout user', async () => {
    await store.loginUser({ identifier: 'user@example.com', password: 'pass' })
    await store.logoutUser()
    expect(store.user).toBeNull()
    expect(store.token).toBeNull()
    expect(localStorage.getItem('token')).toBeNull()
  })

  it('should register user', async () => {
    const result = await store.registerUser({
      firstname: 'John',
      lastname: 'Doe',
      username: 'johndoe',
      email: 'john@example.com',
      birthdate: '1990-01-01',
      password: 'pass',
      password_confirmation: 'pass',
    })
    expect(result.success).toBe(true)
    expect(result.user.email).toBe('john@example.com')
  })
})
