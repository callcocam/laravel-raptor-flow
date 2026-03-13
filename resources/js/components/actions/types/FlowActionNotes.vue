<!--
  FlowActionNotes - Textarea de notas + botão salvar
  type: 'notes' — renderiza área de texto com estado local.
  Emite 'execute' com as notas ao clicar em Salvar.
-->
<script setup lang="ts">
import type { FlowActionSchema } from '../../../types/detailModal'
import type { FlowKanbanExecution } from '../../../types/kanban'
import { ref, watch } from 'vue'

const props = defineProps<{
  action: FlowActionSchema
  execution: FlowKanbanExecution
}>()

const emit = defineEmits<{ execute: [notes: string] }>()

const localNotes = ref('')

watch(
  () => props.execution?.notes ?? '',
  (v) => { localNotes.value = v },
  { immediate: true },
)
</script>

<template>
  <div class="w-full space-y-2">
    <label class="block text-xs font-medium text-muted-foreground">
      {{ action.label }}
    </label>
    <textarea
      v-model="localNotes"
      class="w-full min-h-[80px] rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
      :placeholder="action.placeholder ?? 'Adicionar notas...'"
    />
    <button
      type="button"
      class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
      @click="emit('execute', localNotes)"
    >
      Salvar notas
    </button>
  </div>
</template>
