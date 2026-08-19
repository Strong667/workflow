import { computed } from 'vue'
import { useUiStore } from '@/stores/ui'

/**
 * Значение токена темы «как его видит браузер».
 *
 * PrimeVue 4 отдаёт токены обычным цветом, PrimeVue 5 — записью
 * `light-dark(#fff, #000)`, которую Chart.js разобрать не может.
 * Пробный элемент снимает разницу между версиями.
 */
export function resolveToken(variable: string, fallback: string): string {
  const probe = document.createElement('span')
  probe.style.cssText = `position:absolute;visibility:hidden;color:var(${variable},${fallback})`
  document.body.appendChild(probe)
  const color = getComputedStyle(probe).color
  probe.remove()
  return color || fallback
}

export interface ChartTokens {
  primary: string
  success: string
  warn: string
  danger: string
  neutral: string
  text: string
  muted: string
  grid: string
}

function readTokens(): ChartTokens {
  return {
    primary: resolveToken('--p-primary-500', '#6366f1'),
    success: resolveToken('--p-green-500', '#22c55e'),
    warn: resolveToken('--p-amber-500', '#f59e0b'),
    danger: resolveToken('--p-red-500', '#ef4444'),
    neutral: resolveToken('--p-surface-400', '#94a3b8'),
    text: resolveToken('--p-text-color', '#1f2937'),
    muted: resolveToken('--p-text-muted-color', '#6b7280'),
    grid: resolveToken('--p-content-border-color', '#e5e7eb'),
  }
}

/** Токены значениями; пересчитываются при переключении светлой и тёмной темы. */
export function useChartTokens() {
  const ui = useUiStore()
  return computed<ChartTokens>(() => {
    void ui.theme
    return readTokens()
  })
}
