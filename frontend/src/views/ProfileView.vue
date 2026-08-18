<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import { authApi } from '@/api'
import { apiMessage } from '@/api/client'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const auth = useAuthStore()

const profileRef = ref<FormInstance>()
const passwordRef = ref<FormInstance>()
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

const profileRules: FormRules = {
  name: [{ required: true, message: t('common.error'), trigger: 'blur' }],
  email: [
    { required: true, message: t('auth.emailRequired'), trigger: 'blur' },
    { type: 'email', message: t('auth.emailInvalid'), trigger: 'blur' },
  ],
}

const passwordRules: FormRules = {
  current_password: [{ required: true, message: t('auth.passwordRequired'), trigger: 'blur' }],
  password: [
    { required: true, message: t('auth.passwordRequired'), trigger: 'blur' },
    { min: 6, message: t('auth.passwordMin'), trigger: 'blur' },
  ],
  password_confirmation: [
    {
      validator: (_rule, value: string, callback: (error?: Error) => void) => {
        if (value !== password.password) callback(new Error(t('profile.passwordMismatch')))
        else callback()
      },
      trigger: 'blur',
    },
  ],
}

async function saveProfile(): Promise<void> {
  const valid = await profileRef.value?.validate().catch(() => false)
  if (!valid) return

  savingProfile.value = true
  try {
    auth.setUser(await authApi.updateProfile(profile))
    ElMessage.success(t('common.saved'))
  } catch (error) {
    ElMessage.error(apiMessage(error))
  } finally {
    savingProfile.value = false
  }
}

async function savePassword(): Promise<void> {
  const valid = await passwordRef.value?.validate().catch(() => false)
  if (!valid) return

  savingPassword.value = true
  try {
    await authApi.updateProfile(password)
    ElMessage.success(t('profile.passwordUpdated'))
    passwordRef.value?.resetFields()
  } catch (error) {
    ElMessage.error(apiMessage(error))
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
        <el-avatar :size="82" :src="auth.user?.avatar ?? undefined" class="identity__avatar">
          {{ auth.initials }}
        </el-avatar>
        <h2 class="identity__name">{{ auth.user?.name }}</h2>
        <p class="wf-muted identity__email">{{ auth.user?.email }}</p>
        <el-tag effect="plain" round>{{ auth.role ? t(`roles.${auth.role}`) : '' }}</el-tag>
      </section>

      <section class="wf-card panel">
        <h3 class="panel__title">{{ t('profile.title') }}</h3>
        <el-form ref="profileRef" :model="profile" :rules="profileRules" label-position="top">
          <el-form-item :label="t('profile.name')" prop="name">
            <el-input v-model="profile.name" />
          </el-form-item>
          <el-form-item :label="t('profile.email')" prop="email">
            <el-input v-model="profile.email" type="email" />
          </el-form-item>
          <el-form-item :label="t('profile.avatar')" prop="avatar">
            <el-input v-model="profile.avatar" placeholder="https://…" />
          </el-form-item>
          <el-button type="primary" :loading="savingProfile" @click="saveProfile">{{ t('common.save') }}</el-button>
        </el-form>
      </section>

      <section class="wf-card panel">
        <h3 class="panel__title">{{ t('profile.security') }}</h3>
        <el-form ref="passwordRef" :model="password" :rules="passwordRules" label-position="top">
          <el-form-item :label="t('profile.currentPassword')" prop="current_password">
            <el-input v-model="password.current_password" type="password" show-password />
          </el-form-item>
          <el-form-item :label="t('profile.newPassword')" prop="password">
            <el-input v-model="password.password" type="password" show-password />
          </el-form-item>
          <el-form-item :label="t('profile.confirmPassword')" prop="password_confirmation">
            <el-input v-model="password.password_confirmation" type="password" show-password />
          </el-form-item>
          <el-button type="primary" plain :loading="savingPassword" @click="savePassword">
            {{ t('common.save') }}
          </el-button>
        </el-form>
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
