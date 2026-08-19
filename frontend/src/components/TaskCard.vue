<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import type { MenuItem } from 'primevue/menuitem'
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
const { priorityLabel, prioritySeverity } = useTaskMeta()
const menu = ref()

const menuItems = computed<MenuItem[]>(() => [
  { label: t('common.edit'), icon: 'pi pi-pencil', command: () => emit('edit', props.task) },
  { separator: true },
  { label: t('common.delete'), icon: 'pi pi-trash', command: () => emit('remove', props.task) },
])

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
      <Button
        icon="pi pi-ellipsis-v"
        severity="secondary"
        text
        rounded
        size="small"
        class="task__menu"
        :aria-label="t('common.actions')"
        @click.stop="menu.toggle($event)"
      />
      <Menu ref="menu" :model="menuItems" :popup="true" />
    </header>

    <p v-if="task.description" class="task__description">{{ task.description }}</p>

    <footer class="task__footer">
      <Tag :value="priorityLabel(task.priority)" :severity="prioritySeverity(task.priority)" rounded />
      <span v-if="task.deadline" class="task__deadline" :class="{ 'task__deadline--late': task.is_overdue }">
        <i class="pi pi-calendar" />{{ formatDate(task.deadline) }}
      </span>
      <Avatar
        v-if="task.employee"
        v-tooltip.top="task.employee.full_name"
        :image="task.employee.avatar ?? undefined"
        :label="task.employee.avatar ? undefined : initials"
        shape="circle"
        size="normal"
        class="task__avatar"
      />
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
    border-left: 3px solid var(--p-red-500);
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

.task__menu {
  flex: 0 0 auto;
  width: 1.8rem;
  height: 1.8rem;
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
    color: var(--p-red-500);
  }
}

.task__avatar {
  margin-left: auto;
  width: 1.6rem;
  height: 1.6rem;
  font-size: 0.65rem;
}

.task__unassigned {
  margin-left: auto;
  font-size: 11px;
  color: var(--wf-text-muted);
}
</style>
