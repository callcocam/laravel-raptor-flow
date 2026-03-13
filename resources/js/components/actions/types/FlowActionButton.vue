<!--
  FlowActionButton - Botão de ação simples
  Renderiza um botão que emite 'execute' ao clicar.
  Sem confirmação. O FlowActionRenderer propaga o evento para o FlowDetailModal.
-->
<script setup lang="ts">
import { resolveActionUrl, variantClass, useActionIcon } from '../../../composables/useFlowAction'
import type { FlowActionSchema } from '../../../types/detailModal'
import type { FlowKanbanExecution } from '../../../types/kanban'

const props = defineProps<{
  action: FlowActionSchema
  execution: FlowKanbanExecution
}>()

const emit = defineEmits<{ execute: [] }>()

const iconComponent = useActionIcon(props.action)
const resolvedUrl = resolveActionUrl(props.action.url ?? '', props.execution)
</script>

<template>
  <button
    type="button"
    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-md px-4 text-sm font-medium transition-colors disabled:opacity-50"
    :class="variantClass(action.variant)"
    :title="resolvedUrl"
    @click="emit('execute')"
  >
    <component :is="iconComponent" v-if="iconComponent" class="h-4 w-4 shrink-0" />
    {{ action.label }}
  </button>
</template>
