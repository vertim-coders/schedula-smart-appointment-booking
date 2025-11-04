import axios from 'axios'

// Check for data in both admin (schedulaData) and frontend (schedulaFrontendData) contexts
const schedulaData =
  window.schedulaData ||
  window.schedulaFrontendData ||
  window.schedulaServiceFormData ||
  {}

// Validate that we have the required configuration
if (!schedulaData.apiUrl || !schedulaData.nonce) {
  console.warn(
    'Schedula: API configuration is incomplete. Make sure schedulaData or schedulaFrontendData is properly localized.'
  )
}

// Create an Axios instance with default configuration
const apiClient = axios.create({
  baseURL: schedulaData.apiUrl || '',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': schedulaData.nonce || ''
  },
  responseType: 'json' // Default to json, can be overridden for specific requests like blob for files
})

// Add response interceptor for better error handling
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response) {
      // The request was made and the server responded with a status code
      // that falls out of the range of 2xx
      console.error('Schedula API Error:', {
        status: error.response.status,
        statusText: error.response.statusText,
        url: error.config.url,
        method: error.config.method,
        data: error.response.data
      })
    } else if (error.request) {
      // The request was made but no response was received
      console.error('Schedula API Error: No response received', error.request)
    } else {
      // Something happened in setting up the request that triggered an Error
      console.error('Schedula API Error:', error.message)
    }
    return Promise.reject(error)
  }
)

// --- API Functions for Customers ---
const customersApi = {
  /**
   * Fetches all customers, with optional search and pagination.
   * @param {Object} [params={}] - Object containing filter parameters (search, page, per_page).
   * @returns {Promise<Object>} A promise that resolves to an object like { clients: [], total_items: X }.
   */
  getCustomers: (params = {}) => {
    return apiClient.get('/customers', { params })
  },

  /**
   * Fetches a single customer by ID.
   * @param {number} id - The ID of the customer.
   * @returns {Promise<Object>} A promise that resolves to a customer object.
   */
  getCustomer: (id) => {
    return apiClient.get(`/customers/${id}`)
  },

  /**
   * Creates a new customer.
   * @param {Object} customerData - The data for the new customer.
   * @returns {Promise<Object>} A promise that resolves to the created customer object.
   */
  createCustomer: (customerData) => {
    return apiClient.post('/customers', customerData)
  },

  /**
   * Updates an existing customer.
   * @param {number} id - The ID of the customer to update.
   * @param {Object} customerData - The updated data for the customer.
   * @returns {Promise<Object>} A promise that resolves to the updated customer object.
   */
  updateCustomer: (id, customerData) => {
    return apiClient.put(`/customers/${id}`, customerData)
  },

  /**
   * Deletes a customer.
   * @param {number} id - The ID of the customer to delete.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  deleteCustomer: (id) => {
    return apiClient.delete(`/customers/${id}`)
  },

  /**
   * Checks if a customer has associated appointments.
   * @param {number} id - The ID of the customer.
   * @returns {Promise<Object>} A promise that resolves to an object like { has_appointments: boolean }.
   */
  customerHasAppointments: (id) => {
    return apiClient.get(`/customers/${id}/has-appointments`)
  },

  /**
   * Gets statistics for a single customer.
   * @param {number} id - The ID of the customer.
   * @returns {Promise<Object>} A promise that resolves to an object like { appointments_count: number, total_paid: string }.
   */
  getClientStats: (id) => {
    return apiClient.get(`/customers/${id}/stats`)
  }
}

