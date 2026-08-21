<script setup lang="ts">
withDefaults(
  defineProps<{
    label: string
    value: number | string
    hint?: string
    tone?: 'default' | 'danger'
    loading?: boolean
  }>(),
  { hint: '', tone: 'default', loading: false },
)
</script>

<template>
  <div class="stat wf-card" :class="{ 'stat--danger': tone === 'danger' }">
    <span class="wf-eyebrow stat__label">{{ label }}</span>
    <div class="stat__row">
      <Skeleton v-if="loading" width="3rem" height="1.9rem" />
      <span v-else class="wf-mono stat__value">{{ value }}</span>
      <span v-if="hint && !loading" class="stat__hint">{{ hint }}</span>
    </div>
  </div>
</template>

<style scoped lang="scss">
.stat {
  padding: 13px 15px 14px;
  display: flex;
  flex-direction: column;
  gap: 7px;
  position: relative;
  overflow: hidden;

  /* Просроченное подсвечиваем полосой слева, а не заливкой */
  &--danger {
    border-color: color-mix(in srgb, var(--wf-danger) 35%, var(--wf-line));

    &::before {
      content: '';
      position: absolute;
      inset: 0 auto 0 0;
      width: 3px;
      background: var(--wf-danger);
    }

    .stat__label,
    .stat__value {
      color: var(--wf-danger);
    }
  }
}

.stat__row {
  display: flex;
  align-items: baseline;
  gap: 9px;
  min-height: 30px;
}

.stat__value {
  font-size: 26px;
  font-weight: 500;
  line-height: 1;
  letter-spacing: -0.02em;
}

.stat__hint {
  font-size: 11.5px;
  color: var(--wf-ink-3);
}
</style>
