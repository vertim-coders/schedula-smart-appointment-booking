<template>
  <div>
    <h1 class="text-4xl font-semibold mb-8 text-left" :style="{ color: 'var(--admin-text-color)' }">{{ __('Payments Overview', 'schedula-smart-appointment-booking') }}</h1>

    <!-- Filters Section -->
    <div class="p-6 rounded-lg shadow-md mb-6 content-card">
      <h3 class="text-xl font-semibold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{ __('Filter Payments', 'schedula-smart-appointment-booking') }}</h3>
      
      <!-- Row 1: Search Filters (Client, Employee, Service, Payment Type) -->
      <div class="flex flex-wrap items-center gap-4 mb-4">
        <!-- Client Search -->
        <div class="relative flex-1 min-w-[200px]">
          <BaseInput
            id="search-client"
            v-model="filters.customer_name"
            :placeholder="__('Search by Client Name', 'schedula-smart-appointment-booking')"
            @input="debouncedFetchPayments"
            icon="fas fa-user"  
          />
        </div>

        <!-- Employee Search -->
        <div class="relative flex-1 min-w-[200px]">
          <BaseInput
            id="search-employee"
            v-model="filters.staff_name"
            :placeholder="__('Search by Employee Name', 'schedula-smart-appointment-booking')"
            @input="debouncedFetchPayments"
            icon="fas fa-user-tie"
          />
        </div>

        <!-- Service Search -->
        <div class="relative flex-1 min-w-[200px]">
          <BaseInput
            id="search-service"
            v-model="filters.service_title"
            :placeholder="__('Search by Service Title', 'schedula-smart-appointment-booking')"
            @input="debouncedFetchPayments"
            icon="fas fa-cut"
          />
        </div>

        <!-- Payment Type Filter -->
        <div class="w-full sm:w-48">
          <BaseSelect
            id="payment-type-filter"
            v-model="filters.payment_type"
            :options="paymentTypeOptions"
            @change="fetchPayments"
          />
        </div>
      </div>

      <!-- Row 2: Date Range Picker, Column Visibility and Reset Button -->
      <div class="flex flex-wrap items-center justify-between gap-4">
          <!-- Date Range Filter -->
          <div class="flex items-center gap-4">
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
          </div>

          <div class="flex items-center gap-4">
              <!-- Column Visibility Toggle -->
              <div class="relative" ref="columnMenuContainer">
                <button @click="toggleColumnMenu" class="p-2 rounded-full focus:outline-none" :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-input-text-color)' }">
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
              <!-- Reset Filters Button -->
              <button @click="resetFilters"
                      :disabled="isResetting"
                      class="px-4 py-2.5 rounded-xl shadow-sm text-sm font-medium flex items-center disabled:opacity-50 disabled:cursor-not-allowed" 
                      :style="{ 
                        backgroundColor: 'var(--admin-button-secondary-bg)', 
                        color: 'var(--admin-button-secondary-text)',
                      }">
                <svg v-if="isResetting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <i v-else class="fas fa-sync-alt mr-2"></i>
                {{ isResetting ? __('Resetting...', 'schedula-smart-appointment-booking') : __('Reset Filters', 'schedula-smart-appointment-booking') }}
              </button>
          </div>
      </div>
    </div>

    <!-- Bulk Actions (New location) -->
    <div class="flex justify-end mb-4 pr-6">
      <button 
        v-if="selectedPayments.length > 0"
        @click="openBulkDeleteModal"
        :disabled="isDeleting"
        class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150 ease-in-out bg-red-600 text-white hover:bg-red-700">
        <i v-if="isDeleting" class="animate-spin h-5 w-5 mr-2 fas fa-spinner"></i>
        <i v-else class="fas fa-trash mr-2"></i>
        {{ __('Delete Selected', 'schedula-smart-appointment-booking') }} ({{ selectedPayments.length }})
      </button>
    </div>

    <!-- Payments List Component -->
    <PaymentList
      :payments="payments"
      :loading="loading"
      :error="error"
      :total-earnings="totalEarnings"
      :columns="columns"
      :sortBy="sortBy"
      :sortDirection="sortDirection"
      :selectedPayments="selectedPayments"
      @delete-payment="confirmDeletePayment"
      @open-receipt="handleOpenReceipt"
      @update:sort="handleSortChange"
      @update:selection="handleSelectionChange"
    />

    <!-- Receipt Modal Component -->
    <PaymentReceiptModal
      :show="showReceiptModal"
      :loading="loadingReceipt"
      :error="receiptError"
      :receipt-data="currentReceipt"
      @close="closeReceiptModal"
      @download-success="handleDownloadSuccess"
      @download-error="handleDownloadError"
    />

    <!-- Delete Confirmation Modal (Unified for single and bulk delete) -->
    <transition name="modal-fade">
      <div v-if="showDeleteConfirmModal || showBulkDeleteModal"
           class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
           @click.self="cancelDelete">
        <div class="rounded-lg shadow-xl w-full max-w-sm mx-4 my-8 p-6 relative modal-content">
          <h3 class="text-xl font-bold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{ showDeleteConfirmModal ? __('Confirm Deletion', 'schedula-smart-appointment-booking') : __('Confirm Bulk Deletion', 'schedula-smart-appointment-booking') }}</h3>
          <p v-if="showDeleteConfirmModal" class="mb-6" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Are you sure you want to delete this payment? This will also delete the associated appointment.', 'schedula-smart-appointment-booking') }}</p>
          <p v-if="showBulkDeleteModal" class="mb-6" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Are you sure you want to delete the selected', 'schedula-smart-appointment-booking') }} {{ selectedPayments.length }} {{ __('payments? This will also delete their associated appointments.', 'schedula-smart-appointment-booking') }}</p>
          <div class="flex justify-end space-x-3">
            <button @click="cancelDelete"
            class="px-4 py-2 rounded-md" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)', border: '1px solid var(--admin-input-border-color)' }">
              {{ __('Cancel', 'schedula-smart-appointment-booking') }}
            </button>
            <button @click="showDeleteConfirmModal ? executeDelete() : executeBulkDelete()" :disabled="isDeleting" class="px-4 py-2 rounded-md bg-red-600 text-white hover:bg-red-700 flex items-center justify-center w-24">
              <svg v-if="isDeleting" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span v-else>{{ isDeleting ? __('Deleting...', 'schedula-smart-appointment-booking') : __('Delete', 'schedula-smart-appointment-booking') }}</span>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, reactive, onBeforeUnmount, watch } from 'vue';
