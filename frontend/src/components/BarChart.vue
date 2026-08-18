<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    /** Каждый элемент — группа столбцов с общим названием по оси X. */
    data: Array<{ label: string; values: number[] }>
    legend?: string[]
    colors?: string[]
    height?: number
  }>(),
  {
    legend: () => [],
    colors: () => ['var(--el-color-primary)', 'var(--el-color-success)'],
    height: 220,
  },
)

const chartWidth = 640
const padding = { top: 16, right: 8, bottom: 28, left: 32 }

const max = computed(() => Math.max(1, ...props.data.flatMap((item) => item.values)))
const plotHeight = computed(() => props.height - padding.top - padding.bottom)
const plotWidth = computed(() => chartWidth - padding.left - padding.right)
const groupWidth = computed(() => plotWidth.value / Math.max(props.data.length, 1))

const seriesCount = computed(() => Math.max(1, ...props.data.map((item) => item.values.length)))
const barWidth = computed(() => Math.min(26, (groupWidth.value - 14) / seriesCount.value))

const ticks = computed(() => {
  const step = Math.ceil(max.value / 3) || 1
  return [0, step, step * 2, step * 3]
})

function barHeight(value: number): number {
  return (value / max.value) * plotHeight.value
}
</script>

<template>
  <div class="chart">
    <svg :viewBox="`0 0 ${chartWidth} ${height}`" class="chart__svg" role="img">
      <g v-for="tick in ticks" :key="tick">
        <line
          :x1="padding.left"
          :x2="chartWidth - padding.right"
          :y1="padding.top + plotHeight - barHeight(tick)"
          :y2="padding.top + plotHeight - barHeight(tick)"
          class="chart__grid"
        />
        <text :x="0" :y="padding.top + plotHeight - barHeight(tick) + 4" class="chart__tick">{{ tick }}</text>
      </g>

      <g v-for="(group, groupIndex) in data" :key="group.label">
        <rect
          v-for="(value, seriesIndex) in group.values"
          :key="seriesIndex"
          :x="
            padding.left +
            groupIndex * groupWidth +
            groupWidth / 2 -
            (group.values.length * barWidth) / 2 +
            seriesIndex * barWidth
          "
          :y="padding.top + plotHeight - barHeight(value)"
          :width="barWidth - 3"
          :height="Math.max(barHeight(value), value > 0 ? 2 : 0)"
          :fill="colors[seriesIndex % colors.length]"
          rx="4"
        >
          <title>{{ group.label }}: {{ value }}</title>
        </rect>
        <text
          :x="padding.left + groupIndex * groupWidth + groupWidth / 2"
          :y="height - 8"
          text-anchor="middle"
          class="chart__label"
        >
          {{ group.label }}
        </text>
      </g>
    </svg>

    <div v-if="legend.length" class="chart__legend">
      <span v-for="(item, index) in legend" :key="item" class="chart__legend-item">
        <i class="chart__dot" :style="{ background: colors[index % colors.length] }" />
        {{ item }}
      </span>
    </div>
  </div>
</template>

<style scoped lang="scss">
.chart__svg {
  width: 100%;
  height: auto;
  display: block;
}

.chart__grid {
  stroke: var(--wf-border);
  stroke-width: 1;
}

.chart__tick,
.chart__label {
  fill: var(--wf-text-muted);
  font-size: 11px;
}

.chart__legend {
  display: flex;
  gap: 16px;
  justify-content: center;
  font-size: 12px;
  color: var(--wf-text-muted);
  margin-top: 6px;
}

.chart__legend-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.chart__dot {
  width: 9px;
  height: 9px;
  border-radius: 3px;
  display: inline-block;
}
</style>
