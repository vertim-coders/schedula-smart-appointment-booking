<template>
  <div>
    <h2 class="text-3xl font-bold mb-6" :style="{ color: 'var(--admin-text-color)' }">{{ __('Clients Management', 'schedula-smart-appointment-booking') }}</h2>

    <!-- Conditional Rendering: Show Client Form or Client List -->
    <div v-if="showClientForm" class="p-6 rounded-lg shadow-xl content-card">
      <!-- Client Form (Full Page) -->
      <ClientForm
        :initial-client="clientToEdit"
        @client-saved="handleClientSaved"
        @close-form="closeClientForm"
      />
    </div>

    <div v-else>
      <!-- Client List View -->

      <!-- Actions and Search Container -->
      <div class="p-6 rounded-lg shadow-md content-card">
        <!-- TOP ROW: Add New Client Button, Rows per page selector, and Column Visibility -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
          <!-- Action Button: Add New Client -->
          <button
            @click="openAddClientForm"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out" :style="{ backgroundColor: 'var(--admin-link-indigo-bg)', color: 'var(--admin-link-indigo-text)' }">
            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            {{ __('Add New Client', 'schedula-smart-appointment-booking') }}
          </button>

          <div class="flex items-center gap-4">
            <!-- Rows per page selector -->
            <div v-if="itemsPerPageOptions.length > 1" class="flex items-center gap-2">
              <span class="text-sm whitespace-nowrap" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Rows per page:', 'schedula-smart-appointment-booking') }}</span>
              <select v-model="itemsPerPage" @change="handleItemsPerPageChange" class="rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm p-1.5 input-field">
                <option v-for="option in itemsPerPageOptions" :key="option" :value="option">{{ option }}</option>
              </select>
            </div>

            <!-- Column Visibility Toggle -->
            <div class="relative" ref="columnMenuContainer">
              <button @click="toggleColumnMenu" class="p-2 rounded-full focus:outline-none column-visibility-button">
                <i class="fas fa-eye"></i>
              </button>
              <div v-if="showColumnMenu" class="absolute right-0 mt-2 w-48 rounded-md shadow-lg z-10" :style="{ backgroundColor: 'var(--admin-card-bg-color)' }">
                <div v-for="(visible, key) in columns" :key="key" class="px-4 py-2">
                  <label class="flex items-center">
                    <input type="checkbox" :checked="visible" @change="toggleColumn(key)" class="form-checkbox h-5 w-5" :style="{ color: 'var(--admin-link-indigo-text)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{ formatColumnName(key) }}</span>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Client Search Input -->
        <div class="relative w-full sm:w-80">
          <BaseInput
            id="search-client"
            v-model="searchClientName"
            :placeholder="__('Search by First Name, Last Name, or Email', 'schedula-smart-appointment-booking')"
            @input="debouncedFetchClients"
            icon="fas fa-user"
          />
        </div>
      </div>

      <!-- Main Clients List Display -->
      <div class="mt-8">
        <div class="flex justify-between items-center h-8 mb-4">
          <h3 class="text-xl font-semibold" :style="{ color: 'var(--admin-text-color)' }">{{ __('All Clients', 'schedula-smart-appointment-booking') }}</h3>
          <!-- New Bulk Delete Button - Appears when clients are selected, aligned with "All Clients" -->
          <button 
            v-if="selectedClients.length > 0"
            @click="openBulkDeleteModal"
            :disabled="isDeleting"
            class="inline-flex items-center px-2 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150 ease-in-out bg-red-600 text-white hover:bg-red-700">
            <svg v-if="isDeleting" class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <i v-else class="fas fa-trash mr-2"></i>
            {{ __('Delete Selected', 'schedula-smart-appointment-booking') }} ({{ selectedClients.length }})
          </button>
        </div>
        
      <ClientList
        :clients="clients"
        :loading="loadingClients"
        :error="clientsError"
        :columns="columns"
        :sortBy="sortBy"
        :sortDirection="sortDirection"
        :selectedClients="selectedClients"
        @edit-client="startEditClient" 
        @delete-client="handleDeleteClient"
        @update:sort="handleSort"
        @update:selection="handleSelectionChange"
      />
      </div>


      <!-- Pagination Controls (Original position at bottom) -->
      <div v-if="totalPages > 1 || itemsPerPageOptions.length > 1" class="flex flex-col sm:flex-row items-center justify-between mt-6 p-4 rounded-lg shadow-md content-card">
        <!-- Rows per page selector (original position) -->
        <div class="flex items-center gap-2 mb-4 sm:mb-0">
          <span class="text-sm text-gray-700">{{ __('Rows per page:', 'schedula-smart-appointment-booking') }}</span>
          <select v-model="itemsPerPage" @change="handleItemsPerPageChange" class="rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm p-1.5 input-field">
            <option v-for="option in itemsPerPageOptions" :key="option" :value="option">{{ option }}</option>
          </select>
        </div>

        <!-- Pagination buttons and info -->
        <div class="flex items-center gap-2">
          <span class="text-sm mr-2" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Page', 'schedula-smart-appointment-booking') }} {{ currentPage }} of {{ totalPages }}</span>
          
          <button
            @click="goToPage(1)"
            :disabled="currentPage === 1"
            class="p-2 rounded-md disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150"
            :style="{ 
              backgroundColor: 'var(--admin-input-bg-color)', 
              color: currentPage === 1 ? 'var(--admin-input-text-muted)' : 'var(--admin-input-text-color)'
            }"
            :title="__('First Page', 'schedula-smart-appointment-booking')">
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
            :title="__('Previous Page', 'schedula-smart-appointment-booking')">
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
            :title="__('Next Page', 'schedula-smart-appointment-booking')">
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
            :title="__('Last Page', 'schedula-smart-appointment-booking')">
            <i class="fas fa-angle-double-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Unified Delete Confirmation Modal -->
    <div v-if="showDeleteModal || showBulkDeleteModal" class="fixed inset-0 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50" :style="{ backgroundColor: 'rgba(0, 0, 0, 0.5)' }">
      <div class="p-8 rounded-lg shadow-xl max-w-sm mx-auto modal-content">
        <h3 class="text-lg font-bold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{ __('Confirm Deletion', 'schedula-smart-appointment-booking') }}</h3>
        
        <!-- Content for single client deletion -->
        <div v-if="showDeleteModal">
          <p class="mb-4" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Are you sure you want to delete client', 'schedula-smart-appointment-booking') }} "<span class="font-semibold">{{ clientToDelete ? clientToDelete.first_name + ' ' + clientToDelete.last_name : '' }}</span>"?</p>
          <p v-if="clientToDelete && clientToDelete.has_appointments" class="text-sm mt-2" :style="{ color: 'var(--admin-suggestion-red-text)' }">
            {{ __('**Warning:** This client has associated appointments. Deleting this client will **permanently delete all their linked appointments** as well.', 'schedula-smart-appointment-booking') }}
          </p>
        </div>

        <!-- Content for bulk client deletion -->
        <div v-else-if="showBulkDeleteModal">
          <p class="mb-4" :style="{ color: 'var(--admin-card-text-color)' }">
            {{ sprintf(_n('Are you sure you want to delete %d selected client?', 'Are you sure you want to delete %d selected clients?', bulkClientsToDeleteCount, 'schedula-smart-appointment-booking'), bulkClientsToDeleteCount) }}
          </p>
          <p class="text-sm mt-2" :style="{ color: 'var(--admin-suggestion-red-text)' }">
            {{ __('**Warning:** This action will **permanently delete all selected clients and their associated appointments**. This action cannot be undone.', 'schedula-smart-appointment-booking') }}
          </p>
        </div>

        <div class="flex justify-end space-x-4 mt-4">
          <button @click="closeDeleteModals" class="px-4 py-2 rounded-md" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)' }">{{ __('Cancel', 'schedula-smart-appointment-booking') }}</button>
          <button 
            @click="showDeleteModal ? confirmDeleteClient() : confirmBulkDeleteAction()" 
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
  </div>
