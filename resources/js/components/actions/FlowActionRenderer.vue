<!--
  FlowActionRenderer - Renderiza as acoes do Flow dinamicamente.
  Usa o FlowActionRegistry para obter o componente correto pelo tipo da acao.

  Mapeamento de type para componente:
  - 'action' sem confirm  -> 'flow-action-button'
  - 'action' com confirm  -> 'flow-action-confirm'
  - 'notes'               → 'flow-action-notes'
  - 'link'                → 'flow-action-link'

  Tambem respeita o campo 'component' da acao para sobrescrita explicita.
-->
<script setup lang="ts">
import { computed } from 'vue'
import FlowActionRegistry from '../../utils/FlowActionRegistry'
import type { FlowActionSchema } from '../../types/detailModal'
import type { FlowKanbanExecution } from '../../types/kanban'
import { executeFlowAction, resolveActionUrl } from '../../composables/useFlowAction'

const props = defineProps<{
  action: FlowActionSchema
  execution: FlowKanbanExecution
}>()

const emit = defineEmits<{
  executed: [execution: FlowKanbanExecution, action: FlowActionSchema, resolvedUrl: string, notes?: string, executed?: boolean]
}>()

const component = computed(() => {
  // 1. Sobrescrita explicita por nome de componente.
  const override = props.action.component
  if (override) {
    const c = FlowActionRegistry.get(override)
    if (c) return c
  }

  // 2. Mapeamento por type.
  const typeMap: Record<string, string> = {
    action: props.action.confirm ? 'flow-action-confirm' : 'flow-action-button',
    notes: 'flow-action-notes',
    link: 'flow-action-link',
  }

  const name = typeMap[props.action.type ?? 'action'] ?? 'flow-action-button'
  const registered = FlowActionRegistry.get(name)

  if (!registered) {
    console.warn(`FlowActionRenderer: '${name}' não encontrado no FlowActionRegistry`)
    return FlowActionRegistry.get('flow-action-button')
  }

  return registered
})

function handleExecute(notes?: string): void {
  const resolvedUrl = resolveActionUrl(props.action.url ?? '', props.execution)
  const didExecute = executeFlowAction(props.action, props.execution, {
    notes,
    preserveState: false,
    preserveScroll: false,
  })

  emit('executed', props.execution, props.action, resolvedUrl, notes, didExecute)
}
</script>

<template>
  <component
    :is="component"
    :action="action"
    :execution="execution"
    @execute="handleExecute"
  />
</template>
