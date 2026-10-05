<script setup>
import { onBeforeUnmount, watch } from 'vue';

const props = defineProps({ open: Boolean, title: String });
const emit = defineEmits(['close']);

const onKey = (e) => e.key === 'Escape' && emit('close');
watch(() => props.open, (o) => {
  document.body.classList.toggle('modal-open', o);
  document[o ? 'addEventListener' : 'removeEventListener']('keydown', onKey);
}, { immediate: true });
onBeforeUnmount(() => {
  document.body.classList.remove('modal-open');
  document.removeEventListener('keydown', onKey);
});
</script>
<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="open" class="modal-backdrop flex items-center justify-center p-5" @click.self="emit('close')">
        <div class="modal" role="dialog" aria-modal="true" :aria-label="title">
          <div class="modal-header">
            <div class="modal-title">{{ title }}</div>
            <button type="button" class="modal-close" aria-label="Close" @click="emit('close')"><i class="ti ti-x text-[17px]" /></button>
          </div>
          <div class="modal-body flex-1"><slot /></div>
          <div v-if="$slots.footer" class="modal-footer"><slot name="footer" /></div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
