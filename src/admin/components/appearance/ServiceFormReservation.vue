<template>
  <div class="p-8" :class="{'dark-mode': appearanceSettings.adminDarkModeEnabled}" :style="adminCustomStyles">
    <!-- Skeleton Loader -->
    <div v-if="loading" class="animate-pulse">
      <div class="h-10 w-52 mb-6 rounded-md" :style="{ backgroundColor: 'var(--admin-input-bg-color)' }"></div>
      <div class="h-9 w-1/4 mb-6 rounded-md" :style="{ backgroundColor: 'var(--admin-input-bg-color)' }"></div>
      <div class="h-10 w-40 mb-6 rounded-md" :style="{ backgroundColor: 'var(--admin-button-primary-bg)' }"></div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="i in 3" :key="i" class="p-6 rounded-lg shadow-lg flex flex-col justify-between h-full" :style="{ backgroundColor: 'var(--admin-card-bg-color)' }">
          <div class="flex justify-between items-start mb-4">
            <div class="h-7 w-1/2 rounded-md" :style="{ backgroundColor: 'var(--admin-input-bg-color)' }"></div>
            <div class="flex space-x-2">
              <div class="h-8 w-8 rounded-full" :style="{ backgroundColor: 'var(--admin-input-bg-color)' }"></div>
              <div class="h-8 w-8 rounded-full" :style="{ backgroundColor: 'var(--admin-input-bg-color)' }"></div>
            </div>
          </div>
          <div class="mb-4 p-3 rounded-md border border-dashed" :style="{ borderColor: 'var(--admin-input-border-color)', backgroundColor: 'var(--admin-input-bg-color)' }">
            <div class="h-5 w-3/4 rounded-md" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)' }"></div>
          </div>
          <div class="space-y-3 text-sm mb-4">
            <div class="h-5 w-5/6 rounded-md" :style="{ backgroundColor: 'var(--admin-input-bg-color)' }"></div>
            <div class="h-5 w-4/6 rounded-md" :style="{ backgroundColor: 'var(--admin-input-bg-color)' }"></div>
            <div class="h-5 w-3/4 rounded-md" :style="{ backgroundColor: 'var(--admin-input-bg-color)' }"></div>
          </div>
        </div>
      </div>
    </div>

    <template v-else>
      <div class="mb-6">
        <router-link :to="{ name: 'Appearance' }" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2" :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-card-text-color)', '--tw-ring-color': 'var(--admin-link-indigo-bg)' }">
          <i class="fas fa-arrow-left mr-2"></i> {{ __('Back to Appearance Home', 'schedula-smart-appointment-booking') }}
        </router-link>
      </div>
      <h1 class="text-3xl font-bold mb-6" :style="{ color: 'var(--admin-text-color)' }">{{ __('Service Forms', 'schedula-smart-appointment-booking') }}</h1>
      <div class="mb-6">
        <button @click="addNewForm" class="px-4 py-2 rounded-md hover:bg-blue-600" :style="{ backgroundColor: 'var(--admin-button-primary-bg)', color: 'var(--admin-button-primary-text)' }">
          {{ __('Add a new form', 'schedula-smart-appointment-booking') }}
        </button>
      </div>

      <div v-if="forms.length === 0" :style="{ color: 'var(--admin-card-text-color)' }">
        {{ __('No forms created yet. Click "Add a new form" to get started.', 'schedula-smart-appointment-booking') }}
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="form in forms" :key="form.id" class="relative p-6 rounded-lg shadow-lg flex flex-col justify-between h-full transition-all duration-200 ease-in-out transform hover:scale-105 hover:shadow-xl" :style="{ backgroundColor: 'var(--admin-card-bg-color)', boxShadow: 'var(--admin-shadow)' }">
          <!-- Card Header with Form Name and Actions -->
          <div class="flex justify-between items-start mb-4">
            <h2 class="text-xl font-bold" :style="{ color: 'var(--admin-text-color)' }">{{ form.name }}</h2>
            <div class="flex space-x-2">
              <button @click="editForm(form.id)" class="p-2 rounded-full text-sm hover:bg-gray-200 transition-colors duration-150" :style="{ color: 'var(--admin-badge-yellow-text)' }">
                <i class="fas fa-edit"></i>
              </button>
              <button @click="confirmDeleteForm(form)" :disabled="deletingFormId === form.id" class="p-2 rounded-full text-sm hover:bg-gray-200 transition-colors duration-150" :style="{ color: 'var(--admin-badge-red-text)' }">
                <i v-if="deletingFormId === form.id" class="fas fa-spinner fa-spin"></i>
                <i v-else class="fas fa-trash-alt"></i>
              </button>
            </div>
          </div>

          <!-- Shortcode Section -->
          <div class="mb-4 p-3 rounded-md border border-dashed flex items-center justify-between" :style="{ borderColor: 'var(--admin-input-border-color)', backgroundColor: 'var(--admin-input-bg-color)' }">
            <code class="text-sm font-mono break-words" :style="{ color: 'var(--admin-input-text-color)' }">
              {{ form.shortcode }}
            </code>
            <button @click="copyShortcode(form.shortcode, form.id)" class="ml-2 px-2 py-1 rounded-md text-xs flex items-center hover:bg-gray-300 transition-colors duration-150" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)' }">
              <i class="fas fa-copy"></i>
            </button>
            <transition name="fade">
              <span v-if="copiedFormId === form.id" class="absolute -top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full whitespace-nowrap shadow-md">{{ __('Copied!', 'schedula-smart-appointment-booking') }}</span>
            </transition>
          </div>

          <!-- Associated Details Section -->
          <div class="space-y-2 text-sm mb-4">
            <p class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
              <i class="fas fa-concierge-bell mr-2 text-blue-500"></i>
              <span class="font-semibold">{{ __('Service:', 'schedula-smart-appointment-booking') }}</span> {{ form.serviceName || 'N/A' }}
            </p>
            <p class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
              <i class="fas fa-tags mr-2 text-purple-500"></i>
              <span class="font-semibold">{{ __('Category:', 'schedula-smart-appointment-booking') }}</span> {{ form.categoryName || 'N/A' }}
            </p>
            <p v-if="form.staffName" class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
              <i class="fas fa-user-tie mr-2 text-green-500"></i>
              <span class="font-semibold">{{ __('Staff:', 'schedula-smart-appointment-booking') }}</span> {{ form.staffName || 'N/A' }}
            </p>
          </div>
        </div>
      </div>
    </template>

    <!-- Delete Confirmation Modal (like CategoryList.vue) -->
    <transition name="modal-fade">
      <div v-if="showDeleteModal" class="fixed inset-0 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50" :style="{ backgroundColor: 'rgba(0, 0, 0, 0.5)' }">
        <div class="p-8 rounded-lg shadow-xl max-w-sm mx-auto modal-content" :style="{ backgroundColor: 'var(--admin-card-bg-color)' }">
          <h3 class="text-lg font-bold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{ __('Confirm Deletion', 'schedula-smart-appointment-booking') }}</h3>
          <p class="mb-4" :style="{ color: 'var(--admin-card-text-color)' }">{{ sprintf(__('Are you sure you want to delete the form "%s"?', 'schedula-smart-appointment-booking'), formToDelete ? formToDelete.name : '') }}</p>
          <div class="flex justify-end space-x-4">
            <button @click="showDeleteModal = false; formToDelete = null" class="px-4 py-2 rounded-md" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)', border: '1px solid var(--admin-input-border-color)' }">{{ __('Cancel', 'schedula-smart-appointment-booking') }}</button>
            <button @click="deleteFormConfirmed"  class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
              <svg v-if="deletingFormId === formToDelete.id" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="00 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span v-else>{{ __('Delete', 'schedula-smart-appointment-booking') }}</span>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>


