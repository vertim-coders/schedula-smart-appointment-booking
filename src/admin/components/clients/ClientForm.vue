<template>
  <div :class="{ 'h-full flex flex-col': isFullscreen }" class="p-6"> 
    <div class="flex justify-between items-center mb-6">
      <h3 class="text-2xl font-semibold text-gray-900">{{ isEditing ? __('Edit Client', 'schedula-smart-appointment-booking') : __('Add New Client', 'schedula-smart-appointment-booking') }}</h3>
      <button
        @click="closeForm"
        class="text-gray-500 hover:text-gray-700 focus:outline-none"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <form @submit.prevent="handleSubmit" class="flex-1 flex flex-col">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6"> <!-- Increased gap to gap-8 -->
        <BaseInput
          id="first_name"
          :label="__('First Name', 'schedula-smart-appointment-booking')"
          v-model="client.first_name"
          :required="true"
        />
        <BaseInput
          id="last_name"
          :label="__('Last Name', 'schedula-smart-appointment-booking')"
          v-model="client.last_name"
          :required="true"
        />
      </div>

      <div class="mb-6"> 
        <BaseInput
          id="email"
          :label="__('Email', 'schedula-smart-appointment-booking')"
          type="email"
          v-model="client.email"
          :required="true"
        />
      </div>

      <div class="mb-6"> 
        <BasePhoneInput
          id="phone"
          :label="__('Phone', 'schedula-smart-appointment-booking')"
          v-model="client.phone"
          :required="true"
        />
      </div>

      <div class="mb-6 flex-grow"> <!-- Added flex-grow to notes to push buttons to bottom -->
        <BaseTextarea
          id="notes"
          :label="__('Notes', 'schedula-smart-appointment-booking')"
          v-model="client.notes"
          rows="3"
        />
      </div>
      
      <div class="flex justify-end space-x-3 mt-auto pt-4 border-t border-gray-200"> <!-- mt-auto to push to bottom -->
        <button
          type="button"
          @click="closeForm"
          class="px-6 py-2 rounded-lg shadow-sm text-sm font-medium" :style="{ backgroundColor: 'var(--admin-button-third-bg)', color: 'var(--admin-button-secondary-text)' }">

          <i class="fas fa-times mr-2"></i>{{ __('Cancel', 'schedula-smart-appointment-booking') }}
        </button>
        <button
          type="submit"
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
  </div>
</template>

<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue';
import { __ } from '@wordpress/i18n';
import { customersApi } from '../../api.js';
import BaseInput from '../common/BaseInput.vue';
import BaseTextarea from '../common/BaseTextarea.vue';
import { useToast } from '../../composables/useToast.js'; // Import useToast
import BasePhoneInput from '../common/BasePhoneInput.vue';

const props = defineProps({
  initialClient: {
    type: Object,
    default: null,
  },
  isFullscreen: {
    type: Boolean,
    default: false,
  }
});

const emit = defineEmits(['client-saved', 'close-form']);

const { success, error } = useToast(); // Use the toast composable

const defaultClient = { id: null, first_name: '', last_name: '', email: '', phone: '', notes: '' };
const client = ref({ ...defaultClient });
const isEditing = ref(false);
const saving = ref(false);
// Removed formError and showSuccessMessage refs as toast will handle these
// const formError = ref(null); 
// const showSuccessMessage = ref(false);
// const successMessage = ref('');

watch(() => props.initialClient, (newVal) => {
  if (newVal) {
    client.value = { ...newVal };
    isEditing.value = true;
  } else {
    client.value = { ...defaultClient };
    isEditing.value = false;
  }
  // No need to reset formError here, as toast handles errors
  // formError.value = null; 
}, { immediate: true });

const handleSubmit = async () => {
  // formError.value = null; // Removed

  if (!client.value.first_name.trim() || !client.value.last_name.trim()) {
    error(__('First Name and Last Name cannot be empty.', 'schedula-smart-appointment-booking')); // Use toast for error
    return;
  }
  if (!client.value.email.trim()) {
    error(__('Email cannot be empty.', 'schedula-smart-appointment-booking')); // Use toast for error
    return;
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(client.value.email)) {
    error(__('Please enter a valid email address.', 'schedula-smart-appointment-booking')); // Use toast for error
    return;
  }

  saving.value = true;
  try {
    let response;
    if (isEditing.value) {
      response = await customersApi.updateCustomer(client.value.id, client.value);
    } else {
      response = await customersApi.createCustomer(client.value);
    }

    const savedClient = response.data;
    const successMessage = isEditing.value 
        ? __('Client "%s" updated successfully!', 'schedula-smart-appointment-booking')
        : __('New client "%s" created successfully!', 'schedula-smart-appointment-booking');
    
    success(successMessage.replace('%s', `${savedClient.first_name} ${savedClient.last_name}`));

    saving.value = false;

    // The toast has its own duration, so we can immediately emit and close the form
    emit('client-saved', savedClient);
    closeForm();
    
  } catch (err) {
    error(err.response?.data?.message || err.message || __('Failed to save client.', 'schedula-smart-appointment-booking')); // Use toast for error
    console.error('Error saving client:', err);
    saving.value = false;
  }
};

const closeForm = () => {
  client.value = { ...defaultClient };
  isEditing.value = false;
  // No need to reset formError here
  // formError.value = null; 
  emit('close-form');
};
</script>

<style scoped>

</style>