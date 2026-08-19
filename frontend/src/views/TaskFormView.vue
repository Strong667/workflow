<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import DatePicker from 'primevue/datepicker'
import { useToast } from 'primevue/usetoast'
import { employeesApi, tasksApi } from '@/api'
import { apiMessage } from '@/api/client'
import { toDate, toDateString } from '@/composables/useTaskMeta'
import { rules, useValidation } from '@/composables/useValidation'
import { useTasksStore, STATUSES } from '@/stores/tasks'
import type { Employee, TaskPriority, TaskStatus } from '@/types'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const toast = useToast()
const tasks = useTasksStore()

const taskId = computed(() => (route.params.id ? Number(route.params.id) : null))
const isEdit = computed(() => taskId.value !== null)
const loading = ref(false)
const employees = ref<Employee[]>([])
const employeesLoading = ref(false)

const form = reactive({
  title: '',
  description: '',
  employee_id: null as number | null,
  status: 'todo' as TaskStatus,
  priority: 'medium' as TaskPriority,
  deadline: null as Date | null,
})

const { errors, validate, validateField } = useValidation(form, {
  title: [rules.required(t('common.requiredField'))],
})

const statusOptions = computed(() => STATUSES.map((value) => ({ value, label: t(`tasks.statuses.${value}`) })))
const priorityOptions = computed(() =>
  (['low', 'medium', 'high'] as TaskPriority[]).map((value) => ({ value, label: t(`tasks.priorities.${value}`) })),
)

/** Удалённый поиск исполнителя, чтобы не тянуть весь штат в селект. */
async function searchEmployees(query = ''): Promise<void> {
  employeesLoading.value = true
  try {
    const response = await employeesApi.list({ search: query, per_page: 20 })
    employees.value = response.data
  } finally {
    employeesLoading.value = false
  }
}

onMounted(async () => {
  await searchEmployees()

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
        deadline: toDate(task.deadline),
      })
      if (task.employee && !employees.value.some((item) => item.id === task.employee_id)) {
        employees.value.unshift(task.employee)
      }
    } catch (error) {
      toast.add({ severity: 'error', summary: apiMessage(error), life: 4000 })
      await router.push({ name: 'tasks' })
    } finally {
      loading.value = false
    }
  }
})

async function submit(): Promise<void> {
  if (!validate()) return

  const payload = { ...form, deadline: toDateString(form.deadline) }

  try {
    if (isEdit.value && taskId.value) {
      await tasks.update(taskId.value, payload)
      toast.add({ severity: 'success', summary: t('common.saved'), life: 3000 })
    } else {
      await tasks.create(payload)
      toast.add({ severity: 'success', summary: t('common.created'), life: 3000 })
    }
    await router.push({ name: 'tasks' })
  } catch (error) {
    toast.add({ severity: 'error', summary: apiMessage(error), life: 4000 })
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
      <Button icon="pi pi-arrow-left" :label="t('common.back')" severity="secondary" outlined @click="router.back()" />
    </div>

    <div class="wf-card form">
      <div v-if="loading" class="form__loading"><ProgressSpinner style="width: 42px; height: 42px" /></div>

      <form v-else @submit.prevent="submit">
        <div class="wf-field">
          <label for="title" class="wf-field__label">{{ t('tasks.titleField') }}</label>
          <InputText
            id="title"
            v-model="form.title"
            maxlength="180"
            :invalid="Boolean(errors.title)"
            fluid
            @blur="validateField('title')"
          />
          <small v-if="errors.title" class="wf-field__error">{{ errors.title }}</small>
        </div>

        <div class="wf-field">
          <label for="description" class="wf-field__label">{{ t('tasks.description') }}</label>
          <Textarea id="description" v-model="form.description" rows="5" maxlength="5000" auto-resize fluid />
        </div>

        <div class="form__grid">
          <div class="wf-field">
            <label for="assignee" class="wf-field__label">{{ t('tasks.assignee') }}</label>
            <Select
              id="assignee"
              v-model="form.employee_id"
              :options="employees"
              option-value="id"
              filter
              show-clear
              :loading="employeesLoading"
              :placeholder="t('tasks.unassigned')"
              :filter-placeholder="t('common.search')"
              fluid
              @filter="(event: { value: string }) => searchEmployees(event.value)"
            >
              <template #option="{ option }: { option: Employee }">
                <div class="assignee">
                  <span>{{ option.full_name }}</span>
                  <small v-if="option.position" class="wf-muted">{{ option.position }}</small>
                </div>
              </template>
              <template #value="{ value }">
                <span v-if="value">{{ employees.find((item) => item.id === value)?.full_name ?? '—' }}</span>
                <span v-else class="wf-muted">{{ t('tasks.unassigned') }}</span>
              </template>
            </Select>
          </div>

          <div class="wf-field">
            <label for="status" class="wf-field__label">{{ t('tasks.status') }}</label>
            <Select
              id="status"
              v-model="form.status"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              fluid
            />
          </div>

          <div class="wf-field">
            <label for="priority" class="wf-field__label">{{ t('tasks.priority') }}</label>
            <Select
              id="priority"
              v-model="form.priority"
              :options="priorityOptions"
              option-label="label"
              option-value="value"
              fluid
            />
          </div>

          <div class="wf-field">
            <label for="deadline" class="wf-field__label">{{ t('tasks.deadline') }}</label>
            <DatePicker id="deadline" v-model="form.deadline" date-format="dd.mm.yy" show-icon fluid />
          </div>
        </div>

        <div class="form__actions">
          <Button :label="t('common.cancel')" severity="secondary" outlined @click="router.back()" />
          <Button type="submit" :label="isEdit ? t('common.save') : t('common.create')" :loading="tasks.saving" />
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped lang="scss">
.form {
  padding: 22px;
  max-width: 820px;
}

.form__loading {
  display: grid;
  place-items: center;
  min-height: 260px;
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

.assignee {
  display: flex;
  flex-direction: column;
  line-height: 1.25;
}
</style>
