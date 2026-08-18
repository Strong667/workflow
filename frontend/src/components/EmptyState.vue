<script setup lang="ts">
withDefaults(defineProps<{ text: string; icon?: string }>(), { icon: 'Box' })
</script>

<template>
  <div class="empty">
    <el-icon :size="30" class="empty__icon"><component :is="icon" /></el-icon>
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
  opacity: 0.5;
}

.empty__text {
  margin: 0;
  font-size: 13px;
}
</style>
