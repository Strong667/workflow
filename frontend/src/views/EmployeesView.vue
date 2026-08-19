<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useDebounceFn } from '@vueuse/core'
import { useQuasar, type QTableColumn, type QTableProps } from 'quasar'
import EmptyState from '@/components/EmptyState.vue'
import TableSkeleton from '@/components/TableSkeleton.vue'
import { apiMessage } from '@/api/client'
import { formatDate } from '@/composables/useTaskMeta'
import { useAuthStore } from '@/stores/auth'
import { useDepartmentsStore } from '@/stores/departments'
import { useEmployeesStore } from '@/stores/employees'
import type { Employee } from '@/types'

const { t } = useI18n()
const router = useRouter()
const $q = useQuasar()
const auth = useAuthStore()
const employees = useEmployeesStore()
const departments = useDepartmentsStore()

const search = ref(employees.filters.search ?? '')
const canManage = auth.can('admin', 'manager')

const columns = computed<QTableColumn<Employee>[]>(() => [
  { name: 'employee', label: t('employees.title'), field: 'full_name', align: 'left', style: 'min-width: 230px' },
  { name: 'position', label: t('employees.position'), field: 'position', align: 'left', sortable: true },
  { name: 'department', label: t('employees.department'), field: 'department_id', align: 'left' },
  { name: 'phone', label: t('employees.phone'), field: 'phone', align: 'left' },
  { name: 'hire_date', label: t('employees.hireDate'), field: 'hire_date', align: 'left', sortable: true },
  { name: 'tasks', label: t('employees.tasksCount'), field: 'tasks_count', align: 'center' },
  { name: 'actions', label: t('common.actions'), field: 'id', align: 'right' },
])

/**
 * q-table в серверном режиме ведёт собственное состояние пагинации,
 * поэтому связываем его через v-model и синхронизируем после каждой загрузки.
 */
const pagination = ref({
  page: employees.filters.page ?? 1,
  rowsPerPage: employees.filters.per_page ?? 10,
  rowsNumber: 0,
  sortBy: '',
  descending: true,
})

function syncPagination(): void {
  pagination.value.rowsNumber = employees.total
  pagination.value.page = employees.filters.page ?? 1
}

// Поиск отправляется на сервер не чаще раза в 400 мс.
const debouncedSearch = useDebounceFn(async (value: string) => {
  employees.setFilter('search', value)
  await employees.fetch()
  syncPagination()
}, 400)

watch(search, (value) => debouncedSearch(value))

onMounted(async () => {
  await Promise.all([employees.fetch(), departments.fetchOptions()])
  syncPagination()
})

async function onDepartmentChange(value: number | null): Promise<void> {
  employees.setFilter('department_id', value)
  await employees.fetch()
  syncPagination()
}

/** q-table отдаёт страницу и сортировку одним событием — переносим их в стор. */
async function onRequest(props: Parameters<NonNullable<QTableProps['onRequest']>>[0]): Promise<void> {
  const { page, sortBy, descending } = props.pagination
  pagination.value = { ...pagination.value, page, sortBy: sortBy ?? '', descending }

  employees.setFilter('sort', sortBy || 'created_at')
  employees.setFilter('direction', descending ? 'desc' : 'asc')
  employees.setFilter('page', page)
  await employees.fetch()
  syncPagination()
}

async function resetFilters(): Promise<void> {
  search.value = ''
  employees.resetFilters()
  pagination.value = { ...pagination.value, page: 1, sortBy: '', descending: true }
  await employees.fetch()
  syncPagination()
}

