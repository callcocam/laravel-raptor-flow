/**
 * useFlowAction - Utilitários compartilhados pelos componentes de Action do Flow
 */

import * as LucideIcons from 'lucide-vue-next'
import { computed } from 'vue'
import type { FlowActionSchema } from '../types/detailModal'
import type { FlowKanbanExecution } from '../types/kanban'

/**
 * Resolve placeholders {param} ou {nested.field} em uma URL com os dados da execução.
 *
 * Suporta dot notation:
 *   {id}           → execution.id
 *   {gondola.id}   → execution.gondola.id
 *   {workable.id}  → execution.workable.id
 */
export function resolveActionUrl(url: string, execution: FlowKanbanExecution): string {
  return url.replace(/\{([\w.]+)\}/g, (_, path: string) => {
    const keys = path.split('.')
    let value: unknown = execution as unknown
    for (const key of keys) {
      if (value == null || typeof value !== 'object') return ''
      value = (value as Record<string, unknown>)[key]
    }
    return String(value ?? '')
  })
}

/** Mapa de variant → classes Tailwind */
export function variantClass(variant?: FlowActionSchema['variant']): string {
  const map: Record<string, string> = {
    default: 'bg-primary text-primary-foreground hover:bg-primary/90',
    destructive: 'border border-destructive text-destructive hover:bg-destructive/10',
    outline: 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
    ghost: 'hover:bg-accent hover:text-accent-foreground',
    secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
  }
  return map[variant ?? 'outline'] ?? map.outline
}

/** Retorna o componente de ícone do Lucide pelo nome ou null */
export function useActionIcon(action: FlowActionSchema) {
  return computed(() => {
    if (!action.icon) return null
    return (LucideIcons as Record<string, unknown>)[action.icon] ?? null
  })
}
