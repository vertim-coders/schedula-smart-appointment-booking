<template>
  <div class="p-6 rounded-lg shadow-md content-card">
    <h3 class="text-xl font-semibold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{ title }}</h3>
    <div v-if="error" class="px-4 py-3 rounded relative mb-4" role="alert" :style="{ backgroundColor: 'var(--admin-suggestion-red-bg)', borderColor: 'var(--admin-border-color)', color: 'var(--admin-suggestion-red-text)' }">
      <span class="block sm:inline">{{ __('Error: ', 'schedula-smart-appointment-booking') }}{{ error }}</span>
    </div>
    <div class="relative shadow-md sm:rounded-lg content-card">
      <div class="overflow-x-auto">
      <table class="w-full text-sm text-left text-gray-500">
        <thead class="text-xs text-gray-700 uppercase hidden sm:table-header-group">
          <tr>
            <th v-if="columns.id" scope="col" class="py-3 px-2 w-16 cursor-pointer" @click="handleSort('payment_id')">{{ __('ID', 'schedula-smart-appointment-booking') }}
              <i v-if="sortBy === 'payment_id'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
              <i v-else class="fas fa-sort"></i>
            </th>
            <th v-if="columns.date_time" scope="col" class="py-3 px-2 w-28 cursor-pointer" @click="handleSort('payment_date')">{{ __('Payment Date', 'schedula-smart-appointment-booking') }}
              <i v-if="sortBy === 'payment_date'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
              <i v-else class="fas fa-sort"></i>
            </th>
            <th v-if="columns.appointment_date_time" scope="col" class="py-3 px-2 w-28 cursor-pointer" @click="handleSort('start_datetime')">{{ __('Appt. Date', 'schedula-smart-appointment-booking') }}
              <i v-if="sortBy === 'start_datetime'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
              <i v-else class="fas fa-sort"></i>
            </th>
            <th v-if="columns.type" scope="col" class="py-3 px-2 w-20">{{ __('Type', 'schedula-smart-appointment-booking') }}</th>
            <th v-if="columns.client" scope="col" class="py-3 px-2 w-24 cursor-pointer" @click="handleSort('customer_full_name')">{{ __('Client', 'schedula-smart-appointment-booking') }}
              <i v-if="sortBy === 'customer_full_name'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
              <i v-else class="fas fa-sort"></i>
            </th>
            <th v-if="columns.employee" scope="col" class="py-3 px-2 w-20 cursor-pointer" @click="handleSort('staff_name')">{{ __('Employee', 'schedula-smart-appointment-booking') }}
              <i v-if="sortBy === 'staff_name'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
              <i v-else class="fas fa-sort"></i>
            </th>
            <th v-if="columns.service" scope="col" class="py-3 px-1 w-20 cursor-pointer" @click="handleSort('service_title')">{{ __('Service', 'schedula-smart-appointment-booking') }}
              <i v-if="sortBy === 'service_title'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
              <i v-else class="fas fa-sort"></i>
            </th>
            <th v-if="columns.amount" scope="col" class="py-3 px-2 w-28 cursor-pointer" @click="handleSort('amount')">
                {{ __('Total Price', 'schedula-smart-appointment-booking') }}
                <i v-if="sortBy === 'amount'" :class="['fas ml-1', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                <i v-else class="fas fa-sort ml-1"></i>
            </th>
            <th scope="col" class="py-3 px-2 w-16">{{ __('Actions', 'schedula-smart-appointment-booking') }}</th>
            <th scope="col" class="py-3 px-2 w-10">
              <input type="checkbox" @change="toggleAll" :checked="allSelected" :indeterminate="isIndeterminate" 
                class="h-4 w-4 rounded form-checkbox" 
                :style="{borderColor: 'black', backgroundColor: allSelected ? 'var(--admin-link-indigo-bg)' : 'white'}" />
            </th>
          </tr>
        </thead>
        <tbody>
            <!-- Loading state -->
            <tr v-if="loading" class="border-b">
              <td :colspan="visibleColumnCount" class="py-4 px-2 text-center h-[250px]">
                <AppLoader />
              </td>
            </tr>
            
            <!-- No payments message -->
            <tr v-else-if="payments.length === 0" class="border-b">
              <td :colspan="visibleColumnCount" class="py-4 px-2 text-center">
                <div class="px-4 py-3 rounded relative" :style="{ backgroundColor: 'var(--admin-input-border-color)', borderColor: 'var(--admin-border-color)', color: 'var(--admin-card-text-color)' }">
                  {{ __('No payments found for the selected filters.', 'schedula-smart-appointment-booking') }}
                </div>
              </td>
            </tr>
            
            <!-- Payments list -->
            <tr v-else v-for="payment in payments" :key="payment.payment_id" :class="['border-b', 'hover:bg-indigo-100', { 'bg-indigo-50': isSelected(payment.payment_id) }]">
              <td v-if="columns.id" class="py-4 px-2 sm:table-cell hidden">{{ payment.payment_id }}</td>
              <td v-if="columns.date_time" class="py-4 px-2 sm:table-cell hidden">
                <div class="flex flex-col text-xs">
                  <span class="font-medium">{{ formatDate(payment.payment_date) }}</span>
                  <span>{{ formatTime(payment.payment_date) }}</span>
                </div>
              </td>
              <td v-if="columns.appointment_date_time" class="py-4 px-2 sm:table-cell hidden">
                <div class="flex flex-col text-xs">
                  <span class="font-medium">{{ formatDate(payment.start_datetime) }}</span>
                  <span>{{ formatTime(payment.start_datetime) }}</span>
                </div>
              </td>
              <td v-if="columns.type" class="py-4 px-2 sm:table-cell hidden text-xs">
                <div class="whitespace-nowrap" :title="payment.display_payment_type">
                  {{ payment.display_payment_type }}
                </div>
              </td>
              <td v-if="columns.client" class="py-4 px-2 sm:table-cell hidden">
                <div class="break-words max-w-24" :title="payment.customer_full_name">
                  {{ payment.customer_full_name }}
                </div>
              </td>
              <td v-if="columns.employee" class="py-4 px-2 sm:table-cell hidden">
                <div class="break-words max-w-20" :title="payment.staff_name || __('N/A', 'schedula-smart-appointment-booking')">
                  {{ payment.staff_name || __('N/A', 'schedula-smart-appointment-booking') }}
                </div>
              </td>
              <td v-if="columns.service" class="py-4 px-1 sm:table-cell hidden">
                <div class="break-words" :title="payment.service_title || __('N/A', 'schedula-smart-appointment-booking')">
                  {{ payment.service_title || __('N/A', 'schedula-smart-appointment-booking') }}
                </div>
              </td>
              <td v-if="columns.amount" class="py-4 px-1 sm:table-cell hidden">{{ formatPrice(payment.amount) }}</td>
              <td class="py-4 px-2 sm:table-cell hidden">
                <div class="flex items-center justify-center space-x-1">
                  <button @click.stop="emit('open-receipt', payment.payment_id)"
                          class="text-indigo-600 hover:text-indigo-900 text-sm p-1">
                    <i class="fas fa-receipt"></i>
                  </button>
                  <button @click.stop="emit('delete-payment', payment.payment_id)"
                          class="text-red-600 hover:text-red-900 text-sm p-1">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
              <td class="py-4 px-2 w-10 sm:table-cell hidden">
                <input 
                  type="checkbox" 
                  :checked="isSelected(payment.payment_id)" 
                  @change="toggleSelection(payment.payment_id, $event.target.checked)" 
                  class="h-4 w-4 rounded form-checkbox" 
                  :style="{borderColor: 'black', backgroundColor: isSelected(payment.payment_id) ? 'var(--admin-link-indigo-bg)' : 'white'}"
                  @click.stop
                />
              </td>
              
              <!-- Mobile view -->
              <td class="block sm:hidden p-2 border-t">
                <div class="flex items-start space-x-4">
                  <div class="flex-grow">
                    <div class="flex flex-col space-y-1">
                      <div v-if="columns.id" class="block"><strong>{{ __('ID:', 'schedula-smart-appointment-booking') }}</strong> {{ payment.payment_id }}</div>
                      <div v-if="columns.date_time" class="block"><strong>{{ __('Payment Date:', 'schedula-smart-appointment-booking') }}</strong> {{ formatDate(payment.payment_date) }} {{ formatTime(payment.payment_date) }}</div>
                      <div v-if="columns.appointment_date_time" class="block"><strong>{{ __('Appt. Date:', 'schedula-smart-appointment-booking') }}</strong> {{ formatDate(payment.start_datetime) }} {{ formatTime(payment.start_datetime) }}</div>
                      <div v-if="columns.type" class="block"><strong>{{ __('Type:', 'schedula-smart-appointment-booking') }}</strong> {{ payment.display_payment_type }}</div>
                      <div v-if="columns.client" class="block"><strong>{{ __('Client:', 'schedula-smart-appointment-booking') }}</strong> {{ payment.customer_full_name }}</div>
                      <div v-if="columns.employee" class="block"><strong>{{ __('Employee:', 'schedula-smart-appointment-booking') }}</strong> {{ payment.staff_name || __('N/A', 'schedula-smart-appointment-booking') }}</div>
                      <div v-if="columns.service" class="block"><strong>{{ __('Service:', 'schedula-smart-appointment-booking') }}</strong> {{ payment.service_title || __('N/A', 'schedula-smart-appointment-booking') }}</div>
                      <div v-if="columns.amount" class="block"><strong>{{ __('Total Price:', 'schedula-smart-appointment-booking') }}</strong> {{ formatPrice(payment.amount) }}</div>
                      <div class="block flex space-x-1 mt-2">
                        <button @click.stop="emit('open-receipt', payment.payment_id)" class="text-indigo-600 hover:text-indigo-900 text-sm p-1">
                          <i class="fas fa-receipt"></i>
                        </button>
                        <button @click.stop="emit('delete-payment', payment.payment_id)" class="text-red-600 hover:text-red-900 text-sm p-1">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                  <input 
                    type="checkbox" 
                    :checked="isSelected(payment.payment_id)" 
                    @change="toggleSelection(payment.payment_id, $event.target.checked)" 
                    class="h-4 w-4 rounded form-checkbox mt-1" 
                    :style="{borderColor: 'black', backgroundColor: isSelected(payment.payment_id) ? 'var(--admin-link-indigo-bg)' : 'white'}"
                    @click.stop
                  />
                </div>
              </td>
            </tr>
        </tbody>
      </table>
      </div>
    </div>
    
    <!-- Total Earnings Display -->
    <div class="mt-6 p-4 rounded-lg shadow-inner text-right" :style="{ backgroundColor: 'var(--admin-suggestion-indigo-bg)' }">
      <h4 class="text-xl font-bold" :style="{ color: 'var(--admin-suggestion-indigo-text)' }">{{ __('Total Earnings:', 'schedula-smart-appointment-booking') }} {{ formatPrice(totalEarnings) }}</h4>
    </div>
  </div>
