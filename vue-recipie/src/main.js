import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router'
import store from './store'
import axios from './axios'

// Initialize auth headers before creating the app
store.dispatch('initializeAuth')

// Make axios available globally
const app = createApp(App)
app.config.globalProperties.$axios = axios

app.use(store)
app.use(router)
app.mount('#app')
