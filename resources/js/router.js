import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from './components/views/Dashboard.vue'; // Импортируйте ваши компоненты
import Deal from './components/views/Deal.vue'; // Импортируйте ваши компоненты
import Settings from './components/views/Settings.vue'; // Импортируйте ваши компоненты

const routes = [
    {
        path: '/app/',
        name: 'Dashboard',
        component: Dashboard,
    },
    {
        path: '/app/deal/',
        name: 'Deal',
        component: Deal,
    },
    {
        path: '/app/settings/',
        name: 'Settings',
        component: Settings,
    },
];

const router = createRouter({
    history: createWebHistory(), // Используйте HTML5 History API
    routes,
});

export default router;
