<template>
  <div class="p-6 rounded-lg shadow-md content-card">
    <h3 class="text-xl font-semibold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{ isEditing ? __('Edit Category', 'schedula-smart-appointment-booking') : __('Add New Category', 'schedula-smart-appointment-booking') }}</h3>
    <form @submit.prevent="handleSubmit">
      <div class="mb-4">
        <BaseInput
          id="category-name"
          :label="__('Category Name', 'schedula-smart-appointment-booking')"
          icon="fas fa-tag"
          v-model="category.name"
          :required="true"
          :validationMessage="formError && formError.includes('Category name cannot be empty') ? __('Category name cannot be empty.', 'schedula-smart-appointment-booking') : ''"
        />
      </div>

      <div class="mb-4">
        <BaseTextarea
          id="category-description"
          :label="__('Description', 'schedula-smart-appointment-booking')"
          icon="fas fa-align-left"
          v-model="category.description"
          rows="3"
        />
      </div>

      <div class="flex justify-end space-x-3">
        <button
          @click="closeForm"
          type="button"
          class="px-6 py-2 rounded-lg shadow-sm text-sm font-medium inline-flex items-center" 
          :style="{ backgroundColor: 'var(--admin-button-third-bg)', color: 'var(--admin-button-secondary-text)' }"
        >
          <i class="fas fa-times mr-2"></i>{{ __('Cancel', 'schedula-smart-appointment-booking') }}
        </button>
        <button
          type="submit"
          :disabled="saving"
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
import { servicesCategoriesApi } from '../../api.js';
import BaseInput from '../../components/common/BaseInput.vue';
import BaseTextarea from '../../components/common/BaseTextarea.vue';
import { useToast } from '../../composables/useToast.js'; 

const props = defineProps({
  initialCategory: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['category-saved', 'close-form']);
const { error: toastError } = useToast(); // Destructure toast functions


const defaultCategory = { id: null, name: '', description: '' };
const category = ref({ ...defaultCategory });
const isEditing = ref(false);
const saving = ref(false);
// Removed formError, now using toasts
// const formError = ref(null);

watch(() => props.initialCategory, (newVal) => {
  if (newVal) {
    category.value = { ...newVal };
    isEditing.value = true;
  } else {
    category.value = { ...defaultCategory };
    isEditing.value = false;
  }
  // Removed formError.value = null;
}, { immediate: true });

const handleSubmit = async () => {
  // Removed formError.value = null;

  if (!category.value.name.trim()) {
    toastError(__('Category name cannot be empty.', 'schedula-smart-appointment-booking'));
    return;
  }

  saving.value = true;
  try {
    let response;
    if (isEditing.value) {
      response = await servicesCategoriesApi.updateCategory(category.value.id, category.value);
    } else {
      response = await servicesCategoriesApi.createCategory(category.value);
    }

    const savedCategory = response.data;
    emit('category-saved', savedCategory);
    category.value = { ...defaultCategory };
    isEditing.value = false;
    closeForm();
  } catch (err) {
    toastError(err.response?.data?.message || err.message || __('Failed to save category.', 'schedula-smart-appointment-booking'));
    console.error('Error saving category:', err);
  } finally {
    saving.value = false;
  }
};

const closeForm = () => {
  category.value = { ...defaultCategory };
  isEditing.value = false;
  // Removed formError.value = null;
  emit('close-form');
};
</script>
