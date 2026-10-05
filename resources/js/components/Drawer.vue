<script setup>
defineProps({ open: Boolean, title: String });
const emit = defineEmits(['close']);
</script>
<template>
  <Teleport to="body">
    <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" enter-active-class="transition" leave-active-class="transition">
      <div v-if="open" class="fixed inset-0 z-40 flex justify-end bg-ink/30" @click.self="emit('close')" @keydown.esc="emit('close')">
        <aside class="flex h-full w-full max-w-xl flex-col bg-white shadow-2xl" role="dialog" aria-modal="true" :aria-label="title">
          <header class="flex items-center justify-between border-b border-line px-6 py-4">
            <h2 class="text-[17px]">{{ title }}</h2>
            <button class="btn btn-sm" @click="emit('close')">Close</button>
          </header>
          <div class="flex-1 overflow-y-auto px-6 py-5"><slot /></div>
          <footer v-if="$slots.footer" class="flex justify-end gap-2 border-t border-line px-6 py-3"><slot name="footer" /></footer>
        </aside>
      </div>
    </Transition>
  </Teleport>
</template>
