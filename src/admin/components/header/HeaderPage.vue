<template>
  <div class="relative md:fixed z-[9998] schedula-header left-0 right-0 w-full h-[70px] px-4 flex justify-between items-center shadow"
       :class="{ 'top-[0px]': isDesktop, 'top-[46px]': !isDesktop }"
       :style="{ backgroundColor: 'var(--admin-header-bg-color)' }"
>
      <div class="flex items-center flex-shrink-0 justify-center">
          <!-- <div class="w-7 h-7 flex items-center justify-center mr-2 rounded-full font-bold text-base" :style="{ backgroundColor: 'var(--admin-s-bg-color)', color: 'var(--admin-s-text-color)' }">S</div> -->
          
            <img src="../../../assets/icons/logo.png" class="w-[65px] h-[65px] object-cover"/>
            <h1 class="text-lg font-bold hidden sm:block" :style="{ color: 'var(--admin-header-text-color)' }">Schedula</h1>
          
      </div>
            
      <!-- Navigation Links (visible on all devices) -->
      <nav class="flex items-center space-x-1 sm:space-x-4">
          <a href="https://docs.vertimcoders.com/docs/schedula-documentation/"
             target="_blank" rel="noopener noreferrer"
             class="flex items-center justify-center w-8 h-8 sm:w-auto sm:h-auto sm:px-2 hover:bg-black hover:bg-opacity-10 rounded transition-colors duration-200 focus:outline-none"
             :style="{ color: 'var(--admin-header-text-color)' }"
             :title="__('Documentation', 'schedula-smart-appointment-booking')">
              <i class="fas fa-book text-sm"></i>
              <span class="text-xs ml-1 hidden sm:inline">{{ __('Documentation', 'schedula-smart-appointment-booking') }}</span>
          </a>
          <a href="https://vertimcoders.com/contact-us/"
             target="_blank" rel="noopener noreferrer"
             class="flex items-center justify-center w-8 h-8 sm:w-auto sm:h-auto sm:px-2 hover:bg-black hover:bg-opacity-10 rounded transition-colors duration-200 focus:outline-none"
             :style="{ color: 'var(--admin-header-text-color)' }"
             :title="__('Support', 'schedula-smart-appointment-booking')">
              <i class="fas fa-headset text-sm"></i>
              <span class="text-xs ml-1 hidden sm:inline">{{ __('Support', 'schedula-smart-appointment-booking') }}</span>
          </a>
            <button
             ref="contactButton"
             type="button"
             class="flex items-center justify-center w-8 h-8 sm:w-auto sm:h-auto sm:px-2 hover:bg-black hover:bg-opacity-10 rounded transition-colors duration-200"
             @click="toggleForm('feedback', $event)"
             :style="{ color: 'var(--admin-header-text-color)' }"
             :title="__('Feedback', 'schedula-smart-appointment-booking')">
              <i class="fas fa-comment-dots text-sm"></i>
              <span class="text-xs ml-1 hidden sm:inline">{{ __('Feedback', 'schedula-smart-appointment-booking') }}</span>
            </button>
          <!-- Feature Request Link -->
          <button
             ref="featureRequestButton"
             type="button"
             class="flex items-center justify-center w-8 h-8 sm:w-auto sm:h-auto sm:px-2 hover:bg-black hover:bg-opacity-10 rounded transition-colors duration-200"
             @click="toggleForm('featureRequest', $event)"
             :style="{ color: 'var(--admin-header-text-color)' }"
             :title="__('Request Feature', 'schedula-smart-appointment-booking')">
              <i class="fas fa-lightbulb text-sm"></i>
              <span class="text-xs ml-1 hidden sm:inline">{{ __('Request Feature', 'schedula-smart-appointment-booking') }}</span>
          </button>
          <button
             @click="toggleDarkMode"
             class="flex items-center justify-center w-8 h-8 sm:w-auto sm:h-auto sm:px-2 hover:bg-black hover:bg-opacity-10 rounded transition-colors duration-200"
             :style="{ color: 'var(--admin-header-text-color)' }"
             :title="darkModeTitle">
              <i class="text-sm" :class="darkModeIcon"></i>
              <span class="text-xs ml-1 hidden sm:inline">{{ darkModeText }}</span>
          </button>
          
          <!-- Form Dropdown Container -->
          <div class="relative" ref="formContainer">
              <!-- Form Dropdown -->
              <div v-if="activeForm"
                   class="fixed w-64 rounded-md shadow-lg py-2 z-50"
                   :style="[{ backgroundColor: 'var(--admin-card-bg-color)', backdropFilter: 'none' }, formStyle]"
                   @click.stop>
                  <form @submit.prevent="submitForm" class="px-4 py-2">
                      <h3 class="text-lg font-semibold mb-2" :style="{ color: 'var(--admin-text-color)' }">{{ formTitle }}</h3>
                      <div class="mb-3">
                          <label for="formName" class="block text-sm font-medium" :style="{ color: 'var(--admin-text-color)' }">{{ __('Name', 'schedula-smart-appointment-booking') }}</label>
                          <input type="text" id="formName" v-model="formName" required
                                 class="mt-1 block w-full rounded-md shadow-sm"
                                 :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-text-color)', borderColor: 'var(--admin-border-color)' }">
                      </div>
                      <div class="mb-3">
                          <label for="formEmail" class="block text-sm font-medium" :style="{ color: 'var(--admin-text-color)' }">{{ __('Email', 'schedula-smart-appointment-booking') }}</label>
                          <input type="email" id="formEmail" v-model="formEmail" required
                                 class="mt-1 block w-full rounded-md shadow-sm"
                                 :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-text-color)', borderColor: 'var(--admin-border-color)' }">
                      </div>
                      <div class="mb-3">
                          <label for="formMessage" class="block text-sm font-medium" :style="{ color: 'var(--admin-text-color)' }">{{ formMessageLabel }}</label>
                          <textarea id="formMessage" v-model="formMessage" rows="3" required
                                    class="mt-1 block w-full rounded-md shadow-sm"
                                    :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-text-color)', borderColor: 'var(--admin-border-color)' }"></textarea>
                      </div>
                      <button type="submit"
                              class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium focus:outline-none"
                              :style="{ backgroundColor: 'var(--admin-button-primary-bg)', color: 'var(--admin-button-primary-text)' }"
                              :disabled="isSubmitting">
                          <span v-if="isSubmitting" class="flex items-center">
                              <i class="fas fa-spinner fa-spin mr-2"></i>
                              {{ __('Sending...', 'schedula-smart-appointment-booking') }}
                          </span>
                          <span v-else>
                              {{ __('Send Message', 'schedula-smart-appointment-booking') }}
                          </span>
                      </button>
                  </form>
              </div>
          </div>
      </nav>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { __ } from '@wordpress/i18n';
