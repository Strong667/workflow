<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    items: Array<{ label: string; value: number; color?: string }>
    /** Подписи с цветом значения — для приоритетов задач. */
    colorLabels?: boolean
  }>(),
  { colorLabels: false },
)

const max = computed(() => Math.max(1, ...props.items.map((item) => item.value)))
</script>

<template>
  <ul class="meters">
    <li v-for="item in items" :key="item.label" class="meters__row">
      <span class="meters__label" :style="colorLabels && item.color ? { color: item.color } : undefined">
        {{ item.label }}
      </span>
      <span class="meters__track">
        <span
          class="meters__fill"
          :style="{ width: `${(item.value / max) * 100}%`, background: item.color ?? 'var(--wf-ink)' }"
        />
      </span>
      <span class="wf-mono meters__value">{{ item.value }}</span>
    </li>
  </ul>
</template>

<style scoped lang="scss">
.meters {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 9px;
}

.meters__row {
  display: grid;
  grid-template-columns: minmax(78px, 30%) 1fr 30px;
  align-items: center;
  gap: 10px;
  font-size: 12px;
}

.meters__label {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.meters__track {
  height: 6px;
  border-radius: 999px;
  background: var(--wf-surface-3);
  overflow: hidden;
}

.meters__fill {
  display: block;
  height: 100%;
  border-radius: 999px;
  transition: width 0.35s ease;
}

.meters__value {
  text-align: right;
  font-size: 12px;
}
</style>
