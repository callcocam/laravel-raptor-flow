<script setup lang="ts">
import { formatDisplayValue } from '../../../composables/display'
import type { DisplayFieldConfig } from '../../../types/display'
import { computed } from 'vue'

interface Props {
  field: DisplayFieldConfig
  value?: unknown
}

const props = withDefaults(defineProps<Props>(), {
  value: undefined,
})

const content = computed(() => formatDisplayValue(props.value, props.field.format ?? 'text'))
</script>

<template>
  <p class="text-sm text-muted-foreground">
    {{ content }}
  </p>
</template>
