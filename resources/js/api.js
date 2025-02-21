// src/api.js
import axios from 'axios';

const apiClient = axios.create({
    // baseURL: 'http://furama-goods.local:9995/api',
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
    },
    withCredentials: true,
});

// Добавьте интерсепторы, если необходимо
apiClient.interceptors.response.use(
    response => response,
    error => {
        // Обработка ошибок
        return Promise.reject(error);
    }
);

export default apiClient;
