import axios from 'axios';
import router from '../Router';
import {useAuthStore} from "@/Stores/Auth.js";

const csrfToken = document.head.querySelector('meta[name="csrf-token"]');

const http_axios = axios.create({
    baseURL: (import.meta.env.VITE_BASE_URL || '/')+'api/',
    headers: {
        'X-CSRF-TOKEN': csrfToken.content,
        "Content-type": "application/json",
        "Access-Control-Allow-Origin": "*",
    },
});

http_axios.interceptors.request.use((request) => {
    const userStore = useAuthStore()
    const token = userStore.getToken()
    
    console.log('Interceptor - URL:', request.url);
    console.log('Interceptor - Token disponível:', !!token);
    console.log('Interceptor - Token:', token ? token.substring(0, 20) + '...' : 'null');

    if(token) {
        request.headers.Authorization = 'Bearer ' + token
        console.log('Interceptor - Authorization header set:', request.headers.Authorization.substring(0, 30) + '...');
    } else {
        console.log('Interceptor - Nenhum token encontrado');
    }

    return request;
})


http_axios.interceptors.response.use(
    response => response,
    error => {
        if(error.response && [401,403].includes(error.response.status)) {
            // Verificar se há código de referência na URL
            const urlParams = new URLSearchParams(window.location.search);
            const hasReferralCode = urlParams.has('code') || urlParams.has('ref');
            
            // Se há código de referência, não redirecionar para login
            if (hasReferralCode) {
                console.log('Código de referência detectado, não redirecionando para login');
                return Promise.reject(error);
            }
            
            //window.location.href = "/";
            router.push('login');
        }
        return Promise.reject(error);
    }
)

export default http_axios;