<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { formsApi, servicesCategoriesApi, staffApi } from '../../api';
import { useAdminAppearance } from '../../composables/useAdminAppearance';
import { formEditState } from '../../state';
import { useToast } from '../../composables/useToast.js';
import { __, sprintf } from '@wordpress/i18n';

const { appearanceSettings, adminCustomStyles } = useAdminAppearance();

const router = useRouter();
const forms = ref([]);
const loading = ref(false);

const emit = defineEmits(['back-to-home']);
const { success, error } = useToast();

const goToAppearanceHome = () => {
  emit('back-to-home');
};

const copiedFormId = ref(null);
const copyShortcode = (shortcode, formId) => {
  try {
    navigator.clipboard.writeText(shortcode).then(() => {
      copiedFormId.value = formId;
      setTimeout(() => {
        copiedFormId.value = null;
      }, 2000);
    });
  } catch (err) {
    console.error('Failed to copy shortcode:', err);
  }
};

const showDeleteModal = ref(false);
const formToDelete = ref(null);
const deletingFormId = ref(null);

const confirmDeleteForm = (form) => {
  formToDelete.value = form;
  showDeleteModal.value = true;
};

const deleteFormConfirmed = async () => {
  if (!formToDelete.value) return;
  deletingFormId.value = formToDelete.value.id;
  try {
    await formsApi.deleteForm(formToDelete.value.id);
    // Invalidate cache to force a reload
    formEditState.formListCache.lastFetched = null;
    await fetchForms(); // Re-fetch after deletion
    success('Form deleted successfully!');
  } catch (err) {
    error('Error deleting form: ' + (err.response?.data?.message || err.message || 'Unknown error'));
  } finally {
    showDeleteModal.value = false;
    formToDelete.value = null;
    deletingFormId.value = null;
  }
};

