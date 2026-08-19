<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import MeterList from '@/components/MeterList.vue'
import StatCard from '@/components/StatCard.vue'
import EmptyState from '@/components/EmptyState.vue'
import { dashboardApi } from '@/api'
import { apiMessage } from '@/api/client'
import { useChartTokens } from '@/ui/tokens'
import { formatDate, formatDateTime, useTaskMeta } from '@/composables/useTaskMeta'
import type { DashboardStats, TaskPriority, TaskStatus } from '@/types'
import { Chart, Timeline } from '@/ui/lazy-components'
import { useNotify } from '@/ui/feedback'

const { t } = useI18n()
const notify = useNotify()
const tokens = useChartTokens()
const { statusLabel, statusColor, priorityLabel, priorityColor, prioritySeverity } = useTaskMeta()

const stats = ref<DashboardStats | null>(null)
const loading = ref(true)

const priorityMeters = computed(() =>
  Object.entries(stats.value?.tasks_by_priority ?? {}).map(([priority, value]) => ({
    label: priorityLabel(priority as TaskPriority),
    value,
    color: priorityColor(priority as TaskPriority),
  })),
)

const weeklyData = computed(() => ({
  labels: (stats.value?.tasks_per_week ?? []).map((item) => item.label),
  datasets: [
    {
      label: t('dashboard.created'),
      data: (stats.value?.tasks_per_week ?? []).map((item) => item.created),
      backgroundColor: tokens.value.primary,
      borderRadius: 6,
      maxBarThickness: 26,
    },
    {
      label: t('dashboard.done'),
      data: (stats.value?.tasks_per_week ?? []).map((item) => item.done),
      backgroundColor: tokens.value.success,
      borderRadius: 6,
      maxBarThickness: 26,
    },
  ],
}))

const statusData = computed(() => {
  const entries = Object.entries(stats.value?.tasks_by_status ?? {}) as Array<[TaskStatus, number]>
  return {
    labels: entries.map(([status]) => statusLabel(status)),
    datasets: [
      {
        data: entries.map(([, value]) => value),
        backgroundColor: entries.map(([status]) => statusColor(status)),
        borderWidth: 0,
      },
    ],
  }
})

const departmentData = computed(() => ({
  labels: (stats.value?.employees_by_department ?? []).map((item) => item.name),
  datasets: [
    {
      label: t('dashboard.employees'),
      data: (stats.value?.employees_by_department ?? []).map((item) => item.total),
      backgroundColor: tokens.value.primary,
      borderRadius: 6,
      maxBarThickness: 22,
    },
  ],
}))

const barOptions = computed(() => ({
  maintainAspectRatio: false,
  // Анимация Chart.js крутится на requestAnimationFrame: в фоновой вкладке
  // он не вызывается, и график остаётся пустым до переключения на неё.
  animation: false as const,
  plugins: {
    legend: { labels: { color: tokens.value.muted, usePointStyle: true, boxWidth: 8 } },
  },
  scales: {
    x: { ticks: { color: tokens.value.muted }, grid: { display: false } },
    y: {
      beginAtZero: true,
      ticks: { color: tokens.value.muted, precision: 0 },
      grid: { color: tokens.value.grid },
    },
  },
}))

const horizontalBarOptions = computed(() => ({
  ...barOptions.value,
  indexAxis: 'y' as const,
  plugins: { legend: { display: false } },
  scales: {
    x: {
      beginAtZero: true,
      ticks: { color: tokens.value.muted, precision: 0 },
      grid: { color: tokens.value.grid },
    },
    y: { ticks: { color: tokens.value.muted }, grid: { display: false } },
  },
}))

