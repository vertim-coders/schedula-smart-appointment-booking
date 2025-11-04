<template>
  <div>
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">{{ __('General Settings', 'schedula-smart-appointment-booking') }}</h2>
    <div class="mb-4">
      <label for="form-name" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Form Name', 'schedula-smart-appointment-booking') }}</label>
      <input type="text" id="form-name" :value="form.name" @input="updateForm('name', $event.target.value)" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2" />
    </div>

    <div class="mb-4">
      <label for="associated-service" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Associated Service', 'schedula-smart-appointment-booking') }}</label>
      <select 
        id="associated-service" 
        :value="form.service_id" 
        @change="handleServiceDropdownChange($event.target.value)" 
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2"
      >
        <option 
          v-for="service in availableServices" 
          :key="service.id" 
          :value="service.id"
        >
          {{ service.name || service.title || `Service #${service.id}` }}
        </option>
      </select>
      <p v-if="availableServices.length === 0" class="mt-2 text-sm text-red-600">
        {{ __('No services available. Please add services first.', 'schedula-smart-appointment-booking') }}
      </p>
    </div>

    <div class="mb-4">
      <label for="associated-category" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Associated Category', 'schedula-smart-appointment-booking') }}</label>
      <!-- Category input is disabled and auto-populated -->
      <select id="associated-category" :value="form.category_id" disabled class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 bg-gray-100 cursor-not-allowed">
        <option :value="null">{{ selectedCategoryName || __('Category auto-selected by Service', 'schedula-smart-appointment-booking') }}</option>
        <option v-if="selectedCategoryName" :value="form.category_id">{{ selectedCategoryName }}</option>
      </select>
    </div>

    <div class="mb-6">
      <label for="associated-staff" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Associated Staff', 'schedula-smart-appointment-booking') }}</label>
      <select 
        id="associated-staff" 
        :value="form.staff_id || 0" 
        @change="updateForm('staff_id', parseInt($event.target.value) || 0)" 
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2"
        :disabled="!form.service_id || staffForSelectedService.length === 0"
      >
        <option :value="0">{{ __('Any Staff Member', 'schedula-smart-appointment-booking') }}</option>
        <option 
          v-for="staff in staffForSelectedService" 
          :key="staff.id" 
          :value="staff.id"
        >
          {{ staff.name }}
        </option>
      </select>
      <p v-if="!form.service_id" class="mt-2 text-sm text-gray-500">{{ __('Please select a service first', 'schedula-smart-appointment-booking') }}</p>
      <p v-else-if="staffForSelectedService.length === 0" class="mt-2 text-sm text-yellow-600">{{ __('No staff members available for the selected service', 'schedula-smart-appointment-booking') }}</p>
      <p v-else class="mt-2 text-sm text-gray-500">{{ form.staff_id ? `${__('Selected:', 'schedula-smart-appointment-booking')} ${selectedStaffName}` : `${__('Select a staff member or leave as "Any Staff Member"', 'schedula-smart-appointment-booking')}` }}</p>
    </div>
  </div>
</template> 

<script setup>
import { __ } from '@wordpress/i18n';
import { computed } from 'vue';

const props = defineProps({
  form: { type: Object, required: true },
  settings: { type: Object, required: true },
  initialServices: { type: Array, default: () => [] },
  initialCategories: { type: Array, default: () => [] },
  initialStaff: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:form']);

const availableServices = computed(() => props.initialServices.map(service => ({
  id: service.id,
  name: service.name || service.title || `Service #${service.id}`,
  category_id: service.category_id || null,
  ...service
})));

const availableCategories = computed(() => props.initialCategories);

const staffForSelectedService = computed(() => {
  if (!props.form.service_id) {
    return [];
  }
  const service = availableServices.value.find(s => s.id == props.form.service_id);
  // If a service has no specific staff assigned, make all staff available for it.
  if (!service || !service.staff_ids || service.staff_ids.length === 0) {
    return props.initialStaff;
  }
  // Otherwise, filter by the assigned staff IDs.
  return props.initialStaff.filter(staff => service.staff_ids.includes(Number(staff.id)));
});

// Computed property to display the selected category's name
const selectedCategoryName = computed(() => {
  const category = availableCategories.value.find(c => c.id == props.form.category_id);
  return category ? category.name : '';
});

// Computed property to get the selected staff member's name
const selectedStaffName = computed(() => {
  if (!props.form.staff_id || props.form.staff_id == 0) return __('Any Staff Member', 'schedula-smart-appointment-booking');
  const staff = props.initialStaff.find(s => s.id == props.form.staff_id);
  return staff ? staff.name : __('Any Staff Member', 'schedula-smart-appointment-booking');
});

// Handle service dropdown change
const handleServiceDropdownChange = (serviceId) => {
  if (!serviceId) {
    emit('update:form', { 
      ...props.form, 
      service_id: null, 
      category_id: null, 
      staff_id: 0 
    });
    return;
  }

  const service = availableServices.value.find(s => s.id == serviceId);
  if (service) {
    emit('update:form', { 
      ...props.form, 
      service_id: parseInt(serviceId), 
      category_id: service.category_id || null,
      staff_id: 0 // Reset staff selection when service changes
    });
  }
};

const updateForm = (key, value) => {
  emit('update:form', { ...props.form, [key]: value });
};
</script>

<style scoped>
/* Add any specific styling for ServiceFormDetails here if needed */
</style>