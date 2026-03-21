<script setup lang="ts">
import FlowKanbanFilters from './FlowKanbanFilters.vue';
import type { FlowKanbanFilterConfig } from '../../types/kanban';

interface Props {
  title?: string;
  description?: string;
  filterConfigs?: FlowKanbanFilterConfig[];
  showFilters?: boolean;
}

withDefaults(defineProps<Props>(), {
  title: 'Kanban - Workflow',
  description: '',
  filterConfigs: () => [],
  showFilters: true,
});

const emit = defineEmits<{
  apply: [filters: Record<string, unknown>];
  clear: [];
}>();
</script>

<template>
  <div class=" px-1 py-1"> 
    <FlowKanbanFilters
      v-if="showFilters && filterConfigs && filterConfigs.length > 0"
      :filter-configs="filterConfigs"
      @apply="emit('apply', $event)"
      @clear="emit('clear')"
    >
      <template v-if="$slots['extra-filters']" #extra-filters="slotProps">
        <slot name="extra-filters" v-bind="slotProps" />
      </template>
    </FlowKanbanFilters>
  </div>
</template>