// --- API Functions for Appointments ---
const appointmentsApi = {
  /**
   * Fetches dashboard summary data.
   * @param {string} [startDate] - Optional start date (YYYY-MM-DD).
   * @param {string} [endDate] - Optional end date (YYYY-MM-DD).
   * @returns {Promise<Object>} A promise that resolves to the dashboard summary data.
   */
  getDashboardSummary: (startDate = null, endDate = null) => {
    const params = {}
    if (startDate) params.start_date = startDate
    if (endDate) params.end_date = endDate
    return apiClient.get('/dashboard-summary', { params })
  },

  /**
   * Fetches the next upcoming appointment.
   * @returns {Promise<Object>} A promise that resolves to the next appointment object or null.
   */
  getNextUpcomingAppointment: () => {
    return apiClient.get('/dashboard/next-appointment')
  },

  /**
   * Fetches appointment load analysis (e.g., busiest days).
   * @returns {Promise<Object>} A promise that resolves to an object with busiest_days (an array).
   */
  getAppointmentLoadAnalysis: () => {
    return apiClient.get('/dashboard/appointment-load-analysis')
  },

  /**
   * Fetches all appointments, with optional filters.
   * @param {Object} filters - Object containing filter parameters (start_date, end_date, status, customer_name, staff_name, page, per_page).
   * @returns {Promise<Object>} A promise that resolves to an object like { appointments: [], total_items: X }.
   */
  getAppointments: (filters = {}) => {
    return apiClient.get('/appointments', { params: filters })
  },

  /**
   * Fetches a single appointment by ID.
   * @param {number} id - The ID of the appointment.
   * @returns {Promise<Object>} A promise that resolves to an appointment object.
   */
  getAppointment: (id) => {
    return apiClient.get(`/appointments/${id}`)
  },

  /**
   * Creates a new appointment.
   * @param {Object} appointmentData - The data for the new appointment.
   * @returns {Promise<Object>} A promise that resolves to the created appointment object.
   */
  createAppointment: (appointmentData) => {
    return apiClient.post('/appointments', appointmentData)
  },

  /**
   * Updates an existing appointment.
   * @param {number} id - The ID of the appointment to update.
   * @param {Object} appointmentData - The updated data for the appointment.
   * @returns {Promise<Object>} A promise that resolves to the updated appointment object.
   * Note: The backend expects appointment_date and appointment_time as separate fields.
   */
  updateAppointment: (id, appointmentData) => {
    return apiClient.put(`/appointments/${id}`, appointmentData)
  },

  /**
   * Deletes an appointment.
   * @param {number} id - The ID of the appointment to delete.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  deleteAppointment: (id) => {
    return apiClient.delete(`/appointments/${id}`)
  },

  /**
   * Checks staff availability for a given time slot.
   * @param {number} staffId - The ID of the staff member.
   * @param {string} appointmentDate - The date of the appointment (YYYY-MM-DD).
   * @param {string} appointmentTime - The time of the appointment (HH:MM).
   * @param {number} duration - The duration of the appointment in minutes.
   * @param {number} [excludeAppointmentId=0] - Optional. ID of the current appointment to exclude from conflict checks (for updates).
   * @returns {Promise<Object>} A promise that resolves to an object like { available: boolean, message: string }.
   */
  checkStaffAvailability: (
    staffId,
    appointmentDate,
    appointmentTime,
    duration,
    excludeAppointmentId = 0
  ) => {
    // Matches PHP route: /staff-availability
    return apiClient.get('/staff-availability', {
      params: {
        staff_id: staffId,
        appointment_date: appointmentDate,
        appointment_time: appointmentTime,
        duration: duration,
        exclude_appointment_id: excludeAppointmentId
      }
    })
  },

  /**
   * Fetches available time slots for a given service, date, and optional staff.
   * @param {Object} params - Object containing service_id, date, and optional staff_id.
   * @returns {Promise<Array>} A promise that resolves to an array of available time slot strings (e.g., ["09:00", "09:30"]).
   */
  getAvailableTimeSlots: (params) => {
    return apiClient.get('/available-time-slots', { params })
  },

  /**
   * Fetches staff members for a specific service.
   * @param {number} service_id - The ID of the service.
   * @returns {Promise<Array>} Array of staff members.
   */
  getStaffForService: (service_id) => {
    return apiClient.get(`/services/${service_id}/staff`)
  },

  /**
   * Fetches all data required for the booking form in a single request.
   * @returns {Promise<Object>} A promise that resolves to an object containing categories, services, and staff.
   */
  getBookingFormData: () => {
    return apiClient.get('/booking-form-data')
  },

  /**
   * Checks for conflicts in a recurring appointment series.
   * @param {Object} data - The recurrence data to check.
   * @returns {Promise<Object>} A promise that resolves to an object with conflicting_dates.
   */
  checkRecurrenceConflicts: (data) => {
    return apiClient.post('/appointments/check-recurrence-conflicts', data)
  },

  getCustomerByEmail: (email) => {
    return apiClient.get('/customer-by-email', { params: { email } })
  }
}

