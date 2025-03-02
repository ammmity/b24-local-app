import { createRouter, createWebHistory } from 'vue-router';
import AppLayout from './components/layout/AppLayout.vue';
import Dashboard from './components/views/Dashboard.vue';
import Settings from './components/views/Settings.vue';
import OperationPrices from './components/views/OperationPrices.vue';
import OperationLogs from './components/views/OperationLogs.vue';

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
                path: 'details',
                name: 'Details',
                component: () => import('./components/views/Details.vue')
            },
            {
                path: 'deal-production-scheme',
                name: 'Deal production scheme',
                component: () => import('./components/views/DealProductionScheme.vue')
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
            {
                path: 'operation-prices',
                name: 'operation-prices',
                component: () => import('./components/views/OperationPrices.vue'),
            },
            {
                path: 'operation-logs',
                name: 'operation-logs',
                component: () => import('./components/views/OperationLogs.vue')
            }
        ]
    }
];

const router = createRouter({
    history: createWebHistory(), // Используйте HTML5 History API
    routes,
});

export default router;
