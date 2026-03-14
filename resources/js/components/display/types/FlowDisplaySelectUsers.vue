<script setup lang="ts">
import type { FlowKanbanExecution } from '../../../types/kanban'
import { computed } from 'vue'

interface Props {
  execution: FlowKanbanExecution
  currentUserId?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  currentUserId: null,
})

const users = computed(() => (props.execution.users ?? props.execution.config?.users ?? []) as Array<{ id: string; name?: string }>)
</script>

<template>
  <div class="flex flex-wrap gap-2">
    <template v-for="user in users" :key="user.id">
      <span
        class="inline-flex items-center rounded-full border px-2 py-1 text-xs font-medium"
        :class="
          user.id === props.currentUserId
            ? 'border-green-600 bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
            : 'border-border bg-muted/50 text-muted-foreground'
        "
      >
        {{ user.name ?? user.id }}
        <span v-if="user.id === props.currentUserId" class="ml-1">(você)</span>
      </span>
    </template>
    <span
      v-if="!users.length"
      class="text-sm text-muted-foreground"
    >
      —
    </span>
  </div>
</template>
