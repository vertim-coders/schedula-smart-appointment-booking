<template>
  <!-- Add schedula-settings class to main container -->
  <div class="min-h-screen pb-10 px-4 md:px-6 lg:px-8 schedula-settings" :class="{'dark-mode': appearanceSettings.adminDarkModeEnabled}" :style="adminCustomStyles">
    <button @click="$emit('back')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 mb-6" :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-card-text-color)', '--tw-ring-color': 'var(--admin-link-indigo-bg)' }">
      <i class="fas fa-arrow-left mr-2"></i> {{__('Back to Appearance Home', 'schedula-smart-appointment-booking')}}
    </button>
    <h1 class="text-4xl font-semibold mb-8 text-left" :style="{ color: 'var(--admin-text-color)' }">{{__('Customize Reservation Form', 'schedula-smart-appointment-booking')}}</h1>

    <!-- Loading State -->
    <ReservationFormSkeleton v-if="loadingSettings" />
    
    <!-- Original error state for initial load - now using toast for message -->
    <div v-else-if="settingsError" class="error-container" :style="{ color: 'var(--admin-suggestion-red-text)' }">
      <div class="error-message">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        {{ settingsError }} <!-- Assuming this is already localized or handled via toastError -->
        <button @click="loadSettings" class="retry-btn" :style="{ backgroundColor: 'var(--admin-button-primary-bg)', color: 'var(--admin-button-primary-text)' }">
          <i class="fas fa-redo mr-1"></i>
          {{__('Retry', 'schedula-smart-appointment-booking')}}
        </button>
      </div>
    </div>

    <!-- Main Content Area -->
    <div v-else class="flex flex-col gap-6">
      <!-- Horizontal Tab Navigation -->
      <nav class="p-3 rounded-lg shadow-md flex flex-wrap justify-start items-center gap-2" :style="{ backgroundColor: 'var(--admin-card-bg-color)' }">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="['px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 flex items-center', { 'shadow-md': activeTab === tab.id }]"
          :style="activeTab === tab.id ? { backgroundColor: 'var(--admin-button-primary-bg)', color: 'var(--admin-button-primary-text)' } : { backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-card-text-color)', '--tw-ring-color': 'var(--admin-link-indigo-bg)' }"
        >
          <i :class="tab.icon" class="mr-2"></i> {{__(tab.name, 'schedula-smart-appointment-booking')}}
        </button>
      </nav>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 items-stretch">
        <!-- Settings Controls -->
        <div class="lg:col-span-1 p-4 rounded-lg shadow-md flex flex-col schedula-form" :style="{ backgroundColor: 'var(--admin-card-bg-color)' }">
          <form @submit.prevent="saveSettings" class="flex flex-col">
            <div class="flex-grow">
              <!-- Embed Settings -->
              <div v-if="activeTab === 'embed'" class="settings-section">
                <h2 class="section-title" :style="{ color: 'var(--admin-text-color)' }"><i class="fas fa-code mr-2"></i> {{__('Embed Your Form', 'schedula-smart-appointment-booking')}}</h2>
                <p class="text-sm mb-4" :style="{ color: 'var(--admin-card-text-color)' }">
                  {{ __('Copy and paste this shortcode into any page or post to display the reservation form.', 'schedula-smart-appointment-booking') }}
                </p>
                <div class="flex items-center gap-2">
                  <input 
                    type="text" 
                    :value="shortcode" 
                    readonly 
                    class="form-input w-full flex-grow" 
                    :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-input-text-color)', borderColor: 'var(--admin-input-border-color)' }"
                  />
                  <button 
                    type="button"
                    @click="copyShortcode" 
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm"
                    :style="{ backgroundColor: 'var(--admin-button-primary-bg)', color: 'var(--admin-button-primary-text)', '--tw-ring-color': 'var(--admin-link-indigo-bg)' }"
                  >
                    <i class="fas fa-copy mr-2"></i> {{ copyButtonText }}
                  </button>
                </div>
              </div>

              <!-- Global Colors & Theme -->
              <div v-if="activeTab === 'global'" class="settings-section">
                <h2 class="section-title" :style="{ color: 'var(--admin-text-color)' }"><i class="fas fa-paint-brush mr-2"></i> {{__('Global Colors & Theme', 'schedula-smart-appointment-booking')}}</h2>
                <div class="space-y-4 mb-6">
                  <div class="flex items-center justify-between">
                    <label class="text-sm font-medium" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Primary Color', 'schedula-smart-appointment-booking')}}</label>
                    <input type="color" v-model="settings.colors.primary" class="w-16 h-8 rounded-md shadow-sm" :style="{ borderColor: 'var(--admin-input-border-color)' }" />
                  </div>
                  <div class="flex items-center justify-between">
                    <label class="text-sm font-medium" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Background Color', 'schedula-smart-appointment-booking')}}</label>
                    <input type="color" v-model="settings.colors.background" class="w-16 h-8 rounded-md shadow-sm" :style="{ borderColor: 'var(--admin-input-border-color)' }" />
                  </div>
                  <div class="flex items-center justify-between">
                    <label class="text-sm font-medium" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Text Color', 'schedula-smart-appointment-booking')}}</label>
                    <input type="color" v-model="settings.colors.textColor" class="w-16 h-8 rounded-md shadow-sm" :style="{ borderColor: 'var(--admin-input-border-color)' }" />
                  </div>
                  <div class="flex items-center justify-between">
                    <label class="text-sm font-medium" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Header Text Color', 'schedula-smart-appointment-booking')}}</label>
                    <input type="color" v-model="settings.colors.headerText" class="w-16 h-8 rounded-md shadow-sm" :style="{ borderColor: 'var(--admin-input-border-color)' }" />
                  </div>
                </div>

                <h3 class="section-subtitle" :style="{ color: 'var(--admin-text-color)' }"><i class="fas fa-brush mr-2"></i> {{__('Theme & Effects', 'schedula-smart-appointment-booking')}}</h3>
                <div class="checkbox-grid mt-4 mb-6">
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.theme.roundedCorners" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Rounded Corners', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.theme.shadows" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Shadows', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.theme.showHeader" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Display Header', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                </div>

                <h3 class="section-subtitle" :style="{ color: 'var(--admin-text-color)' }"><i class="fas fa-text-height mr-2"></i> {{__('Typography & Layout', 'schedula-smart-appointment-booking')}}</h3>
                <div class="grid grid-cols-1 md:grid-cols-1 gap-4 mt-4 mb-6">

                  <div class="mt-4">
                    <label class="block text-sm font-medium mb-2" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Add your own font', 'schedula-smart-appointment-booking')}}</label>
                    <input
                      type="text"
                      v-model="settings.layout.customFontFamily"
                      :placeholder="__('e.g., Arial, sans-serif', 'schedula-smart-appointment-booking')"
                      class="form-input w-full"
                      :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-input-text-color)', borderColor: 'var(--admin-input-border-color)' }"
                    />
                    <p class="text-xs mt-1" :style="{ color: 'var(--admin-card-text-color)' }">{{__('This will override the Google Font selection if filled.', 'schedula-smart-appointment-booking')}}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-2" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Input/Button Size', 'schedula-smart-appointment-booking')}}</label>
                    <select v-model="settings.layout.inputSize" class="form-select w-full" :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-input-text-color)', borderColor: 'var(--admin-input-border-color)' }">
                      <option value="small">{{__('Small', 'schedula-smart-appointment-booking')}}</option>
                      <option value="medium">{{__('Medium', 'schedula-smart-appointment-booking')}}</option>
                      <option value="large">{{__('Large', 'schedula-smart-appointment-booking')}}</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-2" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Border Radius', 'schedula-smart-appointment-booking')}}</label>
                    <select v-model="settings.layout.borderRadius" :disabled="!settings.theme.roundedCorners" class="form-select w-full" :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-input-text-color)', borderColor: 'var(--admin-input-border-color)' }">
                      <option value="small">{{__('Small', 'schedula-smart-appointment-booking')}}</option>
                      <option value="medium">{{__('Medium', 'schedula-smart-appointment-booking')}}</option>
                      <option value="large">{{__('Large', 'schedula-smart-appointment-booking')}}</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-2" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Shadow Strength', 'schedula-smart-appointment-booking')}}</label>
                    <select v-model="settings.layout.shadowStrength" class="form-select w-full" :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-input-text-color)', borderColor: 'var(--admin-input-border-color)' }">
                      <option value="none">{{__('None', 'schedula-smart-appointment-booking')}}</option>
                      <option value="small">{{__('Small', 'schedula-smart-appointment-booking')}}</option>
                      <option value="medium">{{__('Medium', 'schedula-smart-appointment-booking')}}</option>
                      <option value="large">{{__('Large', 'schedula-smart-appointment-booking')}}</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-2" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Form Max Width', 'schedula-smart-appointment-booking')}}</label>
                    <select v-model="settings.layout.formWidth" class="form-select w-full" :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-input-text-color)', borderColor: 'var(--admin-input-border-color)' }">
                      <option value="640px">{{__('Small', 'schedula-smart-appointment-booking')}}</option>
                      <option value="960px">{{__('Large', 'schedula-smart-appointment-booking')}}</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Service Settings -->
              <div v-if="activeTab === 'services'" class="settings-section">
                <h2 class="section-title" :style="{ color: 'var(--admin-text-color)' }"><i class="fas fa-concierge-bell mr-2"></i> {{__('Service Settings', 'schedula-smart-appointment-booking')}}</h2>
                <div class="checkbox-grid mt-4">
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.services.showServiceImages" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show Service Images', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.services.showCategoryDescription" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show Category Description', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.services.showServiceDescription" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show Service Description', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.services.showServicePreview" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show Service Preview Image', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                </div>
              </div>

              <!-- Calendar Settings -->
              <div v-if="activeTab === 'calendar'" class="settings-section">
                <h2 class="section-title" :style="{ color: 'var(--admin-text-color)' }"><i class="fas fa-calendar-alt mr-2"></i> {{__('Calendar & Time Slot Settings', 'schedula-smart-appointment-booking')}}</h2>
                <div class="checkbox-grid mt-4">
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.calendar.showCalendar" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show Calendar', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.calendar.showBlockedTimeslots" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show Blocked Timeslots', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.calendar.showOnlyNearestTimeslot" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show Only Nearest Timeslot', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                </div>
                <div class="mt-4">
                  <label class="block text-sm font-medium mb-2" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Date & Time Layout', 'schedula-smart-appointment-booking')}}</label>
                  <select v-model="settings.calendar.layoutStyle" class="form-select w-full" :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-input-text-color)', borderColor: 'var(--admin-input-border-color)' }">
                    <option value="default">{{__('Default (Calendar Grid)', 'schedula-smart-appointment-booking')}}</option>
                  </select>
                </div>
              </div>

              <!-- Customer Settings -->
              <div v-if="activeTab === 'customer'" class="settings-section">
                <h2 class="section-title" :style="{ color: 'var(--admin-text-color)' }"><i class="fas fa-user-circle mr-2"></i> {{__('Customer Details Settings', 'schedula-smart-appointment-booking')}}</h2>
                <div class="checkbox-grid mt-4">
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.customer.showNotesField" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show Notes Field', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.customer.showFirstNameField" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show First Name Field', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.customer.showLastNameField" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show Last Name Field', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.customer.showEmailField" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show Email Field', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.customer.showPhoneField" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show Phone Field', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                </div>
              </div>

              <!-- Payment Settings -->
              <div v-if="activeTab === 'payment'" class="settings-section">
                <h2 class="section-title" :style="{ color: 'var(--admin-text-color)' }"><i class="fas fa-money-bill-wave mr-2"></i> {{__('Payment Settings', 'schedula-smart-appointment-booking')}}</h2>
                <div class="checkbox-grid mt-4">
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.payment.showPaymentStep" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show Payment Step', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                  <!--
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.payment.allowCashPayment" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Allow Cash Payment', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.payment.allowCardPayment" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Allow Card Payment', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                  -->
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.payment.showPriceBreakdown" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show Price Breakdown', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                  <!--
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.payment.showTaxes" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show Taxes', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                  --> 
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.payment.showPaymentMethodDescription" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show Payment Method Description', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                </div>
              </div>

              <!-- Confirmation Settings -->
              <div v-if="activeTab === 'confirmation'" class="settings-section">
                <h2 class="section-title" :style="{ color: 'var(--admin-text-color)' }"><i class="fas fa-check-circle mr-2"></i> {{__('Confirmation Settings', 'schedula-smart-appointment-booking')}}</h2>
                <div class="checkbox-grid mt-4">
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.confirmation.showSummaryStep" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show Summary Step', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.confirmation.showServiceImage" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show Service Image', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.confirmation.showStaffInfo" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show Staff Info', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                   
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.confirmation.allowEditing" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Allow Editing on Confirmation', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.confirmation.showBookAgainButton" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show "Book Again" Button', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                  <label class="flex items-center">
                    <input type="checkbox" v-model="settings.confirmation.showConfirmationDetails" class="form-checkbox" :style="{ '--tw-ring-color': 'var(--admin-link-indigo-bg)' }" />
                    <span class="ml-2 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Show Confirmation Details (Date, Time, Employee, Price)', 'schedula-smart-appointment-booking')}}</span>
                  </label>
                </div>
              </div>

              <!-- Custom CSS Settings -->
              <div v-if="activeTab === 'custom-css'" class="settings-section">
                <h2 class="section-title" :style="{ color: 'var(--admin-text-color)' }"><i class="fas fa-code mr-2"></i> {{__('Custom CSS', 'schedula-smart-appointment-booking')}}</h2>
                <div class="mt-4">
                  <label class="block text-sm font-medium mb-2" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Enter your custom CSS here:', 'schedula-smart-appointment-booking')}}</label>
                  <textarea
                    v-model="settings.customCss"
                    rows="10"
                    class="form-textarea w-full p-2 border rounded-md"
                    :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-input-text-color)', borderColor: 'var(--admin-input-border-color)' }"
                    :placeholder="__('/* Add your custom CSS here */', 'schedula-smart-appointment-booking')"
                  ></textarea>
                  <p class="text-xs mt-1" :style="{ color: 'var(--admin-card-text-color)' }">{{__('This CSS will be applied directly to the reservation form in the frontend.', 'schedula-smart-appointment-booking')}}</p>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-auto pt-4 border-t flex justify-end items-center gap-2" :style="{ borderColor: 'var(--admin-border-color)' }">
              <button type="button" @click="confirmReset" :disabled="resetting || saving" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md shadow-sm" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)' }">
                <svg v-if="resetting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <i v-else class="fas fa-undo mr-1"></i> {{ resetting ? __('Resetting...', 'schedula-smart-appointment-booking') : __('Reset', 'schedula-smart-appointment-booking') }}
              </button>
              <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md shadow-sm" :style="{ backgroundColor: 'var(--admin-button-primary-bg)', color: 'var(--admin-button-primary-text)' }">
                <svg v-if="saving" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <i v-else class="fas fa-save mr-1"></i>
                {{ saving ? __('Saving...', 'schedula-smart-appointment-booking') : __('Save', 'schedula-smart-appointment-booking') }}
              </button>
            </div>
          </form>
        </div>

        <!-- Live Preview -->
        <div class="lg:col-span-3 p-4 rounded-lg shadow-md flex flex-col schedula-preview" :style="{ backgroundColor: 'var(--admin-card-bg-color)' }">
          <h2 class="preview-title mb-2" :style="{ color: 'var(--admin-text-color)' }"><i class="fas fa-eye mr-2"></i> {{__('Live Preview', 'schedula-smart-appointment-booking')}}</h2>
          <div class="flex-grow rounded-lg border overflow-auto flex items-center justify-center" :style="{ borderColor: 'var(--admin-border-color)', backgroundColor: 'var(--admin-input-bg-color)' }">
            <div class="preview-wrapper w-full h-full">
              <LivePreview 
                :settings="settings" 
                :preview-step="parseInt(previewStep)" 
                @edit-label="updateLabel"
                class="w-full h-full"
                :custom-css="settings.customCss"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Custom Confirmation Modal for Reset -->
    <transition name="modal-fade">
      <div v-if="showResetConfirmModal" class="modal-overlay schedula-modal" :style="{ backgroundColor: 'var(--admin-modal-overlay-bg)' }">
        <div class="modal-dialog" :style="{ backgroundColor: 'var(--admin-card-bg-color)' }">
          <div class="modal-header" :style="{ borderColor: 'var(--admin-border-color)' }">
            <h3 class="modal-title" :style="{ color: 'var(--admin-text-color)' }">{{__('Confirm Reset', 'schedula-smart-appointment-booking')}}</h3>
            <button @click="showResetConfirmModal = false" class="modal-close-button" :style="{ color: 'var(--admin-card-text-color)' }">&times;</button>
          </div>
          <div class="modal-body" :style="{ color: 'var(--admin-card-text-color)' }">
            <p>{{__('Are you sure you want to reset all settings to their default values? This action cannot be undone.', 'schedula-smart-appointment-booking')}}</p>
          </div>
          <div class="modal-footer">
            <button @click="showResetConfirmModal = false" class="modal-cancel-button" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)' }">{{__('Cancel', 'schedula-smart-appointment-booking')}}</button>
            <button @click="resetSettingsConfirmed" class="modal-confirm-button" :style="{ backgroundColor: 'var(--admin-button-third-bg)', color: 'var(--admin-button-secondary-text)' }">{{__('Reset', 'schedula-smart-appointment-booking')}}</button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Label Editor Modal -->
    <LabelEditorModal
      :show="showLabelEditorModal"
      :initialText="currentEditingLabelValue"
      @save="handleLabelSave"
      @close="handleLabelClose"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import ReservationFormSkeleton from './ReservationFormSkeleton.vue';
