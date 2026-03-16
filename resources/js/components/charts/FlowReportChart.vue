<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '~/components/ui/card';
import { Bar, Doughnut } from 'vue-chartjs';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Title,
    Tooltip,
} from 'chart.js';
import { computed } from 'vue';

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title);

interface ChartDataset {
    label: string;
    data: number[];
}

interface ChartData {
    labels: string[];
    datasets: ChartDataset[];
}

interface Props {
    title: string;
    description?: string;
    chartType: 'bar' | 'doughnut' | 'horizontal-bar';
    chartData: ChartData;
    chartOptions?: Record<string, unknown>;
    loading?: boolean;
    emptyMessage?: string;
    heightClass?: string;
}

const props = withDefaults(defineProps<Props>(), {
    description: undefined,
    chartOptions: undefined,
    loading: false,
    emptyMessage: 'Sem dados disponiveis',
    heightClass: 'h-[240px]',
});

const hasData = computed(() => {
    if (!props.chartData.labels.length) {
        return false;
    }

    return props.chartData.datasets.some((dataset) =>
        dataset.data.some((value) => Number.isFinite(value) && value > 0),
    );
});

const resolvedOptions = computed(() => {
    if (props.chartOptions) {
        return props.chartOptions;
    }

    if (props.chartType === 'doughnut') {
        return {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 14,
                        usePointStyle: true,
                    },
                },
            },
        };
    }

    if (props.chartType === 'horizontal-bar') {
        return {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.08)' },
                },
                y: {
                    grid: { display: false },
                },
            },
        };
    }

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.08)' },
            },
            x: {
                grid: { display: false },
            },
        },
    };
});

const resolvedData = computed(() => {
    const palette = ['#22c55e', '#14b8a6', '#3b82f6', '#eab308', '#f97316', '#64748b', '#ef4444'];

    if (props.chartType === 'doughnut') {
        return {
            labels: props.chartData.labels,
            datasets: props.chartData.datasets.map((dataset) => ({
                ...dataset,
                backgroundColor: palette.slice(0, Math.max(dataset.data.length, 1)),
                borderWidth: 0,
                hoverOffset: 6,
            })),
        };
    }

    return {
        labels: props.chartData.labels,
        datasets: props.chartData.datasets.map((dataset, index) => ({
            ...dataset,
            backgroundColor: palette[index % palette.length],
            borderRadius: 6,
            borderSkipped: false,
        })),
    };
});
</script>

<template>
    <Card class="flex flex-col">
        <CardHeader>
            <CardTitle class="text-base">{{ title }}</CardTitle>
            <CardDescription v-if="description">{{ description }}</CardDescription>
        </CardHeader>
        <CardContent class="flex-1 pt-0">
            <div :class="['w-full', heightClass]">
                <div
                    v-if="loading"
                    class="flex h-full items-center justify-center rounded-lg bg-muted/30 text-sm text-muted-foreground"
                >
                    Carregando dados...
                </div>

                <div
                    v-else-if="!hasData"
                    class="flex h-full items-center justify-center rounded-lg bg-muted/30 text-sm text-muted-foreground"
                >
                    {{ emptyMessage }}
                </div>

                <Doughnut
                    v-else-if="chartType === 'doughnut'"
                    :data="resolvedData"
                    :options="resolvedOptions"
                    class="!max-h-[240px]"
                />

                <Bar
                    v-else
                    :data="resolvedData"
                    :options="resolvedOptions"
                    class="!max-h-[240px]"
                />
            </div>
        </CardContent>
    </Card>
</template>
