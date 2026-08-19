import { ref, watch } from 'vue'
import { useUiStore } from '@/stores/ui'

interface ChartTokens {
  primary: string
  success: string
  warn: string
  danger: string
  neutral: string
  text: string
  muted: string
  grid: string
}

/**
 * PrimeVue 5 отдаёт токены как `light-dark(#fff, #000)` — Chart.js такую
 * запись не понимает, поэтому просим браузер вычислить итоговый цвет.
 */
function resolveColor(variable: string, fallback: string): string {
  const probe = document.createElement('span')
  probe.style.cssText = `position:absolute;visibility:hidden;color:var(${variable},${fallback})`
  document.body.appendChild(probe)
  const color = getComputedStyle(probe).color
  probe.remove()
  return color || fallback
}

function readTokens(): ChartTokens {
  return {
    primary: resolveColor('--p-primary-500', '#6366f1'),
    success: resolveColor('--p-green-500', '#22c55e'),
    warn: resolveColor('--p-amber-500', '#f59e0b'),
    danger: resolveColor('--p-red-500', '#ef4444'),
    neutral: resolveColor('--p-surface-400', '#94a3b8'),
    text: resolveColor('--p-text-color', '#1f2937'),
    muted: resolveColor('--p-text-muted-color', '#6b7280'),
    grid: resolveColor('--p-content-border-color', '#e5e7eb'),
  }
}

/** Токены темы значениями; пересчитываются при переключении светлой/тёмной. */
export function useChartTheme() {
  const ui = useUiStore()
  const tokens = ref<ChartTokens>(readTokens())

  watch(
    () => ui.theme,
    () => {
      tokens.value = readTokens()
    },
    { flush: 'post' },
  )

  return tokens
}
