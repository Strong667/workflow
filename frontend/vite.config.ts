import { fileURLToPath, URL } from 'node:url'
import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { quasar, transformAssetUrls } from '@quasar/vite-plugin'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')

  return {
    plugins: [
      vue({ template: { transformAssetUrls } }),
      // Плагин сам подставляет импорты компонентов и директив Quasar,
      // поэтому регистрировать их вручную не нужно.
      quasar({ sassVariables: 'src/styles/quasar-variables.sass' }),
    ],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
    css: {
      preprocessorOptions: {
        // Quasar подставляет в свой index.sass импорт 'src/styles/quasar-variables.sass'
        // относительно корня проекта — добавляем корень в пути поиска sass.
        sass: { loadPaths: [fileURLToPath(new URL('.', import.meta.url))] },
        scss: { loadPaths: [fileURLToPath(new URL('.', import.meta.url))] },
      },
    },
    server: {
      // Свои порты, отличные от дефолтных: на машине рядом крутятся другие проекты.
      port: Number(env.VITE_PORT) || 5188,
      // Без strictPort Vite при занятом порте молча уходит на соседний,
      // который может принадлежать другому проекту.
      strictPort: true,
      proxy: {
        '/api': {
          target: env.VITE_API_PROXY || 'http://127.0.0.1:8088',
          changeOrigin: true,
        },
      },
    },
    preview: {
      port: Number(env.VITE_PREVIEW_PORT) || 5189,
      strictPort: true,
    },
    build: {
      // Ручное разделение вендорного кода, чтобы initial-чанк оставался лёгким.
      rollupOptions: {
        output: {
          manualChunks(id: string) {
            if (!id.includes('node_modules')) return undefined
            if (id.includes('chart.js') || id.includes('@kurkle')) return 'charts'
            if (id.includes('vue-i18n') || id.includes('@intlify')) return 'i18n'
            if (id.includes('vue-router') || id.includes('pinia') || id.includes('/@vue/') || id.includes('/vue/')) {
              return 'vue'
            }
            // Quasar не сводим в один чанк: плагин подключает компоненты
            // по факту использования, и они уезжают в чанки своих страниц.
            return undefined
          },
        },
      },
      chunkSizeWarningLimit: 700,
    },
  }
})
