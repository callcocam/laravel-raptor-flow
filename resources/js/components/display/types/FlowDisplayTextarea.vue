<script setup lang="ts">
interface Props {
  value?: unknown
}

const props = withDefaults(defineProps<Props>(), {
  value: undefined,
})
</script>

<template>
  <p class="whitespace-pre-wrap text-sm text-foreground">
    {{ props.value ?? '—' }}
  </p>
</template>
