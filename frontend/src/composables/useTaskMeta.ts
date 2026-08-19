import { useI18n } from 'vue-i18n'
import type { TaskPriority, TaskStatus } from '@/types'

type Severity = 'secondary' | 'success' | 'info' | 'warn' | 'danger' | 'contrast'

const STATUS_SEVERITIES: Record<TaskStatus, Severity> = {
  todo: 'secondary',
  in_progress: 'info',
  review: 'warn',
  done: 'success',
}

const STATUS_COLOR_VARS: Record<TaskStatus, [string, string]> = {
  todo: ['--p-surface-400', '#94a3b8'],
  in_progress: ['--p-blue-500', '#3b82f6'],
  review: ['--p-amber-500', '#f59e0b'],
  done: ['--p-green-500', '#22c55e'],
}

const PRIORITY_SEVERITIES: Record<TaskPriority, Severity> = {
  low: 'secondary',
  medium: 'warn',
  high: 'danger',
}

const PRIORITY_COLOR_VARS: Record<TaskPriority, [string, string]> = {
  low: ['--p-surface-400', '#94a3b8'],
  medium: ['--p-amber-500', '#f59e0b'],
  high: ['--p-red-500', '#ef4444'],
}

/** Значения токенов PrimeVue приходят в виде light-dark(...) — вычисляем итоговый цвет. */
function resolveToken(variable: string, fallback: string): string {
  const probe = document.createElement('span')
  probe.style.cssText = `position:absolute;visibility:hidden;color:var(${variable},${fallback})`
  document.body.appendChild(probe)
  const color = getComputedStyle(probe).color
  probe.remove()
  return color || fallback
}

/** Единый источник подписей, цветов и severity для статусов и приоритетов задач. */
export function useTaskMeta() {
  const { t } = useI18n()

  return {
    statusLabel: (status: TaskStatus) => t(`tasks.statuses.${status}`),
    statusSeverity: (status: TaskStatus) => STATUS_SEVERITIES[status],
    statusColor: (status: TaskStatus) => resolveToken(...STATUS_COLOR_VARS[status]),
    priorityLabel: (priority: TaskPriority) => t(`tasks.priorities.${priority}`),
    prioritySeverity: (priority: TaskPriority) => PRIORITY_SEVERITIES[priority],
    priorityColor: (priority: TaskPriority) => resolveToken(...PRIORITY_COLOR_VARS[priority]),
  }
}

/** Дата в формате dd.mm.yyyy без внешних зависимостей. */
export function formatDate(value?: string | null): string {
  if (!value) return '—'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? '—' : date.toLocaleDateString('ru-RU')
}

export function formatDateTime(value?: string | null): string {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '—'
  return `${date.toLocaleDateString('ru-RU')} ${date.toLocaleTimeString('ru-RU', {
    hour: '2-digit',
    minute: '2-digit',
  })}`
}

/** DatePicker работает с Date, API — со строками YYYY-MM-DD. */
export function toDate(value?: string | null): Date | null {
  if (!value) return null
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? null : date
}

export function toDateString(value: Date | string | null | undefined): string | null {
  if (!value) return null
  const date = value instanceof Date ? value : new Date(value)
  if (Number.isNaN(date.getTime())) return null
  const month = `${date.getMonth() + 1}`.padStart(2, '0')
  const day = `${date.getDate()}`.padStart(2, '0')
  return `${date.getFullYear()}-${month}-${day}`
}
