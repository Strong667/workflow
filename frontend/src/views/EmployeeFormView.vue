<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import AvatarUpload from '@/components/AvatarUpload.vue'
import { apiMessage } from '@/api/client'
import { toDate, toDateString } from '@/composables/useTaskMeta'
import { rules, useValidation } from '@/composables/useValidation'
import { useDepartmentsStore } from '@/stores/departments'
import { useAuthStore } from '@/stores/auth'
import { useEmployeesStore } from '@/stores/employees'
import type { Role } from '@/types'
import { DatePicker, Password } from '@/ui/lazy-components'
import { useNotify } from '@/ui/feedback'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const notify = useNotify()
const auth = useAuthStore()
const employees = useEmployeesStore()
const departments = useDepartmentsStore()

const employeeId = computed(() => (route.params.id ? Number(route.params.id) : null))
const isEdit = computed(() => employeeId.value !== null)
const initials = computed(() =>
  `${form.first_name[0] ?? ''}${form.last_name[0] ?? ''}`.toUpperCase(),
)
const loading = ref(false)

const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  department_id: null as number | null,
  position: '',
  hire_date: null as Date | null,
  avatar: null as string | null,
  role: 'employee' as Role,
  password: '',
})

const { errors, validate, validateField } = useValidation(form, {
  first_name: [rules.required(t('common.requiredField'))],
  last_name: [rules.required(t('common.requiredField'))],
  email: [rules.required(t('auth.emailRequired')), rules.email(t('auth.emailInvalid'))],
  // При создании пароль обязателен: человек должен войти сразу.
  password: [
    (value) => (!isEdit.value && !value ? t('auth.passwordRequired') : null),
    (value) => (typeof value === 'string' && value.length > 0 && value.length < 6 ? t('auth.passwordMin') : null),
  ],
})

/** Менеджер не выдаёт роль администратора. */
const roleOptions = computed(() =>
  (auth.can('admin') ? (['admin', 'manager', 'employee'] as Role[]) : (['manager', 'employee'] as Role[])).map(
    (value) => ({ value, label: t(`roles.${value}`) }),
  ),
)

onMounted(async () => {
  await departments.fetchOptions()

  if (isEdit.value && employeeId.value) {
    loading.value = true
    try {
      const employee = await employees.fetchOne(employeeId.value)
      Object.assign(form, {
        first_name: employee.first_name,
        last_name: employee.last_name,
        email: employee.email,
        phone: employee.phone ?? '',
        department_id: employee.department_id,
        position: employee.position ?? '',
        hire_date: toDate(employee.hire_date),
        avatar: employee.avatar,
        role: employee.account?.role ?? 'employee',
        password: '',
      })
    } catch (error) {
      notify.error(apiMessage(error, t('employees.notFound')))
      await router.push({ name: 'employees' })
    } finally {
      loading.value = false
    }
  }
})

async function submit(): Promise<void> {
  if (!validate()) return

  const payload = {
    ...form,
    hire_date: toDateString(form.hire_date),
    // Пустой пароль при редактировании означает «оставить прежний».
    ...(isEdit.value && !form.password ? { password: undefined } : {}),
  }

  try {
    if (isEdit.value && employeeId.value) {
      await employees.update(employeeId.value, payload)
      notify.success(t('common.saved'))
      await router.push({ name: 'employees.show', params: { id: employeeId.value } })
    } else {
      const created = await employees.create(payload)
      notify.success(t('common.created'))
      await router.push({ name: 'employees.show', params: { id: created.id } })
    }
  } catch (error) {
    notify.error(apiMessage(error))
  }
}
</script>

