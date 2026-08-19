import { definePreset } from '@primeuix/themes'
import Aura from '@primeuix/themes/aura'
import type { PrimeVueConfiguration } from 'primevue/config'

/** Индиго вместо изумрудного из Aura — фирменный цвет CRM. */
const WorkFlowPreset = definePreset(Aura, {
  semantic: {
    primary: {
      50: '{indigo.50}',
      100: '{indigo.100}',
      200: '{indigo.200}',
      300: '{indigo.300}',
      400: '{indigo.400}',
      500: '{indigo.500}',
      600: '{indigo.600}',
      700: '{indigo.700}',
      800: '{indigo.800}',
      900: '{indigo.900}',
      950: '{indigo.950}',
    },
  },
})

export const primeVueOptions: PrimeVueConfiguration = {
  theme: {
    preset: WorkFlowPreset,
    options: {
      // Тема переключается классом .dark на <html> — его ставит ui-стор.
      darkModeSelector: '.dark',
      cssLayer: {
        name: 'primevue',
        order: 'theme, base, primevue',
      },
    },
  },
  ripple: true,
}
