<template>
  <div class="p-6 rounded-lg shadow-md content-card">
    <h3 class="text-xl font-semibold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{ isEditing ? __('Edit Service', 'schedula-smart-appointment-booking') : __('Add New Service', 'schedula-smart-appointment-booking') }}</h3>

    <form @submit.prevent="handleSubmit">
      <div class="mb-4">
        <BaseInput
          id="service-title"
          :label="__('Service Title', 'schedula-smart-appointment-booking')"
          icon="fas fa-cut"
          v-model="service.title"
          :required="true"
          :validationMessage="formError && formError.includes(__('Service title cannot be empty', 'schedula-smart-appointment-booking')) ? __('Service title cannot be empty.', 'schedula-smart-appointment-booking') : ''"
        />
      </div>

      <div class="mb-4">
        <BaseSelect
          id="service-category"
          :label="__('Category', 'schedula-smart-appointment-booking')"
          icon="fas fa-folder"
          v-model="service.category_id"
          :required="true"
          :placeholder="__('Select a category', 'schedula-smart-appointment-booking')"
          :options="categories.map(c => ({ value: c.id, text: c.name }))"
          :validationMessage="formError && formError.includes(__('Please select a category', 'schedula-smart-appointment-booking')) ? __('Please select a category.', 'schedula-smart-appointment-booking') : ''"
        />
        <p v-if="categories.length === 0" class="text-sm mt-1" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('No categories available. Please add some categories first.', 'schedula-smart-appointment-booking') }}</p>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-card-text-color)' }">
          <i class="fas fa-clock mr-1" :style="{ color: 'var(--admin-input-text-color)' }"></i>{{ __('Duration', 'schedula-smart-appointment-booking') }}
          <span class="text-red-500">*</span>
        </label>
        <div class="flex space-x-2">
          <BaseInput
            id="service-duration-value"
            type="number"
            v-model.number="durationValue"
            min="0"
            :required="true"
            :validationMessage="formError && formError.includes(__('Duration', 'schedula-smart-appointment-booking')) ? __('Duration', 'schedula-smart-appointment-booking') : ''"
            class="flex-grow"
          />
          <BaseSelect
            id="service-duration-unit"
            v-model="durationUnit"
            :options="[{ value: 'minutes', text: __('Minutes', 'schedula-smart-appointment-booking') }, { value: 'hours', text: __('Hours', 'schedula-smart-appointment-booking') }]"
            :required="true"
            class="w-32"
          />
        </div>
      </div>

      <div class="mb-4">
        <BaseInput
          id="service-price"
          :label="__('Price', 'schedula-smart-appointment-booking')"
          :icon="globalSettings.currencySymbol"
          type="number"
          v-model.number="service.price"
          step="0.01"
          min="0"
          :required="true"
          :validationMessage="formError && formError.includes(__('Price cannot be negative', 'schedula-smart-appointment-booking')) ? __('Price cannot be negative.', 'schedula-smart-appointment-booking') : ''"
        />
      </div>

      <!-- Image Upload Section -->
      <div class="mb-4">
        <label class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-card-text-color)' }">
          <i class="fas fa-image mr-1" :style="{ color: 'var(--admin-input-text-color)' }"></i>{{ __('Service Image', 'schedula-smart-appointment-booking') }}
        </label>
        <div class="flex items-center space-x-3">
          <button
            type="button"
            @click="handleSelectServiceImage"
            class="inline-flex items-center px-4 py-2 rounded-lg shadow-sm text-sm font-medium" :style="{ backgroundColor: 'var(--admin-button-primary-bg)', color: 'var(--admin-button-secondary-text)' }">
          
            <i class="fas fa-image mr-2"></i> {{ __('Select Image', 'schedula-smart-appointment-booking') }}
          </button>
          <button
            v-if="service.image_url"
            type="button"
            @click="removeImage"
            class="inline-flex items-center px-3 py-2 rounded-lg shadow-sm text-sm font-medium" :style="{ backgroundColor: 'var(--admin-suggestion-red-bg)', color: 'var(--admin-suggestion-red-text)' }">
          
            <i class="fas fa-times-circle mr-1"></i> {{ __('Remove', 'schedula-smart-appointment-booking') }}
          </button>
        </div>
        <p class="text-xs mt-1" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Upload or select an image from your WordPress Media Library.', 'schedula-smart-appointment-booking') }}</p>
        
        <div v-if="service.image_url" class="mt-4 rounded-lg overflow-hidden shadow-sm max-w-xs" :style="{ borderColor: 'var(--admin-border-color)' }">
          <img :src="service.image_url" alt="Service Preview" class="w-full h-auto object-cover" onerror="this.onerror=null;this.src=window.schedulaData.pluginUrl + 'assets/images/placeholders/no-image-placeholder.png';" />
        </div>
        <div v-if="mediaUploaderError" class="px-4 py-3 rounded relative mt-2" role="alert" :style="{ backgroundColor: 'var(--admin-suggestion-red-bg)', borderColor: 'var(--admin-border-color)', color: 'var(--admin-suggestion-red-text)' }">
          <span class="block sm:inline">{{ mediaUploaderError }}</span>
        </div>
      </div>

      <div class="mb-4">
        <BaseTextarea
          id="service-description"
          :label="__('Description', 'schedula-smart-appointment-booking')"
          icon="fas fa-align-left"
          v-model="service.description"
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
          :disabled="saving || categories.length === 0"
          class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out" :style="{ backgroundColor: 'var(--admin-link-indigo-bg)', color: 'var(--admin-link-indigo-text)' }">
        
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
import { ref, watch, defineProps, defineEmits, onMounted, onUnmounted } from 'vue';
import { servicesCategoriesApi } from '../../api.js';
import { __ } from '@wordpress/i18n';
import { useGlobalSettings } from '@/admin/composables/useGlobalSettings.js';
import { useToast } from '../../composables/useToast.js'; // Import useToast

