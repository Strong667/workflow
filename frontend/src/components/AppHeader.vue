<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { SUPPORTED_LOCALES } from '@/locales'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import type { Locale } from '@/types'

const ui = useUiStore()
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const pageTitle = computed(() => (route.meta.titleKey ? t(route.meta.titleKey as string) : ''))

function onCommand(command: string): void {
  if (command === 'logout') {
    void auth.logout()
    return
  }
  void router.push({ name: command })
}
</script>

<template>
  <header class="header">
    <div class="header__left">
      <el-button text circle :title="t('settings.sidebar')" @click="ui.toggleSidebar()">
        <el-icon :size="18"><Expand v-if="ui.sidebarCollapsed" /><Fold v-else /></el-icon>
      </el-button>
      <h2 class="header__title">{{ pageTitle }}</h2>
    </div>

    <div class="header__right">
      <el-select
        :model-value="ui.locale"
        size="small"
        class="header__locale"
        @update:model-value="(value: Locale) => ui.applyLocale(value)"
      >
        <el-option v-for="item in SUPPORTED_LOCALES" :key="item.value" :label="item.label" :value="item.value" />
      </el-select>

      <el-button text circle :title="t('settings.theme')" @click="ui.toggleTheme()">
        <el-icon :size="18"><Moon v-if="ui.theme === 'light'" /><Sunny v-else /></el-icon>
      </el-button>

      <el-dropdown trigger="click" @command="onCommand">
        <div class="header__user">
          <el-avatar :size="32" :src="auth.user?.avatar ?? undefined">{{ auth.initials }}</el-avatar>
          <div class="header__user-info">
            <span class="header__user-name">{{ auth.user?.name }}</span>
            <span class="header__user-role">{{ auth.role ? t(`roles.${auth.role}`) : '' }}</span>
          </div>
          <el-icon class="wf-muted"><ArrowDown /></el-icon>
        </div>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item command="profile">
              <el-icon><User /></el-icon>{{ t('nav.profile') }}
            </el-dropdown-item>
            <el-dropdown-item command="settings">
              <el-icon><Setting /></el-icon>{{ t('nav.settings') }}
            </el-dropdown-item>
            <el-dropdown-item command="logout" divided>
              <el-icon><SwitchButton /></el-icon>{{ t('nav.logout') }}
            </el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
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
  width: 112px;
}

.header__user {
  display: flex;
  align-items: center;
  gap: 9px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 10px;
  outline: none;

  &:hover {
    background: var(--el-fill-color-light);
  }
}

.header__user-info {
  display: flex;
  flex-direction: column;
  line-height: 1.15;
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
