<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  items: Array<{ label: string; value: number; color?: string }>
}>()

const max = computed(() => Math.max(1, ...props.items.map((item) => item.value)))
</script>

<template>
  <ul class="meters">
    <li v-for="item in items" :key="item.label" class="meters__row">
      <span class="meters__label">{{ item.label }}</span>
      <span class="meters__track">
        <span
          class="meters__fill"
          :style="{ width: `${(item.value / max) * 100}%`, background: item.color ?? 'var(--el-color-primary)' }"
        />
      </span>
      <span class="meters__value">{{ item.value }}</span>
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
  gap: 12px;
}

.meters__row {
  display: grid;
  grid-template-columns: minmax(90px, 34%) 1fr 36px;
  align-items: center;
  gap: 12px;
  font-size: 13px;
}

.meters__label {
  color: var(--el-text-color-regular);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.meters__track {
  height: 8px;
  border-radius: 999px;
  background: var(--el-fill-color);
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
  font-variant-numeric: tabular-nums;
  color: var(--wf-text-muted);
}
</style>