import { appearanceApi } from '@/admin/api.js';
import LivePreview from './LivePreview.vue';
import { __ } from '@wordpress/i18n';
import LabelEditorModal from './LabelEditorModal.vue';
import { useAppearanceSettings } from '@/admin/composables/useAppearanceSettings.js';
import { useAdminAppearance } from '../../composables/useAdminAppearance';
import { useToast } from '@/admin/composables/useToast.js'; // Import useToast

const { appearanceSettings, adminCustomStyles } = useAdminAppearance();

defineEmits(['back', 'edit-label']);

const { settings } = useAppearanceSettings();

const loadingSettings = ref(true);
const settingsError = ref(null);
const saving = ref(false);
const resetting = ref(false);
// const showSuccessMessage = ref(false); // REMOVED
// const errorMessage = ref(null); // REMOVED
const showResetConfirmModal = ref(false);
const activeTab = ref('global');
const previewStep = ref('1');

const fontSearchQuery = ref('');

const showLabelEditorModal = ref(false);
const currentEditingLabelKey = ref('');
const currentEditingLabelValue = ref('');

// Store initial settings to compare for changes
const initialSettings = ref({}); // This will hold a deep copy of the settings when loaded

const tabs = [
  { id: 'global', name: __('Global Settings', 'schedula-smart-appointment-booking'), icon: 'fas fa-paint-roller' },
  { id: 'services', name: __('Service', 'schedula-smart-appointment-booking'), icon: 'fas fa-concierge-bell' },
  { id: 'calendar', name: __('Calendar', 'schedula-smart-appointment-booking'), icon: 'fas fa-calendar-alt' },
  { id: 'customer', name: __('Customer', 'schedula-smart-appointment-booking'), icon: 'fas fa-user-circle' },
  { id: 'payment', name: __('Payment', 'schedula-smart-appointment-booking'), icon: 'fas fa-money-bill-wave' },
  { id: 'confirmation', name: __('Confirmation', 'schedula-smart-appointment-booking'), icon: 'fas fa-check-circle' },
  { id: 'custom-css', name: __('Custom CSS', 'schedula-smart-appointment-booking'), icon: 'fas fa-code' },
  { id: 'embed', name: __('Embed', 'schedula-smart-appointment-booking'), icon: 'fas fa-code' },
];

