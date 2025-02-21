import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router';

// Создание приложения
const app = createApp(App);

// Получение переменной из атрибута
const appElement = document.getElementById('app');
const dealId = appElement.getAttribute('data-deal-id');

// Передача переменной в корневой компонент через provide
app.provide('dealId', dealId);

// Использование роутера
app.use(router);

// Монтирование приложения
app.mount('#app');
