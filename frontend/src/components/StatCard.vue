<script setup lang="ts">
withDefaults(
  defineProps<{
    label: string
    value: number | string
    icon: string
    tone?: 'primary' | 'positive' | 'warning' | 'negative'
    loading?: boolean
  }>(),
  { tone: 'primary', loading: false },
)
</script>

<template>
  <div class="stat wf-card">
    <div class="stat__icon" :class="`stat__icon--${tone}`">
      <q-icon :name="icon" size="22px" />
    </div>
    <div class="stat__body">
      <span class="stat__label">{{ label }}</span>
      <q-skeleton v-if="loading" type="text" width="60px" height="28px" />
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
    background: rgba(79, 70, 229, 0.14);
    color: var(--q-primary);
  }

  &--positive {
    background: rgba(34, 197, 94, 0.14);
    color: var(--q-positive);
  }

  &--warning {
    background: rgba(245, 158, 11, 0.16);
    color: var(--q-warning);
  }

  &--negative {
    background: rgba(239, 68, 68, 0.14);
    color: var(--q-negative);
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
