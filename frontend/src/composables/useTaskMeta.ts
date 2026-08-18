import { useI18n } from 'vue-i18n'
import type { TaskPriority, TaskStatus } from '@/types'

type TagType = 'primary' | 'success' | 'info' | 'warning' | 'danger'

const STATUS_TYPES: Record<TaskStatus, TagType> = {
  todo: 'info',
  in_progress: 'primary',
  review: 'warning',
  done: 'success',
}

const STATUS_COLORS: Record<TaskStatus, string> = {
  todo: 'var(--el-color-info)',
  in_progress: 'var(--el-color-primary)',
  review: 'var(--el-color-warning)',
  done: 'var(--el-color-success)',
}

const PRIORITY_TYPES: Record<TaskPriority, TagType> = {
  low: 'info',
  medium: 'warning',
  high: 'danger',
}

const PRIORITY_COLORS: Record<TaskPriority, string> = {
  low: 'var(--el-color-info)',
  medium: 'var(--el-color-warning)',
  high: 'var(--el-color-danger)',
}

/** Единый источник подписей и цветов для статусов и приоритетов задач. */
export function useTaskMeta() {
  const { t } = useI18n()

  return {
    statusLabel: (status: TaskStatus) => t(`tasks.statuses.${status}`),
    statusType: (status: TaskStatus) => STATUS_TYPES[status],
    statusColor: (status: TaskStatus) => STATUS_COLORS[status],
    priorityLabel: (priority: TaskPriority) => t(`tasks.priorities.${priority}`),
    priorityType: (priority: TaskPriority) => PRIORITY_TYPES[priority],
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
