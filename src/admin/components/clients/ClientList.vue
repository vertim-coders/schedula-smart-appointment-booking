<template>
  <div>
    <div class="overflow-x-auto relative shadow-md sm:rounded-lg content-card">
      <table class="w-full text-sm text-left text-gray-500">
        <thead class="text-xs text-gray-700 uppercase">
          <tr>
            <th v-if="columns.id" scope="col" class="py-3 px-6" :class="{ 'cursor-pointer': true }" @click="handleSort('id')">{{ __('Client ID', 'schedula-smart-appointment-booking') }} 
              <i v-if="sortBy === 'id'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
              <i v-else class="fas fa-sort"></i>
            </th>
            <th v-if="columns.first_name" scope="col" class="py-3 px-6" :class="{ 'cursor-pointer': true }" @click="handleSort('first_name')">{{ __('First Name', 'schedula-smart-appointment-booking') }} 
              <i v-if="sortBy === 'first_name'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
              <i v-else class="fas fa-sort"></i>
            </th>
            <th v-if="columns.last_name" scope="col" class="py-3 px-6" :class="{ 'cursor-pointer': true }" @click="handleSort('last_name')">{{ __('Last Name', 'schedula-smart-appointment-booking') }} 
              <i v-if="sortBy === 'last_name'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
              <i v-else class="fas fa-sort"></i>
            </th>
            <th v-if="columns.email" scope="col" class="py-3 px-6" :class="{ 'cursor-pointer': true }" @click="handleSort('email')">{{ __('Email', 'schedula-smart-appointment-booking') }} 
              <i v-if="sortBy === 'email'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
              <i v-else class="fas fa-sort"></i>
            </th>
            <th v-if="columns.phone" scope="col" class="py-3 px-6">{{ __('Phone', 'schedula-smart-appointment-booking') }}</th>
            <th v-if="columns.notes" scope="col" class="py-3 px-6">{{ __('Notes', 'schedula-smart-appointment-booking') }}</th>
            <th v-if="columns.created_at" scope="col" class="py-3 px-6" :class="{ 'cursor-pointer': true }" @click="handleSort('created_at')">{{ __('Created At', 'schedula-smart-appointment-booking') }} 
              <i v-if="sortBy === 'created_at'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
              <i v-else class="fas fa-sort"></i>
            </th>
            <th scope="col" class="py-3 px-6 text-center">{{ __('Actions', 'schedula-smart-appointment-booking') }}</th>
            <!-- NEW SELECTION HEADER - Now the very last column -->
            <th scope="col" class="py-3 px-6">
              <input type="checkbox" @change="toggleAll" :checked="allSelected" :indeterminate="isIndeterminate" 
                class="h-4 w-4 rounded form-checkbox" 
                :style="{borderColor: 'black', backgroundColor: allSelected ? 'var(--admin-link-indigo-bg)' : 'white'}" />
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading" class="border-b">
            <td :colspan="visibleColumnCount" class="py-4 px-6 text-center h-[250px]">
              <AppLoader />
            </td>
          </tr>
          <tr v-else-if="error" class="border-b">
            <td :colspan="visibleColumnCount" class="py-4 px-6 text-center">
              <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                <span class="block sm:inline">{{ sprintf(__('Error: %s', 'schedula-smart-appointment-booking'), error) }}</span>
              </div>
            </td>
          </tr>
          <tr v-else-if="clients.length === 0" class="border-b">
            <td :colspan="visibleColumnCount" class="py-4 px-6 text-center">
              <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative">
                <span class="block sm:inline">{{ __('No clients found. Click "Add New Client" to get started!', 'schedula-smart-appointment-booking') }}</span>
              </div>
            </td>
          </tr>
          <tr v-if="!loading && !error && clients.length > 0" v-for="(client, index) in clients" :key="client.id" :class="['border-b', 'hover:bg-indigo-100 cursor-pointer', { 'bg-indigo-50': isSelected(client.id) }]">
            <td v-if="columns.id" class="py-4 px-6" @click="emit('edit-client', client)">{{ client.id }}</td>
            <td v-if="columns.first_name" class="py-4 px-6" @click="emit('edit-client', client)">{{ client.first_name }}</td>
            <td v-if="columns.last_name" class="py-4 px-6" @click="emit('edit-client', client)">{{ client.last_name }}</td>
            <td v-if="columns.email" class="py-4 px-6" @click="emit('edit-client', client)">{{ client.email }}</td>
            <td v-if="columns.phone" class="py-4 px-6" @click="emit('edit-client', client)">{{ client.phone || 'N/A' }}</td>
            <td v-if="columns.notes" class="py-4 px-6" @click="emit('edit-client', client)">{{ client.notes || 'N/A' }}</td>
            <td v-if="columns.created_at" class="py-4 px-6" @click="emit('edit-client', client)">{{ client.created_at ? new Date(client.created_at).toLocaleDateString() : 'N/A' }}</td>
            <td class="py-4 px-6 text-center space-x-2">
              <button 
                @mouseenter="showClientResume(client, $event)"
                @mouseleave="hideClientResume"
                class="text-blue-600 hover:text-blue-900 text-sm p-1">
                <i class="fas fa-book-open"></i>
              </button>
              <button @click.stop="emit('edit-client', client)" class="text-indigo-600 hover:text-indigo-900 text-sm p-1">
                <i class="fas fa-edit"></i>
              </button>
              <button @click.stop="emit('delete-client', client)" class="text-red-600 hover:text-red-900 text-sm p-1">
                <i class="fas fa-trash"></i>
              </button>
            </td>
            <!-- NEW SELECTION CHECKBOX - Now the very last column -->
            <td class="py-4 px-6">
              <input 
                type="checkbox" 
                :checked="isSelected(client.id)" 
                @change="toggleSelection(client.id, $event.target.checked)" 
                class="h-4 w-4 rounded form-checkbox" 
                :style="{borderColor: 'black', backgroundColor: isSelected(client.id) ? 'var(--admin-link-indigo-bg)' : 'white'}"
                @click.stop
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <!-- Quick Preview Tooltip -->
    <div 
      v-if="quickPreview.show"
      :style="{ top: quickPreview.y + 'px', left: quickPreview.x + 'px', backgroundColor: 'var(--admin-card-bg-color)', color: 'var(--admin-card-text-color)' }"
      class="fixed z-50 p-3 rounded-lg shadow-xl max-w-xs pointer-events-none transform -translate-x-1/2 -translate-y-full"
    >
      <div class="font-semibold">{{ quickPreview.client?.first_name }} {{ quickPreview.client?.last_name }}</div>
      <div v-if="quickPreview.loading" class="mt-2">
        <AppLoader />
      </div>
      <div v-else-if="quickPreview.error" class="mt-2 text-red-500">
        {{ quickPreview.error }}
      </div>
      <div v-else-if="quickPreview.stats" class="mt-2">
        <p class="text-sm">{{ __('Appointments:', 'schedula-smart-appointment-booking') }} {{ quickPreview.stats.appointments_count }}</p>
        <p class="text-sm">{{ __('Total Paid:', 'schedula-smart-appointment-booking') }} {{ quickPreview.stats.total_paid }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, defineProps, defineEmits, reactive, ref } from 'vue';
