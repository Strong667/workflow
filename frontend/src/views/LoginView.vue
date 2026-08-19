<script setup lang="ts">
import { onMounted, reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Password from 'primevue/password'
import { useToast } from 'primevue/usetoast'
import { SUPPORTED_LOCALES } from '@/locales'
import { apiMessage } from '@/api/client'
import { rules, useValidation } from '@/composables/useValidation'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import type { Locale } from '@/types'

const { t } = useI18n()
const auth = useAuthStore()
const ui = useUiStore()
const router = useRouter()
const route = useRoute()
const toast = useToast()

const form = reactive({ email: '', password: '' })

const { errors, validate, validateField } = useValidation(form, {
  email: [rules.required(t('auth.emailRequired')), rules.email(t('auth.emailInvalid'))],
  password: [rules.required(t('auth.passwordRequired')), rules.minLength(6, t('auth.passwordMin'))],
})

const demoAccounts = [
  { email: 'admin@workflow.test', role: 'admin' },
  { email: 'manager@workflow.test', role: 'manager' },
  { email: 'user@workflow.test', role: 'employee' },
]

onMounted(() => {
  if (route.query.expired) {
    toast.add({ severity: 'warn', summary: t('auth.sessionExpired'), life: 4000 })
  }
})

function useDemo(email: string): void {
  form.email = email
  form.password = 'password'
}

async function submit(): Promise<void> {
  if (!validate()) return

  try {
    await auth.login(form.email, form.password)
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : { name: 'dashboard' }
    await router.push(redirect as never)
  } catch (error) {
    toast.add({ severity: 'error', summary: apiMessage(error, t('auth.invalid')), life: 4000 })
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
        <li><i class="pi pi-check-circle" />{{ t('nav.employees') }}, {{ t('nav.departments') }}</li>
        <li><i class="pi pi-check-circle" />{{ t('tasks.subtitle') }}</li>
        <li><i class="pi pi-check-circle" />{{ t('nav.activity') }}</li>
      </ul>
    </div>

    <div class="login__panel">
      <div class="login__panel-top">
        <Select
          :model-value="ui.locale"
          :options="SUPPORTED_LOCALES"
          option-label="label"
          option-value="value"
          size="small"
          class="login__locale"
          @update:model-value="(value: Locale) => ui.applyLocale(value)"
        />
        <Button
          :icon="ui.theme === 'light' ? 'pi pi-moon' : 'pi pi-sun'"
          severity="secondary"
          text
          rounded
          @click="ui.toggleTheme()"
        />
      </div>

      <div class="login__form-wrap">
        <h2 class="login__title">{{ t('auth.title') }}</h2>
        <p class="wf-muted login__subtitle">{{ t('common.appName') }}</p>

        <form @submit.prevent="submit">
          <div class="wf-field">
            <label for="email" class="wf-field__label">{{ t('auth.email') }}</label>
            <IconField>
              <InputIcon class="pi pi-envelope" />
              <InputText
                id="email"
                v-model="form.email"
                autocomplete="username"
                placeholder="admin@workflow.test"
                :invalid="Boolean(errors.email)"
                fluid
                @blur="validateField('email')"
              />
            </IconField>
            <small v-if="errors.email" class="wf-field__error">{{ errors.email }}</small>
          </div>

          <div class="wf-field">
            <label for="password" class="wf-field__label">{{ t('auth.password') }}</label>
            <Password
              id="password"
              v-model="form.password"
              :feedback="false"
              toggle-mask
              autocomplete="current-password"
              :invalid="Boolean(errors.password)"
              fluid
              @blur="validateField('password')"
            />
            <small v-if="errors.password" class="wf-field__error">{{ errors.password }}</small>
          </div>

          <Button type="submit" :label="t('auth.signIn')" :loading="auth.loading" class="login__submit" />
        </form>

        <div class="login__demo">
          <span class="wf-muted">{{ t('auth.demo') }}</span>
          <div class="login__demo-list">
            <Button
              v-for="account in demoAccounts"
              :key="account.email"
              :label="t(`roles.${account.role}`)"
              severity="secondary"
              outlined
              size="small"
              @click="useDemo(account.email)"
            />
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
  background: linear-gradient(150deg, var(--p-indigo-600) 0%, var(--p-violet-600) 55%, var(--p-blue-600) 100%);
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

.login__locale {
  width: 130px;
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

@media (max-width: 900px) {
  .login {
    grid-template-columns: 1fr;
  }

  .login__aside {
    display: none;
  }
}
</style>