// --- API Functions for Services & Categories ---
const servicesCategoriesApi = {
  /**
   * Fetches all services, with optional search and pagination.
   * @param {Object} [params={}] - Optional parameters, e.g., { search: 'term', page: 1, per_page: 10 }.
   * @returns {Promise<Object>} A promise that resolves to either an array of services (legacy) or an object like { services: [], total_items: X } (with pagination).
   */
  getServices: (params = {}) => {
    return apiClient.get('/services', { params })
  },

  /**
   * Fetches a single service by ID.
   * @param {number} id - The ID of the service.
   * @returns {Promise<Object>} A promise that resolves to a service object.
   */
  getService: (id) => {
    return apiClient.get(`/services/${id}`)
  },

  /**
   * Creates a new service.
   * @param {Object} serviceData - The data for the new service.
   * @returns {Promise<Object>} A promise that resolves to the created service object.
   */
  createService: (serviceData) => {
    return apiClient.post('/services', serviceData)
  },

  /**
   * Updates an existing service.
   * @param {number} id - The ID of the service to update.
   * @param {Object} serviceData - The updated data for the service.
   * @returns {Promise<Object>} A promise that resolves to the updated service object.
   */
  updateService: (id, serviceData) => {
    return apiClient.put(`/services/${id}`, serviceData)
  },

  /**
   * Deletes a service.
   * @param {number} id - The ID of the service to delete.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  deleteService: (id) => {
    return apiClient.delete(`/services/${id}`)
  },

  /**
   * Fetches all categories.
   * @returns {Promise<Array>} A promise that resolves to an array of category objects.
   */
  getCategories: (params = {}) => {
    return apiClient.get('/categories', { params })
  },

  /**
   * Fetches a single category by ID.
   * @param {number} id - The ID of the category.
   * @returns {Promise<Object>} A promise that resolves to a category object.
   */
  getCategory: (id) => {
    return apiClient.get(`/categories/${id}`)
  },

  /**
   * Creates a new category.
   * @param {Object} categoryData - The data for the new category.
   * @returns {Promise<Object>} A promise that resolves to the created category object.
   */
  createCategory: (categoryData) => {
    return apiClient.post('/categories', categoryData)
  },

  /**
   * Updates an existing category.
   * @param {number} id - The ID of the category to update.
   * @param {Object} categoryData - The updated data for the category.
   * @returns {Promise<Object>} A promise that resolves to the updated category object.
   */
  updateCategory: (id, categoryData) => {
    return apiClient.put(`/categories/${id}`, categoryData)
  },

  /**
   * Deletes a category.
   * @param {number} id - The ID of the category to delete.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  deleteCategory: (id) => {
    return apiClient.delete(`/categories/${id}`)
  }
}

// --- API Functions for Staff ---
const staffApi = {
  /**
   * Fetches all staff members, with optional search and pagination.
   * @param {Object} [params={}] - Object containing filter parameters (search, page, per_page, status).
   * @returns {Promise<Object>} A promise that resolves to an object like { staff: [], total_items: X }.
   */
  getStaffMembers: (params = {}) => {
    return apiClient.get('/staff', { params })
  },

  /**
   * Fetches a single staff member by ID.
   * @param {number} id - The ID of the staff member.
   * @returns {Promise<Object>} A promise that resolves to a staff member object.
   */
  getStaffMember: (id) => {
    return apiClient.get(`/staff/${id}`)
  },

  /**
   * Creates a new staff member.
   * @param {Object} staffData - The data for the new staff member.
   * @returns {Promise<Object>} A promise that resolves to the created staff member object.
   */
  createStaffMember: (staffData) => {
    return apiClient.post('/staff', staffData)
  },

  /**
   * Updates an existing staff member.
   * @param {number} id - The ID of the staff member to update.
   * @param {Object} staffData - The updated data for the staff member.
   * @returns {Promise<Object>} A promise that resolves to the updated staff member object.
   */
  updateStaffMember: (id, staffData) => {
    return apiClient.put(`/staff/${id}`, staffData)
  },

  /**
   * Deletes a staff member.
   * @param {number} id - The ID of the staff member to delete.
   * @param {Object} [params={}] - Optional parameters, e.g., { force: true }.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  deleteStaffMember: (id, params = {}) => {
    return apiClient.delete(`/staff/${id}`, { params })
  },

  /**
   * Fetches services associated with a staff member.
   * @param {number} staffId - The ID of the staff member.
   * @returns {Promise<Array>} A promise that resolves to an array of staff service objects.
   */
  getStaffServices: (staffId) => {
    return apiClient.get(`/staff/${staffId}/services`)
  },

  /**
   * Adds a service to a staff member.
   * @param {number} staffId - The ID of the staff member.
   * @param {Object} serviceData - The service data (service_id, price, duration).
   * @returns {Promise<Object>} A promise that resolves to the created service entry.
   */
  addStaffService: (staffId, serviceData) => {
    return apiClient.post(`/staff/${staffId}/services`, serviceData)
  },

  /**
   * Updates a staff service entry.
   * @param {number} staffId - The ID of the staff member.
   * @param {number} serviceId - The ID of the service.
   * @param {Object} updateData - The data to update (price, duration).
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  updateStaffService: (staffId, serviceId, updateData) => {
    return apiClient.put(`/staff/${staffId}/services/${serviceId}`, updateData)
  },

  /**
   * Deletes a staff service entry.
   * @param {number} staffId - The ID of the staff member.
   * @param {number} serviceId - The ID of the service.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  deleteStaffService: (staffId, serviceId) => {
    return apiClient.delete(`/staff/${staffId}/services/${serviceId}`)
  },

  /**
   * Fetches the schedule for a staff member.
   * @param {number} staffId - The ID of the staff member.
   * @returns {Promise<Array>} A promise that resolves to an array of schedule entries.
   */
  getStaffSchedule: (staffId) => {
    return apiClient.get(`/staff/${staffId}/schedule`)
  },

  /**
   * Adds a schedule entry for a staff member.
   * @param {number} staffId - The ID of the staff member.
   * @param {Object} scheduleData - The schedule data (day_of_week, start_time, end_time).
   * @returns {Promise<Object>} A promise that resolves to the created schedule entry.
   */
  addStaffSchedule: (staffId, scheduleData) => {
    return apiClient.post(`/staff/${staffId}/schedule`, scheduleData)
  },

  /**
   * Updates a staff schedule entry.
   * @param {number} staffId - The ID of the staff member.
   * @param {number} scheduleId - The ID of the schedule entry.
   * @param {Object} updateData - The data to update.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  updateStaffSchedule: (staffId, scheduleId, updateData) => {
    return apiClient.put(`/staff/${staffId}/schedule/${scheduleId}`, updateData)
  },

  /**
   * Deletes a staff schedule entry.
   * @param {number} staffId - The ID of the staff member.
   * @param {number} scheduleId - The ID of the schedule entry.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  deleteStaffSchedule: (staffId, scheduleId) => {
    return apiClient.delete(`/staff/${staffId}/schedule/${scheduleId}`)
  },

  /**
   * Fetches breaks for a specific schedule item.
   * @param {number} scheduleItemId - The ID of the schedule item.
   * @returns {Promise<Array>} A promise that resolves to an array of break entries.
   */
  getScheduleItemBreaks: (scheduleItemId) => {
    return apiClient.get(`/schedule-items/${scheduleItemId}/breaks`)
  },

  /**
   * Adds a break to a schedule item.
   * @param {number} scheduleItemId - The ID of the schedule item.
   * @param {Object} breakData - The break data (start_time, end_time, description).
   * @returns {Promise<Object>} A promise that resolves to the created break entry.
   */
  addScheduleItemBreak: (scheduleItemId, breakData) => {
    return apiClient.post(`/schedule-items/${scheduleItemId}/breaks`, breakData)
  },

  /**
   * Updates a schedule item break.
   * @param {number} scheduleItemId - The ID of the schedule item.
   * @param {number} breakId - The ID of the break entry.
   * @param {Object} updateData - The data to update.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  updateScheduleItemBreak: (scheduleItemId, breakId, updateData) => {
    return apiClient.put(
      `/schedule-items/${scheduleItemId}/breaks/${breakId}`,
      updateData
    )
  },

  /**
   * Deletes a schedule item break.
   * @param {number} scheduleItemId - The ID of the schedule item.
   * @param {number} breakId - The ID of the break entry.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  deleteScheduleItemBreak: (scheduleItemId, breakId) => {
    return apiClient.delete(
      `/schedule-items/${scheduleItemId}/breaks/${breakId}`
    )
  },

  /**
   * Fetches all holidays, with optional filters.
   * @param {Object} filters - Object containing filter parameters (staff_id, start_date, end_date).
   * @returns {Promise<Array>} A promise that resolves to an array of holiday objects.
   */
  getHolidays: (filters = {}) => {
    return apiClient.get('/holidays', { params: filters })
  },

  /**
   * Fetches a single holiday by ID.
   * @param {number} id - The ID of the holiday.
   * @returns {Promise<Object>} A promise that resolves to a holiday object.
   */
  getHoliday: (id) => {
    return apiClient.get(`/holidays/${id}`)
  },

  /**
   * Creates a new holiday.
   * @param {Object} holidayData - The data for the new holiday.
   * @returns {Promise<Object>} A promise that resolves to the created holiday object.
   */
  createHoliday: (holidayData) => {
    return apiClient.post('/holidays', holidayData)
  },

  /**
   * Updates an existing holiday.
   * @param {number} id - The ID of the holiday to update.
   * @param {Object} holidayData - The updated data for the holiday.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  updateHoliday: (id, holidayData) => {
    return apiClient.put(`/holidays/${id}`, holidayData)
  },

  /**
   * Deletes a holiday.
   * @param {number} id - The ID of the holiday to delete.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  deleteHoliday: (id) => {
    return apiClient.delete(`/holidays/${id}`)
  },

  /**
   * Fetches days when a staff member is unavailable due to holidays or no schedule.
   * @param {number} staffId - The ID of the staff member (0 for any staff).
   * @param {string} startDate - The start date for the range (YYYY-MM-DD).
   * @param {string} endDate - The end date for the range (YYYY-MM-DD).
   * @returns {Promise<Array>} A promise that resolves to an array of unavailable date strings (YYYY-MM-DD).
   */
  getStaffUnavailableDays: (staffId, startDate, endDate) => {
    return apiClient.get(`/staff/${staffId}/unavailable-days`, {
      params: {
        staff_id: staffId,
        start_date: startDate,
        end_date: endDate
      }
    })
  },

  /**
   * Fetches working hours for a staff member on a specific date.
   * @param {number} staffId - The ID of the staff member.
   * @param {string} date - The date to check (YYYY-MM-DD).
   * @returns {Promise<Array>} A promise that resolves to an array of working hour slots, e.g., [{ start_time: '09:00:00', end_time: '12:00:00' }].
   */
  getStaffWorkingHours: (staffId, date) => {
    return apiClient.get(`/staff/${staffId}/working-hours`, {
      params: {
        date: date
      }
    })
  }
}

