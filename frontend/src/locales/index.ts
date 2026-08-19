import { createI18n } from 'vue-i18n'
import ru from './ru'
import type { Locale } from '@/types'

/** Русская локаль — эталон структуры сообщений для остальных языков. */
export type MessageSchema = {
  [K in keyof typeof ru]: {
    [P in keyof (typeof ru)[K]]: (typeof ru)[K][P] extends string
      ? string
      : { [S in keyof (typeof ru)[K][P]]: string }
  }
}

export const SUPPORTED_LOCALES: Array<{ value: Locale; label: string }> = [
  { value: 'ru', label: 'Русский' },
  { value: 'en', label: 'English' },
  { value: 'kk', label: 'Қазақша' },
]

export const i18n = createI18n<[MessageSchema], Locale, false>({
  legacy: false,
  globalInjection: true,
  locale: 'ru',
  fallbackLocale: 'ru',
  messages: { ru } as Record<Locale, MessageSchema>,
})

export const LOCALE_STORAGE_KEY = 'workflow_locale'

/** Локаль из localStorage с проверкой на поддерживаемое значение. */
export function getStoredLocale(): Locale {
  const stored = localStorage.getItem(LOCALE_STORAGE_KEY) as Locale | null
  return SUPPORTED_LOCALES.some((item) => item.value === stored) ? (stored as Locale) : 'ru'
}

const loaded = new Set<Locale>(['ru'])

/**
 * Загрузчики перечислены явно: шаблонный import() Vite не разбирает статически,
 * и в прод-сборке такие чанки не создаются.
 */
type LazyLocale = Exclude<Locale, 'ru'>

const loaders: Record<LazyLocale, () => Promise<{ default: MessageSchema }>> = {
  en: () => import('./en'),
  kk: () => import('./kk'),
}

/** Догружает файл переводов по требованию — отдельным чанком. */
export async function setLocale(locale: Locale): Promise<void> {
  if (!loaded.has(locale)) {
    const messages = await loaders[locale as LazyLocale]()
    i18n.global.setLocaleMessage(locale, messages.default)
    loaded.add(locale)
  }
  i18n.global.locale.value = locale
}
