<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { errorText, fmtDate, http, notify, num, session } from '../api';
import WeekNav from '../components/WeekNav.vue';

const props = defineProps({ id: String });
const route = useRoute();
const router = useRouter();
const week = ref(route.query.week || session.week);
const data = ref(null);
const f = ref(null);
const busy = ref(false);
const error = ref('');

async function load() {
  data.value = (await http.get(`/projects/${props.id}/weekly-update`, { params: { week: week.value } })).data;
  const u = data.value.update;
  f.value = {
    rag: u.rag || 'G', progress: u.progress ?? null, phase: u.phase, summary: u.summary || '',
    critical_path: u.critical_path || '', achievements: u.achievements || '', next_steps: u.next_steps || '',
    support_needed: u.support_needed || '',
    metrics: (u.metrics || []).map((m) => ({ ...m, target: m.target ?? '' })),
  };
}
watch(week, load, { immediate: true });

const ragOpts = [
  { k: 'G', label: 'On track', help: 'Will hit the next milestone', cls: 'peer-checked:border-g peer-checked:bg-g-soft', dot: 'bg-g' },
  { k: 'A', label: 'At risk', help: 'Issues, but recoverable by the team', cls: 'peer-checked:border-a peer-checked:bg-a-soft', dot: 'bg-a' },
  { k: 'R', label: 'Off track', help: 'Needs a decision or help to recover', cls: 'peer-checked:border-r peer-checked:bg-r-soft', dot: 'bg-r' },
];
const prevMetric = (label) => data.value?.previous_metrics?.find((m) => m.label === label);
const addMetric = () => f.value.metrics.push({ label: '', value: '', target: '', unit: '%' });
const needsReason = computed(() => f.value && f.value.rag !== 'G' && !f.value.critical_path.trim());

async function save() {
  if (needsReason.value) { error.value = 'Please list what is in the critical path — it explains the RAG status in the report.'; return; }
  busy.value = true; error.value = '';
  try {
    const metrics = f.value.metrics.filter((m) => m.label.trim() && m.value !== '' && m.value !== null)
      .map((m) => ({ ...m, target: m.target === '' ? null : m.target }));
    await http.put(`/projects/${props.id}/weekly-update`, { ...f.value, metrics, week: week.value });
    notify('Update saved — it will appear in this week\'s report.');
    router.push(`/projects/${props.id}`);
  } catch (e) { error.value = errorText(e); } finally { busy.value = false; }
}
</script>

