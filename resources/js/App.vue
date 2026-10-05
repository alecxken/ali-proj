<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { can, http, session, toast } from './api';

const route = useRoute();
const router = useRouter();
const menuOpen = ref(false);
const nav = computed(() => [
  { to: '/', label: 'Dashboard', icon: 'layout-dashboard', exact: true },
  { to: '/projects', label: 'Projects', icon: 'folder-open' },
  { to: '/issues', label: 'Issues & risks', icon: 'alert-triangle' },
  { to: '/reports', label: 'Weekly report', icon: 'file-report' },
  ...(can.manage() ? [{ to: '/people', label: 'People', icon: 'users' }] : []),
]);
const active = (n) => (n.exact ? route.path === n.to : route.path.startsWith(n.to));
watch(() => route.path, () => (menuOpen.value = false));

async function logout() {
  await http.post('/logout');
  session.user = null;
  router.push('/login');
}
</script>

<template>
  <RouterView v-if="!session.user || route.meta.public" />
  <div v-else class="flex min-h-screen">
    <div v-if="menuOpen" class="fixed inset-0 z-10 bg-black/40 lg:hidden" @click="menuOpen = false" />

    <aside :class="['fixed top-0 z-20 flex h-screen w-[252px] flex-shrink-0 flex-col bg-white transition-transform duration-200 lg:sticky lg:my-3 lg:ml-3 lg:h-[calc(100vh-24px)] lg:translate-x-0 lg:rounded-[24px]', menuOpen ? 'translate-x-0' : '-translate-x-full']"
           style="box-shadow:0 2px 10px rgba(0,61,88,.05), 0 18px 40px -26px rgba(0,61,88,.35);">
      <RouterLink to="/" class="flex items-center gap-3 px-5 pt-6 pb-5">
        <img src="/images/ncba-logo-dark.svg" alt="NCBA" class="h-8 w-auto">
        <span class="h-7 w-px bg-brand-lgray" />
        <span class="text-[14px] font-semibold text-brand-dark">Pulse</span>
      </RouterLink>

      <nav class="flex flex-1 flex-col gap-0.5 overflow-y-auto px-3.5 pb-3">
        <RouterLink v-for="n in nav" :key="n.to" :to="n.to" :class="['nav-item', active(n) && 'on']">
          <i :class="['nav-ico ti', 'ti-' + n.icon]" />
          <span class="nav-lbl">{{ n.label }}</span>
        </RouterLink>
      </nav>

      <div class="px-3.5 pb-4">
        <div class="mx-3.5 my-3 h-px bg-brand-lgray" />
        <div class="flex items-center gap-3 rounded-[14px] px-3 py-2">
          <RouterLink to="/account" class="grid size-9 flex-shrink-0 place-items-center rounded-full bg-brand-green text-[12px] font-bold text-brand-darker" :title="session.user.name">{{ session.user.initials }}</RouterLink>
          <div class="min-w-0 flex-1 leading-tight">
            <div class="truncate text-[12.5px] font-bold text-brand-gray">{{ session.user.name }}</div>
            <div class="text-[10px] font-light" style="color:#93A4AC;">{{ session.lookups?.roles[session.user.role] }}</div>
          </div>
          <button class="cursor-pointer border-0 bg-transparent" style="color:#A9B8C0;" aria-label="Sign out" title="Sign out" @click="logout"><i class="ti ti-logout text-[18px]" /></button>
        </div>
      </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
      <div class="mx-auto flex w-full max-w-[1400px] items-center gap-3 px-5 pt-5 sm:px-8 lg:hidden">
        <button class="icon-btn" aria-label="Open menu" @click="menuOpen = true"><i class="ti ti-menu-2 text-[19px]" /></button>
      </div>
      <main class="mx-auto w-full max-w-[1400px] min-w-0 flex-1 px-5 pt-6 pb-9 sm:px-8 sm:pt-7 lg:px-10"><RouterView :key="route.path" /></main>
    </div>
  </div>

  <div class="fixed right-4 bottom-4 z-[999] flex flex-col gap-2 sm:right-7 sm:bottom-7" aria-live="polite">
    <TransitionGroup name="toast">
      <div v-for="t in toast.items" :key="t.id" :class="['toast', t.type === 'error' ? 'red' : 'green']">
        <i :class="['ti text-[18px]', t.type === 'error' ? 'ti-alert-circle' : 'ti-circle-check']" />{{ t.message }}
      </div>
    </TransitionGroup>
  </div>
</template>