import { paymentsApi } from '../api.js'; // Only paymentsApi needed here now
import { Datepicker } from 'flowbite-datepicker';
import debounce from 'lodash.debounce';; // For debouncing search inputs
import { useGlobalSettings } from '../composables/useGlobalSettings.js'; // Import useGlobalSettings
import { useAdminAppearance } from '@/admin/composables/useAdminAppearance.js'; // Import useAdminAppearance
import { useToast } from '../composables/useToast.js'; // Import useToast
import { __, sprintf } from '@wordpress/i18n';

// Import the new child components
import PaymentList from '../components/payments/PaymentList.vue';
import PaymentReceiptModal from '../components/payments/PaymentReceiptModal.vue';
import BaseInput from '../components/common/BaseInput.vue';
import BaseSelect from '../components/common/BaseSelect.vue';

// Destructure formatPrice from useGlobalSettings and appearanceSettings from useAdminAppearance
const { formatPrice, generalSettings, fetchGlobalSettings } = useGlobalSettings();
const { appearanceSettings, adminCustomStyles } = useAdminAppearance();
const { success, error: toastError, info } = useToast(); // Destructure toast functions

// Reactive state variables
const payments = ref([]);
const loading = ref(true);
const error = ref(null);
const totalEarnings = ref(0);

const sortBy = ref('payment_date'); // Default sort by payment_date
const sortDirection = ref('desc'); // Default sort direction
const selectedPayments = ref([]);

// NEW: Loading state for reset button
const isResetting = ref(false);

const paymentTypeOptions = [
  { value: '', text: __('All Types', 'schedula-smart-appointment-booking') },
  { value: 'cash', text: __('Cash', 'schedula-smart-appointment-booking') },
  { value: 'card', text: __('Card', 'schedula-smart-appointment-booking') },
  { value: 'bank_transfer', text: __('Bank Transfer', 'schedula-smart-appointment-booking') },
  { value: 'other', text: __('Other', 'schedula-smart-appointment-booking') },
];

const filters = ref({
  start_date: '',
  end_date: '',
  customer_name: '',
  staff_name: '',
  service_title: '',
  payment_type: '', 
});

// Removed pageMessage and pageMessageType as toasts are now used
// const pageMessage = ref(''); 
// const pageMessageType = ref(''); 

const showReceiptModal = ref(false);
const currentReceipt = ref(null);
const loadingReceipt = ref(false);
const receiptError = ref(null);

