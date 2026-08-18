<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage } from 'element-plus'
import BarChart from '@/components/BarChart.vue'
import MeterList from '@/components/MeterList.vue'
import StatCard from '@/components/StatCard.vue'
import EmptyState from '@/components/EmptyState.vue'
import { dashboardApi } from '@/api'
import { apiMessage } from '@/api/client'
import { formatDate, formatDateTime, useTaskMeta } from '@/composables/useTaskMeta'
import type { DashboardStats, TaskPriority, TaskStatus } from '@/types'

const { t } = useI18n()
const { statusLabel, statusColor, priorityLabel, priorityColor, priorityType } = useTaskMeta()

const stats = ref<DashboardStats | null>(null)
const loading = ref(true)

const statusMeters = computed(() =>
  Object.entries(stats.value?.tasks_by_status ?? {}).map(([status, value]) => ({
    label: statusLabel(status as TaskStatus),
    value,
    color: statusColor(status as TaskStatus),
  })),
)

const priorityMeters = computed(() =>
  Object.entries(stats.value?.tasks_by_priority ?? {}).map(([priority, value]) => ({
    label: priorityLabel(priority as TaskPriority),
    value,
    color: priorityColor(priority as TaskPriority),
  })),
)

const departmentChart = computed(() =>
  (stats.value?.employees_by_department ?? []).map((item) => ({ label: item.name, values: [item.total] })),
)

const weeklyChart = computed(() =>
  (stats.value?.tasks_per_week ?? []).map((item) => ({ label: item.label, values: [item.created, item.done] })),
)

onMounted(async () => {
  try {
    stats.value = await dashboardApi.stats()
  } catch (error) {
    ElMessage.error(apiMessage(error))
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
      <el-button type="primary" :icon="'Plus'" @click="$router.push({ name: 'tasks.create' })">
        {{ t('tasks.create') }}
      </el-button>
    </div>

    <div class="wf-grid stats">
      <StatCard
        :label="t('dashboard.employees')"
        :value="stats?.totals.employees ?? 0"
        icon="UserFilled"
        tone="primary"
        :loading="loading"
      />
      <StatCard
        :label="t('dashboard.departments')"
        :value="stats?.totals.departments ?? 0"
        icon="OfficeBuilding"
        tone="success"
        :loading="loading"
      />
      <StatCard
        :label="t('dashboard.tasks')"
        :value="stats?.totals.tasks ?? 0"
        icon="Files"
        tone="warning"
        :loading="loading"
      />
      <StatCard
        :label="t('dashboard.overdue')"
        :value="stats?.totals.overdue ?? 0"
        icon="WarningFilled"
        tone="danger"
        :loading="loading"
      />
    </div>

    <div class="wf-grid charts">
      <section class="wf-card panel panel--wide">
        <h3 class="panel__title">{{ t('dashboard.weekly') }}</h3>
        <el-skeleton v-if="loading" :rows="5" animated />
        <BarChart
          v-else
          :data="weeklyChart"
          :legend="[t('dashboard.created'), t('dashboard.done')]"
          :height="230"
        />
      </section>

      <section class="wf-card panel">
        <h3 class="panel__title">{{ t('dashboard.tasksByStatus') }}</h3>
        <el-skeleton v-if="loading" :rows="4" animated />
        <MeterList v-else :items="statusMeters" />

        <h3 class="panel__title panel__title--spaced">{{ t('dashboard.tasksByPriority') }}</h3>
        <el-skeleton v-if="loading" :rows="3" animated />
        <MeterList v-else :items="priorityMeters" />
      </section>

      <section class="wf-card panel">
        <h3 class="panel__title">{{ t('dashboard.byDepartment') }}</h3>
        <el-skeleton v-if="loading" :rows="5" animated />
        <BarChart v-else :data="departmentChart" :height="220" />
      </section>

      <section class="wf-card panel">
        <h3 class="panel__title">{{ t('dashboard.recentTasks') }}</h3>
        <el-skeleton v-if="loading" :rows="5" animated />
        <ul v-else-if="stats?.recent_tasks.length" class="feed">
          <li v-for="task in stats.recent_tasks" :key="task.id" class="feed__item">
            <div class="feed__main">
              <router-link :to="{ name: 'tasks' }" class="feed__title">{{ task.title }}</router-link>
              <span class="wf-muted feed__meta">
                {{ task.employee?.full_name ?? t('tasks.unassigned') }} · {{ formatDate(task.deadline) }}
              </span>
            </div>
            <el-tag size="small" :type="priorityType(task.priority)" effect="light" round>
              {{ priorityLabel(task.priority) }}
            </el-tag>
          </li>
        </ul>
        <EmptyState v-else :text="t('common.noData')" />
      </section>

      <section class="wf-card panel panel--wide">
        <h3 class="panel__title">{{ t('dashboard.recentActivity') }}</h3>
        <el-skeleton v-if="loading" :rows="5" animated />
        <el-timeline v-else-if="stats?.recent_activity.length" class="timeline">
          <el-timeline-item
            v-for="log in stats.recent_activity"
            :key="log.id"
            :timestamp="formatDateTime(log.created_at)"
            placement="top"
          >
            <span class="feed__title">{{ log.description ?? log.action }}</span>
            <span class="wf-muted"> — {{ log.user?.name ?? t('activity.system') }}</span>
          </el-timeline-item>
        </el-timeline>
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

.timeline {
  padding-left: 4px;
  margin-bottom: -20px;
}

@media (max-width: 1100px) {
  .panel--wide {
    grid-column: span 1;
  }
}
</style>
