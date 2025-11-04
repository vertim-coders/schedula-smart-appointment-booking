<template>
    <div>
      <h2 class="text-3xl font-bold mb-6" :style="{ color: 'var(--admin-text-color)' }">{{ __('Staff Management', 'schedula-smart-appointment-booking') }}</h2>
  
       <!-- Conditional Rendering: Show Staff Form or Staff List -->
      <div v-if="showStaffForm" class="p-6 rounded-lg shadow-xl content-card">
        <!-- Staff Form (Full Page) -->
        <EmployeForm
          :staff="selectedStaff"
          :isSaving="savingStaffForm" 
          @submit="handleStaffFormSubmit"
          @cancel="closeStaffForm"
        />
      </div>
  
      <div v-else>
        <!-- Staff List View -->
  
        <!-- Top Section: Add Staff Button, Search Bar & Pagination Controls -->
        <div class="p-6 rounded-lg shadow-md content-card">
          <!-- TOP ROW: Add Staff Button, Rows per page selector, and Column Visibility -->
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
            <!-- Add Staff Button -->
            <button @click="openAddStaffForm"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out w-full sm:w-auto justify-center" :style="{ backgroundColor: 'var(--admin-link-indigo-bg)', color: 'var(--admin-link-indigo-text)' }">
              <i class="fas fa-user-plus mr-2"></i> {{ __('Add Staff', 'schedula-smart-appointment-booking') }}
            </button>
  
            <div class="flex items-center gap-4">
              <!-- Top Rows per page selector -->
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
  
          <!-- Search Bar -->
          <div class="flex flex-wrap items-center gap-4 w-full sm:w-auto justify-start">
            <div class="relative w-full sm:w-96">
              <BaseInput
                id="search-staff"
                v-model="searchQuery"
                :placeholder="__('Search staff by name, email, or phone', 'schedula-smart-appointment-booking')"
                @input="debouncedFetchStaffMembers"
                icon="fas fa-user-tie"
              />
            </div>
          </div>
        </div>
  
        <!-- Main Staff List Display -->
        <div class="mt-8">
          <div class="flex justify-between items-center h-8 mb-4">
            <h3 class="text-xl font-semibold" :style="{ color: 'var(--admin-text-color)' }">{{ __('All Staff Members', 'schedula-smart-appointment-booking') }}</h3>
            <!-- Bulk Delete Button - Appears when staff members are selected, aligned with "All Staff Members" -->
            <button 
              v-if="selectedStaffIds.length > 0"
              @click="openBulkDeleteModal"
              :disabled="isDeleting"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150 ease-in-out bg-red-600 text-white hover:bg-red-700">
              <svg v-if="isDeleting" class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <i v-else class="fas fa-trash mr-2"></i>
              {{ __('Delete Selected', 'schedula-smart-appointment-booking') }} ({{ selectedStaffIds.length }})
            </button>
          </div>
          
          <EmployeList
            :staffMembers="staffMembers"
            :loading="loading"
            :error="error"
            :message="message"
            :messageType="messageType"
            :columns="columns"
            :sortBy="sortBy"          
            :sortDirection="sortDirection" 
            :selectedStaffIds="selectedStaffIds"
            @edit="handleEditStaff"
            @delete="handleDeleteStaff"
            @update:sort="handleSort"
            @update:selection="handleStaffSelection"
            @update:selectAll="handleSelectAllStaff"
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
  
          <!-- Page info and navigation -->
          <div class="flex items-center gap-4">
            <!-- Page info -->
            <span class="text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Page', 'schedula-smart-appointment-booking') }} {{ currentPage }} of {{ totalPages }}</span>
            
            <!-- Navigation arrows -->
            <div class="flex items-center gap-1">
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
      </div>
      <!-- Unified Delete Confirmation Modal -->
      <transition name="modal-fade">
        <div v-if="showDeleteModal || showBulkDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-opacity-50" @click.self="closeDeleteModals" :style="{ backgroundColor: 'rgba(0, 0, 0, 0.5)' }">
          <div class="rounded-lg shadow-xl p-6 max-w-sm mx-auto modal-content" @click.stop>
            <h3 class="text-lg font-bold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{ __('Confirm Deletion', 'schedula-smart-appointment-booking') }}</h3>
            
            <!-- Content for single client deletion -->
            <div v-if="showDeleteModal">
              <p class="mb-4" :style="{ color: 'var(--admin-card-text-color)' }">
                {{ __('Are you sure you want to delete staff member', 'schedula-smart-appointment-booking') }} "<span class="font-semibold">{{ staffToDelete ? staffToDelete.name : '' }}</span>"?
              </p>
              <p v-if="forceDeleteRequired" class="text-sm mt-2" :style="{ color: 'var(--admin-suggestion-red-text)' }">
                {{ __('**Warning:** This staff member has associated appointments. Deleting this staff member will **permanently delete all their linked appointments** as well.', 'schedula-smart-appointment-booking') }}
              </p>
            </div>
  
            <!-- Content for bulk client deletion -->
            <div v-else-if="showBulkDeleteModal">
              <p class="mb-4" :style="{ color: 'var(--admin-card-text-color)' }">
                {{ __('Are you sure you want to delete', 'schedula-smart-appointment-booking') }} **{{ selectedStaffIds.length }}** {{ __('selected staff members?', 'schedula-smart-appointment-booking') }}
              </p>
              <p class="text-sm mt-2" :style="{ color: 'var(--admin-suggestion-red-text)' }">
                {{ __('**Warning:** This action will **permanently delete all selected staff members and their associated appointments**. This action cannot be undone.', 'schedula-smart-appointment-booking') }}
              </p>
            </div>
  
            <div class="flex justify-end space-x-4 mt-4">
              <button @click="closeDeleteModals" class="px-4 py-2 rounded-md" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)' }">{{ __('Cancel', 'schedula-smart-appointment-booking') }}</button>
              <button 
                @click="showDeleteModal ? confirmDeleteStaff() : confirmBulkDeleteStaffAction()" 
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
      </transition>
  
      <!-- Upgrade to Pro Modal -->
      <div v-if="showUpgradeModal" class="fixed inset-0 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50" :style="{ backgroundColor: 'rgba(0, 0, 0, 0.5)' }">
        <div class="p-8 rounded-lg shadow-xl max-w-sm mx-auto modal-content">
          <h3 class="text-lg font-bold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{ __('Upgrade to Pro for More Staff', 'schedula-smart-appointment-booking') }}</h3>
          <p class="mb-4" :style="{ color: 'var(--admin-card-text-color)' }">
            {{ __('You have reached the limit of 3 staff members for the free version. To add more, please upgrade to the Pro version of Schedula.', 'schedula-smart-appointment-booking') }}
          </p>
          <div class="flex justify-end space-x-4 mt-6">
            <button @click="showUpgradeModal = false" class="px-4 py-2 rounded-md" :style="{ backgroundColor: 'var(--admin-button-third-bg)', color: 'var(--admin-button-secondary-text)' }">{{ __('Cancel', 'schedula-smart-appointment-booking') }}</button>
            <a href="https://vertimcoders.com/tests-product-landing/apps/schedula-for-woocommerce/" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
              {{ __('Upgrade to Pro', 'schedula-smart-appointment-booking') }}
            </a>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { __ } from '@wordpress/i18n';
  import { ref, onMounted, computed, reactive, onBeforeUnmount } from 'vue';
  import EmployeList from '../components/staff/EmployeList.vue';
  import EmployeForm from '../components/staff/EmployeForm.vue';
  import BaseInput from '../components/common/BaseInput.vue';
  import { staffApi } from '../api.js';
  import { useToast } from '../composables/useToast.js'; // Import useToast
  
  // State for UI visibility
  const showStaffForm = ref(false);
  const showUpgradeModal = ref(false);
  const showDeleteModal = ref(false); // For single staff deletion
  const showBulkDeleteModal = ref(false); // For bulk staff deletion
  const isDeleting = ref(false);
  const savingStaffForm = ref(false); //  State for the EmployeForm's saving process
  
  // State for Data
  const staffMembers = ref([]);
  const loading = ref(true);
  const error = ref(null);
  
  // State for Editing/Deletion
  const selectedStaff = ref(null);
  const staffToDelete = ref(null); // For single staff deletion
  const deleteMessage = ref('');
  const forceDeleteRequired = ref(false); // For single delete, if staff has appointments
  
  const searchQuery = ref('');
  let debounceTimeout = null;
  
  // Column visibility state
  const showColumnMenu = ref(false);
  const columnMenuContainer = ref(null);
  const columns = reactive({
    id: false,
    image: false,
    name: true,
    email: true,
    phone: true,
  });
  
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
  
  // --- Pagination State ---
  const currentPage = ref(1);
  const itemsPerPageOptions = [5, 10, 20, 50];
  const itemsPerPage = ref(itemsPerPageOptions[1]);
  const totalStaff = ref(0);
  
  // NEW: Sorting State
  const sortBy = ref('name'); // Default sort column for staff
  const sortDirection = ref('asc'); // Default sort direction
  
  // Selection State
  const selectedStaffIds = ref([]); // Stores IDs of selected staff members
  
  // Computed property for total pages
  const totalPages = computed(() => {
    return Math.ceil(totalStaff.value / itemsPerPage.value);
  });
  
  const isStaffLimitReached = computed(() => totalStaff.value >= 3);
  
  // Store original data for comparison during updates
  const originalStaffServices = ref([]);
  const originalStaffSchedule = ref([]);
  const originalStaffHolidays = ref([]); 
  
  // --- Use the toast composable ---
  const { success, error: toastError } = useToast(); // Renamed error to toastError to avoid conflict
  
  // --- Pagination Methods ---
  const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
      currentPage.value = page;
      fetchStaffMembers();
    }
  };
  
  const nextPage = () => {
    if (currentPage.value < totalPages.value) {
      currentPage.value++;
      fetchStaffMembers();
    }
  };
  
  const prevPage = () => {
    if (currentPage.value > 1) {
      currentPage.value--;
      fetchStaffMembers();
    }
  };
  
  const handleItemsPerPageChange = () => {
    currentPage.value = 1;
    fetchStaffMembers();
  };
  
  // Debounced fetch for search input
  const debouncedFetchStaffMembers = () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
      currentPage.value = 1;
      fetchStaffMembers();
    }, 300);
  };
  
  const fetchStaffMembers = async () => {
    loading.value = true;
    error.value = null; // Still keep for internal error handling/logs
    selectedStaffIds.value = []; // Clear selection on new fetch to avoid inconsistencies
    try {
      const params = {
        search: searchQuery.value,
        page: currentPage.value,
        per_page: itemsPerPage.value,
        status: 'all',
        sort_by: sortBy.value, 
        sort_direction: sortDirection.value, 
      };
      
      const response = await staffApi.getStaffMembers(params);
      
      if (response.data && response.data.staff) {
        staffMembers.value = response.data.staff;
        totalStaff.value = response.data.total_items || 0;
      } else if (Array.isArray(response.data)) {
        totalStaff.value = response.data.length; // Fallback for non-paginated API
        const page = Number(currentPage.value) || 1;
        const perPage = Number(itemsPerPage.value) || 10;
        const start = (page - 1) * perPage;
        const end = start + perPage;
        staffMembers.value = response.data.slice(start, end);
      } else {
        staffMembers.value = [];
        totalStaff.value = 0;
      }
    } catch (err) {
      error.value = err.response?.data?.message || err.message || __('Error fetching staff members.', 'schedula-smart-appointment-booking');
      toastError(error.value); // Use toast for error
      console.error('Fetch staff error:', err);
      staffMembers.value = [];
      totalStaff.value = 0;
    } finally {
      loading.value = false;
    }
  };
  
  
  const handleSort = ({ sortBy: column, sortDirection: direction }) => {
    sortBy.value = column;
    sortDirection.value = direction;
    currentPage.value = 1; // Reset to first page when sorting changes
    fetchStaffMembers();
  };
  
  // --- Selection Handlers ---
  const handleStaffSelection = (staffId, isChecked) => {
    if (isChecked) {
      if (!selectedStaffIds.value.includes(staffId)) {
        selectedStaffIds.value.push(staffId);
      }
    } else {
      const index = selectedStaffIds.value.indexOf(staffId);
      if (index > -1) {
        selectedStaffIds.value.splice(index, 1);
      }
    }
  };
  
  const handleSelectAllStaff = (isChecked) => {
    if (isChecked) {
      selectedStaffIds.value = staffMembers.value.map(staff => staff.id);
    } else {
      selectedStaffIds.value = [];
    }
  };
  
  const handleEditStaff = async (staff) => {
    // 1. Show form immediately with basic data from the list
    selectedStaff.value = staff;
    showStaffForm.value = true;

    // 2. Fetch detailed data in the background
    try {
      const [
        fullStaffDetailsRes,
        servicesRes,
        scheduleRes,
        holidaysRes
      ] = await Promise.all([
        staffApi.getStaffMember(staff.id),
        staffApi.getStaffServices(staff.id),
        staffApi.getStaffSchedule(staff.id),
        staffApi.getHolidays({ staff_id: staff.id })
      ]);

      // 3. Merge detailed data into the existing object for reactivity
      selectedStaff.value = {
        ...fullStaffDetailsRes.data, // Use full details as the base
        services: servicesRes.data,
        schedule: scheduleRes.data,
        holidays: holidaysRes.data
      };

      // 4. Store original data for comparison on submit
      originalStaffServices.value = servicesRes.data;
      originalStaffSchedule.value = scheduleRes.data;
      originalStaffHolidays.value = holidaysRes.data;

    } catch (err) {
      console.error("Error fetching detailed staff data for edit:", err);
      toastError(__("Could not load all staff details. Some information may be missing.", 'schedula-smart-appointment-booking'));
    }
  };
  
  const handleStaffFormSubmit = async (formData) => {
    console.log('StaffPage: handleStaffFormSubmit triggered with formData:', formData);
    savingStaffForm.value = true; // Set saving state to true
    
    try {
      const cleanGeneralData = {
        name: formData.general.name?.toString().trim(),
        email: formData.general.email?.toString().trim().toLowerCase(),
        phone: formData.general.phone?.toString().trim() || '',
        description: formData.general.description?.toString().trim() || '',
        status: formData.general.status || 'active',
        image_url: formData.general.image_url || ''
      };
  
      if (!cleanGeneralData.name) {
        throw new Error(__('Staff name is required', 'schedula-smart-appointment-booking'));
      }
      
      if (!cleanGeneralData.email) {
        throw new Error(__('Staff email is required', 'schedula-smart-appointment-booking'));
      }
  
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(cleanGeneralData.email)) {
        throw new Error(__('Please enter a valid email address', 'schedula-smart-appointment-booking'));
      }
  
      Object.keys(cleanGeneralData).forEach(key => {
        if (cleanGeneralData[key] === undefined || cleanGeneralData[key] === null) {
          delete cleanGeneralData[key];
        }
      });
  
      if (formData.general.id) {
        cleanGeneralData.id = formData.general.id;
      }
  
      let staffResponse;
      const isUpdating = !!cleanGeneralData.id;
      const staffId = cleanGeneralData.id;
  
      if (isUpdating) {
        console.log('StaffPage: Updating existing staff member with ID:', staffId);
        const { id, ...updateData } = cleanGeneralData;
        staffResponse = await staffApi.updateStaffMember(staffId, updateData);
        success(__('Staff member updated successfully!', 'schedula-smart-appointment-booking')); // Use toast for success
      } else {
        if (isStaffLimitReached.value) {
          toastError(__('You have reached the staff limit for the free version. Please upgrade to add more staff members.', 'schedula-smart-appointment-booking'));
          savingStaffForm.value = false;
          return;
        }
        console.log('StaffPage: Creating new staff member.');
        staffResponse = await staffApi.createStaffMember(cleanGeneralData);
        success(__('Staff member created successfully!', 'schedula-smart-appointment-booking')); // Use toast for success
        cleanGeneralData.id = staffResponse.data.id;
        // Reset original data after creating new staff for subsequent edits
        originalStaffServices.value = [];
        originalStaffSchedule.value = [];
        originalStaffHolidays.value = [];
      }
  
      const currentStaffId = cleanGeneralData.id;
  
      // --- Handle Services ---
      console.log('StaffPage: Handling services updates...');
      const servicesToSave = formData.services || [];
      const existingServices = originalStaffServices.value || [];
  
      const servicesToAdd = servicesToSave.filter(
        (s) => !existingServices.some((es) => es.service_id === s.service_id)
      );
      for (const service of servicesToAdd) {
        await staffApi.addStaffService(currentStaffId, {
          service_id: service.service_id,
          price: service.price,
          duration: service.duration === '' || service.duration === null || service.duration === undefined ? null : Number(service.duration),
        });
      }
  
      const servicesToUpdate = servicesToSave.filter((s) =>
        existingServices.some(
          (es) =>
            es.service_id === s.service_id &&
            (es.default_price !== s.price || es.default_duration !== s.duration)
        )
      );
      for (const service of servicesToUpdate) {
        await staffApi.updateStaffService(
          currentStaffId,
          service.service_id,
          {
            price: service.price,
            duration: service.duration === '' || service.duration === null || service.duration === undefined ? null : Number(service.duration),
          }
        );
      }
  
      const servicesToDelete = existingServices.filter(
        (es) => !servicesToSave.some((s) => s.service_id === es.service_id)
      );
      for (const service of servicesToDelete) {
        await staffApi.deleteStaffService(currentStaffId, service.service_id);
      }
  
      // --- Handle Schedule ---
      console.log('StaffPage: Handling schedule updates...');
      const scheduleToSave = formData.schedule || [];
      const existingSchedule = originalStaffSchedule.value || [];
  
      const scheduleToAdd = scheduleToSave.filter(
        (s) => !existingSchedule.some((es) => es.day_of_week === s.day_of_week)
      );
      for (const item of scheduleToAdd) {
        const response = await staffApi.addStaffSchedule(currentStaffId, {
          day_of_week: item.day_of_week,
          start_time: item.start_time,
          end_time: item.end_time,
        });
        if (response.data && response.data.id && item.breaks && item.breaks.length > 0) {
          const newScheduleItemId = response.data.id;
          for (const breakItem of item.breaks) {
            await staffApi.addScheduleItemBreak(newScheduleItemId, breakItem);
          }
        }
      }
  
      const scheduleToUpdate = scheduleToSave.filter((s) => {
        const existing = existingSchedule.find((es) => es.day_of_week === s.day_of_week);
        return (
          existing &&
          (existing.start_time !== s.start_time || existing.end_time !== s.end_time || JSON.stringify(existing.breaks) !== JSON.stringify(s.breaks))
        );
      });
      for (const item of scheduleToUpdate) {
        await staffApi.updateStaffSchedule(currentStaffId, item.id, {
          day_of_week: item.day_of_week,
          start_time: item.start_time,
          end_time: item.end_time,
        });
        if (item.id) {
          const existingBreaks = await staffApi.getScheduleItemBreaks(item.id).then(res => res.data).catch(() => []);
  
          const breaksToAdd = item.breaks.filter(
            (b) => !existingBreaks.some((eb) => eb.start_time === b.start_time && eb.end_time === b.end_time)
          );
          for (const breakItem of breaksToAdd) {
            await staffApi.addScheduleItemBreak(item.id, breakItem);
          }
  
          const breaksToUpdate = item.breaks.filter((b) => {
            const existing = existingBreaks.find((eb) => eb.id === b.id);
            return existing && (existing.start_time !== b.start_time || existing.end_time !== b.end_time || existing.description !== b.description);
          });
          for (const breakItem of breaksToUpdate) {
            await staffApi.updateScheduleItemBreak(item.id, breakItem.id, breakItem);
          }
  
          const breaksToDelete = existingBreaks.filter(
            (eb) => !item.breaks.some((b) => b.id === eb.id)
          );
          for (const breakItem of breaksToDelete) {
            await staffApi.deleteScheduleItemBreak(item.id, breakItem.id);
          }
        }
      }
  
      const scheduleToDelete = existingSchedule.filter(
        (es) => !scheduleToSave.some((s) => s.day_of_week === es.day_of_week)
      );
      for (const item of scheduleToDelete) {
        await staffApi.deleteStaffSchedule(currentStaffId, item.id);
      }
  
      // --- Handle Holidays ---
      console.log('StaffPage: Handling holidays updates...');
      const holidaysToSave = formData.holidays || [];
      const existingHolidays = originalStaffHolidays.value || [];
  
      const holidaysToAdd = holidaysToSave.filter(
        (h) => !h.id
      );
      for (const holiday of holidaysToAdd) {
        await staffApi.createHoliday({
          staff_id: currentStaffId,
          start_date: holiday.start_date,
          end_date: holiday.end_date,
          description: holiday.description,
        });
      }
  
      const holidaysToUpdate = holidaysToSave.filter((h) => {
        const existing = existingHolidays.find((eh) => eh.id === h.id);
        return (
          existing &&
          (existing.start_date !== h.start_date ||
            existing.end_date !== h.end_date ||
            existing.description !== h.description)
        );
      });
      for (const holiday of holidaysToUpdate) {
        await staffApi.updateHoliday(holiday.id, {
          staff_id: currentStaffId,
          start_date: holiday.start_date,
          end_date: holiday.end_date,
          description: holiday.description,
        });
      }
  
      const holidaysToDelete = existingHolidays.filter(
        (eh) => !holidaysToSave.some((h) => h.id === eh.id)
      );
      for (const holiday of holidaysToDelete) {
        await staffApi.deleteHoliday(holiday.id);
      }
  
      showStaffForm.value = false;
      selectedStaff.value = null;
      currentPage.value = 1;
      await fetchStaffMembers();
      
      console.log('StaffPage: Staff form submission completed successfully.');
      
    } catch (err) {
      console.error('StaffPage: Error during staff form submission:', {
        message: err.message,
        response: err.response?.data,
        status: err.response?.status,
        formData: formData.general
      });
      
      let errorMessage = __('Error saving staff member.', 'schedula-smart-appointment-booking');
      
      if (err.response?.data?.message) {
        errorMessage = err.response.data.message;
      } else if (err.response?.data?.error) {
        errorMessage = err.response.data.error;
      } else if (err.message) {
        errorMessage = err.message;
      }
      
      toastError(errorMessage); // Use toast for error
      
    } finally {
      savingStaffForm.value = false; // Set saving state to false in finally block
      console.log('StaffPage: savingStaffForm set to false.');
    }
  };
  
  const closeDeleteModals = () => {
    showDeleteModal.value = false;
    showBulkDeleteModal.value = false;
    staffToDelete.value = null;
    forceDeleteRequired.value = false;
    isDeleting.value = false;
  };
  
  // Handle delete button click (emitted from EmployeListe)
  const handleDeleteStaff = async (staffId) => {
    staffToDelete.value = staffMembers.value.find(s => s.id === staffId);
    if (!staffToDelete.value) {
      toastError(__('Staff member not found for deletion.', 'schedula-smart-appointment-booking')); // Use toast for error
      return;
    }
  
    try {
      const response = await staffApi.deleteStaffMember(staffId, { force: false }); // First call to check for appointments
      deleteMessage.value = response.data.message;
      forceDeleteRequired.value = response.data.code === 'staff_has_appointments';
      showDeleteModal.value = true;
    } catch (error) {
      // If API returns 409, it means staff has appointments
      if (error.response && error.response.status === 409 && error.response.data.code === 'staff_has_appointments') {
        deleteMessage.value = error.response.data.message;
        forceDeleteRequired.value = true;
        showDeleteModal.value = true;
      } else {
        const errorMessage = error.response?.data?.message || __('An unexpected error occurred while preparing deletion.', 'schedula-smart-appointment-booking');
        toastError(errorMessage); // Use toast for error
      }
    }
  };
  
  const confirmDeleteStaff = async () => {
    if (!staffToDelete.value) return;
    isDeleting.value = true;
  
    try {
      await staffApi.deleteStaffMember(staffToDelete.value.id, { force: true });
      success(__('Staff member and all associated data deleted successfully!', 'schedula-smart-appointment-booking')); // Use toast for success
      fetchStaffMembersAndCloseModal();
    } catch (err) {
      handleDeletionError(err);
    }
  };
  
  // Opens the modal for bulk deletion
  const openBulkDeleteModal = () => {
    if (selectedStaffIds.value.length === 0) {
      toastError(__('No staff members selected for deletion.', 'schedula-smart-appointment-booking')); // Use toast for error
      return;
    }
    showBulkDeleteModal.value = true;
    showDeleteModal.value = false; // Ensure single delete modal is closed
    // No need to check for appointments individually for bulk delete, as the warning is generic
  };
  
  // Confirms and executes bulk deletion
  const confirmBulkDeleteStaffAction = async () => {
    if (selectedStaffIds.value.length === 0) return;
  
    isDeleting.value = true;
    try {
      // For now, looping through individual deletions:
      // If your backend supports a single bulk delete endpoint, use that here instead.
      for (const staffId of selectedStaffIds.value) {
        await staffApi.deleteStaffMember(staffId, { force: true }); // Force delete each selected staff
      }
  
      selectedStaffIds.value = []; // Clear selection after successful deletion
      currentPage.value = 1; // Reset to first page
      await fetchStaffMembers(); // Refresh the list
      success(__('Selected staff members and their associated appointments deleted successfully!', 'schedula-smart-appointment-booking')); // Use toast for success
    } catch (err) {
      toastError(err.response?.data?.message || err.message || __('Error deleting selected staff members. Some might not have been deleted.', 'schedula-smart-appointment-booking')); // Use toast for error
      console.error('Error deleting selected staff members:', err);
    } finally {
      isDeleting.value = false;
      closeDeleteModals(); // Close all modals
    }
  };
  
  
  const handleDeletionError = (err) => {
    const errorMessage = err.response?.data?.message || err.message || __('Error deleting staff member.', 'schedula-smart-appointment-booking');
    toastError(errorMessage); // Use toast for error
    closeDeleteModals();
  };
  
  const fetchStaffMembersAndCloseModal = () => {
    if (staffMembers.value.length === 1 && currentPage.value > 1) {
      currentPage.value--;
    }
    fetchStaffMembers();
    closeDeleteModals();
  };
  
  const openAddStaffForm = () => {
    if (isStaffLimitReached.value) {
      showUpgradeModal.value = true;
      return;
    }
    showStaffForm.value = true;
    selectedStaff.value = null;
    originalStaffServices.value = [];
    originalStaffSchedule.value = [];
    originalStaffHolidays.value = [];
  };
  
  const closeStaffForm = () => {
    showStaffForm.value = false;
    selectedStaff.value = null;
  };
  
  onMounted(() => {
    fetchStaffMembers();
    document.addEventListener('mousedown', handleClickOutside);
  });
  
  onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleClickOutside);
  });
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
  
  .modal-fade-enter-active > div,
  .modal-fade-leave-active > div {
    transition: transform 0.3s ease;
  }
  
  .modal-fade-enter-from > div,
  .modal-fade-leave-to > div {
    transform: scale(0.95);
  }
  
  .modal-content {
    background-color: var(--admin-card-bg-color);
    color: var(--admin-text-color);
    border: 1px solid var(--admin-border-color);
  }
  </style>
