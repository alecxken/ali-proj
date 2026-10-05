<script setup>
import { onMounted, ref, watch } from 'vue';
import { can, fmtDate, http, session } from '../api';
import IssueDrawer from '../components/IssueDrawer.vue';

const issues = ref([]);
const projects = ref([]);
const filters = ref({ status: 'open', kind: '', severity: '', project_id: '' });
const open = ref(false);
const editing = ref(null);

const load = async () => (issues.value = (await http.get('/issues', { params: Object.fromEntries(Object.entries(filters.value).filter(([, v]) => v)) })).data);
watch(filters, load, { deep: true, immediate: true });
onMounted(async () => (projects.value = (await http.get('/projects', { params: { status: 'all' } })).data));

const editable = () => projects.value.filter((p) => p.can_edit);
const edit = (i) => { editing.value = i ? { ...i } : null; open.value = true; };
const sevCls = { Critical: '!bg-r !text-white', High: '!bg-r-soft !text-r', Medium: '!bg-a-soft !text-a' };
</script>
<template>
  <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <h1>Issues, risks &amp; production incidents</h1>
    <button v-if="can.write()" class="btn btn-primary" @click="edit(null)">+ Log issue or risk</button>
  </div>
  <div class="mb-4 flex flex-wrap gap-2">
    <select v-model="filters.status" class="input w-auto" aria-label="Status"><option value="open">All open</option><option value="all">Everything</option>
      <option v-for="s in session.lookups.issueStatus" :key="s">{{ s }}</option></select>
    <select v-model="filters.kind" class="input w-auto" aria-label="Type"><option value="">All types</option><option v-for="k in session.lookups.issueKinds" :key="k">{{ k }}</option></select>
    <select v-model="filters.severity" class="input w-auto" aria-label="Severity"><option value="">All severities</option><option v-for="s in session.lookups.severity" :key="s">{{ s }}</option></select>
    <select v-model="filters.project_id" class="input w-auto" aria-label="Project"><option value="">All projects</option><option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option></select>
  </div>
  <section class="card overflow-x-auto">
    <table class="w-full min-w-[860px]">
      <thead><tr><th class="th">Issue</th><th class="th">Project</th><th class="th">Severity</th><th class="th">Status</th><th class="th">Owner</th><th class="th">Due</th><th class="th w-[30%]">Latest progress</th></tr></thead>
      <tbody>
        <tr v-for="i in issues" :key="i.id" class="cursor-pointer hover:bg-[#F8FAFB]" @click="edit(i)">
          <td class="td"><div class="font-semibold">{{ i.title }}</div><div class="text-[12px] text-muted">{{ i.kind }}</div></td>
          <td class="td">{{ i.project?.name || 'Programme-level' }}</td>
          <td class="td"><span :class="['pill', sevCls[i.severity]]">{{ i.severity }}</span></td>
          <td class="td">{{ i.status }}</td>
          <td class="td">{{ i.owner_name || '—' }}</td>
          <td class="td whitespace-nowrap" :class="i.overdue && 'font-semibold text-r'">{{ fmtDate(i.due_date) }}<div v-if="i.overdue" class="text-[11.5px]">overdue</div></td>
          <td class="td text-[13px]">{{ i.latest_note || '—' }}</td>
        </tr>
      </tbody>
    </table>
    <p v-if="!issues.length" class="p-10 text-center text-muted">Nothing matches these filters.</p>
  </section>
  <IssueDrawer :open="open" :issue="editing" :projects="editing?.project_id && !editable().some((p) => p.id === editing.project_id) ? projects : editable()" @close="open = false" @saved="load" />
</template>
