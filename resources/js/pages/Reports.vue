<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { errorText, http, notify, session } from '../api';
import RagBadge from '../components/RagBadge.vue';
import WeekNav from '../components/WeekNav.vue';

const route = useRoute();
const week = ref(route.query.week || session.week);
const programme = ref(route.query.programme || '');
const programmes = ref([]);
const snap = ref(null);
const runs = ref([]);
const busy = ref('');

const load = async () => (snap.value = (await http.get('/reports/preview', { params: { week: week.value, programme: programme.value || undefined } })).data);
const loadRuns = async () => (runs.value = (await http.get('/reports')).data);
watch([week, programme], load, { immediate: true });
onMounted(async () => {
  loadRuns();
  const p = (await http.get('/projects', { params: { status: 'all' } })).data;
  programmes.value = [...new Set(p.map((x) => x.programme).filter(Boolean))].sort();
});

async function generate(format) {
  busy.value = format;
  try {
    const { data } = await http.post('/reports', { week: week.value, format, programme: programme.value || null });
    window.location.href = `/api/reports/${data.id}/download`;
    notify(`${format === 'pdf' ? 'PDF' : 'PowerPoint'} report ready.`);
    loadRuns();
  } catch (e) { notify(errorText(e), 'error'); } finally { busy.value = ''; }
}
</script>
<template>
  <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div><h1>Weekly report</h1><p class="text-muted">Built from the updates, milestones and issues in Pulse — nothing to copy and paste.</p></div>
    <div class="flex flex-wrap items-center gap-2">
      <select v-model="programme" class="input w-auto" aria-label="Programme"><option value="">All programmes</option><option v-for="p in programmes" :key="p">{{ p }}</option></select>
      <WeekNav v-model="week" />
    </div>
  </div>

  <div v-if="snap" class="grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(300px,1fr)]">
    <section class="card">
      <div class="card-h"><h2>What goes in the report</h2><span class="text-[12.5px] text-muted">{{ snap.week_label }}</span></div>
      <div v-if="snap.totals.submitted < snap.totals.expected" class="mx-5 mt-4 rounded-[13px] border border-[rgba(249,168,37,.3)] bg-a-soft px-4 py-2.5 text-[13px] text-[#6B4600]">
        <b>{{ snap.totals.expected - snap.totals.submitted }} project(s) haven't updated this week</b> — the report will show their last update, flagged as stale:
        {{ snap.projects.filter((p) => p.stale).map((p) => p.name).join(', ') }}.
      </div>
      <table class="mt-2 w-full">
        <tbody>
          <tr v-for="p in snap.projects" :key="p.id">
            <td class="td"><div class="font-semibold">{{ p.name }}</div><div class="text-[12.5px] text-muted">{{ p.summary || 'No update yet' }}</div></td>
            <td class="td w-28"><RagBadge :rag="p.rag" /></td>
          </tr>
        </tbody>
      </table>
      <p class="px-5 py-3 text-[13px] text-muted">Plus {{ snap.issues.length }} open issues &amp; risks ({{ snap.totals.production_issues }} production), and {{ snap.totals.overdue_milestones }} overdue milestones.</p>
    </section>

    <div class="flex flex-col gap-4">
      <section class="card p-5">
        <h2 class="mb-1">Download</h2>
        <p class="mb-4 text-[13px] text-muted">PDF for email; PowerPoint (fully editable) for the review meeting.</p>
        <div class="grid gap-2">
          <button class="btn btn-primary justify-center py-2.5" :disabled="!!busy" @click="generate('pdf')">{{ busy === 'pdf' ? 'Building PDF…' : 'Download PDF' }}</button>
          <button class="btn justify-center py-2.5" :disabled="!!busy" @click="generate('pptx')">{{ busy === 'pptx' ? 'Building slides…' : 'Download PowerPoint' }}</button>
        </div>
        <p class="mt-3 text-[12px] text-muted">Reports are also built automatically every Friday at 16:00.</p>
      </section>
      <section class="card">
        <div class="card-h"><h2>Recent reports</h2></div>
        <p v-if="!runs.length" class="p-5 text-center text-muted">None yet.</p>
        <a v-for="r in runs" :key="r.id" :href="`/api/reports/${r.id}/download`" class="flex items-center justify-between gap-3 border-b border-line px-5 py-2.5 text-[13px] last:border-0 hover:bg-canvas">
          <span><b>Week of {{ r.week_start }}</b> · {{ r.programme || 'All programmes' }}<br><span class="text-muted">{{ r.user?.name || 'Scheduled' }} · {{ new Date(r.created_at).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' }) }}</span></span>
          <span class="pill uppercase">{{ r.format }}</span>
        </a>
      </section>
    </div>
  </div>
</template>
