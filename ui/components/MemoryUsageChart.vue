<template>
    <figure>
        <canvas id="memory-usage-chart" width="480" height="320"></canvas>
    </figure>
</template>

<script>
import Chart from 'chart.js';
import gql from 'graphql-tag';

const MEGABYTE = 1000000;
const THREE_SECONDS = 3000;

export const fragment = gql`
    fragment MemoryUsageChart on Server {
        memory {
            current
            peak
        }
    }
`;

export default {
    data() {
        return {
            chart: null,
        };
    },
    props: {
        memory: {
            type: Object,
            required: true,
        },
    },
    mounted() {
        let context = document.getElementById('memory-usage-chart').getContext('2d');

        this.chart = new Chart(context, {
            type: 'bar',
            data: {
                datasets: [
                    {
                        label: 'Current',
                        backgroundColor: 'hsl(141, 71%, 48%)',
                    },
                    {
                        label: 'Peak',
                        backgroundColor: 'hsl(347, 100%, 69%)',
                    },
                ],
                labels: ['Usage'],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: true,
                    text: 'Memory',
                },
                tooltips: {
                    enabled: false,
                },
                hover: {
                    mode: 'point',
                    intersect: true,
                },
                 scales: {
                    yAxes: [
                        {
                            scaleLabel: {
                                display: true,
                                labelString: 'Megabytes',
                            },
                            ticks: {
                                beginAtZero: true,
                                precision: 0,
                            },
                        },
                    ],
                },
            },
        });

        this.update();

        setInterval(this.update, THREE_SECONDS);
    },
    methods: { 
        update() {
            this.chart.data.datasets[0].data[0] = this.memory.current / MEGABYTE;
            this.chart.data.datasets[1].data[0] = this.memory.peak / MEGABYTE;

            this.chart.update();
        },
    },
}
</script>