// --- Use the toast composable ---
const { success, error: toastError, info } = useToast();

const shortcode = '[schesab_reservation_form]';
const copyButtonText = ref(__('Copy', 'schedula-smart-appointment-booking'));

const copyShortcode = () => {
  navigator.clipboard.writeText(shortcode).then(() => {
    success(__('Shortcode copied to clipboard!', 'schedula-smart-appointment-booking'));
    copyButtonText.value = __('Copied!', 'schedula-smart-appointment-booking');
    setTimeout(() => {
      copyButtonText.value = __('Copy', 'schedula-smart-appointment-booking');
    }, 2000);
  }).catch(err => {
    console.error('Failed to copy shortcode: ', err);
    toastError(__('Failed to copy shortcode.', 'schedula-smart-appointment-booking'));
  });
};







// Watch for changes in customFontFamily to override fontFamily
watch(() => settings.layout.customFontFamily, (newCustomFont) => {
  settings.layout.fontFamily = newCustomFont || 'sans-serif';
});

// --- End Google Fonts API Integration ---


const loadSettings = async () => {
  loadingSettings.value = true;
  settingsError.value = null;
  // errorMessage.value = null; // REMOVED
  try {
    const response = await appearanceApi.getSettings();
    if (response.data && response.data.data) {
      const loadedSettings = response.data.data;
      // Ensure layout object exists
      if (!loadedSettings.layout) {
        loadedSettings.layout = {};
      }
      // Initialize customFontFamily if it doesn't exist
      if (typeof loadedSettings.layout.customFontFamily === 'undefined') {
        loadedSettings.layout.customFontFamily = '';
      }

      // Ensure that theme.animations exists, default to true if not present in saved settings
      if (loadedSettings.theme && typeof loadedSettings.theme.animations === 'undefined') {
          loadedSettings.theme.animations = true; 
      }

      // Ensure `showHeader` exists, default to true
      if (loadedSettings.theme && typeof loadedSettings.theme.showHeader === 'undefined') {
          loadedSettings.theme.showHeader = true;
      }
      Object.assign(settings, mergeDeep(settings, loadedSettings));
      // Store a deep copy of the loaded settings
      initialSettings.value = JSON.parse(JSON.stringify(settings));

      // After loading, if customFontFamily has a value, ensure it's applied to fontFamily
      if (settings.layout.customFontFamily) {
        settings.layout.fontFamily = settings.layout.customFontFamily;
      } else if (settings.layout.fontFamily) {
        // If no custom font, ensure fontSearchQuery reflects the loaded Google font
        fontSearchQuery.value = settings.layout.fontFamily.split(',')[0].trim();
      }
    }
  } catch (err) {
    settingsError.value = __('Failed to load appearance settings.', 'schedula-smart-appointment-booking');
    console.error('Error loading appearance settings:', err);
    toastError(settingsError.value); // Use toast for loading error
  } finally {
    loadingSettings.value = false;
  }
};

