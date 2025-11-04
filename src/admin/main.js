import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import 'vue-toastification/dist/index.css'
import VueTelInput from 'vue-tel-input'
import 'vue-tel-input/vue-tel-input.css'

import './style/global-admin.css'
import '@/admin/utils/cdn-tailwindcss.js'

import '@fortawesome/fontawesome-free/css/all.min.css'
//import '@fortawesome/fontawesome-free/js/all.js';

import { useGlobalSettings } from './composables/useGlobalSettings'

// Create the Vue application instance

const app = createApp(App)

// Configure VueTelInput
const globalOptions = {
  mode: 'international',
  dropdownOptions: {
    showSearchBox: true
  }
}

app.use(VueTelInput, globalOptions)
app.use(router)

// Mount the Vue application to the DOM element with the ID 'schesab-admin-app'.
app.mount('#schesab-admin-app')

// Initialize global settings
const { generalSettings } = useGlobalSettings()
