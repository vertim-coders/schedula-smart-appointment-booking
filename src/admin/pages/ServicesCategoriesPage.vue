<template>
  <div>
    <h2 class="text-3xl font-bold text-gray-900 mb-6">{{ __('Services & Categories Management', 'schedula-smart-appointment-booking') }}</h2>
    <!-- Conditional Views -->
    <!-- View 1: Main List Display -->
    <div v-if="currentView === 'list'">
      <!-- Actions and Search Container -->
      <div class="bg-white p-6 rounded-lg shadow-md content-card">
        <!-- TOP ROW: Action Buttons, Rows per page selector, and Column Visibility -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
          <!-- Action Buttons -->
          <div class="flex flex-wrap gap-4">
            <button :disabled="loadingCategories || loadingServices" @click="openAddCategoryModal" class="disabled:cursor-not-allowed disabled:opacity-10 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-[#1749a9] hover:bg-[#133a8c] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1749a9] transition duration-150 ease-in-out">
              <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" /></svg>
              {{ __('Add New Category', 'schedula-smart-appointment-booking') }}
            </button>
            <button :disabled="loadingCategories || loadingServices" @click="currentView = 'manage-categories'" class="disabled:cursor-not-allowed disabled:opacity-10 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
              <!-- Corrected SVG path for better compatibility -->
              <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM13 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2h-2zM13 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2h-2z" />
              </svg>
              {{ __('Manage Categories', 'schedula-smart-appointment-booking') }}
            </button>
            <button :disabled="loadingCategories || loadingServices" @click="openAddServiceModal" class="disabled:cursor-not-allowed disabled:opacity-10 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
              <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" /></svg>
              {{ __('Add New Service', 'schedula-smart-appointment-booking') }}
            </button>
          </div>

          <div class="flex items-center gap-4">
            <!-- Top Rows per page selector -->
            <div v-if="itemsPerPageOptions.length > 1" class="flex items-center gap-2">
              <span class="text-sm text-gray-700 whitespace-nowrap">{{ __('Rows per page:', 'schedula-smart-appointment-booking') }}</span>
              <select v-model="itemsPerPage" @change="handleItemsPerPageChange" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm p-1.5 input-field">
                <option v-for="option in itemsPerPageOptions" :key="option" :value="option">{{ option }}</option>
              </select>
            </div>

            <!-- Column Visibility Toggle -->
            <div class="relative" ref="columnMenuContainer">
              <button @click="toggleColumnMenu" class="p-2 rounded-full focus:outline-none column-visibility-button">
                <i class="fas fa-eye"></i>
              </button>
              <div v-if="showColumnMenu" class="absolute right-0 mt-2 w-48 rounded-md shadow-lg z-10" :style="{ backgroundColor: 'var(--admin-card-bg-color)', border: '1px solid var(--admin-border-color)' }">
                <div v-for="(visible, key) in columns" :key="key" class="px-4 py-2">
                  <label class="flex items-center">
                    <input type="checkbox" :checked="visible" @change="toggleColumn(key)" class="form-checkbox h-5 w-5 text-indigo-600" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-text-color)' }">{{ formatColumnName(key) }}</span>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Service Search Bar -->
        <div class="flex flex-wrap items-center gap-4 w-full sm:w-auto justify-start">
          <div class="relative w-full sm:w-80">
            <BaseInput
              id="search-service"
              v-model="searchServiceName"
              :placeholder="__('Search by service name', 'schedula-smart-appointment-booking')"
              @input="debouncedFetchServices"
              icon="fas fa-cut"
            />
          </div>
        </div>
      </div>

      <!-- Main Services List -->
      <div>
        <div class="flex justify-between items-center h-8 mb-4">
          <h3 class="text-xl font-semibold text-gray-700">{{ __('All Services', 'schedula-smart-appointment-booking') }}</h3>
          <button 
            v-if="selectedServices.length > 0"
            @click="openBulkDeleteModal"
            :disabled="isDeleting"
            class="inline-flex items-center px-2 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150 ease-in-out bg-red-600 text-white hover:bg-red-700">
            <svg v-if="isDeleting" class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <i v-else class="fas fa-trash mr-2"></i>
            {{ sprintf(__('Delete Selected (%d)', 'schedula-smart-appointment-booking'), selectedServices.length) }}
          </button>
        </div>
        <ServiceList 
          :services="services" 
          :categories="categories" 
          :loading="loadingServices" 
          :error="servicesError" 
          :columns="columns" 
          :sortBy="sortBy"
          :sortDirection="sortDirection"
          :selectedServices="selectedServices"
          @edit-service="startEditService" 
          @delete-service="handleDeleteService"
          @update:sort="handleSort"
          @update:selection="handleSelectionChange"
        />
      </div>

      <!-- Pagination Controls -->
      <div v-if="totalPages > 1 || itemsPerPageOptions.length > 1" class="flex flex-col sm:flex-row items-center justify-between mt-6 p-4 rounded-lg shadow-md content-card">
        <!-- Rows per page selector -->
        <div class="flex items-center gap-2 mb-4 sm:mb-0">
          <span class="text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Rows per page:', 'schedula-smart-appointment-booking') }}</span>
          <select v-model="itemsPerPage" @change="handleItemsPerPageChange" class="rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm p-1.5 input-field">
            <option v-for="option in itemsPerPageOptions" :key="option" :value="option">{{ option }}</option>
          </select>
        </div>

        <!-- Pagination buttons and info -->
        <div class="flex items-center gap-2">
          <span class="text-sm mr-2" :style="{ color: 'var(--admin-card-text-color)' }">{{ sprintf(__('Page %d of %d', 'schedula-smart-appointment-booking'), currentPage, totalPages) }}</span>

          <button
            @click="goToPage(1)"
            :disabled="currentPage === 1"
            class="p-2 rounded-md disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150"
            :style="{ 
              backgroundColor: 'var(--admin-input-bg-color)', 
              color: currentPage === 1 ? 'var(--admin-input-text-muted)' : 'var(--admin-input-text-color)'
            }"
            :title="__('First Page', 'schedula-smart-appointment-booking')"
          >
            <i class="fas fa-angle-double-left"></i>
          </button>
          <button
            @click="prevPage"
            :disabled="currentPage === 1"
            class="p-2 rounded-md disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150"
            :style="{ 
              backgroundColor: 'var(--admin-input-bg-color)', 
              color: currentPage === 1 ? 'var(--admin-input-text-muted)' : 'var(--admin-input-text-color)'
            }"
            :title="__('Previous Page', 'schedula-smart-appointment-booking')"
          >
            <i class="fas fa-angle-left"></i>
          </button>
          <button
            @click="nextPage"
            :disabled="currentPage === totalPages"
            class="p-2 rounded-md disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150"
            :style="{ 
              backgroundColor: 'var(--admin-input-bg-color)', 
              color: currentPage === totalPages ? 'var(--admin-input-text-muted)' : 'var(--admin-input-text-color)'
            }"
            :title="__('Next Page', 'schedula-smart-appointment-booking')"
          >
            <i class="fas fa-angle-right"></i>
          </button>
          <button
            @click="goToPage(totalPages)"
            :disabled="currentPage === totalPages"
            class="p-2 rounded-md disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150"
            :style="{ 
              backgroundColor: 'var(--admin-input-bg-color)', 
              color: currentPage === totalPages ? 'var(--admin-input-text-muted)' : 'var(--admin-input-text-color)'
            }"
            :title="__('Last Page', 'schedula-smart-appointment-booking')"
          >
            <i class="fas fa-angle-double-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- View 2: Add/Edit Service Form -->
    <div v-if="currentView === 'add-edit-service'">
      <ServiceForm :initial-service="serviceToEdit" :categories="categories" :total-services="totalServices" @service-saved="handleServiceSaved" @close-form="closeServiceFormModal" @limit-reached="() => showUpgradeModal = true" />
    </div>

    <!-- View 3: Manage Categories Page -->
    <div v-if="currentView === 'manage-categories'">
      <ManageCategoriesPage :categories="categories" :loading="loadingCategories" :error="categoriesError" @close="closeManageCategoriesModal" @categories-updated="handleCategoriesUpdatedFromModal" @delete-category="handleDeleteCategory" />
    </div>

    <!-- Modal for Add/Edit Category (remains a modal as it's simpler) -->
    <transition name="modal-fade">
      <div v-if="showCategoryFormModal" class="fixed inset-0 z-50 flex items-start justify-center bg-black bg-opacity-50 overflow-y-auto" :style="{ paddingTop: wpAdminBarHeight + 'px' }">
        <div class="relative bg-white rounded-lg shadow-xl m-4 sm:m-6 md:m-8 lg:m-12 w-full max-w-lg p-0">
          <CategoryForm :initial-category="categoryToEdit" @category-saved="handleCategorySaved" @close-form="closeCategoryFormModal" />
        </div>
      </div>
    </transition>

    <!-- Unified Delete Confirmation Modal -->
    <div v-if="showDeleteModal || showBulkDeleteModal" class="fixed inset-0 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50" :style="{ backgroundColor: 'rgba(0, 0, 0, 0.5)' }">
      <div class="p-8 rounded-lg shadow-xl max-w-sm mx-auto modal-content">
        <h3 class="text-lg font-bold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{ __('Confirm Deletion', 'schedula-smart-appointment-booking') }}</h3>
        
        <!-- Content for single service deletion -->
        <div v-if="showDeleteModal">
          <p class="mb-4" :style="{ color: 'var(--admin-card-text-color)' }" v-html="sprintf(__('Are you sure you want to delete service &quot;<span class=\'font-semibold\'>%s</span>&quot;?', 'schedula-smart-appointment-booking'), serviceToDelete ? serviceToDelete.title : '')"></p>
        </div>

        <!-- Content for bulk service deletion -->
        <div v-else-if="showBulkDeleteModal">
          <p class="mb-4" :style="{ color: 'var(--admin-card-text-color)' }">
            {{ sprintf(__('Are you sure you want to delete %d selected services?', 'schedula-smart-appointment-booking'), selectedServices.length) }}
          </p>
          <p class="text-sm mt-2" :style="{ color: 'var(--admin-suggestion-red-text)' }">
            <strong>{{ __('Warning:', 'schedula-smart-appointment-booking') }}</strong> {{ __('This action cannot be undone.', 'schedula-smart-appointment-booking') }}
          </p>
        </div>

        <div class="flex justify-end space-x-4 mt-4">
          <button @click="closeDeleteModals" class="px-4 py-2 rounded-md" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)' }">{{ __('Cancel', 'schedula-smart-appointment-booking') }}</button>
          <button 
            @click="showDeleteModal ? confirmDelete() : confirmBulkDelete()" 
            :disabled="isDeleting" 
            class="px-4 py-2 rounded-md bg-red-600 text-white hover:bg-red-700 flex items-center justify-center w-24">
              <svg v-if="isDeleting" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span v-else>{{ __('Delete', 'schedula-smart-appointment-booking') }}</span>
            </button>
        </div>
      </div>
    </div>

    <!-- Upgrade to Pro Modal -->
    <div v-if="showUpgradeModal" class="fixed inset-0 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50" :style="{ backgroundColor: 'rgba(0, 0, 0, 0.5)' }">
      <div class="p-8 rounded-lg shadow-xl max-w-sm mx-auto modal-content">
        <h3 class="text-lg font-bold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{ __('Upgrade to Pro for More Services', 'schedula-smart-appointment-booking') }}</h3>
        <p class="mb-4" :style="{ color: 'var(--admin-card-text-color)' }">
          {{ __('You have reached the limit of 5 services for the free version. To add more, please upgrade to the Pro version of Schedula.', 'schedula-smart-appointment-booking') }}
        </p>
        <div class="flex justify-end space-x-4 mt-6">
          <button @click="showUpgradeModal = false" class="px-4 py-2 rounded-md" :style="{ backgroundColor: 'var(--admin-button-third-bg)', color: 'var(--admin-button-secondary-text)' }">{{ __('Cancel', 'schedula-smart-appointment-booking') }}</button>
          <a href="https://vertimcoders.com/tests-product-landing/apps/schedula-for-woocommerce/" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600  focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            {{ __('Upgrade to Pro', 'schedula-smart-appointment-booking') }}
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, reactive, onUnmounted } from 'vue';
import { __, sprintf } from '@wordpress/i18n';
import CategoryForm from '../components/categories/CategoryForm.vue';
import ServiceList from '../components/services/ServicesListe.vue';
import ServiceForm from '../components/services/ServicesForm.vue';
import ManageCategoriesPage from '../components/modals/ManageCategoriesModal.vue';
import BaseInput from '../components/common/BaseInput.vue';
import { servicesCategoriesApi } from '../api.js';
import { useToast } from '../composables/useToast.js'; // Import useToast

