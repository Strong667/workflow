<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useQuasar, type QTableColumn, type QTableProps } from 'quasar'
import EmptyState from '@/components/EmptyState.vue'
import TableSkeleton from '@/components/TableSkeleton.vue'
import { activityApi } from '@/api'
import { apiMessage } from '@/api/client'
import { formatDateTime, fromQuasarDate } from '@/composables/useTaskMeta'
import type { ActivityLog } from '@/types'

const { t } = useI18n()
const $q = useQuasar()

const logs = ref<ActivityLog[]>([])
const loading = ref(true)
const total = ref(0)
const page = ref(1)
const perPage = ref(20)

const filters = reactive<{ action: string | null; entity: string | null; range: { from: string; to: string } | null }>({
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

const actionTones: Record<string, string> = {
  create: 'positive',
  update: 'info',
  delete: 'negative',
  move: 'warning',
  login: 'grey-6',
  logout: 'grey-6',
}

const columns = computed<QTableColumn<ActivityLog>[]>(() => [
  { name: 'created_at', label: t('activity.date'), field: 'created_at', align: 'left', style: 'width: 175px' },
  { name: 'user', label: t('activity.user'), field: 'user_id', align: 'left' },
  { name: 'action', label: t('activity.action'), field: 'action', align: 'left' },
  { name: 'entity', label: t('activity.entity'), field: 'entity', align: 'left' },
  { name: 'description', label: t('activity.description'), field: 'description', align: 'left' },
])

/** q-table ведёт своё состояние пагинации — связываем через v-model. */
const pagination = ref({ page: 1, rowsPerPage: 20, rowsNumber: 0 })

const rangeLabel = computed(() =>
  filters.range ? `${filters.range.from} — ${filters.range.to}` : '',
)

async function fetch(): Promise<void> {
  loading.value = true
  try {
    const response = await activityApi.list({
      action: filters.action ?? undefined,
      entity: filters.entity ?? undefined,
      date_from: fromQuasarDate(filters.range?.from) ?? undefined,
      date_to: fromQuasarDate(filters.range?.to) ?? undefined,
      page: page.value,
    })
    logs.value = response.data
    total.value = response.meta.total
    perPage.value = response.meta.per_page
    pagination.value = { page: page.value, rowsPerPage: response.meta.per_page, rowsNumber: response.meta.total }
  } catch (error) {
    $q.notify({ type: 'negative', message: apiMessage(error) })
  } finally {
    loading.value = false
  }
}

function applyFilters(): void {
  page.value = 1
  void fetch()
}

function onRequest(props: Parameters<NonNullable<QTableProps['onRequest']>>[0]): void {
  page.value = props.pagination.page
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
      <q-select
        v-model="filters.action"
        :options="actionOptions"
        option-label="label"
        option-value="value"
        emit-value
        map-options
        clearable
        outlined
        dense
        :label="t('activity.filterAction')"
        class="filters__select"
        @update:model-value="applyFilters"
      />

      <q-select
        v-model="filters.entity"
        :options="entityOptions"
        option-label="label"
        option-value="value"
        emit-value
        map-options
        clearable
        outlined
        dense
        :label="t('activity.filterEntity')"
        class="filters__select"
        @update:model-value="applyFilters"
      />

      <q-input
        :model-value="rangeLabel"
        outlined
        dense
        readonly
        clearable
        :label="`${t('common.from')} — ${t('common.to')}`"
        class="filters__range"
        @clear="() => { filters.range = null; applyFilters() }"
      >
        <template #append>
          <q-icon name="event" class="cursor-pointer">
            <q-popup-proxy cover transition-show="scale" transition-hide="scale">
              <q-date v-model="filters.range" range minimal @update:model-value="applyFilters">
                <div class="row items-center justify-end">
                  <q-btn v-close-popup flat no-caps color="primary" :label="t('common.cancel')" />
                </div>
              </q-date>
            </q-popup-proxy>
          </q-icon>
        </template>
      </q-input>

      <q-btn flat no-caps :label="t('common.reset')" @click="reset" />
    </div>

    <div class="wf-card table-wrap">
      <TableSkeleton v-if="loading && !logs.length" :rows="8" :columns="5" />

      <q-table
        v-else-if="logs.length"
        :rows="logs"
        :columns="columns"
        row-key="id"
        flat
        :loading="loading"
        v-model:pagination="pagination"
        :rows-per-page-options="[20]"
        @request="onRequest"
      >
        <template #body-cell-created_at="props">
          <q-td :props="props">{{ formatDateTime(props.row.created_at) }}</q-td>
        </template>

        <template #body-cell-user="props">
          <q-td :props="props">
            <div class="user">
              <q-avatar size="26px" color="primary" text-color="white">
                <img v-if="props.row.user?.avatar" :src="props.row.user.avatar" alt="" />
                <template v-else>{{ (props.row.user?.name ?? 'S')[0] }}</template>
              </q-avatar>
              <span>{{ props.row.user?.name ?? t('activity.system') }}</span>
            </div>
          </q-td>
        </template>

        <template #body-cell-action="props">
          <q-td :props="props">
            <q-chip
              dense
              square
              :color="actionTones[props.row.action] ?? 'grey-6'"
              text-color="white"
              :label="t(`activity.actions.${props.row.action}`, props.row.action)"
            />
          </q-td>
        </template>

        <template #body-cell-entity="props">
          <q-td :props="props">
            <span class="wf-muted">
              {{ props.row.entity }}{{ props.row.entity_id ? ` #${props.row.entity_id}` : '' }}
            </span>
          </q-td>
        </template>

        <template #body-cell-description="props">
          <q-td :props="props">{{ props.row.description ?? '—' }}</q-td>
        </template>
      </q-table>

      <EmptyState v-else :text="t('activity.empty')" icon="history" />
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
  width: 200px;
}

.filters__range {
  width: 250px;
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
</style>
