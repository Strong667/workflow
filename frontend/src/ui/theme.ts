import { definePreset } from '@primeuix/themes'
import Aura from '@primeuix/themes/aura'
import type { PrimeVueConfiguration } from 'primevue/config'

/**
 * Тёплая бумага и графит с цитроновым акцентом.
 *
 * Основная кнопка в светлой теме почти чёрная, в тёмной — цитроновая,
 * поэтому primary задаётся отдельно для каждой схемы.
 */
const GRAPHITE = {
  50: '#F4F4F5',
  100: '#E7E7E9',
  200: '#C9CACD',
  300: '#A6A8AC',
  400: '#6E7176',
  500: '#15171A',
  600: '#101216',
  700: '#0C0D0F',
  800: '#08090B',
  900: '#050607',
  950: '#000000',
}

const CITRON = {
  50: '#FBFEEC',
  100: '#F4FBC8',
  200: '#EBF89C',
  300: '#E2F571',
  400: '#DDF45C',
  500: '#D8F24B',
  600: '#C2DB35',
  700: '#A3B92A',
  800: '#7E8F20',
  900: '#5C6817',
  950: '#3A420E',
}

/** Тёплые поверхности: бумага вместо холодного серого. */
const PAPER = {
  0: '#FFFFFF',
  50: '#FAF8F3',
  100: '#F3F1EC',
  200: '#F0EDE6',
  300: '#E4E0D7',
  400: '#C8C4B9',
  500: '#8B9199',
  600: '#565C64',
  700: '#3A3F45',
  800: '#22262B',
  900: '#15171A',
  950: '#0C0D0F',
}

const WorkFlowPreset = definePreset(Aura, {
  primitive: {
    borderRadius: {
      none: '0',
      xs: '4px',
      sm: '6px',
      md: '8px',
      lg: '11px',
      xl: '14px',
    },
  },
  semantic: {
    primary: GRAPHITE,
    colorScheme: {
      light: {
        primary: {
          color: '{primary.500}',
          contrastColor: '#F7F5F0',
          hoverColor: '{primary.400}',
          activeColor: '{primary.600}',
        },
        surface: PAPER,
        content: {
          background: '{surface.0}',
          hoverBackground: '{surface.100}',
          borderColor: '#E4E0D7',
          color: '#15171A',
        },
        text: {
          color: '#15171A',
          mutedColor: '#8B9199',
        },
        formField: {
          background: '{surface.0}',
          borderColor: '#E4E0D7',
          hoverBorderColor: '#C8C4B9',
          focusBorderColor: '{primary.500}',
          color: '#15171A',
          placeholderColor: '#8B9199',
        },
      },
      dark: {
        // В тёмной теме акцентная кнопка цитроновая с тёмным текстом.
        primary: {
          color: CITRON[500],
          contrastColor: '#15171A',
          hoverColor: CITRON[400],
          activeColor: CITRON[600],
        },
        surface: {
          0: '#15171A',
          50: '#1A1D21',
          100: '#22262B',
          200: '#262A2F',
          300: '#2F343A',
          400: '#6B7178',
          500: '#8B9199',
          600: '#A5ABB3',
          700: '#C7CBD1',
          800: '#E2E0DA',
          900: '#F1EFE9',
          950: '#FFFFFF',
        },
        content: {
          background: '#15171A',
          hoverBackground: '#22262B',
          borderColor: '#262A2F',
          color: '#F1EFE9',
        },
        text: {
          color: '#F1EFE9',
          mutedColor: '#6B7178',
        },
        formField: {
          background: '#1A1D21',
          borderColor: '#262A2F',
          hoverBorderColor: '#2F343A',
          focusBorderColor: CITRON[500],
          color: '#F1EFE9',
          placeholderColor: '#6B7178',
        },
      },
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
  ripple: false,
}
