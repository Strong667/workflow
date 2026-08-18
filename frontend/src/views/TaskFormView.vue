<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import { employeesApi, tasksApi } from '@/api'
import { apiMessage } from '@/api/client'
import { useTasksStore, STATUSES } from '@/stores/tasks'
import type { Employee, Task, TaskPriority } from '@/types'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const tasks = useTasksStore()

const taskId = computed(() => (route.params.id ? Number(route.params.id) : null))
const isEdit = computed(() => taskId.value !== null)
const loading = ref(false)
const employees = ref<Employee[]>([])
const employeesLoading = ref(false)
const formRef = ref<FormInstance>()

const form = reactive<Partial<Task>>({
  title: '',
  description: '',
  employee_id: null,
  status: 'todo',
  priority: 'medium',
  deadline: null,
})

const rules: FormRules = {
  title: [{ required: true, message: t('common.error'), trigger: 'blur' }],
}

const priorities: TaskPriority[] = ['low', 'medium', 'high']

/** Удалённый поиск исполнителя, чтобы не тянуть весь штат в селект. */
async function searchEmployees(query: string): Promise<void> {
  employeesLoading.value = true
  try {
    const response = await employeesApi.list({ search: query, per_page: 20 })
    employees.value = response.data
  } finally {
    employeesLoading.value = false
  }
}

onMounted(async () => {
  await searchEmployees('')

  if (isEdit.value && taskId.value) {
    loading.value = true
    try {
      const task = await tasksApi.get(taskId.value)
      Object.assign(form, {
        title: task.title,
        description: task.description ?? '',
        employee_id: task.employee_id,
        status: task.status,
        priority: task.priority,
        deadline: task.deadline,
      })
      if (task.employee && !employees.value.some((item) => item.id === task.employee_id)) {
        employees.value.unshift(task.employee)
      }
    } catch (error) {
      ElMessage.error(apiMessage(error))
      await router.push({ name: 'tasks' })
    } finally {
      loading.value = false
    }
  }
})

async function submit(): Promise<void> {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  try {
    if (isEdit.value && taskId.value) {
      await tasks.update(taskId.value, form)
      ElMessage.success(t('common.saved'))
    } else {
      await tasks.create(form)
      ElMessage.success(t('common.created'))
    }
    await router.push({ name: 'tasks' })
  } catch (error) {
    ElMessage.error(apiMessage(error))
  }
}
</script>

<template>
  <div class="wf-page">
    <div class="wf-page__header">
      <div>
        <h1 class="wf-page__title">{{ isEdit ? t('tasks.editTitle') : t('tasks.createTitle') }}</h1>
        <p class="wf-page__subtitle">{{ t('tasks.subtitle') }}</p>
      </div>
      <el-button icon="ArrowLeft" @click="router.back()">{{ t('common.back') }}</el-button>
    </div>

    <div class="wf-card form" v-loading="loading">
      <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
        <el-form-item :label="t('tasks.titleField')" prop="title">
          <el-input v-model="form.title" maxlength="180" show-word-limit />
        </el-form-item>

        <el-form-item :label="t('tasks.description')" prop="description">
          <el-input v-model="form.description" type="textarea" :rows="5" maxlength="5000" show-word-limit />
        </el-form-item>

        <div class="form__grid">
          <el-form-item :label="t('tasks.assignee')" prop="employee_id">
            <el-select
              v-model="form.employee_id"
              filterable
              remote
              clearable
              class="full"
              :remote-method="searchEmployees"
              :loading="employeesLoading"
              :placeholder="t('tasks.unassigned')"
            >
              <el-option
                v-for="item in employees"
                :key="item.id"
                :label="`${item.full_name}${item.position ? ' · ' + item.position : ''}`"
                :value="item.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item :label="t('tasks.status')" prop="status">
            <el-select v-model="form.status" class="full">
              <el-option
                v-for="status in STATUSES"
                :key="status"
                :label="t(`tasks.statuses.${status}`)"
                :value="status"
              />
            </el-select>
          </el-form-item>

          <el-form-item :label="t('tasks.priority')" prop="priority">
            <el-select v-model="form.priority" class="full">
              <el-option
                v-for="priority in priorities"
                :key="priority"
                :label="t(`tasks.priorities.${priority}`)"
                :value="priority"
              />
            </el-select>
          </el-form-item>

          <el-form-item :label="t('tasks.deadline')" prop="deadline">
            <el-date-picker v-model="form.deadline" type="date" value-format="YYYY-MM-DD" class="full" />
          </el-form-item>
        </div>

        <div class="form__actions">
          <el-button @click="router.back()">{{ t('common.cancel') }}</el-button>
          <el-button type="primary" :loading="tasks.saving" @click="submit">
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
  max-width: 820px;
}

.form__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
