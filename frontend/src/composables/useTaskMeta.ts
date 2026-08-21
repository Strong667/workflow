import { useI18n } from 'vue-i18n'
import { resolveToken } from '@/ui/tokens'
import type { TaskPriority, TaskStatus } from '@/types'

type Severity = 'secondary' | 'success' | 'info' | 'warn' | 'danger' | 'contrast'

const STATUS_SEVERITIES: Record<TaskStatus, Severity> = {
  todo: 'secondary',
  in_progress: 'info',
  review: 'warn',
  done: 'success',
}

const STATUS_COLOR_VARS: Record<TaskStatus, [string, string]> = {
  todo: ['--wf-todo', '#9ba2aa'],
  in_progress: ['--wf-progress', '#2f6fed'],
  review: ['--wf-review', '#d98613'],
  done: ['--wf-done', '#1c9a5f'],
}

const PRIORITY_SEVERITIES: Record<TaskPriority, Severity> = {
  low: 'secondary',
  medium: 'warn',
  high: 'danger',
}

const PRIORITY_COLOR_VARS: Record<TaskPriority, [string, string]> = {
  low: ['--wf-todo', '#9ba2aa'],
  medium: ['--wf-review', '#d98613'],
  high: ['--wf-danger', '#d8412f'],
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
