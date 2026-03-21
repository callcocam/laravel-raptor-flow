<script setup lang="ts">
import type { FlowKanbanFilterConfig } from '../../types/kanban';
import { computed, ref, watch } from 'vue';

interface Props {
  filterConfigs?: FlowKanbanFilterConfig[];
}

const props = withDefaults(defineProps<Props>(), {
  filterConfigs: () => [],
});

const emit = defineEmits<{
  apply: [filters: Record<string, unknown>];
  clear: [];
}>();

const filterValues = ref<Record<string, unknown>>({
  only_overdue: false,
  show_completed: false,
});

const activeFiltersCount = computed(() =>
  Object.entries(filterValues.value).filter(([, value]) => {
    if (value === null || value === undefined || value === '') return false;
    if (value === false) return false;
    return true;
  }).length
);

const hasActiveFilters = computed(() => activeFiltersCount.value > 0);

function updateFilter(name: string, value: unknown) {
  if (value === null || value === undefined || value === '') {
    const next = { ...filterValues.value };
    delete next[name];
    filterValues.value = next;
  } else {
    filterValues.value = { ...filterValues.value, [name]: value };
  }
  emit('apply', { ...filterValues.value });
}

function clearFilters() {
  filterValues.value = { only_overdue: false, show_completed: false };
  emit('clear');
}

function initializeFromQuery() {
  const params = new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '');
  props.filterConfigs.forEach((filter) => {
    const v = params.get(filter.name);
    if (v !== null && v !== '') filterValues.value[filter.name] = v;
  });
  filterValues.value.only_overdue = params.get('only_overdue') === 'true' || params.get('only_overdue') === '1';
  filterValues.value.show_completed = params.get('show_completed') === 'true' || params.get('show_completed') === '1';
}

initializeFromQuery();

watch(
  () => props.filterConfigs,
  () => initializeFromQuery(),
  { deep: true }
);
</script>

<template>
  <div
    v-if="filterConfigs.length > 0"
    class="flex flex-wrap items-end gap-3 rounded-lg border border-border bg-muted/30 px-4 py-3"
  >
    <div
      v-for="filter in filterConfigs"
      :key="filter.name"
      :class="['w-44', filter.classes ?? '']"
    >
      <label v-if="filter.label" :for="`flow-filter-${filter.name}`" class="mb-1 block text-xs font-medium text-foreground">
        {{ filter.label }}
      </label>
      <select
        :id="`flow-filter-${filter.name}`"
        :value="filterValues[filter.name] ?? ''"
        class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
        @change="updateFilter(filter.name, ($event.target as HTMLSelectElement).value || null)"
      >
        <option value="">{{ filter.placeholder ?? 'Todos' }}</option>
        <option
          v-for="opt in (filter.options ?? [])"
          :key="String(opt.value)"
          :value="opt.value"
        >
          {{ opt.label }}
        </option>
      </select>
    </div>

    <slot name="extra-filters" :filter-values="filterValues" :update-filter="updateFilter" />

    <div class="flex flex-col justify-end gap-2 pb-0.5">
      <div class="flex items-center gap-2">
        <input
          id="flow-kanban-only-overdue"
          type="checkbox"
          class="h-4 w-4 rounded border-input"
          :checked="!!filterValues.only_overdue"
          @change="updateFilter('only_overdue', (event.target as HTMLInputElement).checked)"
        />
        <label for="flow-kanban-only-overdue" class="cursor-pointer text-xs text-foreground">
          Apenas atrasadas
        </label>
      </div>
      <div class="flex items-center gap-2">
        <input
          id="flow-kanban-show-completed"
          type="checkbox"
          class="h-4 w-4 rounded border-input"
          :checked="!!filterValues.show_completed"
          @change="updateFilter('show_completed', (event.target as HTMLInputElement).checked)"
        />
        <label for="flow-kanban-show-completed" class="cursor-pointer text-xs text-foreground">
          Mostrar concluídas
        </label>
      </div>
    </div>

    <div class="ml-auto flex items-end gap-2 pb-0.5">
      <span
        v-if="activeFiltersCount > 0"
        class="inline-flex h-5 items-center rounded-full bg-secondary px-2 text-[10px] font-medium"
      >
        {{ activeFiltersCount }}
      </span>
      <button
        v-if="hasActiveFilters"
        type="button"
        class="inline-flex h-9 items-center gap-1.5 rounded-md px-2.5 text-xs text-muted-foreground hover:bg-accent hover:text-foreground"
        @click="clearFilters"
      >
        Limpar filtros
      </button>
    </div>
  </div>
</template>
