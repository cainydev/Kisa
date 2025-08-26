import.meta.glob([
    '../images/**',
]);

import Axios from 'axios';

window.axios = Axios.create({
    withCredentials: true,
    baseURL: 'http://localhost:8000/api',
});

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
