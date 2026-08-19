export type Role = 'admin' | 'manager' | 'employee'
export type Theme = 'light' | 'dark'
export type Locale = 'ru' | 'en' | 'kk'
export type TaskStatus = 'todo' | 'in_progress' | 'review' | 'done'
export type TaskPriority = 'low' | 'medium' | 'high'

export interface User {
  id: number
  employee?: { id: number; full_name: string } | null
  name: string
  email: string
  role: Role
  avatar: string | null
  language: Locale
  theme: Theme
}

export interface UserPayload {
  name?: string
  email?: string
  role?: Role
  password?: string
}

export interface Department {
  id: number
  name: string
  description: string | null
  employees_count?: number
  created_at?: string
}

export interface EmployeeAccount {
  id: number
  email: string
  role: Role
}

export interface Employee {
  id: number
  user_id?: number | null
  account?: EmployeeAccount | null
  first_name: string
  last_name: string
  full_name: string
  email: string
  phone: string | null
  department_id: number | null
  department?: Department
  position: string | null
  hire_date: string | null
  avatar: string | null
  tasks_count?: number
  tasks?: Task[]
  created_at?: string
}

export interface Task {
  id: number
  title: string
  description: string | null
  employee_id: number | null
  employee?: Employee
  status: TaskStatus
  priority: TaskPriority
  deadline: string | null
  position: number
  is_overdue: boolean
  created_at?: string
  updated_at?: string
}

export interface ActivityLog {
  id: number
  user_id: number | null
  user?: User
  action: string
  entity: string
  entity_id: number | null
  description: string | null
  created_at: string
}

export interface Paginated<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface DashboardStats {
  totals: {
    employees: number
    departments: number
    tasks: number
    overdue: number
  }
  tasks_by_status: Record<string, number>
  tasks_by_priority: Record<string, number>
  employees_by_department: Array<{ name: string; total: number }>
  tasks_per_week: Array<{ label: string; created: number; done: number }>
  recent_tasks: Task[]
  recent_activity: ActivityLog[]
}

export type Board = Record<TaskStatus, Task[]>

export interface EmployeeFilters {
  search?: string
  role?: Role | null
  department_id?: number | null
  position?: string
  sort?: string
  direction?: 'asc' | 'desc'
  page?: number
  per_page?: number
}

export interface TaskFilters {
  search?: string
  status?: TaskStatus | null
  priority?: TaskPriority | null
  employee_id?: number | null
  department_id?: number | null
}
