<script setup lang="ts">
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { ScrollArea } from '@/components/ui/scroll-area'
import FlowActionRenderer from '../actions/FlowActionRenderer.vue'
import { resolveActionUrl } from '../../composables/useFlowAction'
import type { DetailModalConfig, DetailModalLinkConfig, FlowActionSchema } from '../../types/detailModal'
import type { FlowKanbanExecution, FlowKanbanStep } from '../../types/kanban'
import { AlertCircle, CheckCircle, Clock, ExternalLink, Pause, Play, XCircle } from 'lucide-vue-next'
import { computed } from 'vue'

interface Props {
  execution: FlowKanbanExecution | null
  config: DetailModalConfig | null
  steps?: FlowKanbanStep[]
  currentUserId?: string | null
  userRoles?: string[]
}

const props = withDefaults(defineProps<Props>(), {
  steps: () => [],
})

const emit = defineEmits<{
  close: []
  action: [execution: FlowKanbanExecution, action: FlowActionSchema, resolvedUrl: string, notes?: string]
}>()

const isOpen = computed(() => props.execution !== null && props.config !== null)

const workable = computed(() => props.execution?.workable ?? null)
const titleText = computed(() => workable.value?.name ?? '—')

const firstLink = computed((): DetailModalLinkConfig | null => props.config?.links?.[0] ?? null)

const titleUrl = computed((): string | null => {
  if (!props.execution || !firstLink.value) return null
  const { url } = firstLink.value
  return typeof url === 'function' ? url(props.execution) : url
})

