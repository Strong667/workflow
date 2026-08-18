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
  { name: 'dashboard', icon: 'Odometer', labelKey: 'nav.dashboard' },
  { name: 'employees', icon: 'UserFilled', labelKey: 'nav.employees' },
  { name: 'tasks', icon: 'Files', labelKey: 'nav.tasks' },
  { name: 'departments', icon: 'OfficeBuilding', labelKey: 'nav.departments' },
  { name: 'activity', icon: 'Clock', labelKey: 'nav.activity', roles: ['admin', 'manager'] },
]

const systemNav: NavItem[] = [
  { name: 'settings', icon: 'Setting', labelKey: 'nav.settings' },
  { name: 'profile', icon: 'User', labelKey: 'nav.profile' },
]

const visibleMainNav = computed(() => mainNav.filter((item) => !item.roles || auth.can(...item.roles)))

/** На мобильном сайдбар перекрывает контент — после перехода закрываем его. */
function closeOnMobile(): void {
  if (window.innerWidth < 768 && !ui.sidebarCollapsed) {
    ui.toggleSidebar()
  }
}

const activeName = computed(() => {
  const name = String(route.name ?? '')
  return name.split('.')[0]
})
</script>

<template>
  <aside class="sidebar">
    <div class="sidebar__brand">
      <div class="sidebar__logo">WF</div>
      <transition name="fade">
        <span v-if="!ui.sidebarCollapsed" class="sidebar__title">WorkFlow</span>
      </transition>
    </div>

    <nav class="sidebar__nav">
      <p v-if="!ui.sidebarCollapsed" class="sidebar__group">{{ t('nav.main') }}</p>
      <router-link
        v-for="item in visibleMainNav"
        :key="item.name"
        :to="{ name: item.name }"
        class="sidebar__link"
        :class="{ 'sidebar__link--active': activeName === item.name }"
        @click="closeOnMobile"
      >
        <el-icon class="sidebar__icon"><component :is="item.icon" /></el-icon>
        <span v-if="!ui.sidebarCollapsed">{{ t(item.labelKey) }}</span>
      </router-link>

      <p v-if="!ui.sidebarCollapsed" class="sidebar__group">{{ t('nav.system') }}</p>
      <router-link
        v-for="item in systemNav"
        :key="item.name"
        :to="{ name: item.name }"
        class="sidebar__link"
        :class="{ 'sidebar__link--active': activeName === item.name }"
        @click="closeOnMobile"
      >
        <el-icon class="sidebar__icon"><component :is="item.icon" /></el-icon>
        <span v-if="!ui.sidebarCollapsed">{{ t(item.labelKey) }}</span>
      </router-link>
    </nav>

    <div class="sidebar__footer">
      <el-button text class="sidebar__logout" @click="auth.logout()">
        <el-icon><SwitchButton /></el-icon>
        <span v-if="!ui.sidebarCollapsed">{{ t('nav.logout') }}</span>
      </el-button>
    </div>
  </aside>
</template>

<style scoped lang="scss">
.sidebar {
  display: flex;
  flex-direction: column;
  background: var(--wf-surface);
  border-right: 1px solid var(--wf-border);
  height: 100vh;
  position: sticky;
  top: 0;
  overflow: hidden;
}

.sidebar__brand {
  display: flex;
  align-items: center;
  gap: 10px;
  height: var(--wf-header-height);
  padding: 0 18px;
  border-bottom: 1px solid var(--wf-border);
  flex: 0 0 auto;
}

.sidebar__logo {
  width: 32px;
  height: 32px;
  border-radius: 9px;
  display: grid;
  place-items: center;
  background: var(--el-color-primary);
  color: #fff;
  font-weight: 700;
  font-size: 13px;
  flex: 0 0 auto;
}

.sidebar__title {
  font-weight: 650;
  font-size: 16px;
  white-space: nowrap;
}

.sidebar__nav {
  flex: 1;
  overflow-y: auto;
  padding: 12px 10px;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.sidebar__group {
  margin: 14px 10px 6px;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--wf-text-muted);
}

.sidebar__link {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 9px 11px;
  border-radius: 9px;
  font-size: 14px;
  color: var(--el-text-color-regular);
  white-space: nowrap;
  transition: background 0.15s ease, color 0.15s ease;

  &:hover {
    background: var(--el-fill-color-light);
  }

  &--active {
    background: var(--el-color-primary-light-9);
    color: var(--el-color-primary);
    font-weight: 600;
  }
}

html.dark .sidebar__link--active {
  background: rgba(79, 70, 229, 0.18);
}

.sidebar__icon {
  font-size: 17px;
  flex: 0 0 auto;
}

.sidebar__footer {
  padding: 10px;
  border-top: 1px solid var(--wf-border);
}

.sidebar__logout {
  width: 100%;
  justify-content: flex-start;
  gap: 11px;
  padding: 9px 11px;
}
</style>
