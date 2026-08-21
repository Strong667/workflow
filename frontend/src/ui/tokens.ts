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
    // Столбики «создано» — графит (в тёмной теме светлый), «завершено» — цитрон.
    primary: resolveToken('--wf-ink', '#15171a'),
    success: resolveToken('--wf-accent', '#d8f24b'),
    warn: resolveToken('--wf-review', '#d98613'),
    danger: resolveToken('--wf-danger', '#d8412f'),
    neutral: resolveToken('--wf-todo', '#9ba2aa'),
    text: resolveToken('--wf-ink', '#15171a'),
    muted: resolveToken('--wf-ink-3', '#8b9199'),
    grid: resolveToken('--wf-line', '#e4e0d7'),
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
