<template>
  <div class="mb-8">
    <div class="flex justify-between items-center mb-4">
    </div>

    <AppLoader v-if="loading" /> 
    <div v-else-if="error" class="px-4 py-3 rounded relative" role="alert" :style="{ backgroundColor: 'var(--admin-suggestion-red-bg)', borderColor: 'var(--admin-suggestion-red-border)', color: 'var(--admin-suggestion-red-text)' }">
      <span class="block sm:inline">{{ __('Error:', 'schedula-smart-appointment-booking') }} {{ error }}</span>
    </div>
    <div v-else-if="categories.length === 0" class="px-4 py-3 rounded relative" role="alert" :style="{ backgroundColor: 'var(--admin-input-border-color)', borderColor: 'var(--admin-input-border-color)', color: 'var(--admin-card-text-color)' }">
      <span class="block sm:inline">{{ __('No categories found.', 'schedula-smart-appointment-booking') }}</span>
    </div>
    <div v-else class="overflow-x-auto relative shadow-md sm:rounded-lg content-card">
      <table class="w-full text-sm text-left">
        <thead class="text-xs uppercase" :style="{ backgroundColor: 'var(--admin-table-header-bg)', color: 'var(--admin-table-header-text)' }">
          <tr>
            <th scope="col" class="py-3 px-6 w-12"></th> <!-- Column for drag handle -->
            <th scope="col" class="py-3 px-6">{{ __('Name', 'schedula-smart-appointment-booking') }}</th>
            <th scope="col" class="py-3 px-6">{{ __('Description', 'schedula-smart-appointment-booking') }}</th>
            <th scope="col" class="py-3 px-6 text-right">{{ __('Actions', 'schedula-smart-appointment-booking') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr 
            v-for="(category, index) in categories" 
            :key="category.id" 
            class="border-b hover:bg-gray-50" 
            :style="{ backgroundColor: 'var(--admin-card-bg)', borderColor: 'var(--admin-border-color)' }"
            draggable="true"
            @dragstart="onDragStart(category, index)"
            @dragover.prevent="onDragOver(index)"
            @drop="onDrop(index)"
          >
            <!-- Drag Handle -->
            <td class="py-4 px-3 cursor-grab" :style="{ color: 'var(--admin-text-muted)' }">
              <i class="fas fa-grip-lines"></i>
            </td>
            
            <th scope="row" class="py-4 px-6 font-medium whitespace-nowrap" :style="{ color: 'var(--admin-text-color)' }">
              <input
                type="text"
                v-model="category.name"
                @input="emit('update-category-inline', category)"
                class="block w-full rounded-md shadow-sm sm:text-sm p-1 border"
                :style="{ 
                  backgroundColor: 'var(--admin-field-bg)', 
                  borderColor: 'var(--admin-input-border-color)', 
                  color: 'var(--admin-text-color)',
                  '--tw-ring-color': 'var(--admin-link-indigo-bg)',
                  '--tw-border-color': 'var(--admin-input-border-color)'
                }"
              />
            </th>
           
            <td class="py-4 px-6">
              <textarea
                v-model="category.description"
                @input="emit('update-category-inline', category)"
                rows="2"
                class="block w-full rounded-md shadow-sm sm:text-sm p-1 border"
                :style="{ 
                  backgroundColor: 'var(--admin-field-bg)', 
                  borderColor: 'var(--admin-input-border-color)', 
                  color: 'var(--admin-text-color)',
                  '--tw-ring-color': 'var(--admin-link-indigo-bg)',
                  '--tw-border-color': 'var(--admin-input-border-color)'
                }"
              ></textarea>
            </td>
            
            <td class="py-4 px-6 text-right space-x-2">
              <button
                @click="confirmDelete(category)"
                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                :style="{ backgroundColor: 'var(--admin-suggestion-red-bg)', color: 'var(--admin-suggestion-red-text)', borderColor: 'var(--admin-suggestion-red-border)', border: '1px solid' }"
              >
                <i class="fas fa-trash mr-1"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    
    <div v-if="showDeleteModal" class="fixed inset-0 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50" :style="{ backgroundColor: 'rgba(0, 0, 0, 0.5)' }">
      <div class="p-8 rounded-lg shadow-xl max-w-sm mx-auto modal-content">
        <h3 class="text-lg font-bold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{ __('Confirm Deletion', 'schedula-smart-appointment-booking') }}</h3>
        <p class="mb-4" :style="{ color: 'var(--admin-card-text-color)' }">{{ sprintf(__('Are you sure you want to delete the category "%s"?', 'schedula-smart-appointment-booking'), categoryToDelete ? categoryToDelete.name : '') }}</p>
        <div class="flex justify-end space-x-4">
          <button @click="closeDeleteModal" class="px-4 py-2 rounded-md" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)', border: '1px solid var(--admin-input-border-color)' }">{{ __('Cancel', 'schedula-smart-appointment-booking') }}</button>
          <button @click="emitDelete" :disabled="isDeleting" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 flex items-center justify-center">
            <svg v-if="isDeleting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-if="isDeleting">{{ __('Deleting...', 'schedula-smart-appointment-booking') }}</span>
            <span v-else>{{ __('Delete', 'schedula-smart-appointment-booking') }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, defineProps, defineEmits } from 'vue';
