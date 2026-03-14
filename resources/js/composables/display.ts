import type { DisplayCardItemConfig, DisplayFieldConfig } from '../types/display'
import type { FlowKanbanExecution } from '../types/kanban'

export function resolveDisplayValue(source: unknown, path: string): unknown {
  const keys = path.split('.')
  let value: unknown = source

  for (const key of keys) {
    if (value == null || typeof value !== 'object') {
      return undefined
    }
    value = (value as Record<string, unknown>)[key]
  }

  return value
}

export function formatDisplayValue(value: unknown, format?: string): string {
  if (value == null || value === '') {
    return '—'
  }

  if (format === 'date') {
    return new Date(String(value)).toLocaleDateString('pt-BR')
  }

  if (format === 'datetime') {
    return new Date(String(value)).toLocaleString('pt-BR')
  }

  if (typeof value === 'object' && value !== null && 'name' in value && typeof (value as { name: unknown }).name === 'string') {
    return (value as { name: string }).name
  }

  return String(value)
}

export function resolveFieldValue(execution: FlowKanbanExecution, field: DisplayFieldConfig): unknown {
  return resolveDisplayValue(execution, field.key)
}

export function resolveCardItemValue(execution: FlowKanbanExecution, card: DisplayCardItemConfig): string {
  return formatDisplayValue(resolveDisplayValue(execution, card.key), card.format)
}

export function badgeClass(value: unknown, variant?: string): string {
  if (variant) {
    const explicitMap: Record<string, string> = {
      default: 'bg-muted text-muted-foreground',
      success: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
      warning: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
      destructive: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
      info: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
    }

    return explicitMap[variant] ?? explicitMap.default
  }

  const statusMap: Record<string, string> = {
    pending: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    in_progress: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
    completed: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
    blocked: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
    skipped: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
  }

  return statusMap[String(value)] ?? 'bg-muted text-muted-foreground'
}
