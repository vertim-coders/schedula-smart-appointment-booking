<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-xl font-semibold" :style="{ color: 'var(--admin-text-color)' }">{{ isEditing ? __('Edit Appointment', 'schedula-smart-appointment-booking') : __('Create New Appointment', 'schedula-smart-appointment-booking') }}</h3>
      <button
        type="button"
        @click="resetForm"
        class="px-4 py-2 rounded-lg shadow-sm text-sm font-medium inline-flex items-center"
        :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)' }"
      >
        <i class="fas fa-sync-alt mr-2"></i>{{ __('Reset Form', 'schedula-smart-appointment-booking') }}
      </button>
    </div>
    
    <form @submit.prevent="submitForm">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <!-- Recurrence Options -->
        <div v-if="generalSettings && generalSettings.enableRecurringAppointments" 
             class="md:col-span-2 p-4 rounded-lg"
             :style="{ backgroundColor: 'var(--admin-card-bg-color)', borderColor: 'var(--admin-border-color)', color: 'var(--admin-card-text-color)', border: '1px solid var(--admin-border-color)' }">
            <h4 class="text-lg font-semibold mb-4">{{ __('Recurrence Settings', 'schedula-smart-appointment-booking') }}</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <BaseCheckbox
                id="is_recurring"
                :label="__('Make this a recurring appointment', 'schedula-smart-appointment-booking')"
                v-model="form.is_recurring"
                />
            </div>
            <div v-if="form.is_recurring" class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                <BaseSelect
                id="recurrence_frequency"
                :label="__('Frequency', 'schedula-smart-appointment-booking')"
                v-model="form.recurrence_frequency"
                :options="recurrenceFrequencyOptions"
                :required="form.is_recurring"
                />
                <BaseInput
                id="recurrence_interval"
                :label="__('Interval', 'schedula-smart-appointment-booking')"
                type="number"
                v-model.number="form.recurrence_interval"
                :min="1"
                :required="form.is_recurring"
                />
                <BaseSelect
                id="recurrence_count"
                :label="__('Number of Occurrences', 'schedula-smart-appointment-booking')"
                v-model.number="form.recurrence_count"
                :options="recurrenceCountOptions"
                :required="form.is_recurring"
                />
            </div>
            </div>
        </div>

        <!-- Customer Selection / Manual Entry -->
        <div>
          <div v-if="isEditing && form.customer_first_name">
            <!-- Display customer name directly when editing using BaseInput -->
            <BaseInput
              id="customer_name_display"
              :label="__('Customer', 'schedula-smart-appointment-booking')"
              type="text"
              :modelValue="`${form.customer_first_name} ${form.customer_last_name}`"
              :disabled="true"
              icon="fas fa-user"
            />
            <p class="text-sm mt-1" :style="{ color: 'var(--admin-card-text-color)' }">
              {{ __('To change the customer, please create a new appointment or edit the customer directly in the Clients section.', 'schedula-smart-appointment-booking') }}
            </p>
          </div>
          <div v-else>
            <!-- Dropdown for new appointments using raw HTML for correct styling -->
            <div>
              <label for="customer_selection" class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-card-text-color)' }">
                <i class="fas fa-user mr-1"></i>{{ __('Customer', 'schedula-smart-appointment-booking') }}
                <span class="text-red-500">*</span>
              </label>
              <div class="standardized-input-container">
                <select
                  id="customer_selection"
                  v-model="selectedClientId"
                  @change="onCustomerSelect($event.target.value)"
                  :disabled="loadingCustomers"
                  required
                  class="standardized-input-main select-appearance"
                >
                  <option value="" disabled>{{ __('Select an existing customer', 'schedula-smart-appointment-booking') }}</option>
                  <option v-for="option in customerOptions" :key="option.value" :value="option.value">
                    {{ option.text }}
                  </option>
                </select>
                <button
                  type="button"
                  @click="openInlineClientForm"
                  class="standardized-button"
                  :disabled="loadingCustomers"
                >
                  <i class="fas fa-plus mr-1"></i> {{ __('New Client', 'schedula-smart-appointment-booking') }}
                </button>
              </div>
            </div>
            <p v-if="loadingCustomers" class="text-sm mt-1 flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
              <i class="fas fa-spinner fa-spin mr-2"></i> {{ __('Loading customers...', 'schedula-smart-appointment-booking') }}
            </p>
            <p v-if="customerError" class="text-sm mt-1" :style="{ color: 'var(--admin-suggestion-red-text)' }">
              <i class="fas fa-exclamation-triangle mr-2"></i> {{ sprintf(__('Error loading customers: %s', 'schedula-smart-appointment-booking'), customerError) }}
            </p>
          </div>
        </div>

        <!-- Service -->
        <div>
          <BaseSelect
            id="service_id"
            :label="__('Service', 'schedula-smart-appointment-booking')"
            v-model="form.service_id"
            @update:modelValue="onServiceChange"
            :options="serviceOptions"
            :placeholder="__('Select a service', 'schedula-smart-appointment-booking')"
            :required="true"
            :disabled="loadingServices"
            icon="fas fa-concierge-bell"
          />
          <p v-if="loadingServices" class="text-sm mt-1 flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
            <i class="fas fa-spinner fa-spin mr-2"></i> {{ __('Loading services...', 'schedula-smart-appointment-booking') }}
          </p>
          <p v-if="servicesError" class="text-sm mt-1" :style="{ color: 'var(--admin-suggestion-red-text)' }">{{ __('Error loading services: ', 'schedula-smart-appointment-booking') }}{{ servicesError }}</p>
        </div>

        <!-- NEW: Number of Persons -->
        <div v-if="generalSettings && generalSettings.enableGroupBooking">
          <BaseSelect
            id="number_of_persons"
            :label="__('Number of Persons', 'schedula-smart-appointment-booking')"
            v-model.number="form.number_of_persons"
            :options="personOptions"
            :required="true"
            icon="fas fa-users"
          />
        </div>

        <!-- Staff -->
        <div>
          <BaseSelect
            id="staff_id"
            :label="__('Employee', 'schedula-smart-appointment-booking')"
            v-model="form.staff_id"
            @update:modelValue="onStaffChange"
            :options="staffOptions"
            :placeholder="__('Any Employee', 'schedula-smart-appointment-booking')"
            :disabled="loadingFilteredStaff"
            icon="fas fa-user-tie"
          />
          <p v-if="loadingFilteredStaff" class="text-sm mt-1 flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
            <i class="fas fa-spinner fa-spin mr-2"></i> {{ __('Loading employees...', 'schedula-smart-appointment-booking') }}
          </p>
          <div v-if="staffAvailabilityMessage" 
              :class="['px-4 py-3 rounded relative mt-1']" 
              role="alert" :style="{ backgroundColor: staffAvailabilityType === 'success' ? 'var(--admin-input-border-color)' : 'var(--admin-suggestion-red-bg)', color: staffAvailabilityType === 'success' ? 'var(--admin-card-text-color)' : 'var(--admin-suggestion-red-text)' }">
            <span class="block sm:inline">{{ staffAvailabilityMessage }}</span>
          </div>
        </div>

        <!-- Appointment Date -->
        <div>
          <BaseInput
            id="appointment_date_datepicker"
            :label="__('Date', 'schedula-smart-appointment-booking')"
            type="text"
            v-model="form.appointment_date"
            :required="true"
            icon="fas fa-calendar-alt"
            placeholder="YYYY-MM-DD"
          />
        </div>
        
        <!-- Start Time -->
        <div>
          <BaseSelect
            id="start_time"
            :label="__('Start Time', 'schedula-smart-appointment-booking')"
            v-model="form.appointment_time"
            @update:modelValue="adjustEndTime"
            :options="timeSlotOptions"
            :placeholder="__('Select a time', 'schedula-smart-appointment-booking')"
            :required="true"
            :disabled="isLoadingTimeSlots || !form.staff_id || !form.appointment_date || !form.service_id"
            icon="fas fa-clock"
          />
          <p v-if="isLoadingTimeSlots" class="text-sm mt-1 flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
            <i class="fas fa-spinner fa-spin mr-2"></i> {{ __('Loading available times...', 'schedula-smart-appointment-booking') }}
          </p>
          <p v-if="timeSlotsError" class="text-sm mt-1" :style="{ color: 'var(--admin-suggestion-red-text)' }">
            {{ timeSlotsError }}
          </p>
          <p v-if="!isLoadingTimeSlots && !timeSlotsError && availableTimeSlots.length === 0 && form.staff_id && form.appointment_date && form.service_id" class="text-sm mt-1" :style="{ color: 'var(--admin-card-text-color)' }">
            {{ __('No available time slots for this day.', 'schedula-smart-appointment-booking') }}
          </p>
        </div>

        <!-- End Time (Auto-calculated) -->
        <div>
          <BaseInput
            id="end_time"
            :label="__('End Time', 'schedula-smart-appointment-booking')"
            type="time"
            :modelValue="calculatedEndTime"
            :disabled="true"
            icon="fas fa-clock"
          />
          <p class="text-sm mt-1" :style="{ color: 'var(--admin-card-text-color)' }">
            {{ __('Duration of the service: ', 'schedula-smart-appointment-booking') }}{{ form.duration }} {{ __('minutes', 'schedula-smart-appointment-booking') }}
          </p>
        </div>

        <!-- Price (Informative) -->
        <div>
          <BaseInput
            id="price"
            :label="__('Price', 'schedula-smart-appointment-booking')"
            type="text"
            :modelValue="formatPrice(form.price)"
            :disabled="true"
            icon="fas fa-money-bill"
          />
        </div>
        <!-- Status -->
        <div>
          <BaseSelect
            id="status"
            :label="__('Status', 'schedula-smart-appointment-booking')"
            v-model="form.status"
            :options="statusOptions"
            :required="true"
            icon="fas fa-info-circle"
          />
        </div>
      </div>

      <!-- Notes -->
      <div class="mb-4">
        <BaseTextarea
          id="notes"
          :label="__('Notes', 'schedula-smart-appointment-booking')"
          v-model="form.notes"
          rows="3"
          icon="fas fa-sticky-note"
        />
      </div>

      <div class="flex justify-end space-x-3">
        <button
          type="button"
          @click="emit('cancel')"
          class="px-6 py-2 rounded-lg shadow-sm text-sm font-medium inline-flex items-center"
          :style="{ backgroundColor: 'var(--admin-button-third-bg)', color: 'var(--admin-button-secondary-text)' }"
        >
          <i class="fas fa-times mr-2"></i>{{ __('Close', 'schedula-smart-appointment-booking') }}
        </button>
        <button
          type="submit"
          :disabled="isSaveButtonDisabled"
          class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed"
          :style="{ backgroundColor: 'var(--admin-link-indigo-bg)', color: 'var(--admin-link-indigo-text)' }"
        >
          <svg v-if="saving" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <i v-else class="fas fa-save mr-2"></i>{{ saving ? __('Saving...', 'schedula-smart-appointment-booking') : __('Save', 'schedula-smart-appointment-booking') }}
        </button>
      </div>
    </form>

    <!-- Inline ClientForm Modal/Section -->
    <transition name="modal-fade">
      <div v-if="showInlineClientForm"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 overflow-y-auto" 
          @click.self="closeInlineClientForm">
        <div class="rounded-lg shadow-xl w-full max-w-lg my-8 mx-4 transform transition-all overflow-y-auto max-h-[95vh] modal-content" @click.stop> 
          <ClientForm
            :initial-client="null" 
            @client-saved="handleNewClientSavedFromInlineForm"
            @close-form="closeInlineClientForm"
          />
        </div>
      </div>
    </transition>
  </div>
