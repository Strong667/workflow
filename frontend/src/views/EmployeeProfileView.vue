<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import EmptyState from '@/components/EmptyState.vue'
import { apiMessage } from '@/api/client'
import { formatDate, useTaskMeta } from '@/composables/useTaskMeta'
import { useAuthStore } from '@/stores/auth'
import { useEmployeesStore } from '@/stores/employees'
import type { Task } from '@/types'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const confirm = useConfirm()
const toast = useToast()
const auth = useAuthStore()
const employees = useEmployeesStore()
const { statusLabel, statusSeverity, priorityLabel, prioritySeverity } = useTaskMeta()

const loading = ref(true)
const employee = computed(() => employees.current)
const canManage = auth.can('admin', 'manager')

const details = computed(() => [
  { label: t('employees.email'), value: employee.value?.email ?? '—' },
  { label: t('employees.phone'), value: employee.value?.phone ?? '—' },
  { label: t('employees.hireDate'), value: formatDate(employee.value?.hire_date) },
  { label: t('employees.department'), value: employee.value?.department?.name ?? t('employees.noDepartment') },
])

onMounted(async () => {
  try {
    await employees.fetchOne(Number(route.params.id))
  } catch (error) {
    toast.add({ severity: 'error', summary: apiMessage(error, t('employees.notFound')), life: 4000 })
    await router.push({ name: 'employees' })
  } finally {
    loading.value = false
  }
})

function remove(): void {
  if (!employee.value) return

  confirm.require({
    header: t('common.confirm'),
    message: t('employees.deleteConfirm', { name: employee.value.full_name }),
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: t('common.delete'),
    rejectLabel: t('common.cancel'),
    acceptProps: { severity: 'danger' },
    rejectProps: { severity: 'secondary', outlined: true },
    accept: async () => {
      try {
        await employees.remove(employee.value!.id)
        toast.add({ severity: 'success', summary: t('common.deleted'), life: 3000 })
        await router.push({ name: 'employees' })
      } catch (error) {
        toast.add({ severity: 'error', summary: apiMessage(error), life: 4000 })
      }
    },
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
        <Button
          icon="pi pi-arrow-left"
          :label="t('common.back')"
          severity="secondary"
          outlined
          @click="router.push({ name: 'employees' })"
        />
        <Button
          v-if="canManage"
          icon="pi pi-pencil"
          :label="t('common.edit')"
          severity="secondary"
          outlined
          @click="router.push({ name: 'employees.edit', params: { id: route.params.id } })"
        />
        <Button v-if="canManage" icon="pi pi-trash" :label="t('common.delete')" severity="danger" outlined @click="remove" />
      </div>
    </div>

    <div v-if="loading" class="wf-card loading"><ProgressSpinner style="width: 42px; height: 42px" /></div>

    <div v-else-if="employee" class="profile">
      <section class="wf-card profile__card">
        <Avatar
          :image="employee.avatar ?? undefined"
          :label="employee.avatar ? undefined : `${employee.first_name[0]}${employee.last_name[0]}`"
          shape="circle"
          size="xlarge"
        />
        <h2 class="profile__name">{{ employee.full_name }}</h2>
        <p class="wf-muted profile__position">{{ employee.position ?? '—' }}</p>
        <Tag v-if="employee.department" :value="employee.department.name" severity="secondary" rounded />

        <ul class="details">
          <li v-for="row in details" :key="row.label" class="details__row">
            <span class="wf-muted details__label">{{ row.label }}</span>
            <span class="details__value">{{ row.value }}</span>
          </li>
        </ul>
      </section>

      <section class="wf-card profile__tasks">
        <h3 class="profile__tasks-title">
          {{ t('employees.tasksOf') }}
          <Badge :value="employee.tasks?.length ?? 0" severity="secondary" />
        </h3>

        <DataTable v-if="employee.tasks?.length" :value="employee.tasks" data-key="id">
          <Column field="title" :header="t('tasks.titleField')" style="min-width: 220px" />
          <Column :header="t('tasks.status')" style="width: 150px">
            <template #body="{ data }: { data: Task }">
              <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" rounded />
            </template>
          </Column>
          <Column :header="t('tasks.priority')" style="width: 140px">
            <template #body="{ data }: { data: Task }">
              <Tag :value="priorityLabel(data.priority)" :severity="prioritySeverity(data.priority)" rounded />
            </template>
          </Column>
          <Column :header="t('tasks.deadline')" style="width: 130px">
            <template #body="{ data }: { data: Task }">
              <span :class="{ overdue: data.is_overdue }">{{ formatDate(data.deadline) }}</span>
            </template>
          </Column>
        </DataTable>

        <EmptyState v-else :text="t('tasks.empty')" icon="pi pi-list-check" />
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
  margin: 18px 0 0;
  padding: 0;
  list-style: none;
  text-align: left;
}

.details__row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid var(--wf-border);
  font-size: 13px;

  &:last-child {
    border-bottom: none;
  }
}

.details__value {
  font-weight: 550;
  text-align: right;
  word-break: break-word;
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

.overdue {
  color: var(--p-red-500);
}

@media (max-width: 900px) {
  .profile {
    grid-template-columns: 1fr;
  }
}
</style>
