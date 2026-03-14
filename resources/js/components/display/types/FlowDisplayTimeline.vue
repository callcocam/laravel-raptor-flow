<script setup lang="ts">
import type { FlowKanbanExecution, FlowKanbanStep } from '../../../types/kanban'
import { CheckCircle } from 'lucide-vue-next'
import { computed } from 'vue'

interface Props {
  execution: FlowKanbanExecution
  steps?: FlowKanbanStep[]
}

const props = withDefaults(defineProps<Props>(), {
  steps: () => [],
})

const currentStepId = computed(() => props.execution.workflow_step_template_id ?? '')
</script>

<template>
  <div class="relative py-2">
    <div v-if="steps.length" class="relative">
      <div class="absolute left-0 right-0 top-5 h-0.5 bg-border" />
      <div class="relative flex justify-between">
        <div v-for="(step, index) in steps" :key="step.id" class="flex flex-col items-center">
          <div
            class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all"
            :class="[
              step.id === currentStepId
                ? 'border-primary bg-primary text-primary-foreground shadow-lg ring-4 ring-primary/20'
                : 'border-muted-foreground/30 bg-card',
            ]"
          >
            <CheckCircle
              v-if="steps.findIndex((item) => item.id === currentStepId) > index"
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
</template>
