import { useI18n } from 'vue-i18n'
import type { TaskPriority, TaskStatus } from '@/types'

/** Цвета Quasar-палитры: используются и в классах, и в Chart.js. */
const STATUS_COLORS: Record<TaskStatus, string> = {
  todo: '#94a3b8',
  in_progress: '#3b82f6',
  review: '#f59e0b',
  done: '#22c55e',
}

const PRIORITY_COLORS: Record<TaskPriority, string> = {
  low: '#94a3b8',
  medium: '#f59e0b',
  high: '#ef4444',
}

/** Имена цветов Quasar для color="…" у q-chip и q-badge. */
const PRIORITY_TONES: Record<TaskPriority, string> = {
  low: 'grey-6',
  medium: 'warning',
  high: 'negative',
}

const STATUS_TONES: Record<TaskStatus, string> = {
  todo: 'grey-6',
  in_progress: 'info',
  review: 'warning',
  done: 'positive',
}

export function useTaskMeta() {
  const { t } = useI18n()

  return {
    statusLabel: (status: TaskStatus) => t(`tasks.statuses.${status}`),
    statusTone: (status: TaskStatus) => STATUS_TONES[status],
    statusColor: (status: TaskStatus) => STATUS_COLORS[status],
    priorityLabel: (priority: TaskPriority) => t(`tasks.priorities.${priority}`),
    priorityTone: (priority: TaskPriority) => PRIORITY_TONES[priority],
    priorityColor: (priority: TaskPriority) => PRIORITY_COLORS[priority],
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

/** q-date работает со строкой YYYY/MM/DD, API — с YYYY-MM-DD. */
export function toQuasarDate(value?: string | null): string | null {
  return value ? value.replaceAll('-', '/') : null
}

export function fromQuasarDate(value?: string | null): string | null {
  return value ? value.replaceAll('/', '-') : null
}
