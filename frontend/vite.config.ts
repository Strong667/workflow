import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
  build: {
    // Ручное разделение вендорного кода, чтобы initial-чанк оставался лёгким.
    rollupOptions: {
      output: {
        manualChunks(id: string) {
          if (!id.includes('node_modules')) return undefined
          if (id.includes('element-plus')) return 'element'
          if (id.includes('vue-i18n') || id.includes('@intlify')) return 'i18n'
          if (id.includes('vue-router') || id.includes('pinia') || id.includes('/@vue/') || id.includes('/vue/')) {
            return 'vue'
          }
          return undefined
        },
      },
    },
    // Element Plus намеренно вынесен в один долгоживущий вендорный чанк.
    chunkSizeWarningLimit: 1100,
  },
})
