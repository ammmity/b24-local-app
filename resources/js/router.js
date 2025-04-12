import { createRouter, createWebHistory } from 'vue-router';
import apiClient from './api';
import AppLayout from './components/layout/AppLayout.vue';
import Settings from './components/views/Settings.vue';
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
            {
                path: 'restricted',
                name: 'restricted',
                component: () => import('./components/views/Restricted.vue')
            },
        ]
    }
];

const router = createRouter({
    history: createWebHistory(), // Используйте HTML5 History API
    routes,
});

// Глобальный хук для проверки авторизации
router.beforeEach(async (to, from, next) => {
    // Если пользователь уже на странице restricted, пропускаем проверку
    if (to.name === 'restricted') {
        next();
        return;
    }

    try {
        // Проверяем авторизацию через API
        const response = await apiClient.get('/users/me');
        if (response.data && response.data.IS_TEHNOLOG) {
            // Пользователь авторизован и является технологом
            next();
        } else {
            // Пользователь не авторизован или не является технологом - показываем страницу restricted
            next({ name: 'restricted' });
        }
    } catch (error) {
        // Ошибка при проверке авторизации
        console.error('Ошибка при проверке авторизации:', error);
        next({ name: 'restricted' });
    }
});

// Для программной навигации можно создать вспомогательную функцию
export const navigateTo = (path) => {
    const fullPath = basePath + (path || '').replace(/^\//, '');
    window.location.href = fullPath;
}

export default router;
