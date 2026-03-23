<script setup lang="ts">
import FlowKanbanCard from './FlowKanbanCard.vue';
import type { FlowKanbanCardConfig } from '../../types/display';
import type { FlowKanbanExecution, FlowKanbanGroupConfig, FlowKanbanStep } from '../../types/kanban';
import { computed, inject, ref, type Ref } from 'vue';

interface Props {
  step: FlowKanbanStep;
  executions: FlowKanbanExecution[];
  cardConfig?: FlowKanbanCardConfig | null;
  /** Configs de grupo para validação de drop (ex: planogramas, projetos). */
  groupConfigs?: FlowKanbanGroupConfig[] | null;
  userRoles?: string[];
  currentUserId?: string | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  move: [workableId: string, fromStepId: string, toStepId: string];
  cardClick: [execution: FlowKanbanExecution];
}>();

const isDragOver = ref(false);
const localSearch = ref('');
const currentDragData = inject<Ref<{ groupId: string; fromStepId: string } | null>>(
  'flowKanbanDragData',
  ref(null)
);

function normalizeText(value: unknown): string {
  return String(value ?? '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim();
}

const filteredExecutions = computed(() => {
  const query = normalizeText(localSearch.value);

  if (!query) {
    return props.executions;
  }

  return props.executions.filter((execution) => {
    const workableName = normalizeText(execution.workable?.name);
    const workableId = normalizeText(execution.workable?.id);
    const executionId = normalizeText(execution.id);

    return workableName.includes(query) || workableId.includes(query) || executionId.includes(query);
  });
});

const isInvalidDrop = computed(() => {
  if (!isDragOver.value || !currentDragData.value) return false;
  if (currentDragData.value.fromStepId === props.step.id) return true;
  return !canDropInColumn(currentDragData.value.groupId, props.step.id);
});

const overdueCount = computed(() =>
  filteredExecutions.value.filter((e) => e.is_overdue).length
);

function canDropInColumn(groupId: string, targetStepId: string): boolean {
  if (!props.groupConfigs?.length) return true;
  const group = props.groupConfigs.find((g) => g.id === groupId);
  if (!group?.stepIds?.length) return true;
  return group.stepIds.includes(targetStepId);
}

function handleDrop(event: DragEvent) {
  event.preventDefault();
  isDragOver.value = false;
  const raw = event.dataTransfer?.getData('application/json');
  if (!raw) return;
  try {
    const data = JSON.parse(raw);
    const workableId = data.executionId ?? data.workableId;
    const fromStepId = data.fromStepId;
    const groupId = data.groupId ?? '';
    if (fromStepId === props.step.id) return;
    if (!canDropInColumn(groupId, props.step.id)) return;
    emit('move', workableId, fromStepId, props.step.id);
  } catch {
    // ignore
  } finally {
    if (currentDragData) currentDragData.value = null;
  }
}

function handleDragOver(event: DragEvent) {
  event.preventDefault();
  isDragOver.value = true;
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = isInvalidDrop.value ? 'none' : 'move';
  }
}

function handleDragLeave() {
  isDragOver.value = false;
}

function handleCardClick(execution: FlowKanbanExecution) {
  emit('cardClick', execution);
}

const columnColor = computed(() => props.step.color ?? '#6b7280');
</script>

<template>
  <div
    class="flex h-full w-80 shrink-0 flex-col rounded-lg border bg-card transition-all"
    :class="{
      'ring-2 ring-primary': isDragOver && !isInvalidDrop,
      'ring-2 ring-destructive bg-destructive/5': isInvalidDrop,
    }"
    :style="{ borderTopWidth: '3px', borderTopColor: columnColor }"
  >
    <div class="sticky top-0 z-10 space-y-2 border-b bg-card p-3 rounded-t-lg">
      <div class="flex items-start justify-between gap-2">
        <div class="min-w-0 flex-1">
          <h3 class="font-semibold text-foreground truncate">{{ step.name }}</h3>
          <p v-if="step.description" class="text-xs text-muted-foreground truncate">
            {{ step.description }}
          </p>
        </div>
        <div class="ml-2 flex shrink-0 items-center gap-1.5">
          <span
            class="flex h-6 min-w-6 items-center justify-center rounded-full bg-muted px-2 text-xs font-medium"
          >
            {{ filteredExecutions.length }}
          </span>
          <span
            v-if="overdueCount > 0"
            class="flex h-6 min-w-6 items-center justify-center rounded-full bg-destructive px-2 text-xs font-medium text-destructive-foreground"
            title="Atrasadas"
          >
            {{ overdueCount }}
          </span>
        </div>
      </div>

      <input
        v-model="localSearch"
        type="text"
        placeholder="Buscar gôndola"
        class="h-8 w-full rounded-md border border-input bg-background px-2 text-xs"
      />
    </div>

    <div
      class="flex-1 space-y-2 overflow-y-auto overflow-x-hidden p-3"
      @drop="handleDrop"
      @dragover="handleDragOver"
      @dragleave="handleDragLeave"
    >
      <FlowKanbanCard
        v-for="execution in filteredExecutions"
        :key="execution.id"
        :execution="execution"
        :step-id="step.id"
        :card-config="cardConfig"
        :user-roles="userRoles"
        :required-role="(execution as any).config?.responsible_role?.slug"
        :current-user-id="currentUserId"
        :cursor-is-invalid="isInvalidDrop"
        @click="handleCardClick(execution)"
      >
        <template v-if="$slots.cardActions" #actions="slotProps">
          <slot name="cardActions" v-bind="slotProps" />
        </template>
      </FlowKanbanCard>

      <div
        v-if="filteredExecutions.length === 0"
        class="flex h-20 items-center justify-center rounded-lg border-2 border-dashed text-xs text-muted-foreground"
      >
        Nenhuma gôndola encontrada
      </div>
    </div>
  </div>
</template>
