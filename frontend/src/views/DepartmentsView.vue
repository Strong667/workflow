<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDebounceFn } from '@vueuse/core'
import { useQuasar } from 'quasar'
import EmptyState from '@/components/EmptyState.vue'
import TableSkeleton from '@/components/TableSkeleton.vue'
import { apiMessage } from '@/api/client'
import { rules, useValidation } from '@/composables/useValidation'
import { useAuthStore } from '@/stores/auth'
import { useDepartmentsStore } from '@/stores/departments'
import type { Department } from '@/types'

const { t } = useI18n()
const $q = useQuasar()
const auth = useAuthStore()
const departments = useDepartmentsStore()

const canManage = auth.can('admin', 'manager')
const search = ref('')
const dialogVisible = ref(false)
const editing = ref<Department | null>(null)
const saving = ref(false)

const form = reactive({ name: '', description: '' })
const { errors, validate, validateField, clear } = useValidation(form, {
  name: [rules.required(t('common.requiredField'))],
})

const debouncedSearch = useDebounceFn((value: string) => {
  void departments.fetch({ search: value, page: 1 })
}, 400)

watch(search, (value) => debouncedSearch(value ?? ''))

onMounted(() => {
  void departments.fetch()
})

function openCreate(): void {
  editing.value = null
  Object.assign(form, { name: '', description: '' })
  clear()
  dialogVisible.value = true
}

function openEdit(department: Department): void {
  editing.value = department
  Object.assign(form, { name: department.name, description: department.description ?? '' })
  clear()
  dialogVisible.value = true
}

async function submit(): Promise<void> {
  if (!validate()) return

  saving.value = true
  try {
    if (editing.value) {
      await departments.update(editing.value.id, form)
      $q.notify({ type: 'positive', message: t('common.saved') })
    } else {
      await departments.create(form)
      $q.notify({ type: 'positive', message: t('common.created') })
    }
    dialogVisible.value = false
  } catch (error) {
    $q.notify({ type: 'negative', message: apiMessage(error) })
  } finally {
    saving.value = false
  }
}

function remove(department: Department): void {
  $q.dialog({
    title: t('common.confirm'),
    message: t('departments.deleteConfirm', { name: department.name }),
    cancel: { label: t('common.cancel'), flat: true, noCaps: true },
    ok: { label: t('common.delete'), color: 'negative', unelevated: true, noCaps: true },
    persistent: true,
  }).onOk(async () => {
    try {
      await departments.remove(department.id)
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
        <h1 class="wf-page__title">{{ t('departments.title') }}</h1>
        <p class="wf-page__subtitle">
          {{ t('departments.subtitle') }} · {{ t('common.total') }}: {{ departments.total }}
        </p>
      </div>
      <q-btn
        v-if="canManage"
        color="primary"
        unelevated
        no-caps
        icon="add"
        :label="t('departments.create')"
        @click="openCreate"
      />
    </div>

    <div class="wf-card filters">
      <q-input v-model="search" outlined dense clearable :placeholder="t('common.search')" class="filters__search">
        <template #prepend><q-icon name="search" /></template>
      </q-input>
    </div>

    <TableSkeleton v-if="departments.loading" :rows="4" :columns="3" class="wf-card skeleton-card" />

    <div v-else-if="departments.items.length" class="wf-grid cards">
      <article v-for="department in departments.items" :key="department.id" class="wf-card department">
        <div class="department__head">
          <div class="department__icon"><q-icon name="apartment" size="20px" /></div>
          <h3 class="department__name">{{ department.name }}</h3>
          <q-btn v-if="canManage" flat round dense size="sm" icon="more_vert" :aria-label="t('common.actions')">
            <q-menu auto-close>
              <q-list style="min-width: 150px">
                <q-item clickable @click="openEdit(department)">
                  <q-item-section avatar><q-icon name="edit" size="18px" /></q-item-section>
                  <q-item-section>{{ t('common.edit') }}</q-item-section>
                </q-item>
                <q-separator />
                <q-item clickable class="text-negative" @click="remove(department)">
                  <q-item-section avatar><q-icon name="delete" size="18px" /></q-item-section>
                  <q-item-section>{{ t('common.delete') }}</q-item-section>
                </q-item>
              </q-list>
            </q-menu>
          </q-btn>
        </div>

        <p class="department__description wf-muted">{{ department.description ?? '—' }}</p>

        <router-link :to="{ name: 'employees' }" class="department__footer">
          <q-icon name="groups" size="16px" />
          {{ t('departments.employeesCount') }}: <b>{{ department.employees_count ?? 0 }}</b>
        </router-link>
      </article>
    </div>

    <EmptyState v-else :text="t('departments.empty')" icon="apartment" />

    <q-pagination
      v-if="departments.lastPage > 1"
      :model-value="departments.page"
      :max="departments.lastPage"
      direction-links
      boundary-numbers
      color="primary"
      class="pagination"
      @update:model-value="(page: number) => departments.fetch({ search, page })"
    />

    <q-dialog v-model="dialogVisible">
      <q-card class="dialog">
        <q-card-section class="dialog__header">
          <h3 class="dialog__title">{{ editing ? t('departments.editTitle') : t('departments.createTitle') }}</h3>
        </q-card-section>

        <q-card-section class="q-gutter-md">
          <q-input
            v-model="form.name"
            outlined
            autofocus
            :label="t('departments.name')"
            :error="Boolean(errors.name)"
            :error-message="errors.name"
            @blur="validateField('name')"
          />
          <q-input v-model="form.description" outlined type="textarea" rows="3" :label="t('departments.description')" />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn v-close-popup flat no-caps :label="t('common.cancel')" />
          <q-btn color="primary" unelevated no-caps :label="t('common.save')" :loading="saving" @click="submit" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<style scoped lang="scss">
.filters {
  padding: 14px;
}

.filters__search {
  max-width: 320px;
}

.cards {
  grid-template-columns: repeat(auto-fill, minmax(268px, 1fr));
}

.department {
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  transition: transform 0.15s ease, box-shadow 0.15s ease;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 24, 40, 0.08);
  }
}

.department__head {
  display: flex;
  align-items: center;
  gap: 10px;
}

.department__icon {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: grid;
  place-items: center;
  background: rgba(79, 70, 229, 0.14);
  color: var(--q-primary);
}

.department__name {
  margin: 0;
  font-size: 15px;
  font-weight: 650;
  flex: 1;
}

.department__description {
  margin: 0;
  font-size: 12.5px;
  min-height: 34px;
}

.department__footer {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12.5px;
  border-top: 1px solid var(--wf-border);
  padding-top: 10px;
}

.skeleton-card {
  padding: 18px;
}

.pagination {
  align-self: flex-end;
}

.dialog {
  width: 460px;
  max-width: 90vw;
}

.dialog__header {
  padding-bottom: 0;
}

.dialog__title {
  margin: 0;
  font-size: 16px;
  font-weight: 650;
}
</style>
