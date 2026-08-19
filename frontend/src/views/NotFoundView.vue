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
    <div class="error-page__code error-page__code--404">404</div>
    <h1 class="error-page__title">{{ t('errors.notFound') }}</h1>
    <p class="error-page__text wf-muted">{{ t('errors.notFoundText') }}</p>
    <div class="error-page__actions">
      <q-btn flat no-caps :label="t('errors.goBack')" @click="router.back()" />
      <q-btn
        color="primary"
        unelevated
        no-caps
        :label="auth.isAuthenticated ? t('errors.goHome') : t('auth.signIn')"
        @click="router.push({ name: auth.isAuthenticated ? 'dashboard' : 'login' })"
      />
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

  &--404 {
    background: linear-gradient(120deg, #4f46e5, #7c3aed);
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
