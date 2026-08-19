<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useQuasar } from 'quasar'
import { authApi } from '@/api'
import { apiMessage } from '@/api/client'
import { SUPPORTED_LOCALES } from '@/locales'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import type { Locale, Theme } from '@/types'

const { t } = useI18n()
const $q = useQuasar()
const ui = useUiStore()
const auth = useAuthStore()

const stack = [
  'Vue 3',
  'TypeScript',
  'Vite',
  'Pinia',
  'Vue Router',
  'Axios',
  'Quasar 2',
  'Chart.js',
  'Vue I18n',
  'Laravel 12',
  'MySQL 8',
]

const themeOptions = computed(() => [
  { value: 'light', label: t('settings.light'), icon: 'light_mode' },
  { value: 'dark', label: t('settings.dark'), icon: 'dark_mode' },
])

const about = computed(() => [
  { label: t('settings.version'), value: '1.0.0' },
  { label: t('nav.profile'), value: auth.user?.name ?? '—' },
  { label: t('settings.role'), value: auth.role ? t(`roles.${auth.role}`) : '—' },
])

/** Настройки хранятся локально и — для авторизованного пользователя — на сервере. */
async function persist(payload: { theme?: Theme; language?: Locale }): Promise<void> {
  try {
    auth.setUser(await authApi.updateProfile(payload))
  } catch (error) {
    $q.notify({ type: 'negative', message: apiMessage(error) })
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

        <div class="row-line">
          <div class="row-line__label">
            <span>{{ t('settings.theme') }}</span>
            <span class="wf-muted row-line__hint">
              {{ ui.theme === 'dark' ? t('settings.dark') : t('settings.light') }}
            </span>
          </div>
          <q-btn-toggle
            :model-value="ui.theme"
            :options="themeOptions"
            no-caps
            unelevated
            toggle-color="primary"
            color="grey-3"
            text-color="grey-9"
            @update:model-value="(value: Theme) => onThemeChange(value)"
          />
        </div>

        <div class="row-line">
          <div class="row-line__label">
            <span>{{ t('settings.language') }}</span>
            <span class="wf-muted row-line__hint">ru / en / kk</span>
          </div>
          <q-select
            :model-value="ui.locale"
            :options="SUPPORTED_LOCALES"
            option-label="label"
            option-value="value"
            emit-value
            map-options
            outlined
            dense
            class="row-line__control"
            @update:model-value="(value: Locale) => onLocaleChange(value)"
          />
        </div>

        <div class="row-line">
          <div class="row-line__label">
            <span>{{ t('settings.sidebar') }}</span>
          </div>
          <q-toggle :model-value="ui.sidebarCollapsed" color="primary" @update:model-value="ui.toggleSidebar()" />
        </div>
      </section>

      <section class="wf-card panel">
        <h3 class="panel__title">{{ t('settings.about') }}</h3>

        <q-list separator>
          <q-item v-for="row in about" :key="row.label" class="about__row">
            <q-item-section class="wf-muted">{{ row.label }}</q-item-section>
            <q-item-section side class="about__value">{{ row.value }}</q-item-section>
          </q-item>
        </q-list>

        <h4 class="panel__subtitle">{{ t('settings.stack') }}</h4>
        <div class="tags">
          <q-chip v-for="item in stack" :key="item" dense square outline color="primary" :label="item" />
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
  margin: 0 0 12px;
  font-size: 14px;
  font-weight: 650;
}

.panel__subtitle {
  margin: 20px 0 10px;
  font-size: 13px;
  font-weight: 600;
}

.row-line {
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

.row-line__label {
  display: flex;
  flex-direction: column;
  font-size: 13.5px;
}

.row-line__hint {
  font-size: 11.5px;
}

.row-line__control {
  width: 190px;
}

.about__row {
  padding-left: 0;
  padding-right: 0;
  font-size: 13px;
  min-height: 42px;
}

.about__value {
  font-weight: 550;
}

.tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}
</style>
