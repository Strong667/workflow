<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import type { MenuItem } from '@/ui/lazy-components'
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
const search = ref('')

/** Хлебные крошки: раздел меню и название текущего экрана. */
const breadcrumb = computed(() => {
  const name = String(route.name ?? '')
  const section = ['settings', 'profile'].some((item) => name.startsWith(item)) ? 'nav.system' : 'nav.main'
  const title = route.meta.titleKey ? t(route.meta.titleKey as string) : ''
  return { section: t(section), title }
})

const userMenu = computed<MenuItem[]>(() => [
  { label: t('nav.profile'), icon: 'pi pi-user', command: () => router.push({ name: 'profile' }) },
  { label: t('nav.settings'), icon: 'pi pi-cog', command: () => router.push({ name: 'settings' }) },
  { separator: true },
  { label: t('nav.logout'), icon: 'pi pi-sign-out', command: () => auth.logout() },
])

const roleCode = computed(() => (auth.role ?? '').toUpperCase())

/** Языков три — переключаем по кругу, не занимая место селектом. */
function cycleLocale(): void {
  const codes = SUPPORTED_LOCALES.map((item) => item.value)
  const next = codes[(codes.indexOf(ui.locale) + 1) % codes.length]
  void ui.applyLocale(next as Locale)
}
</script>

<template>
  <header class="header">
    <div class="header__left">
      <Button
        icon="pi pi-bars"
        severity="secondary"
        text
        rounded
        class="header__burger"
        :aria-label="t('settings.sidebar')"
        @click="ui.toggleSidebar()"
      />
      <nav class="header__crumbs">
        <span class="header__crumb-muted">{{ breadcrumb.section }}</span>
        <span class="header__crumb-sep">/</span>
        <span class="header__crumb">{{ breadcrumb.title }}</span>
      </nav>
    </div>

    <div class="header__right">
      <label class="header__search">
        <i class="pi pi-search" />
        <input v-model="search" type="search" :placeholder="t('common.searchAll')" />
        <span class="wf-mono header__hotkey">⌘K</span>
      </label>

      <button type="button" class="header__icon-btn header__lang wf-mono" @click="cycleLocale">
        {{ ui.locale.toUpperCase() }}
      </button>

      <button
        type="button"
        class="header__icon-btn"
        :aria-label="t('settings.theme')"
        @click="ui.toggleTheme()"
      >
        <i class="pi" :class="ui.theme === 'light' ? 'pi-moon' : 'pi-sun'" />
      </button>

      <button class="header__user" type="button" @click="menu.toggle($event)">
        <Avatar
          :image="auth.user?.avatar ?? undefined"
          :label="auth.user?.avatar ? undefined : auth.initials"
          shape="circle"
          class="header__avatar"
        />
        <span class="header__user-info">
          <span class="header__user-name">{{ auth.user?.name }}</span>
          <span class="wf-mono header__user-role">{{ roleCode }}</span>
        </span>
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
  gap: 14px;
  padding: 0 14px;
  background: var(--wf-surface);
  border-bottom: 1px solid var(--wf-line);
  position: sticky;
  top: 0;
  z-index: 10;
}

.header__left {
  display: flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
}

.header__burger {
  display: none;
}

.header__crumbs {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 12.5px;
  min-width: 0;
}

.header__crumb-muted,
.header__crumb-sep {
  color: var(--wf-ink-3);
}

.header__crumb {
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

.header__search {
  display: flex;
  align-items: center;
  gap: 7px;
  height: 30px;
  width: 300px;
  padding: 0 9px;
  border: 1px solid var(--wf-line);
  border-radius: var(--wf-radius-sm);
  background: var(--wf-surface-2);
  color: var(--wf-ink-3);

  i {
    font-size: 12px;
  }

  input {
    flex: 1;
    min-width: 0;
    border: 0;
    outline: none;
    background: transparent;
    font: inherit;
    font-size: 12px;
    color: var(--wf-ink);

    &::placeholder {
      color: var(--wf-ink-3);
    }

    &::-webkit-search-cancel-button {
      display: none;
    }
  }
}

.header__hotkey {
  font-size: 10px;
  color: var(--wf-ink-3);
  border: 1px solid var(--wf-line);
  border-radius: 4px;
  padding: 1px 4px;
}

.header__lang {
  width: auto;
  min-width: 34px;
  padding: 0 8px;
  font-size: 10.5px;
  letter-spacing: 0.06em;
}

.header__icon-btn {
  width: 30px;
  height: 30px;
  display: grid;
  place-items: center;
  border: 1px solid var(--wf-line);
  border-radius: var(--wf-radius-sm);
  background: var(--wf-surface);
  color: var(--wf-ink-2);
  cursor: pointer;

  &:hover {
    background: var(--wf-surface-3);
    color: var(--wf-ink);
  }

  i {
    font-size: 13px;
  }
}

.header__user {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 3px 8px 3px 3px;
  border: 1px solid var(--wf-line);
  border-radius: 999px;
  background: var(--wf-surface);
  color: inherit;
  font: inherit;
  cursor: pointer;

  &:hover {
    background: var(--wf-surface-3);
  }
}

.header__avatar {
  width: 24px;
  height: 24px;
  font-size: 10px;
}

.header__user-info {
  display: flex;
  flex-direction: column;
  line-height: 1.15;
  text-align: left;
}

.header__user-name {
  font-size: 12px;
  font-weight: 600;
}

.header__user-role {
  font-size: 9.5px;
  letter-spacing: 0.06em;
  color: var(--wf-ink-3);
}

@media (max-width: 900px) {
  .header__search {
    display: none;
  }

  .header__burger {
    display: inline-flex;
  }
}

@media (max-width: 640px) {
  .header__user-info {
    display: none;
  }
}
</style>
