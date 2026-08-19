<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import EmptyState from '@/components/EmptyState.vue'
import TableSkeleton from '@/components/TableSkeleton.vue'
import { activityApi } from '@/api'
import { apiMessage } from '@/api/client'
import { formatDateTime, toDateString } from '@/composables/useTaskMeta'
import type { ActivityLog } from '@/types'
import { Column, DataTable, DatePicker, type DataTablePageEvent } from '@/ui/lazy-components'
import { useNotify } from '@/ui/feedback'

const { t } = useI18n()
const notify = useNotify()

const logs = ref<ActivityLog[]>([])
const loading = ref(true)
const total = ref(0)
const page = ref(1)
const perPage = ref(20)

const filters = reactive<{ action: string | null; entity: string | null; range: Date[] | null }>({
  action: null,
  entity: null,
  range: null,
})

const actionOptions = computed(() =>
  ['create', 'update', 'delete', 'move', 'login', 'logout'].map((value) => ({
    value,
    label: t(`activity.actions.${value}`),
  })),
)

const entityOptions = ['Employee', 'Task', 'Department', 'User'].map((value) => ({ value, label: value }))

const actionSeverities: Record<string, 'success' | 'info' | 'danger' | 'warn' | 'secondary'> = {
  create: 'success',
  update: 'info',
  delete: 'danger',
  move: 'warn',
  login: 'secondary',
  logout: 'secondary',
}

async function fetch(): Promise<void> {
  loading.value = true
  try {
    const response = await activityApi.list({
      action: filters.action ?? undefined,
      entity: filters.entity ?? undefined,
      date_from: toDateString(filters.range?.[0]) ?? undefined,
      date_to: toDateString(filters.range?.[1]) ?? undefined,
      page: page.value,
    })
    logs.value = response.data
    total.value = response.meta.total
    perPage.value = response.meta.per_page
  } catch (error) {
    notify.error(apiMessage(error))
  } finally {
    loading.value = false
  }
}

function applyFilters(): void {
  page.value = 1
  void fetch()
}

function onPage(event: DataTablePageEvent): void {
  page.value = event.page + 1
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
      <Select
        v-model="filters.action"
        :options="actionOptions"
        option-label="label"
        option-value="value"
        :placeholder="t('activity.filterAction')"
        show-clear
        class="filters__select"
        @change="applyFilters"
      />

      <Select
        v-model="filters.entity"
        :options="entityOptions"
        option-label="label"
        option-value="value"
        :placeholder="t('activity.filterEntity')"
        show-clear
        class="filters__select"
        @change="applyFilters"
      />

      <DatePicker
        v-model="filters.range"
        selection-mode="range"
        date-format="dd.mm.yy"
        show-icon
        :placeholder="`${t('common.from')} — ${t('common.to')}`"
        class="filters__range"
        @date-select="filters.range?.length === 2 && applyFilters()"
      />

      <Button :label="t('common.reset')" severity="secondary" text @click="reset" />
    </div>

    <div class="wf-card table-wrap">
      <TableSkeleton v-if="loading" :rows="8" :columns="5" />

      <DataTable
        v-else-if="logs.length"
        :value="logs"
        lazy
        paginator
        :rows="perPage"
        :total-records="total"
        :first="(page - 1) * perPage"
        data-key="id"
        @page="onPage"
      >
        <Column :header="t('activity.date')" style="width: 175px">
          <template #body="{ data }: { data: ActivityLog }">{{ formatDateTime(data.created_at) }}</template>
        </Column>

        <Column :header="t('activity.user')" style="min-width: 170px">
          <template #body="{ data }: { data: ActivityLog }">
            <div class="user">
              <Avatar
                :image="data.user?.avatar ?? undefined"
                :label="data.user?.avatar ? undefined : (data.user?.name ?? 'S')[0]"
                shape="circle"
                size="normal"
                class="user__avatar"
              />
              <span>{{ data.user?.name ?? t('activity.system') }}</span>
            </div>
          </template>
        </Column>

        <Column :header="t('activity.action')" style="width: 150px">
          <template #body="{ data }: { data: ActivityLog }">
            <Tag
              :value="t(`activity.actions.${data.action}`, data.action)"
              :severity="actionSeverities[data.action] ?? 'secondary'"
              rounded
            />
          </template>
        </Column>

        <Column :header="t('activity.entity')" style="width: 140px">
          <template #body="{ data }: { data: ActivityLog }">
            <span class="wf-muted">{{ data.entity }}{{ data.entity_id ? ` #${data.entity_id}` : '' }}</span>
          </template>
        </Column>

        <Column :header="t('activity.description')" style="min-width: 240px">
          <template #body="{ data }: { data: ActivityLog }">{{ data.description ?? '—' }}</template>
        </Column>
      </DataTable>

      <EmptyState v-else :text="t('activity.empty')" icon="pi pi-history" />
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
  width: 195px;
}

.filters__range {
  width: 260px;
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

.user__avatar {
  width: 1.7rem;
  height: 1.7rem;
  font-size: 0.7rem;
}
</style>