</template>


<script setup>
import { ref, watch, onMounted, onBeforeUnmount, defineProps, defineEmits, computed, nextTick } from 'vue';
import { useToast } from '../../composables/useToast.js'; // Corrected import path for useToast
import { Datepicker } from 'flowbite-datepicker';
import ClientForm from '../clients/ClientForm.vue'; // Path to ClientForm
import {__} from '@wordpress/i18n';
// Import Base Components
import BaseInput from '../common/BaseInput.vue';
import BaseSelect from '../common/BaseSelect.vue';
import BaseTextarea from '../common/BaseTextarea.vue';
import BaseCheckbox from '../common/BaseCheckbox.vue';
import BaseInputWithButton from '../common/BaseInputWithButton.vue';

import { appointmentsApi, customersApi, servicesCategoriesApi, staffApi } from '@/admin/api'; // Ensure all necessary APIs are imported
import { useGlobalSettings } from '../../composables/useGlobalSettings.js'; // Import useGlobalSettings

const props = defineProps({
  initialData: {
    type: Object,
    default: null,
  },
  initialDate: {
    type: String,
    default: '',
  },
  initialTime: {
    type: String,
    default: '',
  }
});

const emit = defineEmits(['submit', 'cancel']);
const { success, error: toastError, info } = useToast(); // Destructure toast functions

