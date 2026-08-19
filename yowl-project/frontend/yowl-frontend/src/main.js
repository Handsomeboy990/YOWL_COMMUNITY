import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'

import App from './App.vue'
import router from './router'
import { i18n } from './i18n'

const pinia = createPinia()
pinia.use(piniaPluginPersistedstate)
import { useUserStore } from './stores/user'

const app = createApp(App)

app.use(pinia)
app.use(router)
app.use(i18n)

app.mount('#app')

const userStore = useUserStore();

//get current user on mounted
userStore.initUser()
