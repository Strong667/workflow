import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import { i18n, setLocale } from '@/locales'
import type { Locale, Theme } from '@/types'

const THEME_KEY = 'workflow_theme'
const LOCALE_KEY = 'workflow_locale'
const SIDEBAR_KEY = 'workflow_sidebar'

function preferredTheme(): Theme {
  const stored = localStorage.getItem(THEME_KEY) as Theme | null
  if (stored === 'light' || stored === 'dark') return stored
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

export const useUiStore = defineStore('ui', () => {
  const theme = ref<Theme>(preferredTheme())
  const locale = ref<Locale>((localStorage.getItem(LOCALE_KEY) as Locale | null) ?? 'ru')
  const sidebarCollapsed = ref(localStorage.getItem(SIDEBAR_KEY) === '1')

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
    localStorage.setItem(LOCALE_KEY, next)
    await setLocale(next)
    document.documentElement.lang = next
  }

  function toggleSidebar(): void {
    sidebarCollapsed.value = !sidebarCollapsed.value
  }

  watch(sidebarCollapsed, (value) => localStorage.setItem(SIDEBAR_KEY, value ? '1' : '0'))

  // Первичная синхронизация DOM с сохранёнными настройками.
  applyTheme(theme.value)
  document.documentElement.lang = locale.value
  i18n.global.locale.value = locale.value

  return { theme, locale, sidebarCollapsed, applyTheme, toggleTheme, applyLocale, toggleSidebar }
})
