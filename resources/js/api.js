import axios from 'axios';

const apiClient = axios.create({
    baseURL: import.meta.env.DEV ? '/api' : import.meta.env.VITE_APP_API_URL,
    headers: {
        'Content-Type': 'application/json',
    },
    // withCredentials: true,
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
