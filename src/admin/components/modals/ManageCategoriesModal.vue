<template>
  <div class="p-6 rounded-lg shadow-md content-card">
    <h3 class="text-xl font-semibold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{ __('Manage Categories', 'schedula-smart-appointment-booking') }}</h3>
    <CategoryList
      :categories="editableCategories" 
      :loading="loading"
      :error="error"
      @delete-category="emitDeleteCategory"
      @update-category-inline="handleInlineCategoryChange"
      @reorder-categories="handleReorderCategories" 
    />
    <div class="flex justify-end space-x-3 mt-6">
      <button
        type="button"
        @click="emit('close')"
        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
        :style="{
          backgroundColor: 'var(--admin-input-bg-color)',
          color: 'var(--admin-card-text-color)',
          '--tw-ring-color': 'var(--admin-link-indigo-bg)'
        }"
      >
        <i class="fas fa-arrow-left mr-2"></i>
        {{ __('Back to Services', 'schedula-smart-appointment-booking') }}
      </button>

      
      <button
        type="button"
        @click="saveAllCategories"
        :disabled="saving"
        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed"
        :style="{ backgroundColor: 'var(--admin-link-indigo-bg)', color: 'var(--admin-link-indigo-text)' }"
      >
        <svg v-if="saving" class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ saving ? __('Saving...', 'schedula-smart-appointment-booking') : __('Save All Changes', 'schedula-smart-appointment-booking') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, defineProps, defineEmits, watch } from 'vue';
import CategoryList from '../categories/CategoryList.vue';
import { __ } from '@wordpress/i18n';

const props = defineProps({
  categories: {
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
});

const emit = defineEmits(['close', 'categories-updated', 'delete-category']);

const editableCategories = ref([]);
const saving = ref(false);

watch(() => props.categories, (newCategories) => {
  editableCategories.value = JSON.parse(JSON.stringify(newCategories));
}, { immediate: true, deep: true });

const handleInlineCategoryChange = (updatedCategory) => {
  const index = editableCategories.value.findIndex(cat => cat.id === updatedCategory.id);
  if (index !== -1) {
    editableCategories.value[index] = { ...updatedCategory };
  }
};

const handleReorderCategories = ({ draggedItem, draggedIndex, dropIndex }) => {
  // Perform the reordering in the local editableCategories array
  const reorderedCategories = [...editableCategories.value];
  const [removed] = reorderedCategories.splice(draggedIndex, 1);
  reorderedCategories.splice(dropIndex, 0, removed);
  editableCategories.value = reorderedCategories;

  // You might want to emit an event to the parent (ServicesCategoriesPage)
  // to persist this new order to the backend. For now, this just reorders locally.
  // emit('categories-reordered', editableCategories.value); 
};


const saveAllCategories = async () => {
  saving.value = true;
  try {
    emit('categories-updated', editableCategories.value); 
  } catch (err) {
    console.error('Error saving all categories:', err);
  } finally {
    saving.value = false;
  }
};

const emitDeleteCategory = (categoryId) => {
  emit('delete-category', categoryId);
};
</script>

<style scoped>
.content-card {
  background-color: var(--admin-card-bg);
  border: 1px solid var(--admin-border-color);
  color: var(--admin-text-color);
}
</style>
