/**
 * useFlowAction - Utilitarios compartilhados pelos componentes de acao do Flow.
 */

import { router } from '@inertiajs/vue3'
import * as LucideIcons from 'lucide-vue-next'
import { computed } from 'vue'
import type { FlowActionSchema } from '../types/detailModal'
import type { FlowKanbanExecution } from '../types/kanban'

interface ExecuteFlowActionOptions {
  notes?: string
  preserveState?: boolean
  preserveScroll?: boolean
  onSuccess?: () => void
  onError?: () => void
}

/**
 * Resolve placeholders {param} ou {nested.field} em uma URL com os dados da execução.
 *
 * Suporta notacao por ponto:
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

/**
 * Executa uma ação de flow no frontend (backend-driven) usando Inertia.
 * Por padrão força refresh completo para evitar estado stale na modal/board.
 */
export function executeFlowAction(
  action: FlowActionSchema,
  execution: FlowKanbanExecution,
  options: ExecuteFlowActionOptions = {},
): boolean {
  if (!action.url) {
    return false
  }

  const resolvedUrl = resolveActionUrl(action.url, execution)
  if (!resolvedUrl || resolvedUrl === '#') {
    return false
  }

  const method = (action.method ?? 'post').toLowerCase()
  const payload: Record<string, unknown> = {
    ...(action.data ?? {}),
  }

  if (action.type === 'notes' && options.notes !== undefined) {
    payload.notes = options.notes
  }

  const visitOptions = {
    preserveState: options.preserveState ?? false,
    preserveScroll: options.preserveScroll ?? false,
    onSuccess: options.onSuccess,
    onError: options.onError,
  }

  if (method === 'get') {
    router.get(resolvedUrl, payload, visitOptions)
    return true
  }

  if (method === 'post') {
    router.post(resolvedUrl, payload, visitOptions)
    return true
  }

  if (method === 'put') {
    router.put(resolvedUrl, payload, visitOptions)
    return true
  }

  if (method === 'patch') {
    router.patch(resolvedUrl, payload, visitOptions)
    return true
  }

  if (method === 'delete') {
    router.delete(resolvedUrl, {
      ...visitOptions,
      data: payload,
    })
    return true
  }

  router.post(resolvedUrl, payload, visitOptions)

  return true
}

/** Mapa de variante para classes Tailwind. */
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

/** Retorna o componente de icone do Lucide pelo nome, ou null. */
export function useActionIcon(action: FlowActionSchema) {
  return computed(() => {
    if (!action.icon) return null
    return (LucideIcons as Record<string, unknown>)[action.icon] ?? null
  })
}
