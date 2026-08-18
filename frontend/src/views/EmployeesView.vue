<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useDebounceFn } from '@vueuse/core'
import { ElMessage, ElMessageBox } from 'element-plus'
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

function onSortChange({ prop, order }: { prop: string; order: string | null }): void {
  employees.setFilter('sort', order ? prop : 'created_at')
  employees.setFilter('direction', order === 'ascending' ? 'asc' : 'desc')
  void employees.fetch()
}

function onPageChange(page: number): void {
  employees.setFilter('page', page)
  void employees.fetch()
}

function resetFilters(): void {
  search.value = ''
  employees.resetFilters()
  void employees.fetch()
}

async function remove(employee: Employee): Promise<void> {
  try {
    await ElMessageBox.confirm(
      t('employees.deleteConfirm', { name: employee.full_name }),
      t('common.confirm'),
      { type: 'warning', confirmButtonText: t('common.delete'), cancelButtonText: t('common.cancel') },
    )
  } catch {
    return
  }

  try {
    await employees.remove(employee.id)
    ElMessage.success(t('common.deleted'))
  } catch (error) {
    ElMessage.error(apiMessage(error))
  }
}
</script>

<template>
  <div class="wf-page">
    <div class="wf-page__header">
      <div>
        <h1 class="wf-page__title">{{ t('employees.title') }}</h1>
        <p class="wf-page__subtitle">{{ t('employees.subtitle') }} · {{ t('common.total') }}: {{ employees.total }}</p>
      </div>
      <el-button v-if="canManage" type="primary" icon="Plus" @click="router.push({ name: 'employees.create' })">
        {{ t('employees.create') }}
      </el-button>
    </div>

    <div class="wf-card filters">
      <el-input
        v-model="search"
        :placeholder="t('employees.searchPlaceholder')"
        clearable
        class="filters__search"
      >
        <template #prefix><el-icon><Search /></el-icon></template>
      </el-input>

      <el-select
        :model-value="employees.filters.department_id"
        :placeholder="t('employees.filterDepartment')"
        clearable
        class="filters__select"
        @update:model-value="onDepartmentChange"
      >
        <el-option v-for="item in departments.options" :key="item.id" :label="item.name" :value="item.id" />
      </el-select>

      <el-button text @click="resetFilters">{{ t('common.reset') }}</el-button>
    </div>

    <div class="wf-card table-wrap">
      <TableSkeleton v-if="employees.loading" :rows="6" :columns="6" />

      <template v-else-if="employees.items.length">
        <el-table :data="employees.items" style="width: 100%" @sort-change="onSortChange">
          <el-table-column :label="t('employees.title')" min-width="230">
            <template #default="{ row }: { row: Employee }">
              <div class="person">
                <el-avatar :size="34" :src="row.avatar ?? undefined">
                  {{ row.first_name[0] }}{{ row.last_name[0] }}
                </el-avatar>
                <div class="person__info">
                  <router-link :to="{ name: 'employees.show', params: { id: row.id } }" class="person__name">
                    {{ row.full_name }}
                  </router-link>
                  <span class="wf-muted person__email">{{ row.email }}</span>
                </div>
              </div>
            </template>
          </el-table-column>

          <el-table-column prop="position" :label="t('employees.position')" sortable="custom" min-width="150">
            <template #default="{ row }: { row: Employee }">{{ row.position ?? '—' }}</template>
          </el-table-column>

          <el-table-column :label="t('employees.department')" min-width="140">
            <template #default="{ row }: { row: Employee }">
              <el-tag v-if="row.department" size="small" effect="plain">{{ row.department.name }}</el-tag>
              <span v-else class="wf-muted">{{ t('employees.noDepartment') }}</span>
            </template>
          </el-table-column>

          <el-table-column :label="t('employees.phone')" min-width="140">
            <template #default="{ row }: { row: Employee }">{{ row.phone ?? '—' }}</template>
          </el-table-column>

          <el-table-column prop="hire_date" :label="t('employees.hireDate')" sortable="custom" min-width="120">
            <template #default="{ row }: { row: Employee }">{{ formatDate(row.hire_date) }}</template>
          </el-table-column>

          <el-table-column :label="t('employees.tasksCount')" width="100" align="center">
            <template #default="{ row }: { row: Employee }">
              <el-badge :value="row.tasks_count ?? 0" type="info" />
            </template>
          </el-table-column>

          <el-table-column :label="t('common.actions')" width="130" align="right" fixed="right">
            <template #default="{ row }: { row: Employee }">
              <el-button text circle @click="router.push({ name: 'employees.show', params: { id: row.id } })">
                <el-icon><View /></el-icon>
              </el-button>
              <el-button
                v-if="canManage"
                text
                circle
                @click="router.push({ name: 'employees.edit', params: { id: row.id } })"
              >
                <el-icon><Edit /></el-icon>
              </el-button>
              <el-button v-if="canManage" text circle type="danger" @click="remove(row)">
                <el-icon><Delete /></el-icon>
              </el-button>
            </template>
          </el-table-column>
        </el-table>

        <div class="pagination">
          <el-pagination
            layout="prev, pager, next"
            :total="employees.total"
            :page-size="employees.filters.per_page"
            :current-page="employees.filters.page"
            background
            @current-change="onPageChange"
          />
        </div>
      </template>

      <EmptyState v-else :text="t('employees.empty')" icon="UserFilled" />
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
  width: 200px;
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
  color: var(--el-color-primary);
}

.person__email {
  font-size: 11.5px;
}

.pagination {
  display: flex;
  justify-content: flex-end;
  padding: 14px 4px 4px;
}
</style>
