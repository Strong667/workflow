<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useDebounceFn } from '@vueuse/core'
import { useQuasar } from 'quasar'
import KanbanColumn from '@/components/KanbanColumn.vue'
import { apiMessage } from '@/api/client'
import { useTaskMeta } from '@/composables/useTaskMeta'
import { useDepartmentsStore } from '@/stores/departments'
import { useTasksStore, STATUSES } from '@/stores/tasks'
import type { Task, TaskPriority, TaskStatus } from '@/types'

const { t } = useI18n()
const router = useRouter()
const $q = useQuasar()
const tasks = useTasksStore()
const departments = useDepartmentsStore()
const { statusLabel, statusColor } = useTaskMeta()

const search = ref(tasks.filters.search ?? '')
const dragged = ref<{ task: Task; from: TaskStatus } | null>(null)

const priorityOptions = computed(() =>
  (['low', 'medium', 'high'] as TaskPriority[]).map((value) => ({ value, label: t(`tasks.priorities.${value}`) })),
)

const debouncedSearch = useDebounceFn((value: string) => {
  tasks.filters.search = value
  void tasks.fetchBoard()
}, 400)

watch(search, (value) => debouncedSearch(value ?? ''))

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
      $q.notify({ type: 'positive', message: t('tasks.moved') })
    }
  } catch (error) {
    $q.notify({ type: 'negative', message: apiMessage(error) })
  }
}

function remove(task: Task): void {
  $q.dialog({
    title: t('common.confirm'),
    message: t('tasks.deleteConfirm', { title: task.title }),
    cancel: { label: t('common.cancel'), flat: true, noCaps: true },
    ok: { label: t('common.delete'), color: 'negative', unelevated: true, noCaps: true },
    persistent: true,
  }).onOk(async () => {
    try {
      await tasks.remove(task.id)
      $q.notify({ type: 'positive', message: t('common.deleted') })
    } catch (error) {
      $q.notify({ type: 'negative', message: apiMessage(error) })
    }
  })
}
</script>

<template>
  <div class="wf-page">
    <div class="wf-page__header">
      <div>
        <h1 class="wf-page__title">{{ t('tasks.title') }}</h1>
        <p class="wf-page__subtitle">{{ t('tasks.dragHint') }} · {{ t('common.total') }}: {{ tasks.totalCount }}</p>
      </div>
      <q-btn
        color="primary"
        unelevated
        no-caps
        icon="add"
        :label="t('tasks.create')"
        @click="router.push({ name: 'tasks.create' })"
      />
    </div>

    <div class="wf-card filters">
      <q-input
        v-model="search"
        outlined
        dense
        clearable
        :placeholder="t('tasks.searchPlaceholder')"
        class="filters__search"
      >
        <template #prepend><q-icon name="search" /></template>
      </q-input>

      <q-select
        v-model="tasks.filters.priority"
        :options="priorityOptions"
        option-label="label"
        option-value="value"
        emit-value
        map-options
        clearable
        outlined
        dense
        :label="t('tasks.priority')"
        class="filters__select"
        @update:model-value="applyFilter"
      />

      <q-select
        v-model="tasks.filters.department_id"
        :options="departments.options"
        option-label="name"
        option-value="id"
        emit-value
        map-options
        clearable
        outlined
        dense
        :label="t('employees.department')"
        class="filters__select"
        @update:model-value="applyFilter"
      />

      <q-btn flat no-caps :label="t('common.reset')" @click="resetFilters" />
    </div>

    <div class="board" :class="{ 'board--loading': tasks.loading }">
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
  width: 200px;
}

.board {
  display: flex;
  gap: 14px;
  align-items: stretch;
  overflow-x: auto;
  padding-bottom: 8px;
  min-height: 400px;
  transition: opacity 0.15s ease;

  &--loading {
    opacity: 0.55;
    pointer-events: none;
  }
}
</style>
