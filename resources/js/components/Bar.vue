<script setup>
defineProps({ value: { type: Number, default: 0 }, tone: { type: String, default: 'bg-brand' } });
</script>
<template>
  <div class="h-2 overflow-hidden rounded-full bg-n-soft" role="progressbar" :aria-valuenow="Math.round(value * 100)" aria-valuemin="0" aria-valuemax="100">
    <div :class="['h-full rounded-full transition-all', tone]" :style="{ width: Math.round((value || 0) * 100) + '%' }" />
  </div>
</template>