import AppLoader from '../shared/AppLoader.vue';
import { __ } from '@wordpress/i18n';
import { customersApi } from '../../api';

const props = defineProps({
  clients: {
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
    default: 'asc', // 'asc' or 'desc'
  },
  selectedClients: { // NEW PROP
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['edit-client', 'delete-client', 'update:sort', 'update:selection']); // NEW EMIT: update:selection

const quickPreview = reactive({
  show: false,
  loading: false,
  error: null,
  client: null,
  stats: null,
  x: 0,
  y: 0,
});

let fetchTimeout = null;

const showClientResume = (client, event) => {
  quickPreview.x = event.clientX;
  quickPreview.y = event.clientY - 10;
  quickPreview.show = true;
  quickPreview.loading = true;
  quickPreview.error = null;
  quickPreview.client = client;
  quickPreview.stats = null;

  // Debounce the fetch
  if (fetchTimeout) clearTimeout(fetchTimeout);
  
  fetchTimeout = setTimeout(async () => {
    try {
      const response = await customersApi.getClientStats(client.id);
      quickPreview.stats = response.data;
    } catch (err) {
      quickPreview.error = __('Error loading stats.', 'schedula-smart-appointment-booking');
    } finally {
      quickPreview.loading = false;
    }
  }, 300); // Wait 300ms before fetching
};

const hideClientResume = () => {
  if (fetchTimeout) clearTimeout(fetchTimeout);
  quickPreview.show = false;
};

const handleSort = (column) => {
  let newDirection = 'asc';
  if (props.sortBy === column) {
    newDirection = props.sortDirection === 'asc' ? 'desc' : 'asc';
  }
  emit('update:sort', { sortBy: column, sortDirection: newDirection });
};

// Computed property to determine the number of visible columns for colspan
const visibleColumnCount = computed(() => {
  // +2 for the actions column and the new selection column
  return Object.values(props.columns).filter(visible => visible).length + 2; 
});

// Check if a client's ID is in the selectedClients array
const isSelected = (clientId) => {
  return props.selectedClients.includes(clientId);
};

// Emit selection change to parent
const toggleSelection = (clientId, isChecked) => {
  emit('update:selection', clientId, isChecked);
};

// Computed property for "select all" checkbox state
const allSelected = computed(() => {
  // If there are no clients, "select all" isn't truly selected
  if (props.clients.length === 0) return false;
  // All clients must be present in selectedClients for 'allSelected' to be true
  return props.clients.every(client => isSelected(client.id));
});

// Computed property for indeterminate state (some selected, but not all)
const isIndeterminate = computed(() => {
  return props.selectedClients.length > 0 && props.selectedClients.length < props.clients.length;
});

// Toggle all clients selection
const toggleAll = (event) => {
  const isChecked = event.target.checked;
  props.clients.forEach(client => {
    // Only emit change if the current selection state is different from the desired state
    const isCurrentlySelected = isSelected(client.id);
    if (isChecked && !isCurrentlySelected) {
      emit('update:selection', client.id, true);
    } else if (!isChecked && isCurrentlySelected) {
      emit('update:selection', client.id, false);
    }
  });
};
</script>

<style scoped>
/* Add any additional styles here */
</style>
>
