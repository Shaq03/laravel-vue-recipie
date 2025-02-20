import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router'
import axios from 'axios'
import store from './store'

// Change baseURL to include the full path
axios.defaults.baseURL = 'http://localhost:8000/api'
axios.defaults.withCredentials = true
axios.defaults.headers.common['Accept'] = 'application/json'
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// Initialize auth headers before creating the app
store.dispatch('initializeAuth')

const app = createApp(App)
app.use(store)
app.use(router)
app.mount('#app')
