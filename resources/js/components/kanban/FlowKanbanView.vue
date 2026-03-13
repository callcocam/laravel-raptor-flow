<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import FlowDetailModal from './FlowDetailModal.vue';
import FlowKanbanBoard from './FlowKanbanBoard.vue';
import FlowKanbanHeader from './FlowKanbanHeader.vue';
import type {
  DetailModalConfig,
  FlowActionSchema,
  FlowKanbanActionConfig,
  FlowKanbanActionRequest,
} from '../../types/detailModal';
import type {
  FlowKanbanBoardData,
  FlowKanbanExecution,
  FlowKanbanFilterConfig,
  FlowKanbanGroupConfig,
} from '../../types/kanban';
import { computed, ref } from 'vue';

const ROUTER_OPTIONS = { preserveState: true, preserveScroll: true };

interface Props {
  board: FlowKanbanBoardData;
  /** Configs de grupo para validar drops entre colunas (ex: planogramas, projetos). */
  groupConfigs?: FlowKanbanGroupConfig[] | null;
  filters?: { data: FlowKanbanFilterConfig[] | null };
  userRoles?: string[];
  currentUserId?: string | null;
  title?: string;
  description?: string;
  showFilters?: boolean;
  /** Config do modal genérico. As ações devem ser FlowActionSchema[] gerados pelo backend. */
  detailModalConfig?: DetailModalConfig | null;
  /**
   * @deprecated Use FlowActionSchema.url nas ações do detailModalConfig.
   * Mantido para compatibilidade com código existente.
   */
  actionConfig?: FlowKanbanActionConfig | null;
}

const props = withDefaults(defineProps<Props>(), {
  groupConfigs: () => [],
  filters: () => ({ data: null }),
  showFilters: true,
  detailModalConfig: null,
  actionConfig: null,
});

const emit = defineEmits<{
  move: [workableId: string, fromStepId: string, toStepId: string];
  cardClick: [execution: FlowKanbanExecution];
  'filters-applied': [filters: Record<string, unknown>];
  'filters-cleared': [];
  closeDetail: [];
  action: [execution: FlowKanbanExecution, action: FlowActionSchema, resolvedUrl: string, notes?: string];
}>();

const selectedExecution = ref<FlowKanbanExecution | null>(null);
const filterConfigs = computed(() => props.filters?.data ?? []);

function runRequest(req: FlowKanbanActionRequest) {
  const method = (req.method ?? 'post').toLowerCase();
  const payload = (req.data ?? {}) as Record<string, string | number | boolean | null | undefined>;
  if (method === 'get') {
    router.get(req.url, payload, ROUTER_OPTIONS);
  } else if (method === 'post') {
    router.post(req.url, payload, ROUTER_OPTIONS);
  } else if (method === 'patch' || method === 'put') {
    (router[method as 'patch' | 'put'] as (url: string, payload: object, opts: object) => void)(req.url, payload, ROUTER_OPTIONS);
  } else if (method === 'delete') {
    router.delete(req.url, ROUTER_OPTIONS);
  }
}

function handleMove(workableId: string, fromStepId: string, toStepId: string) {
  if (props.actionConfig?.move) {
    const req = props.actionConfig.move(workableId, fromStepId, toStepId);
    runRequest({ ...req, data: req.data ?? { to_step_id: toStepId } });
    return;
  }
  emit('move', workableId, fromStepId, toStepId);
}

function handleCardClick(execution: FlowKanbanExecution) {
  emit('cardClick', execution);
  if (props.detailModalConfig) {
    selectedExecution.value = execution;
  }
}

function handleCloseDetail() {
  selectedExecution.value = null;
  emit('closeDetail');
}

function handleApplyFilters(filters: Record<string, unknown>) {
  emit('filters-applied', filters);
}

function handleClearFilters() {
  emit('filters-cleared');
}

/**
 * Recebe o evento 'action' do FlowDetailModal.
 * Se a ação tem URL resolvido (backend-driven), executa via router.
 * Caso contrário, tenta o actionConfig legado.
 * Sempre emite o evento 'action' para que a página possa reagir também.
 */
function handleModalAction(
  execution: FlowKanbanExecution,
  action: FlowActionSchema,
  resolvedUrl: string,
  notes?: string,
) {
  emit('action', execution, action, resolvedUrl, notes);

  // Executa via router se a ação tem URL válido
  if (resolvedUrl && resolvedUrl !== '#') {
    const data: Record<string, unknown> = { ...(action.data ?? {}) };
    if (action.type === 'notes' && notes !== undefined) {
      data.notes = notes;
    }
    runRequest({ url: resolvedUrl, method: action.method ?? 'post', data });
    return;
  }

  // Fallback: actionConfig legado
  const ac = props.actionConfig;
  if (!ac) return;
  const legacyMap: Record<string, ((e: unknown) => FlowKanbanActionRequest) | undefined> = {
    start: ac.start,
    pause: ac.pause,
    resume: ac.resume,
    abandon: ac.abandon,
  };
  if (action.id === 'notes' && ac.updateNotes) {
    runRequest(ac.updateNotes(execution, notes ?? ''));
    return;
  }
  const fn = legacyMap[action.id];
  if (fn) {
    runRequest(fn(execution));
  }
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
        :group-configs="groupConfigs"
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

    <FlowDetailModal
      v-if="detailModalConfig"
      :execution="selectedExecution"
      :config="detailModalConfig"
      :steps="board.steps"
      :current-user-id="currentUserId"
      :user-roles="userRoles"
      @close="handleCloseDetail"
      @action="handleModalAction"
    />
    <slot v-else name="detail-modal" />
  </div>
</template>
