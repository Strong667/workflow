<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Chart } from '@/ui/lazy-components'
import { useNotify } from '@/ui/feedback'
import { useChartTokens } from '@/ui/tokens'
import MeterList from '@/components/MeterList.vue'
import StatCard from '@/components/StatCard.vue'
import EmptyState from '@/components/EmptyState.vue'
import { dashboardApi } from '@/api'
import { apiMessage } from '@/api/client'
import { formatDate, formatDateTime, useTaskMeta } from '@/composables/useTaskMeta'
import type { DashboardStats, TaskPriority, TaskStatus } from '@/types'

const { t } = useI18n()
const notify = useNotify()
const tokens = useChartTokens()
const { statusLabel, statusColor, priorityLabel, priorityColor } = useTaskMeta()

const stats = ref<DashboardStats | null>(null)
const loading = ref(true)

const updatedAt = computed(() => formatDateTime(new Date().toISOString()))

/** Порядок фиксируем по конвейеру, а не по тому, как ответил сервер. */
const STATUS_ORDER: TaskStatus[] = ['todo', 'in_progress', 'review', 'done']
const PRIORITY_ORDER: TaskPriority[] = ['high', 'medium', 'low']

const statusRows = computed(() =>
  STATUS_ORDER.map((status) => ({
    label: statusLabel(status),
    value: stats.value?.tasks_by_status?.[status] ?? 0,
    color: statusColor(status),
  })),
)

const priorityMeters = computed(() =>
  PRIORITY_ORDER.map((priority) => ({
    label: priorityLabel(priority),
    value: stats.value?.tasks_by_priority?.[priority] ?? 0,
    color: priorityColor(priority),
  })),
)

const departmentMeters = computed(() =>
  (stats.value?.employees_by_department ?? []).map((item) => ({
    label: item.name,
    value: item.total,
    color: 'var(--wf-ink)',
  })),
)

const weeklyData = computed(() => ({
  labels: (stats.value?.tasks_per_week ?? []).map((item) => item.label),
  datasets: [
    {
      label: t('dashboard.created'),
      data: (stats.value?.tasks_per_week ?? []).map((item) => item.created),
      backgroundColor: tokens.value.primary,
      borderRadius: 2,
      maxBarThickness: 14,
    },
    {
      label: t('dashboard.done'),
      data: (stats.value?.tasks_per_week ?? []).map((item) => item.done),
      backgroundColor: tokens.value.success,
      borderRadius: 2,
      maxBarThickness: 14,
    },
  ],
}))

const statusData = computed(() => ({
  labels: STATUS_ORDER.map((status) => statusLabel(status)),
  datasets: [
    {
      data: STATUS_ORDER.map((status) => stats.value?.tasks_by_status?.[status] ?? 0),
      backgroundColor: STATUS_ORDER.map((status) => statusColor(status)),
      borderWidth: 0,
    },
  ],
}))

const barOptions = computed(() => ({
  maintainAspectRatio: false,
  // Анимация Chart.js крутится на requestAnimationFrame: в фоновой вкладке
  // он не вызывается, и график остаётся пустым до переключения на неё.
  animation: false as const,
  plugins: {
    legend: {
      align: 'end' as const,
      labels: {
        color: tokens.value.muted,
        usePointStyle: true,
        pointStyle: 'rect' as const,
        boxWidth: 8,
        boxHeight: 8,
        font: { size: 11 },
      },
    },
  },
  scales: {
    x: {
      ticks: { color: tokens.value.muted, font: { family: 'JetBrains Mono', size: 10 } },
      grid: { display: false },
      border: { color: tokens.value.grid },
    },
    y: {
      beginAtZero: true,
      ticks: { color: tokens.value.muted, precision: 0, font: { family: 'JetBrains Mono', size: 10 } },
      grid: { color: tokens.value.grid },
      border: { display: false },
    },
  },
}))

const doughnutOptions = computed(() => ({
  maintainAspectRatio: false,
  animation: false as const,
  cutout: '70%',
  plugins: { legend: { display: false } },
}))

