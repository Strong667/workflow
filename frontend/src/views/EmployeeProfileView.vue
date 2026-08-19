<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar, type QTableColumn } from 'quasar'
import EmptyState from '@/components/EmptyState.vue'
import { apiMessage } from '@/api/client'
import { formatDate, useTaskMeta } from '@/composables/useTaskMeta'
import { useAuthStore } from '@/stores/auth'
import { useEmployeesStore } from '@/stores/employees'
import type { Task } from '@/types'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const auth = useAuthStore()
const employees = useEmployeesStore()
const { statusLabel, statusTone, priorityLabel, priorityTone } = useTaskMeta()

const loading = ref(true)
const employee = computed(() => employees.current)
const canManage = auth.can('admin', 'manager')

const details = computed(() => [
  { label: t('employees.email'), value: employee.value?.email ?? '—' },
  { label: t('employees.phone'), value: employee.value?.phone ?? '—' },
  { label: t('employees.hireDate'), value: formatDate(employee.value?.hire_date) },
  { label: t('employees.department'), value: employee.value?.department?.name ?? t('employees.noDepartment') },
])

const taskColumns = computed<QTableColumn<Task>[]>(() => [
  { name: 'title', label: t('tasks.titleField'), field: 'title', align: 'left' },
  { name: 'status', label: t('tasks.status'), field: 'status', align: 'left' },
  { name: 'priority', label: t('tasks.priority'), field: 'priority', align: 'left' },
  { name: 'deadline', label: t('tasks.deadline'), field: 'deadline', align: 'left' },
])

onMounted(async () => {
  try {
    await employees.fetchOne(Number(route.params.id))
  } catch (error) {
    $q.notify({ type: 'negative', message: apiMessage(error, t('employees.notFound')) })
    await router.push({ name: 'employees' })
  } finally {
    loading.value = false
  }
})

function remove(): void {
  if (!employee.value) return

  $q.dialog({
    title: t('common.confirm'),
    message: t('employees.deleteConfirm', { name: employee.value.full_name }),
    cancel: { label: t('common.cancel'), flat: true, noCaps: true },
    ok: { label: t('common.delete'), color: 'negative', unelevated: true, noCaps: true },
    persistent: true,
  }).onOk(async () => {
    try {
      await employees.remove(employee.value!.id)
      $q.notify({ type: 'positive', message: t('common.deleted') })
      await router.push({ name: 'employees' })
    } catch (error) {
      $q.notify({ type: 'negative', message: apiMessage(error) })
    }
  })
}
</script>

<template>
  <div class="wf-page">
    <div class="wf-page__header">
      <div>
        <h1 class="wf-page__title">{{ t('employees.profile') }}</h1>
        <p class="wf-page__subtitle">{{ employee?.email }}</p>
      </div>
      <div class="wf-toolbar">
        <q-btn flat no-caps icon="arrow_back" :label="t('common.back')" @click="router.push({ name: 'employees' })" />
        <q-btn
          v-if="canManage"
          outline
          no-caps
          color="primary"
          icon="edit"
          :label="t('common.edit')"
          @click="router.push({ name: 'employees.edit', params: { id: route.params.id } })"
        />
        <q-btn v-if="canManage" outline no-caps color="negative" icon="delete" :label="t('common.delete')" @click="remove" />
      </div>
    </div>

    <div v-if="loading" class="wf-card loading"><q-spinner size="42px" color="primary" /></div>

    <div v-else-if="employee" class="profile">
      <section class="wf-card profile__card">
        <q-avatar size="82px" color="primary" text-color="white" class="profile__avatar">
          <img v-if="employee.avatar" :src="employee.avatar" alt="" />
          <template v-else>{{ employee.first_name[0] }}{{ employee.last_name[0] }}</template>
        </q-avatar>
        <h2 class="profile__name">{{ employee.full_name }}</h2>
        <p class="wf-muted profile__position">{{ employee.position ?? '—' }}</p>
        <q-chip v-if="employee.department" dense square outline color="primary" :label="employee.department.name" />

        <q-list separator class="details">
          <q-item v-for="row in details" :key="row.label" class="details__row">
            <q-item-section class="wf-muted">{{ row.label }}</q-item-section>
            <q-item-section side class="details__value">{{ row.value }}</q-item-section>
          </q-item>
        </q-list>
      </section>

      <section class="wf-card profile__tasks">
        <h3 class="profile__tasks-title">
          {{ t('employees.tasksOf') }}
          <q-badge outline color="grey-7" :label="employee.tasks?.length ?? 0" />
        </h3>

        <q-table
          v-if="employee.tasks?.length"
          :rows="employee.tasks"
          :columns="taskColumns"
          row-key="id"
          flat
          hide-pagination
          :rows-per-page-options="[0]"
        >
          <template #body-cell-status="props">
            <q-td :props="props">
              <q-chip dense square :color="statusTone(props.row.status)" text-color="white" :label="statusLabel(props.row.status)" />
            </q-td>
          </template>
          <template #body-cell-priority="props">
            <q-td :props="props">
              <q-chip dense square outline :color="priorityTone(props.row.priority)" :label="priorityLabel(props.row.priority)" />
            </q-td>
          </template>
          <template #body-cell-deadline="props">
            <q-td :props="props">
              <span :class="{ 'text-negative': props.row.is_overdue }">{{ formatDate(props.row.deadline) }}</span>
            </q-td>
          </template>
        </q-table>

        <EmptyState v-else :text="t('tasks.empty')" icon="task_alt" />
      </section>
    </div>
  </div>
</template>

<style scoped lang="scss">
.profile {
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 18px;
  align-items: start;
}

.profile__card {
  padding: 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 8px;
}

.profile__avatar {
  font-size: 26px;
}

.profile__name {
  margin: 6px 0 0;
  font-size: 18px;
  font-weight: 650;
}

.profile__position {
  margin: 0;
  font-size: 13px;
}

.details {
  width: 100%;
  margin-top: 18px;
  text-align: left;
  font-size: 13px;
}

.details__row {
  padding-left: 0;
  padding-right: 0;
  min-height: 42px;
}

.details__value {
  font-weight: 550;
  text-align: right;
}

.profile__tasks {
  padding: 18px;
}

.profile__tasks-title {
  margin: 0 0 14px;
  font-size: 14px;
  font-weight: 650;
  display: flex;
  align-items: center;
  gap: 8px;
}

.loading {
  display: grid;
  place-items: center;
  min-height: 280px;
}

@media (max-width: 900px) {
  .profile {
    grid-template-columns: 1fr;
  }
}
</style>
