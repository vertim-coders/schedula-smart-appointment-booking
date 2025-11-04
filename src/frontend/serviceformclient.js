import { createApp } from 'vue'
import ServiceFormClient from '@/frontend/components/reservation/ServiceFormClient.vue'
import VueTelInput from 'vue-tel-input'
import 'vue-tel-input/vue-tel-input.css'
import { __ } from '@wordpress/i18n'

import '@/frontend/utils/cdn-tailwindcss.js'

tailwind.config = {
  prefix: 'schedula-',
  corePlugins: {
    preflight: false
  },
  content: ['./**/*.{js,vue}']
}

console.log(
  __(
    'Schedula Service Form Client script loaded',
    'schedula-appointment-booking'
  )
)

// Configure VueTelInput
const globalOptions = {
  mode: 'international',
  dropdownOptions: {
    showSearchBox: true
  }
}

document.addEventListener('DOMContentLoaded', () => {
  console.log(
    __(
      'DOM Content Loaded. Starting Schedula Service Form Client initialization.',
      'schedula-appointment-booking'
    )
  )

  const elements = document.querySelectorAll('div[id^="schesab-service-form-"]')

  if (elements.length === 0) {
    console.warn(
      __(
        'No DIV elements found for Schedula Service Form Client. Check shortcode ID or DOM.',
        'schedula-appointment-booking'
      )
    )
  } else {
    console.log(
      __(
        'Found %d form DIV elements',
        'schedula-smart-appointment-booking'
      ).replace('%d', elements.length)
    )
  }

  elements.forEach((element, index) => {
    console.log(
      __(
        'Processing element %d:',
        'schedula-smart-appointment-booking'
      ).replace('%d', index + 1),
      element.id
    )

    try {
      let formData = {}
      if (element.dataset.formData) {
        try {
          formData = JSON.parse(element.dataset.formData)
          console.log(
            __(
              'Parsed formData from element:',
              'schedula-smart-appointment-booking'
            ),
            formData
          )
        } catch (e) {
          console.error(
            __(
              'Failed to parse formData from element.dataset.formData:',
              'schedula-appointment-booking'
            ),
            e
          )
          console.log(
            __('Raw formData:', 'schedula-smart-appointment-booking'),
            element.dataset.formData
          )
        }
      } else {
        console.log(
          __(
            'element.dataset.formData is empty or undefined for element:',
            'schedula-appointment-booking'
          ),
          element.id
        )
      }

      const serviceId = element.dataset.serviceId
        ? Number(element.dataset.serviceId)
        : null
      const categoryId = element.dataset.categoryId
        ? Number(element.dataset.categoryId)
        : null
      const staffId = element.dataset.staffId
        ? Number(element.dataset.staffId)
        : null

      console.log(
        __(
          'Creating Vue app with props:',
          'schedula-smart-appointment-booking'
        ),
        {
          serviceId: serviceId,
          categoryId: categoryId,
          staffId: staffId,
          previewSettings: formData,
          hasFormData: !!element.dataset.formData
        }
      )

      const app = createApp(ServiceFormClient, {
        serviceId: serviceId,
        categoryId: categoryId,
        staffId: staffId,
        previewSettings: formData
      })

      app.use(VueTelInput, globalOptions)

      app.config.errorHandler = (err, vm, info) => {
        console.error(
          __('Vue error:', 'schedula-smart-appointment-booking'),
          err
        )
        console.error(
          __('Error in component:', 'schedula-smart-appointment-booking'),
          vm
        )
        console.error(
          __('Error info:', 'schedula-smart-appointment-booking'),
          info
        )
      }

      app.mount(element)
      console.log(
        __(
          'Vue app mounted successfully to:',
          'schedula-smart-appointment-booking'
        ),
        element.id
      )
    } catch (error) {
      console.error(
        __(
          'Error mounting Vue app to %s:',
          'schedula-appointment-booking'
        ).replace('%s', element.id),
        error
      )

      const errorDiv = document.createElement('div')
      errorDiv.style.padding = '10px'
      errorDiv.style.background = '#ffebee'
      errorDiv.style.border = '1px solid #ef9a9a'
      errorDiv.style.color = '#c62828'
      errorDiv.style.margin = '10px 0'
      errorDiv.style.borderRadius = '4px'
      errorDiv.innerHTML = `
                <strong>${__(
                  'Error loading booking form:',
                  'schedula-appointment-booking'
                )}</strong><br>
                ${
                  error.message ||
                  __('Unknown error', 'schedula-smart-appointment-booking')
                }<br>
                <small>${__(
                  'Check browser console for details. (ID: %s)',
                  'schedula-appointment-booking'
                ).replace('%s', element.id)}</small>
            `
      element.appendChild(errorDiv)
    }
  })
})
