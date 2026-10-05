<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { can, fmtDate, http, session } from '../api';
import Bar from '../components/Bar.vue';
import RagBadge from '../components/RagBadge.vue';

const route = useRoute();
const status = ref('Active');
const rag = ref(route.query.rag || '');
const programme = ref(route.query.programme || '');
const q = ref('');
const mine = ref(false);
const projects = ref([]);
const load = async () => (projects.value = (await http.get('/projects', { params: { status: status.value, mine: mine.value ? 1 : undefined } })).data);
watch([status, mine], load, { immediate: true });

const filtered = computed(() => {
  const term = q.value.toLowerCase();
  return projects.value.filter((p) => (!term || p.name.toLowerCase().includes(term) || (p.code || '').toLowerCase().includes(term))
    && (!rag.value || (rag.value === 'none' ? !p.rag : p.rag === rag.value))
    && (!programme.value || (p.programme || 'No programme') === programme.value));
});
const programmeNames = computed(() => [...new Set(projects.value.map((p) => p.programme || 'No programme'))].sort());
const groups = computed(() => {
  const g = {};
  for (const p of filtered.value) (g[p.programme || 'No programme'] ||= []).push(p);
  return g;
});
</script>
<template>
  <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <h1>Projects</h1>
    <RouterLink v-if="can.manage()" to="/projects/new" class="btn btn-primary">+ New project</RouterLink>
  </div>
  <div class="mb-4 flex flex-wrap items-center gap-2">
    <input v-model="q" type="search" class="input w-64" placeholder="Search name or code" aria-label="Search projects">
    <select v-model="status" class="input w-auto" aria-label="Status">
      <option v-for="s in session.lookups.projectStatus" :key="s">{{ s }}</option><option value="all">All statuses</option>
    </select>
    <select v-model="rag" class="input w-auto" aria-label="Health">
      <option value="">All health</option><option value="G">On track</option><option value="A">At risk</option><option value="R">Off track</option><option value="none">No update</option></select>
    <select v-model="programme" class="input w-auto" aria-label="Programme">
      <option value="">All programmes</option><option v-for="n in programmeNames" :key="n">{{ n }}</option></select>
    <label class="flex items-center gap-2 text-[13px]"><input v-model="mine" type="checkbox"> Only my projects</label>
  </div>

  <section v-for="(list, name) in groups" :key="name" class="card mb-4 overflow-x-auto">
    <div class="card-h"><h2>{{ name }}</h2><span class="text-[12.5px] text-muted">{{ list.length }} project{{ list.length > 1 ? 's' : '' }}</span></div>
    <table class="w-full min-w-[760px]">
      <thead><tr><th class="th">Project</th><th class="th">Phase</th><th class="th">Status</th><th class="th w-40">Progress</th><th class="th">Owner</th><th class="th">Last update</th><th class="th" /></tr></thead>
      <tbody>
        <tr v-for="p in list" :key="p.id" class="cursor-pointer hover:bg-canvas" @click="$router.push(`/projects/${p.id}`)">
          <td class="td"><div class="font-semibold">{{ p.name }}</div><div class="text-[12px] text-muted">{{ p.code }}<span v-if="p.open_issues"> · {{ p.open_issues }} open issue{{ p.open_issues > 1 ? 's' : '' }}</span></div></td>
          <td class="td">{{ p.phase }}</td>
          <td class="td"><RagBadge :rag="p.rag" /></td>
          <td class="td"><div v-if="p.progress != null" class="flex items-center gap-2 text-[12px]"><Bar class="flex-1" :value="p.progress / 100" />{{ p.progress }}%</div><span v-else class="text-muted">—</span></td>
          <td class="td">{{ p.owner || '—' }}</td>
          <td class="td" :class="p.last_update !== session.week && 'text-a font-semibold'">{{ p.last_update ? fmtDate(p.last_update) : 'Never' }}</td>
          <td class="td text-right"><RouterLink v-if="p.can_edit" :to="`/projects/${p.id}/update`" class="btn btn-sm" @click.stop>Update</RouterLink></td>
        </tr>
      </tbody>
    </table>
  </section>
  <p v-if="!filtered.length" class="card p-10 text-center text-muted">No projects match.</p>
</template>
