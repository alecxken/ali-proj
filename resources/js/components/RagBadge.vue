<script setup>
const props = defineProps({ rag: String, short: Boolean });
const labels = { G: 'On track', A: 'At risk', R: 'Off track' };
const cls = { G: 'text-g bg-g-soft', A: 'text-a bg-a-soft', R: 'text-r bg-r-soft' };
</script>
<template>
  <span :class="['inline-flex items-center gap-1.5 rounded-full py-0.5 pl-2 pr-2.5 text-[12px] font-semibold whitespace-nowrap', cls[rag] || 'text-n bg-n-soft']">
    <i class="size-2 rounded-full bg-current" aria-hidden="true" />{{ short ? '' : (labels[rag] || 'No update') }}
  </span>
</template>
