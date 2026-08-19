import { http } from './client'
import type {
  ActivityLog,
  Board,
  DashboardStats,
  Department,
  Employee,
  EmployeeFilters,
  Locale,
  Paginated,
  Task,
  TaskFilters,
  TaskStatus,
  Theme,
  User,
} from '@/types'

interface LoginResponse {
  access_token: string
  token_type: string
  expires_in: number
  user: User
}

export const authApi = {
  login: (email: string, password: string) =>
    http.post<LoginResponse>('/login', { email, password }).then((r) => r.data),
  logout: () => http.post('/logout').then((r) => r.data),
  me: () => http.get<{ data: User }>('/me').then((r) => r.data.data),
  updateProfile: (payload: Partial<User> & {
    current_password?: string
    password?: string
    password_confirmation?: string
  }) => http.put<{ data: User }>('/profile', payload).then((r) => r.data.data),
}

export const uploadsApi = {
  /** Загружает изображение и возвращает публичный URL для поля avatar. */
  avatar: (file: File) => {
    const form = new FormData()
    form.append('file', file)

    return http
      .post<{ url: string }>('/uploads/avatar', form, { timeout: 60000 })
      .then((r) => r.data.url)
  },
}

export const dashboardApi = {
  stats: () => http.get<{ data: DashboardStats }>('/dashboard').then((r) => r.data.data),
}

export const employeesApi = {
  list: (params: EmployeeFilters = {}) =>
    http.get<Paginated<Employee>>('/employees', { params }).then((r) => r.data),
  get: (id: number) => http.get<{ data: Employee }>(`/employees/${id}`).then((r) => r.data.data),
  create: (payload: Partial<Employee>) =>
    http.post<{ data: Employee }>('/employees', payload).then((r) => r.data.data),
  update: (id: number, payload: Partial<Employee>) =>
    http.put<{ data: Employee }>(`/employees/${id}`, payload).then((r) => r.data.data),
  remove: (id: number) => http.delete(`/employees/${id}`).then((r) => r.data),

}

export const departmentsApi = {
  list: (params: { search?: string; page?: number; all?: boolean } = {}) =>
    http.get<Paginated<Department>>('/departments', { params }).then((r) => r.data),
  all: () =>
    http.get<{ data: Department[] }>('/departments', { params: { all: true } }).then((r) => r.data.data),
  create: (payload: Partial<Department>) =>
    http.post<{ data: Department }>('/departments', payload).then((r) => r.data.data),
  update: (id: number, payload: Partial<Department>) =>
    http.put<{ data: Department }>(`/departments/${id}`, payload).then((r) => r.data.data),
  remove: (id: number) => http.delete(`/departments/${id}`).then((r) => r.data),
}

export const tasksApi = {
  board: (params: TaskFilters = {}) =>
    http.get<{ data: Board }>('/tasks', { params: { ...params, board: 1 } }).then((r) => r.data.data),
  list: (params: TaskFilters & { page?: number } = {}) =>
    http.get<Paginated<Task>>('/tasks', { params }).then((r) => r.data),
  get: (id: number) => http.get<{ data: Task }>(`/tasks/${id}`).then((r) => r.data.data),
  create: (payload: Partial<Task>) =>
    http.post<{ data: Task }>('/tasks', payload).then((r) => r.data.data),
  update: (id: number, payload: Partial<Task>) =>
    http.put<{ data: Task }>(`/tasks/${id}`, payload).then((r) => r.data.data),
  move: (id: number, status: TaskStatus, position: number) =>
    http.patch<{ data: Task }>(`/tasks/${id}/move`, { status, position }).then((r) => r.data.data),
  remove: (id: number) => http.delete(`/tasks/${id}`).then((r) => r.data),
}

export const activityApi = {
  list: (params: {
    action?: string
    entity?: string
    user_id?: number
    date_from?: string
    date_to?: string
    page?: number
  } = {}) => http.get<Paginated<ActivityLog>>('/activity-logs', { params }).then((r) => r.data),
}

export type { Locale, Theme }
