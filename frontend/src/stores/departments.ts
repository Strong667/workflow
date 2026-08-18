import { defineStore } from 'pinia'
import { ref } from 'vue'
import { departmentsApi } from '@/api'
import type { Department } from '@/types'

export const useDepartmentsStore = defineStore('departments', () => {
  const items = ref<Department[]>([])
  const options = ref<Department[]>([])
  const loading = ref(false)
  const total = ref(0)
  const page = ref(1)
  const lastPage = ref(1)

  async function fetch(params: { search?: string; page?: number } = {}): Promise<void> {
    loading.value = true
    try {
      const response = await departmentsApi.list({ ...params, page: params.page ?? page.value })
      items.value = response.data
      total.value = response.meta.total
      page.value = response.meta.current_page
      lastPage.value = response.meta.last_page
    } finally {
      loading.value = false
    }
  }

  /** Справочник для селектов — грузится один раз и кэшируется в сторе. */
  async function fetchOptions(force = false): Promise<Department[]> {
    if (options.value.length && !force) return options.value
    options.value = await departmentsApi.all()
    return options.value
  }

  async function create(payload: Partial<Department>): Promise<Department> {
    const department = await departmentsApi.create(payload)
    await Promise.all([fetch(), fetchOptions(true)])
    return department
  }

  async function update(id: number, payload: Partial<Department>): Promise<Department> {
    const department = await departmentsApi.update(id, payload)
    await Promise.all([fetch(), fetchOptions(true)])
    return department
  }

  async function remove(id: number): Promise<void> {
    await departmentsApi.remove(id)
    await Promise.all([fetch(), fetchOptions(true)])
  }

  return { items, options, loading, total, page, lastPage, fetch, fetchOptions, create, update, remove }
})
