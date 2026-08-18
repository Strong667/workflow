<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import { SUPPORTED_LOCALES } from '@/locales'
import { apiMessage } from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import type { Locale } from '@/types'

const { t } = useI18n()
const auth = useAuthStore()
const ui = useUiStore()
const router = useRouter()
const route = useRoute()

const formRef = ref<FormInstance>()
const form = reactive({ email: '', password: '' })

const rules: FormRules = {
  email: [
    { required: true, message: t('auth.emailRequired'), trigger: 'blur' },
    { type: 'email', message: t('auth.emailInvalid'), trigger: 'blur' },
  ],
  password: [
    { required: true, message: t('auth.passwordRequired'), trigger: 'blur' },
    { min: 6, message: t('auth.passwordMin'), trigger: 'blur' },
  ],
}

const demoAccounts = [
  { email: 'admin@workflow.test', role: 'admin' },
  { email: 'manager@workflow.test', role: 'manager' },
  { email: 'user@workflow.test', role: 'employee' },
]

onMounted(() => {
  if (route.query.expired) {
    ElMessage.warning(t('auth.sessionExpired'))
  }
})

function useDemo(email: string): void {
  form.email = email
  form.password = 'password'
}

async function submit(): Promise<void> {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  try {
    await auth.login(form.email, form.password)
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : { name: 'dashboard' }
    await router.push(redirect as never)
  } catch (error) {
    ElMessage.error(apiMessage(error, t('auth.invalid')))
  }
}
</script>

<template>
  <div class="login">
    <div class="login__aside">
      <div class="login__brand">
        <div class="login__logo">WF</div>
        <span>WorkFlow CRM</span>
      </div>
      <h1 class="login__headline">{{ t('auth.subtitle') }}</h1>
      <ul class="login__features">
        <li><el-icon><Select /></el-icon>{{ t('nav.employees') }}, {{ t('nav.departments') }}</li>
        <li><el-icon><Select /></el-icon>{{ t('tasks.subtitle') }}</li>
        <li><el-icon><Select /></el-icon>{{ t('nav.activity') }}</li>
      </ul>
    </div>

    <div class="login__panel">
      <div class="login__panel-top">
        <el-select
          :model-value="ui.locale"
          size="small"
          style="width: 118px"
          @update:model-value="(value: Locale) => ui.applyLocale(value)"
        >
          <el-option v-for="item in SUPPORTED_LOCALES" :key="item.value" :label="item.label" :value="item.value" />
        </el-select>
        <el-button text circle @click="ui.toggleTheme()">
          <el-icon :size="18"><Moon v-if="ui.theme === 'light'" /><Sunny v-else /></el-icon>
        </el-button>
      </div>

      <div class="login__form-wrap">
        <h2 class="login__title">{{ t('auth.title') }}</h2>
        <p class="wf-muted login__subtitle">{{ t('common.appName') }}</p>

        <el-form ref="formRef" :model="form" :rules="rules" label-position="top" size="large" @submit.prevent="submit">
          <el-form-item :label="t('auth.email')" prop="email">
            <el-input v-model="form.email" autocomplete="username" placeholder="admin@workflow.test">
              <template #prefix><el-icon><Message /></el-icon></template>
            </el-input>
          </el-form-item>

          <el-form-item :label="t('auth.password')" prop="password">
            <el-input v-model="form.password" type="password" show-password autocomplete="current-password">
              <template #prefix><el-icon><Lock /></el-icon></template>
            </el-input>
          </el-form-item>

          <el-button type="primary" native-type="submit" class="login__submit" :loading="auth.loading">
            {{ t('auth.signIn') }}
          </el-button>
        </el-form>

        <div class="login__demo">
          <span class="wf-muted">{{ t('auth.demo') }}</span>
          <div class="login__demo-list">
            <el-tag
              v-for="account in demoAccounts"
              :key="account.email"
              class="login__demo-tag"
              effect="plain"
              @click="useDemo(account.email)"
            >
              {{ t(`roles.${account.role}`) }}
            </el-tag>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
.login {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 1.05fr 1fr;
}

.login__aside {
  background: linear-gradient(150deg, #4f46e5 0%, #7c3aed 55%, #2563eb 100%);
  color: #fff;
  padding: 48px;
  display: flex;
  flex-direction: column;
  gap: 28px;
  justify-content: center;
}

.login__brand {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 650;
  font-size: 17px;
}

.login__logo {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: grid;
  place-items: center;
  background: rgba(255, 255, 255, 0.18);
  font-size: 13px;
}

.login__headline {
  margin: 0;
  font-size: 30px;
  line-height: 1.25;
  font-weight: 700;
  max-width: 460px;
  letter-spacing: -0.5px;
}

.login__features {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
  font-size: 14px;
  opacity: 0.92;

  li {
    display: flex;
    align-items: center;
    gap: 10px;
  }
}

.login__panel {
  background: var(--wf-surface);
  display: flex;
  flex-direction: column;
  padding: 20px;
}

.login__panel-top {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  align-items: center;
}

.login__form-wrap {
  margin: auto;
  width: 100%;
  max-width: 380px;
  padding: 24px 0;
}

.login__title {
  margin: 0;
  font-size: 26px;
  font-weight: 700;
  letter-spacing: -0.4px;
}

.login__subtitle {
  margin: 6px 0 26px;
  font-size: 13px;
}

.login__submit {
  width: 100%;
}

.login__demo {
  margin-top: 26px;
  font-size: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.login__demo-list {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.login__demo-tag {
  cursor: pointer;
}

@media (max-width: 900px) {
  .login {
    grid-template-columns: 1fr;
  }

  .login__aside {
    display: none;
  }
}
</style>
