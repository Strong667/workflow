<script setup lang="ts">
import AppHeader from '@/components/AppHeader.vue'
import AppSidebar from '@/components/AppSidebar.vue'
import { useUiStore } from '@/stores/ui'

const ui = useUiStore()
</script>

<template>
  <div class="layout" :class="{ 'layout--collapsed': ui.sidebarCollapsed }">
    <AppSidebar class="layout__sidebar" />
    <div class="layout__body">
      <AppHeader class="layout__header" />
      <main class="layout__main">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
  </div>
</template>

<style scoped lang="scss">
.layout {
  display: flex;
  min-height: 100vh;
}

.layout__sidebar {
  width: var(--wf-sidebar-width);
  flex: 0 0 var(--wf-sidebar-width);
  transition: width 0.2s ease, flex-basis 0.2s ease;
}

.layout--collapsed .layout__sidebar {
  width: var(--wf-sidebar-width-collapsed);
  flex-basis: var(--wf-sidebar-width-collapsed);
}

.layout__body {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.layout__main {
  flex: 1;
  min-width: 0;
}

@media (max-width: 768px) {
  .layout__sidebar,
  .layout--collapsed .layout__sidebar {
    position: fixed;
    z-index: 1000;
    height: 100vh;
    width: var(--wf-sidebar-width);
    flex-basis: 0;
    transform: translateX(-100%);
    transition: transform 0.2s ease;
  }

  .layout:not(.layout--collapsed) .layout__sidebar {
    transform: translateX(0);
  }
}
</style>
