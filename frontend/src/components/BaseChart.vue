<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Chart, registerables, type ChartData, type ChartOptions, type ChartType } from 'chart.js'

Chart.register(...registerables)

const props = defineProps<{
  type: ChartType
  data: ChartData
  options?: ChartOptions
  height?: number
}>()

const canvas = ref<HTMLCanvasElement>()
let chart: Chart | null = null

function render(): void {
  if (!canvas.value) return
  chart?.destroy()
  chart = new Chart(canvas.value, {
    type: props.type,
    data: props.data as ChartData,
    options: props.options,
  })
}

onMounted(render)
onBeforeUnmount(() => chart?.destroy())

// Данные и опции зависят от темы и языка — перерисовываем целиком,
// объём данных на дашборде небольшой.
watch(() => [props.data, props.options], render, { deep: true })
</script>

<template>
  <div class="chart" :style="{ height: `${height ?? 230}px` }">
    <canvas ref="canvas" />
  </div>
</template>

<style scoped>
.chart {
  position: relative;
  width: 100%;
}
</style>
