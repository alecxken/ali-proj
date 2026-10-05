<script setup>
// Horizontal stepper: steps = [{ label, hint }]. Completed steps are clickable to go back.
const props = defineProps({ steps: { type: Array, required: true }, modelValue: { type: Number, default: 0 } });
const emit = defineEmits(['update:modelValue']);
const go = (i) => i < props.modelValue && emit('update:modelValue', i);
</script>
<template>
  <nav aria-label="Progress" class="mb-6">
    <ol class="flex items-start">
      <template v-for="(s, i) in steps" :key="s.label">
        <li class="flex w-[84px] flex-shrink-0 flex-col items-center gap-1.5 text-center sm:w-[110px]" :aria-current="i === modelValue ? 'step' : undefined">
          <button type="button" :class="['sp-step', i < modelValue && 'done', i === modelValue && 'active']" :aria-label="`Step ${i + 1}: ${s.label}`" @click="go(i)">
            <i v-if="i < modelValue" class="ti ti-check text-[16px]" /><span v-else>{{ i + 1 }}</span>
          </button>
          <span :class="['sp-lbl !whitespace-normal leading-tight', i === modelValue && 'active']">{{ s.label }}</span>
        </li>
        <div v-if="i < steps.length - 1" :class="['sp-line', i < modelValue && 'done']" />
      </template>
    </ol>
    <p v-if="steps[modelValue].hint" class="t-sub mt-4 text-center">Step {{ modelValue + 1 }} of {{ steps.length }} — {{ steps[modelValue].hint }}</p>
  </nav>
</template>
