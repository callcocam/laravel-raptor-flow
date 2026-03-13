<script setup lang="ts">
import type { FlowKanbanExecution } from '../../types/kanban';
import { computed, inject, ref, type Ref } from 'vue';

interface Props {
  execution: FlowKanbanExecution;
  stepId: string;
  nextStepName?: string | null;
  previousStepName?: string | null;
  userRoles?: string[];
  requiredRole?: string | null;
  currentUserId?: string | null;
  cursorIsInvalid?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  cursorIsInvalid: false,
});

const emit = defineEmits<{
  click: [execution: FlowKanbanExecution];
}>();

const currentDragData = inject<Ref<{ groupId: string; fromStepId: string } | null>>(
  'flowKanbanDragData',
  ref(null)
);

const workable = computed(() => props.execution.workable);
const workableLabel = computed(() => workable.value?.name ?? '—');
const workableSubLabel = computed(() => workable.value?.group_label ?? null);

const canDrag = computed(() => {
  if (props.execution.permissions) return props.execution.permissions.can_move;
  return true;
});

const statusColor = computed(() => {
  switch (props.execution.status) {
    case 'pending':
      return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
    case 'in_progress':
      return 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300';
    case 'completed':
      return 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300';
    case 'blocked':
      return 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300';
    case 'skipped':
      return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300';
    default:
      return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
  }
});

const statusLabel = computed(() => {
  const labels: Record<string, string> = {
    pending: 'Pendente',
    in_progress: 'Em andamento',
    completed: 'Concluída',
    blocked: 'Bloqueada',
    skipped: 'Pulada',
  };
  return labels[props.execution.status] ?? props.execution.status;
});

const slaFormatted = computed(() => {
  const d = props.execution.sla_date;
  if (!d) return null;
  return new Date(d).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
});

function handleDragStart(event: DragEvent) {
  if (!event.dataTransfer) return;
  const groupId = String(props.execution.workable?.group_id ?? '');
  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData(
    'application/json',
    JSON.stringify({
      workableId: (workable.value as any)?.id ?? props.execution.id,
      fromStepId: props.stepId,
      executionId: props.execution.id,
      groupId,
    })
  );
  if (currentDragData) currentDragData.value = { groupId, fromStepId: props.stepId };
}

function handleDragEnd() {
  if (currentDragData) currentDragData.value = null;
}

function handleClick() {
  emit('click', props.execution);
}
</script>

<template>
  <div
    :draggable="canDrag"
    class="group w-full rounded-lg border bg-card p-3 shadow-sm transition-all"
    :class="[
      canDrag ? 'cursor-move hover:shadow-md' : 'cursor-default',
      execution.is_overdue ? 'border-red-500 dark:border-red-600' : 'border-border',
    ]"
    @dragstart="handleDragStart"
    @dragend="handleDragEnd"
  >
    <div class="mb-2 flex items-start justify-between gap-2">
      <div class="min-w-0 flex-1">
        <h4 class="font-medium break-words text-card-foreground">
          {{ workableLabel }}
        </h4>
        <span
          v-if="workableSubLabel"
          class="mt-1 block w-full text-xs break-words text-muted-foreground"
        >
          {{ workableSubLabel }}
        </span>
      </div>
      <span
        class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap"
        :class="statusColor"
      >
        {{ statusLabel }}
      </span>
    </div>

    <div
      v-if="execution.is_overdue"
      class="mb-2 flex items-center gap-1 text-xs text-destructive"
    >
      <span class="font-medium">Atrasada</span>
    </div>

    <div
      v-if="slaFormatted"
      class="mb-2 text-xs text-muted-foreground"
    >
      SLA: {{ slaFormatted }}
    </div>

    <div
      v-if="execution.currentResponsible && execution.status === 'in_progress'"
      class="mb-2 flex items-center gap-2 rounded bg-blue-50 px-2 py-1 dark:bg-blue-950/30"
    >
      <span class="truncate text-xs font-medium text-blue-700 dark:text-blue-300">
        {{ execution.currentResponsible.name }}
      </span>
      <span class="shrink-0 text-[10px] text-blue-600 dark:text-blue-400">Responsável</span>
    </div>

    <div
      v-if="previousStepName || nextStepName"
      class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs"
    >
      <span v-if="previousStepName" class="text-muted-foreground">← {{ previousStepName }}</span>
      <span v-if="nextStepName" class="text-muted-foreground">{{ nextStepName }} →</span>
    </div>

    <div
      v-if="execution.notes"
      class="mt-2 line-clamp-2 border-t pt-2 text-xs text-muted-foreground"
    >
      {{ execution.notes }}
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2 border-t pt-2">
      <button
        type="button"
        class="inline-flex h-8 items-center justify-center rounded-md border border-input bg-background px-3 text-sm hover:bg-accent"
        @click.stop="handleClick"
      >
        Detalhes
      </button>
      <slot name="actions" :execution="execution" />
    </div>
  </div>
</template>