<template>
  <template v-if="data && f">
    <p class="mb-1 text-[12.5px] text-muted"><RouterLink :to="`/projects/${id}`" class="hover:underline">{{ data.project.name }}</RouterLink> /</p>
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
      <h1>{{ data.exists ? 'Edit weekly update' : 'Weekly update' }}</h1>
      <WeekNav v-model="week" />
    </div>
    <div v-if="data.carried_from" class="mb-4 rounded-[16px] border border-[#BFE3F4] bg-brand-soft px-4 py-3 text-[13.5px] text-brand-ink">
      Pre-filled from the update for the week of <b>{{ fmtDate(data.carried_from) }}</b>. Change only what moved — achievements start blank each week.
    </div>
    <div v-if="!data.can_edit" class="mb-4 rounded-[16px] bg-a-soft px-4 py-3 text-a">You can view this update but not edit it.</div>

    <form class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_340px]" @submit.prevent="save">
      <div class="card p-6">
        <p v-if="error" class="mb-4 rounded-[13px] bg-r-soft px-3 py-2 text-[13px] text-[#8B241D]" role="alert">{{ error }}</p>

        <fieldset class="mb-5"><legend class="label">Overall status</legend>
          <div class="grid gap-2 sm:grid-cols-3">
            <label v-for="o in ragOpts" :key="o.k" class="cursor-pointer">
              <input v-model="f.rag" type="radio" name="rag" :value="o.k" class="peer sr-only">
              <span :class="['block rounded-[16px] border-2 border-line px-3 py-2.5 peer-focus-visible:ring-3 peer-focus-visible:ring-brand/25', o.cls]">
                <span class="flex items-center gap-2 font-semibold"><i :class="['size-3 rounded-full', o.dot]" />{{ o.label }}</span>
                <span class="text-[12px] text-muted">{{ o.help }}</span>
              </span>
            </label>
          </div>
        </fieldset>

        <div class="mb-5 grid gap-4 sm:grid-cols-[1fr_200px]">
          <div><label class="label" for="summary">Headline <span class="hint">one or two sentences leadership will read first</span></label>
            <textarea id="summary" v-model="f.summary" class="input min-h-[70px]" required maxlength="1000" /></div>
          <div class="flex flex-col gap-4">
            <div><label class="label">Phase</label><select v-model="f.phase" class="input"><option v-for="p in session.lookups.phases" :key="p">{{ p }}</option></select></div>
            <div><label class="label">Overall progress <span class="hint">{{ f.progress ?? '—' }}%</span></label>
              <input v-model.number="f.progress" type="range" min="0" max="100" step="5" class="w-full accent-[var(--color-brand)]"></div>
          </div>
        </div>

        <div class="mb-5 grid gap-4 md:grid-cols-2">
          <div><label class="label">Key items in the critical path <span class="hint">blockers — one per line</span></label>
            <textarea v-model="f.critical_path" class="input min-h-32" :class="needsReason && '!border-a'" placeholder="iOS app crashes during loan application" /></div>
          <div><label class="label">Achieved this week <span class="hint">one per line</span></label>
            <textarea v-model="f.achievements" class="input min-h-32" placeholder="UAT kicked off on TestRail" /></div>
          <div><label class="label">Next steps <span class="hint">one per line</span></label>
            <textarea v-model="f.next_steps" class="input min-h-24" /></div>
          <div><label class="label">Support needed from leadership <span class="hint">optional</span></label>
            <textarea v-model="f.support_needed" class="input min-h-24" placeholder="Escalations, decisions, resources" /></div>
        </div>

        <div class="mb-2 flex items-center justify-between"><span class="label !mb-0">Metrics <span class="hint">test coverage, pass rate, adoption… tracked week on week</span></span>
          <button type="button" class="btn btn-sm" @click="addMetric">+ Add metric</button></div>
        <div class="hidden grid-cols-[minmax(0,2.4fr)_1fr_1fr_80px_32px] gap-2 text-[12px] font-semibold text-muted sm:grid">
          <span>Name</span><span>This week</span><span>Target</span><span>Unit</span><span />
        </div>
        <div v-for="(m, i) in f.metrics" :key="i" :class="['grid', data.previous_metrics?.length ? 'mb-5' : 'mb-2']" class=" grid-cols-2 gap-2 sm:grid-cols-[minmax(0,2.4fr)_1fr_1fr_80px_32px]">
          <input v-model="m.label" class="input col-span-2 sm:col-span-1" placeholder="e.g. UAT pass rate" aria-label="Metric name">
          <div class="relative"><input v-model="m.value" type="number" step="any" class="input" aria-label="Value">
            <span v-if="prevMetric(m.label)" class="absolute -bottom-[17px] left-1 text-[11px] text-muted">last wk {{ num(prevMetric(m.label).value) }}</span></div>
          <input v-model="m.target" type="number" step="any" class="input" placeholder="100" aria-label="Target">
          <input v-model="m.unit" class="input" aria-label="Unit">
          <button type="button" class="text-muted hover:text-r" aria-label="Remove metric" @click="f.metrics.splice(i, 1)">×</button>
        </div>
        <p v-if="!f.metrics.length" class="text-[13px] text-muted">No metrics yet.</p>

        <div class="mt-7 flex gap-2">
          <button class="btn btn-primary" :disabled="busy || !data.can_edit">{{ busy ? 'Saving…' : 'Save update' }}</button>
          <RouterLink :to="`/projects/${id}`" class="btn">Cancel</RouterLink>
        </div>
      </div>

      <aside class="card h-fit p-5 text-[13px]">
        <div class="eyebrow mb-2">Writing a good update</div>
        <ul class="list-disc space-y-1.5 pl-4 text-[#34424F]">
          <li>Lead with the headline: where are we and what changed.</li>
          <li><b>Amber or red needs a reason</b> in the critical path — it's what leadership acts on.</li>
          <li>Keep bullets short; the report formats them for you.</li>
          <li>Log production incidents under <RouterLink to="/issues" class="text-brand">Issues &amp; risks</RouterLink> so they're tracked to closure.</li>
        </ul>
      </aside>
    </form>
  </template>
</template>