// State for UI visibility
const currentView = ref('list'); // 'list', 'add-edit-service', 'manage-categories'
const showCategoryFormModal = ref(false); // This one remains a modal for simplicity
const showDeleteModal = ref(false);
const showBulkDeleteModal = ref(false);
const showUpgradeModal = ref(false);
const isDeleting = ref(false);

// State for Data (Categories & Services)
const categories = ref([]);
const loadingCategories = ref(true);
const categoriesError = ref(null);

const services = ref([]);
const loadingServices = ref(true);
const servicesError = ref(null);

// State for Editing (used by forms)
const categoryToEdit = ref(null);
const serviceToEdit = ref(null);
const serviceToDelete = ref(null);

const selectedServices = ref([]);

const columnMenuContainer = ref(null);

// Column visibility state
const showColumnMenu = ref(false);
const columns = reactive({
  title: true,
  category: true,
  duration: true,
  price: true,
});

const columnDisplayNames = {
  title: __('Title', 'schedula-smart-appointment-booking'),
  category: __('Category', 'schedula-smart-appointment-booking'),
  duration: __('Duration', 'schedula-smart-appointment-booking'),
  price: __('Price', 'schedula-smart-appointment-booking'),
};

// Sorting state for services
const sortBy = ref('title'); // Default sort by title
const sortDirection = ref('asc'); // Default sort direction

