<script setup lang="ts">
import type { FlowKanbanExecution, FlowKanbanStep } from '../../../types/kanban'
import { CheckCircle, Flag } from 'lucide-vue-next'
import { computed } from 'vue'

interface Props {
  execution: FlowKanbanExecution
  steps?: FlowKanbanStep[]
}

const props = withDefaults(defineProps<Props>(), {
  steps: () => [],
})

const currentStepId = computed(() => props.execution.workflow_step_template_id ?? '')
const currentStepIndex = computed(() => props.steps.findIndex((item) => item.id === currentStepId.value))
const isLastWorkflowStep = computed(() => currentStepIndex.value !== -1 && currentStepIndex.value === props.steps.length - 1)
</script>

<template>
  <div class="relative py-2">
    <div
      v-if="steps.length && isLastWorkflowStep"
      class="mb-3 inline-flex items-center gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 dark:border-emerald-900/80 dark:bg-emerald-950/40 dark:text-emerald-300"
    >
      <Flag class="h-4 w-4" />
      Ultima etapa do workflow
    </div>

    <div v-if="steps.length" class="relative">
      <div class="absolute left-0 right-0 top-5 h-0.5 bg-border" />
      <div class="relative flex justify-between">
        <div v-for="(step, index) in steps" :key="step.id" class="flex flex-col items-center">
          <div
            class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all"
            :class="[
              step.id === currentStepId
                ? isLastWorkflowStep
                  ? 'border-emerald-500 bg-emerald-500 text-white shadow-lg ring-4 ring-emerald-500/20 dark:border-emerald-400 dark:bg-emerald-400 dark:text-emerald-950 dark:ring-emerald-400/20'
                  : 'border-primary bg-primary text-primary-foreground shadow-lg ring-4 ring-primary/20'
                : 'border-muted-foreground/30 bg-card',
            ]"
          >
            <CheckCircle
              v-if="currentStepIndex > index"
              class="h-5 w-5 text-green-600 dark:text-green-400"
            />
            <span v-else class="text-xs font-bold">{{ index + 1 }}</span>
          </div>
          <div
            class="mt-2 max-w-[100px] text-center text-xs font-medium"
            :class="step.id === currentStepId ? (isLastWorkflowStep ? 'text-emerald-600 dark:text-emerald-400' : 'text-primary') : 'text-muted-foreground'"
          >
            {{ step.name }}
          </div>
        </div>
      </div>
    </div>
    <p v-else class="text-sm text-muted-foreground">Nenhuma etapa configurada.</p>
  </div>
</template>
