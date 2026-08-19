<script setup lang="ts">
withDefaults(defineProps<{ rows?: number; columns?: number }>(), { rows: 6, columns: 5 })
</script>

<template>
  <div class="skeleton">
    <div v-for="row in rows" :key="row" class="skeleton__row">
      <q-skeleton v-for="column in columns" :key="column" type="text" class="skeleton__cell" />
    </div>
  </div>
</template>

<style scoped lang="scss">
.skeleton {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 14px 0;
}

.skeleton__row {
  display: flex;
  gap: 16px;
}

.skeleton__cell {
  flex: 1;
}
</style>
