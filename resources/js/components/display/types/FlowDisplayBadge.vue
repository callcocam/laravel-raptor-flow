<script setup lang="ts">
import { badgeClass, formatDisplayValue } from '../../../composables/display'
import type { DisplayFieldConfig } from '../../../types/display'
import type { FlowKanbanExecution } from '../../../types/kanban'
import { computed } from 'vue'

interface Props {
  field: DisplayFieldConfig
  value?: unknown
  execution?: FlowKanbanExecution
}

const props = withDefaults(defineProps<Props>(), {
  value: undefined,
  execution: undefined,
})

const content = computed(() => formatDisplayValue(
  props.value,
  props.field.format ?? props.field.type,
  props.execution,
  props.field.key,
))

const classes = computed(() => badgeClass(
  props.value,
  props.field.variant,
  props.execution,
  props.field.key,
))
</script>

<template>
  <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="classes">
    {{ content }}
  </span>
</template>