import { __, sprintf } from '@wordpress/i18n';
import AppLoader from '../shared/AppLoader.vue'; 
import { useToast } from '../../composables/useToast.js'; // Import useToast


const props = defineProps({
  categories: {
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
});

const emit = defineEmits(['update-category-inline', 'delete-category', 'reorder-categories']); 
const { success, error: toastError } = useToast(); // Destructure toast functions


const showDeleteModal = ref(false);
const categoryToDelete = ref(null);
const isDeleting = ref(false);

const confirmDelete = (category) => {
  categoryToDelete.value = category;
  showDeleteModal.value = true;
};

const emitDelete = () => {
  if (categoryToDelete.value) {
    isDeleting.value = true;
    emit('delete-category', categoryToDelete.value.id);
    setTimeout(() => {
        closeDeleteModal();
    }, 500);
  } else {
    toastError(__('No category selected for deletion.', 'schedula-smart-appointment-booking')); // Toast for error if somehow triggered without selection
  }
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  categoryToDelete.value = null;
  isDeleting.value = false;
};

// --- Drag and Drop Logic ---
const draggedItem = ref(null);
const draggedIndex = ref(null);

const onDragStart = (item, index) => {
  draggedItem.value = item;
  draggedIndex.value = index;
  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData('text/plain', index); // Set data for Firefox compatibility
};

const onDragOver = (index) => {
  event.preventDefault(); // Necessary to allow dropping
  const targetRow = event.currentTarget;
  if (draggedIndex.value !== null && draggedIndex.value !== index) {
    // Add a visual indicator for where the item will be dropped
    if (index > draggedIndex.value) {
      targetRow.style.borderBottom = '2px solid var(--admin-link-indigo-bg)';
      targetRow.style.borderTop = '';
    } else {
      targetRow.style.borderTop = '2px solid var(--admin-link-indigo-bg)';
      targetRow.style.borderBottom = '';
    }
  }
};

const onDrop = (dropIndex) => {
  event.preventDefault();
  const targetRow = event.currentTarget;
  targetRow.style.borderTop = ''; // Clear visual indicators
  targetRow.style.borderBottom = ''; // Clear visual indicators

  if (draggedItem.value === null || draggedIndex.value === null || draggedIndex.value === dropIndex) {
    return;
  }

  // Emit an event to the parent component to handle the reordering
  emit('reorder-categories', {
    draggedItem: draggedItem.value,
    draggedIndex: draggedIndex.value,
    dropIndex: dropIndex
  });

  draggedItem.value = null;
  draggedIndex.value = null;
};

// Add dragleave to clear visual on leaving a drop target
const onDragLeave = () => {
  const targetRow = event.currentTarget;
  targetRow.style.borderTop = '';
  targetRow.style.borderBottom = '';
};
</script>

<style scoped>



/* Modal transition styles (kept here for the delete confirmation modal) */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

/* Optional: Add a slight scale/slide effect for the modal content */
.modal-fade-enter-active .modal-content,
.modal-fade-leave-active .modal-content {
  transition: transform 0.3s ease;
}

.modal-fade-enter-from .modal-content,
.modal-fade-leave-to .modal-content {
  transform: scale(0.95);
}

</style>