</template>

<script setup>
import { __, _n, sprintf } from '@wordpress/i18n';
import { ref, onMounted, computed, reactive, onBeforeUnmount } from 'vue';
import ClientList from '../components/clients/ClientList.vue';
import ClientForm from '../components/clients/ClientForm.vue';
import BaseInput from '../components/common/BaseInput.vue';
import { customersApi } from '../api.js';
import { useToast } from '../composables/useToast.js'; // Import useToast

// State for UI visibility
const showClientForm = ref(false);
const showDeleteModal = ref(false); // For single client deletion
const showBulkDeleteModal = ref(false); // For bulk client deletion
const isDeleting = ref(false);

// State for Data
const clients = ref([]);
const loadingClients = ref(true);
const clientsError = ref(null);

// State for Editing/Deletion
const clientToEdit = ref(null); 
const clientToDelete = ref(null); // For single client deletion
const bulkClientsToDeleteCount = ref(0); // To show count in bulk delete modal

// State for selected clients
const selectedClients = ref([]);

// Column visibility state
const showColumnMenu = ref(false);
const columnMenuContainer = ref(null);
const columns = reactive({
  id: false,
  first_name: true,
  last_name: true,
  email: true,
  phone: true,
  notes: false,
});

const sortBy = ref('id'); // Default sort by ID
const sortDirection = ref('desc'); // Default sort direction

