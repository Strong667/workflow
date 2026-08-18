<script setup lang="ts">
import { computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import elementEn from 'element-plus/es/locale/lang/en'
import elementKk from 'element-plus/es/locale/lang/kk'
import elementRu from 'element-plus/es/locale/lang/ru'
import { useUiStore } from '@/stores/ui'
import type { Locale } from '@/types'

const ui = useUiStore()
const route = useRoute()
const { t } = useI18n()

const elementLocales = { ru: elementRu, en: elementEn, kk: elementKk }
const elementLocale = computed(() => elementLocales[ui.locale as Locale] ?? elementRu)

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
  <el-config-provider :locale="elementLocale">
    <router-view v-slot="{ Component }">
      <transition name="fade" mode="out-in">
        <component :is="Component" />
      </transition>
    </router-view>
  </el-config-provider>
</template>
