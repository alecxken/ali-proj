<script setup>
import { computed, onMounted, ref } from 'vue';
import { addWeeks, errorText, fmtDate, http, notify, session } from '../api';
import IssueDrawer from '../components/IssueDrawer.vue';
import Metrics from '../components/Metrics.vue';
import RagBadge from '../components/RagBadge.vue';

const props = defineProps({ id: String });
const p = ref(null);
const issueOpen = ref(false);
const editingIssue = ref(null);
const newMs = ref({ title: '', due_date: '', status: 'Not started' });
const showAllUpdates = ref(false);
const TL_SHORT = 3;

const load = async () => (p.value = (await http.get(`/projects/${props.id}`)).data);
onMounted(load);

const latest = computed(() => p.value?.updates?.[0]);
const previous = computed(() => p.value?.updates?.[1]);
const hasThisWeek = computed(() => latest.value?.week_start === session.week);
// One entry per reporting week, newest first, from this week back to the first update.
// Weeks without an update stay in the sequence as gaps so missing progression is visible.
const timeline = computed(() => {
  const ups = p.value?.updates || [];
  if (!ups.length) return [];
  const byWeek = Object.fromEntries(ups.map((u) => [u.week_start, u]));
  const oldest = ups[ups.length - 1].week_start;
  const rows = [];
  for (let w = session.week, n = 0; w >= oldest && n < 104; w = addWeeks(w, -1), n++) {
    const u = byWeek[w];
    const prev = u ? byWeek[addWeeks(w, -1)] || ups.find((x) => x.week_start < w) : null;
    rows.push({ week: w, age: n, u, delta: u && prev && u.progress != null && prev.progress != null ? u.progress - prev.progress : null, moved: u && prev && u.rag !== prev.rag ? prev.rag : null });
  }
  return rows;
});
const ageLabel = (n) => (n === 0 ? 'This week' : n === 1 ? 'Last week' : `${n} weeks ago`);
const ragTone = { G: 'bg-g', A: 'bg-brand-amber', R: 'bg-brand-danger' };
const lines = (t) => (t || '').split(/\r?\n/).map((s) => s.replace(/^[•\-*\s]+/, '').trim()).filter(Boolean);
const openIssues = computed(() => (p.value?.issues || []).filter((i) => session.lookups.openIssueStatus.includes(i.status)));

async function addMilestone() {
  try {
    await http.post(`/projects/${props.id}/milestones`, newMs.value);
    newMs.value = { title: '', due_date: '', status: 'Not started' };
    load();
  } catch (e) { notify(errorText(e), 'error'); }
}
async function saveMilestone(m) {
  try { await http.put(`/milestones/${m.id}`, m); notify('Milestone updated.'); load(); } catch (e) { notify(errorText(e), 'error'); }
}
async function deleteMilestone(m) {
  if (confirm(`Delete milestone "${m.title}"?`)) { await http.delete(`/milestones/${m.id}`); load(); }
}
function editIssue(i) { editingIssue.value = i ? { ...i } : { project_id: p.value.id }; issueOpen.value = true; }
</script>