// Destructure formatPrice from useGlobalSettings
const { generalSettings, formatPrice, formatTime, fetchGlobalSettings } = useGlobalSettings();
console.log('GeneralSettings variable immediately after useGlobalSettings():', generalSettings); // NEW LOG

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const defaultForm = {
  id: null,
  service_id: '',
  staff_id: '',
  customer_id: null,
  customer_first_name: '',
  customer_last_name: '',
  customer_email: '',
  customer_phone: '',
  appointment_date: '',
  appointment_time: '',
  duration: 0, 
  price: 0.00, 
  status: 'pending',
  notes: '', 
  number_of_persons: 1,
  // NEW RECURRENCE FIELDS
  is_recurring: false,
  recurrence_frequency: 'weekly', 
  recurrence_interval: 1,
  recurrence_count: 1,
};

const form = ref({ ...defaultForm });
const isEditing = ref(false);
const saving = ref(false);
// const formError = ref(null); // Removed: Toast will handle this

const services = ref([]);
const loadingServices = ref(true);
const servicesError = ref(null); // Keep for display in P tag if needed

const filteredStaffMembers = ref([]); 
const loadingFilteredStaff = ref(false);

const customers = ref([]); 
const loadingCustomers = ref(true);
const customerError = ref(null); // NEW: To display error for customer fetching
const selectedClientId = ref(''); 

