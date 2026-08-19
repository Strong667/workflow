<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { apiMessage } from '@/api/client'
import { fromQuasarDate, toQuasarDate } from '@/composables/useTaskMeta'
import { rules, useValidation } from '@/composables/useValidation'
import { useDepartmentsStore } from '@/stores/departments'
import { useEmployeesStore } from '@/stores/employees'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const employees = useEmployeesStore()
const departments = useDepartmentsStore()

const employeeId = computed(() => (route.params.id ? Number(route.params.id) : null))
const isEdit = computed(() => employeeId.value !== null)
const loading = ref(false)

const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  department_id: null as number | null,
  position: '',
  hire_date: null as string | null,
  avatar: '',
})

const { errors, validate, validateField } = useValidation(form, {
  first_name: [rules.required(t('common.requiredField'))],
  last_name: [rules.required(t('common.requiredField'))],
  email: [rules.required(t('auth.emailRequired')), rules.email(t('auth.emailInvalid'))],
})

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
        hire_date: toQuasarDate(employee.hire_date),
        avatar: employee.avatar ?? '',
      })
    } catch (error) {
      $q.notify({ type: 'negative', message: apiMessage(error, t('employees.notFound')) })
      await router.push({ name: 'employees' })
    } finally {
      loading.value = false
    }
  }
})

async function submit(): Promise<void> {
  if (!validate()) return

  const payload = { ...form, hire_date: fromQuasarDate(form.hire_date) }

  try {
    if (isEdit.value && employeeId.value) {
      await employees.update(employeeId.value, payload)
      $q.notify({ type: 'positive', message: t('common.saved') })
      await router.push({ name: 'employees.show', params: { id: employeeId.value } })
    } else {
      const created = await employees.create(payload)
      $q.notify({ type: 'positive', message: t('common.created') })
      await router.push({ name: 'employees.show', params: { id: created.id } })
    }
  } catch (error) {
    $q.notify({ type: 'negative', message: apiMessage(error) })
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
      <q-btn flat no-caps icon="arrow_back" :label="t('common.back')" @click="router.back()" />
    </div>

    <div class="wf-card form">
      <q-inner-loading :showing="loading"><q-spinner size="42px" color="primary" /></q-inner-loading>

      <q-form v-if="!loading" @submit.prevent="submit">
        <div class="form__grid">
          <q-input
            v-model="form.first_name"
            outlined
            :label="t('employees.firstName')"
            :error="Boolean(errors.first_name)"
            :error-message="errors.first_name"
            @blur="validateField('first_name')"
          />

          <q-input
            v-model="form.last_name"
            outlined
            :label="t('employees.lastName')"
            :error="Boolean(errors.last_name)"
            :error-message="errors.last_name"
            @blur="validateField('last_name')"
          />

          <q-input
            v-model="form.email"
            outlined
            type="email"
            :label="t('employees.email')"
            :error="Boolean(errors.email)"
            :error-message="errors.email"
            @blur="validateField('email')"
          />

          <q-input v-model="form.phone" outlined :label="t('employees.phone')" placeholder="+7 700 000 00 00" />

          <q-select
            v-model="form.department_id"
            :options="departments.options"
            option-label="name"
            option-value="id"
            emit-value
            map-options
            clearable
            outlined
            :label="t('employees.department')"
          />

          <q-input v-model="form.position" outlined :label="t('employees.position')" />

          <q-input v-model="form.hire_date" outlined :label="t('employees.hireDate')" mask="####/##/##" readonly>
            <template #append>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                  <q-date v-model="form.hire_date" minimal>
                    <div class="row items-center justify-end">
                      <q-btn v-close-popup flat no-caps color="primary" :label="t('common.cancel')" />
                    </div>
                  </q-date>
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>

          <q-input v-model="form.avatar" outlined :label="t('employees.avatar')" placeholder="https://…" />
        </div>

        <div class="form__actions">
          <q-btn flat no-caps :label="t('common.cancel')" @click="router.back()" />
          <q-btn
            type="submit"
            color="primary"
            unelevated
            no-caps
            :label="isEdit ? t('common.save') : t('common.create')"
            :loading="employees.saving"
          />
        </div>
      </q-form>
    </div>
  </div>
</template>

<style scoped lang="scss">
.form {
  padding: 22px;
  max-width: 880px;
  position: relative;
  min-height: 200px;
}

.form__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 18px 20px;
}

.form__actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 22px;
}
</style>
