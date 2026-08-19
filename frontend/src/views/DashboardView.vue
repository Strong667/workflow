<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useQuasar } from 'quasar'
import BaseChart from '@/components/BaseChart.vue'
import MeterList from '@/components/MeterList.vue'
import StatCard from '@/components/StatCard.vue'
import EmptyState from '@/components/EmptyState.vue'
import { dashboardApi } from '@/api'
import { apiMessage } from '@/api/client'
import { useChartTheme } from '@/composables/useChartTheme'
import { formatDate, formatDateTime, useTaskMeta } from '@/composables/useTaskMeta'
import type { DashboardStats, TaskPriority, TaskStatus } from '@/types'

const { t } = useI18n()
const $q = useQuasar()
const tokens = useChartTheme()
const { statusLabel, statusColor, priorityLabel, priorityColor, priorityTone } = useTaskMeta()

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
    $q.notify({ type: 'negative', message: apiMessage(error) })
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
      <q-btn
        color="primary"
        unelevated
        no-caps
        icon="add"
        :label="t('tasks.create')"
        @click="$router.push({ name: 'tasks.create' })"
      />
    </div>

    <div class="wf-grid stats">
      <StatCard
        :label="t('dashboard.employees')"
        :value="stats?.totals.employees ?? 0"
        icon="groups"
        tone="primary"
        :loading="loading"
      />
      <StatCard
        :label="t('dashboard.departments')"
        :value="stats?.totals.departments ?? 0"
        icon="apartment"
        tone="positive"
        :loading="loading"
      />
      <StatCard
        :label="t('dashboard.tasks')"
        :value="stats?.totals.tasks ?? 0"
        icon="task_alt"
        tone="warning"
        :loading="loading"
      />
      <StatCard
        :label="t('dashboard.overdue')"
        :value="stats?.totals.overdue ?? 0"
        icon="warning"
        tone="negative"
        :loading="loading"
      />
    </div>

    <div class="wf-grid charts">
      <section class="wf-card panel panel--wide">
        <h3 class="panel__title">{{ t('dashboard.weekly') }}</h3>
        <q-skeleton v-if="loading" height="230px" />
        <BaseChart v-else type="bar" :data="weeklyData" :options="barOptions" />
      </section>

      <section class="wf-card panel">
        <h3 class="panel__title">{{ t('dashboard.tasksByStatus') }}</h3>
        <q-skeleton v-if="loading" height="230px" />
        <BaseChart v-else type="doughnut" :data="statusData" :options="doughnutOptions" />
      </section>

      <section class="wf-card panel">
        <h3 class="panel__title">{{ t('dashboard.byDepartment') }}</h3>
        <q-skeleton v-if="loading" height="230px" />
        <BaseChart v-else type="bar" :data="departmentData" :options="horizontalBarOptions" />
      </section>

      <section class="wf-card panel">
        <h3 class="panel__title">{{ t('dashboard.tasksByPriority') }}</h3>
        <q-skeleton v-if="loading" height="80px" />
        <MeterList v-else :items="priorityMeters" />

        <h3 class="panel__title panel__title--spaced">{{ t('dashboard.recentTasks') }}</h3>
        <q-skeleton v-if="loading" height="120px" />
        <q-list v-else-if="stats?.recent_tasks.length" dense separator>
          <q-item v-for="task in stats.recent_tasks" :key="task.id" class="feed__item">
            <q-item-section>
              <q-item-label class="feed__title">{{ task.title }}</q-item-label>
              <q-item-label caption class="wf-muted">
                {{ task.employee?.full_name ?? t('tasks.unassigned') }} · {{ formatDate(task.deadline) }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-chip
                dense
                square
                :color="priorityTone(task.priority)"
                text-color="white"
                :label="priorityLabel(task.priority)"
              />
            </q-item-section>
          </q-item>
        </q-list>
        <EmptyState v-else :text="t('common.noData')" />
      </section>

      <section class="wf-card panel panel--wide">
        <h3 class="panel__title">{{ t('dashboard.recentActivity') }}</h3>
        <q-skeleton v-if="loading" height="200px" />
        <q-timeline v-else-if="stats?.recent_activity.length" color="primary" class="timeline">
          <q-timeline-entry
            v-for="log in stats.recent_activity"
            :key="log.id"
            :subtitle="formatDateTime(log.created_at)"
            icon="bolt"
          >
            <span class="feed__title">{{ log.description ?? log.action }}</span>
            <span class="wf-muted"> — {{ log.user?.name ?? t('activity.system') }}</span>
          </q-timeline-entry>
        </q-timeline>
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

.feed__item {
  padding-left: 0;
  padding-right: 0;
}

.feed__title {
  font-size: 13px;
  font-weight: 550;
}

.timeline {
  padding-left: 4px;
}

@media (max-width: 1100px) {
  .panel--wide {
    grid-column: span 1;
  }
}
</style>