import { useAdminAppearance } from '@/admin/composables/useAdminAppearance.js';
import { contactApi } from '@/admin/api.js';
import { useToast } from '@/admin/composables/useToast.js';

const { appearanceSettings, saveAppearanceSettings, fetchAppearanceSettings } = useAdminAppearance();
const { success, error } = useToast();

const isDesktop = ref(false);
const activeForm = ref(null); // null, 'feedback', 'featureRequest'
const formName = ref('');
const formEmail = ref('');
const formMessage = ref('');
const isSubmitting = ref(false);
const formStyle = ref({});

const contactButton = ref(null);
const featureRequestButton = ref(null);
const formContainer = ref(null);

const checkScreenSize = () => {
  isDesktop.value = window.innerWidth >= 640; // sm breakpoint
};

const closeFormOnClickOutside = (event) => {
  if (
    activeForm.value &&
    formContainer.value && !formContainer.value.contains(event.target) &&
    (!contactButton.value || !contactButton.value.contains(event.target)) &&
    (!featureRequestButton.value || !featureRequestButton.value.contains(event.target))
  ) {
    activeForm.value = null;
  }
};

onMounted(() => {
  fetchAppearanceSettings();
  checkScreenSize();
  window.addEventListener('resize', checkScreenSize);
  
  document.addEventListener('click', closeFormOnClickOutside);

  // Force reposition based on WordPress admin bar - MOBILE USES TOP 0 WITH REDUCED PADDING
  const updateHeaderPosition = () => {
    const adminBar = document.getElementById('wpadminbar');
    const header = document.querySelector('.schedula-header');
    
    if (header) {
      const isMobile = window.innerWidth < 640;
      const adminBarHeight = adminBar ? adminBar.offsetHeight : 0;
      
      if (isMobile) {
        // On mobile, keep top: 0 but add reduced padding to push content below admin bar
        header.style.top = '0px';
        header.style.paddingTop = adminBarHeight > 0 ? '32px' : '0px'; // Reduced from 46px to 32px
        header.classList.add('mobile-header-positioned');
      } else {
        // Desktop - use admin bar height for top position
        header.style.top = `${adminBarHeight}px`;
        header.style.paddingTop = '0px';
        header.classList.remove('mobile-header-positioned');
      }
      
      header.style.position = 'fixed';
      header.style.zIndex = '9998';
    }
  };
  
  setTimeout(updateHeaderPosition, 100);
  
  // Also listen for resize events to update positioning
  window.addEventListener('resize', () => {
    setTimeout(updateHeaderPosition, 50);
  });
  
  // Use MutationObserver to maintain proper positioning
  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
        const header = mutation.target;
        const isMobile = window.innerWidth < 640;
        
        if (isMobile && header.classList.contains('schedula-header')) {
          // Ensure proper positioning on mobile - top 0 with reduced padding
          setTimeout(() => {
            const adminBar = document.getElementById('wpadminbar');
            const adminBarHeight = adminBar ? adminBar.offsetHeight : 0;
            header.style.top = '0px';
            header.style.paddingTop = adminBarHeight > 0 ? '32px' : '0px'; // Reduced padding
          }, 0);
        }
      }
    });
  });
  
  setTimeout(() => {
    const header = document.querySelector('.schedula-header');
    if (header) {
      observer.observe(header, { attributes: true, attributeFilter: ['style'] });
    }
  }, 200);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', closeFormOnClickOutside);
  window.removeEventListener('resize', checkScreenSize);
});

