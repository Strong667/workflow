<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { employeesApi, tasksApi } from '@/api'
import { apiMessage } from '@/api/client'
import { fromQuasarDate, toQuasarDate } from '@/composables/useTaskMeta'
import { rules, useValidation } from '@/composables/useValidation'
import { useTasksStore, STATUSES } from '@/stores/tasks'
import type { Employee, TaskPriority, TaskStatus } from '@/types'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const tasks = useTasksStore()

const taskId = computed(() => (route.params.id ? Number(route.params.id) : null))
const isEdit = computed(() => taskId.value !== null)
const loading = ref(false)
const employees = ref<Employee[]>([])

const form = reactive({
  title: '',
  description: '',
  employee_id: null as number | null,
  status: 'todo' as TaskStatus,
  priority: 'medium' as TaskPriority,
  deadline: null as string | null,
})

const { errors, validate, validateField } = useValidation(form, {
  title: [rules.required(t('common.requiredField'))],
})

const statusOptions = computed(() => STATUSES.map((value) => ({ value, label: t(`tasks.statuses.${value}`) })))
const priorityOptions = computed(() =>
  (['low', 'medium', 'high'] as TaskPriority[]).map((value) => ({ value, label: t(`tasks.priorities.${value}`) })),
)

const employeeOptions = computed(() =>
  employees.value.map((item) => ({
    value: item.id,
    label: item.position ? `${item.full_name} · ${item.position}` : item.full_name,
  })),
)

/** Удалённый поиск исполнителя, чтобы не тянуть весь штат в селект. */
async function searchEmployees(query = '', done?: (callback: () => void) => void): Promise<void> {
  const response = await employeesApi.list({ search: query, per_page: 20 })
  const apply = () => {
    employees.value = response.data
  }
  done ? done(apply) : apply()
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
        deadline: toQuasarDate(task.deadline),
      })
      if (task.employee && !employees.value.some((item) => item.id === task.employee_id)) {
        employees.value.unshift(task.employee)
      }
    } catch (error) {
      $q.notify({ type: 'negative', message: apiMessage(error) })
      await router.push({ name: 'tasks' })
    } finally {
      loading.value = false
    }
  }
})

async function submit(): Promise<void> {
  if (!validate()) return

  const payload = { ...form, deadline: fromQuasarDate(form.deadline) }

  try {
    if (isEdit.value && taskId.value) {
      await tasks.update(taskId.value, payload)
      $q.notify({ type: 'positive', message: t('common.saved') })
    } else {
      await tasks.create(payload)
      $q.notify({ type: 'positive', message: t('common.created') })
    }
    await router.push({ name: 'tasks' })
  } catch (error) {
    $q.notify({ type: 'negative', message: apiMessage(error) })
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
      <q-btn flat no-caps icon="arrow_back" :label="t('common.back')" @click="router.back()" />
    </div>

    <div class="wf-card form">
      <q-inner-loading :showing="loading"><q-spinner size="42px" color="primary" /></q-inner-loading>

      <q-form v-if="!loading" @submit.prevent="submit">
        <q-input
          v-model="form.title"
          outlined
          counter
          maxlength="180"
          :label="t('tasks.titleField')"
          :error="Boolean(errors.title)"
          :error-message="errors.title"
          class="q-mb-md"
          @blur="validateField('title')"
        />

        <q-input
          v-model="form.description"
          outlined
          type="textarea"
          rows="5"
          counter
          maxlength="5000"
          :label="t('tasks.description')"
          class="q-mb-md"
        />

        <div class="form__grid">
          <q-select
            v-model="form.employee_id"
            :options="employeeOptions"
            option-label="label"
            option-value="value"
            emit-value
            map-options
            clearable
            use-input
            input-debounce="350"
            outlined
            :label="t('tasks.assignee')"
            @filter="(value: string, done: (cb: () => void) => void) => searchEmployees(value, done)"
          />

          <q-select
            v-model="form.status"
            :options="statusOptions"
            option-label="label"
            option-value="value"
            emit-value
            map-options
            outlined
            :label="t('tasks.status')"
          />

          <q-select
            v-model="form.priority"
            :options="priorityOptions"
            option-label="label"
            option-value="value"
            emit-value
            map-options
            outlined
            :label="t('tasks.priority')"
          />

          <q-input v-model="form.deadline" outlined :label="t('tasks.deadline')" mask="####/##/##" readonly>
            <template #append>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                  <q-date v-model="form.deadline" minimal>
                    <div class="row items-center justify-end">
                      <q-btn v-close-popup flat no-caps color="primary" :label="t('common.cancel')" />
                    </div>
                  </q-date>
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>
        </div>

        <div class="form__actions">
          <q-btn flat no-caps :label="t('common.cancel')" @click="router.back()" />
          <q-btn
            type="submit"
            color="primary"
            unelevated
            no-caps
            :label="isEdit ? t('common.save') : t('common.create')"
            :loading="tasks.saving"
          />
        </div>
      </q-form>
    </div>
  </div>
</template>

<style scoped lang="scss">
.form {
  padding: 22px;
  max-width: 820px;
  position: relative;
  min-height: 200px;
}

.form__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 18px 20px;
}

.form__actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 22px;
}
</style>
