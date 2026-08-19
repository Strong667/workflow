<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { SUPPORTED_LOCALES } from '@/locales'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import type { Locale, Role } from '@/types'

interface NavItem {
  name: string
  icon: string
  labelKey: string
  roles?: Role[]
}

const ui = useUiStore()
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const mainNav: NavItem[] = [
  { name: 'dashboard', icon: 'dashboard', labelKey: 'nav.dashboard' },
  { name: 'employees', icon: 'groups', labelKey: 'nav.employees' },
  { name: 'tasks', icon: 'task_alt', labelKey: 'nav.tasks' },
  { name: 'departments', icon: 'apartment', labelKey: 'nav.departments' },
  { name: 'activity', icon: 'history', labelKey: 'nav.activity', roles: ['admin', 'manager'] },
]

const systemNav: NavItem[] = [
  { name: 'settings', icon: 'settings', labelKey: 'nav.settings' },
  { name: 'profile', icon: 'person', labelKey: 'nav.profile' },
]

const visibleMainNav = computed(() => mainNav.filter((item) => !item.roles || auth.can(...item.roles)))
const activeName = computed(() => String(route.name ?? '').split('.')[0])
const pageTitle = computed(() => (route.meta.titleKey ? t(route.meta.titleKey as string) : ''))
</script>

<template>
  <q-layout view="lHh LpR lFf" class="layout">
    <q-drawer
      :model-value="true"
      :mini="ui.sidebarCollapsed"
      :width="248"
      :mini-width="68"
      show-if-above
      bordered
      class="drawer"
    >
      <div class="drawer__brand">
        <div class="drawer__logo">WF</div>
        <span v-if="!ui.sidebarCollapsed" class="drawer__title">WorkFlow</span>
      </div>

      <q-scroll-area class="drawer__scroll">
        <q-list padding>
          <q-item-label v-if="!ui.sidebarCollapsed" header class="drawer__group">
            {{ t('nav.main') }}
          </q-item-label>

          <q-item
            v-for="item in visibleMainNav"
            :key="item.name"
            v-ripple
            clickable
            :active="activeName === item.name"
            active-class="drawer__item--active"
            class="drawer__item"
            @click="router.push({ name: item.name })"
          >
            <q-item-section avatar>
              <q-icon :name="item.icon" size="21px" />
              <q-tooltip v-if="ui.sidebarCollapsed" anchor="center right" self="center left">
                {{ t(item.labelKey) }}
              </q-tooltip>
            </q-item-section>
            <q-item-section>{{ t(item.labelKey) }}</q-item-section>
          </q-item>

          <q-item-label v-if="!ui.sidebarCollapsed" header class="drawer__group">
            {{ t('nav.system') }}
          </q-item-label>

          <q-item
            v-for="item in systemNav"
            :key="item.name"
            v-ripple
            clickable
            :active="activeName === item.name"
            active-class="drawer__item--active"
            class="drawer__item"
            @click="router.push({ name: item.name })"
          >
            <q-item-section avatar>
              <q-icon :name="item.icon" size="21px" />
              <q-tooltip v-if="ui.sidebarCollapsed" anchor="center right" self="center left">
                {{ t(item.labelKey) }}
              </q-tooltip>
            </q-item-section>
            <q-item-section>{{ t(item.labelKey) }}</q-item-section>
          </q-item>
        </q-list>
      </q-scroll-area>

      <div class="drawer__footer">
        <q-item v-ripple clickable class="drawer__item" @click="auth.logout()">
          <q-item-section avatar><q-icon name="logout" size="21px" /></q-item-section>
          <q-item-section>{{ t('nav.logout') }}</q-item-section>
        </q-item>
      </div>
    </q-drawer>

    <q-header bordered class="header">
      <q-toolbar class="header__toolbar">
        <q-btn flat round dense icon="menu" :aria-label="t('settings.sidebar')" @click="ui.toggleSidebar()" />
        <q-toolbar-title class="header__title">{{ pageTitle }}</q-toolbar-title>

        <q-select
          :model-value="ui.locale"
          :options="SUPPORTED_LOCALES"
          option-label="label"
          option-value="value"
          emit-value
          map-options
          dense
          outlined
          class="header__locale gt-xs"
          @update:model-value="(value: Locale) => ui.applyLocale(value)"
        />

        <q-btn
          flat
          round
          dense
          :icon="ui.theme === 'light' ? 'dark_mode' : 'light_mode'"
          :aria-label="t('settings.theme')"
          @click="ui.toggleTheme()"
        />

        <q-btn flat dense no-caps class="header__user">
          <q-avatar size="32px" color="primary" text-color="white">
            <img v-if="auth.user?.avatar" :src="auth.user.avatar" alt="" />
            <template v-else>{{ auth.initials }}</template>
          </q-avatar>
          <div class="header__user-info gt-xs">
            <span class="header__user-name">{{ auth.user?.name }}</span>
            <span class="header__user-role">{{ auth.role ? t(`roles.${auth.role}`) : '' }}</span>
          </div>
          <q-icon name="expand_more" size="18px" />

          <q-menu>
            <q-list style="min-width: 180px">
              <q-item v-close-popup clickable @click="router.push({ name: 'profile' })">
                <q-item-section avatar><q-icon name="person" /></q-item-section>
                <q-item-section>{{ t('nav.profile') }}</q-item-section>
              </q-item>
              <q-item v-close-popup clickable @click="router.push({ name: 'settings' })">
                <q-item-section avatar><q-icon name="settings" /></q-item-section>
                <q-item-section>{{ t('nav.settings') }}</q-item-section>
              </q-item>
              <q-separator />
              <q-item v-close-popup clickable @click="auth.logout()">
                <q-item-section avatar><q-icon name="logout" /></q-item-section>
                <q-item-section>{{ t('nav.logout') }}</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
      </q-toolbar>
    </q-header>

    <q-page-container>
      <q-page>
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<style scoped lang="scss">
.drawer {
  background: var(--wf-surface);
  display: flex;
  flex-direction: column;
}

