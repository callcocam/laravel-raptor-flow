<!--
  FlowActionConfirm - Botão de ação com diálogo de confirmação
  Abre um AlertDialog antes de emitir 'execute'.
  Seguindo o padrão do ActionConfirm.vue do laravel-raptor.
-->
<script setup lang="ts">
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog'
import { variantClass, useActionIcon } from '../../../composables/useFlowAction'
import type { FlowActionSchema } from '../../../types/detailModal'
import type { FlowKanbanExecution } from '../../../types/kanban'
import { ref } from 'vue'

const props = defineProps<{
  action: FlowActionSchema
  execution: FlowKanbanExecution
}>()

const emit = defineEmits<{ execute: [] }>()

const isOpen = ref(false)
const iconComponent = useActionIcon(props.action)

function handleConfirm() {
  isOpen.value = false
  emit('execute')
}
</script>

<template>
  <AlertDialog v-model:open="isOpen">
    <AlertDialogTrigger as-child>
      <button
        type="button"
        class="inline-flex h-9 items-center justify-center gap-1.5 rounded-md px-4 text-sm font-medium transition-colors disabled:opacity-50"
        :class="variantClass(action.variant)"
      >
        <component :is="iconComponent" v-if="iconComponent" class="h-4 w-4 shrink-0" />
        {{ action.label }}
      </button>
    </AlertDialogTrigger>

    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>{{ action.confirm?.title ?? 'Confirmar ação' }}</AlertDialogTitle>
        <AlertDialogDescription v-if="action.confirm?.description">
          {{ action.confirm.description }}
        </AlertDialogDescription>
      </AlertDialogHeader>

      <AlertDialogFooter>
        <AlertDialogCancel>Cancelar</AlertDialogCancel>
        <AlertDialogAction
          :class="action.variant === 'destructive' ? 'bg-destructive text-destructive-foreground hover:bg-destructive/90' : ''"
          @click="handleConfirm"
        >
          Confirmar
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>