const doughnutOptions = computed(() => ({
  maintainAspectRatio: false,
  animation: false as const,
  cutout: '62%',
  plugins: {
    legend: {
      position: 'bottom' as const,
      labels: { color: tokens.value.muted, usePointStyle: true, boxWidth: 8, padding: 14 },
    },
  },
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
        <p class="wf-page__subtitle">{{ t('dashboard.subtitle') }}</p>
      </div>
      <Button icon="pi pi-plus" :label="t('tasks.create')" @click="$router.push({ name: 'tasks.create' })" />
    </div>

    <div class="wf-grid stats">
      <StatCard
        :label="t('dashboard.employees')"
        :value="stats?.totals.employees ?? 0"
        icon="pi pi-users"
        tone="primary"
        :loading="loading"
      />
      <StatCard
        :label="t('dashboard.departments')"
        :value="stats?.totals.departments ?? 0"
        icon="pi pi-building"
        tone="success"
        :loading="loading"
      />
      <StatCard
        :label="t('dashboard.tasks')"
        :value="stats?.totals.tasks ?? 0"
        icon="pi pi-list-check"
        tone="warn"
        :loading="loading"
      />
      <StatCard
        :label="t('dashboard.overdue')"
        :value="stats?.totals.overdue ?? 0"
        icon="pi pi-exclamation-triangle"
        tone="danger"
        :loading="loading"
      />
    </div>

    <div class="wf-grid charts">
      <section class="wf-card panel panel--wide">
        <h3 class="panel__title">{{ t('dashboard.weekly') }}</h3>
        <Skeleton v-if="loading" height="230px" />
        <Chart v-else type="bar" :data="weeklyData" :options="barOptions" class="chart" />
      </section>

      <section class="wf-card panel">
        <h3 class="panel__title">{{ t('dashboard.tasksByStatus') }}</h3>
        <Skeleton v-if="loading" height="230px" />
        <Chart v-else type="doughnut" :data="statusData" :options="doughnutOptions" class="chart" />
      </section>

      <section class="wf-card panel">
        <h3 class="panel__title">{{ t('dashboard.byDepartment') }}</h3>
        <Skeleton v-if="loading" height="230px" />
        <Chart v-else type="bar" :data="departmentData" :options="horizontalBarOptions" class="chart" />
      </section>

      <section class="wf-card panel">
        <h3 class="panel__title">{{ t('dashboard.tasksByPriority') }}</h3>
        <Skeleton v-if="loading" height="80px" />
        <MeterList v-else :items="priorityMeters" />

        <h3 class="panel__title panel__title--spaced">{{ t('dashboard.recentTasks') }}</h3>
        <Skeleton v-if="loading" height="120px" />
        <ul v-else-if="stats?.recent_tasks.length" class="feed">
          <li v-for="task in stats.recent_tasks" :key="task.id" class="feed__item">
            <div class="feed__main">
              <router-link :to="{ name: 'tasks' }" class="feed__title">{{ task.title }}</router-link>
              <span class="wf-muted feed__meta">
                {{ task.employee?.full_name ?? t('tasks.unassigned') }} · {{ formatDate(task.deadline) }}
              </span>
            </div>
            <Tag :value="priorityLabel(task.priority)" :severity="prioritySeverity(task.priority)" rounded />
          </li>
        </ul>
        <EmptyState v-else :text="t('common.noData')" />
      </section>

      <section class="wf-card panel panel--wide">
        <h3 class="panel__title">{{ t('dashboard.recentActivity') }}</h3>
        <Skeleton v-if="loading" height="200px" />
        <Timeline v-else-if="stats?.recent_activity.length" :value="stats.recent_activity" class="timeline">
          <template #opposite="{ item }">
            <span class="wf-muted timeline__date">{{ formatDateTime(item.created_at) }}</span>
          </template>
          <template #content="{ item }">
            <span class="feed__title">{{ item.description ?? item.action }}</span>
            <span class="wf-muted"> — {{ item.user?.name ?? t('activity.system') }}</span>
          </template>
        </Timeline>
        <EmptyState v-else :text="t('activity.empty')" />
      </section>
    </div>
  </div>
</template>

<style scoped lang="scss">
.stats {
  grid-template-columns: repeat(auto-fit, minmax(212px, 1fr));
}

.charts {
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  align-items: start;
}

.panel {
  padding: 18px;
}

.panel--wide {
  grid-column: span 2;
}

.panel__title {
  margin: 0 0 16px;
  font-size: 14px;
  font-weight: 650;
}

.panel__title--spaced {
  margin-top: 24px;
}

.chart {
  height: 230px;
}

.feed {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.feed__item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.feed__main {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.feed__title {
  font-size: 13px;
  font-weight: 550;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.feed__meta {
  font-size: 11.5px;
}

.timeline__date {
  font-size: 11.5px;
  white-space: nowrap;
}

:deep(.p-timeline-event-opposite) {
  flex: 0 0 auto;
}

@media (max-width: 1100px) {
  .panel--wide {
    grid-column: span 1;
  }
}
</style>