const toggleColumnMenu = () => {
  showColumnMenu.value = !showColumnMenu.value;
};

const handleClickOutside = (event) => {
  if (showColumnMenu.value && columnMenuContainer.value && !columnMenuContainer.value.contains(event.target)) {
    showColumnMenu.value = false;
  }
};

const toggleColumn = (key) => {
  columns[key] = !columns[key];
};

const formatColumnName = (key) => {
  return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}; 

const handleSort = ({ sortBy: column, sortDirection: direction }) => {
  sortBy.value = column;
  sortDirection.value = direction;
  currentPage.value = 1; // Reset to first page on new sort
  fetchClients();
};

// Search state
const searchClientName = ref('');
let debounceTimeout = null;

// --- Pagination State ---
const currentPage = ref(1);
const itemsPerPageOptions = [5, 10, 20, 50]; // Options for rows per page
const itemsPerPage = ref(itemsPerPageOptions[1]); // Default to 10 items per page
const totalClients = ref(0); // Total number of clients (from API)

// Computed property for total pages
const totalPages = computed(() => {
  return Math.ceil(totalClients.value / itemsPerPage.value);
});

onMounted(() => {
  fetchClients();
  document.addEventListener('mousedown', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleClickOutside);
});

// --- Use the toast composable ---
const { success, error } = useToast();


// Debounce function to limit API calls on input
const debouncedFetchClients = () => {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    currentPage.value = 1; // Reset to first page on new search/filter
    fetchClients();
  }, 300); // Adjust debounce time as needed (e.g., 300ms)
};

// --- Data Fetching 
const fetchClients = async () => {
  loadingClients.value = true;
  clientsError.value = null; // Still good to keep for internal error handling/logs
  
  try {
    const params = {
      search: searchClientName.value,
      page: currentPage.value,
      per_page: itemsPerPage.value,
      sort_by: sortBy.value, 
      sort_direction: sortDirection.value,
    };
   
    const response = await customersApi.getCustomers(params); 
    clients.value = response.data.clients; 
    totalClients.value = response.data.total_items; 
  } catch (err) {
    clientsError.value = err.response?.data?.message || err.message || __('Failed to fetch clients.', 'schedula-smart-appointment-booking');
    error(clientsError.value); // Use toast for error
    console.error('Error fetching clients:', err);
  } finally {
    loadingClients.value = false;
  }
};

// --- Pagination Methods ---
const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
    fetchClients();
  }
};

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
    fetchClients();
  }
};

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
    fetchClients();
  }
};

