<script setup lang="ts">
withDefaults(
  defineProps<{
    label: string
    value: number | string
    icon: string
    tone?: 'primary' | 'success' | 'warning' | 'danger'
    loading?: boolean
  }>(),
  { tone: 'primary', loading: false },
)
</script>

<template>
  <div class="stat wf-card">
    <div class="stat__icon" :class="`stat__icon--${tone}`">
      <el-icon :size="20"><component :is="icon" /></el-icon>
    </div>
    <div class="stat__body">
      <span class="stat__label">{{ label }}</span>
      <el-skeleton v-if="loading" :rows="1" animated class="stat__skeleton" />
      <span v-else class="stat__value">{{ value }}</span>
    </div>
  </div>
</template>

<style scoped lang="scss">
.stat {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px;
}

.stat__icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  flex: 0 0 auto;

  &--primary {
    background: var(--el-color-primary-light-9);
    color: var(--el-color-primary);
  }

  &--success {
    background: var(--el-color-success-light-9);
    color: var(--el-color-success);
  }

  &--warning {
    background: var(--el-color-warning-light-9);
    color: var(--el-color-warning);
  }

  &--danger {
    background: var(--el-color-danger-light-9);
    color: var(--el-color-danger);
  }
}

.stat__body {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.stat__label {
  font-size: 12px;
  color: var(--wf-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.stat__value {
  font-size: 24px;
  font-weight: 680;
  line-height: 1.1;
}

.stat__skeleton {
  width: 60px;
}
</style>
