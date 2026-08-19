import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import { getStoredLocale, LOCALE_STORAGE_KEY, setLocale } from '@/locales'
import type { Locale, Theme } from '@/types'

const THEME_KEY = 'workflow_theme'
const SIDEBAR_KEY = 'workflow_sidebar'

function preferredTheme(): Theme {
  const stored = localStorage.getItem(THEME_KEY) as Theme | null
  if (stored === 'light' || stored === 'dark') return stored
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

/** На узких экранах сайдбар — оверлей, поэтому по умолчанию он свёрнут. */
function preferredSidebarState(): boolean {
  const stored = localStorage.getItem(SIDEBAR_KEY)
  if (stored !== null) return stored === '1'
  return window.innerWidth < 768
}

export const useUiStore = defineStore('ui', () => {
  const theme = ref<Theme>(preferredTheme())
  const locale = ref<Locale>(getStoredLocale())
  const sidebarCollapsed = ref(preferredSidebarState())

  function applyTheme(next: Theme): void {
    theme.value = next
    localStorage.setItem(THEME_KEY, next)
    document.documentElement.classList.toggle('dark', next === 'dark')
    document.documentElement.dataset.theme = next
  }

  function toggleTheme(): void {
    applyTheme(theme.value === 'dark' ? 'light' : 'dark')
  }

  async function applyLocale(next: Locale): Promise<void> {
    locale.value = next
    localStorage.setItem(LOCALE_STORAGE_KEY, next)
    await setLocale(next)
    document.documentElement.lang = next
  }

  function toggleSidebar(): void {
    sidebarCollapsed.value = !sidebarCollapsed.value
  }

  watch(sidebarCollapsed, (value) => localStorage.setItem(SIDEBAR_KEY, value ? '1' : '0'))

  // Первичная синхронизация DOM с сохранёнными настройками.
  // Сообщения для не-русских локалей грузятся отдельным чанком, поэтому через setLocale.
  applyTheme(theme.value)
  document.documentElement.lang = locale.value
  void setLocale(locale.value)

  return { theme, locale, sidebarCollapsed, applyTheme, toggleTheme, applyLocale, toggleSidebar }
})
