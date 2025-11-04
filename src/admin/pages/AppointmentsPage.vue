<template>
  <div>
    <h2 class="text-3xl font-bold mb-6" :style="{ color: 'var(--admin-text-color)' }">{{ __('Appointments', 'schedula-smart-appointment-booking') }}</h2>

    <!-- Conditional Rendering: Show Form or List -->
    <div v-if="showAppointmentForm" class="p-6 rounded-lg shadow-xl content-card">
      <!-- Appointment Form (Full Page) - Reduced padding here too -->
      <AppointmentsForm
        ref="appointmentFormRef"
        :initialData="selectedAppointment"
        :initialTime="modalInitialTime"
        @submit="handleAppointmentFormSubmit"
        @cancel="closeAppointmentForm"
      />
    </div>

    <div v-else>
      <!-- Appointments List View -->

      <!-- Filters Container -->
      <div class="p-6 rounded-lg shadow-md content-card">
        <!-- Row 1: Create Appointment Button, Rows per page selector, and Column Visibility -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
          <!-- Create Appointment Button -->
          <button @click="openCreateAppointmentForm"
                  class="inline-flex items-center px-4 py-2 text-base border border-transparent font-medium rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out mb-4 sm:mb-0" :style="{ backgroundColor: 'var(--admin-link-indigo-bg)', color: 'var(--admin-link-indigo-text)' }">
              <i class="fas fa-plus-circle mr-2"></i> {{ __('Create an Appointment', 'schedula-smart-appointment-booking') }}
          </button>

          <div class="flex flex-wrap items-center gap-4">
            <!-- Top Rows per page selector -->
            <div v-if="itemsPerPageOptions.length > 1" class="flex items-center gap-2">
              <span class="text-sm whitespace-nowrap" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Rows per page:', 'schedula-smart-appointment-booking') }}</span>
              <select v-model="itemsPerPage" @change="handleItemsPerPageChange" class="rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm p-1.5 input-field">
                <option v-for="option in itemsPerPageOptions" :key="option" :value="option">{{ option }}</option>
              </select>
            </div>

            <!-- Column Visibility Toggle -->
            <div class="relative" ref="columnMenuContainer">
              <button @click="toggleColumnMenu" class="p-2 rounded-full focus:outline-none column-visibility-button">
                <i class="fas fa-eye"></i>
              </button>
              <div v-if="showColumnMenu" class="absolute right-0 mt-2 w-48 rounded-md shadow-lg z-10" :style="{ backgroundColor: 'var(--admin-card-bg-color)' }">
                <div v-for="(visible, key) in columns" :key="key" class="px-4 py-2">
                  <label class="flex items-center">
                    <input type="checkbox" :checked="visible" @change="toggleColumn(key)" class="form-checkbox h-5 w-5" :style="{ color: 'var(--admin-link-indigo-text)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{ formatColumnName(key) }}</span>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Row 2: Search Filters -->
        <div class="flex flex-col sm:flex-row gap-4 mb-6">
          <!-- Client Search -->
          <div class="relative flex-1 min-w-[200px]">
            <BaseInput
              id="search-client"
              v-model="filters.customer_name"
              :placeholder="__('Search client', 'schedula-smart-appointment-booking')"
              @input="debouncedFetchAppointments"
              icon="fas fa-user"
            />
          </div>

          <!-- Employee Search -->
          <div class="relative flex-1 min-w-[200px]">
            <BaseInput
              id="search-employee"
              v-model="filters.staff_name"
              :placeholder="__('Search employee', 'schedula-smart-appointment-booking')"
              @input="debouncedFetchAppointments"
              icon="fas fa-user-tie"
            />
          </div>
        </div>

        <!-- Row 3: Date, Status, and Reset Filters -->
        <div class="flex flex-wrap items-center gap-4">
          <!-- Date Range Filter -->
          <div class="relative w-full sm:w-48">
            <BaseInput
              id="start-date"
              v-model="filters.start_date"
              :placeholder="__('Start Date', 'schedula-smart-appointment-booking')"
              icon="fas fa-calendar"
            />
          </div>
          <div class="relative w-full sm:w-48">
            <BaseInput
              id="end-date"
              v-model="filters.end_date"
              :placeholder="__('End Date', 'schedula-smart-appointment-booking')"
              icon="fas fa-calendar"
            />
          </div>
          
          <!-- Status Filter -->
          <div class="w-full sm:w-48">
            <BaseSelect
              id="status-filter"
              v-model="filters.status"
              :options="statusOptions"
              @change="fetchAppointments"
            />
          </div>

          <!-- Reset Button -->
          <button @click="resetFilters" class="w-full sm:w-auto justify-center inline-flex items-center px-4 py-2.5 text-sm font-medium rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)' }">
            <i class="fas fa-sync-alt mr-2"></i> {{ __('Reset', 'schedula-smart-appointment-booking') }}
          </button>
        </div>
      </div>


      <!-- Bulk Actions -->
      <div class="mt-8">
        <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
          <h3 class="text-xl font-semibold" :style="{ color: 'var(--admin-text-color)' }">{{ __('All Appointments', 'schedula-smart-appointment-booking') }}</h3>
          <button 
          v-if="selectedAppointments.length > 0"
          @click="openBulkDeleteModal"
          :disabled="isDeleting"
          class="inline-flex items-center px-2 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150 ease-in-out bg-red-600 text-white hover:bg-red-700">
          <svg v-if="isDeleting" class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <i v-else class="fas fa-trash mr-2"></i>
          {{ __('Delete Selected', 'schedula-smart-appointment-booking') }} ({{ selectedAppointments.length }})
           </button>
        </div>

        <!-- Appointments List Component -->
        <AppointmentsListe
        :appointments="appointments"
        :loading="loading"
        :error="error"
        :no-results-message="noResultsMessage"
        :columns="columns"
        :sort-by="sortBy"
        :sort-direction="sortDirection"
        :selected-appointments="selectedAppointments"
          @update:sort="handleSort"
          @update:selection="handleSelectionChange"
          @edit-appointment="handleEditAppointment"
          @delete-appointment="handleDeleteAppointment"
        />

      </div>

      <!-- Pagination Controls -->
      <div v-if="totalPages > 1 || itemsPerPageOptions.length > 1" class="flex flex-col sm:flex-row items-center justify-between mt-6 p-4 rounded-lg shadow-md content-card">
        <!-- Left: Rows per page selector -->
        <div class="flex items-center gap-2 mb-4 sm:mb-0">
          <span class="text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Rows per page:', 'schedula-smart-appointment-booking') }}</span>
          <select v-model="itemsPerPage" @change="handleItemsPerPageChange" class="rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm p-1.5 input-field">
            <option v-for="option in itemsPerPageOptions" :key="option" :value="option">{{ option }}</option>
          </select>
        </div>

        <!-- Right: Page info and navigation -->
        <div class="flex flex-wrap items-center justify-center gap-4">
          <!-- Page info -->
          <span class="text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Page', 'schedula-smart-appointment-booking') }} {{ currentPage }} {{ __('of', 'schedula-smart-appointment-booking') }} {{ totalPages }}</span>
          
          <!-- Navigation arrows -->
          <div class="flex items-center gap-1">
            <button 
            @click="goToPage(1)" 
            :disabled="currentPage === 1" 
            class="px-3 py-1.5 rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150"
            :title="__('First Page', 'schedula-smart-appointment-booking')"
          >
            <i class="fas fa-angle-double-left"></i>
          </button>
            <button
              @click="prevPage"
              :disabled="currentPage === 1"
              class="p-2 rounded-md disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150"
              :style="{ 
                backgroundColor: 'var(--admin-input-bg-color)', 
                color: currentPage === 1 ? 'var(--admin-input-text-muted)' : 'var(--admin-input-text-color)'
              }"
              title="__('Previous Page', 'schedula-smart-appointment-booking') ">
              <i class="fas fa-angle-left"></i>
            </button>
            <button
              @click="nextPage"
              :disabled="currentPage === totalPages"
              class="p-2 rounded-md disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150"
              :style="{ 
                backgroundColor: 'var(--admin-input-bg-color)', 
                color: currentPage === totalPages ? 'var(--admin-input-text-muted)' : 'var(--admin-input-text-color)'
              }"
              title="__('Next Page', 'schedula-smart-appointment-booking')">
              <i class="fas fa-angle-right"></i>
            </button>
            <button
              @click="goToPage(totalPages)"
              :disabled="currentPage === totalPages"
              class="p-2 rounded-md disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150"
              :style="{ 
                backgroundColor: 'var(--admin-input-bg-color)', 
                color: currentPage === totalPages ? 'var(--admin-input-text-muted)' : 'var(--admin-input-text-color)'
              }"
              title="__('Last Page', 'schedula-smart-appointment-booking')">
              <i class="fas fa-angle-double-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Unified Delete Confirmation Modal -->
    <transition name="modal-fade">
      <div v-if="showDeleteModal || showBulkDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeDeleteModals">
        <div class="rounded-lg shadow-xl p-6 max-w-sm mx-auto modal-content" @click.stop>
          <h3 class="text-lg font-bold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{ __('Confirm Deletion', 'schedula-smart-appointment-booking') }}</h3>
          
          <div v-if="showDeleteModal">
            <p class="mb-4" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Are you sure you want to delete this appointment?', 'schedula-smart-appointment-booking') }}</p>
          </div>

          <div v-if="showBulkDeleteModal">
            <p class="mb-4" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Are you sure you want to delete the selected', 'schedula-smart-appointment-booking') }} {{ selectedAppointments.length }} {{ __('appointments?', 'schedula-smart-appointment-booking') }}</p>
          </div>

          <div class="flex justify-end space-x-4">
            <button @click="closeDeleteModals" class="px-4 py-2 rounded-md" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)' }">{{ __('Cancel', 'schedula-smart-appointment-booking') }}</button>
            <button @click="showDeleteModal ? confirmDeleteAppointment() : confirmBulkDelete()" 
            :disabled="isDeleting" 
            class="px-4 py-2 rounded-md bg-red-600 text-white hover:bg-red-700 flex items-center justify-center w-24">
              <svg v-if="isDeleting" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span v-else>{{ __('Delete', 'schedula-smart-appointment-booking') }}</span>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick, computed, reactive, onBeforeUnmount } from 'vue';
