<script setup lang="ts">
import { badgeClass, formatDisplayValue } from '../../../composables/display'
import type { DisplayFieldConfig } from '../../../types/display'
import { computed } from 'vue'

interface Props {
  field: DisplayFieldConfig
  value?: unknown
}

const props = withDefaults(defineProps<Props>(), {
  value: undefined,
})

const content = computed(() => formatDisplayValue(props.value, props.field.format ?? props.field.type))
const classes = computed(() => badgeClass(props.value, props.field.variant))
</script>

<template>
  <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="classes">
    {{ content }}
  </span>
</template>
