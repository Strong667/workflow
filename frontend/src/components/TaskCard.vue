<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { formatDate, useTaskMeta } from '@/composables/useTaskMeta'
import type { Task } from '@/types'

defineProps<{ task: Task; draggable?: boolean }>()
const emit = defineEmits<{
  (e: 'edit', task: Task): void
  (e: 'remove', task: Task): void
  (e: 'dragstart', event: DragEvent, task: Task): void
  (e: 'dragend'): void
}>()

const { t } = useI18n()
const { priorityLabel, priorityType } = useTaskMeta()
</script>

<template>
  <article
    class="task wf-card"
    :class="{ 'task--overdue': task.is_overdue }"
    :draggable="draggable"
    @dragstart="emit('dragstart', $event, task)"
    @dragend="emit('dragend')"
  >
    <header class="task__head">
      <h4 class="task__title">{{ task.title }}</h4>
      <el-dropdown trigger="click" @command="(command: string) => command === 'edit' ? emit('edit', task) : emit('remove', task)">
        <el-icon class="task__menu"><MoreFilled /></el-icon>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item command="edit">{{ t('common.edit') }}</el-dropdown-item>
            <el-dropdown-item command="remove" divided>{{ t('common.delete') }}</el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </header>

    <p v-if="task.description" class="task__description">{{ task.description }}</p>

    <footer class="task__footer">
      <el-tag :type="priorityType(task.priority)" size="small" effect="light" round>
        {{ priorityLabel(task.priority) }}
      </el-tag>
      <span v-if="task.deadline" class="task__deadline" :class="{ 'task__deadline--late': task.is_overdue }">
        <el-icon><Calendar /></el-icon>{{ formatDate(task.deadline) }}
      </span>
      <el-tooltip v-if="task.employee" :content="task.employee.full_name" placement="top">
        <el-avatar :size="22" :src="task.employee.avatar ?? undefined" class="task__avatar">
          {{ task.employee.first_name[0] }}{{ task.employee.last_name[0] }}
        </el-avatar>
      </el-tooltip>
      <span v-else class="task__unassigned">{{ t('tasks.unassigned') }}</span>
    </footer>
  </article>
</template>

<style scoped lang="scss">
.task {
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  cursor: grab;
  transition: box-shadow 0.15s ease, transform 0.15s ease;

  &:hover {
    box-shadow: 0 4px 14px rgba(16, 24, 40, 0.09);
  }

  &:active {
    cursor: grabbing;
  }

  &--overdue {
    border-left: 3px solid var(--el-color-danger);
  }
}

.task__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
}

.task__title {
  margin: 0;
  font-size: 13.5px;
  font-weight: 600;
  line-height: 1.35;
}

.task__menu {
  color: var(--wf-text-muted);
  cursor: pointer;
  outline: none;
}

.task__description {
  margin: 0;
  font-size: 12px;
  color: var(--wf-text-muted);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.task__footer {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.task__deadline {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11.5px;
  color: var(--wf-text-muted);

  &--late {
    color: var(--el-color-danger);
  }
}

.task__avatar {
  margin-left: auto;
  font-size: 10px;
}

.task__unassigned {
  margin-left: auto;
  font-size: 11px;
  color: var(--wf-text-muted);
}
</style>