const toggleColumnMenu = () => {
  showColumnMenu.value = !showColumnMenu.value;
};

const toggleColumn = (key) => {
  columns[key] = !columns[key];
};

const formatColumnName = (key) => {
  return columnDisplayNames[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

// Search state for services
const searchServiceName = ref('');
let debounceTimeoutServices = null; // Separate debounce for services

// --- Pagination State ---
const currentPage = ref(1);
const itemsPerPageOptions = [5, 10, 20, 50]; // Options for rows per page
const itemsPerPage = ref(itemsPerPageOptions[1]); // Default to 10 items per page
const totalServices = ref(0); // Total number of services (from API)

// Computed property for total pages
const totalPages = computed(() => {
  const total = Number(totalServices.value);
  const perPage = Number(itemsPerPage.value);
  if (perPage <= 0) return 1; // Avoid division by zero or negative
  return Math.ceil(total / perPage);
});

// Dynamic admin bar height for modal positioning
const wpAdminBarHeight = ref(32);

// --- Use the toast composable ---
const { success, error: toastError, info } = useToast();

// --- Pagination Methods ---
const goToPage = (page) => {
  const targetPage = parseInt(page);
  if (targetPage >= 1 && targetPage <= totalPages.value) {
    currentPage.value = targetPage;
    fetchServices();
    // Scroll to top of the services list for better UX
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
    fetchServices();
    // Scroll to top of the services list for better UX
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
    fetchServices();
    // Scroll to top of the services list for better UX
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

const handleItemsPerPageChange = (event) => {
  const newValue = parseInt(event?.target?.value) || itemsPerPage.value;
  itemsPerPage.value = newValue;
  currentPage.value = 1; // Reset to first page when items per page changes
  fetchServices();
};

const handleSort = ({ sortBy: column, sortDirection: direction }) => {
  sortBy.value = column;
  sortDirection.value = direction;
  currentPage.value = 1; // Reset to first page on new sort
  fetchServices();
};

const handleClickOutside = (event) => {
  if (showColumnMenu.value && columnMenuContainer.value && !columnMenuContainer.value.contains(event.target)) {
    showColumnMenu.value = false;
  }
};

onMounted(() => {
  const bar = document.getElementById('wpadminbar');
  if (bar) {
    wpAdminBarHeight.value = bar.offsetHeight;
  }
  fetchCategories();
  fetchServices();
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

// --- Helper Functions ---

// Debounce function for services
const debouncedFetchServices = () => {
  clearTimeout(debounceTimeoutServices);
  debounceTimeoutServices = setTimeout(() => {
    currentPage.value = 1; // Reset to first page on new search
    fetchServices();
  }, 300); // Adjust debounce time as needed
};

// --- Data Fetching (Now using Axios API client) ---
const fetchCategories = async () => {
  loadingCategories.value = true;
  categoriesError.value = null;
  try {
    const response = await servicesCategoriesApi.getCategories(); // Using Axios API client
    categories.value = response.data; // Axios puts the response data in .data
  } catch (err) {
    categoriesError.value = err.response?.data?.message || err.message || __('Failed to fetch categories.', 'schedula-smart-appointment-booking');
    toastError(categoriesError.value); // Use toast for error
    console.error('Error fetching categories:', err);
  } finally {
    loadingCategories.value = false;
  }
};

const fetchServices = async () => {
  loadingServices.value = true;
  servicesError.value = null;

  try {
    const params = {
      search: searchServiceName.value,
      page: currentPage.value,
      per_page: itemsPerPage.value,
      sort_by: sortBy.value,
      sort_direction: sortDirection.value,
    };

    const response = await servicesCategoriesApi.getServices(params);
    
    if (response.data && typeof response.data === 'object' && !Array.isArray(response.data)) {
      if (Array.isArray(response.data.services) && typeof response.data.total_items === 'number') {
        services.value = response.data.services;
        totalServices.value = parseInt(response.data.total_items) || 0;
      } else {
        console.error('Malformed structured API response:', response.data);
        services.value = [];
        totalServices.value = 0;
        toastError(__('Received malformed data from server. Please try again.', 'schedula-smart-appointment-booking'));
        return;
      }
    } else if (Array.isArray(response.data)) {
      console.warn('API returned legacy plain array format. Consider updating the backend.');
      
      let allServices = response.data;
      
      if (searchServiceName.value) {
        const searchTerm = searchServiceName.value.toLowerCase();
        allServices = allServices.filter(service =>
          (service.title && service.title.toLowerCase().includes(searchTerm)) ||
          (service.description && service.description.toLowerCase().includes(searchTerm))
        );
      }
      
      allServices.sort((a, b) => {
        let aVal = a[sortBy.value];
        let bVal = b[sortBy.value];
        
        if (typeof aVal === 'string') {
          aVal = aVal.toLowerCase();
          bVal = bVal.toLowerCase();
        }
        
        if (sortDirection.value === 'desc') {
          return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
        } else {
          return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
        }
      });
      
      totalServices.value = allServices.length;
      
      const startIndex = (currentPage.value - 1) * itemsPerPage.value;
      const endIndex = startIndex + itemsPerPage.value;
      services.value = allServices.slice(startIndex, endIndex);
      
      info(__('Using client-side processing due to legacy API format.', 'schedula-smart-appointment-booking'));
    } else {
      console.error('Unexpected API response format:', response.data);
      services.value = [];
      totalServices.value = 0;
      toastError(__('Received unexpected data format from server. Please check the console and contact support.', 'schedula-smart-appointment-booking'));
    }

  } catch (err) {
    const errorMessage = err.response?.data?.message || err.message || __('Failed to fetch services.', 'schedula-smart-appointment-booking');
    servicesError.value = errorMessage;
    toastError(errorMessage);
    console.error('Error fetching services:', err);
    
    services.value = [];
    totalServices.value = 0;
  } finally {
    loadingServices.value = false;
  }
};

// --- UI Modal Control Functions ---
const openAddCategoryModal = () => {
  categoryToEdit.value = null;
  showCategoryFormModal.value = true;
  currentView.value = 'list';
};

const closeCategoryFormModal = () => {
  showCategoryFormModal.value = false;
  categoryToEdit.value = null;
};

const openAddServiceModal = () => {
  if (totalServices.value >= 5) {
    showUpgradeModal.value = true;
    return;
  }
  serviceToEdit.value = null;
  currentView.value = 'add-edit-service';
};

const closeServiceFormModal = () => {
  currentView.value = 'list';
  serviceToEdit.value = null;
};

const closeManageCategoriesModal = () => {
  currentView.value = 'list';
  fetchCategories();
  fetchServices();
};

// --- Category Handlers (Now using Axios API client) ---
const handleCategorySaved = async (savedCategory) => {
  await fetchCategories();
  success(savedCategory.id ? __('Category updated successfully!', 'schedula-smart-appointment-booking') : __('Category added successfully!', 'schedula-smart-appointment-booking'));
  closeCategoryFormModal();
};

const handleCategoriesUpdatedFromModal = async (updatedCategories) => {
  let successCount = 0;
  let errorCount = 0;
  for (const category of updatedCategories) {
    try {
      await servicesCategoriesApi.updateCategory(category.id, category);
      successCount++;
    } catch (err) {
      errorCount++;
      console.error(`Error updating category ${category.name}:`, err);
      const errorMessage = err.response?.data?.message || err.message || sprintf(__('Failed to update category "%s".', 'schedula-smart-appointment-booking'), category.name);
      toastError(sprintf(__('Error updating some categories: %s', 'schedula-smart-appointment-booking'), errorMessage));
    }
  }

  await fetchCategories();
  if (successCount > 0 && errorCount === 0) {
    success(__('All categories updated successfully!', 'schedula-smart-appointment-booking'));
  } else if (successCount > 0 && errorCount > 0) {
    info(sprintf(__('Updated %d categories, but %d failed. Check console for details.', 'schedula-smart-appointment-booking'), successCount, errorCount));
  } else if (errorCount > 0) {
    toastError(__('Failed to update any categories. Check console for details.', 'schedula-smart-appointment-booking'));
  }
  closeManageCategoriesModal();
};

const handleDeleteCategory = async (categoryId) => {
  try {
    await servicesCategoriesApi.deleteCategory(categoryId);
    await fetchCategories();
    success(__('Category deleted successfully!', 'schedula-smart-appointment-booking'));
  } catch (err) {
    const errorMessage = err.response?.data?.message || err.message || __('Failed to delete category.', 'schedula-smart-appointment-booking');
    toastError(errorMessage);
    console.error('Error deleting category:', err);
  }
};

// --- Service Handlers (Now using Axios API client) ---
const handleServiceSaved = async (savedService) => {
  currentPage.value = 1;
  await fetchServices();
  success(savedService.id ? __('Service updated successfully!', 'schedula-smart-appointment-booking') : __('Service added successfully!', 'schedula-smart-appointment-booking'));
  closeServiceFormModal();
};

const startEditService = (service) => {
  serviceToEdit.value = {
    ...service,
    id: Number(service.id),
    category_id: Number(service.category_id),
    duration: Number(service.duration),
    price: Number(service.price),
    description: service.description || ''
  };
  currentView.value = 'add-edit-service';
};

const handleDeleteService = (service) => {
  serviceToDelete.value = service;
  showDeleteModal.value = true;
};

const confirmDelete = async () => {
  if (!serviceToDelete.value) return;
  isDeleting.value = true;
  try {
    await servicesCategoriesApi.deleteService(serviceToDelete.value.id);
    if (services.value.length === 1 && currentPage.value > 1) {
      currentPage.value--;
    }
    await fetchServices();
    success(__('Service deleted successfully!', 'schedula-smart-appointment-booking'));
  } catch (err) {
    const errorMessage = err.response?.data?.message || err.message || __('Failed to delete service.', 'schedula-smart-appointment-booking');
    toastError(errorMessage);
    console.error('Error deleting service:', err);
  } finally {
    isDeleting.value = false;
    closeDeleteModals();
  }
};

const handleSelectionChange = (serviceId, isSelected) => {
  if (isSelected) {
    if (!selectedServices.value.includes(serviceId)) {
      selectedServices.value.push(serviceId);
    }
  } else {
    const index = selectedServices.value.indexOf(serviceId);
    if (index > -1) {
      selectedServices.value.splice(index, 1);
    }
  }
};

const openBulkDeleteModal = () => {
  if (selectedServices.value.length === 0) {
    info(__('Please select at least one service to delete.', 'schedula-smart-appointment-booking'));
    return;
  }
  showBulkDeleteModal.value = true;
};

const confirmBulkDelete = async () => {
  isDeleting.value = true;
  const initialSelectedCount = selectedServices.value.length;
  try {
    for (const serviceId of selectedServices.value) {
      await servicesCategoriesApi.deleteService(serviceId);
    }
    success(__('Selected services deleted successfully!', 'schedula-smart-appointment-booking'));
    
    if (services.value.length === initialSelectedCount && currentPage.value > 1) {
      currentPage.value--;
    }
    await fetchServices();
  } catch (err) {
    const errorMessage = err.response?.data?.message || err.message || __('Error deleting selected services.', 'schedula-smart-appointment-booking');
    toastError(errorMessage);
    console.error('Error deleting services:', err);
  } finally {
    selectedServices.value = [];
    isDeleting.value = false;
    closeDeleteModals();
  }
};

const closeDeleteModals = () => {
  showDeleteModal.value = false;
  showBulkDeleteModal.value = false;
  serviceToDelete.value = null;
};

</script>

<style scoped>
/* Modal transition styles */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-fade-enter-active > div,
.modal-fade-leave-active > div {
  transition: transform 0.3s ease;
}

.modal-fade-enter-from > div,
.modal-fade-leave-to > div {
  transform: scale(0.95);
}

/* Content card style */
.content-card {
  background-color: var(--admin-card-bg);
  border: 1px solid var(--admin-border-color);
  color: var(--admin-text-color);
}

.column-visibility-button:hover {
  background-color: var(--admin-border-color);
}
</style>