// --- API Functions for Payments ---
const paymentsApi = {
  /**
   * Fetches all payments, with optional filters.
   * @param {Object} filters - Object containing filter parameters (start_date, end_date, customer_name, staff_name, service_title, payment_type).
   * @returns {Promise<Array>} A promise that resolves to an array of payment objects.
   */
  getPayments: (filters = {}) => {
    return apiClient.get('/payments', { params: filters })
  },

  /**
   * Deletes a payment and its associated appointment.
   * @param {number} id - The ID of the payment to delete.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  deletePayment: (id) => {
    return apiClient.delete(`/payments/${id}`)
  },

  /**
   * Fetches detailed information for a payment receipt.
   * @param {number} id - The ID of the payment.
   * @returns {Promise<Object>} A promise that resolves to the receipt data.
   */
  getPaymentReceipt: (id) => {
    return apiClient.get(`/payments/${id}/receipt`)
  },

  /**
   * Creates a new payment record.
   * @param {Object} paymentData - The data for the new payment record.
   * @returns {Promise<Object>} A promise that resolves to the created payment object.
   */
  createPayment: (paymentData) => {
    return apiClient.post('/payments', paymentData)
  }
}

const newsletterApi = {
  /**
   * Subscribes an email to the newsletter.
   * @param {string} email - The email address to subscribe.
   * @returns {Promise<Object>} A promise that resolves with the API response data.
   */
  subscribe: (email) => {
    return apiClient.post('/subscribe-newsletter', { email: email })
  }
}

