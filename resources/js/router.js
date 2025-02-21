import { createRouter, createWebHistory } from 'vue-router';
import AppLayout from './components/layout/AppLayout.vue';
import Dashboard from './components/views/Dashboard.vue';
import Deal from './components/views/Deal.vue';
import Settings from './components/views/Settings.vue';
// import ProductProduction from './components/views/ProductProduction.vue';

const routes = [
    {
        path: '/app',
        component: AppLayout,
        children: [
            {
                path: '',
                name: 'Dashboard',
                component: Dashboard,
            },
            {
                path: 'deal',
                name: 'Deal',
                component: Deal,
            },
            {
                path: 'settings',
                name: 'Settings',
                component: Settings,
            },
            {
                path: 'operation-types',
                name: 'OperationTypes',
                component: () => import('./components/views/OpertaionTypes.vue')
            },
            {
                path: 'product-production/:id',
                name: 'ProductProduction',
                component: () => import('./components/views/ProductProduction.vue')
            },
        ]
    }
];

const router = createRouter({
    history: createWebHistory(), // Используйте HTML5 History API
    routes,
});

export default router;