import { useToast } from '../composables/useToast.js'; // Corrected import path for useToast
import { useRoute } from 'vue-router';
import AppointmentsListe from '../components/appointments/AppointmentsListe.vue';
import AppointmentsForm from '../components/appointments/AppointmentsForm.vue';
import BaseInput from '../components/common/BaseInput.vue';
import BaseSelect from '../components/common/BaseSelect.vue';
import { appointmentsApi } from '../api.js'; // Ensure correct import path
import { Datepicker } from 'flowbite-datepicker'; // For datepicker functionality
import {__} from '@wordpress/i18n';

const appointments = ref([]);
const loading = ref(true);
const error = ref(null);
const { success, error: toastError, info } = useToast(); // Destructure toast functions

const showAppointmentForm = ref(false); // Controls visibility of the full-page form
const selectedAppointment = ref(null); // For editing existing appointments
const modalInitialDate = ref(''); // For pre-filling date when creating from calendar click
const modalInitialTime = ref(''); // For pre-filling time when creating from calendar click

const showDeleteModal = ref(false);
const showBulkDeleteModal = ref(false);
const isDeleting = ref(false);
const appointmentToDelete = ref(null);
const selectedAppointments = ref([]);
const appointmentFormRef = ref(null);

const statusOptions = [
  { value: '', text: __('All Statuses', 'schedula-smart-appointment-booking') },
  { value: 'pending', text: __('Pending', 'schedula-smart-appointment-booking') },
  { value: 'confirmed', text: __('Confirmed', 'schedula-smart-appointment-booking') },
  { value: 'completed', text: __('Completed', 'schedula-smart-appointment-booking') },
  { value: 'cancelled', text: __('Cancelled', 'schedula-smart-appointment-booking') },
];

