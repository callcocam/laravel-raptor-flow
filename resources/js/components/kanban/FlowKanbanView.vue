<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import FlowDetailModal from './FlowDetailModal.vue';
import FlowKanbanBoard from './FlowKanbanBoard.vue';
import FlowKanbanHeader from './FlowKanbanHeader.vue';
import type {
  DetailModalConfig,
  FlowActionSchema,
  FlowKanbanActionRequest,
} from '../../types/detailModal';
import type {
  FlowKanbanBoardData,
  FlowKanbanBoardPayload,
  FlowKanbanBoardRawData,
  FlowKanbanBoardMeta,
  FlowKanbanExecution,
  FlowKanbanFilterConfig,
  FlowKanbanGroupConfig,
  FlowKanbanStep,
} from '../../types/kanban';
import type { FlowKanbanCardConfig } from '../../types/display';
import { computed, ref } from 'vue';

const ROUTER_OPTIONS = { preserveState: false, preserveScroll: false };

interface Props {
  board: FlowKanbanBoardPayload;
  /** Configs de grupo para validar drops entre colunas (ex: planogramas, projetos). */
  groupConfigs?: FlowKanbanGroupConfig[] | null;
  filters?: { data: FlowKanbanFilterConfig[] | null };
  userRoles?: string[];
  currentUserId?: string | null;
  title?: string;
  description?: string;
  showFilters?: boolean;
  cardConfig?: FlowKanbanCardConfig | null;
  /** Config do modal genérico. As ações devem ser FlowActionSchema[] gerados pelo backend. */
  detailModalConfig?: DetailModalConfig | null;
}

const props = withDefaults(defineProps<Props>(), {
  groupConfigs: () => [],
  filters: () => ({ data: null }),
  showFilters: true,
  cardConfig: null,
  detailModalConfig: null,
});

const emit = defineEmits<{
  move: [workableId: string, fromStepId: string, toStepId: string];
  cardClick: [execution: FlowKanbanExecution];
  'filters-applied': [filters: Record<string, unknown>];
  'filters-cleared': [];
  closeDetail: [];
  action: [execution: FlowKanbanExecution, action: FlowActionSchema, resolvedUrl: string, notes?: string, executed?: boolean];
}>();

const selectedExecution = ref<FlowKanbanExecution | null>(null);
const filterConfigs = computed(() => props.filters?.data ?? []);
const normalizedSteps = computed<FlowKanbanStep[]>(() =>
  isRawBoardData(props.board) ? props.board.steps : (props.board as unknown as FlowKanbanStep[])
);

const normalizedExecutions = computed<Record<string, FlowKanbanExecution[]>>(() =>
  isRawBoardData(props.board)
    ? props.board.executions
    : Object.fromEntries((props.board as FlowKanbanBoardData).map((template) => [template.id, template.executions]))
);

const resolvedCardConfig = computed<FlowKanbanCardConfig | null>(() => {
  if (props.cardConfig) {
    return props.cardConfig
  }

  const meta = (props.board as FlowKanbanBoardRawData & FlowKanbanBoardMeta)
  return meta.cardConfig ?? null
})

function isRawBoardData(board: FlowKanbanBoardPayload): board is FlowKanbanBoardRawData {
  return !Array.isArray(board);
}

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
  runRequest({
    url: `/flow/executions/${workableId}/move`,
    method: 'post',
    data: { to_step_id: toStepId },
  });

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
 * A execução principal acontece no FlowActionRenderer (fluxo centralizado).
 * Aqui mantemos apenas fallback legado e propagação de evento.
 */
function handleModalAction(
  execution: FlowKanbanExecution,
  action: FlowActionSchema,
  resolvedUrl: string,
  notes?: string,
  executed?: boolean,
) {
  emit('action', execution, action, resolvedUrl, notes, executed);

  // Fluxo centralizado: FlowActionRenderer já executou a ação.
  if (executed) {
    return
  }

  // Fallback para fluxos legados (ex.: NoteBlockRenderer) que ainda só emitem evento.
  if (resolvedUrl && resolvedUrl !== '#') {
    const data: Record<string, unknown> = { ...(action.data ?? {}) };
    if (action.type === 'notes' && notes !== undefined) {
      data.notes = notes;
    }
    runRequest({ url: resolvedUrl, method: action.method ?? 'post', data });
  }
}
</script>

<template>
  <div class="flex h-full flex-col">
    <div class="border-b border-border bg-background px-4">
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
        :steps="normalizedSteps"
        :executions="normalizedExecutions"
        :card-config="resolvedCardConfig"
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
      :steps="normalizedSteps"
      :current-user-id="currentUserId"
      :user-roles="userRoles"
      @close="handleCloseDetail"
      @action="handleModalAction"
    />
    <slot v-else name="detail-modal" />
  </div>
</template>
