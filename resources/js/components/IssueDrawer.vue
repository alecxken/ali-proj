<script setup>
import { ref, watch } from 'vue';
import { can, errorText, http, notify, session } from '../api';
import Drawer from './Drawer.vue';

const props = defineProps({ open: Boolean, issue: Object, projects: { type: Array, default: () => [] } });
const emit = defineEmits(['close', 'saved']);
const blank = () => ({ project_id: null, kind: 'Issue', title: '', description: '', severity: 'Medium', status: 'Open', owner_name: '', due_date: '', latest_note: '' });
const f = ref(blank());
const error = ref('');
const busy = ref(false);
watch(() => props.open, (o) => {
  if (!o) return;
  error.value = '';
  f.value = { ...blank(), ...(props.issue || {}) };
  f.value.due_date ||= '';
});

async function save() {
  busy.value = true; error.value = '';
  try {
    const { data } = f.value.id ? await http.put(`/issues/${f.value.id}`, f.value) : await http.post('/issues', f.value);
    notify(f.value.id ? 'Saved.' : `${f.value.kind} logged.`);
    emit('saved', data); emit('close');
  } catch (e) { error.value = errorText(e); } finally { busy.value = false; }
}
async function remove() {
  if (!confirm('Delete this item permanently? Closing it keeps the history.')) return;
  await http.delete(`/issues/${f.value.id}`);
  emit('saved'); emit('close');
}
</script>
<template>
  <Drawer :open="open" :title="f.id ? 'Edit ' + f.kind.toLowerCase() : 'Log an issue or risk'" @close="emit('close')">
    <form id="issue-form" @submit.prevent="save">
      <p v-if="error" class="mb-4 rounded-[13px] bg-r-soft px-3 py-2 text-[13px] text-[#8B241D]" role="alert">{{ error }}</p>
      <div class="mb-4"><label class="label">Type</label>
        <div class="flex flex-wrap gap-1.5">
          <button v-for="k in session.lookups.issueKinds" :key="k" type="button" @click="f.kind = k"
                  :class="['chip', f.kind === k && 'sel']">{{ k }}</button>
        </div></div>
      <div class="mb-4"><label class="label">Title</label><input v-model="f.title" class="input" required maxlength="240" placeholder="What's happening, in one line"></div>
      <div class="mb-4"><label class="label">Project</label>
        <select v-model="f.project_id" class="input"><option v-if="can.manage()" :value="null">— Programme-level —</option>
          <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option></select></div>
      <div class="mb-4 grid grid-cols-2 gap-3">
        <div><label class="label">Severity</label><select v-model="f.severity" class="input"><option v-for="s in session.lookups.severity" :key="s">{{ s }}</option></select></div>
        <div><label class="label">Status</label><select v-model="f.status" class="input"><option v-for="s in session.lookups.issueStatus" :key="s">{{ s }}</option></select></div>
        <div><label class="label">Owner / resolver</label><input v-model="f.owner_name" class="input" placeholder="Person, team or vendor"></div>
        <div><label class="label">Target date</label><input v-model="f.due_date" type="date" class="input"></div>
      </div>
      <div class="mb-4"><label class="label">Details</label><textarea v-model="f.description" class="input min-h-20" /></div>
      <div><label class="label">Latest progress <span class="hint">shown in the weekly report</span></label><textarea v-model="f.latest_note" class="input min-h-20" /></div>
    </form>
    <template #footer>
      <button v-if="f.id && can.manage()" class="btn btn-danger mr-auto" @click="remove">Delete</button>
      <button class="btn" @click="emit('close')">Cancel</button>
      <button class="btn btn-primary" form="issue-form" :disabled="busy">{{ busy ? 'Saving…' : 'Save' }}</button>
    </template>
  </Drawer>
</template>