// Column visibility state
const showColumnMenu = ref(false);
const columnMenuContainer = ref(null);
const columns = reactive({
  id: false,
  date_time: true,
  client: true,
  employee: true,
  service: true,
  price: false,
  phone: false,
  email: false,
  duration: false,
  status: true,
  recurrence: false,
  number_of_persons: false,
});

const toggleColumnMenu = () => {
  showColumnMenu.value = !showColumnMenu.value;
};

const handleClickOutside = (event) => {
  if (showColumnMenu.value && columnMenuContainer.value && !columnMenuContainer.value.contains(event.target)) {
    showColumnMenu.value = false;
  }
};

const toggleColumn = (key) => {
  columns[key] = !columns[key];
};

const formatColumnName = (key) => {
  return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const filters = ref({
  start_date: '',
  end_date: '',
  status: '',
  customer_name: '',
  staff_name: '',
});

const noResultsMessage = ref(__('No appointments found.', 'schedula-smart-appointment-booking'));

const sortBy = ref('start_datetime'); // Default sort by date
const sortDirection = ref('desc'); // Default sort direction

// --- Pagination State ---
const currentPage = ref(1);
const itemsPerPageOptions = [5, 10, 20, 50]; // Options for rows per page
const itemsPerPage = ref(itemsPerPageOptions[1]); // Default to 10 items per page
const totalAppointments = ref(0); // Total number of appointments (from API)

// Computed property for total pages
const totalPages = computed(() => {
  return Math.ceil(totalAppointments.value / itemsPerPage.value);
});

// Debounce for search inputs
let debounceTimeout = null;
const debouncedFetchAppointments = () => {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    currentPage.value = 1; // Reset to first page on new search/filter
    fetchAppointments();
  }, 300); // Adjust debounce time as needed
};

