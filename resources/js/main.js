import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router';
import ElementPlus from 'element-plus'; // Импорт Element Plus
import 'element-plus/dist/index.css'; // Импорт стилей

// Создание приложения
const app = createApp(App);

// Получение переменной из атрибута && Передача переменной в корневой компонент через provide
const appElement = document.getElementById('app');
const dealId = appElement.getAttribute('data-deal-id');
app.provide('dealId', dealId);

app.use(router);
app.use(ElementPlus);

// Монтирование приложения
app.mount('#app');