const toggleDarkMode = () => {
  appearanceSettings.adminDarkModeEnabled = !appearanceSettings.adminDarkModeEnabled;
  saveAppearanceSettings();
};

const toggleForm = (formType, event) => {
  if (activeForm.value === formType) {
    activeForm.value = null;
  } else {
    activeForm.value = formType;
    // Reset fields when opening a new form
    formName.value = '';
    formEmail.value = '';
    formMessage.value = '';

    if (event) {
      const button = event.currentTarget;
      const rect = button.getBoundingClientRect();
      const modalWidth = 256; // w-64 = 16rem = 256px
      const offset = 80; // Push left from right-aligned position

      formStyle.value = {
        top: `${rect.bottom + 5}px`,
        left: `${rect.right - modalWidth - offset}px`,
      };
    }
  }
};

const submitForm = async () => {
  if (isSubmitting.value) return;
  isSubmitting.value = true;

  const subject = activeForm.value === 'feedback'
    ? 'Schedula contact'
    : 'Schedula Feature request';

  try {
    const response = await contactApi.submitContactForm({
      name: formName.value,
      email: formEmail.value,
      message: formMessage.value,
      subject: subject,
    });
    
    if (response.data.success) {
      success(__('Message sent successfully!', 'schedula-smart-appointment-booking'));
      formName.value = '';
      formEmail.value = '';
      formMessage.value = '';
      activeForm.value = null;
    } else {
      error(__('Failed to send message: ', 'schedula-smart-appointment-booking') + response.data.message);
    }
  } catch (err) {
    console.error('Schedula API Error: error submitting form:', err);
    error(__('An error occurred while sending your message.', 'schedula-smart-appointment-booking'));
  } finally {
    isSubmitting.value = false;
  }
};

const formTitle = computed(() => {
    if (activeForm.value === 'feedback') return __('Feedback', 'schedula-smart-appointment-booking');
    if (activeForm.value === 'featureRequest') return __('Request a Feature', 'schedula-smart-appointment-booking');
    return '';
});

const formMessageLabel = computed(() => {
    if (activeForm.value === 'feedback') return __('How can we help you?', 'schedula-smart-appointment-booking');
    if (activeForm.value === 'featureRequest') return __('Describe the feature you\'d like to see.', 'schedula-smart-appointment-booking');
    return '';
});