// Fetch appointments based on current filters and pagination
const fetchAppointments = async () => {
  loading.value = true;
  error.value = null;

  try {
    const params = {
      ...filters.value,
      page: currentPage.value,
      per_page: itemsPerPage.value,
      sort_by: sortBy.value,
      sort_direction: sortDirection.value,
    };
  
    const response = await appointmentsApi.getAppointments(params);
    appointments.value = response.data.appointments;
    totalAppointments.value = response.data.total_items; // Update total count

    if (appointments.value.length === 0 && (filters.value.customer_name || filters.value.staff_name || filters.value.status || filters.value.start_date || filters.value.end_date)) {
      noResultsMessage.value = __('No appointments found matching your criteria.', 'schedula-smart-appointment-booking');
    } else if (appointments.value.length === 0) {
      noResultsMessage.value = __('No appointments found. Click "Create an Appointment" to get started!', 'schedula-smart-appointment-booking');
    }
  } catch (err) {
    error.value = err.response?.data?.message || err.message || 'An error occurred while fetching appointments.';
    toastError(error.value); // Use toast for error
    console.error('Error fetching appointments:', err);
  } finally {
    loading.value = false;
  }
};

// --- Pagination Methods ---
const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
    fetchAppointments();
  }
};

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
    fetchAppointments();
  }
};

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
    fetchAppointments();
  }
};

const handleItemsPerPageChange = () => {
  currentPage.value = 1; // Reset to first page when items per page changes
  fetchAppointments();
};

const handleSort = ({ sortBy: column, sortDirection: direction }) => {
  // Ensure we're setting the reactive refs correctly
  sortBy.value = column;
  sortDirection.value = direction;
  currentPage.value = 1; // Reset to first page on new sort
  fetchAppointments(); // Re-fetch with new sorting
};

const handleSelectionChange = (appointmentId, isSelected) => {
  if (isSelected) {
    if (!selectedAppointments.value.includes(appointmentId)) {
      selectedAppointments.value.push(appointmentId);
    }
  } else {
    const index = selectedAppointments.value.indexOf(appointmentId);
    if (index > -1) {
      selectedAppointments.value.splice(index, 1);
    }
  }
};

const openBulkDeleteModal = () => {
  if (selectedAppointments.value.length === 0) {
    info(__('Please select at least one appointment to delete.', 'schedula-smart-appointment-booking')); // Use toast for info
    return;
  }
  showBulkDeleteModal.value = true;
};

// Handle form submission from AppointmentsForm
const handleAppointmentFormSubmit = async (formData) => {
  try {
    if (formData.id) {
      await appointmentsApi.updateAppointment(formData.id, formData);
      success(__('Appointment updated successfully!', 'schedula-smart-appointment-booking')); // Use toast for success
    } else {
      await appointmentsApi.createAppointment(formData);
      success(__('Appointment created successfully!', 'schedula-smart-appointment-booking')); // Use toast for success
    }
    showAppointmentForm.value = false; // Close form on success
    selectedAppointment.value = null; // Clear selected appointment
    modalInitialDate.value = ''; // Clear any pre-set date/time
    modalInitialTime.value = '';
    // After saving, reset to page 1 and refetch to see the new/updated item
    currentPage.value = 1; 
    fetchAppointments(); 
  } catch (err) {
    const errorMessage = err.response?.data?.message || err.message || __('Error saving appointment.', 'schedula-smart-appointment-booking');
    toastError(errorMessage); // Use toast for error
    console.error('Error saving appointment:', err);
  } finally {
    appointmentFormRef.value?.stopSaving();
  }
};