<template>
  <template v-if="p">
    <p class="mb-1 text-[12.5px] text-muted"><RouterLink to="/projects" class="hover:underline">Projects</RouterLink> / {{ p.programme || 'No programme' }}</p>
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
      <div>
        <div class="flex flex-wrap items-center gap-3"><h1>{{ p.name }}</h1><RagBadge :rag="latest?.rag" /></div>
        <p class="mt-1 text-[13px] text-muted">
          {{ p.code }} · {{ p.phase }} · {{ p.status }} · Owner {{ p.owner?.name || '—' }}
          <span v-if="p.target_date"> · Target {{ fmtDate(p.target_date, { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
        </p>
      </div>
      <div class="flex gap-2">
        <RouterLink v-if="p.can_manage" :to="`/projects/${p.id}/edit`" class="btn">Edit</RouterLink>
        <RouterLink v-if="p.can_edit" :to="`/projects/${p.id}/update`" class="btn btn-primary">{{ hasThisWeek ? "Edit this week's update" : "Post this week's update" }}</RouterLink>
      </div>
    </div>
    <p v-if="p.description" class="mb-4 max-w-3xl text-[#34424F]">{{ p.description }}</p>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.7fr)_minmax(320px,1fr)]">
      <div class="flex flex-col gap-4">
        <section class="card">
          <div class="card-h">
            <h2>Latest update <span v-if="latest" class="font-normal text-muted">· week of {{ fmtDate(latest.week_start) }} by {{ latest.author?.name || '—' }}</span></h2>
            <span v-if="latest && !hasThisWeek" class="pill !bg-a-soft !text-a">Not updated this week</span>
          </div>
          <div v-if="!latest" class="p-8 text-center text-muted">No updates yet.</div>
          <div v-else class="p-5">
            <p class="mb-4 text-[15px]">{{ latest.summary }}</p>
            <div class="grid gap-5 md:grid-cols-2">
              <div>
                <div class="eyebrow mb-1">Key items in the critical path</div>
                <ul class="list-disc pl-5 marker:text-r"><li v-for="l in lines(latest.critical_path)" :key="l" class="my-1">{{ l }}</li></ul>
                <p v-if="!lines(latest.critical_path).length" class="text-muted">None reported.</p>
                <template v-if="lines(latest.support_needed).length">
                  <div class="eyebrow mt-4 mb-1 !text-a">Support needed</div>
                  <ul class="list-disc pl-5 marker:text-a"><li v-for="l in lines(latest.support_needed)" :key="l" class="my-1">{{ l }}</li></ul>
                </template>
              </div>
              <div>
                <div class="eyebrow mb-1">Achieved</div>
                <ul class="list-disc pl-5 marker:text-g"><li v-for="l in lines(latest.achievements)" :key="l" class="my-1">{{ l }}</li></ul>
                <p v-if="!lines(latest.achievements).length" class="text-muted">—</p>
                <div class="eyebrow mt-4 mb-1">Next steps</div>
                <ul class="list-disc pl-5"><li v-for="l in lines(latest.next_steps)" :key="l" class="my-1">{{ l }}</li></ul>
              </div>
            </div>
          </div>
        </section>

        <section class="card">
          <div class="card-h"><h2>Progress timeline</h2><span class="t-sub !text-[11.5px]">{{ p.updates.length }} update{{ p.updates.length === 1 ? '' : 's' }} · weekly</span></div>
          <p v-if="!timeline.length" class="empty"><i class="ti ti-timeline" />No updates yet — the timeline starts with the first one.</p>
          <ol v-else class="tl px-5 py-5 sm:px-6">
            <li v-for="r in (showAllUpdates ? timeline : timeline.slice(0, TL_SHORT))" :key="r.week" class="tl-item">
              <span v-if="r.u" :class="['tl-dot', ragTone[r.u.rag] || 'bg-n']"><i class="ti ti-check" /></span>
              <span v-else class="tl-dot bg-white !text-brand-mgray"><i class="ti ti-minus" /></span>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                  <span :class="['text-[13.5px] font-bold', r.age === 0 ? 'text-brand-blue' : 'text-brand-dark']">{{ ageLabel(r.age) }}</span>
                  <span class="text-[11.5px] text-muted">week of {{ fmtDate(r.week, { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
                  <RagBadge v-if="r.u" :rag="r.u.rag" />
                  <span v-if="r.u?.progress != null" class="pill">{{ r.u.progress }}%<template v-if="r.delta"> · {{ r.delta > 0 ? '+' : '' }}{{ r.delta }}</template></span>
                  <span v-if="r.moved" class="pill !bg-a-soft !text-a">was {{ { G: 'on track', A: 'at risk', R: 'off track' }[r.moved] }}</span>
                </div>
                <template v-if="r.u">
                  <p class="mt-1 text-[13.5px]">{{ r.u.summary }}</p>
                  <div class="mt-1 flex items-center gap-3 text-[11.5px] text-muted">
                    <span>{{ r.u.author?.name }}</span>
                    <RouterLink v-if="p.can_edit" :to="{ path: `/projects/${p.id}/update`, query: { week: r.week } }" class="font-bold">Edit</RouterLink>
                  </div>
                </template>
                <p v-else class="mt-1 text-[13px] text-muted">No update posted.
                  <RouterLink v-if="p.can_edit" :to="{ path: `/projects/${p.id}/update`, query: { week: r.week } }" class="font-bold">Post one</RouterLink></p>
              </div>
            </li>
          </ol>
          <button v-if="timeline.length > TL_SHORT" class="w-full cursor-pointer border-0 border-t border-brand-lgray bg-transparent py-3 text-[12.5px] font-bold text-brand-blue hover:bg-canvas" @click="showAllUpdates = !showAllUpdates">
            {{ showAllUpdates ? 'Show less' : `View more (${timeline.length - TL_SHORT} earlier)` }}
          </button>
        </section>
      </div>

      <div class="flex flex-col gap-4">
        <section class="card"><div class="card-h"><h2>Metrics</h2><span class="text-[12px] text-muted">change vs previous week</span></div>
          <div class="px-5 py-3"><Metrics :metrics="latest?.metrics" :previous="previous?.metrics || []" /></div></section>

        <section id="milestones" class="card">
          <div class="card-h"><h2>Milestones</h2><span class="text-[12.5px] text-muted">{{ p.milestones.filter((m) => m.status === 'Done').length }}/{{ p.milestones.length }} done</span></div>
          <ul class="px-5 py-1">
            <li v-for="m in p.milestones" :key="m.id" class="flex items-center gap-2 border-b border-line py-2 last:border-0">
              <div class="min-w-0 flex-1">
                <div :class="['text-[13.5px]', m.status === 'Done' && 'text-muted line-through']">{{ m.title }}</div>
                <div class="text-[12px]" :class="m.overdue ? 'font-semibold text-r' : 'text-muted'">{{ m.due_date ? fmtDate(m.due_date) : 'No date' }}{{ m.overdue ? ' · overdue' : '' }}</div>
              </div>
              <select v-if="p.can_edit" v-model="m.status" class="input !w-auto !py-1 text-[12.5px]" :aria-label="`Status of ${m.title}`" @change="saveMilestone(m)">
                <option v-for="s in session.lookups.milestoneStatus" :key="s">{{ s }}</option></select>
              <span v-else class="pill">{{ m.status }}</span>
              <button v-if="p.can_edit" class="px-1 text-muted hover:text-r" :aria-label="`Delete ${m.title}`" @click="deleteMilestone(m)">×</button>
            </li>
          </ul>
          <form v-if="p.can_edit" class="flex flex-wrap gap-2 border-t border-line px-5 py-3" @submit.prevent="addMilestone">
            <input v-model="newMs.title" class="input min-w-40 flex-1" placeholder="Add a milestone" required>
            <input v-model="newMs.due_date" type="date" class="input !w-auto" aria-label="Due date">
            <button class="btn">Add</button>
          </form>
        </section>

        <section class="card">
          <div class="card-h"><h2>Open issues &amp; risks</h2><button v-if="p.can_edit" class="btn btn-sm" @click="editIssue(null)">+ Log</button></div>
          <p v-if="!openIssues.length" class="p-5 text-center text-muted">Nothing open.</p>
          <button v-for="i in openIssues" :key="i.id" class="block w-full border-b border-line px-5 py-3 text-left last:border-0 hover:bg-canvas" @click="p.can_edit && editIssue(i)">
            <div class="flex items-start justify-between gap-2"><b>{{ i.title }}</b><span :class="['pill shrink-0', { Critical: '!bg-r !text-white', High: '!bg-r-soft !text-r', Medium: '!bg-a-soft !text-a' }[i.severity]]">{{ i.severity }}</span></div>
            <div class="text-[12.5px] text-muted">{{ i.kind }} · {{ i.status }}<span v-if="i.due_date" :class="i.overdue && 'text-r font-semibold'"> · due {{ fmtDate(i.due_date) }}</span></div>
          </button>
        </section>
      </div>
    </div>
    <IssueDrawer :open="issueOpen" :issue="editingIssue" :projects="[p]" @close="issueOpen = false" @saved="load" />
  </template>
</template>
