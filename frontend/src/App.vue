<script setup lang="ts">
import { watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { loadQuasarLang } from '@/quasar-langs'
import { useUiStore } from '@/stores/ui'

const ui = useUiStore()
const route = useRoute()
const { t } = useI18n()
const $q = useQuasar()

// Подписи внутри компонентов Quasar переключаются вслед за языком интерфейса.
watch(
  () => ui.locale,
  async (locale) => {
    $q.lang.set(await loadQuasarLang(locale))
  },
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
</template>
