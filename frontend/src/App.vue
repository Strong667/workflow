<script setup lang="ts">
import { watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { usePrimeVue } from 'primevue/config'
import { PRIME_LOCALES } from '@/ui/locales'
import { useUiStore } from '@/stores/ui'

const ui = useUiStore()
const route = useRoute()
const { t } = useI18n()
const primevue = usePrimeVue()

// Подписи внутри компонентов PrimeVue переключаются вслед за языком интерфейса.
watch(
  () => ui.locale,
  (locale) => {
    // Свои подписи кладём поверх дефолтных, чтобы не потерять остальные ключи.
    Object.assign(primevue.config.locale ?? {}, PRIME_LOCALES[locale])
  },
  { immediate: true },
)

watch(
  () => [route.meta.titleKey, ui.locale],
  () => {
    const key = route.meta.titleKey as string | undefined
    document.title = key ? `${t(key)} — WorkFlow CRM` : 'WorkFlow CRM'
  },
  { immediate: true },
)
</script>

<template>
  <router-view v-slot="{ Component }">
    <transition name="fade" mode="out-in">
      <component :is="Component" />
    </transition>
  </router-view>

  <Toast position="top-right" />
  <ConfirmDialog />
</template>
