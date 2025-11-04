<template>
  <div>
    <div v-if="error" class="px-4 py-3 rounded relative" role="alert" :style="{ backgroundColor: 'var(--admin-suggestion-red-bg)', borderColor: 'var(--admin-border-color)', color: 'var(--admin-suggestion-red-text)' }">
      <span class="block sm:inline">{{ __('Error: ', 'schedula-smart-appointment-booking') }}{{ error }}</span>
    </div>
    <div class="relative shadow-md sm:rounded-lg content-card">
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 min-w-full">
          <thead class="text-xs text-gray-700 uppercase hidden sm:table-header-group">
            <!-- Table header content -->
            <tr>
              <th v-if="columns.id" scope="col" class="py-3 px-2 w-16 cursor-pointer" @click="handleSort('id')">{{ __('ID', 'schedula-smart-appointment-booking') }}
                <i v-if="sortBy === 'id'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                <i v-else class="fas fa-sort"></i>
              </th>
              <th v-if="columns.date_time" scope="col" class="py-3 px-2 w-28 cursor-pointer" @click="handleSort('start_datetime')">{{ __('Date & Time', 'schedula-smart-appointment-booking') }}
                <i v-if="sortBy === 'start_datetime'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                <i v-else class="fas fa-sort"></i>
              </th>
              <th v-if="columns.client" scope="col" class="py-3 px-2 w-24 cursor-pointer" @click="handleSort('customer_name')">{{ __('Client', 'schedula-smart-appointment-booking') }}
                <i v-if="sortBy === 'customer_name'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                <i v-else class="fas fa-sort"></i>
              </th>
              <th v-if="columns.employee" scope="col" class="py-3 px-2 w-20 cursor-pointer" @click="handleSort('staff_name')">{{ __('Employee', 'schedula-smart-appointment-booking') }}
                <i v-if="sortBy === 'staff_name'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                <i v-else class="fas fa-sort"></i>
              </th>
              <th v-if="columns.service" scope="col" class="py-3 px-2 w-24 cursor-pointer" @click="handleSort('service_title')">{{ __('Service', 'schedula-smart-appointment-booking') }}
                <i v-if="sortBy === 'service_title'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                <i v-else class="fas fa-sort"></i>
              </th>
              <th v-if="columns.price" scope="col" class="py-3 px-2 w-16 cursor-pointer" @click="handleSort('price')">{{ __('Price', 'schedula-smart-appointment-booking') }}
                <i v-if="sortBy === 'price'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                <i v-else class="fas fa-sort"></i>
              </th>
              <th v-if="columns.phone" scope="col" class="py-3 px-2 w-24">{{ __('Phone', 'schedula-smart-appointment-booking') }}</th>
              <th v-if="columns.email" scope="col" class="py-3 px-2 w-32">{{ __('Email', 'schedula-smart-appointment-booking') }}</th>
              <th v-if="columns.duration" scope="col" class="py-3 px-2 w-16 cursor-pointer" @click="handleSort('duration')">{{ __('Duration', 'schedula-smart-appointment-booking') }}
                <i v-if="sortBy === 'duration'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                <i v-else class="fas fa-sort"></i>
              </th>
              <th v-if="columns.number_of_persons" scope="col" class="py-3 px-2 w-16 cursor-pointer" @click="handleSort('number_of_persons')">{{ __('People', 'schedula-smart-appointment-booking') }}
                <i v-if="sortBy === 'number_of_persons'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                <i v-else class="fas fa-sort"></i>
              </th>
              <th v-if="columns.status" scope="col" class="py-3 px-2 w-20 cursor-pointer" @click="handleSort('status')">{{ __('Status', 'schedula-smart-appointment-booking') }}
                <i v-if="sortBy === 'status'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                <i v-else class="fas fa-sort"></i>
              </th>
              <th v-if="columns.recurrence" scope="col" class="py-3 px-2 w-24">{{ __('Recurrence', 'schedula-smart-appointment-booking') }}</th>
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
            
            <!-- No appointments message -->
            <tr v-else-if="appointments.length === 0" class="border-b">
              <td :colspan="visibleColumnCount" class="py-4 px-2 text-center">
                <div class="px-4 py-3 rounded relative" :style="{ backgroundColor: 'var(--admin-input-border-color)', borderColor: 'var(--admin-border-color)', color: 'var(--admin-card-text-color)' }">
                  {{ noResultsMessage }}
                </div>
              </td>
            </tr>
            
            <!-- Appointments list -->
            <tr v-else v-for="(appointment, index) in appointments" :key="appointment.id" :class="['border-b', 'hover:bg-indigo-100', { 'bg-indigo-50': isSelected(appointment.id) }]">
              <td v-if="columns.id" @click="emit('edit-appointment', appointment)" class="py-4 px-2 sm:table-cell hidden cursor-pointer">{{ appointment.id }}</td>
              <td v-if="columns.date_time" @click="emit('edit-appointment', appointment)" class="py-4 px-2 sm:table-cell hidden cursor-pointer">
                <div class="flex flex-col">
                  <span class="font-medium text-xs">{{ formatDate(appointment.start_datetime) }}</span>
                  <span class="text-xs">{{ formatTime(appointment.start_datetime) }} - {{ formatTime(appointment.end_datetime) }}</span>
                </div>
              </td>
              <td v-if="columns.client" @click="emit('edit-appointment', appointment)" class="py-4 px-2 sm:table-cell hidden cursor-pointer">
                <div class="break-words max-w-24" :title="appointment.customer_name">
                  {{ appointment.customer_name }}
                </div>
              </td>
              <td v-if="columns.employee" @click="emit('edit-appointment', appointment)" class="py-4 px-2 sm:table-cell hidden cursor-pointer">
                <div class="break-words max-w-20" :title="appointment.staff_name || __('N/A', 'schedula-smart-appointment-booking')">
                  {{ appointment.staff_name || __('N/A', 'schedula-smart-appointment-booking') }}
                </div>
              </td>
              <td v-if="columns.service" @click="emit('edit-appointment', appointment)" class="py-4 px-2 sm:table-cell hidden cursor-pointer">
                <div class="break-words max-w-24">
                  {{ appointment.service_title || __('N/A', 'schedula-smart-appointment-booking') }}
                </div>
              </td>
              <td v-if="columns.price" @click="emit('edit-appointment', appointment)" class="py-4 px-2 sm:table-cell hidden cursor-pointer text-xs">
                {{ formatPrice(appointment.price) }}
              </td>
              <td v-if="columns.phone" @click="emit('edit-appointment', appointment)" class="py-4 px-2 sm:table-cell hidden cursor-pointer">
                <div class="break-words max-w-24" :title="appointment.customer_phone || __('N/A', 'schedula-smart-appointment-booking')">
                  {{ appointment.customer_phone || __('N/A', 'schedula-smart-appointment-booking') }}
                </div>
              </td>
              <td v-if="columns.email" @click="emit('edit-appointment', appointment)" class="py-4 px-2 sm:table-cell hidden cursor-pointer">
                <div class="break-words max-w-32" :title="appointment.customer_email">
                  {{ appointment.customer_email }}
                </div>
              </td>
              <td v-if="columns.duration" @click="emit('edit-appointment', appointment)" class="py-4 px-2 sm:table-cell hidden cursor-pointer text-xs">
                {{ appointment.duration }}{{ __('min', 'schedula-smart-appointment-booking') }}
              </td>
              <td v-if="columns.number_of_persons" @click="emit('edit-appointment', appointment)" class="py-4 px-2 sm:table-cell hidden cursor-pointer text-xs">
                {{ appointment.number_of_persons }}
              </td>
              <td v-if="columns.status" @click="emit('edit-appointment', appointment)" class="py-4 px-2 sm:table-cell hidden cursor-pointer">
                <span :class="getStatusBadgeClass(appointment.status)" class="text-xs">
                  {{ appointment.status }}
                </span>
              </td>
              <td v-if="columns.recurrence" @click="emit('edit-appointment', appointment)" class="py-4 px-2 sm:table-cell hidden cursor-pointer text-xs">
                {{ appointment.recurrence_text || __('No', 'schedula-smart-appointment-booking') }}
              </td>
              <td class="py-4 px-2 sm:table-cell hidden">
                <div class="flex space-x-1">
                  <button @click.stop="emit('edit-appointment', appointment)" class="text-indigo-600 hover:text-indigo-900 text-sm p-1">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button @click.stop="emit('delete-appointment', appointment)" class="text-red-600 hover:text-red-900 text-sm p-1">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
              <td class="py-4 px-2 w-10 sm:table-cell hidden">
                <input 
                  type="checkbox" 
                  :checked="isSelected(appointment.id)" 
                  @change="toggleSelection(appointment.id, $event.target.checked)" 
                  class="h-4 w-4 rounded form-checkbox" 
                  :style="{borderColor: 'black', backgroundColor: isSelected(appointment.id) ? 'var(--admin-link-indigo-bg)' : 'white'}"
                  @click.stop
                />
              </td>
              
              <td class="block sm:hidden p-2 border-t">
                <div class="flex items-start space-x-4">
                  <div class="flex-grow">
                    <div class="flex flex-col space-y-1">
                      <div v-if="columns.id" class="block"><strong>{{ __('ID:', 'schedula-smart-appointment-booking') }}</strong> {{ appointment.id }}</div>
                      <div v-if="columns.date_time" class="block"><strong>{{ __('Date & time:', 'schedula-smart-appointment-booking') }}</strong> {{ formatDate(appointment.start_datetime) }} {{ formatTime(appointment.start_datetime) }} - {{ formatTime(appointment.end_datetime) }}</div>
                      <div v-if="columns.client" class="block"><strong>{{ __('Client:', 'schedula-smart-appointment-booking') }}</strong> {{ appointment.customer_name }}</div>
                      <div v-if="columns.employee" class="block"><strong>{{ __('Employees:', 'schedula-smart-appointment-booking') }}</strong> {{ appointment.staff_name || __('N/A', 'schedula-smart-appointment-booking') }}</div>
                      <div v-if="columns.service" class="block"><strong>{{ __('Services:', 'schedula-smart-appointment-booking') }}</strong> {{ appointment.service_title || __('N/A', 'schedula-smart-appointment-booking') }}</div>
                      <div v-if="columns.price" class="block"><strong>{{ __('Price:', 'schedula-smart-appointment-booking') }}</strong> {{ formatPrice(appointment.price) }}</div>
                      <div v-if="columns.phone" class="block"><strong>{{ __('Phone:', 'schedula-smart-appointment-booking') }}</strong> {{ appointment.customer_phone || __('N/A', 'schedula-smart-appointment-booking') }}</div>
                      <div v-if="columns.email" class="block"><strong>{{ __('Email:', 'schedula-smart-appointment-booking') }}</strong> {{ appointment.customer_email }}</div>
                      <div v-if="columns.duration" class="block"><strong>{{ __('Duration:', 'schedula-smart-appointment-booking') }}</strong> {{ sprintf(__('%d min', 'schedula-smart-appointment-booking'), appointment.duration) }}</div>
                      <div v-if="columns.status" class="block"><strong>{{ __('Status:', 'schedula-smart-appointment-booking') }}</strong> <span :class="getStatusBadgeClass(appointment.status)" class="text-xs">{{ appointment.status }}</span></div>
                      <div v-if="columns.recurrence" class="block"><strong>{{ __('Recurrence:', 'schedula-smart-appointment-booking') }}</strong> {{ appointment.recurrence_text || __('No', 'schedula-smart-appointment-booking') }}</div>
                      <div v-if="columns.number_of_persons" class="block"><strong>{{ __('People:', 'schedula-smart-appointment-booking') }}</strong> {{ appointment.number_of_persons }}</div>
                      <div class="block flex space-x-1 mt-2">
                        <button @click.stop="emit('edit-appointment', appointment)" class="text-indigo-600 hover:text-indigo-900 text-sm p-1">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button @click.stop="emit('delete-appointment', appointment)" class="text-red-600 hover:text-red-900 text-sm p-1">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                  <input 
                    type="checkbox" 
                    :checked="isSelected(appointment.id)" 
                    @change="toggleSelection(appointment.id, $event.target.checked)" 
                    class="h-4 w-4 rounded form-checkbox mt-1" 
                    :style="{borderColor: 'black', backgroundColor: isSelected(appointment.id) ? 'var(--admin-link-indigo-bg)' : 'white'}"
                    @click.stop
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, defineProps, defineEmits } from 'vue';
import { __, sprintf } from '@wordpress/i18n';
import AppLoader from '../shared/AppLoader.vue';
import { useGlobalSettings } from '../../composables/useGlobalSettings.js'; // Import useGlobalSettings

