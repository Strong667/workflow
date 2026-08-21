<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import type { Role } from '@/types'

interface NavItem {
  name: string
  icon: string
  labelKey: string
  roles?: Role[]
}

const ui = useUiStore()
const auth = useAuthStore()
const route = useRoute()
const { t } = useI18n()

const mainNav: NavItem[] = [
  { name: 'dashboard', icon: 'pi pi-clock', labelKey: 'nav.dashboard' },
  { name: 'employees', icon: 'pi pi-users', labelKey: 'nav.employees' },
  { name: 'tasks', icon: 'pi pi-check-square', labelKey: 'nav.tasks' },
  { name: 'departments', icon: 'pi pi-building', labelKey: 'nav.departments' },
  { name: 'activity', icon: 'pi pi-history', labelKey: 'nav.activityShort', roles: ['admin', 'manager'] },
]

const systemNav: NavItem[] = [
  { name: 'settings', icon: 'pi pi-cog', labelKey: 'nav.settings' },
  { name: 'profile', icon: 'pi pi-user', labelKey: 'nav.profile' },
]

const visibleMainNav = computed(() => mainNav.filter((item) => !item.roles || auth.can(...item.roles)))
const activeName = computed(() => String(route.name ?? '').split('.')[0])
const expanded = computed(() => !ui.sidebarCollapsed)

/** На мобильном сайдбар перекрывает контент — после перехода закрываем его. */
function closeOnMobile(): void {
  if (window.innerWidth < 768 && !ui.sidebarCollapsed) {
    ui.toggleSidebar()
  }
}
</script>

<template>
  <aside class="sidebar">
    <div class="sidebar__brand">
      <div class="sidebar__logo">WF</div>
      <span v-if="expanded" class="sidebar__title">WorkFlow</span>
    </div>

    <nav class="sidebar__nav">
      <p v-if="expanded" class="wf-eyebrow sidebar__group">{{ t('nav.main') }}</p>
      <router-link
        v-for="item in visibleMainNav"
        :key="item.name"
        v-tooltip.right="!expanded ? t(item.labelKey) : undefined"
        :to="{ name: item.name }"
        class="sidebar__link"
        :class="{ 'sidebar__link--active': activeName === item.name }"
        @click="closeOnMobile"
      >
        <i :class="item.icon" class="sidebar__icon" />
        <span v-if="expanded">{{ t(item.labelKey) }}</span>
      </router-link>

      <p v-if="expanded" class="wf-eyebrow sidebar__group">{{ t('nav.system') }}</p>
      <router-link
        v-for="item in systemNav"
        :key="item.name"
        v-tooltip.right="!expanded ? t(item.labelKey) : undefined"
        :to="{ name: item.name }"
        class="sidebar__link"
        :class="{ 'sidebar__link--active': activeName === item.name }"
        @click="closeOnMobile"
      >
        <i :class="item.icon" class="sidebar__icon" />
        <span v-if="expanded">{{ t(item.labelKey) }}</span>
      </router-link>
    </nav>

    <div class="sidebar__footer">
      <button type="button" class="sidebar__action" @click="ui.toggleSidebar()">
        <i class="pi" :class="expanded ? 'pi-chevron-left' : 'pi-chevron-right'" />
        <span v-if="expanded">{{ t('nav.collapse') }}</span>
      </button>
      <button type="button" class="sidebar__action" @click="auth.logout()">
        <i class="pi pi-sign-out" />
        <span v-if="expanded">{{ t('nav.logout') }}</span>
      </button>
    </div>
  </aside>
</template>

<style scoped lang="scss">
.sidebar {
  display: flex;
  flex-direction: column;
  background: var(--wf-surface);
  border-right: 1px solid var(--wf-line);
  height: 100vh;
  position: sticky;
  top: 0;
  overflow: hidden;
}

.sidebar__brand {
  display: flex;
  align-items: center;
  gap: 9px;
  height: var(--wf-header-height);
  padding: 0 14px;
  border-bottom: 1px solid var(--wf-line-2);
  flex: 0 0 auto;
}

.sidebar__logo {
  width: 26px;
  height: 26px;
  border-radius: 7px;
  display: grid;
  place-items: center;
  background: var(--wf-ink);
  color: #f7f5f0;
  font-family: var(--wf-mono);
  font-weight: 700;
  font-size: 10px;
  flex: 0 0 auto;
}

html.dark .sidebar__logo {
  background: var(--wf-accent);
  color: #15171a;
}

.sidebar__title {
  font-weight: 600;
  font-size: 14px;
  white-space: nowrap;
  letter-spacing: -0.01em;
}

.sidebar__nav {
  flex: 1;
  overflow-y: auto;
  padding: 10px 8px;
  display: flex;
  flex-direction: column;
  gap: 1px;
}

.sidebar__group {
  margin: 8px 8px 5px;
}

.sidebar__link {
  display: flex;
  align-items: center;
  gap: 10px;
  height: 32px;
  padding: 0 8px;
  border-radius: 7px;
  font-size: 12.5px;
  color: var(--wf-ink-2);
  white-space: nowrap;
  transition: background 0.14s ease, color 0.14s ease;

  &:hover {
    background: var(--wf-surface-3);
    color: var(--wf-ink);
  }

  &--active {
    background: var(--wf-tint);
    color: var(--wf-ink);
    font-weight: 600;
  }
}

.sidebar__icon {
  font-size: 14px;
  width: 16px;
  text-align: center;
  flex: 0 0 auto;
}

.sidebar__footer {
  padding: 8px;
  border-top: 1px solid var(--wf-line-2);
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.sidebar__action {
  display: flex;
  align-items: center;
  gap: 10px;
  height: 30px;
  padding: 0 8px;
  border: none;
  border-radius: 7px;
  background: transparent;
  color: var(--wf-ink-3);
  font: inherit;
  font-size: 12px;
  cursor: pointer;
  white-space: nowrap;

  &:hover {
    background: var(--wf-surface-3);
    color: var(--wf-ink);
  }

  i {
    font-size: 13px;
    width: 16px;
    text-align: center;
  }
}
</style>