const statusBadgeConfig = computed(() => {
  const status = props.execution?.status ?? 'pending'
  const configs: Record<string, { label: string; icon: typeof AlertCircle; class: string }> = {
    pending: { label: 'Pendente', icon: AlertCircle, class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
    in_progress: { label: 'Em Andamento', icon: Play, class: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
    completed: { label: 'Concluída', icon: CheckCircle, class: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
    blocked: { label: 'Bloqueada', icon: XCircle, class: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
    paused: { label: 'Pausada', icon: Pause, class: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' },
    skipped: { label: 'Pulada', icon: XCircle, class: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' },
  }
  return configs[status] ?? { label: status, icon: AlertCircle, class: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' }
})

function resolveLink(url: string | ((e: unknown) => string), execution: FlowKanbanExecution): string {
  return typeof url === 'function' ? url(execution) : url
}

function isActionVisible(action: FlowActionSchema): boolean {
  if (!action.visibleStatuses?.length) return true
  return action.visibleStatuses.includes(props.execution?.status ?? '')
}

const notesActions = computed(() =>
  (props.config?.actions ?? []).filter((a) => a.type === 'notes' && isActionVisible(a))
)

const buttonActions = computed(() =>
  (props.config?.actions ?? []).filter((a) => a.type !== 'notes' && isActionVisible(a))
)

// --- Field helpers ---
function getFieldValue(execution: FlowKanbanExecution, key: string): unknown {
  const ex = execution as unknown as Record<string, unknown>
  if (ex[key] !== undefined) return ex[key]
  const w = execution.workable
  if (w && typeof w === 'object' && key in w) return (w as Record<string, unknown>)[key]
  return undefined
}

function formatFieldValue(value: unknown): string {
  if (value == null) return '—'
  if (typeof value === 'object' && value !== null && 'name' in value && typeof (value as { name: unknown }).name === 'string') {
    return (value as { name: string }).name
  }
  return String(value)
}

const timelineSteps = computed(() => {
  return props.steps ?? []
})

const currentStepId = computed(() => props.execution?.workflow_step_template_id ?? '')

// --- Handlers ---
function handleClose() {
  emit('close')
}

function handleActionExecute(action: FlowActionSchema, notes?: string) {
  if (!props.execution) return
  const resolvedUrl = resolveActionUrl(action.url ?? '', props.execution)
  emit('action', props.execution, action, resolvedUrl, notes)
}
</script>

<template>
  <Dialog :open="isOpen" @update:open="handleClose">
    <DialogContent class="max-h-[90vh] overflow-hidden sm:max-w-4xl">
      <!-- Header -->
      <DialogHeader class="space-y-0 pb-2">
        <div class="flex items-start gap-4 pr-8">
          <div class="min-w-0 flex-1">
            <DialogTitle class="text-2xl font-bold">
              <a
                v-if="execution && firstLink && titleUrl"
                :href="titleUrl"
                :target="firstLink.external ? '_blank' : undefined"
                :rel="firstLink.external ? 'noopener noreferrer' : undefined"
                class="inline-flex items-center gap-2 transition-colors hover:text-primary"
              >
                {{ titleText }}
                <ExternalLink v-if="firstLink.external" class="h-4 w-4 shrink-0" />
              </a>
              <span v-else>{{ titleText }}</span>
            </DialogTitle>

            <DialogDescription
              v-if="config?.links && config.links.length > 1"
              class="mt-1 flex flex-wrap items-center gap-2"
            >
              <template v-for="(link, idx) in config.links.slice(1)" :key="link.key">
                <a
                  v-if="execution"
                  :href="resolveLink(link.url, execution)"
                  :target="link.external ? '_blank' : undefined"
                  :rel="link.external ? 'noopener noreferrer' : undefined"
                  class="inline-flex items-center gap-1 text-sm font-medium text-muted-foreground transition-colors hover:text-primary"
                >
                  {{ link.label }}
                  <ExternalLink v-if="link.external" class="h-3 w-3" />
                </a>
                <span v-if="idx < config.links!.length - 2" class="text-muted-foreground">·</span>
              </template>
            </DialogDescription>
          </div>
        </div>

        <!-- Status badges -->
        <div v-if="execution" class="flex flex-wrap items-center gap-2 pt-3">
          <span
            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium"
            :class="statusBadgeConfig.class"
          >
            <component :is="statusBadgeConfig.icon" class="h-3.5 w-3.5" />
            {{ statusBadgeConfig.label }}
          </span>
          <span
            v-if="execution.is_overdue"
            class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200"
          >
            <Clock class="h-3 w-3" />
            SLA Atrasado
          </span>
        </div>
      </DialogHeader>

      <!-- Sections + fields -->
      <ScrollArea v-if="execution && config" class="max-h-[calc(90vh-220px)] pr-4">
        <div class="space-y-6">
          <template v-for="section in config.sections" :key="section.id">
            <!-- Custom section slot -->
            <div v-if="$slots[`section-${section.id}`]" class="space-y-2">
              <slot :name="`section-${section.id}`" :section="section" :execution="execution" />
            </div>

            <div v-else class="space-y-3">
              <h3 v-if="section.label" class="text-sm font-semibold text-foreground">
                {{ section.label }}
              </h3>

              <div class="space-y-3">
                <template v-for="field in section.fields" :key="field.key">
                  <!-- Custom field slot -->
                  <div v-if="$slots[`field-${field.key}`]" class="space-y-1">
                    <slot
                      :name="`field-${field.key}`"
                      :field="field"
                      :value="getFieldValue(execution, field.key)"
                      :execution="execution"
                    />
                  </div>

                  <div v-else class="space-y-1">
                    <label v-if="field.label" class="text-xs font-medium text-muted-foreground">
                      {{ field.label }}
                    </label>

                    <p v-if="field.type === 'text'" class="text-sm text-foreground">
                      {{ formatFieldValue(getFieldValue(execution, field.key)) }}
                    </p>

                    <p v-else-if="field.type === 'textarea'" class="whitespace-pre-wrap text-sm text-foreground">
                      {{ getFieldValue(execution, field.key) ?? '—' }}
                    </p>

                    <p v-else-if="field.type === 'datetime'" class="text-sm text-foreground">
                      {{
                        getFieldValue(execution, field.key)
                          ? new Date(getFieldValue(execution, field.key) as string).toLocaleString('pt-BR')
                          : '—'
                      }}
                    </p>

                    <span
                      v-else-if="field.type === 'badge'"
                      class="inline-flex rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground"
                    >
                      {{ getFieldValue(execution, field.key) ?? '—' }}
                    </span>

                    <!-- Timeline (workflow steps) -->
                    <div v-else-if="field.type === 'timeline'" class="relative py-2">
                      <div v-if="timelineSteps.length" class="relative">
                        <div class="absolute left-0 right-0 top-5 h-0.5 bg-border" />
                        <div class="relative flex justify-between">
                          <div
                            v-for="(step, index) in timelineSteps"
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
                                v-if="timelineSteps.findIndex((s: { id: string }) => s.id === currentStepId) > (index as number)"
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

                    <!-- Select users (participants) -->
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
                      {{ String(getFieldValue(execution, field.key) ?? '—') }}
                    </p>
                  </div>
                </template>
              </div>
            </div>
          </template>
        </div>
      </ScrollArea>

      <!-- Notes actions (type = 'notes') — renderizadas acima do footer -->
      <div v-if="execution && notesActions.length" class="space-y-4 border-t pt-4">
        <FlowActionRenderer
          v-for="action in notesActions"
          :key="action.id"
          :action="action"
          :execution="execution"
          @execute="(notes) => handleActionExecute(action, notes)"
        />
      </div>

      <!-- Footer: ações de botão + fechar -->
      <div class="flex flex-wrap items-center gap-2 border-t pt-4">
        <FlowActionRenderer
          v-for="action in buttonActions"
          :key="action.id"
          :action="action"
          :execution="execution ?? ({} as FlowKanbanExecution)"
          @execute="() => handleActionExecute(action)"
        />

        <div class="flex-1" />

        <button
          type="button"
          class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium transition-colors hover:bg-accent"
          @click="handleClose"
        >
          Fechar
        </button>
      </div>
    </DialogContent>
  </Dialog>
</template>
