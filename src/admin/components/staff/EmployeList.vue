<template>
  <div class="mb-4">
    <div class="flex justify-between items-center mb-4">
      <div v-if="message" :class="['p-2 text-sm rounded-md']" role="alert" :style="{ backgroundColor: messageType === 'success' ? 'var(--admin-input-border-color)' : 'var(--admin-suggestion-red-bg)', color: messageType === 'success' ? 'var(--admin-card-text-color)' : 'var(--admin-suggestion-red-text)' }">
        {{ message }}
      </div>
    </div>

    <div class="overflow-x-auto relative shadow-md sm:rounded-lg content-card">
      <table class="w-full text-sm text-left text-gray-500">
        <thead class="text-xs text-gray-700 uppercase">
          <tr>
            <!-- Original columns -->
            <th v-if="columns.id" scope="col" class="py-3 px-6" :class="{ 'cursor-pointer': true }" @click="handleSort('id')">
              {{ __('ID', 'schedula-smart-appointment-booking') }}
              <i v-if="sortBy === 'id'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
              <i v-else class="fas fa-sort"></i>
            </th>
            <th v-if="columns.image" scope="col" class="py-3 px-6">Staff Image</th>
            <th v-if="columns.name" scope="col" class="py-3 px-6" :class="{ 'cursor-pointer': true }" @click="handleSort('name')">
              {{ __('Name', 'schedula-smart-appointment-booking') }}
              <i v-if="sortBy === 'name'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
              <i v-else class="fas fa-sort"></i>
            </th>
            <th v-if="columns.email" scope="col" class="py-3 px-6" :class="{ 'cursor-pointer': true }" @click="handleSort('email')">
              {{ __('Email', 'schedula-smart-appointment-booking') }}
              <i v-if="sortBy === 'email'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
              <i v-else class="fas fa-sort"></i>
            </th>
            <th v-if="columns.phone" scope="col" class="py-3 px-6">Phone</th>
            <th scope="col" class="py-3 px-6 text-right">{{ __('Actions', 'schedula-smart-appointment-booking') }}</th>
            <!-- Checkbox column header - NOW LAST COLUMN -->
            <th scope="col" class="py-3 px-6 w-12">
              <input
                type="checkbox"
                class="form-checkbox h-4 w-4 text-indigo-600 rounded"
                :checked="allSelected"
                :indeterminate="isIndeterminate"
                @change="toggleAll"
                :style="{ accentColor: 'var(--admin-link-indigo-bg)' }"
              />
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
                <span class="block sm:inline">{{ __('Error:', 'schedula-smart-appointment-booking') }} {{ error }}</span>
              </div>
            </td>
          </tr>
          <tr v-else-if="staffMembers.length === 0" class="border-b">
            <td :colspan="visibleColumnCount" class="py-4 px-6 text-center">
              <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative">
                <span class="block sm:inline">{{ __('No staff members found.', 'schedula-smart-appointment-booking') }}</span>
              </div>
            </td>
          </tr>
          <tr v-else v-for="(staff, index) in staffMembers" :key="staff.id" :class="['border-b', 'hover:bg-indigo-100 cursor-pointer', { 'bg-indigo-50': isSelected(staff.id) }]">
            <!-- Original columns -->
            <td v-if="columns.id" class="py-4 px-6" @click="emit('edit', staff)">{{ staff.id }}</td>
            <td v-if="columns.image" class="py-2 px-4">
              <img v-if="staff.image_url" :src="staff.image_url" :alt="staff.name" class="w-10 h-10 rounded-full object-cover">
              <div v-else class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                <i class="fas fa-user text-gray-500"></i>
              </div>
            </td>
            <td v-if="columns.name" class="py-4 px-6" @click="emit('edit', staff)">{{ staff.name }}</td>
            <td v-if="columns.email" class="py-4 px-6" @click="emit('edit', staff)">{{ staff.email }}</td>
            <td v-if="columns.phone" class="py-4 px-6" @click="emit('edit', staff)">{{ staff.phone }}</td>
            <td class="py-4 px-6 text-right">
              <button
              @click.stop="emit('edit', staff)" 
              class="text-indigo-600 hover:text-indigo-900 text-sm p-1">
              <i class="fas fa-edit"></i> 
            </button>
            <button
              @click.stop="emit('delete', staff.id)"
              class="text-red-600 hover:text-red-900 text-sm p-1">
              <i class="fas fa-trash"></i>
            </button>
            </td>
            <!-- Checkbox cell - NOW LAST COLUMN -->
            <td class="py-4 px-6">
              <input
                type="checkbox"
                class="form-checkbox h-4 w-4 text-indigo-600 rounded"
                :checked="isSelected(staff.id)"
                @change.stop="toggleSelection(staff.id, $event.target.checked)"
                :style="{ accentColor: 'var(--admin-link-indigo-bg)' }"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits, computed } from 'vue';
import AppLoader from '../shared/AppLoader.vue';
import { __ } from '@wordpress/i18n';

const props = defineProps({
  staffMembers: {
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
  message: {
    type: String,
    default: '',
  },
  messageType: {
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
  selectedStaffIds: { 
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['edit', 'delete', 'update:sort', 'update:selection', 'update:selectAll']);

// Computed property for total visible columns including the new checkbox column
const visibleColumnCount = computed(() => {
  // +1 for the actions column
  // +1 for the new selection column (now last column)
  return Object.values(props.columns).filter(visible => visible).length + 2; 
});

// Check if a staff member's ID is in the selectedStaffIds array
const isSelected = (staffId) => {
  return props.selectedStaffIds.includes(staffId);
};

// Emit selection change to parent for individual checkbox
const toggleSelection = (staffId, isChecked) => {
  emit('update:selection', staffId, isChecked);
};

// Computed property for "select all" checkbox state
const allSelected = computed(() => {
  if (props.staffMembers.length === 0) return false;
  return props.staffMembers.every(staff => isSelected(staff.id));
});

// Computed property for indeterminate state (some selected, but not all)
const isIndeterminate = computed(() => {
  return props.selectedStaffIds.length > 0 && props.selectedStaffIds.length < props.staffMembers.length;
});

// Toggle all staff members selection
const toggleAll = (event) => {
  const isChecked = event.target.checked;
  emit('update:selectAll', isChecked);
};

// Handle sort clicks from table headers
const handleSort = (column) => {
  let newSortDirection = 'asc'; // Default for a new column

  // If the same column is clicked, toggle the sort direction
  if (props.sortBy === column) {
    newSortDirection = props.sortDirection === 'asc' ? 'desc' : 'asc';
  }
  // Otherwise, for a new column, newSortDirection remains 'asc'

  emit('update:sort', { sortBy: column, sortDirection: newSortDirection });
};
</script>

<style scoped>
/* Add any specific styles for EmployeListe.vue here if needed */
</style>
