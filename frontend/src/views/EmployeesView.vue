<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useDebounceFn } from '@vueuse/core'
import EmptyState from '@/components/EmptyState.vue'
import TableSkeleton from '@/components/TableSkeleton.vue'
import { apiMessage } from '@/api/client'
import { formatDate } from '@/composables/useTaskMeta'
import { useAuthStore } from '@/stores/auth'
import { useDepartmentsStore } from '@/stores/departments'
import { useEmployeesStore } from '@/stores/employees'
import type { Employee } from '@/types'
import { Column, DataTable, type DataTablePageEvent, type DataTableSortEvent } from '@/ui/lazy-components'
import { useConfirmDelete, useNotify } from '@/ui/feedback'

const { t } = useI18n()
const router = useRouter()
const confirmDelete = useConfirmDelete()
const notify = useNotify()
const auth = useAuthStore()
const employees = useEmployeesStore()
const departments = useDepartmentsStore()

const search = ref(employees.filters.search ?? '')
const canManage = auth.can('admin', 'manager')

// Поиск отправляется на сервер не чаще раза в 400 мс.
const debouncedSearch = useDebounceFn((value: string) => {
  employees.setFilter('search', value)
  void employees.fetch()
}, 400)

watch(search, (value) => debouncedSearch(value))

onMounted(async () => {
  await Promise.all([employees.fetch(), departments.fetchOptions()])
})

function onDepartmentChange(value: number | null): void {
  employees.setFilter('department_id', value)
  void employees.fetch()
}

function onPage(event: DataTablePageEvent): void {
  employees.setFilter('page', event.page + 1)
  void employees.fetch()
}

function onSort(event: DataTableSortEvent): void {
  employees.setFilter('sort', (event.sortField as string) || 'created_at')
  employees.setFilter('direction', event.sortOrder === 1 ? 'asc' : 'desc')
  void employees.fetch()
}

function resetFilters(): void {
  search.value = ''
  employees.resetFilters()
  void employees.fetch()
}

function remove(employee: Employee): void {
  confirmDelete(t('employees.deleteConfirm', { name: employee.full_name }), async () => {
      try {
        await employees.remove(employee.id)
        notify.success(t('common.deleted'))
      } catch (error) {
        notify.error(apiMessage(error))
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
      <Button
        v-if="canManage"
        icon="pi pi-plus"
        :label="t('employees.create')"
        @click="router.push({ name: 'employees.create' })"
      />
    </div>

    <div class="wf-card filters">
      <IconField class="filters__search">
        <InputIcon class="pi pi-search" />
        <InputText v-model="search" :placeholder="t('employees.searchPlaceholder')" fluid />
      </IconField>

      <Select
        :model-value="employees.filters.department_id"
        :options="departments.options"
        option-label="name"
        option-value="id"
        :placeholder="t('employees.filterDepartment')"
        show-clear
        class="filters__select"
        @update:model-value="onDepartmentChange"
      />

      <Button :label="t('common.reset')" severity="secondary" text @click="resetFilters" />
    </div>

    <div class="wf-card table-wrap">
      <TableSkeleton v-if="employees.loading" :rows="6" :columns="6" />

      <DataTable
        v-else-if="employees.items.length"
        :value="employees.items"
        lazy
        paginator
        removable-sort
        :rows="employees.filters.per_page"
        :total-records="employees.total"
        :first="((employees.filters.page ?? 1) - 1) * (employees.filters.per_page ?? 10)"
        data-key="id"
        @page="onPage"
        @sort="onSort"
      >
        <Column :header="t('employees.title')" style="min-width: 230px">
          <template #body="{ data }: { data: Employee }">
            <div class="person">
              <Avatar
                :image="data.avatar ?? undefined"
                :label="data.avatar ? undefined : `${data.first_name[0]}${data.last_name[0]}`"
                shape="circle"
              />
              <div class="person__info">
                <router-link :to="{ name: 'employees.show', params: { id: data.id } }" class="person__name">
                  {{ data.full_name }}
                </router-link>
                <span class="wf-muted person__email">{{ data.email }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column field="position" :header="t('employees.position')" sortable style="min-width: 150px">
          <template #body="{ data }: { data: Employee }">{{ data.position ?? '—' }}</template>
        </Column>

        <Column :header="t('employees.department')" style="min-width: 140px">
          <template #body="{ data }: { data: Employee }">
            <Tag v-if="data.department" :value="data.department.name" severity="secondary" rounded />
            <span v-else class="wf-muted">{{ t('employees.noDepartment') }}</span>
          </template>
        </Column>

        <Column :header="t('employees.phone')" style="min-width: 140px">
          <template #body="{ data }: { data: Employee }">{{ data.phone ?? '—' }}</template>
        </Column>

        <Column field="hire_date" :header="t('employees.hireDate')" sortable style="min-width: 120px">
          <template #body="{ data }: { data: Employee }">{{ formatDate(data.hire_date) }}</template>
        </Column>

        <Column :header="t('employees.tasksCount')" style="width: 100px" align="center">
          <template #body="{ data }: { data: Employee }">
            <Badge :value="data.tasks_count ?? 0" severity="secondary" />
          </template>
        </Column>

        <Column :header="t('common.actions')" style="width: 140px">
          <template #body="{ data }: { data: Employee }">
            <div class="row-actions">
              <Button
                icon="pi pi-eye"
                severity="secondary"
                text
                rounded
                v-tooltip.top="t('employees.profile')"
                @click="router.push({ name: 'employees.show', params: { id: data.id } })"
              />
              <Button
                v-if="canManage"
                icon="pi pi-pencil"
                severity="secondary"
                text
                rounded
                v-tooltip.top="t('common.edit')"
                @click="router.push({ name: 'employees.edit', params: { id: data.id } })"
              />
              <Button
                v-if="canManage"
                icon="pi pi-trash"
                severity="danger"
                text
                rounded
                v-tooltip.top="t('common.delete')"
                @click="remove(data)"
              />
            </div>
          </template>
        </Column>
      </DataTable>

      <EmptyState v-else :text="t('employees.empty')" icon="pi pi-users" />
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
  width: 210px;
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
  color: var(--p-primary-color);
}

.person__email {
  font-size: 11.5px;
}

.row-actions {
  display: flex;
  gap: 2px;
  justify-content: flex-end;
}
</style>
