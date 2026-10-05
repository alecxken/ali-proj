<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { can, http, session, toast } from './api';

const route = useRoute();
const router = useRouter();
const nav = computed(() => [
  { to: '/', label: 'Dashboard', icon: 'M3 3h7v9H3zM14 3h7v5h-7zM14 12h7v9h-7zM3 16h7v5H3z', exact: true },
  { to: '/projects', label: 'Projects', icon: 'M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z' },
  { to: '/issues', label: 'Issues & risks', icon: 'M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z' },
  { to: '/reports', label: 'Weekly report', icon: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M8 13h8M8 17h5' },
  ...(can.manage() ? [{ to: '/people', label: 'People', icon: 'M9 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM1 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1M17 3.5a4 4 0 0 1 0 7.5M23 21v-1a6 6 0 0 0-4-5.6' }] : []),
]);
const active = (n) => (n.exact ? route.path === n.to : route.path.startsWith(n.to));

async function logout() {
  await http.post('/logout');
  session.user = null;
  router.push('/login');
}
</script>

<template>
  <RouterView v-if="!session.user || route.meta.public" />
  <div v-else class="grid min-h-screen lg:grid-cols-[228px_1fr]">
    <aside class="flex flex-wrap items-center gap-1 bg-brand-ink p-3 text-[#DCE9EF] lg:sticky lg:top-0 lg:h-screen lg:flex-col lg:flex-nowrap lg:items-stretch lg:p-4">
      <RouterLink to="/" class="mr-3 flex items-center gap-2.5 px-2 py-1 text-[16px] font-bold text-white lg:mb-4">
        <span class="grid size-7 place-items-center rounded-lg bg-white"><span class="size-3 rounded-full bg-brand ring-4 ring-brand/25" /></span>
        Pulse
      </RouterLink>
      <RouterLink v-for="n in nav" :key="n.to" :to="n.to"
                  :class="['flex items-center gap-2.5 rounded-lg px-2.5 py-2 hover:bg-white/10', active(n) && 'bg-white/15 font-semibold text-white']">
        <svg viewBox="0 0 24 24" class="size-[17px] shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path :d="n.icon" /></svg>
        <span class="hidden sm:inline">{{ n.label }}</span>
      </RouterLink>
      <div class="ml-auto flex items-center gap-2.5 lg:mt-auto lg:ml-0 lg:border-t lg:border-white/10 lg:pt-3">
        <RouterLink to="/account" class="grid size-8 place-items-center rounded-full bg-[#2C7090] text-[12px] font-bold text-white" :title="session.user.name">{{ session.user.initials }}</RouterLink>
        <div class="hidden min-w-0 leading-tight lg:block">
          <div class="truncate font-semibold text-white">{{ session.user.name }}</div>
          <button class="text-[12px] text-[#9DBCCB] hover:text-white" @click="logout">Sign out</button>
        </div>
      </div>
    </aside>
    <main class="min-w-0 px-4 py-5 sm:px-8 sm:py-6"><RouterView :key="route.path" /></main>
  </div>

  <div class="fixed right-4 bottom-4 z-50 flex flex-col gap-2" aria-live="polite">
    <div v-for="t in toast.items" :key="t.id"
         :class="['rounded-lg border px-4 py-2.5 text-[13.5px] shadow-lg', t.type === 'error' ? 'border-[#F2C2BD] bg-r-soft text-[#8B241D]' : 'border-[#B9E2CC] bg-g-soft text-[#135E3C]']">
      {{ t.message }}
    </div>
  </div>
</template>
