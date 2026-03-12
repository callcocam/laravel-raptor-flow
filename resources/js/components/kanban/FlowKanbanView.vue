<script setup lang="ts">
import FlowKanbanBoard from './FlowKanbanBoard.vue';
import FlowKanbanHeader from './FlowKanbanHeader.vue';
import type {
  FlowKanbanBoardData,
  FlowKanbanExecution,
  FlowKanbanFilterConfig,
  FlowKanbanPlanogramOption,
} from '../../types/kanban';
import { computed } from 'vue';

interface Props {
  board: FlowKanbanBoardData;
  planograms?: FlowKanbanPlanogramOption[] | null;
  filters?: { data: FlowKanbanFilterConfig[] | null };
  userRoles?: string[];
  currentUserId?: string | null;
  title?: string;
  description?: string;
  showFilters?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  planograms: () => [],
  filters: () => ({ data: null }),
  showFilters: true,
});

const emit = defineEmits<{
  move: [workableId: string, fromStepId: string, toStepId: string];
  cardClick: [execution: FlowKanbanExecution];
  'filters-applied': [filters: Record<string, unknown>];
  'filters-cleared': [];
}>();

const filterConfigs = computed(() => props.filters?.data ?? []);

function handleMove(workableId: string, fromStepId: string, toStepId: string) {
  emit('move', workableId, fromStepId, toStepId);
}

function handleCardClick(execution: FlowKanbanExecution) {
  emit('cardClick', execution);
}

function handleApplyFilters(filters: Record<string, unknown>) {
  emit('filters-applied', filters);
}

function handleClearFilters() {
  emit('filters-cleared');
}
</script>

<template>
  <div class="flex h-full flex-col">
    <div class="border-b border-border bg-background">
      <FlowKanbanHeader
        :title="title"
        :description="description"
        :filter-configs="filterConfigs"
        :show-filters="showFilters"
        @apply="handleApplyFilters"
        @clear="handleClearFilters"
      >
        <template #actions>
          <slot name="header-actions" />
        </template>
        <template v-if="$slots['extra-filters']" #extra-filters="slotProps">
          <slot name="extra-filters" v-bind="slotProps" />
        </template>
      </FlowKanbanHeader>
    </div>

    <div class="flex-1 overflow-x-auto overflow-y-hidden">
      <FlowKanbanBoard
        :steps="board.steps"
        :executions="board.executions"
        :planograms="planograms"
        :user-roles="userRoles"
        :current-user-id="currentUserId"
        @move="handleMove"
        @card-click="handleCardClick"
      >
        <template v-if="$slots.cardActions" #cardActions="slotProps">
          <slot name="cardActions" v-bind="slotProps" />
        </template>
      </FlowKanbanBoard>
    </div>

    <slot name="detail-modal" />
  </div>
</template>
