import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { authApi } from '@/api'
import { setUnauthorizedHandler, tokenStorage } from '@/api/client'
import router from '@/router'
import type { Role, User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(tokenStorage.get())
  const loading = ref(false)
  const initialized = ref(false)

  const isAuthenticated = computed(() => Boolean(token.value))
  const role = computed<Role | null>(() => user.value?.role ?? null)
  const initials = computed(() =>
    (user.value?.name ?? '')
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((part) => part[0]?.toUpperCase())
      .join(''),
  )

  function can(...roles: Role[]): boolean {
    return role.value !== null && roles.includes(role.value)
  }

  async function login(email: string, password: string): Promise<void> {
    loading.value = true
    try {
      const response = await authApi.login(email, password)
      token.value = response.access_token
      tokenStorage.set(response.access_token)
      user.value = response.user
      initialized.value = true
    } finally {
      loading.value = false
    }
  }

  async function fetchUser(): Promise<void> {
    if (!token.value) {
      initialized.value = true
      return
    }
    try {
      user.value = await authApi.me()
    } catch {
      reset()
    } finally {
      initialized.value = true
    }
  }

  function reset(): void {
    user.value = null
    token.value = null
    tokenStorage.clear()
  }

  async function logout(): Promise<void> {
    try {
      await authApi.logout()
    } catch {
      // Токен мог протухнуть — локальный выход всё равно выполняем.
    } finally {
      reset()
      await router.push({ name: 'login' })
    }
  }

  function setUser(next: User): void {
    user.value = next
  }

  setUnauthorizedHandler(() => {
    reset()
    void router.push({ name: 'login', query: { expired: '1' } })
  })

  return { user, token, loading, initialized, isAuthenticated, role, initials, can, login, logout, fetchUser, setUser, reset }
})
