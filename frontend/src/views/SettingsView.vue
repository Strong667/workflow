<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { ElMessage } from 'element-plus'
import { authApi } from '@/api'
import { apiMessage } from '@/api/client'
import { SUPPORTED_LOCALES } from '@/locales'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import type { Locale, Theme } from '@/types'

const { t } = useI18n()
const ui = useUiStore()
const auth = useAuthStore()

const stack = [
  'Vue 3',
  'TypeScript',
  'Vite',
  'Pinia',
  'Vue Router',
  'Axios',
  'Element Plus',
  'Vue I18n',
  'Laravel 12',
  'MySQL 8',
]

/** Настройки хранятся локально и — для авторизованного пользователя — на сервере. */
async function persist(payload: { theme?: Theme; language?: Locale }): Promise<void> {
  try {
    const user = await authApi.updateProfile(payload)
    auth.setUser(user)
  } catch (error) {
    ElMessage.error(apiMessage(error))
  }
}

async function onThemeChange(value: Theme): Promise<void> {
  ui.applyTheme(value)
  await persist({ theme: value })
}

async function onLocaleChange(value: Locale): Promise<void> {
  await ui.applyLocale(value)
  await persist({ language: value })
}
</script>

<template>
  <div class="wf-page">
    <div class="wf-page__header">
      <div>
        <h1 class="wf-page__title">{{ t('settings.title') }}</h1>
        <p class="wf-page__subtitle">{{ t('settings.subtitle') }}</p>
      </div>
    </div>

    <div class="wf-grid settings">
      <section class="wf-card panel">
        <h3 class="panel__title">{{ t('settings.appearance') }}</h3>

        <div class="row">
          <div class="row__label">
            <span>{{ t('settings.theme') }}</span>
            <span class="wf-muted row__hint">{{ ui.theme === 'dark' ? t('settings.dark') : t('settings.light') }}</span>
          </div>
          <el-radio-group :model-value="ui.theme" @update:model-value="(value: string | number | boolean | undefined) => onThemeChange(value as Theme)">
            <el-radio-button value="light">
              <el-icon><Sunny /></el-icon> {{ t('settings.light') }}
            </el-radio-button>
            <el-radio-button value="dark">
              <el-icon><Moon /></el-icon> {{ t('settings.dark') }}
            </el-radio-button>
          </el-radio-group>
        </div>

        <div class="row">
          <div class="row__label">
            <span>{{ t('settings.language') }}</span>
            <span class="wf-muted row__hint">ru / en / kk</span>
          </div>
          <el-select
            :model-value="ui.locale"
            style="width: 180px"
            @update:model-value="(value: Locale) => onLocaleChange(value)"
          >
            <el-option v-for="item in SUPPORTED_LOCALES" :key="item.value" :label="item.label" :value="item.value" />
          </el-select>
        </div>

        <div class="row">
          <div class="row__label">
            <span>{{ t('settings.sidebar') }}</span>
          </div>
          <el-switch :model-value="ui.sidebarCollapsed" @update:model-value="ui.toggleSidebar()" />
        </div>
      </section>

      <section class="wf-card panel">
        <h3 class="panel__title">{{ t('settings.about') }}</h3>

        <el-descriptions :column="1" border>
          <el-descriptions-item :label="t('settings.version')">1.0.0</el-descriptions-item>
          <el-descriptions-item :label="t('nav.profile')">{{ auth.user?.name }}</el-descriptions-item>
          <el-descriptions-item :label="t('settings.role')">
            {{ auth.role ? t(`roles.${auth.role}`) : '—' }}
          </el-descriptions-item>
        </el-descriptions>

        <h4 class="panel__subtitle">{{ t('settings.stack') }}</h4>
        <div class="tags">
          <el-tag v-for="item in stack" :key="item" effect="plain" round size="small">{{ item }}</el-tag>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped lang="scss">
.settings {
  grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
  align-items: start;
}

.panel {
  padding: 20px;
}

.panel__title {
  margin: 0 0 18px;
  font-size: 14px;
  font-weight: 650;
}

.panel__subtitle {
  margin: 20px 0 10px;
  font-size: 13px;
  font-weight: 600;
}

.row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 0;
  border-bottom: 1px solid var(--wf-border);

  &:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }
}

.row__label {
  display: flex;
  flex-direction: column;
  font-size: 13.5px;
}

.row__hint {
  font-size: 11.5px;
}

.tags {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
}
</style>