const showInlineClientForm = ref(false);

const staffAvailabilityMessage = ref('');
const staffAvailabilityType = ref(''); // 'success' or 'error'

const isPopulatingForEdit = ref(false);

// Flowbite Datepicker instance
let datepickerInstance = null;
let recurrenceDatepickerInstance = null;

// NEW: State for available time slots
const availableTimeSlots = ref([]);
const isLoadingTimeSlots = ref(false);
const timeSlotsError = ref(null);


// Computed options for BaseSelect components
const customerOptions = computed(() => {
  if (loadingCustomers.value) return [];
  return customers.value.map(c => ({
    value: String(c.id), // Ensure value is string for select element
    text: `${c.first_name} ${c.last_name}`
  }));
});

const serviceOptions = computed(() => {
  if (loadingServices.value) return [];
  return services.value.map(s => ({
    value: String(s.id), // Ensure value is string
    text: s.title
  }));
});

const staffOptions = computed(() => {
  if (loadingFilteredStaff.value) return [];
  return filteredStaffMembers.value.map(s => ({
    value: String(s.id), // Ensure value is string
    text: s.name
  }));
});

const statusOptions = computed(() => ([ 
  { value: 'pending', text: __('Pending', 'schedula-smart-appointment-booking') },
  { value: 'confirmed', text: __('Confirmed', 'schedula-smart-appointment-booking') },
  { value: 'completed', text: __('Completed', 'schedula-smart-appointment-booking') },
  { value: 'cancelled', text: __('Cancelled', 'schedula-smart-appointment-booking') },
]));

const recurrenceFrequencyOptions = computed(() => ([
  { value: 'daily', text: __('Daily', 'schedula-smart-appointment-booking') },
  { value: 'weekly', text: __('Weekly', 'schedula-smart-appointment-booking') },
  { value: 'monthly', text: __('Monthly', 'schedula-smart-appointment-booking') },
  { value: 'yearly', text: __('Yearly', 'schedula-smart-appointment-booking') },
]));

const recurrenceCountOptions = computed(() => {
  const max = generalSettings.value?.recurrence?.maxRecurrences;
  if (!max || max <= 0) {
    // If no limit (0) or not set, provide a default range up to 52 (for weekly appointments in a year).
    return Array.from({ length: 52 }, (_, i) => ({ value: i + 1, text: String(i + 1) }));
  }
  return Array.from({ length: max }, (_, i) => ({ value: i + 1, text: String(i + 1) }));
});

const personOptions = computed(() => {
  if (!generalSettings.value || !generalSettings.value.enableGroupBooking) {
    return [{ value: 1, text: '1 person' }];
  }
  const max = generalSettings.value.maxPersonsPerBooking || 1;
  const options = [];
  for (let i = 1; i <= max; i++) {
    options.push({ value: i, text: `${i} ${i > 1 ? __('people', 'schedula-smart-appointment-booking') : __('person', 'schedula-smart-appointment-booking')}` });
  }
  return options;
});

// NEW: Computed property to format time slots for the BaseSelect component
const timeSlotOptions = computed(() => {
  return availableTimeSlots.value.map(time => ({
    value: time, // e.g., "09:30"
    text: formatTime(time) // e.g., "9:30 AM"
  }));
});


const isSaveButtonDisabled = computed(() => {
    const isDisabled = (() => {
        if (saving.value) {
            return true;
        }
        if (!form.value.service_id || !form.value.customer_email || !form.value.appointment_date || !form.value.appointment_time) {
            return true;
        }
        
        if (form.value.customer_email !== null && form.value.customer_email !== undefined && !EMAIL_REGEX.test(String(form.value.customer_email))) {
            return true;
        }

        // Availability check logic
        if (form.value.staff_id) {
            if (form.value.duration <= 0 || isNaN(form.value.duration)) {
                return true;
            }
            // Allow saving if status is success OR if message is empty (meaning no check has been performed/needed yet)
            if (staffAvailabilityType.value === 'error') {
                return true;
            }
            if (staffAvailabilityMessage.value === 'Checking availability...') {
                return true;
            }
        }
        return false;
    })();

    
    return isDisabled;
});

const calculatedEndTime = computed(() => {
  if (!form.value.appointment_date || !form.value.appointment_time || !form.value.duration || isNaN(form.value.duration) || form.value.duration <= 0) {
    return '';
  }
  const startDateTimeString = `${form.value.appointment_date}T${form.value.appointment_time}:00`;
  const startDateTime = new Date(startDateTimeString);
  if (isNaN(startDateTime.getTime())) {
    return '';
  }
  const endDateTime = new Date(startDateTime.getTime() + form.value.duration * 60 * 1000);
  const hours = String(endDateTime.getHours()).padStart(2, '0');
  const minutes = String(endDateTime.getMinutes()).padStart(2, '0');
  const endTime = `${hours}:${minutes}`;
  return endTime;
});

