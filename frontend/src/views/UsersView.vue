<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDebounceFn } from '@vueuse/core'
import { Column, DataTable, Dialog, Password, type DataTablePageEvent } from '@/ui/lazy-components'
import { useConfirmDelete, useNotify } from '@/ui/feedback'
import EmptyState from '@/components/EmptyState.vue'
import TableSkeleton from '@/components/TableSkeleton.vue'
import { usersApi } from '@/api'
import { apiMessage } from '@/api/client'
import { rules, useValidation } from '@/composables/useValidation'
import { useAuthStore } from '@/stores/auth'
import type { Role, User } from '@/types'

const { t } = useI18n()
const notify = useNotify()
const confirmDelete = useConfirmDelete()
const auth = useAuthStore()

const users = ref<User[]>([])
const loading = ref(true)
const saving = ref(false)
const total = ref(0)
const page = ref(1)
const perPage = ref(15)
const search = ref('')
const roleFilter = ref<Role | null>(null)

const dialogVisible = ref(false)
const editing = ref<User | null>(null)

const form = reactive({ name: '', email: '', role: 'employee' as Role, password: '' })

/** При создании пароль обязателен, при редактировании — только если его меняют. */
const { errors, validate, validateField, clear } = useValidation(form, {
  name: [rules.required(t('common.requiredField'))],
  email: [rules.required(t('auth.emailRequired')), rules.email(t('auth.emailInvalid'))],
  password: [
    (value) => (!editing.value && !value ? t('auth.passwordRequired') : null),
    (value) => (typeof value === 'string' && value.length > 0 && value.length < 6 ? t('auth.passwordMin') : null),
  ],
})

/** Менеджер не может выдавать роль администратора — список ролей у него короче. */
const roleOptions = computed(() =>
  (auth.can('admin') ? (['admin', 'manager', 'employee'] as Role[]) : (['manager', 'employee'] as Role[])).map(
    (value) => ({ value, label: t(`roles.${value}`) }),
  ),
)

const roleSeverities: Record<Role, 'danger' | 'warn' | 'secondary'> = {
  admin: 'danger',
  manager: 'warn',
  employee: 'secondary',
}

async function fetch(): Promise<void> {
  loading.value = true
  try {
    const response = await usersApi.list({ search: search.value, role: roleFilter.value, page: page.value })
    users.value = response.data
    total.value = response.meta.total
    perPage.value = response.meta.per_page
  } catch (error) {
    notify.error(apiMessage(error))
  } finally {
    loading.value = false
  }
}

const debouncedSearch = useDebounceFn(() => {
  page.value = 1
  void fetch()
}, 400)

watch(search, () => debouncedSearch())

onMounted(fetch)

function onPage(event: DataTablePageEvent): void {
  page.value = event.page + 1
  void fetch()
}

function openCreate(): void {
  editing.value = null
  Object.assign(form, { name: '', email: '', role: 'employee' as Role, password: '' })
  clear()
  dialogVisible.value = true
}

function openEdit(user: User): void {
  editing.value = user
  Object.assign(form, { name: user.name, email: user.email, role: user.role, password: '' })
  clear()
  dialogVisible.value = true
}

async function submit(): Promise<void> {
  if (!validate()) return

  saving.value = true
  try {
    if (editing.value) {
      const payload = { name: form.name, email: form.email, role: form.role, ...(form.password ? { password: form.password } : {}) }
      await usersApi.update(editing.value.id, payload)
      notify.success(t('common.saved'))
    } else {
      await usersApi.create({ ...form })
      notify.success(t('users.created'))
    }
    dialogVisible.value = false
    await fetch()
  } catch (error) {
    notify.error(apiMessage(error))
  } finally {
    saving.value = false
  }
}

function remove(user: User): void {
  confirmDelete(t('users.deleteConfirm', { name: user.name }), async () => {
    try {
      await usersApi.remove(user.id)
      notify.success(t('common.deleted'))
      await fetch()
    } catch (error) {
      notify.error(apiMessage(error))
    }
  })
}

/** Свой аккаунт и чужие администраторские менеджер не трогает. */
function canManage(user: User): boolean {
  if (user.id === auth.user?.id) return false
  return user.role !== 'admin' || auth.can('admin')
}
</script>

