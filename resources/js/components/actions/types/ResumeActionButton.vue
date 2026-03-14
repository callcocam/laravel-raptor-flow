<script setup lang="ts">
import FlowActionConfirm from './FlowActionConfirm.vue'
import type { FlowActionSchema } from '../../../types/detailModal'
import type { FlowKanbanExecution } from '../../../types/kanban'

defineProps<{
  action: FlowActionSchema
  execution: FlowKanbanExecution
}>()

const emit = defineEmits<{ execute: [] }>()
</script>

<template>
  <FlowActionConfirm :action="action" :execution="execution" @execute="emit('execute')" />
</template>