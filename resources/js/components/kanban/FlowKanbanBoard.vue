<script setup lang="ts">
import FlowKanbanColumn from './FlowKanbanColumn.vue';
import type {
  FlowKanbanExecution,
  FlowKanbanGroupConfig,
  FlowKanbanStep,
} from '../../types/kanban';
import { ref, provide } from 'vue';

interface Props {
  steps: FlowKanbanStep[];
  executions: Record<string, FlowKanbanExecution[]>;
  /** Configs de grupo para validação de drop entre colunas. */
  groupConfigs?: FlowKanbanGroupConfig[] | null;
  userRoles?: string[];
  currentUserId?: string | null;
}

defineProps<Props>();

const emit = defineEmits<{
  move: [workableId: string, fromStepId: string, toStepId: string];
  cardClick: [execution: FlowKanbanExecution];
}>();

const currentDragData = ref<{ groupId: string; fromStepId: string } | null>(null);
provide('flowKanbanDragData', currentDragData);

function handleMove(workableId: string, fromStepId: string, toStepId: string) {
  emit('move', workableId, fromStepId, toStepId);
}

function handleCardClick(execution: FlowKanbanExecution) {
  emit('cardClick', execution);
}
</script>

<template>
  <div class="flex h-full gap-4 px-4 py-3" style="min-width: max-content">
    <FlowKanbanColumn
      v-for="step in steps"
      :key="step.id"
      :step="step"
      :executions="executions[step.id] ?? []"
      :group-configs="groupConfigs"
      :user-roles="userRoles"
      :current-user-id="currentUserId"
      @move="handleMove"
      @card-click="handleCardClick"
    >
      <template v-if="$slots.cardActions" #cardActions="slotProps">
        <slot name="cardActions" v-bind="slotProps" />
      </template>
    </FlowKanbanColumn>
  </div>
</template>
