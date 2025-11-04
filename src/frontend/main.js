import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import 'vue-toastification/dist/index.css'
import VueTelInput from 'vue-tel-input'
import 'vue-tel-input/vue-tel-input.css'

// Apply CSS variables immediately if available
if (window.schedulaFrontendData && window.schedulaFrontendData.settings) {
  const settings = window.schedulaFrontendData.settings

  const getBorderRadius = (size) => {
    switch (size) {
      case 'small':
        return '4px'
      case 'medium':
        return '8px'
      case 'large':
        return '12px'
      default:
        return '8px'
    }
  }

  const getShadow = (strength) => {
    switch (strength) {
      case 'none':
        return 'none'
      case 'small':
        return '0 1px 2px 0 rgba(0, 0, 0, 0.05)'
      case 'medium':
        return '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)'
      case 'large':
        return '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)'
      default:
        return '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)'
    }
  }

  const getFontSize = (size) => {
    switch (size) {
      case 'small':
        return '0.875rem'
      case 'medium':
        return '1rem'
      case 'large':
        return '1.125rem'
      default:
        return '1rem'
    }
  }

  const cssVariables = {
    '--primary-color': settings.colors?.primary || '#081a30',
    '--background-color': settings.colors?.background || '#f8fafc',
    '--text-color': settings.colors?.textColor || '#1a202c',
    '--header-background-color': settings.colors?.primary || '#081a30',
    '--header-text-color': settings.colors?.headerText || '#ffffff',
    '--border-radius-form': settings.theme?.roundedCorners
      ? getBorderRadius(settings.layout?.borderRadius)
      : '0',
    '--shadow-form': settings.theme?.shadows
      ? getShadow(settings.layout?.shadowStrength)
      : 'none',
    '--font-family-form': settings.layout?.fontFamily || 'Inter, sans-serif',
    '--base-font-size-form': getFontSize(settings.layout?.fontSize)
  }

  // Apply CSS variables immediately
  Object.keys(cssVariables).forEach((key) => {
    document.documentElement.style.setProperty(key, cssVariables[key])
  })
}

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

// Mount the app
app.mount('#schesab-frontend-app')

router.isReady().then(() => {
  if (window.location.hash === '#/') {
    window.history.replaceState(
      {},
      document.title,
      window.location.pathname + window.location.search
    )
  }
})