// --- API Functions for Forms ---
const formsApi = {
  /**
   * Fetches all forms.
   * @returns {Promise<Array>} A promise that resolves to an array of form objects.
   */
  getForms: () => {
    return apiClient.get('/forms')
  },

  /**
   * Fetches a single form by ID.
   * @param {number} id - The ID of the form.
   * @returns {Promise<Object>} A promise that resolves to a form object.
   */
  getForm: (id) => {
    return apiClient.get(`/forms/${id}`)
  },

  /**
   * Creates a new form.
   * @param {Object} formData - The data for the new form.
   * @returns {Promise<Object>} A promise that resolves to the created form object.
   */
  createForm: (formData) => {
    return apiClient.post('/forms', formData)
  },

  /**
   * Updates an existing form.
   * @param {number} id - The ID of the form to update.
   * @param {Object} formData - The updated data for the form.
   * @returns {Promise<Object>} A promise that resolves to the updated form object.
   */
  updateForm: (id, formData) => {
    return apiClient.put(`/forms/${id}`, formData)
  },

  /**
   * Deletes a form.
   * @param {number} id - The ID of the form to delete.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  deleteForm: (id) => {
    return apiClient.delete(`/forms/${id}`)
  }
}

// --- API Functions for General Settings AND Company Settings ---
const settingsApi = {
  /**
   * Fetches current general settings.
   * This now includes admin appearance settings from the backend.
   * @returns {Promise<Object>} A promise that resolves to the general settings object.
   */
  getGeneralSettings: () => {
    return apiClient.get('/general-settings')
  },

  /**
   * Saves general settings.
   * When you send the general settings, the admin appearance settings (like colors, dark mode toggle)
   * that are part of this object will also be saved.
   * @param {Object} settingsData - The general settings object to save.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  saveGeneralSettings: (settingsData) => {
    return apiClient.post('/general-settings', settingsData)
  },

  /**
   * Fetches current company settings.
   * @returns {Promise<Object>} A promise that resolves to the company settings object.
   */
  getCompanySettings: () => {
    return apiClient.get('/company-settings')
  },

  /**
   * Saves company settings.
   * @param {Object} settingsData - The company settings object to save.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  saveCompanySettings: (settingsData) => {
    return apiClient.post('/company-settings', settingsData)
  },

  setOnboardingComplete: () => {
    return apiClient.post('/onboarding/complete')
  }
}

// --- API Functions for Appearance Settings ---
const appearanceApi = {
  /**
   * Fetches current appearance settings.
   * @returns {Promise<Object>} A promise that resolves to the appearance settings object.
   */
  getSettings: () => {
    return apiClient.get('/appearance-settings')
  },

  /**
   * Saves appearance settings.
   * @param {Object} settings - The appearance settings object to save.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  saveSettings: (settings) => {
    return apiClient.post('/appearance-settings', settings)
  },

  /**
   * Resets appearance settings to defaults.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  resetSettings: () => {
    return apiClient.delete('/appearance-settings')
  },

  /**
   * Gets appearance settings for frontend (public endpoint).
   * @returns {Promise<Object>} A promise that resolves to the appearance settings object.
   */
  getPublicSettings: () => {
    return apiClient.get('/public/appearance-settings')
  },

  /**
   * Fetches Google Fonts from the backend API.
   * @param {string} [searchQuery=''] - Optional search term to filter fonts.
   * @returns {Promise<Object>} A promise that resolves to an object like { success: true, fonts: [] }.
   */
  getGoogleFonts: (searchQuery = '') => {
    return apiClient.get('/google-fonts', { params: { search: searchQuery } })
  }
}

