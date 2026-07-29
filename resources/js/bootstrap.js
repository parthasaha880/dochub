import axios from 'axios';

const token = document.head.querySelector('meta[name="csrf-token"]');

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

const bearer = localStorage.getItem('edams_token');
if (bearer) {
    window.axios.defaults.headers.common.Authorization = `Bearer ${bearer}`;
}
