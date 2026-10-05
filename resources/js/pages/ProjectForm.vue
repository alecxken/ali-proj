<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { can, errorText, http, notify, session } from '../api';

const props = defineProps({ id: String });
const router = useRouter();
const users = ref([]);
const programmes = ref([]);
const busy = ref(false);
const error = ref('');
const f = ref({ name: '', code: '', programme: '', description: '', phase: 'Discovery', status: 'Active', owner_id: null,
  start_date: '', target_date: '', sort_order: 0, member_ids: [] });

onMounted(async () => {
  const [u, p] = await Promise.all([http.get('/users'), http.get('/projects', { params: { status: 'all' } })]);
  users.value = u.data.filter((x) => x.active);
  programmes.value = [...new Set(p.data.map((x) => x.programme).filter(Boolean))].sort();
  if (props.id) {
    const { data } = await http.get(`/projects/${props.id}`);
    Object.keys(f.value).forEach((k) => data[k] !== undefined && (f.value[k] = data[k] ?? ''));
    f.value.member_ids = data.members.map((m) => m.id);
  }
});

async function save() {
  busy.value = true; error.value = '';
  try {
    const { data } = props.id ? await http.put(`/projects/${props.id}`, f.value) : await http.post('/projects', f.value);
    notify('Project saved.');
    router.push(`/projects/${data.id}`);
  } catch (e) { error.value = errorText(e); } finally { busy.value = false; }
}
async function remove() {
  if (!confirm('Delete this project and all its updates, milestones and issues? This cannot be undone.')) return;
  await http.delete(`/projects/${props.id}`);
  notify('Project deleted.');
  router.push('/projects');
}
</script>
<template>
  <p class="mb-1 text-[12.5px] text-muted"><RouterLink to="/projects" class="hover:underline">Projects</RouterLink> /</p>
  <h1 class="mb-5">{{ id ? 'Edit project' : 'New project' }}</h1>
  <form class="card max-w-3xl p-6" @submit.prevent="save">
    <p v-if="error" class="mb-4 rounded-[13px] bg-r-soft px-3 py-2 text-[13px] text-[#8B241D]" role="alert">{{ error }}</p>
    <div class="grid gap-4 sm:grid-cols-[1fr_160px]">
      <div><label class="label">Project name</label><input v-model="f.name" class="input" required maxlength="200"></div>
      <div><label class="label">Code <span class="hint">optional</span></label><input v-model="f.code" class="input uppercase" maxlength="30"></div>
    </div>
    <div class="mt-4 grid gap-4 sm:grid-cols-3">
      <div><label class="label">Programme</label><input v-model="f.programme" class="input" list="programmes" placeholder="e.g. Digital Lending">
        <datalist id="programmes"><option v-for="p in programmes" :key="p" :value="p" /></datalist></div>
      <div><label class="label">Phase</label><select v-model="f.phase" class="input"><option v-for="p in session.lookups.phases" :key="p">{{ p }}</option></select></div>
      <div><label class="label">Status</label><select v-model="f.status" class="input"><option v-for="s in session.lookups.projectStatus" :key="s">{{ s }}</option></select></div>
    </div>
    <div class="mt-4"><label class="label">Description</label><textarea v-model="f.description" class="input min-h-20" /></div>
    <div class="mt-4 grid gap-4 sm:grid-cols-3">
      <div><label class="label">Owner</label>
        <select v-model="f.owner_id" class="input" :disabled="!can.manage()"><option :value="null">—</option><option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option></select></div>
      <div><label class="label">Start</label><input v-model="f.start_date" type="date" class="input"></div>
      <div><label class="label">Target go-live</label><input v-model="f.target_date" type="date" class="input"></div>
    </div>
    <div class="mt-4"><label class="label">Team <span class="hint">members can post updates, milestones and issues</span></label>
      <div class="grid max-h-56 gap-1 overflow-y-auto rounded-[13px] border border-line p-2 sm:grid-cols-2">
        <label v-for="u in users" :key="u.id" class="flex items-center gap-2 rounded px-2 py-1 text-[13px] hover:bg-canvas">
          <input v-model="f.member_ids" type="checkbox" :value="u.id"> {{ u.name }} <span class="text-muted">· {{ u.title || u.role }}</span>
        </label>
      </div>
    </div>
    <div class="mt-6 flex items-center gap-2">
      <button class="btn btn-primary" :disabled="busy">{{ busy ? 'Saving…' : 'Save project' }}</button>
      <RouterLink :to="id ? `/projects/${id}` : '/projects'" class="btn">Cancel</RouterLink>
      <button v-if="id && can.manage()" type="button" class="btn btn-danger ml-auto" @click="remove">Delete</button>
    </div>
  </form>
</template>
