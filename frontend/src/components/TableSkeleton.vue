<script setup lang="ts">
withDefaults(defineProps<{ rows?: number; columns?: number }>(), { rows: 6, columns: 5 })
</script>

<template>
  <div class="skeleton">
    <div v-for="row in rows" :key="row" class="skeleton__row">
      <span v-for="column in columns" :key="column" class="skeleton__cell" :style="{ animationDelay: `${row * 60}ms` }" />
    </div>
  </div>
</template>

<style scoped lang="scss">
.skeleton {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 12px 0;
}

.skeleton__row {
  display: flex;
  gap: 16px;
}

.skeleton__cell {
  flex: 1;
  height: 14px;
  border-radius: 6px;
  background: linear-gradient(
    90deg,
    var(--el-fill-color) 25%,
    var(--el-fill-color-light) 37%,
    var(--el-fill-color) 63%
  );
  background-size: 400% 100%;
  animation: shimmer 1.3s ease infinite;
}

@keyframes shimmer {
  0% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0 50%;
  }
}
</style>
