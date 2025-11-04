<template>
  <div>
    <h3 class="text-xl font-semibold mb-4">{{ title }}</h3>
    <form @submit.prevent="handleSubmit">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <slot></slot>
      </div>
      <div v-if="message" :class="['px-4 py-3 rounded relative mb-4', messageType === 'success' ? 'success-message' : 'error-message']" role="alert">
        <span class="block sm:inline">{{ message }}</span>
      </div>
      <div v-if="showButtons" class="flex justify-end space-x-3">
        <button
          type="button"
          @click="cancel"
          class="px-6 py-2 border rounded-lg shadow-sm text-sm font-medium hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out"
          :style="{
            borderColor: 'var(--admin-input-border-color)',
            color: 'var(--admin-card-text-color)',
            backgroundColor: 'var(--admin-card-bg-color)'
          }"
        >
          <i class="fas fa-times mr-2"></i>{{ __('Close', 'schedula-smart-appointment-booking') }}
        </button>
        <button
          type="submit"
          :disabled="disabled || saving"
          class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out disabled:opacity-60 disabled:cursor-not-allowed"
          :style="{ backgroundColor: 'var(--admin-button-primary-bg)', color: 'var(--admin-button-primary-text)' }"
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
import { defineProps, defineEmits } from 'vue';
import { __ } from '@wordpress/i18n';
defineProps({
  title: String,
  disabled: Boolean,
  saving: Boolean,
  message: String,
  messageType: String,
  showButtons: { type: Boolean, default: true },
});

const emit = defineEmits(['submit', 'cancel']);

const handleSubmit = () => {
  emit('submit');
};

const cancel = () => {
  emit('cancel');
};
</script>

<style scoped>
.success-message {
  background-color: var(--admin-badge-green-bg);
  border: 1px solid var(--admin-badge-green-text);
  color: var(--admin-badge-green-text);
}
.error-message {
  background-color: var(--admin-badge-red-bg);
  border: 1px solid var(--admin-badge-red-text);
  color: var(--admin-badge-red-text);
}
</style>