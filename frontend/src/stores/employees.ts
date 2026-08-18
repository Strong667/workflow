import { defineStore } from 'pinia'
import { ref } from 'vue'
import { employeesApi } from '@/api'
import type { Employee, EmployeeFilters } from '@/types'

export const useEmployeesStore = defineStore('employees', () => {
  const items = ref<Employee[]>([])
  const current = ref<Employee | null>(null)
  const loading = ref(false)
  const saving = ref(false)
  const total = ref(0)
  const filters = ref<EmployeeFilters>({
    search: '',
    department_id: null,
    position: '',
    sort: 'created_at',
    direction: 'desc',
    page: 1,
    per_page: 10,
  })

  async function fetch(): Promise<void> {
    loading.value = true
    try {
      const response = await employeesApi.list(filters.value)
      items.value = response.data
      total.value = response.meta.total
      filters.value.page = response.meta.current_page
    } finally {
      loading.value = false
    }
  }

  function setFilter<K extends keyof EmployeeFilters>(key: K, value: EmployeeFilters[K]): void {
    filters.value[key] = value
    if (key !== 'page') filters.value.page = 1
  }

  function resetFilters(): void {
    filters.value = { search: '', department_id: null, position: '', sort: 'created_at', direction: 'desc', page: 1, per_page: 10 }
  }

  async function fetchOne(id: number): Promise<Employee> {
    loading.value = true
    try {
      current.value = await employeesApi.get(id)
      return current.value
    } finally {
      loading.value = false
    }
  }

  async function create(payload: Partial<Employee>): Promise<Employee> {
    saving.value = true
    try {
      return await employeesApi.create(payload)
    } finally {
      saving.value = false
    }
  }

  async function update(id: number, payload: Partial<Employee>): Promise<Employee> {
    saving.value = true
    try {
      current.value = await employeesApi.update(id, payload)
      return current.value
    } finally {
      saving.value = false
    }
  }

  async function remove(id: number): Promise<void> {
    await employeesApi.remove(id)
    await fetch()
  }

  return { items, current, loading, saving, total, filters, fetch, fetchOne, create, update, remove, setFilter, resetFilters }
})
