<script setup lang="ts">
import { ref } from 'vue'
import TaskCard from '@/components/TaskCard.vue'
import EmptyState from '@/components/EmptyState.vue'
import type { Task, TaskStatus } from '@/types'

defineProps<{
  status: TaskStatus
  title: string
  color: string
  tasks: Task[]
  emptyText: string
}>()

const emit = defineEmits<{
  (e: 'drop', payload: { status: TaskStatus; index: number }): void
  (e: 'dragstart', event: DragEvent, task: Task): void
  (e: 'dragend'): void
  (e: 'edit', task: Task): void
  (e: 'remove', task: Task): void
}>()

const isOver = ref(false)

function onDrop(event: DragEvent, index: number, status: TaskStatus): void {
  event.preventDefault()
  isOver.value = false
  emit('drop', { status, index })
}
</script>

<template>
  <section
    class="column"
    :class="{ 'column--over': isOver }"
    @dragover.prevent="isOver = true"
    @dragleave="isOver = false"
    @drop="onDrop($event, tasks.length, status)"
  >
    <header class="column__head">
      <span class="column__dot" :style="{ background: color }" />
      <h3 class="column__title">{{ title }}</h3>
      <Badge :value="tasks.length" severity="secondary" />
    </header>

    <div class="column__body">
      <transition-group name="list" tag="div" class="column__list">
        <div
          v-for="(task, index) in tasks"
          :key="task.id"
          class="column__item"
          @dragover.prevent.stop
          @drop.stop="onDrop($event, index, status)"
        >
          <TaskCard
            :task="task"
            draggable
            @dragstart="(event, payload) => emit('dragstart', event, payload)"
            @dragend="emit('dragend')"
            @edit="(payload) => emit('edit', payload)"
            @remove="(payload) => emit('remove', payload)"
          />
        </div>
      </transition-group>

      <EmptyState v-if="!tasks.length" :text="emptyText" icon="pi pi-inbox" />
    </div>
  </section>
</template>

<style scoped lang="scss">
.column {
  display: flex;
  flex-direction: column;
  min-width: 268px;
  flex: 1;
  background: var(--p-surface-100);
  border: 1px dashed transparent;
  border-radius: var(--wf-radius);
  padding: 12px;
  transition: border-color 0.15s ease, background 0.15s ease;

  &--over {
    border-color: var(--p-primary-color);
    background: color-mix(in srgb, var(--p-primary-color) 8%, transparent);
  }
}

html.dark .column {
  background: var(--p-surface-900);
}

.column__head {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.column__dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.column__title {
  margin: 0;
  font-size: 13px;
  font-weight: 650;
  flex: 1;
}

.column__body {
  flex: 1;
  min-height: 120px;
}

.column__list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  position: relative;
}
</style>
