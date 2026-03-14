<script setup lang="ts">
import { formatDisplayValue } from '../../../composables/display'
import type { DisplayFieldConfig } from '../../../types/display'
import { computed } from 'vue'

interface Props {
  field: DisplayFieldConfig
  value?: unknown
  mode?: 'modal' | 'card'
}

const props = withDefaults(defineProps<Props>(), {
  value: undefined,
  mode: 'modal',
})

const textClass = computed(() =>
  props.mode === 'card' ? 'text-xs text-muted-foreground' : 'text-sm text-foreground',
)

const content = computed(() => formatDisplayValue(props.value, 'date'))
</script>

<template>
  <p :class="textClass">
    {{ content }}
  </p>
</template>