function remove(employee: Employee): void {
  $q.dialog({
    title: t('common.confirm'),
    message: t('employees.deleteConfirm', { name: employee.full_name }),
    cancel: { label: t('common.cancel'), flat: true, noCaps: true },
    ok: { label: t('common.delete'), color: 'negative', unelevated: true, noCaps: true },
    persistent: true,
  }).onOk(async () => {
    try {
      await employees.remove(employee.id)
      $q.notify({ type: 'positive', message: t('common.deleted') })
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
        <h1 class="wf-page__title">{{ t('employees.title') }}</h1>
        <p class="wf-page__subtitle">{{ t('employees.subtitle') }} · {{ t('common.total') }}: {{ employees.total }}</p>
      </div>
      <q-btn
        v-if="canManage"
        color="primary"
        unelevated
        no-caps
        icon="add"
        :label="t('employees.create')"
        @click="router.push({ name: 'employees.create' })"
      />
    </div>

    <div class="wf-card filters">
      <q-input
        v-model="search"
        outlined
        dense
        clearable
        :placeholder="t('employees.searchPlaceholder')"
        class="filters__search"
      >
        <template #prepend><q-icon name="search" /></template>
      </q-input>

      <q-select
        :model-value="employees.filters.department_id"
        :options="departments.options"
        option-label="name"
        option-value="id"
        emit-value
        map-options
        clearable
        outlined
        dense
        :label="t('employees.filterDepartment')"
        class="filters__select"
        @update:model-value="onDepartmentChange"
      />

      <q-btn flat no-caps :label="t('common.reset')" @click="resetFilters" />
    </div>

    <div class="wf-card table-wrap">
      <TableSkeleton v-if="employees.loading && !employees.items.length" :rows="6" :columns="6" />

      <q-table
        v-else-if="employees.items.length"
        :rows="employees.items"
        :columns="columns"
        row-key="id"
        flat
        :loading="employees.loading"
        v-model:pagination="pagination"
        :rows-per-page-options="[10]"
        @request="onRequest"
      >
        <template #body-cell-employee="props">
          <q-td :props="props">
            <div class="person">
              <q-avatar size="34px" color="primary" text-color="white">
                <img v-if="props.row.avatar" :src="props.row.avatar" alt="" />
                <template v-else>{{ props.row.first_name[0] }}{{ props.row.last_name[0] }}</template>
              </q-avatar>
              <div class="person__info">
                <router-link :to="{ name: 'employees.show', params: { id: props.row.id } }" class="person__name">
                  {{ props.row.full_name }}
                </router-link>
                <span class="wf-muted person__email">{{ props.row.email }}</span>
              </div>
            </div>
          </q-td>
        </template>

        <template #body-cell-position="props">
          <q-td :props="props">{{ props.row.position ?? '—' }}</q-td>
        </template>

        <template #body-cell-department="props">
          <q-td :props="props">
            <q-chip v-if="props.row.department" dense square outline color="primary" :label="props.row.department.name" />
            <span v-else class="wf-muted">{{ t('employees.noDepartment') }}</span>
          </q-td>
        </template>

        <template #body-cell-phone="props">
          <q-td :props="props">{{ props.row.phone ?? '—' }}</q-td>
        </template>

        <template #body-cell-hire_date="props">
          <q-td :props="props">{{ formatDate(props.row.hire_date) }}</q-td>
        </template>

        <template #body-cell-tasks="props">
          <q-td :props="props">
            <q-badge outline color="grey-7" :label="props.row.tasks_count ?? 0" />
          </q-td>
        </template>

        <template #body-cell-actions="props">
          <q-td :props="props">
            <q-btn flat round dense icon="visibility" @click="router.push({ name: 'employees.show', params: { id: props.row.id } })">
              <q-tooltip>{{ t('employees.profile') }}</q-tooltip>
            </q-btn>
            <q-btn
              v-if="canManage"
              flat
              round
              dense
              icon="edit"
              @click="router.push({ name: 'employees.edit', params: { id: props.row.id } })"
            >
              <q-tooltip>{{ t('common.edit') }}</q-tooltip>
            </q-btn>
            <q-btn v-if="canManage" flat round dense color="negative" icon="delete" @click="remove(props.row)">
              <q-tooltip>{{ t('common.delete') }}</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>

      <EmptyState v-else :text="t('employees.empty')" icon="groups" />
    </div>
  </div>
</template>

<style scoped lang="scss">
.filters {
  padding: 14px;
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  align-items: center;
}

.filters__search {
  max-width: 320px;
  flex: 1 1 240px;
}

.filters__select {
  width: 220px;
}

.table-wrap {
  padding: 8px 12px 12px;
  overflow: hidden;
}

.person {
  display: flex;
  align-items: center;
  gap: 10px;
}

.person__info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.person__name {
  font-weight: 600;
  font-size: 13.5px;
  color: var(--q-primary);
}

.person__email {
  font-size: 11.5px;
}
</style>
