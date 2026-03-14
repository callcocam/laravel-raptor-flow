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
import DisplayFieldRenderer from './DisplayFieldRenderer.vue'
import { resolveActionUrl } from '../../composables/useFlowAction'
import { formatDisplayValue, resolveFieldValue } from '../../composables/display'
import NoteBlockRenderer from './NoteBlockRenderer.vue'
import type { DetailModalConfig, DetailModalLinkConfig, FlowActionSchema } from '../../types/detailModal'
import type { DisplayFieldConfig, DisplayRowConfig, DisplaySectionConfig } from '../../types/display'
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
  const execution = props.execution

  // Primary source of truth: backend policy-driven visibility map.
  if (execution?.action_visibility && action.id in execution.action_visibility) {
    return Boolean(execution.action_visibility[action.id])
  }

  // Compatibility fallback when only abilities map is available.
  if (execution?.abilities) {
    const abilityKey = action.id === 'notes' ? 'can_notes' : `can_${action.id}`
    if (abilityKey in execution.abilities) {
      return Boolean(execution.abilities[abilityKey])
    }
  }

  // No backend visibility information means action should not be shown.
  return false
}

const notesActions = computed(() =>
  (props.config?.actions ?? []).filter((a) => a.type === 'notes' && isActionVisible(a))
)

const buttonActions = computed(() =>
  (props.config?.actions ?? []).filter((a) => a.type !== 'notes' && isActionVisible(a))
)

// --- Field helpers ---
const noteBlocks = computed(() => props.config?.notes ?? [])

function getFieldValue(execution: FlowKanbanExecution, key: string): unknown {
  return resolveFieldValue(execution, { key, type: 'text' })
}

function formatFieldValue(value: unknown): string {
  return formatDisplayValue(value)
}

function getSectionRows(section: DisplaySectionConfig): DisplayRowConfig[] {
  if (section.rows?.length) {
    return section.rows
  }

  if (section.fields?.length) {
    return [{ fields: section.fields }]
  }

  return []
}

function sectionSpanClass(section: DisplaySectionConfig): string {
  const span = Math.min(12, Math.max(1, Number(section.columnSpan ?? 12)))
  const classes: Record<number, string> = {
    1: 'col-span-12 md:col-span-1',
    2: 'col-span-12 md:col-span-2',
    3: 'col-span-12 md:col-span-3',
    4: 'col-span-12 md:col-span-4',
    5: 'col-span-12 md:col-span-5',
    6: 'col-span-12 md:col-span-6',
    7: 'col-span-12 md:col-span-7',
    8: 'col-span-12 md:col-span-8',
    9: 'col-span-12 md:col-span-9',
    10: 'col-span-12 md:col-span-10',
    11: 'col-span-12 md:col-span-11',
    12: 'col-span-12 md:col-span-12',
  }
  return classes[span] ?? classes[12]
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

function handleNoteSave(note: { id: string; label: string; url: string; placeholder?: string }, text: string) {
  if (!props.execution) return

  emit(
    'action',
    props.execution,
    {
      id: note.id,
      type: 'notes',
      label: note.label,
      method: 'post',
      url: note.url,
      placeholder: note.placeholder,
    },
    resolveActionUrl(note.url, props.execution),
    text,
  )
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
        <div class="grid grid-cols-12 gap-4">
          <template v-for="section in config.sections" :key="section.id">
            <!-- Custom section slot -->
            <div v-if="$slots[`section-${section.id}`]" :class="sectionSpanClass(section)" class="space-y-2">
              <slot :name="`section-${section.id}`" :section="section" :execution="execution" />
            </div>

            <div v-else :class="sectionSpanClass(section)" class="space-y-3 rounded-lg border bg-card p-4">
              <h3 v-if="section.label" class="text-sm font-semibold text-foreground">
                {{ section.label }}
              </h3>

              <div class="space-y-4">
                <div v-for="(row, rowIndex) in getSectionRows(section)" :key="`${section.id}-${rowIndex}`" class="space-y-3">
                  <template v-for="field in row.fields" :key="field.key">
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
                      <label v-if="field.label && field.type !== 'link'" class="text-xs font-medium text-muted-foreground">
                        {{ field.label }}
                      </label>

                      <DisplayFieldRenderer
                        :field="field"
                        :execution="execution"
                        :steps="timelineSteps"
                        :current-user-id="currentUserId"
                        mode="modal"
                      />
                    </div>
                  </template>
                </div>
              </div>
            </div>
          </template>
        </div>
      </ScrollArea>

      <!-- Notes blocks (new API) -->
      <div v-if="execution && noteBlocks.length" class="space-y-4 border-t pt-4">
        <NoteBlockRenderer
          v-for="note in noteBlocks"
          :key="note.id"
          :note="note"
          :execution="execution"
          @save="handleNoteSave"
        />
      </div>

      <!-- Notes actions (type = 'notes') — renderizadas acima do footer -->
      <div v-else-if="execution && notesActions.length" class="space-y-4 border-t pt-4">
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
