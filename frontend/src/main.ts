import { createApp } from 'vue'
import { createPinia } from 'pinia'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import 'element-plus/theme-chalk/dark/css-vars.css'
import '@/styles/main.scss'

import App from './App.vue'
import router from './router'
import { getStoredLocale, i18n, setLocale } from './locales'
import { icons } from './icons'

async function bootstrap(): Promise<void> {
  // Переводы выбранного языка загружаем до монтирования — иначе первый кадр будет на fallback-локали.
  await setLocale(getStoredLocale())

  const app = createApp(App)

  app.use(createPinia())
  app.use(router)
  app.use(i18n)
  app.use(ElementPlus)

  // Регистрируем только используемые иконки — полный пакет весит на порядок больше.
  for (const [name, component] of Object.entries(icons)) {
    app.component(name, component)
  }

  app.mount('#app')
}

void bootstrap()