const props = defineProps({
  appointments: {
    type: Array,
    required: true,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: null,
  },

  searchClientName: {
    type: String,
    default: '',
  },
  searchEmployeeName: {
    type: String,
    default: '',
  },
  filterStartDate: {
    type: String,
    default: '',
  },
  filterEndDate: {
    type: String,
    default: '',
  },
  columns: {
    type: Object,
    required: true,
  },
  sortBy: {
    type: String,
    default: null,
  },
  sortDirection: {
    type: String,
    default: 'asc',
  },
  selectedAppointments: { 
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['edit-appointment', 'delete-appointment', 'update:sort', 'update:selection']);

const handleSort = (column) => {
  let newDirection = 'asc';
  if (props.sortBy === column) {
    newDirection = props.sortDirection === 'asc' ? 'desc' : 'asc';
  }
  // Emit the updated sort parameters to the parent component
  emit('update:sort', { sortBy: column, sortDirection: newDirection });
};

// Check if an appointment's ID is in the selectedAppointments array
const isSelected = (appointmentId) => {
  return props.selectedAppointments.includes(appointmentId);
};

// Emit selection change to parent
const toggleSelection = (appointmentId, isChecked) => {
  emit('update:selection', appointmentId, isChecked);
};

// Computed property for "select all" checkbox state
const allSelected = computed(() => {
  if (props.appointments.length === 0) return false;
  return props.appointments.every(appointment => isSelected(appointment.id));
});

// Computed property for indeterminate state (some selected, but not all)
const isIndeterminate = computed(() => {
  return props.selectedAppointments.length > 0 && props.selectedAppointments.length < props.appointments.length;
});

// Toggle all appointments selection
const toggleAll = (event) => {
  const isChecked = event.target.checked;
  props.appointments.forEach(appointment => {
    const isCurrentlySelected = isSelected(appointment.id);
    if (isChecked && !isCurrentlySelected) {
      emit('update:selection', appointment.id, true);
    } else if (!isChecked && isCurrentlySelected) {
      emit('update:selection', appointment.id, false);
    }
  });
};

// Destructure formatPrice from useGlobalSettings
const { formatPrice, formatTime, formatDate } = useGlobalSettings();

const visibleColumnCount = computed(() => {
  return Object.values(props.columns).filter(visible => visible).length + 2; // +1 for actions, +1 for checkbox
});



const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'confirmed':
      return `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium` +
             ` background-color: var(--admin-badge-green-bg); color: var(--admin-badge-green-text);`;
    case 'pending':
      return `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium` +
             ` background-color: var(--admin-badge-yellow-bg); color: var(--admin-badge-yellow-text);`;
    case 'completed':
      return `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium` +
             ` background-color: var(--admin-badge-blue-bg); color: var(--admin-badge-blue-text);`;
    case 'cancelled':
      return `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium` +
             ` background-color: var(--admin-badge-red-bg); color: var(--admin-badge-red-text);`;
    default:
      return `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium` +
             ` background-color: var(--admin-badge-gray-bg); color: var(--admin-badge-gray-text);`;
  }
};