// --- API for Notification Settings ---
const notificationsApi = {
  /**
   * Fetches current notification settings.
   * @returns {Promise<Object>} A promise that resolves to the notification settings object.
   */
  getSettings: () => {
    return apiClient.get('/notification-settings')
  },

  /**
   * Saves notification settings.
   * @param {Object} settingsData - The notification settings object to save.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  saveSettings: (settingsData) => {
    return apiClient.post('/notification-settings', settingsData)
  },

  /**
   * Resets notification settings to defaults.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  resetSettings: () => {
    return apiClient.delete('/notification-settings')
  },

  /**
   * Fetches the email log.
   * @param {Object} [params={}] - Optional parameters like search, pagination, and sorting.
   * @returns {Promise<Array>} A promise that resolves to an array of email log objects.
   */
  getEmailLog: (params = {}) => {
    return apiClient.get('/email-log', { params })
  },

  /**
   * Fetches a single email log entry by ID.
   * @param {number} id - The ID of the email log.
   * @returns {Promise<Object>} A promise that resolves to an email log object.
   */
  getSingleEmailLog: (id) => {
    return apiClient.get(`/email-log/${id}`)
  },

  /**
   * Deletes email log entries.
   * @param {Object} data - Object containing an array of IDs to delete, e.g., { ids: [1, 2, 3] }.
   * @returns {Promise<Object>} A promise that resolves to a success message.
   */
  deleteEmailLog: (data) => {
    return apiClient.delete('/email-log', { data })
  },

  sendTestEmail() {
    return apiClient.post('/notification-settings/test')
  }
}

