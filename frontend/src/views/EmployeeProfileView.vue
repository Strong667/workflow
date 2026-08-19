<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import EmptyState from '@/components/EmptyState.vue'
import { apiMessage } from '@/api/client'
import { formatDate, useTaskMeta } from '@/composables/useTaskMeta'
import { useAuthStore } from '@/stores/auth'
import { employeesApi, usersApi } from '@/api'
import { rules, useValidation } from '@/composables/useValidation'
import { useEmployeesStore } from '@/stores/employees'
import type { Task, User } from '@/types'
import { Column, DataTable, Dialog, Password } from '@/ui/lazy-components'
import { useConfirmDelete, useNotify } from '@/ui/feedback'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const confirmDelete = useConfirmDelete()
const notify = useNotify()
const auth = useAuthStore()
const employees = useEmployeesStore()
const { statusLabel, statusSeverity, priorityLabel, prioritySeverity } = useTaskMeta()

const loading = ref(true)
const accessDialog = ref(false)
const grantingAccess = ref(false)
const accessForm = reactive({ password: '' })

const accessValidation = useValidation(accessForm, {
  password: [rules.required(t('auth.passwordRequired')), rules.minLength(6, t('auth.passwordMin'))],
})

const linkDialog = ref(false)
const linking = ref(false)
const freeAccounts = ref<User[]>([])
const selectedAccount = ref<number | null>(null)
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
    notify.error(apiMessage(error, t('employees.notFound')))
    await router.push({ name: 'employees' })
  } finally {
    loading.value = false
  }
})

/** Аккаунты без карточки — только их и можно привязать. */
async function openLinkDialog(): Promise<void> {
  selectedAccount.value = null
  linkDialog.value = true
  try {
    freeAccounts.value = (await usersApi.list({ unlinked: true })).data
  } catch (error) {
    notify.error(apiMessage(error))
  }
}

async function linkAccount(): Promise<void> {
  if (!employee.value || !selectedAccount.value) return

  linking.value = true
  try {
    employees.current = await employeesApi.linkAccount(employee.value.id, selectedAccount.value)
    notify.success(t('employees.accessGranted'))
    linkDialog.value = false
  } catch (error) {
    notify.error(apiMessage(error))
  } finally {
    linking.value = false
  }
}

function openAccessDialog(): void {
  accessForm.password = ''
  accessValidation.clear()
  accessDialog.value = true
}

/** Аккаунт заводится на email из карточки — данные не разъезжаются. */
async function grantAccess(): Promise<void> {
  if (!employee.value || !accessValidation.validate()) return

  grantingAccess.value = true
  try {
    employees.current = await employeesApi.grantAccess(employee.value.id, { password: accessForm.password })
    notify.success(t('employees.accessGranted'))
    accessDialog.value = false
  } catch (error) {
    notify.error(apiMessage(error))
  } finally {
    grantingAccess.value = false
  }
}

async function revokeAccess(): Promise<void> {
  if (!employee.value) return

  try {
    employees.current = await employeesApi.revokeAccess(employee.value.id)
    notify.success(t('employees.accessRevoked'))
  } catch (error) {
    notify.error(apiMessage(error))
  }
}

function remove(): void {
  if (!employee.value) return

  confirmDelete(t('employees.deleteConfirm', { name: employee.value.full_name }), async () => {
      try {
        await employees.remove(employee.value!.id)
        notify.success(t('common.deleted'))
        await router.push({ name: 'employees' })
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

      <section v-if="canManage" class="wf-card access">
        <h3 class="access__title">
          <i class="pi pi-key" />
          {{ t('employees.access') }}
        </h3>

        <template v-if="employee.account">
          <p class="access__text">
            {{ t('employees.accessActive') }}
            <b>{{ employee.account.email }}</b>
            <Tag :value="t(`roles.${employee.account.role}`)" severity="secondary" rounded class="access__role" />
          </p>
          <Button
            :label="t('employees.revokeAccess')"
            icon="pi pi-ban"
            severity="danger"
            outlined
            size="small"
            @click="revokeAccess"
          />
        </template>

        <template v-else>
          <p class="access__text wf-muted">{{ t('employees.accessNone') }}</p>
          <Button :label="t('employees.grantAccess')" icon="pi pi-key" size="small" @click="openAccessDialog" />
          <Button
            :label="t('employees.linkAccount')"
            icon="pi pi-link"
            severity="secondary"
            outlined
            size="small"
            @click="openLinkDialog"
          />
        </template>
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
    <Dialog
      v-model:visible="linkDialog"
      :header="t('employees.linkAccount')"
      modal
      :style="{ width: '420px' }"
    >
      <p class="wf-muted access__hint">{{ t('employees.linkHint') }}</p>

      <Select
        v-model="selectedAccount"
        :options="freeAccounts"
        option-label="email"
        option-value="id"
        :placeholder="t('employees.linkPlaceholder')"
        :empty-message="t('employees.linkEmpty')"
        filter
        fluid
      >
        <template #option="{ option }: { option: User }">
          <div class="link-option">
            <span>{{ option.name }}</span>
            <small class="wf-muted">{{ option.email }} · {{ t(`roles.${option.role}`) }}</small>
          </div>
        </template>
      </Select>

      <template #footer>
        <Button :label="t('common.cancel')" severity="secondary" outlined @click="linkDialog = false" />
        <Button
          :label="t('employees.linkAccount')"
          :disabled="!selectedAccount"
          :loading="linking"
          @click="linkAccount"
        />
      </template>
    </Dialog>

    <Dialog
      v-model:visible="accessDialog"
      :header="t('employees.grantAccess')"
      modal
      :style="{ width: '420px' }"
    >
      <p class="wf-muted access__hint">{{ t('employees.accessHint', { email: employee?.email }) }}</p>

      <div class="wf-field">
        <label for="access-password" class="wf-field__label">{{ t('auth.password') }}</label>
        <Password
          input-id="access-password"
          v-model="accessForm.password"
          toggle-mask
          :invalid="Boolean(accessValidation.errors.password)"
          fluid
          autofocus
          @blur="accessValidation.validateField('password')"
        />
        <small v-if="accessValidation.errors.password" class="wf-field__error">
          {{ accessValidation.errors.password }}
        </small>
      </div>

      <template #footer>
        <Button :label="t('common.cancel')" severity="secondary" outlined @click="accessDialog = false" />
        <Button :label="t('employees.grantAccess')" :loading="grantingAccess" @click="grantAccess" />
      </template>
    </Dialog>
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

.access {
  grid-column: 1 / -1;
  padding: 18px;
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
}

.access__title {
  margin: 0;
  font-size: 14px;
  font-weight: 650;
  display: flex;
  align-items: center;
  gap: 8px;
}

.access__text {
  margin: 0;
  font-size: 13px;
  flex: 1;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.access__role {
  margin-left: 4px;
}

.link-option {
  display: flex;
  flex-direction: column;
  line-height: 1.25;
}

.access__hint {
  margin: 0 0 16px;
  font-size: 12.5px;
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
