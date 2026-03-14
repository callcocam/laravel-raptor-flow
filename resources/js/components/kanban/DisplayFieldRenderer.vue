<script setup lang="ts">
import { resolveActionUrl } from '../../composables/useFlowAction'
import { badgeClass, formatDisplayValue, resolveCardItemValue, resolveFieldValue } from '../../composables/display'
import type { DisplayCardItemConfig, DisplayFieldConfig } from '../../types/display'
import type { FlowKanbanExecution, FlowKanbanStep } from '../../types/kanban'
import { CheckCircle, ExternalLink } from 'lucide-vue-next'
import { computed } from 'vue'

interface Props {
  field: DisplayFieldConfig
  execution: FlowKanbanExecution
  steps?: FlowKanbanStep[]
  currentUserId?: string | null
  mode?: 'modal' | 'card'
}

const props = withDefaults(defineProps<Props>(), {
  steps: () => [],
  currentUserId: null,
  mode: 'modal',
})

const fieldValue = computed(() => resolveFieldValue(props.execution, props.field))
const isCardMode = computed(() => props.mode === 'card')
const currentStepId = computed(() => props.execution.workflow_step_template_id ?? '')

function formatField(): string {
  return formatDisplayValue(fieldValue.value, props.field.format ?? props.field.type)
}

function formatCard(card: DisplayCardItemConfig): string {
  return resolveCardItemValue(props.execution, card)
}

function resolveUrl(): string {
  if (!props.field.url) {
    return '#'
  }

  return resolveActionUrl(props.field.url, props.execution)
}
</script>

<template>
  <p
    v-if="field.type === 'text' || field.type === 'label'"
    :class="field.type === 'label'
      ? (isCardMode ? 'text-sm font-medium text-card-foreground' : 'text-sm font-medium text-foreground')
      : (isCardMode ? 'text-xs text-muted-foreground' : 'text-sm text-foreground')"
  >
    {{ formatField() }}
  </p>

  <p v-else-if="field.type === 'textarea'" class="whitespace-pre-wrap text-sm text-foreground">
    {{ fieldValue ?? '—' }}
  </p>

  <p v-else-if="field.type === 'date' || field.type === 'datetime'" :class="isCardMode ? 'text-xs text-muted-foreground' : 'text-sm text-foreground'">
    {{ formatField() }}
  </p>

  <span
    v-else-if="field.type === 'badge'"
    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
    :class="badgeClass(fieldValue, field.variant)"
  >
    {{ formatField() }}
  </span>

  <a
    v-else-if="field.type === 'link'"
    :href="resolveUrl()"
    :target="field.external ? '_blank' : undefined"
    :rel="field.external ? 'noopener noreferrer' : undefined"
    :class="isCardMode ? 'text-xs font-medium text-primary hover:underline' : 'inline-flex items-center gap-1 text-sm font-medium text-primary transition-colors hover:underline'"
  >
    {{ field.label ?? formatField() }}
    <ExternalLink v-if="field.external && !isCardMode" class="h-3 w-3" />
  </a>

  <div v-else-if="field.type === 'cards'" :class="isCardMode ? 'grid gap-2 sm:grid-cols-2' : 'grid gap-3 sm:grid-cols-2'">
    <div
      v-for="card in field.cards ?? []"
      :key="`${field.key}-${card.key}`"
      :class="isCardMode ? 'rounded-md border bg-background/70 p-2' : 'rounded-lg border bg-background p-3'"
    >
      <p v-if="card.label" :class="isCardMode ? 'text-[11px] text-muted-foreground' : 'text-xs text-muted-foreground'">{{ card.label }}</p>
      <p :class="isCardMode ? 'text-xs font-semibold text-card-foreground' : 'mt-1 text-sm font-semibold text-foreground'">{{ formatCard(card) }}</p>
    </div>
  </div>

  <div v-else-if="field.type === 'timeline'" class="relative py-2">
    <div v-if="steps.length" class="relative">
      <div class="absolute left-0 right-0 top-5 h-0.5 bg-border" />
      <div class="relative flex justify-between">
        <div
          v-for="(step, index) in steps"
          :key="step.id"
          class="flex flex-col items-center"
        >
          <div
            class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all"
            :class="[
              step.id === currentStepId
                ? 'border-primary bg-primary text-primary-foreground shadow-lg ring-4 ring-primary/20'
                : 'border-muted-foreground/30 bg-card',
            ]"
          >
            <CheckCircle
              v-if="steps.findIndex((s: { id: string }) => s.id === currentStepId) > (index as number)"
              class="h-5 w-5 text-green-600 dark:text-green-400"
            />
            <span v-else class="text-xs font-bold">{{ index + 1 }}</span>
          </div>
          <div
            class="mt-2 max-w-[100px] text-center text-xs font-medium"
            :class="step.id === currentStepId ? 'text-primary' : 'text-muted-foreground'"
          >
            {{ step.name }}
          </div>
        </div>
      </div>
    </div>
    <p v-else class="text-sm text-muted-foreground">Nenhuma etapa configurada.</p>
  </div>

  <div v-else-if="field.type === 'selectUsers'" class="flex flex-wrap gap-2">
    <template v-for="user in (execution.users ?? execution.config?.users ?? [])" :key="(user as any).id">
      <span
        class="inline-flex items-center rounded-full border px-2 py-1 text-xs font-medium"
        :class="
          (user as any).id === currentUserId
            ? 'border-green-600 bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
            : 'border-border bg-muted/50 text-muted-foreground'
        "
      >
        {{ (user as any).name }}
        <span v-if="(user as any).id === currentUserId" class="ml-1">(você)</span>
      </span>
    </template>
    <span
      v-if="!(execution.users?.length ?? execution.config?.users?.length)"
      class="text-sm text-muted-foreground"
    >
      —
    </span>
  </div>

  <p v-else class="text-sm text-muted-foreground">
    {{ String(fieldValue ?? '—') }}
  </p>
</template>