const noResultsMessage = computed(() => {
  const clientName = props.searchClientName.trim();
  const employeeName = props.searchEmployeeName.trim();
  const startDate = props.filterStartDate;
  const endDate = props.filterEndDate;

  let messageParts = [];

  if (clientName) {
    messageParts.push(sprintf(__('client "%s"', 'schedula-smart-appointment-booking'), clientName));
  }
  if (employeeName) {
    messageParts.push(sprintf(__('employee "%s"', 'schedula-smart-appointment-booking'), employeeName));
  }

  let datePeriod = '';
  if (startDate && endDate) {
    datePeriod = sprintf(__('from %s to %s', 'schedula-smart-appointment-booking'), formatDateTime(startDate, 'date'), formatDateTime(endDate, 'date'));
  } else if (startDate) {
    datePeriod = sprintf(__('starting from %s', 'schedula-smart-appointment-booking'), formatDateTime(startDate, 'date'));
  } else if (endDate) {
    datePeriod = sprintf(__('ending by %s', 'schedula-smart-appointment-booking'), formatDateTime(endDate, 'date'));
  }

  if (messageParts.length > 0 && datePeriod) {
    // Both name search and date filter
    return sprintf(__('No appointments found matching %s for the period %s.', 'schedula-smart-appointment-booking'), messageParts.join(sprintf(__(' and ', 'schedula-smart-appointment-booking'))), datePeriod);
  } else if (messageParts.length > 0) {
    // Only name search
    return sprintf(__('No appointments found for %s.', 'schedula-smart-appointment-booking'), messageParts.join(sprintf(__(' and ', 'schedula-smart-appointment-booking'))));
  } else if (datePeriod) {
    // Only date filter
    return sprintf(__('No appointments found for the period %s.', 'schedula-smart-appointment-booking'), datePeriod);
  } else {
    // No filters active, or all filters are empty
    return __('No appointments found.', 'schedula-smart-appointment-booking');
  }
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

  /*
  Label the data
  */
  td:nth-of-type(1):before { content: "Date & Time:"; }
  td:nth-of-type(2):before { content: "Client:"; }
  td:nth-of-type(3):before { content: "Employe:"; }
  td:nth-of-type(4):before { content: "Service:"; }
  td:nth-of-type(5):before { content: "Price:"; } /* Added for mobile view */
  td:nth-of-type(6):before { content: "Phone:"; }
  td:nth-of-type(7):before { content: "Email:"; }
  td:nth-of-type(8):before { content: "Duration:"; }
  td:nth-of-type(9):before { content: "Status:"; }
  td:nth-of-type(10):before { content: "Actions:"; }

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

