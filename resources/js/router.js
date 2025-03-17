import { createRouter, createWebHistory } from 'vue-router';
import AppLayout from './components/layout/AppLayout.vue';
import Dashboard from './components/views/Dashboard.vue';
import Settings from './components/views/Settings.vue';
import Goods from './components/views/Goods.vue'    
import EmployeeReport from './components/views/EmployeeReport.vue'
import VirtualParts from './components/views/VirtualParts.vue'
// Получаем базовый путь из env
const basePath = import.meta.env.VITE_APP_BASE_PATH || '/production-app/public/app/';

const routes = [
    {
        path: import.meta.env.PROD ? import.meta.env.VITE_APP_BASE_PATH + 'app/' : import.meta.env.VITE_APP_BASE_PATH,
        component: AppLayout,
        children: [
            {
                path: '',
                name: 'goods',
                component: () => import('./components/views/Goods.vue')
            },
            {
                path: 'details',
                name: 'Details',
                component: () => import('./components/views/Details.vue')
            },
            {
                path: 'virtual-parts',
                name: 'virtual-parts',
                component: () => import('./components/views/VirtualParts.vue')
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
            },
            {
                path: 'employee-report',
                name: 'employee-report',
                component: () => import('./components/views/EmployeeReport.vue')
            },
        ]
    }
];

const router = createRouter({
    history: createWebHistory(), // Используйте HTML5 History API
    routes,
});

// Для программной навигации можно создать вспомогательную функцию
export const navigateTo = (path) => {
    const fullPath = basePath + (path || '').replace(/^\//, '');
    window.location.href = fullPath;
}

// Или использовать в компонентах так:
// this.$router.push(basePath + 'users')

export default router;