const handleItemsPerPageChange = () => {
  currentPage.value = 1; 
  fetchClients();
};

// --- UI Form Control Functions (renamed for clarity) ---
const openAddClientForm = () => {
  clientToEdit.value = null; 
  showClientForm.value = true;
};

const closeClientForm = () => {
  showClientForm.value = false;
  clientToEdit.value = null;
};

// --- Client Handlers 

// Handles saving from the ClientForm (Add/Edit)
const handleClientSaved = async (savedClient) => {
  currentPage.value = 1; 
  await fetchClients(); 
  closeClientForm();
  // ClientForm itself will display its own success toast, so no need for another one here.
};

// Opens the ClientForm for editing
const startEditClient = (client) => {
  clientToEdit.value = { ...client }; 
  showClientForm.value = true;
};

// Initiates the delete confirmation modal for single deletion
const handleDeleteClient = async (client) => {
  clientToDelete.value = { ...client }; 

  try {
    const response = await customersApi.customerHasAppointments(client.id);
    clientToDelete.value.has_appointments = response.data.has_appointments;
  }
  catch (err) {
    console.error('Error checking client appointments:', err);
    clientToDelete.value.has_appointments = false; 
  }

  showDeleteModal.value = true;
};

// Confirms and executes single deletion
const confirmDeleteClient = async () => {
  if (!clientToDelete.value) return;
  isDeleting.value = true;
  try {
    // await new Promise(resolve => setTimeout(resolve, 2000)); // Removed artificial delay
    await customersApi.deleteCustomer(clientToDelete.value.id);
    
    if (clients.value.length === 1 && currentPage.value > 1) {
      currentPage.value--;
    }
    await fetchClients(); 
    success(__('Client and all associated appointments deleted successfully!', 'schedula-smart-appointment-booking')); // Use toast for success
  } catch (err) {
    error(err.response?.data?.message || err.message || __('Error deleting client.', 'schedula-smart-appointment-booking')); // Use toast for error
    console.error('Error deleting client:', err);
  } finally {
    isDeleting.value = false;
    closeDeleteModals(); 
    clientToDelete.value = null;
  }
};

// Handles selection changes from ClientList
const handleSelectionChange = (clientId, isSelected) => {
  if (isSelected) {
    selectedClients.value.push(clientId);
  } else {
    const index = selectedClients.value.indexOf(clientId);
    if (index > -1) {
      selectedClients.value.splice(index, 1);
    }
  }
};

// Opens the modal for bulk deletion
const openBulkDeleteModal = () => {
  if (selectedClients.value.length === 0) {
    error(__('No clients selected for deletion.', 'schedula-smart-appointment-booking')); // Use toast for error
    return;
  }
  bulkClientsToDeleteCount.value = selectedClients.value.length;
  showBulkDeleteModal.value = true;
};

// Confirms and executes bulk deletion
const confirmBulkDeleteAction = async () => {
  if (selectedClients.value.length === 0) return;

  isDeleting.value = true;
  try {
    for (const clientId of selectedClients.value) {
      await customersApi.deleteCustomer(clientId);
    }

    selectedClients.value = []; 
    currentPage.value = 1; 
    await fetchClients(); 
    success(__('Selected clients and their associated appointments deleted successfully!', 'schedula-smart-appointment-booking')); // Use toast for success
  } catch (err) {
    error(err.response?.data?.message || err.message || __('Error deleting selected clients. Some clients might not have been deleted.', 'schedula-smart-appointment-booking')); // Use toast for error
    console.error('Error deleting selected clients:', err);
  } finally {
    isDeleting.value = false;
    closeDeleteModals(); 
  }
};

// Unified function to close all delete-related modals
const closeDeleteModals = () => {
  showDeleteModal.value = false;
  showBulkDeleteModal.value = false;
};
</script>

<style scoped>

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

.column-visibility-button:hover {
  background-color: var(--admin-border-color);
}
</style>
