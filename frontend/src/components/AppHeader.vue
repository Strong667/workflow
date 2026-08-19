<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import type { MenuItem } from 'primevue/menuitem'
import { SUPPORTED_LOCALES } from '@/locales'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import type { Locale } from '@/types'

const ui = useUiStore()
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const menu = ref()
const pageTitle = computed(() => (route.meta.titleKey ? t(route.meta.titleKey as string) : ''))

const userMenu = computed<MenuItem[]>(() => [
  { label: t('nav.profile'), icon: 'pi pi-user', command: () => router.push({ name: 'profile' }) },
  { label: t('nav.settings'), icon: 'pi pi-cog', command: () => router.push({ name: 'settings' }) },
  { separator: true },
  { label: t('nav.logout'), icon: 'pi pi-sign-out', command: () => auth.logout() },
])
</script>

<template>
  <header class="header">
    <div class="header__left">
      <Button
        :icon="ui.sidebarCollapsed ? 'pi pi-bars' : 'pi pi-align-left'"
        severity="secondary"
        text
        rounded
        :aria-label="t('settings.sidebar')"
        @click="ui.toggleSidebar()"
      />
      <h2 class="header__title">{{ pageTitle }}</h2>
    </div>

    <div class="header__right">
      <Select
        :model-value="ui.locale"
        :options="SUPPORTED_LOCALES"
        option-label="label"
        option-value="value"
        size="small"
        class="header__locale"
        @update:model-value="(value: Locale) => ui.applyLocale(value)"
      />

      <Button
        :icon="ui.theme === 'light' ? 'pi pi-moon' : 'pi pi-sun'"
        severity="secondary"
        text
        rounded
        :aria-label="t('settings.theme')"
        @click="ui.toggleTheme()"
      />

      <button class="header__user" type="button" @click="menu.toggle($event)">
        <Avatar
          :image="auth.user?.avatar ?? undefined"
          :label="auth.user?.avatar ? undefined : auth.initials"
          shape="circle"
          size="normal"
        />
        <span class="header__user-info">
          <span class="header__user-name">{{ auth.user?.name }}</span>
          <span class="header__user-role">{{ auth.role ? t(`roles.${auth.role}`) : '' }}</span>
        </span>
        <i class="pi pi-angle-down wf-muted" />
      </button>
      <Menu ref="menu" :model="userMenu" :popup="true" />
    </div>
  </header>
</template>

<style scoped lang="scss">
.header {
  height: var(--wf-header-height);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 0 20px;
  background: var(--wf-surface);
  border-bottom: 1px solid var(--wf-border);
  position: sticky;
  top: 0;
  z-index: 10;
}

.header__left {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.header__title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.header__right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.header__locale {
  width: 130px;
}

.header__user {
  display: flex;
  align-items: center;
  gap: 9px;
  cursor: pointer;
  padding: 4px 8px;
  border: none;
  border-radius: 10px;
  background: transparent;
  color: inherit;
  font: inherit;

  &:hover {
    background: var(--p-content-hover-background);
  }
}

.header__user-info {
  display: flex;
  flex-direction: column;
  line-height: 1.15;
  text-align: left;
}

.header__user-name {
  font-size: 13px;
  font-weight: 600;
}

.header__user-role {
  font-size: 11px;
  color: var(--wf-text-muted);
}

@media (max-width: 768px) {
  .header__user-info,
  .header__locale {
    display: none;
  }
}
</style>
