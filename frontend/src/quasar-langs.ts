import type { QuasarLanguage } from 'quasar'
import type { Locale } from '@/types'

import langEnUS from 'quasar/lang/en-US'

/**
 * Языковые пакеты грузятся отдельными чанками. Английский — исключение:
 * Quasar статически подключает его как язык по умолчанию, и отдельный
 * динамический импорт только ломал бы чанкование.
 */
const loaders: Record<Locale, () => Promise<{ default: QuasarLanguage }>> = {
  ru: () => import('quasar/lang/ru'),
  en: () => Promise.resolve({ default: langEnUS }),
  kk: () => import('quasar/lang/kk'),
}

export async function loadQuasarLang(locale: Locale): Promise<QuasarLanguage> {
  const pack = await loaders[locale]()
  return pack.default
}
