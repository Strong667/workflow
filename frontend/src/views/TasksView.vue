<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useDebounceFn } from '@vueuse/core'
import { ElMessage, ElMessageBox } from 'element-plus'
import KanbanColumn from '@/components/KanbanColumn.vue'
import { apiMessage } from '@/api/client'
import { useTaskMeta } from '@/composables/useTaskMeta'
import { useDepartmentsStore } from '@/stores/departments'
import { useTasksStore, STATUSES } from '@/stores/tasks'
import type { Task, TaskPriority, TaskStatus } from '@/types'

const { t } = useI18n()
const router = useRouter()
const tasks = useTasksStore()
const departments = useDepartmentsStore()
const { statusLabel, statusColor } = useTaskMeta()

const search = ref(tasks.filters.search ?? '')
const dragged = ref<{ task: Task; from: TaskStatus } | null>(null)

const debouncedSearch = useDebounceFn((value: string) => {
  tasks.filters.search = value
  void tasks.fetchBoard()
}, 400)

watch(search, (value) => debouncedSearch(value))

onMounted(async () => {
  await Promise.all([tasks.fetchBoard(), departments.fetchOptions()])
})

function applyFilter(): void {
  void tasks.fetchBoard()
}

function resetFilters(): void {
  search.value = ''
  tasks.resetFilters()
  void tasks.fetchBoard()
}

function onDragStart(event: DragEvent, task: Task): void {
  dragged.value = { task, from: task.status }
  event.dataTransfer?.setData('text/plain', String(task.id))
  if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move'
}

async function onDrop(payload: { status: TaskStatus; index: number }): Promise<void> {
  const current = dragged.value
  dragged.value = null
  if (!current) return
  if (current.from === payload.status && current.task.position === payload.index) return

  try {
    await tasks.move(current.task.id, current.from, payload.status, payload.index)
    if (current.from !== payload.status) {
      ElMessage.success(t('tasks.moved'))
    }
  } catch (error) {
    ElMessage.error(apiMessage(error))
  }
}

async function remove(task: Task): Promise<void> {
  try {
    await ElMessageBox.confirm(t('tasks.deleteConfirm', { title: task.title }), t('common.confirm'), {
      type: 'warning',
      confirmButtonText: t('common.delete'),
      cancelButtonText: t('common.cancel'),
    })
  } catch {
    return
  }

  try {
    await tasks.remove(task.id)
    ElMessage.success(t('common.deleted'))
  } catch (error) {
    ElMessage.error(apiMessage(error))
  }
}

const priorities: TaskPriority[] = ['low', 'medium', 'high']
</script>

<template>
  <div class="wf-page">
    <div class="wf-page__header">
      <div>
        <h1 class="wf-page__title">{{ t('tasks.title') }}</h1>
        <p class="wf-page__subtitle">{{ t('tasks.dragHint') }} · {{ t('common.total') }}: {{ tasks.totalCount }}</p>
      </div>
      <el-button type="primary" icon="Plus" @click="router.push({ name: 'tasks.create' })">
        {{ t('tasks.create') }}
      </el-button>
    </div>

    <div class="wf-card filters">
      <el-input v-model="search" :placeholder="t('tasks.searchPlaceholder')" clearable class="filters__search">
        <template #prefix><el-icon><Search /></el-icon></template>
      </el-input>

      <el-select
        v-model="tasks.filters.priority"
        :placeholder="t('tasks.priority')"
        clearable
        class="filters__select"
        @change="applyFilter"
      >
        <el-option v-for="item in priorities" :key="item" :label="t(`tasks.priorities.${item}`)" :value="item" />
      </el-select>

      <el-select
        v-model="tasks.filters.department_id"
        :placeholder="t('employees.department')"
        clearable
        class="filters__select"
        @change="applyFilter"
      >
        <el-option v-for="item in departments.options" :key="item.id" :label="item.name" :value="item.id" />
      </el-select>

      <el-button text @click="resetFilters">{{ t('common.reset') }}</el-button>
    </div>

    <div v-loading="tasks.loading" class="board">
      <KanbanColumn
        v-for="status in STATUSES"
        :key="status"
        :status="status"
        :title="statusLabel(status)"
        :color="statusColor(status)"
        :tasks="tasks.board[status]"
        :empty-text="t('tasks.empty')"
        @dragstart="onDragStart"
        @dragend="dragged = null"
        @drop="onDrop"
        @edit="(task) => router.push({ name: 'tasks.edit', params: { id: task.id } })"
        @remove="remove"
      />
    </div>
  </div>
</template>

<style scoped lang="scss">
.filters {
  padding: 14px;
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  align-items: center;
}

.filters__search {
  max-width: 300px;
  flex: 1 1 220px;
}

.filters__select {
  width: 180px;
}

.board {
  display: flex;
  gap: 14px;
  align-items: stretch;
  overflow-x: auto;
  padding-bottom: 8px;
  min-height: 400px;
}
</style>
