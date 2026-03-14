<!--
  FlowActionRenderer - Renderiza ações do Flow dinamicamente
  Usa o FlowActionRegistry para obter o componente correto pelo tipo da ação.

  Mapeamento de type → componente:
  - 'action' sem confirm  → 'flow-action-button'
  - 'action' com confirm  → 'flow-action-confirm'
  - 'notes'               → 'flow-action-notes'
  - 'link'                → 'flow-action-link'

  Também respeita o campo 'component' da ação para override explícito.
-->
<script setup lang="ts">
import { computed } from 'vue'
import FlowActionRegistry from '../../utils/FlowActionRegistry'
import type { FlowActionSchema } from '../../types/detailModal'
import type { FlowKanbanExecution } from '../../types/kanban'

const props = defineProps<{
  action: FlowActionSchema
  execution: FlowKanbanExecution
}>()

const emit = defineEmits<{
  execute: [notes?: string]
}>()

const component = computed(() => {
  // 1. Override explícito por nome de componente
  const override = props.action.component
  if (override) {
    const c = FlowActionRegistry.get(override)
    if (c) return c
  }

  // 2. Mapeamento por type
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
</script>

<template>
  <component
    :is="component"
    :action="action"
    :execution="execution"
    @execute="(notes?: string) => emit('execute', notes)"
  />
</template>
