<!--
  FlowActionLink - Link/âncora de ação
  type: 'link' — renderiza um <a> que navega para a URL resolvida.
  Não emite 'execute'; a navegação é direta.
-->
<script setup lang="ts">
import { resolveActionUrl, useActionIcon } from '../../../composables/useFlowAction'
import type { FlowActionSchema } from '../../../types/detailModal'
import type { FlowKanbanExecution } from '../../../types/kanban'
import { computed } from 'vue'

const props = defineProps<{
  action: FlowActionSchema
  execution: FlowKanbanExecution
}>()

const iconComponent = useActionIcon(props.action)

const href = computed(() => resolveActionUrl(props.action.url ?? '#', props.execution))
const isExternal = computed(() => props.action.method === 'get' && href.value.startsWith('http'))
</script>

<template>
  <a
    :href="href"
    :target="isExternal ? '_blank' : undefined"
    :rel="isExternal ? 'noopener noreferrer' : undefined"
    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-md border border-input bg-background px-4 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
  >
    <component :is="iconComponent" v-if="iconComponent" class="h-4 w-4 shrink-0" />
    {{ action.label }}
  </a>
</template>