const fetchForms = async () => {
  try {
    const [formsResponse, servicesResponse, categoriesResponse, staffResponse] = await Promise.all([
      formsApi.getForms(),
      servicesCategoriesApi.getServices({ per_page: -1 }),
      servicesCategoriesApi.getCategories({ per_page: -1 }),
      staffApi.getStaffMembers({ per_page: -1 }),
    ]);

    // Cache all data
    const cache = formEditState.formListCache;
    cache.allServices = (servicesResponse.data?.services || servicesResponse.data || []);
    cache.allCategories = (categoriesResponse.data?.categories || categoriesResponse.data || []);
    cache.allStaff = (staffResponse.data?.staff || staffResponse.data || []);
    
    cache.forms = formsResponse.data.map(form => {
      const service = cache.allServices.find(s => s.id === form.service_id);
      const category = cache.allCategories.find(c => c.id === form.category_id);
      const staff = cache.allStaff.find(s => s.id === form.staff_id);
      return {
        ...form,
        serviceName: service ? service.title : (form.serviceName || 'N/A'),
        categoryName: category ? category.name : (form.categoryName || 'N/A'),
        staffName: staff ? staff.name : (form.staffName || null),
        shortcode: `[schesab_service_form id="${form.id}"]`,
      };
    });
    
    forms.value = cache.forms;
    cache.lastFetched = Date.now();

  } catch (err) {
    console.error('Error fetching forms:', err);
    error('Error fetching forms: ' + (err.response?.data?.message || err.message || 'Unknown error'));
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loading.value = true;
  fetchForms();
});

const addNewForm = () => {
  // Pass the cached data to the edit state
  const cache = formEditState.formListCache;
  formEditState.form = null; // Important for identifying a new form
  formEditState.services = cache.allServices;
  formEditState.categories = cache.allCategories;
  formEditState.staff = cache.allStaff;
  router.push({ name: 'ServiceFormCustomization' });
};

const editForm = (formId) => {
  const cache = formEditState.formListCache;
  const formToEdit = cache.forms.find(f => f.id === formId);
  if (formToEdit) {
    formEditState.form = formToEdit;
    formEditState.services = cache.allServices;
    formEditState.categories = cache.allCategories;
    formEditState.staff = cache.allStaff;
  }
  router.push({ name: 'ServiceFormCustomization', params: { id: formId } });
};
</script>

<style scoped>
/* Scoped styles for this component */

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

/* Base styles for the form cards */
.p-4.rounded-lg.shadow-md.relative {
  transition: all 0.2s ease-in-out;
}

.p-4.rounded-lg.shadow-md.relative:hover {
  transform: translateY(-2px);
  box-shadow: var(--admin-hover-shadow); /* Assuming you have a CSS variable for hover shadow */
}

code {
  font-family: 'Fira Code', 'Cascadia Code', 'Consolas', monospace;
  font-size: 0.85em; /* Slightly smaller for compactness */
}

/* Fade transition for the "Copied!" message */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>