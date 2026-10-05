<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fmtDate, http, session } from '../api';
import Bar from '../components/Bar.vue';
import RagBadge from '../components/RagBadge.vue';
import WeekNav from '../components/WeekNav.vue';

const route = useRoute();
const router = useRouter();
const week = ref(route.query.week || session.week);
const programme = ref(route.query.programme || '');
const snap = ref(null);
const filter = ref('all');

async function load() {
  router.replace({ query: { ...(week.value !== session.week && { week: week.value }), ...(programme.value && { programme: programme.value }) } });
  snap.value = (await http.get('/dashboard', { params: { week: week.value, programme: programme.value || undefined } })).data;
}
watch([week, programme], load, { immediate: true });

const shown = computed(() => {
  const p = snap.value?.projects || [];
  if (filter.value === 'attention') return p.filter((r) => ['R', 'A'].includes(r.rag));
  if (filter.value === 'missing') return p.filter((r) => r.stale);
  return p;
});
const t = computed(() => snap.value?.totals || {});
const trendLabel = { up: '▲ improved', down: '▼ worsened', flat: 'steady', new: 'new', none: '' };
</script>

<template>
  <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
      <p class="t-eyebrow mb-1">Good to see you, {{ session.user.name.split(' ')[0] }}</p>
      <h1>Portfolio this week</h1>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <select v-model="programme" class="input w-auto" aria-label="Programme">
        <option value="">All programmes</option>
        <option v-for="p in snap?.programmes" :key="p">{{ p }}</option>
      </select>
      <WeekNav v-model="week" />
      <RouterLink :to="{ path: '/reports', query: { week, programme: programme || undefined } }" class="btn btn-primary">Weekly report</RouterLink>
    </div>
  </div>

  <template v-if="snap">
    <!-- What I need to do -->
    <div v-if="snap.my_pending.length" class="mb-4 flex flex-wrap items-center gap-3 rounded-[16px] border border-[rgba(249,168,37,.3)] bg-a-soft px-4 py-3 text-[#6B4600]">
      <strong>{{ snap.my_pending.length }} of your projects still need this week's update:</strong>
      <RouterLink v-for="p in snap.my_pending" :key="p.id" :to="{ path: `/projects/${p.id}/update`, query: { week } }"
                  class="rounded-full bg-white px-3 py-1 text-[13px] font-semibold text-a shadow-sm hover:no-underline">{{ p.name }} →</RouterLink>
    </div>

    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
      <div class="card p-4"><div class="stat-lbl">Projects</div><div class="text-[26px] font-bold">{{ t.projects }}</div>
        <div class="mt-1.5 flex h-2 overflow-hidden rounded-full bg-n-soft">
          <span class="bg-g" :style="{ flex: t.G }" /><span class="bg-a" :style="{ flex: t.A }" /><span class="bg-r" :style="{ flex: t.R }" /><span class="bg-n-soft" :style="{ flex: t.none }" />
        </div></div>
      <div class="card p-4"><div class="stat-lbl">On track</div><div class="text-[26px] font-bold text-g">{{ t.G }}</div></div>
      <div class="card p-4"><div class="stat-lbl">At risk / off track</div><div class="text-[26px] font-bold"><span class="text-a">{{ t.A }}</span> <span class="text-muted">/</span> <span class="text-r">{{ t.R }}</span></div></div>
      <div class="card p-4"><div class="stat-lbl">Updates submitted</div><div class="text-[26px] font-bold">{{ t.submitted }}<span class="text-[16px] text-muted">/{{ t.expected }}</span></div>
        <Bar class="mt-1.5" :value="t.expected ? t.submitted / t.expected : 0" :tone="t.submitted === t.expected ? 'bg-g' : 'bg-a'" /></div>
      <div class="card p-4"><div class="stat-lbl">Open issues</div><div class="text-[26px] font-bold">{{ t.open_issues }}</div>
        <div class="text-[12px]" :class="t.critical_issues ? 'text-r font-semibold' : 'text-muted'">{{ t.critical_issues }} high / critical</div></div>
      <div class="card p-4"><div class="stat-lbl">Overdue milestones</div><div class="text-[26px] font-bold" :class="t.overdue_milestones && 'text-a'">{{ t.overdue_milestones }}</div></div>
    </div>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)]">
      <section class="card">
        <div class="card-h">
          <h2>Projects</h2>
          <div class="flex gap-1" role="tablist">
            <button v-for="[k, l] in [['all', 'All'], ['attention', 'Needs attention'], ['missing', 'No update']]" :key="k" role="tab"
                    :aria-selected="filter === k" :class="['dtab', filter === k && 'on']"
                    @click="filter = k">{{ l }}</button>
          </div>
        </div>
        <p v-if="!shown.length" class="p-8 text-center text-muted">Nothing here — nice.</p>
        <RouterLink v-for="p in shown" :key="p.id" :to="`/projects/${p.id}`"
                    class="grid grid-cols-[minmax(0,1fr)_auto] gap-x-4 gap-y-1.5 border-b border-line px-5 py-3.5 last:border-0 hover:bg-canvas">
          <div class="min-w-0">
            <div class="font-semibold">{{ p.name }}</div>
            <div class="mt-0.5 flex flex-wrap items-center gap-x-3 text-[12.5px] text-muted">
              <span>{{ p.phase }}</span><span v-if="p.owner">{{ p.owner }}</span>
              <span v-if="p.milestones_overdue.length" class="font-semibold text-a">{{ p.milestones_overdue.length }} overdue milestone{{ p.milestones_overdue.length > 1 ? 's' : '' }}</span>
              <span v-if="p.stale" class="font-semibold text-a">No update this week</span>
            </div>
          </div>
          <div class="flex flex-col items-end gap-1">
            <RagBadge :rag="p.rag" />
            <span :class="['text-[11.5px] font-bold', p.trend === 'up' ? 'text-g' : p.trend === 'down' ? 'text-r' : 'text-muted']">{{ trendLabel[p.trend] }}</span>
          </div>
          <p v-if="p.summary" class="col-span-2 text-[13px] text-[#34424F]">{{ p.summary }}</p>
          <p v-if="p.critical_path.length" class="col-span-2 text-[12.5px] text-r"><b>Critical path:</b> {{ p.critical_path[0] }}<span v-if="p.critical_path.length > 1" class="text-muted"> +{{ p.critical_path.length - 1 }} more</span></p>
          <div v-if="p.progress != null" class="col-span-2 flex items-center gap-2 text-[12px] text-muted"><Bar class="flex-1" :value="p.progress / 100" /> {{ p.progress }}%</div>
        </RouterLink>
      </section>

      <div class="flex flex-col gap-4">
        <section class="card">
          <div class="card-h"><h2>Production issues &amp; high risks</h2><RouterLink to="/issues" class="text-[13px] text-brand">All →</RouterLink></div>
          <p v-if="!snap.issues.length" class="p-6 text-center text-muted">No open issues.</p>
          <ul>
            <li v-for="i in snap.issues.filter((x) => x.kind === 'Production issue' || ['Critical', 'High'].includes(x.severity)).slice(0, 6)" :key="i.id"
                class="border-b border-line px-5 py-3 last:border-0">
              <div class="flex items-start justify-between gap-2">
                <span class="font-semibold">{{ i.title }}</span>
                <span :class="['pill', i.severity === 'Critical' && '!bg-r !text-white', i.severity === 'High' && '!bg-r-soft !text-r']">{{ i.severity }}</span>
              </div>
              <div class="stat-lbl">{{ i.project || 'Programme-level' }} · {{ i.status }}
                <span v-if="i.due_date" :class="i.overdue && 'font-semibold text-r'"> · due {{ fmtDate(i.due_date) }}</span></div>
              <p v-if="i.latest_note" class="mt-1 text-[12.5px]">{{ i.latest_note }}</p>
            </li>
          </ul>
        </section>
        <section class="card">
          <div class="card-h"><h2>Milestones — next 3 weeks</h2></div>
          <ul class="px-5 py-2">
            <template v-for="p in snap.projects" :key="p.id">
              <li v-for="m in [...p.milestones_overdue, ...p.milestones_due.filter((d) => !p.milestones_overdue.some((o) => o.title === d.title))]" :key="p.id + m.title"
                  class="flex justify-between gap-3 border-b border-line py-2 text-[13px] last:border-0">
                <span><b>{{ m.title }}</b><br><span class="text-muted">{{ p.name }}</span></span>
                <span :class="['text-right whitespace-nowrap', p.milestones_overdue.includes(m) ? 'font-semibold text-r' : 'text-muted']">
                  {{ fmtDate(m.due_date) }}<br><span class="text-[11.5px]">{{ p.milestones_overdue.includes(m) ? 'overdue' : m.status }}</span></span>
              </li>
            </template>
          </ul>
        </section>
      </div>
    </div>
  </template>
</template>
