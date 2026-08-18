<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage } from 'element-plus'
import EmptyState from '@/components/EmptyState.vue'
import TableSkeleton from '@/components/TableSkeleton.vue'
import { activityApi } from '@/api'
import { apiMessage } from '@/api/client'
import { formatDateTime } from '@/composables/useTaskMeta'
import type { ActivityLog } from '@/types'

const { t } = useI18n()

const logs = ref<ActivityLog[]>([])
const loading = ref(true)
const total = ref(0)
const page = ref(1)
const perPage = ref(20)

const filters = reactive<{ action: string | null; entity: string | null; range: [string, string] | null }>({
  action: null,
  entity: null,
  range: null,
})

const actions = ['create', 'update', 'delete', 'move', 'login', 'logout']
const entities = ['Employee', 'Task', 'Department', 'User']

const actionTypes: Record<string, 'success' | 'primary' | 'danger' | 'warning' | 'info'> = {
  create: 'success',
  update: 'primary',
  delete: 'danger',
  move: 'warning',
  login: 'info',
  logout: 'info',
}

async function fetch(): Promise<void> {
  loading.value = true
  try {
    const response = await activityApi.list({
      action: filters.action ?? undefined,
      entity: filters.entity ?? undefined,
      date_from: filters.range?.[0],
      date_to: filters.range?.[1],
      page: page.value,
    })
    logs.value = response.data
    total.value = response.meta.total
    perPage.value = response.meta.per_page
  } catch (error) {
    ElMessage.error(apiMessage(error))
  } finally {
    loading.value = false
  }
}

function applyFilters(): void {
  page.value = 1
  void fetch()
}

function reset(): void {
  filters.action = null
  filters.entity = null
  filters.range = null
  applyFilters()
}

onMounted(fetch)
</script>

<template>
  <div class="wf-page">
    <div class="wf-page__header">
      <div>
        <h1 class="wf-page__title">{{ t('activity.title') }}</h1>
        <p class="wf-page__subtitle">{{ t('activity.subtitle') }} · {{ t('common.total') }}: {{ total }}</p>
      </div>
    </div>

    <div class="wf-card filters">
      <el-select
        v-model="filters.action"
        :placeholder="t('activity.filterAction')"
        clearable
        class="filters__select"
        @change="applyFilters"
      >
        <el-option v-for="item in actions" :key="item" :label="t(`activity.actions.${item}`)" :value="item" />
      </el-select>

      <el-select
        v-model="filters.entity"
        :placeholder="t('activity.filterEntity')"
        clearable
        class="filters__select"
        @change="applyFilters"
      >
        <el-option v-for="item in entities" :key="item" :label="item" :value="item" />
      </el-select>

      <el-date-picker
        v-model="filters.range"
        type="daterange"
        value-format="YYYY-MM-DD"
        :start-placeholder="t('common.from')"
        :end-placeholder="t('common.to')"
        @change="applyFilters"
      />

      <el-button text @click="reset">{{ t('common.reset') }}</el-button>
    </div>

    <div class="wf-card table-wrap">
      <TableSkeleton v-if="loading" :rows="8" :columns="4" />

      <template v-else-if="logs.length">
        <el-table :data="logs" style="width: 100%">
          <el-table-column :label="t('activity.date')" width="170">
            <template #default="{ row }: { row: ActivityLog }">{{ formatDateTime(row.created_at) }}</template>
          </el-table-column>

          <el-table-column :label="t('activity.user')" min-width="170">
            <template #default="{ row }: { row: ActivityLog }">
              <div class="user">
                <el-avatar :size="26" :src="row.user?.avatar ?? undefined">
                  {{ (row.user?.name ?? 'S')[0] }}
                </el-avatar>
                <span>{{ row.user?.name ?? t('activity.system') }}</span>
              </div>
            </template>
          </el-table-column>

          <el-table-column :label="t('activity.action')" width="140">
            <template #default="{ row }: { row: ActivityLog }">
              <el-tag size="small" :type="actionTypes[row.action] ?? 'info'" effect="light" round>
                {{ t(`activity.actions.${row.action}`, row.action) }}
              </el-tag>
            </template>
          </el-table-column>

          <el-table-column :label="t('activity.entity')" width="130">
            <template #default="{ row }: { row: ActivityLog }">
              <span class="wf-muted">{{ row.entity }}{{ row.entity_id ? ` #${row.entity_id}` : '' }}</span>
            </template>
          </el-table-column>

          <el-table-column :label="t('activity.description')" min-width="240">
            <template #default="{ row }: { row: ActivityLog }">{{ row.description ?? '—' }}</template>
          </el-table-column>
        </el-table>

        <div class="pagination">
          <el-pagination
            layout="prev, pager, next"
            background
            :total="total"
            :page-size="perPage"
            :current-page="page"
            @current-change="(value: number) => { page = value; fetch() }"
          />
        </div>
      </template>

      <EmptyState v-else :text="t('activity.empty')" icon="Clock" />
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

.filters__select {
  width: 190px;
}

.table-wrap {
  padding: 8px 12px 12px;
}

.user {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
}

.pagination {
  display: flex;
  justify-content: flex-end;
  padding: 14px 4px 4px;
}
</style>
