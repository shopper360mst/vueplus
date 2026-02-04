import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import i18n from './i18n'
import { useSessionStore } from './stores/session'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(i18n)

// Initialize locale from session store
const sessionStore = useSessionStore()
i18n.global.locale.value = sessionStore.locale

app.mount('#app')
