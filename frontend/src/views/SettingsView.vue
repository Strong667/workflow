<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { authApi } from '@/api'
import { apiMessage } from '@/api/client'
import { SUPPORTED_LOCALES } from '@/locales'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import type { Locale, Theme } from '@/types'
import { useNotify } from '@/ui/feedback'

const { t } = useI18n()
const notify = useNotify()
const ui = useUiStore()
const auth = useAuthStore()

const stack = [
  'Vue 3',
  'TypeScript',
  'Vite',
  'Pinia',
  'Vue Router',
  'Axios',
  'PrimeVue 4',
  'Chart.js',
  'Vue I18n',
  'Laravel 12',
  'MySQL 8',
]

const themeOptions = computed(() => [
  { value: 'light', label: t('settings.light'), icon: 'pi pi-sun' },
  { value: 'dark', label: t('settings.dark'), icon: 'pi pi-moon' },
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
    notify.error(apiMessage(error))
  }
}

async function onThemeChange(value: Theme | null): Promise<void> {
  if (!value) return
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
          <SelectButton
            :model-value="ui.theme"
            :options="themeOptions"
            option-label="label"
            option-value="value"
            :allow-empty="false"
            @update:model-value="(value: Theme | null) => onThemeChange(value)"
          >
            <template #option="{ option }">
              <i :class="option.icon" class="theme-option__icon" />
              <span>{{ option.label }}</span>
            </template>
          </SelectButton>
        </div>

        <div class="row">
          <div class="row__label">
            <span>{{ t('settings.language') }}</span>
            <span class="wf-muted row__hint">ru / en / kk</span>
          </div>
          <Select
            :model-value="ui.locale"
            :options="SUPPORTED_LOCALES"
            option-label="label"
            option-value="value"
            class="row__control"
            @update:model-value="(value: Locale) => onLocaleChange(value)"
          />
        </div>

        <div class="row">
          <div class="row__label">
            <span>{{ t('settings.sidebar') }}</span>
          </div>
          <ToggleSwitch :model-value="ui.sidebarCollapsed" @update:model-value="ui.toggleSidebar()" />
        </div>
      </section>

      <section class="wf-card panel">
        <h3 class="panel__title">{{ t('settings.about') }}</h3>

        <ul class="details">
          <li v-for="row in about" :key="row.label" class="details__row">
            <span class="wf-muted">{{ row.label }}</span>
            <span class="details__value">{{ row.value }}</span>
          </li>
        </ul>

        <h4 class="panel__subtitle">{{ t('settings.stack') }}</h4>
        <div class="tags">
          <Tag v-for="item in stack" :key="item" :value="item" severity="secondary" rounded />
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

.row__control {
  width: 190px;
}

.theme-option__icon {
  margin-right: 6px;
}

.details {
  margin: 0;
  padding: 0;
  list-style: none;
}

.details__row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid var(--wf-border);
  font-size: 13px;

  &:last-child {
    border-bottom: none;
  }
}

.details__value {
  font-weight: 550;
}

.tags {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
}
</style>