const darkModeIcon = computed(() => (appearanceSettings.adminDarkModeEnabled ? 'fas fa-sun' : 'fas fa-moon'));
const darkModeText = computed(() => (appearanceSettings.adminDarkModeEnabled ? __('Light Mode', 'schedula-smart-appointment-booking') : __('Dark Mode', 'schedula-smart-appointment-booking')));
const darkModeTitle = computed(() => (appearanceSettings.adminDarkModeEnabled ? __('Switch to Light Mode', 'schedula-smart-appointment-booking') : __('Switch to Dark Mode', 'schedula-smart-appointment-booking')));

</script>

<style scoped>
/* Base header styles */
.schedula-header {
  position: fixed !important;
  z-index: 9998 !important; /* Below WP admin bar (9999) but above content */
  backdrop-filter: blur(8px);
}

/* WordPress admin bar adjustments - UPDATED */
body.admin-bar .schedula-header {
  top: 32px !important; /* WordPress admin bar height on desktop */
}

/* Mobile-specific adjustments - REDUCED PADDING AND HEIGHT */
@media (max-width: 639px) {
  .schedula-header {
    left: 0 !important;
    width: 100% !important;
    position: fixed !important;
    top: 0px !important; /* Always top 0 on mobile */
    height: 48px !important; /* Reduced from 60px to 48px */
    box-sizing: border-box !important;
  }
  
  /* Mobile WordPress admin bar adjustment - reduced padding */
  body.admin-bar .schedula-header {
    top: 0px !important; /* Keep at top 0 */
    height: 80px !important; /* 48px content + 32px admin bar space */
    padding-top: 32px !important; /* Reduced from 46px to 32px */
    box-sizing: border-box !important;
  }
  
  /* Ensure the content area of header stays compact */
  body.admin-bar .schedula-header > * {
    height: 48px !important; /* Match reduced header height */
    display: flex !important;
    align-items: center !important;
  }
  
  /* Additional class for ensuring mobile positioning */
  .schedula-header.mobile-header-positioned {
    position: fixed !important;
    top: 0px !important;
  }
}

/* Desktop styles */
@media (min-width: 640px) {
  .schedula-header {
    position: fixed !important;
  }
  
  body.admin-bar .schedula-header {
    top: 32px !important; /* WordPress desktop admin bar height */
  }
}

/* Content spacing adjustments */
.schedula-main {
  margin-top: 50px; /* Base header height */
  padding: 16px 8px;
}

/* WordPress admin bar body adjustments */
body.admin-bar .schedula-main {
  margin-top: 82px; /* 50px header + 32px desktop admin bar */
}

/* Mobile content spacing - UPDATED FOR REDUCED SIZES */
@media (max-width: 639px) {
  .schedula-main {
    margin-top: 48px; /* Reduced mobile header height */
  }
  
  body.admin-bar .schedula-main {
    margin-top: 80px; /* 48px header + 32px admin bar padding */
  }
}

/* Touch handling for mobile */
@media (max-width: 639px) {
  .schedula-header {
    padding: 0 12px;
  }
  
  .schedula-header nav a {
    min-width: 32px;
    min-height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    touch-action: manipulation;
  }
  
  .schedula-header nav a i {
    font-size: 16px;
  }
}

/* Desktop styles */
@media (min-width: 640px) {
  .schedula-header nav a {
    padding: 8px 12px;
    border-radius: 6px;
  }
}

/* Desktop sidebar adjustments */
@media (min-width: 768px) {
  .schedula-header {
    width: calc(100% - 160px) !important;
    left: 160px;
  }
}

.folded .schedula-header {
  width: calc(100% - 36px) !important;
  left: 36px;
}

/* Mobile override - full width on mobile */
@media (max-width: 767px) {
  .schedula-header {
    width: 100% !important;
    left: 0 !important;
    right: 0 !important;
  }
}

/* Performance optimization */
@media (max-width: 639px) {
  .schedula-header {
    transform: translateZ(0);
    -webkit-transform: translateZ(0);
  }
}
</style>
