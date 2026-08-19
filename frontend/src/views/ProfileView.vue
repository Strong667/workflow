<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AvatarUpload from '@/components/AvatarUpload.vue'
import { authApi } from '@/api'
import { apiMessage } from '@/api/client'
import { rules, useValidation } from '@/composables/useValidation'
import { useAuthStore } from '@/stores/auth'
import { Password } from '@/ui/lazy-components'
import { useNotify } from '@/ui/feedback'

const { t } = useI18n()
const notify = useNotify()
const auth = useAuthStore()

const savingProfile = ref(false)
const savingPassword = ref(false)

const profile = reactive({
  name: auth.user?.name ?? '',
  email: auth.user?.email ?? '',
  avatar: auth.user?.avatar ?? null,
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
    notify.success(t('common.saved'))
  } catch (error) {
    notify.error(apiMessage(error))
  } finally {
    savingProfile.value = false
  }
}

async function savePassword(): Promise<void> {
  if (!passwordForm.validate()) return

  savingPassword.value = true
  try {
    await authApi.updateProfile(password)
    notify.success(t('profile.passwordUpdated'))
    Object.assign(password, { current_password: '', password: '', password_confirmation: '' })
    passwordForm.clear()
  } catch (error) {
    notify.error(apiMessage(error))
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
        <Avatar
          :image="profile.avatar ?? undefined"
          :label="profile.avatar ? undefined : auth.initials"
          shape="circle"
          size="xlarge"
        />
        <h2 class="identity__name">{{ auth.user?.name }}</h2>
        <p class="wf-muted identity__email">{{ auth.user?.email }}</p>
        <Tag v-if="auth.role" :value="t(`roles.${auth.role}`)" severity="secondary" rounded />
      </section>

      <section class="wf-card panel">
        <h3 class="panel__title">{{ t('profile.title') }}</h3>
        <form @submit.prevent="saveProfile">
          <div class="wf-field">
            <label for="name" class="wf-field__label">{{ t('profile.name') }}</label>
            <InputText
              id="name"
              v-model="profile.name"
              :invalid="Boolean(profileForm.errors.name)"
              fluid
              @blur="profileForm.validateField('name')"
            />
            <small v-if="profileForm.errors.name" class="wf-field__error">{{ profileForm.errors.name }}</small>
          </div>

          <div class="wf-field">
            <label for="profile-email" class="wf-field__label">{{ t('profile.email') }}</label>
            <InputText
              id="profile-email"
              v-model="profile.email"
              type="email"
              :invalid="Boolean(profileForm.errors.email)"
              fluid
              @blur="profileForm.validateField('email')"
            />
            <small v-if="profileForm.errors.email" class="wf-field__error">{{ profileForm.errors.email }}</small>
          </div>

          <div class="wf-field">
            <span class="wf-field__label">{{ t('profile.photo') }}</span>
            <AvatarUpload v-model="profile.avatar" :initials="auth.initials" :size="86" />
          </div>

          <Button type="submit" :label="t('common.save')" :loading="savingProfile" />
        </form>
      </section>

      <section class="wf-card panel">
        <h3 class="panel__title">{{ t('profile.security') }}</h3>
        <form @submit.prevent="savePassword">
          <div class="wf-field">
            <label for="current-password" class="wf-field__label">{{ t('profile.currentPassword') }}</label>
            <Password
              input-id="current-password"
              v-model="password.current_password"
              :feedback="false"
              toggle-mask
              :invalid="Boolean(passwordForm.errors.current_password)"
              fluid
            />
            <small v-if="passwordForm.errors.current_password" class="wf-field__error">
              {{ passwordForm.errors.current_password }}
            </small>
          </div>

          <div class="wf-field">
            <label for="new-password" class="wf-field__label">{{ t('profile.newPassword') }}</label>
            <Password
              input-id="new-password"
              v-model="password.password"
              toggle-mask
              :invalid="Boolean(passwordForm.errors.password)"
              fluid
            />
            <small v-if="passwordForm.errors.password" class="wf-field__error">
              {{ passwordForm.errors.password }}
            </small>
          </div>

          <div class="wf-field">
            <label for="confirm-password" class="wf-field__label">{{ t('profile.confirmPassword') }}</label>
            <Password
              input-id="confirm-password"
              v-model="password.password_confirmation"
              :feedback="false"
              toggle-mask
              :invalid="Boolean(passwordForm.errors.password_confirmation)"
              fluid
              @blur="passwordForm.validateField('password_confirmation')"
            />
            <small v-if="passwordForm.errors.password_confirmation" class="wf-field__error">
              {{ passwordForm.errors.password_confirmation }}
            </small>
          </div>

          <Button type="submit" :label="t('common.save')" severity="secondary" outlined :loading="savingPassword" />
        </form>
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