// Open form for creating a new appointment (full page)
const openCreateAppointmentForm = () => {
  selectedAppointment.value = null; // Ensure no existing data is passed
  modalInitialDate.value = ''; // Clear any pre-set date/time
  modalInitialTime.value = '';
  showAppointmentForm.value = true; // Show the full-page form
};

// Open form for editing an existing appointment (full page)
const handleEditAppointment = (appointment) => {
  selectedAppointment.value = { ...appointment }; // Pass a copy of the appointment data
  // The AppointmentsForm will extract date/time from start_datetime
  modalInitialDate.value = ''; // Not strictly needed as form will get from initialData
  modalInitialTime.value = '';
  showAppointmentForm.value = true; // Show the full-page form
};

// Close appointment form (full page)
const closeAppointmentForm = () => {
  showAppointmentForm.value = false; // Hide the full-page form
  selectedAppointment.value = null;
  modalInitialDate.value = '';
  modalInitialTime.value = '';
  // Re-initialize datepickers when returning to list view, in case they were affected
  nextTick(() => {
    initDatepickers();
  });
};

// Open delete confirmation modal
const handleDeleteAppointment = (appointment) => {
  appointmentToDelete.value = appointment;
  showDeleteModal.value = true;
};

// Confirm and execute deletion
const confirmDeleteAppointment = async () => {
  if (!appointmentToDelete.value) return;
  isDeleting.value = true;
  try {
    await appointmentsApi.deleteAppointment(appointmentToDelete.value.id);
    success(__('Appointment deleted successfully!', 'schedula-smart-appointment-booking')); // Use toast for success
    // After deletion, check if current page is empty and adjust if needed
    if (appointments.value.length === 1 && currentPage.value > 1) {
      currentPage.value--;
    }
    fetchAppointments(); // Refresh list
  } catch (err) {
    console.error("Full Axios error object:", err);
    let errorMessage = __('An error occurred while deleting the appointment.', 'schedula-smart-appointment-booking');
    if (err.response) {
        console.error("Error Response Data:", err.response.data);
        console.error("Error Response Status:", err.response.status);
        errorMessage = err.response.data.message || JSON.stringify(err.response.data);
    } else if (err.request) {
        console.error("Error Request:", err.request);
        errorMessage = __('The server did not respond. Please check your connection.', 'schedula-smart-appointment-booking');
    } else {
        console.error('Error Message:', err.message);
        errorMessage = err.message;
    }
    toastError(errorMessage); // Use toast for error
  } finally {
    isDeleting.value = false;
    closeDeleteModals();
  }
};

const confirmBulkDelete = async () => {
  isDeleting.value = true;
  try {
    for (const appointmentId of selectedAppointments.value) {
      await appointmentsApi.deleteAppointment(appointmentId);
    }
    success(__('Selected appointments deleted successfully!', 'schedula-smart-appointment-booking')); // Use toast for success
    const deletedCount = selectedAppointments.value.length;
    selectedAppointments.value = []; // Clear selection
    
    // After deletion, check if current page is empty and adjust if needed
    if (appointments.value.length === deletedCount && currentPage.value > 1) {
      currentPage.value--;
    }
    fetchAppointments(); // Refresh list
  } catch (err) {
    console.error("Full Axios error object:", err);
    let errorMessage = __('An error occurred while deleting appointments.', 'schedula-smart-appointment-booking');
    if (err.response) {
        console.error("Error Response Data:", err.response.data);
        console.error("Error Response Status:", err.response.status);
        errorMessage = err.response.data.message || JSON.stringify(err.response.data);
    } else if (err.request) {
        console.error("Error Request:", err.request);
        errorMessage = __('The server did not respond. Please check your connection.', 'schedula-smart-appointment-booking');
    } else {
        console.error('Error Message:', err.message);
        errorMessage = err.message;
    }
    toastError(errorMessage); // Use toast for error
  } finally {
    isDeleting.value = false;
    closeDeleteModals();
  }
};

const closeDeleteModals = () => {
  showDeleteModal.value = false;
  showBulkDeleteModal.value = false;
  appointmentToDelete.value = null;
};

