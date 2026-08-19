/**
 * Тяжёлые компоненты, которые подключаются точечно в своих экранах,
 * чтобы не тянуть их в стартовый чанк. Реэкспорт держит все пути к
 * библиотеке в одном месте — при переименованиях в новой мажорной
 * версии правится только этот файл.
 */
export { default as Chart } from 'primevue/chart'
export { default as Column } from 'primevue/column'
export { default as DataTable } from 'primevue/datatable'
export { default as DatePicker } from 'primevue/datepicker'
export { default as Dialog } from 'primevue/dialog'
export { default as Paginator } from 'primevue/paginator'
export { default as Password } from 'primevue/password'
export { default as Timeline } from 'primevue/timeline'

export type { DataTablePageEvent, DataTableSortEvent } from 'primevue/datatable'
export type { PageState } from 'primevue/paginator'
export type { MenuItem } from 'primevue/menuitem'