// Import your Base components
import BaseInput from '../../components/common/BaseInput.vue'; // Adjust path
import BaseSelect from '../../components/common/BaseSelect.vue'; // Adjust path
import BaseTextarea from '../../components/common/BaseTextarea.vue'; // Adjust path

// Destructure global settings from the composable
const { generalSettings: globalSettings } = useGlobalSettings();
const { success, error: toastError } = useToast(); // Destructure toast functions


const props = defineProps({
  initialService: {
    type: Object,
    default: null,
  },
  categories: { // Pass categories for the dropdown
    type: Array,
    required: true,
    default: () => [],
  },
  totalServices: {
    type: Number,
    default: 0
  }
});

const emit = defineEmits(['service-saved', 'close-form', 'limit-reached']);

// Default service structure for new entries or resetting
const defaultService = { id: null, category_id: '', title: '', description: '', duration: 30, price: 0.00, image_url: '' };
// Reactive state for the form data
const service = ref({ ...defaultService });
// Flag to indicate if the form is in editing mode
const isEditing = ref(false);
// Flag for saving/loading state
const saving = ref(false);
// Removed formError, now using toasts
// const formError = ref(null); 
const mediaUploaderError = ref(null); // Reactive variable for media uploader errors

const durationValue = ref(0); // For numeric duration input
const durationUnit = ref('minutes'); // 'minutes' or 'hours'

// Function to convert durationValue and durationUnit to total minutes
const convertToMinutes = (value, unit) => {
  if (unit === 'hours') {
    return value * 60;
  }
  return value; // Assume minutes
};

// Function to convert total minutes to durationValue and durationUnit for display
const convertMinutesToDisplay = (totalMinutes) => {
  if (totalMinutes === null || totalMinutes === undefined || isNaN(totalMinutes) || totalMinutes < 0) {
    return { value: 0, unit: __('minutes', 'schedula-smart-appointment-booking') };
  }

  if (totalMinutes % 60 === 0 && totalMinutes !== 0) {
    return { value: totalMinutes / 60, unit: __('hours', 'schedula-smart-appointment-booking') };
  }
  return { value: totalMinutes, unit: __('minutes', 'schedula-smart-appointment-booking') };
};

// WordPress media uploader instance
let mediaUploader = null;

