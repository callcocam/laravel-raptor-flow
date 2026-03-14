<script setup lang="ts">
import { resolveCardItemValue } from '../../../composables/display'
import type { DisplayCardItemConfig, DisplayFieldConfig } from '../../../types/display'
import type { FlowKanbanExecution } from '../../../types/kanban'
import { computed } from 'vue'

interface Props {
  field: DisplayFieldConfig
  execution: FlowKanbanExecution
  mode?: 'modal' | 'card'
}

const props = withDefaults(defineProps<Props>(), {
  mode: 'modal',
})

const containerClass = computed(() =>
  props.mode === 'card' ? 'grid gap-2 sm:grid-cols-2' : 'grid gap-3 sm:grid-cols-2',
)

function formatCard(card: DisplayCardItemConfig): string {
  return resolveCardItemValue(props.execution, card)
}
</script>

<template>
  <div :class="containerClass">
    <div
      v-for="card in field.cards ?? []"
      :key="`${field.key}-${card.key}`"
      :class="mode === 'card' ? 'rounded-md border bg-background/70 p-2' : 'rounded-lg border bg-background p-3'"
    >
      <p v-if="card.label" :class="mode === 'card' ? 'text-[11px] text-muted-foreground' : 'text-xs text-muted-foreground'">
        {{ card.label }}
      </p>
      <p :class="mode === 'card' ? 'text-xs font-semibold text-card-foreground' : 'mt-1 text-sm font-semibold text-foreground'">
        {{ formatCard(card) }}
      </p>
    </div>
  </div>
</template>
