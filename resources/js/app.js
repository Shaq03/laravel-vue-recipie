import './bootstrap';
import { createApp } from 'vue';
import router from './router';

const app = createApp({});

// Register components
app.component('cooking-history', require('./components/CookingHistory.vue').default);
app.component('add-cooking-history', require('./components/AddCookingHistory.vue').default);

// Use router
app.use(router);

// Mount the app
app.mount('#app');
