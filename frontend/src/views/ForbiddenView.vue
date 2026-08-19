<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()
</script>

<template>
  <div class="error-page">
    <div class="error-page__code error-page__code--403">403</div>
    <h1 class="error-page__title">{{ t('errors.forbidden') }}</h1>
    <p class="error-page__text wf-muted">{{ t('errors.forbiddenText') }}</p>
    <Tag v-if="auth.role" :value="t(`roles.${auth.role}`)" severity="secondary" rounded />
    <div class="error-page__actions">
      <Button :label="t('errors.goBack')" severity="secondary" outlined @click="router.back()" />
      <Button :label="t('errors.goHome')" @click="router.push({ name: 'dashboard' })" />
    </div>
  </div>
</template>

<style scoped lang="scss">
.error-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 24px;
  text-align: center;
}

.error-page__code {
  font-size: 96px;
  font-weight: 800;
  line-height: 1;
  letter-spacing: -3px;
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;

  &--403 {
    background: linear-gradient(120deg, var(--p-red-500), var(--p-orange-500));
  }
}

.error-page__title {
  margin: 8px 0 0;
  font-size: 22px;
  font-weight: 650;
}

.error-page__text {
  margin: 0;
  max-width: 420px;
  font-size: 13.5px;
}

.error-page__actions {
  display: flex;
  gap: 10px;
  margin-top: 14px;
}
</style>