// --- Helper Functions ---

const updatePriceAndDuration = (serviceId, staffId) => {
  const selectedService = services.value.find(s => String(s.id) === String(serviceId));
  const selectedStaff = filteredStaffMembers.value.find(s => String(s.id) === String(staffId));

  let basePrice = 0.00;
  let newDuration = 0;

  if (selectedService) {
    basePrice = selectedStaff?.price !== undefined && selectedStaff.price !== null ? parseFloat(selectedStaff.price) : (selectedService.price !== undefined && selectedService.price !== null ? parseFloat(selectedService.price) : 0.00);
    newDuration = selectedStaff?.duration !== undefined && selectedStaff.duration !== null ? parseInt(selectedStaff.duration) : (selectedService.duration !== undefined && selectedService.duration !== null ? parseInt(selectedService.duration) : 0);
  }

  // Apply group booking price logic if enabled
  if (generalSettings.value && generalSettings.value.enableGroupBooking) {
    const logic = generalSettings.value.groupBookingPriceLogic;
    const persons = form.value.number_of_persons;
    let finalPrice = basePrice;

    if (logic && persons && !isNaN(basePrice)) {
      switch (logic.type) {
        case 'per_person_multiply':
          finalPrice = basePrice * persons;
          break;
        case 'fixed_discount_per_person':
          const discountedPricePerPerson = Math.max(0, basePrice - parseFloat(logic.amount));
          finalPrice = discountedPricePerPerson * persons;
          break;
        case 'percentage_discount_total':
          const totalInitialPrice = basePrice * persons;
          const discountAmount = (totalInitialPrice * parseFloat(logic.amount)) / 100;
          finalPrice = Math.max(0, totalInitialPrice - discountAmount);
          break;
        default:
          finalPrice = basePrice * persons; // Fallback
          break;
      }
    }
    form.value.price = parseFloat(finalPrice.toFixed(2));
  } else {
    form.value.price = parseFloat(basePrice.toFixed(2));
  }
  
  form.value.duration = newDuration;
};

// --- Form Reset Function ---
const resetForm = () => {
  form.value = { ...defaultForm };
  selectedClientId.value = ''; 
  showInlineClientForm.value = false;
  staffAvailabilityMessage.value = '';
  staffAvailabilityType.value = '';
  // formError.value = null; // Removed: Toast will handle this
  availableTimeSlots.value = []; // Clear time slots
  timeSlotsError.value = null;
  customerError.value = null; // Clear customer error
  if (datepickerInstance) {
    datepickerInstance.setDate('');
  }
  if (recurrenceDatepickerInstance) {
    recurrenceDatepickerInstance.setDate('');
  }
};
// --- End Form Reset Function ---


const fetchServices = async () => {
  if (services.value.length > 0) return;
  loadingServices.value = true;
  servicesError.value = null;
  try {
    const response = await servicesCategoriesApi.getServices({ per_page: 999, page: 1 });
    // Assuming response.data is directly the array of services, or has a .services property
    services.value = response.data.services || response.data; 
  } catch (err) {
    servicesError.value = err.response?.data?.message || err.message || 'Error fetching services.';
    console.error('Fetch services error:', err);
    toastError(__('Failed to load services for the form.', 'schedula-smart-appointment-booking')); // Toast for service fetch error
  } finally {
    loadingServices.value = false;
  }
};

const fetchStaffForSelectedService = async (serviceId) => {
  if (!serviceId) {
    filteredStaffMembers.value = [];
    return;
  }
  loadingFilteredStaff.value = true;
  try {
    const response = await appointmentsApi.getStaffForService(serviceId);
    filteredStaffMembers.value = response.data; 
  } catch (err) {
    console.error('Error fetching staff for service:', err);
    toastError(__('Failed to load employees for the selected service.', 'schedula-smart-appointment-booking')); // Toast for staff fetch error
    filteredStaffMembers.value = []; 
  } finally {
    loadingFilteredStaff.value = false;
  }
};

const fetchCustomers = async (force = false) => {
  if (customers.value.length > 0 && !force) return;
  loadingCustomers.value = true;
  customerError.value = null; // Clear previous customer errors
  try {
    const response = await customersApi.getCustomers();
    customers.value = response.data.clients || response.data; // Access 'clients' array from paginated response or direct data
  } catch (err) {
    customerError.value = err.response?.data?.message || err.message || 'Error fetching customers.';
    console.error('Error fetching customers:', err);
    toastError(__('Failed to load customers for the form.', 'schedula-smart-appointment-booking')); // Toast for customer fetch error
  } finally {
    loadingCustomers.value = false;
  }
};

