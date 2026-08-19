<script setup lang="ts">
withDefaults(
  defineProps<{
    label: string
    value: number | string
    icon: string
    tone?: 'primary' | 'success' | 'warn' | 'danger'
    loading?: boolean
  }>(),
  { tone: 'primary', loading: false },
)
</script>

<template>
  <div class="stat wf-card">
    <div class="stat__icon" :class="`stat__icon--${tone}`">
      <i :class="icon" />
    </div>
    <div class="stat__body">
      <span class="stat__label">{{ label }}</span>
      <Skeleton v-if="loading" width="4rem" height="1.6rem" />
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
  font-size: 18px;

  &--primary {
    background: color-mix(in srgb, var(--p-primary-color) 14%, transparent);
    color: var(--p-primary-color);
  }

  &--success {
    background: color-mix(in srgb, var(--p-green-500) 14%, transparent);
    color: var(--p-green-500);
  }

  &--warn {
    background: color-mix(in srgb, var(--p-amber-500) 16%, transparent);
    color: var(--p-amber-500);
  }

  &--danger {
    background: color-mix(in srgb, var(--p-red-500) 14%, transparent);
    color: var(--p-red-500);
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
</style>
