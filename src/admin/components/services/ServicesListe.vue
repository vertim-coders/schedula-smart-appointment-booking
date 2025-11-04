<template>
  <div>
    <div v-if="error" class="px-4 py-3 rounded relative" role="alert" :style="{ backgroundColor: 'var(--admin-suggestion-red-bg)', borderColor: 'var(--admin-border-color)', color: 'var(--admin-suggestion-red-text)' }">
      <span class="block sm:inline">{{ __('Error:', 'schedula-smart-appointment-booking') }} {{ error }}</span>
    </div>
    <div  class="overflow-x-auto relative shadow-md sm:rounded-lg content-card">
      <table class="w-full text-sm text-left text-gray-500">
        <thead class="text-xs text-gray-700 uppercase">
          <tr>
            <th v-if="columns.title" scope="col" class="py-3 px-6" :class="{ 'cursor-pointer': true }" @click="handleSort('title')">{{ __('Title', 'schedula-smart-appointment-booking') }} 
              <i v-if="sortBy === 'title'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
              <i v-else class="fas fa-sort"></i>
            </th>
            <th v-if="columns.category" scope="col" class="py-3 px-6">{{ __('Category', 'schedula-smart-appointment-booking') }}</th>
            <th v-if="columns.duration" scope="col" class="py-3 px-6" :class="{ 'cursor-pointer': true }" @click="handleSort('duration')">{{ __('Duration', 'schedula-smart-appointment-booking') }}
              <i v-if="sortBy === 'duration'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
              <i v-else class="fas fa-sort"></i>
            </th>
            <th v-if="columns.price" scope="col" class="py-3 px-6" :class="{ 'cursor-pointer': true }" @click="handleSort('price')">{{ __('Price', 'schedula-smart-appointment-booking') }}
              <i v-if="sortBy === 'price'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
              <i v-else class="fas fa-sort"></i>
            </th>
            <th scope="col" class="py-3 px-6 text-right">{{ __('Actions', 'schedula-smart-appointment-booking') }}</th>
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
          <tr v-if="!loading && services.length === 0" class="border-b">
            <td :colspan="visibleColumnCount" class="py-4 px-6 text-center">
              <span class="block sm:inline">{{ __('No services found. Click "Add New Service" to get started!', 'schedula-smart-appointment-booking') }}</span>
            </td>
          </tr>
          <tr v-for="(service, index) in services" :key="service.id" :class="['border-b', 'hover:bg-indigo-100 cursor-pointer', { 'bg-indigo-50': isSelected(service.id) }]" @click="emit('edit-service', service)">
            <td v-if="columns.title" class="py-4 px-6">
              {{ service.title }}
            </td>
            <td v-if="columns.category" class="py-4 px-6">
              {{ getCategoryName(service.category_id) }}
            </td>
            <td v-if="columns.duration" class="py-4 px-6">
              {{ service.duration }} {{ __('mins', 'schedula-smart-appointment-booking') }}
            </td>
            <td v-if="columns.price" class="py-4 px-6">
              {{ formatPrice(service.price) }}
            </td>
            <td class="py-4 px-6 text-right space-x-2">
              <button @click.stop="emit('edit-service', service)" class="text-indigo-600 hover:text-indigo-900 text-sm p-1">
                <i class="fas fa-edit" style="vertical-align: middle;"></i>
              </button>
              <button @click.stop="emit('delete-service', service)" class="text-red-600 hover:text-red-900 text-sm p-1">
                <i class="fas fa-trash" style="vertical-align: middle;"></i>
              </button>
            </td>
            <td class="py-4 px-6">
              <input 
                type="checkbox" 
                :checked="isSelected(service.id)" 
                @change="toggleSelection(service.id, $event.target.checked)" 
                class="h-4 w-4 rounded form-checkbox" 
                :style="{borderColor: 'black', backgroundColor: isSelected(service.id) ? 'var(--admin-link-indigo-bg)' : 'white'}"
                @click.stop
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
import { useGlobalSettings } from '@/admin/composables/useGlobalSettings.js';
import { __ } from '@wordpress/i18n';

const { formatPrice } = useGlobalSettings();

const props = defineProps({
  services: { type: Array, required: true, default: () => [] },
  categories: { type: Array, required: true, default: () => [] },
  loading: { type: Boolean, default: false },
  error: { type: String, default: null },
  columns: { type: Object, required: true },
  sortBy: { type: String, default: null },
  sortDirection: { type: String, default: 'asc' },
  selectedServices: { type: Array, default: () => [] },
});

const emit = defineEmits(['edit-service', 'delete-service', 'update:sort', 'update:selection']);

const visibleColumnCount = computed(() => {
  return Object.values(props.columns).filter(visible => visible).length + 2;
});

const getCategoryName = (categoryId) => {
  const category = props.categories.find(cat => cat.id === categoryId);
  return category ? category.name : 'Uncategorized';
};

const handleSort = (column) => {
  let newDirection = 'asc';
  if (props.sortBy === column) {
    newDirection = props.sortDirection === 'asc' ? 'desc' : 'asc';
  }
  emit('update:sort', { sortBy: column, sortDirection: newDirection });
};

const isSelected = (serviceId) => {
  return props.selectedServices.includes(serviceId);
};

const toggleSelection = (serviceId, isChecked) => {
  emit('update:selection', serviceId, isChecked);
};

const allSelected = computed(() => {
  if (props.services.length === 0) return false;
  return props.services.every(service => isSelected(service.id));
});

const isIndeterminate = computed(() => {
  return props.selectedServices.length > 0 && props.selectedServices.length < props.services.length;
});

const toggleAll = (event) => {
  const isChecked = event.target.checked;
  props.services.forEach(service => {
    const isCurrentlySelected = isSelected(service.id);
    if (isChecked && !isCurrentlySelected) {
      emit('update:selection', service.id, true);
    } else if (!isChecked && isCurrentlySelected) {
      emit('update:selection', service.id, false);
    }
  });
};
</script>

<style scoped>
</style>