<script setup>
import { addWeeks, fmtDate, session } from '../api';
defineProps({ modelValue: String });
const emit = defineEmits(['update:modelValue']);
</script>
<template>
  <div class="inline-flex items-center overflow-hidden rounded-[13px] border border-brand-lgray bg-white text-[13px]">
    <button class="px-3 py-2 hover:bg-canvas" aria-label="Previous week" @click="emit('update:modelValue', addWeeks(modelValue, -1))">‹</button>
    <span class="border-x border-line px-3 py-2 font-semibold">
      Week of {{ fmtDate(modelValue, { day: 'numeric', month: 'short', year: 'numeric' }) }}
      <span v-if="modelValue === session.week" class="ml-1 text-muted font-normal">· this week</span>
    </span>
    <button class="px-3 py-2 hover:bg-canvas disabled:opacity-40" aria-label="Next week" :disabled="modelValue >= session.week"
            @click="emit('update:modelValue', addWeeks(modelValue, 1))">›</button>
  </div>
</template>