const saveSettings = async () => {
  saving.value = true;
  // showSuccessMessage.value = false; // REMOVED
  // errorMessage.value = null; // REMOVED

  // Deep compare current settings with initial settings
  if (JSON.stringify(settings) === JSON.stringify(initialSettings.value)) {
    // No changes, show info message and return
    info(__('No changes detected. Settings are already up to date.', 'schedula-smart-appointment-booking')); // Use info toast
    saving.value = false;
    return;
  }

  try {
    // Before saving, ensure `settings.theme.animations` is explicitly included.
    // If it was removed from the UI, its value might be undefined.
    // We can explicitly set it to true if it doesn't exist.
    if (typeof settings.theme.animations === 'undefined') {
      settings.theme.animations = true; 
    }
    await appearanceApi.saveSettings(JSON.parse(JSON.stringify(settings)));
    success(__('Settings saved successfully!', 'schedula-smart-appointment-booking')); // Use success toast
    // Update initialSettings after successful save
    initialSettings.value = JSON.parse(JSON.stringify(settings));
  } catch (err) {
    const errorMsg = 'Failed to save settings: ' + (err.response?.data?.message || err.message || __('Unknown error', 'schedula-smart-appointment-booking'));
    // errorMessage.value = errorMsg; // REMOVED
    console.error('Error saving settings:', err);
    toastError(errorMsg); // Use error toast
  } finally {
    saving.value = false;
  }
};

