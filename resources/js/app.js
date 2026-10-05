import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import { loadSession, session } from './api';

const routes = [
    { path: '/login', component: () => import('./pages/Login.vue'), meta: { public: true } },
    { path: '/', component: () => import('./pages/Dashboard.vue') },
    { path: '/projects', component: () => import('./pages/Projects.vue') },
    { path: '/projects/new', component: () => import('./pages/ProjectForm.vue') },
    { path: '/projects/:id', component: () => import('./pages/ProjectDetail.vue'), props: true },
    { path: '/projects/:id/edit', component: () => import('./pages/ProjectForm.vue'), props: true },
    { path: '/projects/:id/update', component: () => import('./pages/UpdateForm.vue'), props: true },
    { path: '/issues', component: () => import('./pages/Issues.vue') },
    { path: '/reports', component: () => import('./pages/Reports.vue') },
    { path: '/people', component: () => import('./pages/People.vue') },
    { path: '/account', component: () => import('./pages/Account.vue') },
    { path: '/:pathMatch(.*)*', redirect: '/' },
];

const router = createRouter({ history: createWebHistory(), routes, scrollBehavior: () => ({ top: 0 }) });

router.beforeEach(async (to) => {
    if (!session.loaded) await loadSession();
    if (!to.meta.public && !session.user) return { path: '/login', query: to.fullPath !== '/' ? { next: to.fullPath } : {} };
    if (to.path === '/login' && session.user) return '/';
});

createApp(App).use(router).mount('#app');
