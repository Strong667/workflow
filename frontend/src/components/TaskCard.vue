<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { formatDate, useTaskMeta } from '@/composables/useTaskMeta'
import type { Task } from '@/types'

const props = defineProps<{ task: Task; draggable?: boolean }>()
const emit = defineEmits<{
  (e: 'edit', task: Task): void
  (e: 'remove', task: Task): void
  (e: 'dragstart', event: DragEvent, task: Task): void
  (e: 'dragend'): void
}>()

const { t } = useI18n()
const { priorityLabel, priorityTone } = useTaskMeta()

const initials = computed(() => {
  const employee = props.task.employee
  return employee ? `${employee.first_name[0]}${employee.last_name[0]}` : ''
})
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
      <q-btn flat round dense size="sm" icon="more_vert" :aria-label="t('common.actions')" @click.stop>
        <q-menu auto-close>
          <q-list style="min-width: 150px">
            <q-item clickable @click="emit('edit', task)">
              <q-item-section avatar><q-icon name="edit" size="18px" /></q-item-section>
              <q-item-section>{{ t('common.edit') }}</q-item-section>
            </q-item>
            <q-separator />
            <q-item clickable class="text-negative" @click="emit('remove', task)">
              <q-item-section avatar><q-icon name="delete" size="18px" /></q-item-section>
              <q-item-section>{{ t('common.delete') }}</q-item-section>
            </q-item>
          </q-list>
        </q-menu>
      </q-btn>
    </header>

    <p v-if="task.description" class="task__description">{{ task.description }}</p>

    <footer class="task__footer">
      <q-chip
        dense
        square
        :color="priorityTone(task.priority)"
        text-color="white"
        :label="priorityLabel(task.priority)"
        class="task__chip"
      />
      <span v-if="task.deadline" class="task__deadline" :class="{ 'task__deadline--late': task.is_overdue }">
        <q-icon name="event" size="14px" />{{ formatDate(task.deadline) }}
      </span>
      <q-avatar v-if="task.employee" size="24px" color="primary" text-color="white" class="task__avatar">
        <img v-if="task.employee.avatar" :src="task.employee.avatar" alt="" />
        <template v-else>{{ initials }}</template>
        <q-tooltip>{{ task.employee.full_name }}</q-tooltip>
      </q-avatar>
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
  transition: box-shadow 0.15s ease;

  &:hover {
    box-shadow: 0 4px 14px rgba(16, 24, 40, 0.09);
  }

  &:active {
    cursor: grabbing;
  }

  &--overdue {
    border-left: 3px solid var(--q-negative);
  }
}

.task__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 4px;
}

.task__title {
  margin: 0;
  font-size: 13.5px;
  font-weight: 600;
  line-height: 1.35;
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

.task__chip {
  margin: 0;
  font-size: 11px;
}

.task__deadline {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11.5px;
  color: var(--wf-text-muted);

  &--late {
    color: var(--q-negative);
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
