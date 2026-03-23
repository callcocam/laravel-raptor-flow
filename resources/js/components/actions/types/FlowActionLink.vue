<!--
  FlowActionLink - Link/âncora de ação
  type: 'link' — renderiza um <a> que navega para a URL resolvida.
  Não emite 'execute'; a navegação é direta.
-->
<script setup lang="ts">
import { computed } from 'vue';
import {
    resolveActionUrl,
    useActionIcon,
} from '../../../composables/useFlowAction';
import type { FlowActionSchema } from '../../../types/detailModal';
import type { FlowKanbanExecution } from '../../../types/kanban';

const props = defineProps<{
    action: FlowActionSchema;
    execution: FlowKanbanExecution;
}>();

const iconComponent = useActionIcon(props.action);

const href = computed(() =>
    resolveActionUrl(props.action.url ?? '#', props.execution),
);
const isExternal = computed(() => {
    if (props.target === '_blank') {
        return true;
    }
    const externalFromData = props.action.data?.external;

    if (typeof externalFromData === 'boolean') {
        return externalFromData;
    }

    return props.action.method === 'get' && href.value.startsWith('http');
});

const linkClasses = computed(() => {
    const variant = props.action.variant ?? 'outline';

    if (variant === 'ghost') {
        return 'inline-flex items-center gap-1 text-xs font-medium text-muted-foreground transition-colors hover:text-primary';
    }

    if (variant === 'default') {
        return 'inline-flex h-9 items-center justify-center gap-1.5 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90';
    }

    if (variant === 'destructive') {
        return 'inline-flex h-9 items-center justify-center gap-1.5 rounded-md bg-destructive px-4 text-sm font-medium text-destructive-foreground transition-colors hover:bg-destructive/90';
    }

    if (variant === 'secondary') {
        return 'inline-flex h-9 items-center justify-center gap-1.5 rounded-md bg-secondary px-4 text-sm font-medium text-secondary-foreground transition-colors hover:bg-secondary/80';
    }

    return 'inline-flex h-9 items-center justify-center gap-1.5 rounded-md border border-input bg-background px-4 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground';
});
</script>

<template>
    <a
        :href="href"
        :target="isExternal ? '_blank' : undefined"
        :rel="isExternal ? 'noopener noreferrer' : undefined"
        :class="linkClasses"
    >
        <component
            :is="iconComponent"
            v-if="iconComponent"
            class="h-4 w-4 shrink-0"
        />
        {{ action.label }}
    </a>
</template>