const resetFilters = () => {
  filters.value = {
    start_date: '',
    end_date: '',
    status: '',
    customer_name: '',
    staff_name: '',
  };
  currentPage.value = 1; // Reset pagination on filter reset
  sortBy.value = 'start_datetime'; // Reset sort to default
  sortDirection.value = 'desc'; // Reset sort direction to default
  // Re-initialize datepickers to clear their values
  initDatepickers();
  fetchAppointments();
};

// Initialize Flowbite Datepickers
const initDatepickers = () => {
  const startDateEl = document.getElementById('start-date');
  const endDateEl = document.getElementById('end-date');

  // Destroy existing instances to prevent re-initialization issues
  if (startDateEl && startDateEl._datepicker) {
    startDateEl._datepicker.destroy();
  }
  if (endDateEl && endDateEl._datepicker) {
    endDateEl._datepicker.destroy();
  }

  if (startDateEl) {
    const startDatepicker = new Datepicker(startDateEl, {
      autohide: true,
      format: 'yyyy-mm-dd',
    });
    // Store instance on element for later destruction
    startDateEl._datepicker = startDatepicker;
    startDateEl.addEventListener('changeDate', (event) => {
      const date = event.detail.date;
      filters.value.start_date = date ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}` : '';
      currentPage.value = 1; // Reset to first page on date change
      fetchAppointments();
    });
  }
  if (endDateEl) {
    const endDatepicker = new Datepicker(endDateEl, {
      autohide: true,
      format: 'yyyy-mm-dd',
    });
    // Store instance on element for later destruction
    endDateEl._datepicker = endDatepicker;
    endDateEl.addEventListener('changeDate', (event) => {
      const date = event.detail.date;
      filters.value.end_date = date ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}` : '';
      currentPage.value = 1; // Reset to first page on date change
      fetchAppointments();
    });
  }
};

const route = useRoute();

onMounted(() => {
  // Apply status filter from query if present
  if (route.query.status) {
    filters.value.status = route.query.status;
  }
  fetchAppointments();
  initDatepickers();
  document.addEventListener('mousedown', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleClickOutside);
});

// Watch for changes in the route query (for dynamic navigation from dashboard)
watch(() => route.query.status, (newStatus) => {
  if (newStatus) {
    filters.value.status = newStatus;
    currentPage.value = 1; // Reset to first page when status filter changes from route
    fetchAppointments();
  }
});

</script>

<style scoped>
/* No modal-fade transition for the form itself, as it's now a full page */
/* Modal transition styles for Delete Confirmation Modal (only) */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

/* Optional: Add a slight scale/slide effect for the modal content */
.modal-fade-enter-active > div,
.modal-fade-leave-active > div {
  transition: transform 0.3s ease;
}

.modal-fade-enter-from > div,
.modal-fade-leave-to > div {
  transform: scale(0.95);
}

/* Datepicker specific styles to ensure good contrast */
/* These styles are often needed to override Flowbite's defaults for better visibility */
.datepicker-grid .datepicker-cell.selected,
.datepicker-grid .datepicker-cell.range-start,
.datepicker-grid .datepicker-cell.range-end,
.datepicker-grid .datepicker-cell.range-middle,
.datepicker-grid .datepicker-cell.range-start.selected,
.datepicker-grid .datepicker-cell.range-end.selected {
  color: var(--admin-card-text-color) !important;
}

/* Specific styles for selected/hovered cells to ensure good contrast */
.datepicker-grid .datepicker-cell.selected,
.datepicker-grid .datepicker-cell.range-start,
.datepicker-grid .datepicker-cell.range-end {
  background-color: var(--admin-link-indigo-bg) !important; /* Using indigo for selected dates */
  color: var(--admin-link-indigo-text) !important;
}

.datepicker-grid .datepicker-cell.range-middle {
  background-color: var(--admin-input-border-color) !important; /* Lighter border color for range middle */
  color: var(--admin-card-text-color) !important;
}

.datepicker-grid .datepicker-cell:hover:not(.selected):not(.range-middle) {
  background-color: var(--admin-input-border-color) !important; /* Lighter border color for hover */
}


input[type="text"]::-webkit-calendar-picker-indicator {
  display: none;
}
input[type="text"]::-moz-calendar-picker-indicator {
  display: none;
}
input[type="text"]::-ms-calendar-picker-indicator {
  display: none;
}

.column-visibility-button:hover {
  background-color: var(--admin-border-color);
}
</style>
