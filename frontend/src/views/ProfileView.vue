<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useQuasar } from 'quasar'
import { authApi } from '@/api'
import { apiMessage } from '@/api/client'
import { rules, useValidation } from '@/composables/useValidation'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const $q = useQuasar()
const auth = useAuthStore()

const savingProfile = ref(false)
const savingPassword = ref(false)

const profile = reactive({
  name: auth.user?.name ?? '',
  email: auth.user?.email ?? '',
  avatar: auth.user?.avatar ?? '',
})

const password = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const profileForm = useValidation(profile, {
  name: [rules.required(t('common.requiredField'))],
  email: [rules.required(t('auth.emailRequired')), rules.email(t('auth.emailInvalid'))],
})

const passwordForm = useValidation(password, {
  current_password: [rules.required(t('auth.passwordRequired'))],
  password: [rules.required(t('auth.passwordRequired')), rules.minLength(6, t('auth.passwordMin'))],
  password_confirmation: [rules.matches(() => password.password, t('profile.passwordMismatch'))],
})

async function saveProfile(): Promise<void> {
  if (!profileForm.validate()) return

  savingProfile.value = true
  try {
    auth.setUser(await authApi.updateProfile(profile))
    $q.notify({ type: 'positive', message: t('common.saved') })
  } catch (error) {
    $q.notify({ type: 'negative', message: apiMessage(error) })
  } finally {
    savingProfile.value = false
  }
}

async function savePassword(): Promise<void> {
  if (!passwordForm.validate()) return

  savingPassword.value = true
  try {
    await authApi.updateProfile(password)
    $q.notify({ type: 'positive', message: t('profile.passwordUpdated') })
    Object.assign(password, { current_password: '', password: '', password_confirmation: '' })
    passwordForm.clear()
  } catch (error) {
    $q.notify({ type: 'negative', message: apiMessage(error) })
  } finally {
    savingPassword.value = false
  }
}
</script>

<template>
  <div class="wf-page">
    <div class="wf-page__header">
      <div>
        <h1 class="wf-page__title">{{ t('profile.title') }}</h1>
        <p class="wf-page__subtitle">{{ t('profile.subtitle') }}</p>
      </div>
    </div>

    <div class="wf-grid profile-grid">
      <section class="wf-card panel panel--identity">
        <q-avatar size="82px" color="primary" text-color="white" class="identity__avatar">
          <img v-if="auth.user?.avatar" :src="auth.user.avatar" alt="" />
          <template v-else>{{ auth.initials }}</template>
        </q-avatar>
        <h2 class="identity__name">{{ auth.user?.name }}</h2>
        <p class="wf-muted identity__email">{{ auth.user?.email }}</p>
        <q-chip v-if="auth.role" dense square outline color="primary" :label="t(`roles.${auth.role}`)" />
      </section>

      <section class="wf-card panel">
        <h3 class="panel__title">{{ t('profile.title') }}</h3>
        <q-form class="q-gutter-md" @submit.prevent="saveProfile">
          <q-input
            v-model="profile.name"
            outlined
            :label="t('profile.name')"
            :error="Boolean(profileForm.errors.name)"
            :error-message="profileForm.errors.name"
            @blur="profileForm.validateField('name')"
          />
          <q-input
            v-model="profile.email"
            outlined
            type="email"
            :label="t('profile.email')"
            :error="Boolean(profileForm.errors.email)"
            :error-message="profileForm.errors.email"
            @blur="profileForm.validateField('email')"
          />
          <q-input v-model="profile.avatar" outlined :label="t('profile.avatar')" placeholder="https://…" />
          <q-btn type="submit" color="primary" unelevated no-caps :label="t('common.save')" :loading="savingProfile" />
        </q-form>
      </section>

      <section class="wf-card panel">
        <h3 class="panel__title">{{ t('profile.security') }}</h3>
        <q-form class="q-gutter-md" @submit.prevent="savePassword">
          <q-input
            v-model="password.current_password"
            outlined
            type="password"
            :label="t('profile.currentPassword')"
            :error="Boolean(passwordForm.errors.current_password)"
            :error-message="passwordForm.errors.current_password"
          />
          <q-input
            v-model="password.password"
            outlined
            type="password"
            :label="t('profile.newPassword')"
            :error="Boolean(passwordForm.errors.password)"
            :error-message="passwordForm.errors.password"
          />
          <q-input
            v-model="password.password_confirmation"
            outlined
            type="password"
            :label="t('profile.confirmPassword')"
            :error="Boolean(passwordForm.errors.password_confirmation)"
            :error-message="passwordForm.errors.password_confirmation"
            @blur="passwordForm.validateField('password_confirmation')"
          />
          <q-btn type="submit" outline no-caps color="primary" :label="t('common.save')" :loading="savingPassword" />
        </q-form>
      </section>
    </div>
  </div>
</template>

<style scoped lang="scss">
.profile-grid {
  grid-template-columns: 300px repeat(auto-fit, minmax(300px, 1fr));
  align-items: start;
}

.panel {
  padding: 20px;
}

.panel--identity {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 8px;
  padding: 28px 20px;
}

.panel__title {
  margin: 0 0 18px;
  font-size: 14px;
  font-weight: 650;
}

.identity__avatar {
  font-size: 26px;
}

.identity__name {
  margin: 6px 0 0;
  font-size: 18px;
  font-weight: 650;
}

.identity__email {
  margin: 0;
  font-size: 13px;
}

@media (max-width: 900px) {
  .profile-grid {
    grid-template-columns: 1fr;
  }
}
</style>
