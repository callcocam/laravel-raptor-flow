<script setup lang="ts">
import type { FlowKanbanCardConfig, FlowKanbanCardLinkConfig } from '../../types/display';
import type { FlowActionSchema } from '../../types/detailModal';
import type { FlowKanbanExecution } from '../../types/kanban';
import { resolveDisplayValue } from '../../composables/display';
import FlowActionRenderer from '../actions/FlowActionRenderer.vue';
import DisplayFieldRenderer from './DisplayFieldRenderer.vue';
import { CheckCircle2, Flag } from 'lucide-vue-next';
import { computed, inject, ref, type Ref } from 'vue';

interface Props {
  execution: FlowKanbanExecution;
  stepId: string;
  cardConfig?: FlowKanbanCardConfig | null;
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

const canDrag = computed(() => {
  return Boolean(props.execution.action_visibility?.move);
});

const isLastWorkflowStep = computed(() => props.execution.templateNextStep == null);

function hasFieldValue(value: unknown): boolean {
  if (value === null || value === undefined || value === '') {
    return false;
  }

  if (Array.isArray(value)) {
    return value.length > 0;
  }

  if (typeof value === 'object') {
    return Object.keys(value as Record<string, unknown>).length > 0;
  }

  return true;
}

const configuredColumns = computed(() => {
  return (props.cardConfig?.columns ?? [])
    .map((column) => {
      const showWhenEmpty = Boolean(column.showWhenEmpty)

      return {
        ...column,
        fields: column.fields.filter((field) => {
          if (field.key === 'notes') {
            return false
          }

          return showWhenEmpty || hasFieldValue(resolveDisplayValue(props.execution, field.key))
        }),
      }
    })
    .filter((column) => column.fields.length > 0)
});
const cardActions = computed(() => props.execution.card_actions ?? []);
const cardLinks = computed(() => {
  const links = props.execution.card_links ?? props.cardConfig?.links ?? [];

  return [...links].sort((left, right) => {
    const priorityLeft = Number(left.priority ?? 0);
    const priorityRight = Number(right.priority ?? 0);

    if (priorityLeft !== priorityRight) {
      return priorityLeft - priorityRight;
    }

    return (left.key ?? '').localeCompare(right.key ?? '');
  });
});
const primaryCardLinks = computed(() => cardLinks.value.filter((link) => (link.position ?? 'secondary') === 'primary'));
const secondaryCardLinks = computed(() => cardLinks.value.filter((link) => (link.position ?? 'secondary') !== 'primary'));

function toLinkAction(link: FlowKanbanCardLinkConfig, group: 'primary' | 'secondary'): FlowActionSchema {
  return {
    id: `card-link-${group}-${link.key}`,
    type: 'link',
    label: link.label,
    url: link.url,
    method: 'get',
    variant: group === 'primary' ? 'outline' : 'ghost',
    component: 'flow-action-link',
    data: {
      external: Boolean(link.external),
    },
  };
}

const primaryRenderedActions = computed<FlowActionSchema[]>(() => [
  ...primaryCardLinks.value.map((link) => toLinkAction(link, 'primary')),
  ...cardActions.value,
]);

const secondaryRenderedActions = computed<FlowActionSchema[]>(() =>
  secondaryCardLinks.value.map((link) => toLinkAction(link, 'secondary')),
);

const shouldRenderLegacySlot = computed(() =>
  primaryRenderedActions.value.length === 0 && secondaryRenderedActions.value.length === 0,
);

function handleClick() {
  emit('click', props.execution);
}

function handleDragStart(event: DragEvent) {
  if (!canDrag.value || !event.dataTransfer) return;

  const groupId = String(props.execution.workable?.group_id ?? '');
  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData(
    'application/json',
    JSON.stringify({
      workableId: (props.execution.workable as any)?.id ?? props.execution.id,
      fromStepId: props.stepId,
      executionId: props.execution.id,
      groupId,
    })
  );

  if (currentDragData) {
    currentDragData.value = { groupId, fromStepId: props.stepId };
  }
}

function handleDragEnd() {
  if (currentDragData) {
    currentDragData.value = null;
  }
}

function handleActionExecuted() {
  // no-op: FlowActionRenderer executa a ação internamente.
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
    <div class="space-y-3">
      <div v-if="isLastWorkflowStep" class="flex items-center gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-2 text-emerald-700 dark:border-emerald-900/80 dark:bg-emerald-950/40 dark:text-emerald-300">
        <Flag class="h-4 w-4 shrink-0" />
        <span class="text-xs font-semibold uppercase tracking-wide">Ultima etapa do workflow</span>
      </div>

      <div v-for="column in configuredColumns" :key="column.id" class="space-y-2">
        <p v-if="column.label" class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
          {{ column.label }}
        </p>

        <div class="space-y-2">
          <template v-for="field in column.fields" :key="`${column.id}-${field.key}`">
            <p v-if="field.label && field.type !== 'link' && !field.component" class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
              {{ field.label }}
            </p>

            <DisplayFieldRenderer
              :field="field"
              :execution="execution"
              mode="card"
            />
          </template>
        </div>
      </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2 border-t pt-2">
      <button
        type="button"
        class="inline-flex h-8 items-center justify-center rounded-md border border-input bg-background px-3 text-sm hover:bg-accent"
        @click.stop="handleClick"
      >
        Detalhes
      </button>

      <FlowActionRenderer
        v-for="action in primaryRenderedActions"
        :key="action.id"
        :action="action"
        :execution="execution"
        @executed="handleActionExecuted"
      />

      <slot v-if="shouldRenderLegacySlot" name="actions" :execution="execution" />

      <span
        v-if="isLastWorkflowStep"
        class="inline-flex h-8 items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-2.5 text-xs font-medium text-emerald-700 dark:border-emerald-900/80 dark:bg-emerald-950/40 dark:text-emerald-300"
      >
        <CheckCircle2 class="h-3.5 w-3.5" />
        Etapa final
      </span>
    </div>

    <div v-if="secondaryRenderedActions.length" class="mt-2 flex flex-wrap items-center gap-2">
      <FlowActionRenderer
        v-for="action in secondaryRenderedActions"
        :key="action.id"
        :action="action"
        :execution="execution"
        @executed="handleActionExecuted"
      />
    </div>
  </div>
</template>
