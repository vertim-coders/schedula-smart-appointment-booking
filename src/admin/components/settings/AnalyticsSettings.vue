<template>
  <BaseFormLayout :title="__('Reporting & Analytics Settings', 'schedula-smart-appointment-booking')"
                  :description="__('Enabling users to gain insights from their booking data.', 'schedula-smart-appointment-booking')" :showButtons="false">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="col-span-1">
        <label class="block text-sm font-medium text-gray-700">{{ __('Export Types', 'schedula-smart-appointment-booking') }}</label>
        <div class="mt-2 space-y-2">
          <div class="flex items-center">
            <input id="export-appointments" name="export-type" type="checkbox" v-model="exportType" value="appointments"
                   class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
            <label for="export-appointments" class="ml-2 block text-sm text-gray-900">{{ __('Appointments', 'schedula-smart-appointment-booking') }}</label>
          </div>
          <div class="flex items-center">
            <input id="export-clients" name="export-type" type="checkbox" v-model="exportType" value="clients"
                   class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
            <label for="export-clients" class="ml-2 block text-sm text-gray-900">{{ __('Clients', 'schedula-smart-appointment-booking') }}</label>
          </div>
          <div class="flex items-center">
            <input id="export-payments" name="export-type" type="checkbox" v-model="exportType" value="payments"
                   class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
            <label for="export-payments" class="ml-2 block text-sm text-gray-900">{{ __('Payments', 'schedula-smart-appointment-booking') }}</label>
          </div>
        </div>
      </div>
      <div class="col-span-1">
        <label for="date-range-start" class="block text-sm font-medium text-gray-700">{{ __('Date Range for Export', 'schedula-smart-appointment-booking') }}</label>
        <div class="mt-1 grid grid-cols-2 gap-4">
          <div>
            <input type="text" name="date-range-start" id="date-range-start" ref="startDateInput" :placeholder="__('Start Date', 'schedula-smart-appointment-booking')"
                   class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full p-3 text-lg border-gray-300 rounded-md">
          </div>
          <div>
            <input type="text" name="date-range-end" id="date-range-end" ref="endDateInput" :placeholder="__('End Date', 'schedula-smart-appointment-booking')"
                   class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full p-3 text-lg border-gray-300 rounded-md">
          </div>
        </div>
      </div>
    </div>
    <div class="mt-6">
      <button type="button" @click="handleExport" :disabled="isLoading"
              class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
        {{ isLoading ? __('Exporting...', 'schedula-smart-appointment-booking') : __('Export Data', 'schedula-smart-appointment-booking') }}
      </button>
      <p v-if="exportStatus" :class="{'text-green-600': exportStatus.includes('successful'), 'text-red-600': exportStatus.includes('failed')}" class="mt-4 text-sm">{{ exportStatus }}</p>
    </div>
  </BaseFormLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, defineExpose } from 'vue';
import BaseFormLayout from '../common/BaseFormLayout.vue';
import { analyticsApi } from '@/admin/api.js';
import Datepicker from 'flowbite-datepicker/Datepicker';
import { __ } from '@wordpress/i18n';

const exportType = ref([]); // To hold selected export types (appointments, clients, payments)
const startDate = ref('');
const endDate = ref('');
const isLoading = ref(false);
const exportStatus = ref('');

const startDateInput = ref(null);
const endDateInput = ref(null);
let startDatepicker = null;
let endDatepicker = null;

const handleExport = async () => {
  isLoading.value = true;
  exportStatus.value = '';

  if (exportType.value.length === 0) {
    exportStatus.value = __('Please select at least one export type.', 'schedula-smart-appointment-booking');
    isLoading.value = false;
    return;
  }

  try {
    const typesToExport = exportType.value.join(',');

    const response = await analyticsApi.exportData(
      typesToExport,
      startDate.value,
      endDate.value
    );

    // Create a blob from the response data
    const blob = new Blob([response.data], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);

    // Create a link element and trigger the download
    const link = document.createElement('a');
    link.href = url;
    const filename = exportType.value.length > 1 ? 'schedula-export.csv' : `schedula-${exportType.value[0]}-export.csv`;
    link.setAttribute('download', filename); // Dynamic filename
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);

    exportStatus.value = __('Export successful!', 'schedula-smart-appointment-booking');
  } catch (error) {
    console.error('Error exporting data:', error);
    exportStatus.value = __('Export failed. Please try again.', 'schedula-smart-appointment-booking');
    if (error.response && error.response.data) {
      // Attempt to read error message from blob if available
      const reader = new FileReader();
      reader.onload = () => {
        try {
          const errorData = JSON.parse(reader.result);
          if (errorData.message) {
            exportStatus.value = __('Export failed: ', 'schedula-smart-appointment-booking') + errorData.message;
          }
        } catch (e) {
          // Not a JSON error, use generic message
        }
      };
      reader.readAsText(error.response.data);
    }
  } finally {
    isLoading.value = false;
  }
};

const reset = () => {
  exportType.value = [];
  startDate.value = '';
  endDate.value = '';
  exportStatus.value = '';
  if (startDatepicker) {
    startDatepicker.setDate('');
  }
  if (endDatepicker) {
    endDatepicker.setDate('');
  }
};

defineExpose({
  reset,
});

onMounted(() => {
  startDatepicker = new Datepicker(startDateInput.value, {
    format: 'yyyy-mm-dd',
    autohide: true,
  });
  endDatepicker = new Datepicker(endDateInput.value, {
    format: 'yyyy-mm-dd',
    autohide: true,
  });

  startDateInput.value.addEventListener('changeDate', (event) => {
    startDate.value = event.detail.date ? event.detail.date.toISOString().slice(0, 10) : '';
  });
  endDateInput.value.addEventListener('changeDate', (event) => {
    endDate.value = event.detail.date ? event.detail.date.toISOString().slice(0, 10) : '';
  });
});

onUnmounted(() => {
  if (startDatepicker) {
    startDatepicker.destroy();
  }
  if (endDatepicker) {
    endDatepicker.destroy();
  }
});
</script>
