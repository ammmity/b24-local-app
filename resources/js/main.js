import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router';
import ElementPlus from 'element-plus'; // Импорт Element Plus
import 'element-plus/dist/index.css'; // Импорт стилей
import { user } from './user';

// Создание приложения
const app = createApp(App);

const appElement = document.getElementById('productionApp');

const userAuth = JSON.parse(appElement.getAttribute('data-user-auth'));
user.auth = userAuth;

const dealId = appElement.getAttribute('data-deal-id'); // id сделки
app.provide('dealId', dealId);

app.use(router);
app.use(ElementPlus);

// Монтирование приложения
app.mount('#productionApp');
