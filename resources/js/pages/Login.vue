<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { errorText, http, loadSession, session } from '../api';

const form = ref({ login: '', password: '', remember: false });
const error = ref('');
const busy = ref(false);
const router = useRouter();
const route = useRoute();

async function submit() {
  busy.value = true; error.value = '';
  try {
    await http.get('/me').catch(() => {}); // primes the XSRF cookie
    const { data } = await http.post('/login', form.value);
    Object.assign(session, data, { loaded: true });
    router.push(route.query.next?.startsWith('/') ? route.query.next : '/');
  } catch (e) {
    error.value = errorText(e);
  } finally { busy.value = false; }
}
</script>
<template>
  <div class="grid min-h-screen place-items-center p-6">
    <form class="card w-full max-w-sm p-7 sm:p-8" @submit.prevent="submit">
      <div class="mb-2 flex items-center gap-3">
        <img src="/images/ncba-logo-dark.svg" alt="NCBA" class="h-9 w-auto">
        <span class="h-7 w-px bg-brand-lgray" />
        <span class="text-[18px] font-bold text-brand-dark">Pulse</span>
      </div>
      <p class="t-sub mb-6">Capture project progress once — the weekly report writes itself.</p>
      <p v-if="error" class="mb-4 rounded-[13px] border border-[#F2C2BD] bg-r-soft px-3 py-2 text-[13px] text-[#8B241D]" role="alert">{{ error }}</p>
      <label class="label" for="login">Work email or username</label>
      <input id="login" v-model="form.login" class="input mb-4" autocomplete="username" required autofocus>
      <label class="label" for="pw">Password</label>
      <input id="pw" v-model="form.password" type="password" class="input mb-4" autocomplete="current-password" required>
      <label class="mb-5 flex items-center gap-2 text-[13px] font-semibold"><input v-model="form.remember" type="checkbox"> Keep me signed in</label>
      <button class="btn btn-primary w-full" :disabled="busy">{{ busy ? 'Signing in…' : 'Sign in' }}</button>
    </form>
  </div>
</template>