const confirmReset = () => {
  showResetConfirmModal.value = true;
};

const resetSettingsConfirmed = async () => {
  showResetConfirmModal.value = false;
  resetting.value = true;
  try {
    await appearanceApi.resetSettings();
    await loadSettings(); // Reload settings to get defaults
    success(__('Settings reset to default values!', 'schedula-smart-appointment-booking')); // Use success toast
  } catch (err) {
    const errorMsg = 'Failed to reset settings: ' + (err.response?.data?.message || err.message || __('Unknown error', 'schedula-smart-appointment-booking'));
    // errorMessage.value = null; // REMOVED
    console.error('Error resetting settings:', err);
    toastError(errorMsg); // Use error toast
  } finally {
    resetting.value = false;
  }
};

const updateLabel = ({ key, value }) => {
  currentEditingLabelKey.value = key;
  currentEditingLabelValue.value = value;
  showLabelEditorModal.value = true;
};

const handleLabelSave = async (newValue) => {
  if (currentEditingLabelKey.value && settings.labels[currentEditingLabelKey.value] !== undefined) {
    settings.labels[currentEditingLabelKey.value] = newValue;
    await saveSettings(); // Save changes to backend - now uses toasts internally
  }
  showLabelEditorModal.value = false;
  currentEditingLabelKey.value = '';
  currentEditingLabelValue.value = '';
};

