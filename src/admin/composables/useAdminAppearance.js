import { ref, reactive, computed, watch, onMounted, readonly } from 'vue'
import { settingsApi } from '@/admin/api.js'
import { __ } from '@wordpress/i18n'

// Define initial/default state for admin appearance settings
const defaultAdminAppearanceSettings = {
  adminDarkModeEnabled: false, // Default to light mode
  adminFontFamily: 'Inter', // Default font family
  headerBackgroundColor: '#081a30',
  headerTextColor: '#ffffff',
  sBackgroundColor: '#ffffff',
  sTextColor: '#081a30'
}

const appearanceSettings = reactive({ ...defaultAdminAppearanceSettings })

const isLoaded = ref(false)

// --- Font Loading Logic ---
const loadedFontId = 'schedula-google-font-style'

const loadGoogleFont = (fontFamily) => {
  if (!fontFamily) return

  const fontId = 'schedula-google-font-style'
  let style = document.getElementById(fontId)
  if (!style) {
    style = document.createElement('style')
    style.id = fontId
    document.head.appendChild(style)
  }

  // Use system fonts instead of external Google Fonts
  const fontName = fontFamily.split(',')[0].trim()

  style.textContent = `
        .schedula-app {
            font-family: '${fontName}', sans-serif !important;
        }
    `
}
// --- End Font Loading Logic ---

const fetchAppearanceSettings = async () => {
  if (isLoaded.value) {
    return
  }
  try {
    const response = await settingsApi.getGeneralSettings()
    const generalData = response.data

    // Update appearanceSettings with fetched data, merging with defaults
    Object.assign(appearanceSettings, {
      adminDarkModeEnabled:
        generalData.adminDarkModeEnabled ??
        defaultAdminAppearanceSettings.adminDarkModeEnabled,
      adminFontFamily:
        generalData.adminFontFamily ??
        defaultAdminAppearanceSettings.adminFontFamily,
      headerBackgroundColor:
        generalData.headerBackgroundColor ??
        defaultAdminAppearanceSettings.headerBackgroundColor,
      headerTextColor:
        generalData.headerTextColor ??
        defaultAdminAppearanceSettings.headerTextColor,
      sBackgroundColor:
        generalData.sBackgroundColor ??
        defaultAdminAppearanceSettings.sBackgroundColor,
      sTextColor:
        generalData.sTextColor ?? defaultAdminAppearanceSettings.sTextColor
    })

    // Load the font after fetching the settings
    loadGoogleFont(appearanceSettings.adminFontFamily)

    isLoaded.value = true
  } catch (error) {
    console.error('Error fetching admin appearance settings:', error)
    // Fallback to defaults if fetch fails
    Object.assign(appearanceSettings, { ...defaultAdminAppearanceSettings })
    loadGoogleFont(appearanceSettings.adminFontFamily) // Load default font on error
    isLoaded.value = true
  }
}

const saveAppearanceSettings = async () => {
  try {
    const currentGeneralSettingsResponse =
      await settingsApi.getGeneralSettings()
    const currentGeneralSettings = currentGeneralSettingsResponse.data

    const updatedGeneralSettings = {
      ...currentGeneralSettings,
      // Only send the appearance-related settings to the backend
      adminDarkModeEnabled: appearanceSettings.adminDarkModeEnabled,
      adminFontFamily: appearanceSettings.adminFontFamily,
      headerBackgroundColor: appearanceSettings.headerBackgroundColor,
      headerTextColor: appearanceSettings.headerTextColor,
      sBackgroundColor: appearanceSettings.sBackgroundColor,
      sTextColor: appearanceSettings.sTextColor
    }

    await settingsApi.saveGeneralSettings(updatedGeneralSettings)
    return {
      success: true,
      message: __(
        'Appearance settings saved successfully.',
        'schedula-appointment-booking'
      )
    }
  } catch (error) {
    console.error('Error saving admin appearance settings:', error)
    const errorMessage =
      error.response && error.response.data && error.response.data.message
        ? error.response.data.message
        : __(
            'Failed to save appearance settings.',
            'schedula-appointment-booking'
          )
    return { success: false, message: errorMessage }
  }
}

