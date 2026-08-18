<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import { apiMessage } from '@/api/client'
import { useDepartmentsStore } from '@/stores/departments'
import { useEmployeesStore } from '@/stores/employees'
import type { Employee } from '@/types'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const employees = useEmployeesStore()
const departments = useDepartmentsStore()

const employeeId = computed(() => (route.params.id ? Number(route.params.id) : null))
const isEdit = computed(() => employeeId.value !== null)
const loading = ref(false)
const formRef = ref<FormInstance>()

const form = reactive<Partial<Employee>>({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  department_id: null,
  position: '',
  hire_date: null,
  avatar: '',
})

const rules: FormRules = {
  first_name: [{ required: true, message: t('common.error'), trigger: 'blur' }],
  last_name: [{ required: true, message: t('common.error'), trigger: 'blur' }],
  email: [
    { required: true, message: t('auth.emailRequired'), trigger: 'blur' },
    { type: 'email', message: t('auth.emailInvalid'), trigger: 'blur' },
  ],
}

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
        hire_date: employee.hire_date,
        avatar: employee.avatar ?? '',
      })
    } catch (error) {
      ElMessage.error(apiMessage(error, t('employees.notFound')))
      await router.push({ name: 'employees' })
    } finally {
      loading.value = false
    }
  }
})

async function submit(): Promise<void> {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  try {
    if (isEdit.value && employeeId.value) {
      await employees.update(employeeId.value, form)
      ElMessage.success(t('common.saved'))
      await router.push({ name: 'employees.show', params: { id: employeeId.value } })
    } else {
      const created = await employees.create(form)
      ElMessage.success(t('common.created'))
      await router.push({ name: 'employees.show', params: { id: created.id } })
    }
  } catch (error) {
    ElMessage.error(apiMessage(error))
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
      <el-button icon="ArrowLeft" @click="router.back()">{{ t('common.back') }}</el-button>
    </div>

    <div class="wf-card form" v-loading="loading">
      <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
        <div class="form__grid">
          <el-form-item :label="t('employees.firstName')" prop="first_name">
            <el-input v-model="form.first_name" />
          </el-form-item>

          <el-form-item :label="t('employees.lastName')" prop="last_name">
            <el-input v-model="form.last_name" />
          </el-form-item>

          <el-form-item :label="t('employees.email')" prop="email">
            <el-input v-model="form.email" type="email" />
          </el-form-item>

          <el-form-item :label="t('employees.phone')" prop="phone">
            <el-input v-model="form.phone" placeholder="+7 700 000 00 00" />
          </el-form-item>

          <el-form-item :label="t('employees.department')" prop="department_id">
            <el-select v-model="form.department_id" clearable :placeholder="t('employees.noDepartment')" class="full">
              <el-option v-for="item in departments.options" :key="item.id" :label="item.name" :value="item.id" />
            </el-select>
          </el-form-item>

          <el-form-item :label="t('employees.position')" prop="position">
            <el-input v-model="form.position" />
          </el-form-item>

          <el-form-item :label="t('employees.hireDate')" prop="hire_date">
            <el-date-picker v-model="form.hire_date" type="date" value-format="YYYY-MM-DD" class="full" />
          </el-form-item>

          <el-form-item :label="t('employees.avatar')" prop="avatar">
            <el-input v-model="form.avatar" placeholder="https://…" />
          </el-form-item>
        </div>

        <div class="form__actions">
          <el-button @click="router.back()">{{ t('common.cancel') }}</el-button>
          <el-button type="primary" :loading="employees.saving" @click="submit">
            {{ isEdit ? t('common.save') : t('common.create') }}
          </el-button>
        </div>
      </el-form>
    </div>
  </div>
</template>

<style scoped lang="scss">
.form {
  padding: 22px;
  max-width: 880px;
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

:deep(.full),
:deep(.el-date-editor.full) {
  width: 100%;
}
</style>