const handleLabelClose = () => {
  showLabelEditorModal.value = false;
  currentEditingLabelKey.value = '';
  currentEditingLabelValue.value = '';
};

// Merges deep objects, used for loading settings from API
function mergeDeep(target, source) {
  const output = { ...target };
  if (target && typeof target === 'object' && source && typeof source === 'object') {
    Object.keys(source).forEach(key => {
      if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
        if (!(key in target))
          Object.assign(output, { [key]: source[key] });
        else
          output[key] = mergeDeep(target[key], source[key]);
      } else {
        Object.assign(output, { [key]: source[key] });
      }
    });
  }
  return output;
}

onMounted(async () => {
  await loadSettings();
  // Fetch initial popular fonts when component mounts
   
});

watch(activeTab, (newTab) => {
  switch (newTab) {
    case 'global':
      previewStep.value = '1';
      break;
    case 'services':
      previewStep.value = '1';
      break;
    case 'calendar':
      previewStep.value = '2';
      break;
    case 'customer':
      previewStep.value = '3';
      break;
    case 'payment':
      previewStep.value = '4';
      break;
    case 'confirmation':
      previewStep.value = '5';
      break;
    default:
      previewStep.value = '1';
      break;
  }
});


</script>

<style scoped>
/* Form Controls */
.schedula-form .form-select, 
.schedula-form input[type="text"],
.schedula-form input[type="color"] {
  width: 100% !important;
  height: 32px !important;
  line-height: 32px !important;
  padding: 0 8px !important;
  font-size: 13px !important;
  border: 1px solid #e2e8f0 !important;
  border-radius: 4px !important;
  vertical-align: middle !important;
}

