import { reactive } from 'vue'

export type Rule = (value: unknown) => string | null

/** Набор типовых правил; сообщения приходят из i18n на стороне вызова. */
export const rules = {
  required: (message: string): Rule => (value) => {
    if (value === null || value === undefined) return message
    if (typeof value === 'string' && value.trim() === '') return message
    return null
  },
  email: (message: string): Rule => (value) =>
    typeof value === 'string' && value !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) ? message : null,
  minLength: (length: number, message: string): Rule => (value) =>
    typeof value === 'string' && value.length > 0 && value.length < length ? message : null,
  matches: (getter: () => unknown, message: string): Rule => (value) =>
    value !== getter() ? message : null,
}

/**
 * Лёгкая валидация форм: в PrimeVue нет встроенной, а тянуть отдельный
 * пакет ради четырёх правил избыточно.
 */
export function useValidation<T extends object>(form: T, fieldRules: Partial<Record<keyof T, Rule[]>>) {
  const errors = reactive<Record<string, string>>({})

  function validateField(field: keyof T): boolean {
    const list = fieldRules[field] ?? []
    for (const rule of list) {
      const message = rule(form[field])
      if (message) {
        errors[field as string] = message
        return false
      }
    }
    delete errors[field as string]
    return true
  }

  function validate(): boolean {
    return (Object.keys(fieldRules) as Array<keyof T>)
      .map((field) => validateField(field))
      .every(Boolean)
  }

  function clear(): void {
    for (const key of Object.keys(errors)) delete errors[key]
  }

  return { errors, validate, validateField, clear }
}
