import axios, {
  AxiosError,
  type AxiosInstance,
  type InternalAxiosRequestConfig,
} from 'axios'

const TOKEN_KEY = 'workflow_token'

export const tokenStorage = {
  get: (): string | null => localStorage.getItem(TOKEN_KEY),
  set: (token: string) => localStorage.setItem(TOKEN_KEY, token),
  clear: () => localStorage.removeItem(TOKEN_KEY),
}

export const http: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? '/api',
  headers: { Accept: 'application/json' },
  timeout: 15000,
})

http.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = tokenStorage.get()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

type RetriableRequest = InternalAxiosRequestConfig & { _retry?: boolean }

let refreshing: Promise<string> | null = null
let onUnauthorized: (() => void) | null = null

/** Регистрирует реакцию на окончательный отказ авторизации (вызывается из auth-store). */
export function setUnauthorizedHandler(handler: () => void): void {
  onUnauthorized = handler
}

async function refreshToken(): Promise<string> {
  refreshing ??= axios
    .post<{ access_token: string }>(
      `${http.defaults.baseURL}/refresh`,
      {},
      { headers: { Accept: 'application/json', Authorization: `Bearer ${tokenStorage.get()}` } },
    )
    .then((response) => {
      const token = response.data.access_token
      tokenStorage.set(token)
      return token
    })
    .finally(() => {
      refreshing = null
    })

  return refreshing
}

http.interceptors.response.use(
  (response) => response,
  async (error: AxiosError<{ message?: string; code?: string }>) => {
    const original = error.config as RetriableRequest | undefined
    const status = error.response?.status

    const isRefreshCall = original?.url?.includes('/refresh')
    const isLoginCall = original?.url?.includes('/login')

    if (status === 401 && original && !original._retry && !isRefreshCall && !isLoginCall) {
      original._retry = true
      try {
        const token = await refreshToken()
        original.headers.Authorization = `Bearer ${token}`
        return http(original)
      } catch {
        tokenStorage.clear()
        onUnauthorized?.()
      }
    }

    if (status === 401 && (isRefreshCall || original?._retry)) {
      tokenStorage.clear()
      onUnauthorized?.()
    }

    return Promise.reject(error)
  },
)

/** Достаёт человекочитаемое сообщение из ответа Laravel. */
export function apiMessage(error: unknown, fallback = 'Произошла ошибка'): string {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined
    if (data?.errors) {
      const first = Object.values(data.errors)[0]
      if (first?.[0]) return first[0]
    }
    if (data?.message) return data.message
  }
  return fallback
}