</template>

<script setup>
import { __ } from '@wordpress/i18n';
import { defineProps, defineEmits } from 'vue';
import AppLoader from '../shared/AppLoader.vue';
import { useGlobalSettings } from '../../composables/useGlobalSettings.js'; // Import useGlobalSettings

const props = defineProps({
  title: {
    type: String,
    default: __('All Payments', 'schedula-smart-appointment-booking'),
  },
  payments: {
    type: Array,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: null,
  },
  totalEarnings: {
    type: Number,
    default: 0,
  },
  columns: {
    type: Object,
    default: () => ({
      id: true,
      date_time: true,
      type: true,
      client: true,
      employee: true,
      service: true,
      amount: true,
    }),
  },
  sortBy: {
    type: String,
    default: null,
  },
  sortDirection: {
    type: String,
    default: 'asc', // 'asc' or 'desc'
  },
  selectedPayments: { // NEW PROP
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['delete-payment', 'open-receipt', 'update:sort', 'update:selection']); // NEW EMIT: update:sort, update:selection

// Destructure formatPrice from useGlobalSettings
import { computed } from 'vue'; // Added computed

const { formatPrice, formatTime, formatDate } = useGlobalSettings();

const handleSort = (column) => {
  let newDirection = 'asc';
  if (props.sortBy === column) {
    newDirection = props.sortDirection === 'asc' ? 'desc' : 'asc';
  }
  emit('update:sort', { sortBy: column, sortDirection: newDirection });
};

// Check if a payment's ID is in the selectedPayments array
const isSelected = (paymentId) => {
  return props.selectedPayments.includes(paymentId);
};

// Emit selection change to parent
const toggleSelection = (paymentId, isChecked) => {
  emit('update:selection', paymentId, isChecked);
};

// Computed property for "select all" checkbox state
const allSelected = computed(() => {
  // If there are no payments, "select all" isn't truly selected
  if (props.payments.length === 0) return false;
  // All payments must be present in selectedPayments for 'allSelected' to be true
  return props.payments.every(payment => isSelected(payment.payment_id));
});

// Computed property for indeterminate state (some selected, but not all)
const isIndeterminate = computed(() => {
  return props.selectedPayments.length > 0 && props.selectedPayments.length < props.payments.length;
});

// Toggle all payments selection
const toggleAll = (event) => {
  const isChecked = event.target.checked;
  props.payments.forEach(payment => {
    // Only emit change if the current selection state is different from the desired state
    const isCurrentlySelected = isSelected(payment.payment_id);
    if (isChecked && !isCurrentlySelected) {
      emit('update:selection', payment.payment_id, true);
    } else if (!isChecked && isCurrentlySelected) {
      emit('update:selection', payment.payment_id, false); // Corrected to payment.payment_id
    }
  });
};

// Computed property to determine the number of visible columns for colspan
const visibleColumnCount = computed(() => {
  return Object.values(props.columns).filter(visible => visible).length + 2; // +1 for actions, +1 for checkbox
});




</script>

<style scoped>
.break-words {
  word-break: break-word;
  overflow-wrap: break-word;
}

/* Responsive table adjustments */
@media (max-width: 639px) { /* Small screens (sm breakpoint in Tailwind is 640px) */
  table, thead, tbody, th, td, tr {
    display: block;
  }

  thead tr {
    position: absolute;
    top: -9999px;
    left: -9999px;
  }

  tr {
    border: 1px solid var(--admin-border-color);
    margin-bottom: 0.5rem;
    border-radius: 0.5rem; /* rounded-lg */
    overflow: hidden; /* Ensures border-radius applies */
  }

  td {
    /* Behave like a "row" */
    border: none;
    border-bottom: 1px solid var(--admin-border-color);
    position: relative;
    padding-left: 50% !important; /* Adjust based on label width */
    text-align: right;
  }

  td:before {
    /* Now like a table header */
    position: absolute;
    /* Top/left values mimic padding */
    top: 0px;
    left: 6px;
    width: 45%; /* Adjust to fit content */
    padding-right: 10px;
    white-space: nowrap;
    text-align: left;
    font-weight: bold;
    color: var(--admin-card-text-color);
  }

  /* Hide the desktop cells */
  .sm\:table-cell.hidden {
    display: none;
  }

  /* Show the stacked cells */
  .block.sm\:hidden {
    display: block;
  }
}
</style>
