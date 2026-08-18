<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDebounceFn } from '@vueuse/core'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import EmptyState from '@/components/EmptyState.vue'
import TableSkeleton from '@/components/TableSkeleton.vue'
import { apiMessage } from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useDepartmentsStore } from '@/stores/departments'
import type { Department } from '@/types'

const { t } = useI18n()
const auth = useAuthStore()
const departments = useDepartmentsStore()

const canManage = auth.can('admin', 'manager')
const search = ref('')
const dialogVisible = ref(false)
const editing = ref<Department | null>(null)
const saving = ref(false)
const formRef = ref<FormInstance>()
const form = reactive<Partial<Department>>({ name: '', description: '' })

const rules: FormRules = {
  name: [{ required: true, message: t('common.error'), trigger: 'blur' }],
}

const debouncedSearch = useDebounceFn((value: string) => {
  void departments.fetch({ search: value, page: 1 })
}, 400)

watch(search, (value) => debouncedSearch(value))

onMounted(() => {
  void departments.fetch()
})

function openCreate(): void {
  editing.value = null
  Object.assign(form, { name: '', description: '' })
  dialogVisible.value = true
}

function openEdit(department: Department): void {
  editing.value = department
  Object.assign(form, { name: department.name, description: department.description ?? '' })
  dialogVisible.value = true
}

async function submit(): Promise<void> {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  try {
    if (editing.value) {
      await departments.update(editing.value.id, form)
      ElMessage.success(t('common.saved'))
    } else {
      await departments.create(form)
      ElMessage.success(t('common.created'))
    }
    dialogVisible.value = false
  } catch (error) {
    ElMessage.error(apiMessage(error))
  } finally {
    saving.value = false
  }
}

async function remove(department: Department): Promise<void> {
  try {
    await ElMessageBox.confirm(t('departments.deleteConfirm', { name: department.name }), t('common.confirm'), {
      type: 'warning',
      confirmButtonText: t('common.delete'),
      cancelButtonText: t('common.cancel'),
    })
  } catch {
    return
  }

  try {
    await departments.remove(department.id)
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
        <h1 class="wf-page__title">{{ t('departments.title') }}</h1>
        <p class="wf-page__subtitle">{{ t('departments.subtitle') }} · {{ t('common.total') }}: {{ departments.total }}</p>
      </div>
      <el-button v-if="canManage" type="primary" icon="Plus" @click="openCreate">
        {{ t('departments.create') }}
      </el-button>
    </div>

    <div class="wf-card filters">
      <el-input v-model="search" :placeholder="t('common.search')" clearable class="filters__search">
        <template #prefix><el-icon><Search /></el-icon></template>
      </el-input>
    </div>

    <TableSkeleton v-if="departments.loading" :rows="4" :columns="3" class="wf-card skeleton-card" />

    <div v-else-if="departments.items.length" class="wf-grid cards">
      <article v-for="department in departments.items" :key="department.id" class="wf-card department">
        <div class="department__head">
          <div class="department__icon"><el-icon :size="18"><OfficeBuilding /></el-icon></div>
          <h3 class="department__name">{{ department.name }}</h3>
          <el-dropdown
            v-if="canManage"
            trigger="click"
            @command="(command: string) => (command === 'edit' ? openEdit(department) : remove(department))"
          >
            <el-icon class="department__menu"><MoreFilled /></el-icon>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="edit">{{ t('common.edit') }}</el-dropdown-item>
                <el-dropdown-item command="remove" divided>{{ t('common.delete') }}</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>

        <p class="department__description wf-muted">{{ department.description ?? '—' }}</p>

        <router-link
          :to="{ name: 'employees', query: { department: department.id } }"
          class="department__footer"
        >
          <el-icon><UserFilled /></el-icon>
          {{ t('departments.employeesCount') }}: <b>{{ department.employees_count ?? 0 }}</b>
        </router-link>
      </article>
    </div>

    <EmptyState v-else :text="t('departments.empty')" icon="OfficeBuilding" />

    <el-pagination
      v-if="departments.lastPage > 1"
      class="pagination"
      layout="prev, pager, next"
      background
      :total="departments.total"
      :current-page="departments.page"
      @current-change="(page: number) => departments.fetch({ search, page })"
    />

    <el-dialog
      v-model="dialogVisible"
      :title="editing ? t('departments.editTitle') : t('departments.createTitle')"
      width="440px"
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
        <el-form-item :label="t('departments.name')" prop="name">
          <el-input v-model="form.name" />
        </el-form-item>
        <el-form-item :label="t('departments.description')" prop="description">
          <el-input v-model="form.description" type="textarea" :rows="3" />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">{{ t('common.cancel') }}</el-button>
        <el-button type="primary" :loading="saving" @click="submit">{{ t('common.save') }}</el-button>
      </template>
    </el-dialog>
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
  background: var(--el-color-primary-light-9);
  color: var(--el-color-primary);
}

.department__name {
  margin: 0;
  font-size: 15px;
  font-weight: 650;
  flex: 1;
}

.department__menu {
  cursor: pointer;
  color: var(--wf-text-muted);
  outline: none;
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
  color: var(--el-text-color-regular);
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
