<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import EmptyState from '@/components/EmptyState.vue'
import { apiMessage } from '@/api/client'
import { formatDate, useTaskMeta } from '@/composables/useTaskMeta'
import { useAuthStore } from '@/stores/auth'
import { useEmployeesStore } from '@/stores/employees'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const employees = useEmployeesStore()
const { statusLabel, statusType, priorityLabel, priorityType } = useTaskMeta()

const loading = ref(true)
const employee = computed(() => employees.current)
const canManage = auth.can('admin', 'manager')

onMounted(async () => {
  try {
    await employees.fetchOne(Number(route.params.id))
  } catch (error) {
    ElMessage.error(apiMessage(error, t('employees.notFound')))
    await router.push({ name: 'employees' })
  } finally {
    loading.value = false
  }
})

async function remove(): Promise<void> {
  if (!employee.value) return

  try {
    await ElMessageBox.confirm(
      t('employees.deleteConfirm', { name: employee.value.full_name }),
      t('common.confirm'),
      { type: 'warning', confirmButtonText: t('common.delete'), cancelButtonText: t('common.cancel') },
    )
  } catch {
    return
  }

  try {
    await employees.remove(employee.value.id)
    ElMessage.success(t('common.deleted'))
    await router.push({ name: 'employees' })
  } catch (error) {
    ElMessage.error(apiMessage(error))
  }
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
        <el-button icon="ArrowLeft" @click="router.push({ name: 'employees' })">{{ t('common.back') }}</el-button>
        <el-button
          v-if="canManage"
          icon="Edit"
          @click="router.push({ name: 'employees.edit', params: { id: route.params.id } })"
        >
          {{ t('common.edit') }}
        </el-button>
        <el-button v-if="canManage" type="danger" plain icon="Delete" @click="remove">
          {{ t('common.delete') }}
        </el-button>
      </div>
    </div>

    <el-skeleton v-if="loading" :rows="8" animated class="wf-card skeleton-card" />

    <div v-else-if="employee" class="profile">
      <section class="wf-card profile__card">
        <el-avatar :size="76" :src="employee.avatar ?? undefined" class="profile__avatar">
          {{ employee.first_name[0] }}{{ employee.last_name[0] }}
        </el-avatar>
        <h2 class="profile__name">{{ employee.full_name }}</h2>
        <p class="wf-muted profile__position">{{ employee.position ?? '—' }}</p>
        <el-tag v-if="employee.department" effect="plain" round>{{ employee.department.name }}</el-tag>

        <el-descriptions :column="1" border class="profile__details">
          <el-descriptions-item :label="t('employees.email')">{{ employee.email }}</el-descriptions-item>
          <el-descriptions-item :label="t('employees.phone')">{{ employee.phone ?? '—' }}</el-descriptions-item>
          <el-descriptions-item :label="t('employees.hireDate')">{{ formatDate(employee.hire_date) }}</el-descriptions-item>
          <el-descriptions-item :label="t('employees.department')">
            {{ employee.department?.name ?? t('employees.noDepartment') }}
          </el-descriptions-item>
        </el-descriptions>
      </section>

      <section class="wf-card profile__tasks">
        <h3 class="profile__tasks-title">
          {{ t('employees.tasksOf') }}
          <el-tag size="small" effect="plain" round>{{ employee.tasks?.length ?? 0 }}</el-tag>
        </h3>

        <el-table v-if="employee.tasks?.length" :data="employee.tasks" style="width: 100%">
          <el-table-column prop="title" :label="t('tasks.titleField')" min-width="220" />
          <el-table-column :label="t('tasks.status')" width="140">
            <template #default="{ row }">
              <el-tag size="small" :type="statusType(row.status)" effect="light" round>
                {{ statusLabel(row.status) }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column :label="t('tasks.priority')" width="130">
            <template #default="{ row }">
              <el-tag size="small" :type="priorityType(row.priority)" effect="plain" round>
                {{ priorityLabel(row.priority) }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column :label="t('tasks.deadline')" width="130">
            <template #default="{ row }">
              <span :class="{ overdue: row.is_overdue }">{{ formatDate(row.deadline) }}</span>
            </template>
          </el-table-column>
        </el-table>

        <EmptyState v-else :text="t('tasks.empty')" icon="Files" />
      </section>
    </div>
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

.profile__avatar {
  font-size: 24px;
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

.profile__details {
  width: 100%;
  margin-top: 18px;
  text-align: left;
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

.skeleton-card {
  padding: 24px;
}

.overdue {
  color: var(--el-color-danger);
}

@media (max-width: 900px) {
  .profile {
    grid-template-columns: 1fr;
  }
}
</style>
