import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router'
import store from './store'

// Initialize auth headers before creating the app
store.dispatch('initializeAuth')

const app = createApp(App)
app.use(store)
app.use(router)
app.mount('#app')
