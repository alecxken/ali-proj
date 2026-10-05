<script setup>
import Bar from './Bar.vue';
import { num, ratio, tone } from '../api';
defineProps({ metrics: { type: Array, default: () => [] }, previous: { type: Array, default: () => [] } });
const prevOf = (prev, label) => prev.find((p) => p.label === label);
</script>
<template>
  <div v-if="!metrics?.length" class="text-muted text-[13px]">No metrics reported.</div>
  <div v-for="m in metrics" :key="m.label" class="py-1.5">
    <div class="flex items-baseline justify-between gap-3">
      <span class="text-[13px]">{{ m.label }}</span>
      <span class="tabular-nums font-bold">
        {{ num(m.value) }}{{ m.unit }}
        <span v-if="prevOf(previous, m.label)" class="ml-1 text-[11px] font-semibold"
              :class="m.value >= prevOf(previous, m.label).value ? 'text-g' : 'text-r'">
          {{ m.value >= prevOf(previous, m.label).value ? '+' : '' }}{{ num(m.value - prevOf(previous, m.label).value) }}
        </span>
      </span>
    </div>
    <Bar v-if="ratio(m) !== null" class="mt-1" :value="ratio(m)" :tone="tone(ratio(m))" />
  </div>
</template>
