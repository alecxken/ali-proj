<script setup>
import { ref } from 'vue';
import { errorText, http, notify, session } from '../api';
const f = ref({ current: '', password: '', password_confirmation: '' });
const error = ref('');
async function save() {
  error.value = '';
  try { await http.put('/me/password', f.value); notify('Password changed.'); f.value = { current: '', password: '', password_confirmation: '' }; }
  catch (e) { error.value = errorText(e); }
}
</script>
<template>
  <h1>{{ session.user.name }}</h1>
  <p class="mb-5 text-muted">{{ session.user.email }} · {{ session.lookups.roles[session.user.role] }}</p>
  <form v-if="session.user.auth_source === 'database'" class="card max-w-md p-6" @submit.prevent="save">
    <h2 class="mb-4">Change password</h2>
    <p v-if="error" class="mb-4 rounded-[13px] bg-r-soft px-3 py-2 text-[13px] text-[#8B241D]">{{ error }}</p>
    <label class="label">Current password</label><input v-model="f.current" type="password" class="input mb-4" required autocomplete="current-password">
    <label class="label">New password <span class="hint">8+ characters</span></label><input v-model="f.password" type="password" class="input mb-4" minlength="8" required autocomplete="new-password">
    <label class="label">Confirm new password</label><input v-model="f.password_confirmation" type="password" class="input mb-5" required autocomplete="new-password">
    <button class="btn btn-primary">Update password</button>
  </form>
  <p v-else class="card max-w-md p-6 text-muted">Your password is managed by your organisation's directory.</p>
</template>
