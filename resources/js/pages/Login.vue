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
  <div class="grid min-h-screen place-items-center bg-gradient-to-br from-brand-ink to-brand p-6">
    <form class="card w-full max-w-sm p-7" @submit.prevent="submit">
      <div class="mb-1 flex items-center gap-2.5 text-[18px] font-bold text-brand-ink">
        <span class="grid size-8 place-items-center rounded-lg bg-brand-soft"><span class="size-3 rounded-full bg-brand ring-4 ring-brand/20" /></span>Pulse
      </div>
      <p class="mb-6 text-muted">Capture project progress once — the weekly report writes itself.</p>
      <p v-if="error" class="mb-4 rounded-lg border border-[#F2C2BD] bg-r-soft px-3 py-2 text-[13px] text-[#8B241D]" role="alert">{{ error }}</p>
      <label class="label" for="login">Work email or username</label>
      <input id="login" v-model="form.login" class="input mb-4" autocomplete="username" required autofocus>
      <label class="label" for="pw">Password</label>
      <input id="pw" v-model="form.password" type="password" class="input mb-4" autocomplete="current-password" required>
      <label class="mb-5 flex items-center gap-2 text-[13px]"><input v-model="form.remember" type="checkbox"> Keep me signed in</label>
      <button class="btn btn-primary w-full justify-center py-2.5" :disabled="busy">{{ busy ? 'Signing in…' : 'Sign in' }}</button>
    </form>
  </div>
</template>
