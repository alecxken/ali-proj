import axios from 'axios';
import { reactive } from 'vue';

// Same-origin session auth: Laravel sets the XSRF-TOKEN cookie and axios echoes it back.
export const http = axios.create({
    baseURL: '/api',
    withCredentials: true,
    withXSRFToken: true,
    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
});

export const session = reactive({ user: null, lookups: null, week: null, loaded: false });

export const toast = reactive({ items: [] });
export function notify(message, type = 'success') {
    const id = Math.random();
    toast.items.push({ id, message, type });
    setTimeout(() => (toast.items = toast.items.filter((t) => t.id !== id)), 4500);
}

/** First validation message from a Laravel 422, or a generic one. */
export function errorText(e) {
    const d = e?.response?.data;
    if (d?.errors) return Object.values(d.errors)[0][0];
    return d?.message || 'Something went wrong. Please try again.';
}

http.interceptors.response.use(
    (r) => r,
    (e) => {
        if (e.response?.status === 401 && session.user) {
            session.user = null;
            window.location.href = '/login';
        } else if (e.response?.status === 403) {
            notify("You don't have permission to do that.", 'error');
        } else if (e.response?.status === 419) {
            notify('Your session expired — reload the page.', 'error');
        }
        return Promise.reject(e);
    },
);

export async function loadSession() {
    try {
        const { data } = await http.get('/me');
        Object.assign(session, data, { loaded: true });
    } catch {
        Object.assign(session, { user: null, loaded: true });
    }
}

export const can = {
    manage: () => ['admin', 'pmo'].includes(session.user?.role),
    admin: () => session.user?.role === 'admin',
    write: () => session.user && session.user.role !== 'viewer',
};

// ---- small formatting helpers
export const fmtDate = (s, opts = { day: 'numeric', month: 'short' }) =>
    s ? new Date(s.slice(0, 10) + 'T00:00:00').toLocaleDateString('en-GB', opts) : '—';
export const addWeeks = (iso, n) => {
    const d = new Date(iso + 'T00:00:00');
    d.setDate(d.getDate() + 7 * n);
    return d.toLocaleDateString('en-CA');
};
export const num = (v) => (v == null ? '—' : Number.isInteger(+v) ? String(+v) : (+v).toFixed(1));
export const ratio = (m) => {
    const t = m.target || ((m.unit ?? '%') === '%' ? 100 : null);
    return t ? Math.max(0, Math.min(1, m.value / t)) : null;
};
export const tone = (r) => (r == null ? 'bg-brand-sky' : r >= 0.85 ? 'bg-g' : r >= 0.5 ? 'bg-a' : 'bg-r');
