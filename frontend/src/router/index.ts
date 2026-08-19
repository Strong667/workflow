import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import type { Role } from '@/types'

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    guestOnly?: boolean
    roles?: Role[]
    titleKey?: string
  }
}

// Все страницы подключаются динамическими импортами — отдельный чанк на маршрут.
const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { guestOnly: true, titleKey: 'auth.title' },
  },
  {
    path: '/',
    component: () => import('@/layouts/DefaultLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: { name: 'dashboard' } },
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('@/views/DashboardView.vue'),
        meta: { titleKey: 'nav.dashboard' },
      },
      {
        path: 'employees',
        name: 'employees',
        component: () => import('@/views/EmployeesView.vue'),
        meta: { titleKey: 'nav.employees' },
      },
      {
        path: 'employees/create',
        name: 'employees.create',
        component: () => import('@/views/EmployeeFormView.vue'),
        meta: { titleKey: 'employees.createTitle', roles: ['admin', 'manager'] },
      },
      {
        path: 'employees/:id(\\d+)',
        name: 'employees.show',
        component: () => import('@/views/EmployeeProfileView.vue'),
        meta: { titleKey: 'employees.profile' },
      },
      {
        path: 'employees/:id(\\d+)/edit',
        name: 'employees.edit',
        component: () => import('@/views/EmployeeFormView.vue'),
        meta: { titleKey: 'employees.editTitle', roles: ['admin', 'manager'] },
      },
      {
        path: 'tasks',
        name: 'tasks',
        component: () => import('@/views/TasksView.vue'),
        meta: { titleKey: 'nav.tasks' },
      },
      {
        path: 'tasks/create',
        name: 'tasks.create',
        component: () => import('@/views/TaskFormView.vue'),
        meta: { titleKey: 'tasks.createTitle' },
      },
      {
        path: 'tasks/:id(\\d+)/edit',
        name: 'tasks.edit',
        component: () => import('@/views/TaskFormView.vue'),
        meta: { titleKey: 'tasks.editTitle' },
      },
      {
        path: 'departments',
        name: 'departments',
        component: () => import('@/views/DepartmentsView.vue'),
        meta: { titleKey: 'nav.departments' },
      },
      {
        path: 'users',
        name: 'users',
        component: () => import('@/views/UsersView.vue'),
        meta: { titleKey: 'users.title', roles: ['admin', 'manager'] },
      },
      {
        path: 'activity',
        name: 'activity',
        component: () => import('@/views/ActivityLogView.vue'),
        meta: { titleKey: 'nav.activity', roles: ['admin', 'manager'] },
      },
      {
        path: 'settings',
        name: 'settings',
        component: () => import('@/views/SettingsView.vue'),
        meta: { titleKey: 'nav.settings' },
      },
      {
        path: 'profile',
        name: 'profile',
        component: () => import('@/views/ProfileView.vue'),
        meta: { titleKey: 'nav.profile' },
      },
    ],
  },
  {
    path: '/403',
    name: 'forbidden',
    component: () => import('@/views/ForbiddenView.vue'),
    meta: { titleKey: 'errors.forbidden' },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue'),
    meta: { titleKey: 'errors.notFound' },
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior: (_to, _from, saved) => saved ?? { top: 0 },
})

router.beforeEach(async (to) => {
  // Импорт внутри guard: стор создаётся после установки Pinia в main.ts.
  const { useAuthStore } = await import('@/stores/auth')
  const auth = useAuthStore()

  if (!auth.initialized) {
    await auth.fetchUser()
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  if (to.meta.roles?.length && !auth.can(...to.meta.roles)) {
    return { name: 'forbidden' }
  }

  return true
})

export default router