<template>
  <div class="wf-page">
    <div class="wf-page__header">
      <div>
        <h1 class="wf-page__title">{{ isEdit ? t('employees.editTitle') : t('employees.createTitle') }}</h1>
        <p class="wf-page__subtitle">{{ t('employees.subtitle') }}</p>
      </div>
      <Button icon="pi pi-arrow-left" :label="t('common.back')" severity="secondary" outlined @click="router.back()" />
    </div>

    <div class="wf-card form">
      <div v-if="loading" class="form__loading"><ProgressSpinner style="width: 42px; height: 42px" /></div>

      <form v-else @submit.prevent="submit">
        <div class="form__grid">
          <div class="wf-field">
            <label for="first_name" class="wf-field__label">{{ t('employees.firstName') }}</label>
            <InputText
              id="first_name"
              v-model="form.first_name"
              :invalid="Boolean(errors.first_name)"
              fluid
              @blur="validateField('first_name')"
            />
            <small v-if="errors.first_name" class="wf-field__error">{{ errors.first_name }}</small>
          </div>

          <div class="wf-field">
            <label for="last_name" class="wf-field__label">{{ t('employees.lastName') }}</label>
            <InputText
              id="last_name"
              v-model="form.last_name"
              :invalid="Boolean(errors.last_name)"
              fluid
              @blur="validateField('last_name')"
            />
            <small v-if="errors.last_name" class="wf-field__error">{{ errors.last_name }}</small>
          </div>

          <div class="wf-field">
            <label for="email" class="wf-field__label">{{ t('employees.email') }}</label>
            <InputText
              id="email"
              v-model="form.email"
              type="email"
              :invalid="Boolean(errors.email)"
              fluid
              @blur="validateField('email')"
            />
            <small v-if="errors.email" class="wf-field__error">{{ errors.email }}</small>
          </div>

          <div class="wf-field">
            <label for="phone" class="wf-field__label">{{ t('employees.phone') }}</label>
            <InputText id="phone" v-model="form.phone" placeholder="+7 700 000 00 00" fluid />
          </div>

          <div class="wf-field">
            <label for="department" class="wf-field__label">{{ t('employees.department') }}</label>
            <Select
              id="department"
              v-model="form.department_id"
              :options="departments.options"
              option-label="name"
              option-value="id"
              :placeholder="t('employees.noDepartment')"
              show-clear
              fluid
            />
          </div>

          <div class="wf-field">
            <label for="position" class="wf-field__label">{{ t('employees.position') }}</label>
            <InputText id="position" v-model="form.position" fluid />
          </div>

          <div class="wf-field">
            <label for="hire_date" class="wf-field__label">{{ t('employees.hireDate') }}</label>
            <DatePicker id="hire_date" v-model="form.hire_date" date-format="dd.mm.yy" show-icon fluid />
          </div>

          <div class="wf-field form__section">
            <h3 class="form__section-title">
              <i class="pi pi-key" />
              {{ t('employees.access') }}
            </h3>
            <p class="wf-muted form__section-hint">
              {{ isEdit ? t('employees.accessEditHint') : t('employees.accessCreateHint') }}
            </p>
          </div>

          <div class="wf-field">
            <label for="role" class="wf-field__label">{{ t('settings.role') }}</label>
            <Select
              id="role"
              v-model="form.role"
              :options="roleOptions"
              option-label="label"
              option-value="value"
              fluid
            />
          </div>

          <div class="wf-field">
            <label for="password" class="wf-field__label">
              {{ isEdit ? t('employees.newPassword') : t('auth.password') }}
            </label>
            <Password
              input-id="password"
              v-model="form.password"
              :feedback="!isEdit"
              toggle-mask
              :placeholder="isEdit ? t('employees.passwordKeep') : ''"
              :invalid="Boolean(errors.password)"
              fluid
              @blur="validateField('password')"
            />
            <small v-if="errors.password" class="wf-field__error">{{ errors.password }}</small>
          </div>

          <div class="wf-field form__avatar">
            <span class="wf-field__label">{{ t('employees.photo') }}</span>
            <AvatarUpload v-model="form.avatar" :initials="initials" />
          </div>
        </div>

        <div class="form__actions">
          <Button :label="t('common.cancel')" severity="secondary" outlined @click="router.back()" />
          <Button
            type="submit"
            :label="isEdit ? t('common.save') : t('common.create')"
            :loading="employees.saving"
          />
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped lang="scss">
.form {
  padding: 22px;
  max-width: 880px;
}

.form__loading {
  display: grid;
  place-items: center;
  min-height: 260px;
}

.form__avatar {
  grid-column: 1 / -1;
}

.form__section {
  grid-column: 1 / -1;
  margin-bottom: 0;
  padding-top: 8px;
  border-top: 1px solid var(--wf-border);
}

.form__section-title {
  margin: 12px 0 4px;
  font-size: 14px;
  font-weight: 650;
  display: flex;
  align-items: center;
  gap: 8px;
}

.form__section-hint {
  margin: 0;
  font-size: 12.5px;
}

.form__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 0 20px;
}

.form__actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 12px;
}
</style>
