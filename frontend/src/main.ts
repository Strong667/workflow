import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { Dialog, Loading, Notify, Quasar } from 'quasar'
import '@quasar/extras/material-icons/material-icons.css'
import 'quasar/src/css/index.sass'
import '@/styles/main.scss'

import App from './App.vue'
import router from './router'
import { getStoredLocale, i18n, setLocale } from './locales'
import { loadQuasarLang } from './quasar-langs'

async function bootstrap(): Promise<void> {
  // Переводы и языковой пакет Quasar загружаем до монтирования,
  // иначе первый кадр будет на fallback-локали.
  const locale = getStoredLocale()
  const [lang] = await Promise.all([loadQuasarLang(locale), setLocale(locale)])

  const app = createApp(App)

  app.use(createPinia())
  app.use(router)
  app.use(i18n)
  app.use(Quasar, {
    lang,
    plugins: { Notify, Dialog, Loading },
    config: {
      brand: { primary: '#4f46e5' },
      notify: { position: 'top-right', timeout: 3000, actions: [{ icon: 'close', color: 'white', round: true }] },
    },
  })

  app.mount('#app')
}

void bootstrap()