// NEW: Function to fetch available time slots
const fetchAvailableTimeSlots = async () => {
  const { service_id, appointment_date, staff_id } = form.value;
  if (!service_id || !appointment_date || (!staff_id && staff_id !== 0 && staff_id !== '')) { // Include 0 and '' for 'Any Employee'
    availableTimeSlots.value = [];
    return;
  }

  isLoadingTimeSlots.value = true;
  timeSlotsError.value = null;
  availableTimeSlots.value = [];

  try {
    const response = await appointmentsApi.getAvailableTimeSlots({
      service_id: service_id,
      date: appointment_date,
      staff_id: staff_id,
    });
    availableTimeSlots.value = response.data;
  } catch (err) {
    timeSlotsError.value = err.response?.data?.message || err.message || __('Error fetching available times.', 'schedula-smart-appointment-booking');
    toastError(__('Failed to load available time slots:', 'schedula-smart-appointment-booking') + ' ' + timeSlotsError.value); // Toast for time slot error
    console.error('Fetch available time slots error:', err);
  } finally {
    isLoadingTimeSlots.value = false;
  }
};

// Watch initialData prop for editing existing appointments
watch(() => props.initialData, async (newVal) => {
  staffAvailabilityMessage.value = '';
  staffAvailabilityType.value = '';
  // formError.value = null; // Removed: Toast will handle this

  if (newVal && newVal.id) {
    isPopulatingForEdit.value = true; // Set flag to prevent premature availability checks
    isEditing.value = true;
    
    // Ensure customers and services are loaded before populating form
    await Promise.all([fetchCustomers(), fetchServices()]);

    // Populate form fields from initialData
    // Populate form fields from initialData, ensuring defaults are used for null/undefined values from newVal
    for (const key in defaultForm) {
      if (Object.prototype.hasOwnProperty.call(defaultForm, key)) {
        // Use newVal's value if it's not null or undefined, otherwise use defaultForm's value
        form.value[key] = (newVal[key] !== undefined && newVal[key] !== null) ? newVal[key] : defaultForm[key];
      }
    }

    // Explicitly convert duration and price from initialData to numbers
    form.value.duration = parseInt(newVal.duration) || 0;
    form.value.price = parseFloat(newVal.price) || 0.00;

    if (newVal.start_datetime) {
      const dateTime = new Date(newVal.start_datetime);
      const year = dateTime.getFullYear();
      const month = String(dateTime.getMonth() + 1).padStart(2, '0');
      const day = String(dateTime.getDate()).padStart(2, '0');
      form.value.appointment_date = `${year}-${month}-${day}`;
      form.value.appointment_time = dateTime.toTimeString().split(' ')[0].substring(0, 5);
    }

    form.value.customer_id = newVal.customer_id; 
    selectedClientId.value = String(newVal.customer_id); // Set for dropdown in case it's shown for editing or for clarity

    // --- Fetch customer details if email is missing or invalid in initialData ---
    if ((!form.value.customer_email || !EMAIL_REGEX.test(form.value.customer_email)) && form.value.customer_id) {
        try {
            const customerResponse = await customersApi.getCustomer(form.value.customer_id);
            const fetchedCustomer = customerResponse.data;
            if (fetchedCustomer) {
                form.value.customer_first_name = fetchedCustomer.first_name;
                form.value.customer_last_name = fetchedCustomer.last_name;
                form.value.customer_email = fetchedCustomer.email;
                form.value.customer_phone = fetchedCustomer.phone;
            }
        } catch (err) {
            console.error('Error fetching customer details for appointment:', err);
            toastError(__('Could not retrieve full customer details.', 'schedula-smart-appointment-booking')); // Toast for customer details error
        }
    }
    // --- END NEW ---

    if (form.value.service_id) {
      await fetchStaffForSelectedService(form.value.service_id);
      form.value.staff_id = newVal.staff_id || ''; // Use empty string for 'Any Employee' as the value
    }
    
    await nextTick(); 
    
    // When editing, fetch time slots but also ensure the current time is in the list
    if (form.value.staff_id && form.value.appointment_date && form.value.service_id) {
        // Await the fetch to ensure availableTimeSlots is populated
        await fetchAvailableTimeSlots(); // Call directly, no debounce needed here as it's a one-time population

        // Now, if the original time isn't in the fetched slots, add it
        if (form.value.appointment_time && !availableTimeSlots.value.includes(form.value.appointment_time)) {
            availableTimeSlots.value.push(form.value.appointment_time);
            availableTimeSlots.value.sort(); // Keep the list sorted
        }
    }
    
    isPopulatingForEdit.value = false;

    nextTick(() => {
        initDatepicker();
        if (datepickerInstance && form.value.appointment_date) {
            datepickerInstance.setDate(form.value.appointment_date);
        }
    });

  } else {
    isEditing.value = false;
    resetForm();
    if (props.initialDate) {
      form.value.appointment_date = props.initialDate;
    }
    if (props.initialTime) {
      form.value.appointment_time = props.initialTime;
    }
    // For new appointments, ensure customers and services are fetched
    await Promise.all([fetchCustomers(), fetchServices()]);
    nextTick(() => {
        initDatepicker();
        if (datepickerInstance && form.value.appointment_date) {
            datepickerInstance.setDate(form.value.appointment_date);
        }
    });
  }
}, { immediate: true, deep: true }); 