const resetAppearanceSettings = async () => {
  // Reset reactive state to default values
  Object.assign(appearanceSettings, { ...defaultAdminAppearanceSettings })
  // Save the default settings to the backend
  const result = await saveAppearanceSettings()
  return result
}

const adminCustomStyles = computed(() => {
  const fontFamily = appearanceSettings.adminFontFamily

  // Define light/dark mode dependent colors and properties
  let pageBgColor = '#f3f4f6' // Tailwind gray-100 for body background (default for light)
  let textColor = '#1f2937' // Tailwind gray-900 for general text (default for light)
  let cardBgColor = '#ffffff' // White for cards/panels (default for light)
  let cardTextColor = '#374151' // Tailwind gray-700 for card text (default for light)
  let borderColor = '#e5e7eb' // Tailwind gray-200 for borders (default for light)
  let inputBgColor = '#ffffff' // White for input backgrounds (default for light)
  let inputTextColor = '#374151' // Tailwind gray-700 for input text (default for light)
  let inputBorderColor = '#d1d5db' // Tailwind gray-300 for input borders (default for light)
  let colorScheme = 'light'

  if (appearanceSettings.adminDarkModeEnabled) {
    pageBgColor = '#111827' // Dark mode body background
    textColor = '#e5e7eb' // Dark mode general text
    cardBgColor = '#1f2937' // Dark mode card background
    cardTextColor = '#e5e7eb' // Dark mode card text
    borderColor = '#374151' // Dark mode borders
    inputBgColor = '#374151' // Dark mode input background
    inputTextColor = '#e5e7eb' // Dark mode input text
    inputBorderColor = '#4b5563' // Dark mode input border
    colorScheme = 'dark'
  }

  return {
    '--admin-page-bg-color': pageBgColor,
    '--admin-text-color': textColor,
    '--admin-card-bg-color': cardBgColor,
    '--admin-card-text-color': cardTextColor,
    '--admin-border-color': borderColor,
    '--admin-input-bg-color': inputBgColor,
    '--admin-input-text-color': inputTextColor,
    '--admin-input-border-color': inputBorderColor,
    '--admin-header-bg-color': appearanceSettings.headerBackgroundColor,
    '--admin-header-text-color': appearanceSettings.headerTextColor,
    '--admin-s-bg-color': appearanceSettings.sBackgroundColor,
    '--admin-s-text-color': appearanceSettings.sTextColor,
    '--admin-suggestion-indigo-bg': appearanceSettings.adminDarkModeEnabled
      ? '#1e1b4b'
      : '#eef2ff',
    '--admin-suggestion-indigo-text': appearanceSettings.adminDarkModeEnabled
      ? '#a5b4fc'
      : '#4f46e5',
    '--admin-suggestion-yellow-bg': appearanceSettings.adminDarkModeEnabled
      ? '#282506'
      : '#fefce8',
    '--admin-suggestion-yellow-text': appearanceSettings.adminDarkModeEnabled
      ? '#fde047'
      : '#eab308',
    '--admin-suggestion-red-bg': appearanceSettings.adminDarkModeEnabled
      ? '#450a0a'
      : '#fef2f2',
    '--admin-suggestion-red-text': appearanceSettings.adminDarkModeEnabled
      ? '#fca5a5'
      : '#dc2626',
    '--admin-button-primary-bg': appearanceSettings.adminDarkModeEnabled
      ? '#1d4ed8'
      : '#2563eb',
    '--admin-button-primary-text': '#ffffff',
    '--admin-button-secondary-bg': appearanceSettings.adminDarkModeEnabled
      ? '#15803d'
      : '#16a34a',
    '--admin-button-third-bg': appearanceSettings.adminDarkModeEnabled
      ? '#dc2b2b'
      : '#c02727',
    '--admin-button-secondary-text': '#ffffff',
    '--admin-link-green-bg': appearanceSettings.adminDarkModeEnabled
      ? '#15803d'
      : '#16a34a',
    '--admin-link-green-text': '#ffffff',
    '--admin-link-blue-bg': appearanceSettings.adminDarkModeEnabled
      ? '#1d4ed8'
      : '#2563eb',
    '--admin-link-blue-text': '#ffffff',
    '--admin-link-purple-bg': appearanceSettings.adminDarkModeEnabled
      ? '#7e22ce'
      : '#9333ea',
    '--admin-link-purple-text': '#ffffff',
    '--admin-link-orange-bg': appearanceSettings.adminDarkModeEnabled
      ? '#c2410c'
      : '#ea580c',
    '--admin-link-orange-text': '#ffffff',
    '--admin-link-indigo-bg': appearanceSettings.adminDarkModeEnabled
      ? '#4338ca'
      : '#4f46e5',
    '--admin-link-indigo-text': '#ffffff',
    '--admin-link-indigo-bg-light': appearanceSettings.adminDarkModeEnabled
      ? '#312e81'
      : '#eef2ff', // Lighter indigo for selected state
    '--admin-link-gray-bg': appearanceSettings.adminDarkModeEnabled
      ? '#374151'
      : '#f3f4f6',
    '--admin-link-gray-text': appearanceSettings.adminDarkModeEnabled
      ? '#e5e7eb'
      : '#1f2937',
    '--admin-link-gray-bg-light': appearanceSettings.adminDarkModeEnabled
      ? '#4b5563'
      : '#e5e7eb', // Lighter gray for selected state
    '--admin-status-green-text': appearanceSettings.adminDarkModeEnabled
      ? '#4ade80'
      : '#22c55e',
    '--admin-status-red-text': appearanceSettings.adminDarkModeEnabled
      ? '#f87171'
      : '#ef4444',
    '--admin-input-text-muted': appearanceSettings.adminDarkModeEnabled
      ? '#6b7280'
      : '#9ca3af',
    '--admin-badge-green-bg': appearanceSettings.adminDarkModeEnabled
      ? '#14532d'
      : '#dcfce7',
    '--admin-badge-green-text': appearanceSettings.adminDarkModeEnabled
      ? '#86efac'
      : '#166534',
    '--admin-badge-yellow-bg': appearanceSettings.adminDarkModeEnabled
      ? '#713f12'
      : '#fef9c3',
    '--admin-badge-yellow-text': appearanceSettings.adminDarkModeEnabled
      ? '#fcd34d'
      : '#92400e',
    '--admin-badge-blue-bg': appearanceSettings.adminDarkModeEnabled
      ? '#1e3a8a'
      : '#dbeafe',
    '--admin-badge-blue-text': appearanceSettings.adminDarkModeEnabled
      ? '#93c5fd'
      : '#1e40af',
    '--admin-badge-red-bg': appearanceSettings.adminDarkModeEnabled
      ? '#7f1d1d'
      : '#fee2e2',
    '--admin-badge-red-text': appearanceSettings.adminDarkModeEnabled
      ? '#fca5a5'
      : '#991b1b',
    '--admin-badge-purple-bg': appearanceSettings.adminDarkModeEnabled
      ? '#5b21b6'
      : '#f5f3ff',
    '--admin-badge-purple-text': appearanceSettings.adminDarkModeEnabled
      ? '#c4b5fd'
      : '#7c3aed',
    '--admin-badge-gray-bg': appearanceSettings.adminDarkModeEnabled
      ? '#374151'
      : '#f3f4f6',
    '--admin-badge-gray-text': appearanceSettings.adminDarkModeEnabled
      ? '#d1d5db'
      : '#1f2937',
    '--admin-modal-overlay-bg': appearanceSettings.adminDarkModeEnabled
      ? 'rgba(0, 0, 0, 0.5)'
      : 'rgba(0, 0, 0, 0.5)',
    'color-scheme': colorScheme
  }
})

export function useAdminAppearance() {
  onMounted(() => {
    if (!isLoaded.value) {
      fetchAppearanceSettings()
    }
  })

  // Watch for changes in the font family and load the new font
  watch(
    () => appearanceSettings.adminFontFamily,
    (newFont, oldFont) => {
      if (newFont !== oldFont) {
        loadGoogleFont(newFont)
      }
    }
  )

  return {
    appearanceSettings: appearanceSettings, // Return the reactive object directly
    adminCustomStyles,
    fetchAppearanceSettings,
    saveAppearanceSettings,
    resetAppearanceSettings,
    isLoaded: readonly(isLoaded)
  }
}
;('')
