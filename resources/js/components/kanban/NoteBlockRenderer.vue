<script setup lang="ts">
import type { NotesBlockConfig } from '../../types/display'
import type { FlowKanbanExecution } from '../../types/kanban'
import { ref, watch } from 'vue'

const props = defineProps<{
  note: NotesBlockConfig
  execution: FlowKanbanExecution
}>()

const emit = defineEmits<{
  save: [note: NotesBlockConfig, text: string]
}>()

const localValue = ref('')

watch(
  () => props.execution?.notes ?? '',
  (value) => {
    localValue.value = value
  },
  { immediate: true },
)
</script>

<template>
  <div class="w-full space-y-2">
    <label class="block text-xs font-medium text-muted-foreground">
      {{ note.label }}
    </label>
    <textarea
      v-model="localValue"
      class="w-full min-h-[80px] rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
      :placeholder="note.placeholder ?? 'Adicionar notas...'"
    />
    <button
      type="button"
      class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
      @click="emit('save', note, localValue)"
    >
      Salvar notas
    </button>
  </div>
</template>