const showDeleteConfirmModal = ref(false); // For single delete
const showBulkDeleteModal = ref(false); // For bulk delete
const paymentToDeleteId = ref(null);
const isDeleting = ref(false);

// Column visibility state
const showColumnMenu = ref(false);
const columnMenuContainer = ref(null);
const columns = reactive({
  id: true, // Changed to true by default for payments
  date_time: true,
  appointment_date_time: true,
  type: true,
  client: true,
  employee: true,
  service: true,
  amount: true,
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


// --- API Calls ---
const fetchPayments = async () => {
  loading.value = true;
  error.value = null; 
  try {
    const response = await paymentsApi.getPayments({
      ...filters.value,
      sort_by: sortBy.value,
      sort_direction: sortDirection.value,
    });
    payments.value = response.data;

    // Calculate total earnings from fetched payments (still done here)
    totalEarnings.value = payments.value.reduce((sum, payment) => sum + parseFloat(payment.amount), 0);

  } catch (err) {
    error.value = err.response?.data?.message || err.message || __('Failed to fetch payments.', 'schedula-smart-appointment-booking');
    toastError(error.value); // Use toast for error
    console.error('Error fetching payments:', err);
  } finally {
    loading.value = false;
  }
};

const debouncedFetchPayments = debounce(fetchPayments, 500); // Debounce search inputs

// --- Filter Actions ---
const resetFilters = async () => {
  isResetting.value = true; // Set loading state to true
  filters.value = {
    start_date: '',
    end_date: '',
    customer_name: '',
    staff_name: '',
    service_title: '',
    payment_type: '',
  };
  sortBy.value = 'payment_date'; // Reset sort to default
  sortDirection.value = 'desc'; // Reset sort direction to default
  selectedPayments.value = []; // Clear any selected payments
  initDatepickers(); // Re-initialize datepickers to clear their values
  await fetchPayments(); // Fetch payments with cleared filters

  // Add a small delay to ensure the loader is visible
  await new Promise(resolve => setTimeout(resolve, 500)); 
  isResetting.value = false; // Set loading state to false
  success(__('Filters reset successfully!', 'schedula-smart-appointment-booking')); // Toast for reset success
};

// --- Receipt Modal Logic (Handlers for PaymentList events) ---
const handleOpenReceipt = async (paymentId) => {
  showReceiptModal.value = true;
  loadingReceipt.value = true;
  receiptError.value = null;
  currentReceipt.value = null;
  try {
    const response = await paymentsApi.getPaymentReceipt(paymentId);
    currentReceipt.value = response.data;
  } catch (err) {
    receiptError.value = err.response?.data?.message || err.message || __('Failed to fetch receipt details.', 'schedula-smart-appointment-booking');
    toastError(receiptError.value); // Toast for receipt fetch error
    console.error('Error fetching receipt:', err);
  } finally {
    loadingReceipt.value = false;
  }
};

const closeReceiptModal = () => {
  showReceiptModal.value = false;
  currentReceipt.value = null;
  receiptError.value = null;
};

const handleDownloadSuccess = (msg) => {
  success(msg); // Use success toast
};

const handleDownloadError = (msg) => {
  toastError(msg); // Use error toast
};

// --- Delete Confirmation Logic (Handlers for PaymentList events) ---
const confirmDeletePayment = (paymentId) => {
  paymentToDeleteId.value = paymentId;
  showDeleteConfirmModal.value = true;
  showBulkDeleteModal.value = false; // Ensure bulk modal is closed
};

const openBulkDeleteModal = () => {
  if (selectedPayments.value.length === 0) {
    info('Please select at least one payment to delete.'); // Use info toast
    return;
  }
  showBulkDeleteModal.value = true;
  showDeleteConfirmModal.value = false; // Ensure single delete modal is closed
};

const cancelDelete = () => {
  showDeleteConfirmModal.value = false;
  showBulkDeleteModal.value = false;
  paymentToDeleteId.value = null;
  // selectedPayments.value = []; // Don't clear selected here, allow user to cancel deletion without losing selection
};

const handleSortChange = ({ sortBy: newSortBy, sortDirection: newSortDirection }) => {
  sortBy.value = newSortBy;
  sortDirection.value = newSortDirection;
  fetchPayments(); // Re-fetch payments with new sorting
};

const handleSelectionChange = (paymentId, isChecked) => {
  if (isChecked) {
    if (!selectedPayments.value.includes(paymentId)) {
      selectedPayments.value.push(paymentId);
    }
  } else {
    const index = selectedPayments.value.indexOf(paymentId);
    if (index > -1) {
      selectedPayments.value.splice(index, 1);
    }
  }
};

const executeDelete = async () => {
  if (!paymentToDeleteId.value) return;
  isDeleting.value = true;
  try {
    await paymentsApi.deletePayment(paymentToDeleteId.value);
    success(__('Payment and associated appointment deleted successfully!', 'schedula-smart-appointment-booking')); // Use success toast
    selectedPayments.value = selectedPayments.value.filter(id => id !== paymentToDeleteId.value); // Remove from selection
    fetchPayments(); // Refresh the list
  } catch (err) {
    toastError(err.response?.data?.message || err.message || __('Error deleting payment.', 'schedula-smart-appointment-booking')); // Use error toast
    console.error('Error deleting payment:', err);
  } finally {
    isDeleting.value = false;
    cancelDelete(); // Close the modal
  }
};

const executeBulkDelete = async () => {
  if (selectedPayments.value.length === 0) return;
  isDeleting.value = true;
  try {
    for (const paymentId of selectedPayments.value) {
      await paymentsApi.deletePayment(paymentId);
    }
    success(
      sprintf(
        __('Successfully deleted %d payments and their associated appointments!', 'schedula-smart-appointment-booking'),
        selectedPayments.value.length
      )
    );
    selectedPayments.value = []; // Clear selection after successful bulk delete
    fetchPayments(); // Refresh the list
  } catch (err) {
    toastError(err.response?.data?.message || err.message || __('Error deleting selected payments.', 'schedula-smart-appointment-booking')); // Use error toast
    console.error('Error deleting bulk payments:', err);
  } finally {
    isDeleting.value = false;
    cancelDelete(); // Close the modal
  }
};

// Initialize Flowbite Datepickers
const initDatepickers = () => {
  const startDateEl = document.getElementById('start-date');
  const endDateEl = document.getElementById('end-date');

  // Destroy existing instances if they exist
  if (startDateEl && startDateEl._datepicker) {
    startDateEl._datepicker.destroy();
    startDateEl._datepicker = null;
  }
  if (endDateEl && endDateEl._datepicker) {
    endDateEl._datepicker.destroy();
    endDateEl._datepicker = null;
  }

  // Initialize new instances
  if (startDateEl) {
    const startDatepicker = new Datepicker(startDateEl, {
      autohide: true,
      format: 'yyyy-mm-dd',
    });
    // Store the instance directly on the element for later access/destruction
    startDateEl._datepicker = startDatepicker; 
    startDateEl.addEventListener('changeDate', (event) => {
      const date = event.detail.date;
      filters.value.start_date = date ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}` : '';
      fetchPayments();
    });
  }
  if (endDateEl) {
    const endDatepicker = new Datepicker(endDateEl, {
      autohide: true,
      format: 'yyyy-mm-dd',
    });
    // Store the instance directly on the element for later access/destruction
    endDateEl._datepicker = endDatepicker;
    endDateEl.addEventListener('changeDate', (event) => {
      const date = event.detail.date;
      filters.value.end_date = date ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}` : '';
      fetchPayments();
    });
  }
};

// --- Lifecycle Hooks ---
onMounted(() => {
  fetchGlobalSettings();
  fetchPayments(); // Initial fetch of payments
  nextTick(() => {
    initDatepickers(); // Initialize datepicker after component is mounted
  });
  document.addEventListener('mousedown', handleClickOutside);
});

watch(generalSettings, (newVal, oldVal) => {
  if (newVal.currencySymbol !== oldVal.currencySymbol) {
    fetchPayments();
  }
}, { deep: true });

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleClickOutside);
  // Ensure datepickers are destroyed on unmount
  const startDateEl = document.getElementById('start-date');
  const endDateEl = document.getElementById('end-date');
  if (startDateEl && startDateEl._datepicker) {
    startDateEl._datepicker.destroy();
    startDateEl._datepicker = null;
  }
  if (endDateEl && endDateEl._datepicker) {
    endDateEl._datepicker.destroy();
    endDateEl._datepicker = null;
  }
});
</script>

<style scoped>
/* Modal transition styles (kept here for the delete confirmation modal) */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
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

/* Hide the native date picker indicator for text inputs */
/* This is crucial to remove the small calendar icon */
input[type="text"]::-webkit-calendar-picker-indicator {
  display: none;
}
input[type="text"]::-moz-calendar-picker-indicator {
  display: none;
}
input[type="text"]::-ms-calendar-picker-indicator {
  display: none;
}
</style>
