import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { tasksApi } from '@/api'
import type { Board, Task, TaskFilters, TaskStatus } from '@/types'

export const STATUSES: TaskStatus[] = ['todo', 'in_progress', 'review', 'done']

function emptyBoard(): Board {
  return { todo: [], in_progress: [], review: [], done: [] }
}

export const useTasksStore = defineStore('tasks', () => {
  const board = ref<Board>(emptyBoard())
  const loading = ref(false)
  const saving = ref(false)
  const filters = ref<TaskFilters>({
    search: '',
    priority: null,
    employee_id: null,
    department_id: null,
  })

  const totalCount = computed(() => STATUSES.reduce((sum, status) => sum + board.value[status].length, 0))

  async function fetchBoard(): Promise<void> {
    loading.value = true
    try {
      board.value = { ...emptyBoard(), ...(await tasksApi.board(filters.value)) }
    } finally {
      loading.value = false
    }
  }

  function resetFilters(): void {
    filters.value = { search: '', priority: null, employee_id: null, department_id: null }
  }

  async function create(payload: Partial<Task>): Promise<Task> {
    saving.value = true
    try {
      return await tasksApi.create(payload)
    } finally {
      saving.value = false
    }
  }

  async function update(id: number, payload: Partial<Task>): Promise<Task> {
    saving.value = true
    try {
      const task = await tasksApi.update(id, payload)
      replaceInBoard(task)
      return task
    } finally {
      saving.value = false
    }
  }

  async function remove(id: number): Promise<void> {
    await tasksApi.remove(id)
    STATUSES.forEach((status) => {
      board.value[status] = board.value[status].filter((task) => task.id !== id)
    })
  }

  /**
   * Оптимистичный перенос карточки: UI обновляется сразу,
   * при ошибке доска перезагружается с сервера.
   */
  async function move(taskId: number, from: TaskStatus, to: TaskStatus, index: number): Promise<void> {
    const source = board.value[from]
    const currentIndex = source.findIndex((task) => task.id === taskId)
    if (currentIndex === -1) return

    const [task] = source.splice(currentIndex, 1)
    const target = board.value[to]
    const position = Math.min(Math.max(index, 0), target.length)
    target.splice(position, 0, { ...task, status: to })

    try {
      await tasksApi.move(taskId, to, position)
    } catch (error) {
      await fetchBoard()
      throw error
    }
  }

  function replaceInBoard(task: Task): void {
    STATUSES.forEach((status) => {
      board.value[status] = board.value[status].filter((item) => item.id !== task.id)
    })
    board.value[task.status].push(task)
  }

  return { board, loading, saving, filters, totalCount, fetchBoard, resetFilters, create, update, remove, move }
})
