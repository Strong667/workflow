<script setup lang="ts">
withDefaults(defineProps<{ text: string; icon?: string }>(), { icon: 'pi pi-inbox' })
</script>

<template>
  <div class="empty">
    <i :class="icon" class="empty__icon" />
    <p class="empty__text">{{ text }}</p>
    <slot />
  </div>
</template>

<style scoped lang="scss">
.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 36px 16px;
  color: var(--wf-text-muted);
}

.empty__icon {
  font-size: 26px;
  opacity: 0.55;
}

.empty__text {
  margin: 0;
  font-size: 13px;
}
</style>
