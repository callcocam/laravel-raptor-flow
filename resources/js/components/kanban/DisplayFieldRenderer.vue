<script setup lang="ts">
import { resolveFieldValue } from '../../composables/display'
import type { DisplayFieldConfig, DisplayFieldType } from '../../types/display'
import type { FlowKanbanExecution, FlowKanbanStep } from '../../types/kanban'
import FlowDisplayRegistry from '../../utils/FlowDisplayRegistry'
import { computed } from 'vue'

interface Props {
  field: DisplayFieldConfig
  execution: FlowKanbanExecution
  steps?: FlowKanbanStep[]
  currentUserId?: string | null
  mode?: 'modal' | 'card'
}

const props = withDefaults(defineProps<Props>(), {
  steps: () => [],
  currentUserId: null,
  mode: 'modal',
})

const typeMap: Record<DisplayFieldType, string> = {
  text: 'flow-display-text',
  label: 'flow-display-label',
  textarea: 'flow-display-textarea',
  date: 'flow-display-date',
  datetime: 'flow-display-datetime',
  badge: 'flow-display-badge',
  link: 'flow-display-link',
  cards: 'flow-display-cards',
  timeline: 'flow-display-timeline',
  selectUsers: 'flow-display-select-users',
  custom: 'flow-display-custom',
}

const fieldValue = computed(() => resolveFieldValue(props.execution, props.field))

function resolveRegisteredComponent(name: string) {
  const resolved = FlowDisplayRegistry.get(name)
  if (!resolved) {
    console.warn(`DisplayFieldRenderer: '${name}' não encontrado no FlowDisplayRegistry`)
  }
  return resolved ?? FlowDisplayRegistry.get('flow-display-custom') ?? null
}

const componentName = computed(() => props.field.component ?? typeMap[props.field.type] ?? 'flow-display-custom')
const component = computed(() => resolveRegisteredComponent(componentName.value))
</script>

<template>
  <component
    :is="component"
    v-if="component"
    :field="field"
    :execution="execution"
    :steps="steps"
    :current-user-id="currentUserId"
    :mode="mode"
    :value="fieldValue"
  />
</template>
