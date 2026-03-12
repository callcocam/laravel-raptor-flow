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
  <div class="bg-card px-1 py-1">
    <div v-if="title || description || $slots.actions" class="mb-4 flex items-center justify-between">
      <div>
        <h1 v-if="title" class="text-2xl font-bold tracking-tight text-foreground">{{ title }}</h1>
        <p v-if="description" class="text-sm text-muted-foreground">{{ description }}</p>
      </div>
      <div class="flex items-center gap-2">
        <slot name="actions" />
      </div>
    </div>

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
