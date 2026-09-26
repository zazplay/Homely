import { defineStore } from 'pinia'
import type { AuthResponse, Role, User } from '~/types/api'

export interface RegisterPayload {
  name: string
  email: string
  password: string
  role: Exclude<Role, 'admin'>
  /** Realtors only */
  agency?: string
  license_number?: string
}

export const useAuthStore = defineStore('auth', () => {
  const api = useApi()
  const authToken = useAuthToken()

  const user = ref<User | null>(null)

  const isLoggedIn = computed(() => user.value !== null)
  const canManageListings = computed(() => user.value?.role === 'realtor' || user.value?.role === 'admin')

  function setSession(response: AuthResponse) {
    authToken.set(response.token)
    user.value = response.user
  }

  function clearSession() {
    authToken.set(null)
    user.value = null
  }

  async function login(email: string, password: string) {
    setSession(await api<AuthResponse>('/auth/login', { method: 'POST', body: { email, password } }))
  }

  async function register(payload: RegisterPayload) {
    setSession(await api<AuthResponse>('/auth/register', { method: 'POST', body: payload }))
  }

  /** Restores the user from a saved token; a revoked/expired token just logs out. */
  async function fetchMe() {
    if (!authToken.token.value) return

    try {
      user.value = await api<User>('/auth/me')
    } catch {
      clearSession()
    }
  }

  async function logout() {
    try {
      await api('/auth/logout', { method: 'POST' })
    } finally {
      clearSession()
    }
  }

  function owns(realtorId: number): boolean {
    return user.value !== null && (user.value.id === realtorId || user.value.role === 'admin')
  }

  return { user, isLoggedIn, canManageListings, login, register, fetchMe, logout, owns }
})
