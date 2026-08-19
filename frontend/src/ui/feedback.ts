import { useI18n } from 'vue-i18n'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'

/** Всплывающие уведомления в терминах приложения, без деталей библиотеки. */
export function useNotify() {
  const toast = useToast()

  return {
    success: (message: string) => toast.add({ severity: 'success', summary: message, life: 3000 }),
    error: (message: string) => toast.add({ severity: 'error', summary: message, life: 4000 }),
    warning: (message: string) => toast.add({ severity: 'warn', summary: message, life: 4000 }),
    info: (message: string) => toast.add({ severity: 'info', summary: message, life: 3000 }),
  }
}

/**
 * Диалог подтверждения удаления: один набор кнопок и оформления
 * на все экраны вместо восьми одинаковых опций в каждом.
 */
export function useConfirmDelete() {
  const confirm = useConfirm()
  const { t } = useI18n()

  return (message: string, onConfirm: () => void | Promise<void>): void => {
    confirm.require({
      header: t('common.confirm'),
      message,
      icon: 'pi pi-exclamation-triangle',
      acceptLabel: t('common.delete'),
      rejectLabel: t('common.cancel'),
      acceptProps: { severity: 'danger' },
      rejectProps: { severity: 'secondary', outlined: true },
      accept: () => void onConfirm(),
    })
  }
}