// Watch for changes in the 'initialService' prop
watch(() => props.initialService, (newVal) => {
  if (newVal) {
    // When a service is provided for editing, populate the form
    service.value = {
      ...newVal,
      id: Number(newVal.id),
      category_id: Number(newVal.category_id),
      duration: Number(newVal.duration),
      price: Number(newVal.price),
      description: newVal.description || '',
      image_url: newVal.image_url || '' // Populate image_url
    };
    isEditing.value = true;
    const { value, unit } = convertMinutesToDisplay(service.value.duration);
    durationValue.value = value;
    durationUnit.value = unit;
  } else {
    // If no service is provided (or it's cleared), reset the form for adding
    service.value = { ...defaultService };
    isEditing.value = false;
    durationValue.value = defaultService.duration;
    durationUnit.value = __('minutes', 'schedula-smart-appointment-booking');
  }
  // Removed formError.value = null; // Clear any form errors when the prop changes
  mediaUploaderError.value = null; // Clear media uploader errors too
}, { immediate: true }); // Run the watcher immediately on component mount

// Watch durationValue and durationUnit and update service.duration
watch([durationValue, durationUnit], () => {
  service.value.duration = convertToMinutes(durationValue.value, durationUnit.value);
});

// Function to open WordPress Media Uploader
const handleSelectServiceImage = () => { 
  console.log('Select Image');
  // Ensure wp.media is available
  if (typeof wp !== 'undefined' && wp.media) {
    var uploader = wp.media(
        {
            title: __('Select Service Image', 'schedula-smart-appointment-booking'),
            button: {
                text: __('Select Service Image', 'schedula-smart-appointment-booking')
            },
            multiple: false
        }
    )
    .on(
        'select',
        function () {
            var selection = uploader.state().get( 'selection' );
            selection.map(
                function (attachment) {
                    attachment = attachment.toJSON();
                    if(attachment.type == 'image'){
                        service.value.image_url=attachment.url;
                    }
                }
            );
        }
    )
    .open();
  } else {
    mediaUploaderError.value = __('WordPress media uploader is not available.', 'schedula-smart-appointment-booking');
    console.error('WordPress media uploader (wp.media) is not defined.');
    toastError(__('WordPress media uploader is not available.', 'schedula-smart-appointment-booking')); // Toast for media uploader error
  }
}

// Function to remove the selected image
const removeImage = () => {
  service.value.image_url = '';
};


const handleSubmit = async () => {
  // Removed formError.value = null; // Clear form errors before new submission
  // Client-side validation
  if (!service.value.title.trim()) {
    toastError(__('Service title cannot be empty.', 'schedula-smart-appointment-booking'));
    return;
  }
  if (!service.value.category_id) {
    toastError(__('Please select a category.', 'schedula-smart-appointment-booking'));
    return;
  }
  if (durationValue.value <= 0) {
    toastError(__('Duration must be a positive value.', 'schedula-smart-appointment-booking'));
    return;
  }
  if (service.value.price < 0) {
    toastError(__('Price cannot be negative.', 'schedula-smart-appointment-booking'));
    return;
  }

  if (!isEditing.value && props.totalServices >= 5) {
    emit('limit-reached');
    closeForm();
    return;
  }

  saving.value = true;
  try {
    let response;
    if (isEditing.value) {
      response = await servicesCategoriesApi.updateService(service.value.id, service.value);
    } else {
      response = await servicesCategoriesApi.createService(service.value);
    }

    const savedService = response.data;
    emit('service-saved', savedService);
    service.value = { ...defaultService };
    isEditing.value = false;
    closeForm();
  } catch (err) {
    toastError(err.response?.data?.message || err.message || __('Failed to save service.', 'schedula-smart-appointment-booking'));
    console.error('Error saving service:', err);
  } finally {
    saving.value = false;
  }
};

const closeForm = () => {
  service.value = { ...defaultService };
  isEditing.value = false;
  // Removed formError.value = null;
  mediaUploaderError.value = null; // Clear media uploader errors on close
  durationValue.value = defaultService.duration; // Reset duration value
  durationUnit.value = __('minutes', 'schedula-smart-appointment-booking'); // Reset duration unit
  emit('close-form'); // Emit event to notify parent to close the modal
};

// Clean up media uploader when component is unmounted
onUnmounted(() => {
  if (mediaUploader) {
    try {
      mediaUploader.dispose();
    } catch (e) {
      console.warn('Error disposing media uploader on unmount:', e);
    }
    mediaUploader = null;
  }
});

onMounted(() => {
  // Check if wp.media is available on mount if not already done
  if (typeof wp === 'undefined' || !wp.media) {
    console.warn('WordPress media uploader (wp.media) is not defined. Image upload functionality may not work.');
    // You might want to disable the button or show a message if it's critical
  }
});
</script>
