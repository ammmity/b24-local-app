import axios from 'axios';
import { user } from './user';
const apiClient = axios.create({
    baseURL: import.meta.env.DEV ? '/api' : import.meta.env.VITE_APP_API_URL,
    headers: {
        'Content-Type': 'application/json',
    },
    // withCredentials: true,
});

apiClient.interceptors.request.use(config => {
    if (user.auth) {
        // Добавляем auth данные в параметры запроса, чтобы бекенд понимал что мы работаем из б24
        config.params = {
            ...config.params,
            DOMAIN: user.auth.DOMAIN,
            APP_SID: user.auth.APP_SID,
            AUTH_ID: user.auth.AUTH_ID,
            REFRESH_ID: user.auth.REFRESH_ID,
        };
    }
    return config;
});

apiClient.interceptors.response.use(
    response => response,
    error => {
        // Обработка ошибок
        return Promise.reject(error);
    }
);

export default apiClient;