// Watch for changes to fetch time slots
watch([
  () => form.value.service_id, 
  () => form.value.staff_id, 
  () => form.value.appointment_date, 
], () => {
  if (isPopulatingForEdit.value) return; // Reintroduce this guard
  
  debouncedFetchAvailableTimeSlots();
  
  staffAvailabilityMessage.value = '';
  staffAvailabilityType.value = '';
});

// Watch for time change to check specific slot availability
watch(() => form.value.appointment_time, (newTime) => {
  if (isPopulatingForEdit.value) return;

  staffAvailabilityMessage.value = '';
  staffAvailabilityType.value = '';

  if (form.value.staff_id && form.value.appointment_date && newTime && form.value.duration > 0) {
    debouncedCheckStaffAvailability();
  }
});

// Watch for changes in is_recurring to reset recurrence fields
watch(() => form.value.is_recurring, (newVal) => {
  if (!newVal) { // If is_recurring becomes false
    form.value.recurrence_frequency = 'weekly'; // Or ''
    form.value.recurrence_interval = 1;
    form.value.recurrence_count = 1; // Or null
  }
});

// Watch for changes in number_of_persons to update price
watch(() => form.value.number_of_persons, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    updatePriceAndDuration(form.value.service_id, form.value.staff_id);
  }
});

let availabilityDebounceTimeout = null;
const debouncedCheckStaffAvailability = () => {
  clearTimeout(availabilityDebounceTimeout);
  availabilityDebounceTimeout = setTimeout(checkStaffAvailability, 500);
};

const checkStaffAvailability = async () => {
  const { staff_id, appointment_date, appointment_time, duration, id } = form.value;

  if (!staff_id && staff_id !== 0 && staff_id !== '') { // staff_id can be 0 or empty string for "Any Employee" 
    staffAvailabilityMessage.value = __('Please select an employee or "Any Employee".', 'schedula-smart-appointment-booking');
    staffAvailabilityType.value = 'error';
    return;
  }

  if (!appointment_date || !appointment_time || isNaN(duration) || duration <= 0) {
    staffAvailabilityMessage.value = __('Please select a service, date, and time to check availability.', 'schedula-smart-appointment-booking');
    staffAvailabilityType.value = 'error';
    return;
  }

  try {
    const response = await appointmentsApi.checkStaffAvailability(
      staff_id, 
      appointment_date, 
      appointment_time, 
      duration, 
      id || 0 
    );
    staffAvailabilityMessage.value = response.data.message;
    staffAvailabilityType.value = response.data.available ? 'success' : 'error';
  } catch (err) {
    staffAvailabilityMessage.value = err.response?.data?.message || err.message || __('Error checking staff availability.', 'schedula-smart-appointment-booking');
    staffAvailabilityType.value = 'error';
    toastError(`${__('Failed to check staff availability:', 'schedula-smart-appointment-booking')} ${staffAvailabilityMessage.value}`); // Toast for availability check error
    console.error('Availability check API error:', err);
  }
};

let timeSlotsDebounceTimeout = null;
const debouncedFetchAvailableTimeSlots = () => {
  clearTimeout(timeSlotsDebounceTimeout);
  timeSlotsDebounceTimeout = setTimeout(() => {
    fetchAvailableTimeSlots();
  }, 300); // Adjust debounce time as needed
};

const onServiceChange = async (newServiceId) => {
  form.value.service_id = newServiceId; // Update v-model
  form.value.staff_id = ''; // Reset staff when service changes
  await fetchStaffForSelectedService(form.value.service_id);
  updatePriceAndDuration(form.value.service_id, form.value.staff_id); // Call the new function
  adjustEndTime(); 
};

const onStaffChange = (newStaffId) => {
  form.value.staff_id = newStaffId; // Update v-model
  updatePriceAndDuration(form.value.service_id, form.value.staff_id); // Call the new function
  adjustEndTime(); 
};

const adjustEndTime = () => {
    // This is handled by the computed property `calculatedEndTime`
};

const onCustomerSelect = (newClientId) => {
  selectedClientId.value = newClientId; // Update v-model
  const customer = customers.value.find(c => String(c.id) === String(selectedClientId.value));
  if (customer) {
    Object.assign(form.value, { 
        customer_id: customer.id, 
        customer_first_name: customer.first_name, 
        customer_last_name: customer.last_name, 
        customer_email: customer.email, 
        customer_phone: customer.phone 
    });
  } else {
    Object.assign(form.value, { 
        customer_id: null, 
        customer_first_name: '', 
        customer_last_name: '', 
        customer_email: '', 
        customer_phone: '' 
    });
    selectedClientId.value = ''; 
  }
};

