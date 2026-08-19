import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'
import Tooltip from 'primevue/tooltip'
import Ripple from 'primevue/ripple'
import 'primeicons/primeicons.css'
import '@/styles/main.scss'

import App from './App.vue'
import router from './router'
import { getStoredLocale, i18n, setLocale } from './locales'
import { primeVueOptions } from './plugins/primevue'
import { registerComponents } from './plugins/components'

async function bootstrap(): Promise<void> {
  // Переводы выбранного языка загружаем до монтирования — иначе первый кадр будет на fallback-локали.
  await setLocale(getStoredLocale())

  const app = createApp(App)

  app.use(createPinia())
  app.use(router)
  app.use(i18n)
  app.use(PrimeVue, primeVueOptions)
  app.use(ToastService)
  app.use(ConfirmationService)

  app.directive('tooltip', Tooltip)
  app.directive('ripple', Ripple)

  registerComponents(app)

  app.mount('#app')
}

void bootstrap()
