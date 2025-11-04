<template>
  <transition name="modal-fade">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="close">
      <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-sm mx-4">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-800">{{ __('Edit Label', 'schedula-smart-appointment-booking') }}</h3>
          <button @click="close" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="mb-4">
          <label for="label-text" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Label Text', 'schedula-smart-appointment-booking') }}</label>
          <input
            type="text"
            id="label-text"
            v-model="editedLabelText"
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            @keyup.enter="save"
          />
        </div>
        <div class="flex justify-end space-x-2">
          <button
            @click="close"
            class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <i class="fas fa-times mr-2"></i>{{ __('Close', 'schedula-smart-appointment-booking') }}
          </button>
          <button
            @click="save"
            class="inline-flex items-center px-3 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <i class="fas fa-save mr-2"></i>{{ __('Save', 'schedula-smart-appointment-booking') }}
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, watch } from 'vue';
import { __ } from '@wordpress/i18n';

const props = defineProps({
  show: Boolean,
  initialText: String,
});

const emit = defineEmits(['save', 'close']);

const editedLabelText = ref(props.initialText);

// Watch for changes in initialText prop to update editedLabelText
watch(() => props.initialText, (newVal) => {
  editedLabelText.value = newVal;
});

const save = () => {
  emit('save', editedLabelText.value);
};

const close = () => {
  emit('close');
};
</script>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active {
  transition: opacity 0.3s ease;
}
.modal-fade-enter-from, .modal-fade-leave-to {
  opacity: 0;
}
</style>