const openInlineClientForm = () => {
  showInlineClientForm.value = true;
  selectedClientId.value = ''; // Clear selected client from dropdown
  Object.assign(form.value, { 
      customer_id: null, 
      customer_first_name: '', 
      customer_last_name: '', 
      customer_email: '', 
      customer_phone: '' 
  });
};

const handleNewClientSavedFromInlineForm = async (savedClient) => {
  form.value.customer_id = savedClient.id;
  Object.assign(form.value, { 
      customer_first_name: savedClient.first_name, 
      customer_last_name: savedClient.last_name, 
      customer_email: savedClient.email, 
      customer_phone: savedClient.phone 
  });
  selectedClientId.value = String(savedClient.id); // Select the newly created client in the dropdown
  showInlineClientForm.value = false;
  // The toast is now handled by ClientForm.vue, so we remove it from here to avoid duplicates.
  await fetchCustomers(true); // Re-fetch customers to update the dropdown list
};

const closeInlineClientForm = () => {
  showInlineClientForm.value = false;
  if (!selectedClientId.value) { 
    Object.assign(form.value, { 
        customer_id: null, 
        customer_first_name: '', 
        customer_last_name: '', 
        customer_email: '', 
        customer_phone: '' 
    });
  }
};

const submitForm = async () => {
  // formError.value = null; // Removed: Toast will handle this
  if (!form.value.service_id || !form.value.customer_email || !form.value.appointment_date || !form.value.appointment_time) {
    toastError(__('Please fill in all required fields (Service, Email, Date, Time).', 'schedula-smart-appointment-booking'));
    return;
  }
  if (form.value.customer_email === null || form.value.customer_email === undefined || !EMAIL_REGEX.test(String(form.value.customer_email))) {
    toastError(__('Please enter a valid customer email address.', 'schedula-smart-appointment-booking'));
    return;
  }

  saving.value = true;
  
  if (form.value.staff_id !== '' && form.value.staff_id !== null) { // Only perform final check if a staff is chosen (not 'Any Employee')
    await checkStaffAvailability();
    await nextTick(); 
    if (staffAvailabilityType.value === 'error') {
        toastError(sprintf(__('Submission failed: %s', 'schedula-smart-appointment-booking'), staffAvailabilityMessage.value));
        saving.value = false;
        return;
    }
  }

  emit('submit', { ...form.value });
};

const stopSaving = () => {
  saving.value = false;
};

defineExpose({
  stopSaving
});

onMounted(async () => {
  await fetchGlobalSettings(); // Force a refresh of settings
  // initialData watcher will call fetchCustomers and fetchServices initially
  initDatepicker();

  // Add console.log here
  if (generalSettings) { // Check if generalSettings ref itself is defined
    if (generalSettings.value) { // Check if the value inside the ref is defined
      console.log('Global Settings on Mount:', generalSettings.value);
      console.log('enableRecurringAppointments on Mount:', generalSettings.value.enableRecurringAppointments);
    } else {
      console.log('General Settings ref is defined, but its value is null/undefined after fetchGlobalSettings.');
    }
  } else {
    console.log('General Settings ref itself is undefined/null after fetchGlobalSettings.');
  }
});

onBeforeUnmount(() => {
  if (datepickerInstance) {
    datepickerInstance.destroy();
    datepickerInstance = null;
  }
  if (recurrenceDatepickerInstance) {
    recurrenceDatepickerInstance.destroy();
    recurrenceDatepickerInstance = null;
  }
});

const initDatepicker = () => {
  const datepickerEl = document.getElementById('appointment_date_datepicker');
  if (datepickerEl) {
    if (datepickerInstance) {
      datepickerInstance.destroy();
      datepickerInstance = null;
    }
    datepickerInstance = new Datepicker(datepickerEl, {
      autohide: true,
      format: 'yyyy-mm-dd',
    });
    datepickerEl.addEventListener('changeDate', (event) => {
      const date = event.detail.date;
      const formattedDate = date ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}` : '';
      if (form.value.appointment_date !== formattedDate) {
        form.value.appointment_date = formattedDate;
        debouncedFetchAvailableTimeSlots(); // Trigger fetch when date changes
      }
    });
  }

  
};


</script>


<style scoped>

/* Modal transition styles */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

/* Optional: Add a slight scale/slide effect for the modal content */
.modal-fade-enter-active .modal-content,
.modal-fade-leave-active .modal-content {
  transition: transform 0.3s ease;
}

.modal-fade-enter-from .modal-content,
.modal-fade-leave-to .modal-content {
  transform: scale(0.95);
}
</style>
>
le>