// --- API for Analytics and Reporting ---
const analyticsApi = {
  /**
   * Exports data (appointments, clients, payments) based on type and date range.
   * @param {string} exportType - Type of data to export ('appointments', 'clients', 'payments').
   * @param {string} [startDate] - Optional start date (YYYY-MM-DD).
   * @param {string} [endDate] - Optional end date (YYYY-MM-DD).
   * @returns {Promise<Blob>} A promise that resolves to a Blob containing the CSV data.
   */
  exportData: (exportType, startDate = null, endDate = null) => {
    const params = {
      export_type: exportType
    }
    if (startDate) params.start_date = startDate
    if (endDate) params.end_date = endDate

    return apiClient.get('/export-data', { params, responseType: 'blob' }) // Important: responseType 'blob' for file download
  }
}

// --- API for fetching static utility data like timezones, industries, company sizes ---
const utilityApi = {
  /**
   * Fetches all static utility data (timezones, company sizes, industries, currencies).
   * @returns {Promise<Object>} A promise that resolves to an object containing lists of utility data.
   */
  getUtilityData: () => {
    return apiClient.get('/utility-data')
  }
}

//  API for Stripe Settings and Payments ---
const stripeApi = {
  /**
   * Fetches Stripe settings from the backend.
   * @returns {Promise<Object>} A promise that resolves to the Stripe settings object.
   */
  getStripeSettings: () => {
    return apiClient.get('/stripe-settings')
  },

  /**
   * Updates Stripe settings on the backend.
   * @param {Object} settingsData - The Stripe settings data to save.
   * @returns {Promise<Object>} A promise that resolves to the API response.
   */
  updateStripeSettings: (settingsData) => {
    return apiClient.post('/stripe-settings', settingsData)
  },

  /**
   * Initiates a Stripe Checkout Session.
   * @param {Object} orderDetails - Details for the Checkout Session (e.g., amount, currency, appointment_id, description).
   * @returns {Promise<Object>} A promise that resolves to the Checkout Session details including the checkout_url.
   */
  createCheckoutSession: (orderDetails) => {
    return apiClient.post('/stripe/create-checkout-session', orderDetails)
  },

  /**
   * Initiates a Stripe PaymentIntent.
   * @param {Object} intentDetails - Details for the PaymentIntent (e.g., amount, currency, appointment_id).
   * @returns {Promise<Object>} A promise that resolves to the PaymentIntent details including client_secret.
   */
  createStripePaymentIntent: (intentDetails) => {
    return apiClient.post('/stripe/create-payment-intent', intentDetails)
  },

  /**
   * Confirms a Stripe PaymentIntent (if needed server-side).
   * @param {string} paymentIntentId - The Stripe PaymentIntent ID.
   * @param {number} appointmentId - The ID of the associated appointment in your system.
   * @returns {Promise<Object>} A promise that resolves to the confirmed payment details.
   */
  confirmStripePaymentIntent: (paymentIntentId, appointmentId) => {
    return apiClient.post('/stripe/confirm-payment-intent', {
      payment_intent_id: paymentIntentId,
      appointment_id: appointmentId
    })
  },

  /**
   * Tests the provided Stripe API credentials.
   * @param {Object} credentials - Object containing publishableKey, secretKey, and sandboxMode.
   * @returns {Promise<Object>} A promise that resolves to a success or failure message.
   */
  testStripeCredentials: (credentials) => {
    return apiClient.post('/stripe/test-credentials', credentials)
  },

  /**
   * Verifies a Stripe payment asynchronously.
   * @param {string} checkoutSessionId - The Stripe Checkout Session ID to verify.
   * @returns {Promise<Object>} A promise that resolves to the verification result with success status and message.
   */
  verifyPayment: (checkoutSessionId) => {
    return apiClient.post('/stripe/verify-payment', {
      checkout_session_id: checkoutSessionId
    })
  }
}

// --- API Functions for Contact Form ---
const contactApi = {
  /**
   * Submits a contact form message.
   * @param {Object} formData - Object containing name, email, and message.
   * @returns {Promise<Object>} A promise that resolves to the API response.
   */
  submitContactForm: (formData) => {
    return apiClient.post('/contact-form', formData)
  }
}

// Export all API modules
export {
  customersApi,
  appointmentsApi,
  servicesCategoriesApi,
  staffApi,
  paymentsApi,
  newsletterApi,
  formsApi,
  settingsApi,
  appearanceApi,
  notificationsApi,
  analyticsApi,
  utilityApi,
  stripeApi,
  contactApi
}