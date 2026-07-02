import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import './style.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Initialize Theme (Dark/Light Mode) globally
import { useThemeStore } from './stores/theme'
const themeStore = useThemeStore()
themeStore.initTheme()

app.mount('#app')
