import { ref, onMounted, readonly } from 'vue'
import { settingsApi, utilityApi, paypalApi, stripeApi } from '@/admin/api.js'

// Reactive state for global settings
const generalSettings = ref({
  currencyCode: 'USD', // Store currency CODE as the primary setting
  currencySymbol: '$', // Derived from currencyCode
  enableLocalPayment: true
})

// Reactive state for the full list of currencies
const allCurrencies = ref([])

// Flag to track if settings have been loaded
const settingsLoaded = ref(false)

// Function to fetch settings and currency list
const fetchGlobalSettings = async (force = false) => {
  if (settingsLoaded.value && !force) {
    return // Already loaded
  }
  try {
    // Fetch all settings concurrently
    const [generalResponse, companyResponse, stripeResponse, utilityResponse] =
      await Promise.all([
        settingsApi.getGeneralSettings(),
        settingsApi.getCompanySettings(),
        stripeApi.getStripeSettings(),
        utilityApi.getUtilityData()
      ])

    allCurrencies.value = utilityResponse.data.currencies || []

    // Merge all settings into one global object
    const mergedSettings = {
      ...generalResponse.data,
      ...companyResponse.data,
      ...stripeResponse.data
    }

    generalSettings.value = { ...generalSettings.value, ...mergedSettings }

    // Determine the actual currency symbol based on the fetched currencyCode
    const selectedCurrency = allCurrencies.value.find(
      (c) => c.code === generalSettings.value.currencyCode
    )
    generalSettings.value.currencySymbol = selectedCurrency
      ? selectedCurrency.symbol
      : '$'

    settingsLoaded.value = true
  } catch (error) {
    console.error('Error fetching global settings:', error)
    // Keep defaults if fetch fails
  }
}

/**
 * Custom composable to provide global settings like currency symbol and payment options.
 * It ensures settings are fetched once and provides a reactive interface.
 */
export function useGlobalSettings() {
  onMounted(() => {
    fetchGlobalSettings() // Fetch settings when any component using this composable mounts
  })

  /**
   * Formats a price number with the current currency symbol.
   * @param {number} price - The price to format.
   * @returns {string} The formatted price string (e.g., "$10.00").
   */
  const formatPrice = (price) => {
    // Ensure price is a number and handle potential NaN
    const numericPrice = parseFloat(price)
    if (isNaN(numericPrice)) {
      return `${generalSettings.value.currencySymbol}0.00` // Default to 0 if not a number
    }
    // Simple fixed 2 decimal places. You can enhance this with Intl.NumberFormat for locale-specific formatting.
    return `${generalSettings.value.currencySymbol}${numericPrice.toFixed(2)}`
  }

  /**
   * Formats a time string (HH:mm or full datetime) based on the global time format setting.
   * @param {string} time - The time string to format (e.g., "14:30" or "2023-10-27T14:30:00").
   * @param {object} [options={}] - Additional options.
   * @param {string} [options.date] - A date string to combine with a time-only string.
   * @param {boolean} [options.includeTimezone] - Whether to include the timezone.
   * @param {string} [options.timeFormat] - Override the global time format ('12h' or '24h').
   * @returns {string} The formatted time string (e.g., "2:30 PM" or "14:30").
   */
  const formatTime = (time, options = {}) => {
    const {
      date,
      includeTimezone,
      timeFormat = generalSettings.value.timeFormat
    } = options

    if (!time) return ''

    let dateObj
    // Check if the time string is a full datetime string or just HH:mm
    if (
      time.includes('T') ||
      time.includes(' ') ||
      (time.includes('-') && time.includes(':'))
    ) {
      dateObj = new Date(time)
    } else {
      // Assumes 'HH:mm'
      const d = date ? new Date(date) : new Date()
      const parts = time.split(':')
      if (parts.length >= 2) {
        const hours = parseInt(parts[0], 10)
        const minutes = parseInt(parts[1], 10)
        d.setHours(hours, minutes, 0, 0)
        dateObj = d
      } else {
        return '' // Invalid time format
      }
    }

    if (isNaN(dateObj.getTime())) {
      return '' // Return empty for invalid date/time
    }

    const displayOptions = {
      hour: 'numeric',
      minute: '2-digit',
      hour12: timeFormat === '12h'
    }

    if (
      includeTimezone &&
      generalSettings.value.displayTimezone &&
      generalSettings.value.businessTimezone
    ) {
      try {
        // Check if timezone is valid before using it
        new Intl.DateTimeFormat(undefined, {
          timeZone: generalSettings.value.businessTimezone
        })
        displayOptions.timeZone = generalSettings.value.businessTimezone
        displayOptions.timeZoneName = 'short'
      } catch (e) {
        console.warn(
          `Invalid timezone specified in settings: ${generalSettings.value.businessTimezone}`
        )
      }
    }

    try {
      return dateObj.toLocaleTimeString([], displayOptions)
    } catch (e) {
      console.error('Error formatting time:', e)
      // Fallback for other errors
      delete displayOptions.timeZone
      delete displayOptions.timeZoneName
      return dateObj.toLocaleTimeString([], displayOptions)
    }
  }

  const formatDate = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    if (isNaN(date.getTime())) return ''
    const options = { year: 'numeric', month: 'long', day: 'numeric' }
    return date.toLocaleDateString(undefined, options)
  }

  return {
    // Expose generalSettings as readonly to prevent direct modification from components
    generalSettings: readonly(generalSettings),
    allCurrencies: readonly(allCurrencies), // Expose the full list for the settings page dropdown
    formatPrice,
    formatTime,
    formatDate,
    // Provide a way to re-fetch if needed (e.g., after saving settings)
    fetchGlobalSettings
  }
}