.drawer__brand {
  display: flex;
  align-items: center;
  gap: 10px;
  height: var(--wf-header-height);
  padding: 0 18px;
  border-bottom: 1px solid var(--wf-border);
  flex: 0 0 auto;
}

.drawer__logo {
  width: 32px;
  height: 32px;
  border-radius: 9px;
  display: grid;
  place-items: center;
  background: var(--q-primary);
  color: #fff;
  font-weight: 700;
  font-size: 13px;
  flex: 0 0 auto;
}

.drawer__title {
  font-weight: 650;
  font-size: 16px;
  white-space: nowrap;
}

.drawer__scroll {
  height: calc(100% - var(--wf-header-height) - 58px);
}

.drawer__group {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--wf-text-muted);
  padding-bottom: 4px;
}

.drawer__item {
  border-radius: 9px;
  margin: 2px 8px;
  min-height: 42px;
  color: var(--wf-text);

  &--active {
    background: rgba(79, 70, 229, 0.12);
    color: var(--q-primary);
    font-weight: 600;
  }
}

.drawer__footer {
  border-top: 1px solid var(--wf-border);
  padding: 6px 0;
}

.header {
  background: var(--wf-surface);
  color: var(--wf-text);
}

.header__toolbar {
  height: var(--wf-header-height);
  padding: 0 14px;
  gap: 6px;
}

.header__title {
  font-size: 16px;
  font-weight: 600;
}

.header__locale {
  width: 132px;
}

.header__user {
  padding: 2px 8px;
  gap: 9px;
}

.header__user-info {
  display: flex;
  flex-direction: column;
  line-height: 1.15;
  text-align: left;
  margin-left: 9px;
}

.header__user-name {
  font-size: 13px;
  font-weight: 600;
}

.header__user-role {
  font-size: 11px;
  color: var(--wf-text-muted);
}
</style>
