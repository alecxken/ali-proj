<script setup>
import Highcharts from 'highcharts';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Pulse chart theme: ETES shape language (soft, rounded, light) with NCBA colours.
const INK = '#38302E';
const MUTED = '#6B7C84';
Highcharts.setOptions({
  colors: ['#112337', '#FFBD00', '#1B4F73', '#38302E', '#C9D3DC', '#979797'],
  chart: { backgroundColor: 'transparent', style: { fontFamily: "'Montserrat', sans-serif" }, spacing: [8, 4, 4, 4], animation: { duration: 500 } },
  title: { text: undefined },
  credits: { enabled: false },
  legend: { itemStyle: { color: MUTED, fontSize: '11px', fontWeight: '600' }, itemHoverStyle: { color: INK }, symbolRadius: 6 },
  xAxis: { lineColor: '#EDEDED', tickColor: 'transparent', labels: { style: { color: MUTED, fontSize: '11px', fontWeight: '600' } } },
  yAxis: { gridLineColor: '#EDEDED', title: { text: undefined }, labels: { style: { color: '#979797', fontSize: '10.5px' } } },
  tooltip: {
    backgroundColor: '#fff', borderColor: 'rgba(17,35,55,.08)', borderRadius: 12, shadow: { color: 'rgba(17,35,55,.18)', offsetY: 6, width: 18 },
    style: { color: INK, fontSize: '12px', fontWeight: '600' },
  },
  plotOptions: { series: { borderWidth: 0, animation: { duration: 500 } } },
});

const props = defineProps({ options: { type: Object, required: true }, height: { type: [Number, String], default: 240 } });
const el = ref(null);
let chart;
const build = () => {
  chart?.destroy();
  chart = Highcharts.chart(el.value, { ...props.options, chart: { height: props.height, ...props.options.chart } });
};
onMounted(build);
watch(() => props.options, build, { deep: true });
onBeforeUnmount(() => chart?.destroy());
</script>
<template><div ref="el" class="w-full" /></template>
