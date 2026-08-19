<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import Dialog from 'primevue/dialog'
import Paginator from 'primevue/paginator'
import { useDebounceFn } from '@vueuse/core'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import type { MenuItem } from 'primevue/menuitem'
import type { PageState } from 'primevue/paginator'
import EmptyState from '@/components/EmptyState.vue'
import TableSkeleton from '@/components/TableSkeleton.vue'
import { apiMessage } from '@/api/client'
import { rules, useValidation } from '@/composables/useValidation'
import { useAuthStore } from '@/stores/auth'
import { useDepartmentsStore } from '@/stores/departments'
import type { Department } from '@/types'

const { t } = useI18n()
const confirm = useConfirm()
const toast = useToast()
const auth = useAuthStore()
const departments = useDepartmentsStore()

const canManage = auth.can('admin', 'manager')
const search = ref('')
const dialogVisible = ref(false)
const editing = ref<Department | null>(null)
const saving = ref(false)
const menu = ref()
const menuTarget = ref<Department | null>(null)

const form = reactive({ name: '', description: '' })
const { errors, validate, clear } = useValidation(form, {
  name: [rules.required(t('common.requiredField'))],
})

const menuItems = computed<MenuItem[]>(() => [
  { label: t('common.edit'), icon: 'pi pi-pencil', command: () => menuTarget.value && openEdit(menuTarget.value) },
  { separator: true },
  { label: t('common.delete'), icon: 'pi pi-trash', command: () => menuTarget.value && remove(menuTarget.value) },
])

const debouncedSearch = useDebounceFn((value: string) => {
  void departments.fetch({ search: value, page: 1 })
}, 400)

watch(search, (value) => debouncedSearch(value))

onMounted(() => {
  void departments.fetch()
})

function openMenu(event: Event, department: Department): void {
  menuTarget.value = department
  menu.value.toggle(event)
}

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
      toast.add({ severity: 'success', summary: t('common.saved'), life: 3000 })
    } else {
      await departments.create(form)
      toast.add({ severity: 'success', summary: t('common.created'), life: 3000 })
    }
    dialogVisible.value = false
  } catch (error) {
    toast.add({ severity: 'error', summary: apiMessage(error), life: 4000 })
  } finally {
    saving.value = false
  }
}

function remove(department: Department): void {
  confirm.require({
    header: t('common.confirm'),
    message: t('departments.deleteConfirm', { name: department.name }),
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: t('common.delete'),
    rejectLabel: t('common.cancel'),
    acceptProps: { severity: 'danger' },
    rejectProps: { severity: 'secondary', outlined: true },
    accept: async () => {
      try {
        await departments.remove(department.id)
        toast.add({ severity: 'success', summary: t('common.deleted'), life: 3000 })
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
        <h1 class="wf-page__title">{{ t('departments.title') }}</h1>
        <p class="wf-page__subtitle">
          {{ t('departments.subtitle') }} · {{ t('common.total') }}: {{ departments.total }}
        </p>
      </div>
      <Button v-if="canManage" icon="pi pi-plus" :label="t('departments.create')" @click="openCreate" />
    </div>

    <div class="wf-card filters">
      <IconField class="filters__search">
        <InputIcon class="pi pi-search" />
        <InputText v-model="search" :placeholder="t('common.search')" fluid />
      </IconField>
    </div>

    <TableSkeleton v-if="departments.loading" :rows="4" :columns="3" class="wf-card skeleton-card" />

    <div v-else-if="departments.items.length" class="wf-grid cards">
      <article v-for="department in departments.items" :key="department.id" class="wf-card department">
        <div class="department__head">
          <div class="department__icon"><i class="pi pi-building" /></div>
          <h3 class="department__name">{{ department.name }}</h3>
          <Button
            v-if="canManage"
            icon="pi pi-ellipsis-v"
            severity="secondary"
            text
            rounded
            size="small"
            :aria-label="t('common.actions')"
            @click="openMenu($event, department)"
          />
        </div>

        <p class="department__description wf-muted">{{ department.description ?? '—' }}</p>

        <router-link :to="{ name: 'employees' }" class="department__footer">
          <i class="pi pi-users" />
          {{ t('departments.employeesCount') }}: <b>{{ department.employees_count ?? 0 }}</b>
        </router-link>
      </article>
    </div>

    <EmptyState v-else :text="t('departments.empty')" icon="pi pi-building" />

    <Menu ref="menu" :model="menuItems" :popup="true" />

    <Paginator
      v-if="departments.lastPage > 1"
      class="pagination"
      :rows="15"
      :total-records="departments.total"
      :first="(departments.page - 1) * 15"
      @page="(event: PageState) => departments.fetch({ search, page: event.page + 1 })"
    />

    <Dialog
      v-model:visible="dialogVisible"
      :header="editing ? t('departments.editTitle') : t('departments.createTitle')"
      modal
      :style="{ width: '460px' }"
    >
      <div class="wf-field">
        <label for="department-name" class="wf-field__label">{{ t('departments.name') }}</label>
        <InputText id="department-name" v-model="form.name" :invalid="Boolean(errors.name)" fluid autofocus />
        <small v-if="errors.name" class="wf-field__error">{{ errors.name }}</small>
      </div>

      <div class="wf-field">
        <label for="department-description" class="wf-field__label">{{ t('departments.description') }}</label>
        <Textarea id="department-description" v-model="form.description" rows="3" fluid />
      </div>

      <template #footer>
        <Button :label="t('common.cancel')" severity="secondary" outlined @click="dialogVisible = false" />
        <Button :label="t('common.save')" :loading="saving" @click="submit" />
      </template>
    </Dialog>
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
  background: color-mix(in srgb, var(--p-primary-color) 14%, transparent);
  color: var(--p-primary-color);
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
</style>
