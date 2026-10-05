<script setup>
import { onMounted, ref } from 'vue';
import { can, errorText, http, notify, session } from '../api';
import Drawer from '../components/Drawer.vue';

const users = ref([]);
const open = ref(false);
const f = ref({});
const error = ref('');
const temp = ref('');
const load = async () => (users.value = (await http.get('/users')).data);
onMounted(load);

function edit(u) {
  if (!can.admin()) return;
  error.value = ''; temp.value = '';
  f.value = u ? { ...u, password: '', reset_password: false } : { name: '', email: '', title: '', role: 'contributor', active: true, password: '' };
  open.value = true;
}
async function save() {
  error.value = '';
  try {
    const { data } = f.value.id ? await http.put(`/users/${f.value.id}`, f.value) : await http.post('/users', f.value);
    load();
    if (data.temporary_password) { temp.value = data.temporary_password; f.value = { ...data.user, password: '' }; }
    else { open.value = false; notify('Saved.'); }
  } catch (e) { error.value = errorText(e); }
}
</script>
<template>
  <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div><h1>People</h1><p class="text-muted">Sign-in: <b>{{ session.auth_driver }}</b> — change with <code>PULSE_AUTH_DRIVER</code>.</p></div>
    <button v-if="can.admin()" class="btn btn-primary" @click="edit(null)">+ Add person</button>
  </div>
  <section class="card overflow-x-auto">
    <table class="w-full min-w-[640px]">
      <thead><tr><th class="th">Name</th><th class="th">Email</th><th class="th">Role</th><th class="th">Sign-in</th><th class="th">Status</th></tr></thead>
      <tbody>
        <tr v-for="u in users" :key="u.id" :class="[can.admin() && 'cursor-pointer hover:bg-[#F8FAFB]', !u.active && 'opacity-55']" @click="edit(u)">
          <td class="td"><div class="font-semibold">{{ u.name }}</div><div class="text-[12px] text-muted">{{ u.title }}</div></td>
          <td class="td">{{ u.email }}</td>
          <td class="td">{{ session.lookups.roles[u.role] }}</td>
          <td class="td">{{ u.auth_source }}</td>
          <td class="td">{{ u.active ? 'Active' : 'Disabled' }}</td>
        </tr>
      </tbody>
    </table>
  </section>
  <div class="mt-4 grid gap-3 text-[13px] sm:grid-cols-2 xl:grid-cols-5">
    <div v-for="(label, k) in session.lookups.roles" :key="k" class="card p-3"><b>{{ label }}</b>
      <p class="text-muted">{{ { admin: 'Everything, including people and settings.', pmo: 'Create and edit any project, generate reports.', owner: 'Manage their own projects and team.', contributor: 'Post updates, milestones and issues on their projects.', viewer: 'Read-only dashboards and reports.' }[k] }}</p></div>
  </div>

  <Drawer :open="open" :title="f.id ? 'Edit person' : 'Add person'" @close="open = false">
    <form id="user-form" @submit.prevent="save">
      <p v-if="error" class="mb-4 rounded-lg bg-r-soft px-3 py-2 text-[13px] text-[#8B241D]">{{ error }}</p>
      <div v-if="temp" class="mb-4 rounded-lg border border-[#B9E2CC] bg-g-soft px-3 py-2 text-[13px]">Temporary password: <code class="font-bold">{{ temp }}</code> — share it securely. It won't be shown again.</div>
      <div class="mb-4"><label class="label">Full name</label><input v-model="f.name" class="input" required></div>
      <div class="mb-4"><label class="label">Email</label><input v-model="f.email" type="email" class="input" required></div>
      <div class="mb-4"><label class="label">Job title</label><input v-model="f.title" class="input"></div>
      <div class="mb-4"><label class="label">Role</label><select v-model="f.role" class="input"><option v-for="(l, k) in session.lookups.roles" :key="k" :value="k">{{ l }}</option></select></div>
      <div v-if="!f.id" class="mb-4"><label class="label">Password <span class="hint">leave blank to generate one</span></label><input v-model="f.password" type="text" class="input" minlength="8" autocomplete="off"></div>
      <label v-else class="mb-4 flex items-center gap-2"><input v-model="f.reset_password" type="checkbox"> Reset password</label>
      <label class="flex items-center gap-2"><input v-model="f.active" type="checkbox"> Active (can sign in)</label>
    </form>
    <template #footer><button class="btn" @click="open = false">Close</button><button class="btn btn-primary" form="user-form">Save</button></template>
  </Drawer>
</template>
