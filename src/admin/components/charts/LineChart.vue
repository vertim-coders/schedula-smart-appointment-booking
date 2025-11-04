<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import {
  Chart,
  LineElement,
  PointElement,
  LineController,
  LinearScale,
  CategoryScale,
  Tooltip,
  Legend,
} from 'chart.js';

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
  options: {
    type: Object,
    default: () => ({ // Default options if none are provided
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          display: true,
          beginAtZero: true,
        },
        x: {
          display: true,
        },
      },
      plugins: {
        legend: {
          display: true,
          position: 'top',
        },
        tooltip: {
            mode: 'index',
            intersect: false,
        }
      },
    }),
  },
});

const root = ref(null);
let chart;

Chart.register(LineElement, PointElement, LineController, LinearScale, CategoryScale, Tooltip, Legend);

onMounted(() => {
  chart = new Chart(root.value, {
    type: 'line',
    data: props.data,
    options: props.options, // Use the options from props
  });
});

const chartData = computed(() => props.data);
const chartOptions = computed(() => props.options);

watch(chartData, (data) => {
  if (chart) {
    chart.data = data;
    chart.update();
  }
}, { deep: true });

watch(chartOptions, (options) => {
  if (chart) {
    chart.options = options;
    chart.update();
  }
}, { deep: true });

</script>

<template>
  <canvas ref="root" />
</template>
