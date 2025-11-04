import { reactive, ref, watch, onMounted, onUnmounted, computed } from 'vue'

function deepMerge(target, source) {
  const output = { ...target }
  if (
    target &&
    typeof target === 'object' &&
    source &&
    typeof source === 'object'
  ) {
    Object.keys(source).forEach((key) => {
      if (
        source[key] &&
        typeof source[key] === 'object' &&
        !Array.isArray(source[key])
      ) {
        if (!(key in target)) {
          Object.assign(output, { [key]: source[key] })
        } else {
          output[key] = deepMerge(target[key], source[key])
        }
      } else {
        Object.assign(output, { [key]: source[key] })
      }
    })
  }
  return output
}

export function useAppearanceSettings() {
  const defaultSettings = {
    colors: {
      primary: '#081a30',
      background: '#f8fafc',
      textColor: '#1a202c',
      headerText: '#ffffff'
    },
    theme: {
      darkMode: false,
      roundedCorners: true,
      shadows: true,
      showHeader: true
    },
    forms: {
      serviceForm: {
        displayStaffNames: true
      }
    },
    services: {
      showServiceImages: true,
      showCategoryDescription: false,
      showServiceDescription: true,
      showServicePreview: true
    },
    staff: {
      showStaffInfo: true,
      allowAnyEmployee: true
    },
    calendar: {
      showCalendar: true,
      layoutStyle: 'default',
      showBlockedTimeslots: false,
      showOnlyNearestTimeslot: false
    },
    customer: {
      showNotesField: false,
      showFirstNameField: true,
      showLastNameField: true,
      showEmailField: true,
      showPhoneField: true
    },
    payment: {
      showPaymentStep: true,
      allowCashPayment: true,
      allowCardPayment: false,
      showPriceBreakdown: true,
      showTaxes: false,
      showPaymentMethodDescription: true
    },
    confirmation: {
      showSummaryStep: true,
      showServiceImage: true,
      showStaffInfo: true,
      allowEditing: false,
      showBookAgainButton: true,
      showConfirmationDetails: true
    },
    layout: {
      formWidth: '500px',
      inputSize: 'medium',
      fontSize: 'small',
      borderRadius: 'medium',
      shadowStrength: 'medium',
      fontFamily: 'Inter, sans-serif'
    },
    labels: {
      book_appointment: 'Book Your Appointment',
      booking_steps_description: 'Complete your booking in these simple steps.',
      service_form_title: 'Book Your Service',
      step_1_title: 'Service',
      step_1_subtitle: 'Choose your service',
      step_2_title: 'Time Slot',
      step_2_subtitle: 'Pick date & time',
      step_3_title: 'Details',
      step_3_subtitle: 'Your informations',
      step_4_title: 'Payment',
      step_4_subtitle: 'Payment method',
      step_5_title: 'Confirm',
      step_5_subtitle: 'Review & confirm',
      category: 'Category',
      choose_category: 'Choose Category',
      about_this_category: 'About This Category',
      service: 'Service',
      choose_service: 'Choose Service',
      employee: 'Employee',
      any_employee: 'Any Employee',
      duration_label: 'Duration',
      minutes_suffix: 'min',
      price_label: 'Price',
      service_description_title: 'Service Description',
      service_preview_title: 'Service Preview',
      service_image_placeholder: 'Service image preview',
      select_date: 'Select Date',
      available_times_title: 'Available Times',
      select_date_time_description: 'Select your preferred date and time',
      no_appointments_available: 'No available appointments',
      check_back_later: 'Please check back later.',
      select_a_date: 'Select a date',
      choose_date_to_see_times: 'Choose a date to see available times',
      loading_available_times: 'Loading available times...',
      no_available_times: 'No available times',
      select_different_date: 'Please select a different date',
      selected_time_prefix: 'Selected:',
      your_information: 'Your Informations',
      first_name: 'First Name',
      enter_first_name_placeholder: 'Enter your first name',
      last_name: 'Last Name',
      enter_last_name_placeholder: 'Enter your last name',
      email: 'Email Address',
      enter_email_placeholder: 'Enter your email address',
      phone: 'Phone Number',
      enter_phone_placeholder: 'Enter your phone number',
      notes_label: 'Notes',
      notes_placeholder: 'Any special requests or notes?',
      number_of_persons: 'Number of People',
      payment_method: 'Payment Method',
      pay_with_cash: 'Pay with Cash',
      pay_at_appointment_description: 'Pay at your appointment',
      total_amount: 'Total Amount',
      confirm_appointment: 'Confirm Your Appointment',
      date_time: 'Date & Time',
      employee_confirmation: 'Employee',
      number_of_persons_confirmation: 'Number of People',
      duration_confirmation: 'Duration',
      minutes_suffix_confirmation: 'minutes',
      total_price_confirmation: 'Total Price',
      edit_details_button: 'Edit Details',
      previous: 'Previous',
      continue: 'Continue',
      confirm: 'Confirm Appointment',
      confirming: 'Confirming...',
      book_again: 'Book Another Appointment'
    },
    customCss: ''
  }

  const preloadedSettings = window.schedulaFrontendData?.settings || {}
  const settings = reactive(deepMerge(defaultSettings, preloadedSettings))

  const isLoading = ref(true)
  const isReady = ref(false)

  const getInputPadding = (size) => {
    switch (size) {
      case 'small':
        return '8px 12px'
      case 'medium':
        return '10px 16px'
      case 'large':
        return '12px 20px'
      default:
        return '10px 16px'
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

  const cssVariables = reactive({
    '--primary-color': settings.colors.primary,
    '--background-color': settings.colors.background,
    '--text-color': settings.colors.textColor,
    '--header-background-color': settings.colors.primary,
    '--header-text-color': settings.colors.headerText,
    '--border-radius-form': settings.theme.roundedCorners
      ? getBorderRadius(settings.layout.borderRadius)
      : '0',
    '--shadow-form': settings.theme.shadows
      ? getShadow(settings.layout.shadowStrength)
      : 'none',
    '--font-family-form': settings.layout.fontFamily,
    '--base-font-size-form': getFontSize(settings.layout.fontSize),
    '--input-padding': getInputPadding(settings.layout.inputSize),
    '--button-padding': getInputPadding(settings.layout.inputSize)
  })

  const error = ref(null)
  const previewStep = ref(1)

  const applyCSSVariables = () => {
    Object.keys(cssVariables).forEach((key) => {
      document.documentElement.style.setProperty(key, cssVariables[key])
    })

    // Mark as ready after applying variables
    if (!isReady.value) {
      // Small delay to ensure styles are painted
      setTimeout(() => {
        isReady.value = true
        isLoading.value = false
      }, 50)
    }
  }

  const handleMessage = (event) => {
    if (event.origin !== window.location.origin) return

    if (
      event.data &&
      typeof event.data === 'object' &&
      event.data.type === 'schesab_preview_update'
    ) {
      try {
        Object.assign(settings, deepMerge(settings, event.data.settings))
        if (event.data.previewStep) {
          previewStep.value = event.data.previewStep
        }
      } catch (err) {
        error.value = 'Invalid settings or preview step received from preview'
        console.error('Error processing message from parent:', err)
      }
    }
  }

  watch(
    settings,
    () => {
      cssVariables['--primary-color'] = settings.colors.primary
      cssVariables['--background-color'] = settings.colors.background
      cssVariables['--text-color'] = settings.colors.textColor
      cssVariables['--header-background-color'] = settings.colors.primary
      cssVariables['--header-text-color'] = settings.colors.headerText
      cssVariables['--border-radius-form'] = settings.theme.roundedCorners
        ? getBorderRadius(settings.layout.borderRadius)
        : '0'
      cssVariables['--shadow-form'] = settings.theme.shadows
        ? getShadow(settings.layout.shadowStrength)
        : 'none'
      cssVariables['--font-family-form'] = settings.layout.fontFamily
      cssVariables['--base-font-size-form'] = getFontSize(
        settings.layout.fontSize
      )
      cssVariables['--input-padding'] = getInputPadding(
        settings.layout.inputSize
      )
      cssVariables['--button-padding'] = getInputPadding(
        settings.layout.inputSize
      )

      applyCSSVariables()
    },
    { deep: true, immediate: true }
  )

  onMounted(() => {
    window.addEventListener('message', handleMessage)

    // Apply variables immediately
    applyCSSVariables()

    // If settings are already loaded from PHP, mark as ready quickly
    if (window.schedulaFrontendData?.settings) {
      setTimeout(() => {
        isReady.value = true
        isLoading.value = false
      }, 10)
    }
  })

  onUnmounted(() => {
    window.removeEventListener('message', handleMessage)
  })

  const getFormElementStyles = (element) => {
    const baseStyles = {
      borderRadius: settings.theme.roundedCorners
        ? getBorderRadius(settings.layout.borderRadius)
        : '0',
      boxShadow: settings.theme.shadows
        ? getShadow(settings.layout.shadowStrength)
        : 'none',
      transition: 'all 0.2s ease-in-out',
      fontFamily: settings.layout.fontFamily,
      fontSize: getFontSize(settings.layout.fontSize)
    }

    switch (element) {
      case 'label':
        return {
          ...baseStyles,
          color: settings.colors.textColor,
          fontWeight: '500'
        }
      case 'text':
        return {
          ...baseStyles,
          color: settings.colors.textColor
        }
      case 'input':
      case 'select':
      case 'textarea':
        return {
          ...baseStyles,
          border: `1px solid ${
            settings.theme.darkMode ? '#4b5563' : '#d1d5db'
          }`,
          backgroundColor: settings.theme.darkMode
            ? '#1f2937'
            : settings.colors.background,
          color: settings.theme.darkMode
            ? '#e5e7eb'
            : settings.colors.textColor,
          padding: getInputPadding(settings.layout.inputSize)
        }
      case 'button-primary':
        return {
          ...baseStyles,
          backgroundColor: settings.colors.primary,
          color: settings.colors.headerText,
          padding: getInputPadding(settings.layout.inputSize)
        }
      case 'button-secondary':
        return {
          ...baseStyles,
          backgroundColor: settings.colors.background,
          color: settings.colors.textColor,
          border: `1px solid ${settings.colors.textColor}33`,
          padding: getInputPadding(settings.layout.inputSize)
        }
      case 'card':
        return {
          ...baseStyles,
          backgroundColor: settings.theme.darkMode
            ? '#1f2937'
            : settings.colors.background,
          border: `1px solid ${settings.theme.darkMode ? '#4b5563' : '#e5e7eb'}`
        }
      case 'checkbox':
      case 'radio':
        return {
          ...baseStyles,
          accentColor: settings.colors.primary
        }
      default:
        return baseStyles
    }
  }

  return {
    settings,
    cssVariables,
    error,
    previewStep,
    isLoading,
    isReady,
    getFormElementStyles
  }
}
