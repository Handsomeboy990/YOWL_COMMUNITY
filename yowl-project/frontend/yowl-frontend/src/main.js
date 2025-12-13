import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'

import App from './App.vue'
import router from './router'

const pinia = createPinia()
pinia.use(piniaPluginPersistedstate)
import { useUserStore } from './stores/user'

const app = createApp(App)

app.use(pinia)
app.use(router)

app.mount('#app')

const userStore = useUserStore();

//get current user on mounted
userStore.initUser()
