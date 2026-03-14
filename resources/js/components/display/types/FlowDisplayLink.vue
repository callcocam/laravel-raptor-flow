<script setup lang="ts">
import { resolveActionUrl } from '../../../composables/useFlowAction'
import { formatDisplayValue } from '../../../composables/display'
import type { DisplayFieldConfig } from '../../../types/display'
import type { FlowKanbanExecution } from '../../../types/kanban'
import { ExternalLink } from 'lucide-vue-next'
import { computed } from 'vue'

interface Props {
  field: DisplayFieldConfig
  execution: FlowKanbanExecution
  value?: unknown
  mode?: 'modal' | 'card'
}

const props = withDefaults(defineProps<Props>(), {
  value: undefined,
  mode: 'modal',
})

const href = computed(() => {
  if (!props.field.url) {
    return '#'
  }

  return resolveActionUrl(props.field.url, props.execution)
})

const label = computed(() => props.field.label ?? formatDisplayValue(props.value, props.field.format ?? props.field.type))
const linkClass = computed(() =>
  props.mode === 'card'
    ? 'text-xs font-medium text-primary hover:underline'
    : 'inline-flex items-center gap-1 text-sm font-medium text-primary transition-colors hover:underline',
)
</script>

<template>
  <a
    :href="href"
    :target="field.external ? '_blank' : undefined"
    :rel="field.external ? 'noopener noreferrer' : undefined"
    :class="linkClass"
  >
    {{ label }}
    <ExternalLink v-if="field.external && mode !== 'card'" class="h-3 w-3" />
  </a>
</template>
