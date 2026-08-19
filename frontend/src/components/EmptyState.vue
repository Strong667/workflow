<script setup lang="ts">
withDefaults(defineProps<{ text: string; icon?: string }>(), { icon: 'inbox' })
</script>

<template>
  <div class="empty">
    <q-icon :name="icon" size="30px" class="empty__icon" />
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