<template>
  <div class="wf-page">
    <div class="wf-page__header">
      <div>
        <h1 class="wf-page__title">{{ t('users.title') }}</h1>
        <p class="wf-page__subtitle">{{ t('users.subtitle') }} · {{ t('common.total') }}: {{ total }}</p>
      </div>
      <Button icon="pi pi-user-plus" :label="t('users.create')" @click="openCreate" />
    </div>

    <div class="wf-card filters">
      <IconField class="filters__search">
        <InputIcon class="pi pi-search" />
        <InputText v-model="search" :placeholder="t('users.searchPlaceholder')" fluid />
      </IconField>

      <Select
        v-model="roleFilter"
        :options="roleOptions"
        option-label="label"
        option-value="value"
        :placeholder="t('settings.role')"
        show-clear
        class="filters__select"
        @change="page = 1; fetch()"
      />
    </div>

    <div class="wf-card table-wrap">
      <TableSkeleton v-if="loading && !users.length" :rows="5" :columns="4" />

      <DataTable
        v-else-if="users.length"
        :value="users"
        lazy
        paginator
        :rows="perPage"
        :total-records="total"
        :first="(page - 1) * perPage"
        data-key="id"
        @page="onPage"
      >
        <Column :header="t('users.account')" style="min-width: 240px">
          <template #body="{ data }: { data: User }">
            <div class="person">
              <Avatar
                :image="data.avatar ?? undefined"
                :label="data.avatar ? undefined : data.name[0]"
                shape="circle"
              />
              <div class="person__info">
                <span class="person__name">
                  {{ data.name }}
                  <Tag v-if="data.id === auth.user?.id" :value="t('users.you')" severity="info" rounded />
                </span>
                <span class="wf-muted person__email">{{ data.email }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column :header="t('settings.role')" style="width: 170px">
          <template #body="{ data }: { data: User }">
            <Tag :value="t(`roles.${data.role}`)" :severity="roleSeverities[data.role]" rounded />
          </template>
        </Column>

        <Column :header="t('settings.language')" style="width: 120px">
          <template #body="{ data }: { data: User }">{{ data.language.toUpperCase() }}</template>
        </Column>

        <Column :header="t('common.actions')" style="width: 130px">
          <template #body="{ data }: { data: User }">
            <div class="row-actions">
              <Button
                icon="pi pi-pencil"
                severity="secondary"
                text
                rounded
                :disabled="!canManage(data)"
                v-tooltip.top="t('common.edit')"
                @click="openEdit(data)"
              />
              <Button
                icon="pi pi-trash"
                severity="danger"
                text
                rounded
                :disabled="!canManage(data)"
                v-tooltip.top="t('common.delete')"
                @click="remove(data)"
              />
            </div>
          </template>
        </Column>
      </DataTable>

      <EmptyState v-else :text="t('users.empty')" icon="pi pi-users" />
    </div>

    <Dialog
      v-model:visible="dialogVisible"
      :header="editing ? t('users.editTitle') : t('users.createTitle')"
      modal
      :style="{ width: '460px' }"
    >
      <div class="wf-field">
        <label for="user-name" class="wf-field__label">{{ t('profile.name') }}</label>
        <InputText
          id="user-name"
          v-model="form.name"
          :invalid="Boolean(errors.name)"
          fluid
          autofocus
          @blur="validateField('name')"
        />
        <small v-if="errors.name" class="wf-field__error">{{ errors.name }}</small>
      </div>

      <div class="wf-field">
        <label for="user-email" class="wf-field__label">{{ t('profile.email') }}</label>
        <InputText
          id="user-email"
          v-model="form.email"
          type="email"
          :invalid="Boolean(errors.email)"
          fluid
          @blur="validateField('email')"
        />
        <small v-if="errors.email" class="wf-field__error">{{ errors.email }}</small>
      </div>

      <div class="wf-field">
        <label for="user-role" class="wf-field__label">{{ t('settings.role') }}</label>
        <Select
          id="user-role"
          v-model="form.role"
          :options="roleOptions"
          option-label="label"
          option-value="value"
          fluid
        />
      </div>

      <div class="wf-field">
        <label for="user-password" class="wf-field__label">
          {{ editing ? t('users.newPassword') : t('auth.password') }}
        </label>
        <Password
          input-id="user-password"
          v-model="form.password"
          :feedback="!editing"
          toggle-mask
          :placeholder="editing ? t('users.passwordKeep') : ''"
          :invalid="Boolean(errors.password)"
          fluid
          @blur="validateField('password')"
        />
        <small v-if="errors.password" class="wf-field__error">{{ errors.password }}</small>
      </div>

      <template #footer>
        <Button :label="t('common.cancel')" severity="secondary" outlined @click="dialogVisible = false" />
        <Button :label="editing ? t('common.save') : t('common.create')" :loading="saving" @click="submit" />
      </template>
    </Dialog>
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
  display: flex;
  align-items: center;
  gap: 8px;
}

.person__email {
  font-size: 11.5px;
}

.row-actions {
  display: flex;
  gap: 2px;
}
</style>