onMounted(async () => {
  try {
    stats.value = await dashboardApi.stats()
  } catch (error) {
    notify.error(apiMessage(error))
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="wf-page">
    <div class="wf-page__header">
      <div>
        <h1 class="wf-page__title">{{ t('dashboard.title') }}</h1>
        <p class="wf-page__subtitle">{{ t('dashboard.updatedAt', { time: updatedAt }) }}</p>
      </div>
      <Button icon="pi pi-plus" :label="t('tasks.create')" @click="$router.push({ name: 'tasks.create' })" />
    </div>

    <div class="wf-grid stats">
      <StatCard :label="t('dashboard.employees')" :value="stats?.totals.employees ?? 0" :loading="loading" />
      <StatCard :label="t('dashboard.departments')" :value="stats?.totals.departments ?? 0" :loading="loading" />
      <StatCard :label="t('dashboard.tasks')" :value="stats?.totals.tasks ?? 0" :loading="loading" />
      <StatCard
        :label="t('dashboard.overdue')"
        :value="stats?.totals.overdue ?? 0"
        :hint="stats?.totals.overdue ? t('dashboard.needsAttention') : ''"
        tone="danger"
        :loading="loading"
      />
    </div>

    <div class="wf-grid row-3">
      <section class="wf-card panel">
        <h3 class="wf-panel-title">{{ t('dashboard.weekly') }}</h3>
        <Skeleton v-if="loading" height="176px" />
        <Chart v-else type="bar" :data="weeklyData" :options="barOptions" class="chart chart--bar" />
      </section>

      <section class="wf-card panel">
        <h3 class="wf-panel-title">{{ t('dashboard.tasksByStatus') }}</h3>
        <Skeleton v-if="loading" height="176px" />
        <div v-else class="donut">
          <Chart type="doughnut" :data="statusData" :options="doughnutOptions" class="donut__chart" />
          <ul class="legend">
            <li v-for="row in statusRows" :key="row.label" class="legend__row">
              <span class="legend__dot" :style="{ background: row.color }" />
              <span class="legend__label">{{ row.label }}</span>
              <span class="wf-mono legend__value">{{ row.value }}</span>
            </li>
          </ul>
        </div>
      </section>

      <section class="wf-card panel">
        <h3 class="wf-panel-title">{{ t('dashboard.byDepartment') }}</h3>
        <Skeleton v-if="loading" height="176px" />
        <MeterList v-else :items="departmentMeters" />
      </section>
    </div>

    <div class="wf-grid row-2">
      <section class="wf-card panel">
        <h3 class="wf-panel-title">{{ t('dashboard.tasksByPriority') }}</h3>
        <Skeleton v-if="loading" height="60px" />
        <MeterList v-else :items="priorityMeters" color-labels />

        <h3 class="wf-panel-title panel__title--spaced">{{ t('dashboard.recentTasks') }}</h3>
        <Skeleton v-if="loading" height="120px" />
        <ul v-else-if="stats?.recent_tasks.length" class="feed">
          <li v-for="task in stats.recent_tasks" :key="task.id" class="feed__item">
            <span class="feed__bar" :style="{ background: priorityColor(task.priority) }" />
            <span class="feed__main">
              <span class="feed__title">{{ task.title }}</span>
              <span class="feed__meta wf-muted">
                {{ task.employee?.full_name ?? t('tasks.unassigned') }} ·
                <span class="wf-mono">{{ formatDate(task.deadline) }}</span>
              </span>
            </span>
            <span class="feed__priority" :style="{ color: priorityColor(task.priority) }">
              {{ priorityLabel(task.priority) }}
            </span>
          </li>
        </ul>
        <EmptyState v-else :text="t('common.noData')" />
      </section>

      <section class="wf-card panel">
        <div class="panel__head">
          <h3 class="wf-panel-title panel__title--flush">{{ t('dashboard.recentActivity') }}</h3>
          <router-link :to="{ name: 'activity' }" class="panel__link">{{ t('dashboard.allActivity') }} →</router-link>
        </div>
        <Skeleton v-if="loading" height="200px" />
        <ul v-else-if="stats?.recent_activity.length" class="timeline">
          <li v-for="log in stats.recent_activity" :key="log.id" class="timeline__row">
            <span class="wf-mono timeline__time">{{ formatDateTime(log.created_at) }}</span>
            <span class="timeline__dot" />
            <span class="timeline__text">
              <b>{{ log.description ?? log.action }}</b>
              <span class="wf-muted"> — {{ log.user?.name ?? t('activity.system') }}</span>
            </span>
          </li>
        </ul>
        <EmptyState v-else :text="t('activity.empty')" />
      </section>
    </div>
  </div>
</template>

<style scoped lang="scss">
.stats {
  grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
}

.row-3 {
  grid-template-columns: 1.35fr 1fr 1fr;
  align-items: start;
}

.row-2 {
  grid-template-columns: 1fr 1.45fr;
  align-items: start;
}

.panel {
  padding: 14px 15px 15px;
}

.panel__head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 12px;
}

.panel__title--flush {
  margin-bottom: 0;
}

.panel__title--spaced {
  margin-top: 18px;
}

.panel__link {
  font-size: 11.5px;
  color: var(--wf-ink-3);

  &:hover {
    color: var(--wf-ink);
  }
}

.chart--bar {
  height: 176px;
}

.donut {
  display: grid;
  grid-template-columns: 116px 1fr;
  gap: 14px;
  align-items: center;
}

.donut__chart {
  height: 116px;
}

.legend {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.legend__row {
  display: grid;
  grid-template-columns: 8px 1fr auto;
  align-items: center;
  gap: 9px;
  font-size: 12px;
}

.legend__dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.legend__value {
  font-size: 12px;
}

.feed {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
}

.feed__item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 0;
  border-bottom: 1px solid var(--wf-line-2);

  &:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }
}

.feed__bar {
  width: 2px;
  align-self: stretch;
  border-radius: 2px;
  flex: 0 0 auto;
}

.feed__main {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
  flex: 1;
}

.feed__title {
  font-size: 12.5px;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.feed__meta {
  font-size: 11px;
}

.feed__priority {
  font-size: 11.5px;
  font-weight: 600;
  white-space: nowrap;
}

.timeline {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
}

.timeline__row {
  display: grid;
  grid-template-columns: 74px 8px 1fr;
  align-items: start;
  gap: 12px;
  padding: 8px 0;
  border-bottom: 1px solid var(--wf-line-2);

  &:last-child {
    border-bottom: none;
  }
}

.timeline__time {
  font-size: 10px;
  color: var(--wf-ink-3);
  line-height: 1.35;
}

.timeline__dot {
  width: 6px;
  height: 6px;
  margin-top: 5px;
  border-radius: 50%;
  background: var(--wf-ink-3);
}

.timeline__text {
  font-size: 12px;

  b {
    font-weight: 600;
  }
}

@media (max-width: 1200px) {
  .row-3,
  .row-2 {
    grid-template-columns: 1fr;
  }
}
</style>