.schedula-form input[type="color"] {
  width: 60px !important;
  padding: 2px 4px !important;
}

/* Form Layout */
.form-group {
  margin-bottom: 8px !important;
  display: flex !important;
  flex-direction: column !important;
}

.form-group > label {
  margin-bottom: 4px !important;
  font-size: 11px !important;
  line-height: 1.2 !important;
}

/* Section Layout */
.settings-section {
  margin-bottom: 16px !important;
  padding-bottom: 16px !important;
}

.section-title {
  font-size: 16px !important;
  font-weight: 600 !important;
  color: #1f2937 !important;
  margin-bottom: 12px !important;
}

/* Preview Container */
.schedula-preview {
  display: flex !important;
  flex-direction: column !important;
}

.preview-title {
  font-size: 14px !important;
  font-weight: 600 !important;
  color: #1f2937 !important;
}

/* Remove preview step selector */
.preview-step-selector {
  display: none !important;
}

/* Preview Container Styles */
.schedula-preview .preview-wrapper {
  width: 100% !important;
  height: 100% !important;
  margin: 0 auto !important;
  padding: 0 !important;
}

.schedula-preview .flex-grow {
  background: #f8fafc !important;
  overflow-y: auto !important;
}

/* Adjust preview iframe/content */
.live-preview-iframe {
  width: 100% !important;
  height: 100% !important;
  transform-origin: top center !important;
}

/* Custom Success Notification - REMOVED */
/*
.success-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  background-color: #4CAF50;
  color: white;
  padding: 10px 20px;
  border-radius: 5px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
  display: flex;
  align-items: center;
  z-index: 1000;
}
*/

/* Fade Transition */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter, .fade-leave-to /* .fade-leave-active in <2.1.8 */ {
  opacity: 0;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 999;
}

.modal-dialog {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  width: 90%;
  max-width: 500px;
  z-index: 1000;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid #eee;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #333;
}

.modal-close-button {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #666;
}

.modal-body {
  margin-bottom: 20px;
  color: #555;
  font-size: 0.95rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.modal-cancel-button, .modal-confirm-button {
  padding: 8px 15px;
  border-radius: 5px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.modal-cancel-button {
  background-color: #e0e0e0;
  color: #333;
}

.modal-cancel-button:hover {
  background-color: #d0d0d0;
}

.modal-confirm-button {
  background-color: #ef4444; /* Red for destructive action */
  color: white;
}

.modal-confirm-button:hover {
  background-color: #dc2626;
}

/* Modal Transitions */
.modal-fade-enter-active, .modal-fade-leave-active {
  transition: opacity 0.3s ease;
}
.modal-fade-enter-from, .modal-fade-leave-to {
  opacity: 0;
}

.modal-fade-enter-active .modal-dialog,
.modal-fade-leave-active .modal-dialog {
  transition: transform 0.3s ease-out;
}
.modal-fade-enter-from .modal-dialog,
.modal-fade-leave-to .modal-dialog {
  transform: translateY(-20px);
}
</style>
