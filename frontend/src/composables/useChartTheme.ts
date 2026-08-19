import { computed } from 'vue'
import { useUiStore } from '@/stores/ui'

interface ChartTokens {
  primary: string
  success: string
  warn: string
  danger: string
  text: string
  muted: string
  grid: string
}

const LIGHT: ChartTokens = {
  primary: '#4f46e5',
  success: '#22c55e',
  warn: '#f59e0b',
  danger: '#ef4444',
  text: '#1f2333',
  muted: '#7b8194',
  grid: '#e4e6ee',
}

const DARK: ChartTokens = {
  ...LIGHT,
  text: '#e8e8ec',
  muted: '#9b9fae',
  grid: '#2a2a30',
}

/** Chart.js рисует на canvas и не понимает CSS-переменные — отдаём значения. */
export function useChartTheme() {
  const ui = useUiStore()
  return computed<ChartTokens>(() => (ui.theme === 'dark' ? DARK : LIGHT))
}
