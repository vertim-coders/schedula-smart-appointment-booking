<template>
  <div class="min-h-screen pb-10 px-4 md:px-6 lg:px-8 schedula-settings" :class="{'dark-mode': appearanceSettings.adminDarkModeEnabled}" :style="adminCustomStyles">
    <button @click="cancel" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm mb-6" :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-input-text-color)', '--tw-ring-color': 'var(--admin-link-indigo-bg)' }">
      <i class="fas fa-arrow-left mr-2"></i> {{__('Back to Forms List', 'schedula-smart-appointment-booking')}}
    </button>
    <h1 class="text-4xl font-semibold mb-8 text-left" :style="{ color: 'var(--admin-text-color)' }">{{ formId ? __('Edit Service Form', 'schedula-smart-appointment-booking') : __('Create New Service Form', 'schedula-smart-appointment-booking') }}</h1>

    <!-- Loading and Error States for initial form load -->
    <ServiceFormCustomizationSkeleton v-if="loadingForm" />
    <div v-else-if="formError" class="error-container" :style="{ backgroundColor: 'var(--admin-suggestion-red-bg)', color: 'var(--admin-suggestion-red-text)', borderColor: 'var(--admin-input-border-color)' }">
      <div class="error-message">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        {{ formError }} <!-- Assuming this is already localized or handled via toastError -->
        <button @click="loadForm" class="retry-btn" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)' }">
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
          v-for="tabItem in tabs"
          :key="tabItem.id"
          @click="activeTab = tabItem.id"
          :class="['px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 flex items-center', { 'shadow-md': activeTab === tabItem.id }]"
          :style="activeTab === tabItem.id ? { backgroundColor: 'var(--admin-button-primary-bg)', color: 'var(--admin-button-primary-text)' } : { backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-card-text-color)', '--tw-ring-color': 'var(--admin-link-indigo-bg)' }"
        >
          <i :class="tabItem.icon" class="mr-2"></i> {{__(tabItem.name, 'schedula-smart-appointment-booking')}}
        </button>
      </nav>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 items-stretch">
        <!-- Settings Controls - Make it smaller and scrollable -->
        <div class="lg:col-span-1 p-4 rounded-lg shadow-md flex flex-col schedula-form" :style="{ backgroundColor: 'var(--admin-card-bg-color)' }">
          <form @submit.prevent="saveForm" class="flex flex-col">
            <div class="flex-grow">
              <!-- Tab Content -->
              <div class="tab-content">
                <!-- General Settings Tab -->
                <div v-show="activeTab === 'general'" class="settings-section">
                  <h2 class="section-title" :style="{ color: 'var(--admin-text-color)' }"><i class="fas fa-cog mr-2"></i> {{__('General Settings', 'schedula-smart-appointment-booking')}}</h2>
                  <ServiceFormDetails
                    :form="form"
                    :settings="settings"
                    :initial-services="services"
                    :initial-categories="categories"
                    :initial-staff="staff"
                    @update:form="form = $event"
                    @update:settings="Object.assign(settings, $event)"
                  />
                  <!-- Display Staff Names in Form checkbox -->
                  <div class="mt-4">
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.forms.serviceForm.displayStaffNames" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Display Staff Names in Form', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                  </div>
                </div>

                <!-- Global Settings Tab -->
                <div v-show="activeTab === 'global'" class="settings-section">
                  <h2 class="section-title" :style="{ color: 'var(--admin-text-color)' }"><i class="fas fa-paint-brush mr-2"></i> {{__('Global Settings', 'schedula-smart-appointment-booking')}}</h2>
                  <h3 class="section-subtitle" :style="{ color: 'var(--admin-text-color)', borderColor: 'var(--admin-border-color)' }"><i class="fas fa-brush mr-2"></i> {{__('Theme & Effects', 'schedula-smart-appointment-booking')}}</h3>
                  <div class="checkbox-grid mt-4 mb-6">
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.theme.roundedCorners" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Rounded Corners', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.theme.shadows" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Shadows', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                  </div>

                  <!-- Color inputs simplified to match ReservationFormSettings -->
                  <h3 class="section-subtitle" :style="{ color: 'var(--admin-text-color)', borderColor: 'var(--admin-border-color)' }"><i class="fas fa-palette mr-2"></i> {{__('Color Palette', 'schedula-smart-appointment-booking')}}</h3>
                  <div class="space-y-4 mb-6">
                    <div class="flex items-center justify-between">
                      <label class="text-sm font-medium" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Primary Color', 'schedula-smart-appointment-booking')}}</label>
                      <input type="color" v-model="settings.colors.primary" class="w-16 h-8 rounded-md shadow-sm"
                        :style="{ borderColor: 'var(--admin-input-border-color)', backgroundColor: 'var(--admin-input-bg-color)' }" />
                    </div>
                    <div class="flex items-center justify-between">
                      <label class="text-sm font-medium" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Background Color', 'schedula-smart-appointment-booking')}}</label>
                      <input type="color" v-model="settings.colors.background" class="w-16 h-8 rounded-md shadow-sm"
                        :style="{ borderColor: 'var(--admin-input-border-color)', backgroundColor: 'var(--admin-input-bg-color)' }" />
                    </div>
                    <div class="flex items-center justify-between">
                      <label class="text-sm font-medium" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Text Color', 'schedula-smart-appointment-booking')}}</label>
                      <input type="color" v-model="settings.colors.textColor" class="w-16 h-8 rounded-md shadow-sm"
                        :style="{ borderColor: 'var(--admin-input-border-color)', backgroundColor: 'var(--admin-input-bg-color)' }"  />
                    </div>
                    <div class="flex items-center justify-between">
                      <label class="text-sm font-medium" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Header Text Color', 'schedula-smart-appointment-booking')}}</label>
                      <input type="color" v-model="settings.colors.headerText" class="w-16 h-8 rounded-md shadow-sm"
                        :style="{ borderColor: 'var(--admin-input-border-color)', backgroundColor: 'var(--admin-input-bg-color)' }" />
                    </div>
                  </div>

                  <h3 class="section-subtitle" :style="{ color: 'var(--admin-text-color)', borderColor: 'var(--admin-border-color)' }"><i class="fas fa-text-height mr-2"></i> {{__('Typography & Layout', 'schedula-smart-appointment-booking')}}</h3>
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
                      <select v-model="settings.layout.inputSize" class="form-select w-full"
                        :style="{ backgroundColor: 'var(--admin-input-bg-color)', borderColor: 'var(--admin-input-border-color)', color: 'var(--admin-input-text-color)' }">
                        <option value="small">{{__('Small', 'schedula-smart-appointment-booking')}}</option>
                        <option value="medium">{{__('Medium', 'schedula-smart-appointment-booking')}}</option>
                        <option value="large">{{__('Large', 'schedula-smart-appointment-booking')}}</option>
                      </select>
                    </div>
                    <div>
                      <label class="block text-sm font-medium mb-2" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Shadow Strength', 'schedula-smart-appointment-booking')}}</label>
                      <select v-model="settings.layout.shadowStrength" class="form-select w-full"
                        :style="{ backgroundColor: 'var(--admin-input-bg-color)', borderColor: 'var(--admin-input-border-color)', color: 'var(--admin-input-text-color)' }">
                        <option value="none">{{__('None', 'schedula-smart-appointment-booking')}}</option>
                        <option value="small">{{__('Small', 'schedula-smart-appointment-booking')}}</option>
                        <option value="medium">{{__('Medium', 'schedula-smart-appointment-booking')}}</option>
                        <option value="large">{{__('Large', 'schedula-smart-appointment-booking')}}</option>
                      </select>
                    </div>
                    <div>
                      <label class="block text-sm font-medium mb-2" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Form Width', 'schedula-smart-appointment-booking')}}</label>
                      <select v-model="settings.layout.formWidth" class="form-select w-full"
                        :style="{ backgroundColor: 'var(--admin-input-bg-color)', borderColor: 'var(--admin-input-border-color)', color: 'var(--admin-input-text-color)' }">
                        <option value="960px">{{__('Large', 'schedula-smart-appointment-booking')}}</option>
                        <option value="640px">{{__('Small', 'schedula-smart-appointment-booking')}}</option>
                      </select>
                    </div>
                  </div>
                </div>
                
                <!-- Services Settings Tab -->
                <div v-show="activeTab === 'services'" class="settings-section">
                  <h2 class="section-title" :style="{ color: 'var(--admin-text-color)' }"><i class="fas fa-concierge-bell mr-2"></i> {{__('Service Settings', 'schedula-smart-appointment-booking')}}</h2>
                  <div class="checkbox-grid mt-4">
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.services.showServiceImages" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show Service Images', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.services.showCategoryDescription" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show Category Description', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.services.showServiceDescription" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show Service Description', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.services.showServicePreview" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show Service Preview Image', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                  </div>
                </div>

                <!-- Calendar Settings Tab -->
                <div v-show="activeTab === 'calendar'" class="settings-section">
                  <h2 class="section-title" :style="{ color: 'var(--admin-text-color)' }"><i class="fas fa-calendar-alt mr-2"></i> {{__('Calendar & Time Slot Settings', 'schedula-smart-appointment-booking')}}</h2>
                  <div class="checkbox-grid mt-4">
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.calendar.showCalendar" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show Calendar', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.calendar.showBlockedTimeslots" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show Blocked Timeslots', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.calendar.showOnlyNearestTimeslot" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show Only Nearest Timeslot', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                  </div>
                  <div class="mt-4">
                    <label class="block text-sm font-medium mb-2" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Date & Time Layout', 'schedula-smart-appointment-booking')}}</label>
                    <select v-model="settings.calendar.layoutStyle" class="form-select w-full"
                      :style="{ backgroundColor: 'var(--admin-input-bg-color)', borderColor: 'var(--admin-input-border-color)', color: 'var(--admin-input-text-color)' }">
                      <option value="default">{{__('Default (Calendar Grid)', 'schedula-smart-appointment-booking')}}</option>
                    </select>
                  </div>
                </div>

                <!-- Customer Settings Tab -->
                <div v-show="activeTab === 'customer'" class="settings-section">
                  <h2 class="section-title" :style="{ color: 'var(--admin-text-color)' }"><i class="fas fa-user-circle mr-2"></i> {{__('Customer Details Settings', 'schedula-smart-appointment-booking')}}</h2>
                  <div class="checkbox-grid mt-4">
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.customer.showNotesField" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show Notes Field', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.customer.showFirstNameField" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show First Name Field', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.customer.showLastNameField" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show Last Name Field', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.customer.showEmailField" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show Email Field', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.customer.showPhoneField" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show Phone Field', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                  </div>
                </div>

                <!-- Payment Settings Tab -->
                <div v-show="activeTab === 'payment'" class="settings-section">
                  <h2 class="section-title" :style="{ color: 'var(--admin-text-color)' }"><i class="fas fa-money-bill-wave mr-2"></i> {{__('Payment Settings', 'schedula-smart-appointment-booking')}}</h2>
                  <div class="checkbox-grid mt-4">
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.payment.showPaymentStep" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show Payment Step', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    <!--
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.payment.allowCashPayment" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Allow Cash Payment', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.payment.allowCardPayment" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Allow Card Payment', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    -->
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.payment.showPriceBreakdown" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show Price Breakdown', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    <!--
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.payment.showTaxes" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show Taxes', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    -->
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.payment.showPaymentMethodDescription" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show Payment Method Description', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                  </div>
                </div>

                <!-- Confirmation Settings Tab -->
                <div v-show="activeTab === 'confirmation'" class="settings-section">
                  <h2 class="section-title" :style="{ color: 'var(--admin-text-color)' }"><i class="fas fa-check-circle mr-2"></i> {{__('Confirmation Settings', 'schedula-smart-appointment-booking')}}</h2>
                  <div class="checkbox-grid mt-4">
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.confirmation.showSummaryStep" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show Summary Step', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.confirmation.showServiceImage" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show Service Image', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.confirmation.showStaffInfo" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show Staff Info', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    <!--
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.confirmation.allowEditing" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Allow Editing on Confirmation', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    -->
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.confirmation.showBookAgainButton" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show "Book Again" Button', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                    <label class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                      <input type="checkbox" v-model="settings.confirmation.showConfirmationDetails" class="form-checkbox"
                        :style="{ accentColor: 'var(--admin-link-indigo-bg)' }" />
                      <span class="ml-2 text-sm">{{__('Show Confirmation Details (Date, Time, Employee, Price)', 'schedula-smart-appointment-booking')}}</span>
                    </label>
                  </div>
                </div>

                <!-- Custom CSS Settings -->
                <div v-show="activeTab === 'custom-css'" class="settings-section">
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
                    <p class="text-xs mt-1" :style="{ color: 'var(--admin-card-text-color)' }">{{__('This CSS will be applied directly to the service form in the frontend.', 'schedula-smart-appointment-booking')}}</p>
                  </div>
                </div>
              </div> <!-- END OF tab-content DIV -->
            </div>

            <!-- Action Buttons -->
            <div class="mt-auto pt-4 border-t flex justify-end items-center gap-2"
                 :style="{ borderColor: 'var(--admin-border-color)' }">
                 <button type="button" @click="resetSettingsConfirm" :disabled="!isDirty || resetting || saving" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md shadow-sm" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)' }">
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
          <div class="flex-grow rounded-lg border overflow-auto flex items-center justify-center"
               :style="{ backgroundColor: 'var(--admin-input-bg-color)', borderColor: 'var(--admin-input-border-color)' }">
            <div class="preview-wrapper w-full h-full">
              <ServiceFormClientLivePreview
                :preview-settings="settings"
                :preview-step="parseInt(previewStep)"
                :service-id="form.service_id"
                :category-id="form.category_id"
                :staff-id="form.staff_id"
                :is-preview="true"
                :initial-categories="categories"  
                :initial-services="services"      
                @edit-label="openLabelEditor"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Custom Confirmation Modal for Reset -->
    <transition name="modal-fade">
      <div v-if="showResetConfirmModal" class="modal-overlay" :style="{ backgroundColor: 'var(--admin-modal-overlay-bg)' }">
        <div class="modal-dialog" :style="{ backgroundColor: 'var(--admin-card-bg-color)', color: 'var(--admin-text-color)' }">
          <div class="modal-header" :style="{ borderColor: 'var(--admin-border-color)' }">
            <h3 class="modal-title" :style="{ color: 'var(--admin-text-color)' }">{{__('Confirm Reset', 'schedula-smart-appointment-booking')}}</h3>
            <button @click="showResetConfirmModal = false" class="modal-close-button" :style="{ color: 'var(--admin-text-color)' }">&times;</button>
          </div>
          <div class="modal-body" :style="{ color: 'var(--admin-card-text-color)' }">
            {{__('Are you sure you want to reset all appearance settings for this form to their default values? This action cannot be undone for *this specific form*.', 'schedula-smart-appointment-booking')}}
          </div>
          <div class="modal-footer">
            <button @click="showResetConfirmModal = false" class="modal-cancel-button"
                    :style="{ backgroundColor: 'var(--admin-button-third-bg)', color: 'var(--admin-button-secondary-text)', borderColor: 'var(--admin-input-border-color)' }">{{__('Cancel', 'schedula-smart-appointment-booking')}}</button>
            <button @click="resetSettings" class="modal-confirm-button"
                    :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)', borderColor: 'var(--admin-input-border-color)' }">{{__('Reset', 'schedula-smart-appointment-booking')}}</button>
          </div>
        </div>
      </div>
    </transition>
    <!-- Custom Confirmation Modal for Unsaved Changes on Leave -->
    <transition name="modal-fade">
      <div v-if="showUnsavedChangesModal" class="modal-overlay" :style="{ backgroundColor: 'var(--admin-modal-overlay-bg)' }">
        <div class="modal-dialog" :style="{ backgroundColor: 'var(--admin-card-bg-color)', color: 'var(--admin-text-color)' }">
          <div class="modal-header" :style="{ borderColor: 'var(--admin-border-color)' }">
            <h3 class="modal-title" :style="{ color: 'var(--admin-text-color)' }">{{__('Unsaved Changes', 'schedula-smart-appointment-booking')}}</h3>
            <button @click="cancelUnsavedChangesLeave" class="modal-close-button" :style="{ color: 'var(--admin-text-color)' }">&times;</button>
          </div>
          <div class="modal-body" :style="{ color: 'var(--admin-card-text-color)' }">
            {{__('You have unsaved changes. Are you sure you want to discard them and leave this page?', 'schedula-smart-appointment-booking')}}
          </div>
          <div class="modal-footer">
            <button @click="cancelUnsavedChangesLeave" class="modal-cancel-button"
                    :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)', borderColor: 'var(--admin-input-border-color)' }">{{__('Stay Here', 'schedula-smart-appointment-booking')}}</button>
            <button @click="confirmDiscardAndLeave" class="modal-confirm-button"
                    :style="{ backgroundColor: 'var(--admin-suggestion-red-bg)', color: 'var(--admin-suggestion-red-text)', borderColor: 'var(--admin-input-border-color)' }">{{__('Discard Changes and Leave', 'schedula-smart-appointment-booking')}}</button>
          </div>
        </div>
      </div>
    </transition>
    <!-- Label Editor Modal -->
    <LabelEditorModal :show="showLabelEditorModal" :initialText="currentEditingLabelValue" @save="handleLabelSave" @close="handleLabelClose" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ServiceFormClientLivePreview from './ServiceFormClientLivePreview.vue';
import LabelEditorModal from './LabelEditorModal.vue';
import ServiceFormDetails from './ServiceFormDetails.vue';
import ServiceFormCustomizationSkeleton from './ServiceFormCustomizationSkeleton.vue';
import { formsApi, appearanceApi, servicesCategoriesApi, appointmentsApi } from '@/admin/api';
import { useAppearanceSettings } from '@/admin/composables/useAppearanceSettings.js';
import { useAdminAppearance } from '../../composables/useAdminAppearance';
import { formEditState } from '../../state';
import { useToast } from '@/admin/composables/useToast.js'; // Import useToast
import { __ } from '@wordpress/i18n';


const { appearanceSettings, adminCustomStyles } = useAdminAppearance();
const { settings } = useAppearanceSettings();

const route = useRoute();
const router = useRouter();

const showLabelEditorModal = ref(false);
const currentEditingLabelKey = ref('');
const currentEditingLabelValue = ref('');

const formId = computed(() => route.params.id || null);
const isNewForm = computed(() => !formId.value);

const form = ref({
  id: null,
  name: 'New Service Form',
  service_id: null,
  category_id: null,
  staff_id: null,
  form_data: '{}',
});

const originalForm = ref(null);
const originalSettings = ref(null);

const loadingForm = ref(true);
const formError = ref(null); // This will still be used for initial load error to trigger retry button
const saving = ref(false);
const resetting = ref(false);

const showResetConfirmModal = ref(false);
const showUnsavedChangesModal = ref(false);
let nextRoute = null;

const activeTab = ref('general');
const tabs = [
  { id: 'general', name: __('General Settings', 'schedula-smart-appointment-booking'), icon: 'fas fa-cog' },
  { id: 'global', name: __('Global Settings', 'schedula-smart-appointment-booking'), icon: 'fas fa-paint-brush' },
  { id: 'services', name: __('Services', 'schedula-smart-appointment-booking'), icon: 'fas fa-concierge-bell' },
  { id: 'calendar', name: __('Calendar', 'schedula-smart-appointment-booking'), icon: 'fas fa-calendar-alt' },
  { id: 'customer', name: __('Customer', 'schedula-smart-appointment-booking'), icon: 'fas fa-user-circle' },
  { id: 'payment', name: __('Payment', 'schedula-smart-appointment-booking'), icon: 'fas fa-money-bill-wave' },
  { id: 'confirmation', name: __('Confirmation', 'schedula-smart-appointment-booking'), icon: 'fas fa-check-circle' },
  { id: 'custom-css', name: __('Custom CSS', 'schedula-smart-appointment-booking'), icon: 'fas fa-code' },
];

const services = ref([]);
const categories = ref([]);
const staff = ref([]);


// --- Use the toast composable ---
const { success, error: toastError, info } = useToast();


const fetchBookingFormData = async () => {
  try {
    const response = await appointmentsApi.getBookingFormData();
    services.value = response.data.services;
    categories.value = response.data.categories;
    staff.value = response.data.staff;
  } catch (error) {
    console.error('Error fetching booking form data:', error);
    toastError(__('Failed to load form data.', 'schedula-smart-appointment-booking'));
  }
};







watch(() => settings.layout.customFontFamily, (newCustomFont) => {
  settings.layout.fontFamily = newCustomFont || 'sans-serif';
});

const isInitialLoadComplete = ref(false);

const isDirty = computed(() => {
  if (!isInitialLoadComplete.value) {
    return false;
  }
  const formIsDirty = JSON.stringify(form.value) !== JSON.stringify(originalForm.value);
  const settingsIsDirty = JSON.stringify(settings) !== JSON.stringify(originalSettings.value);
  return formIsDirty || settingsIsDirty;
});

const previewStep = ref(0);
watch(activeTab, (newTab) => {
  if (newTab === 'general' || newTab === 'services' || newTab === 'global') {
    previewStep.value = 0; // Summary View
  } else if (newTab === 'calendar') {
    previewStep.value = 1; // Date & Time Step
  } else if (newTab === 'customer') {
    previewStep.value = 2; // Customer Details Step
  } else if (newTab === 'payment') {
    previewStep.value = 3; // Payment Step
  } else if (newTab === 'confirmation') {
    previewStep.value = 4; // Confirmation Step
  } else if (newTab === 'custom-css') {
    previewStep.value = 0; // Summary View (or appropriate default for CSS changes)
  }
});

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

const processLoadedForm = (loadedForm) => {
    let parsedFormData = {};
    if (loadedForm.form_data) {
        if (typeof loadedForm.form_data === 'string') {
            try {
                parsedFormData = JSON.parse(loadedForm.form_data);
            } catch (e) {
                console.error('Error parsing form_data string:', e);
                parsedFormData = {};
            }
        } else if (typeof loadedForm.form_data === 'object') {
            parsedFormData = loadedForm.form_data;
        }
    }
    
    form.value = { ...loadedForm, form_data: parsedFormData };
    
    Object.assign(settings, mergeDeep(settings, parsedFormData));

    

    originalForm.value = JSON.parse(JSON.stringify(form.value));
    originalSettings.value = JSON.parse(JSON.stringify(settings));
}

const loadForm = async (skipFormFetch = false) => {
  formError.value = null;
  try {
    const globalSettingsResponse = await appearanceApi.getSettings();
    if (globalSettingsResponse.data && globalSettingsResponse.data.data) {
      Object.assign(settings, mergeDeep(settings, globalSettingsResponse.data.data));
    }

    if (formId.value && !skipFormFetch) {
      const formResponse = await formsApi.getForm(formId.value);
      processLoadedForm(formResponse.data);
    } else if (!formId.value) {
      // This is a new form, just set original state
      originalForm.value = JSON.parse(JSON.stringify(form.value));
      originalSettings.value = JSON.parse(JSON.stringify(settings));
    }
  } catch (error) {
    console.error('Error loading form:', error);
    formError.value = 'Failed to load form. Please try again.';
    toastError(formError.value);
  } finally {
    loadingForm.value = false;
  }
};

const saveForm = async () => {
  saving.value = true;
  try {
    const dataToSave = {
      ...form.value,
      serviceId: form.value.service_id,
      categoryId: form.value.category_id,
      staffId: form.value.staff_id,
      formData: JSON.stringify(settings),
    };

    if (isNewForm.value) {
      const createResponse = await formsApi.createForm(dataToSave);
      success(__('Form created successfully!', 'schedula-smart-appointment-booking'));
      const newFormId = (createResponse.data && createResponse.data.id) ? createResponse.data.id : createResponse.id;

      if (newFormId) {
        const formResponse = await formsApi.getForm(newFormId);
        processLoadedForm(formResponse.data);
        router.replace({ params: { id: newFormId } });
      } else {
        toastError(__('Failed to get form ID after creation.', 'schedula-smart-appointment-booking'));
      }
    } else {
      const updateResponse = await formsApi.updateForm(form.value.id, dataToSave);
      success(__('Form saved successfully!', 'schedula-smart-appointment-booking'));
      const savedForm = updateResponse.data || updateResponse;
      processLoadedForm(savedForm);
    }
  } catch (error) {
    console.error('Error saving form:', error);
    toastError(error.response?.data?.message || __('Failed to save form. Please try again.', 'schedula-smart-appointment-booking'));
  } finally {
    saving.value = false;
  }
};

const resetSettingsConfirm = () => {
  showResetConfirmModal.value = true;
};

const resetSettings = async () => {
  showResetConfirmModal.value = false;
  resetting.value = true;
  const defaultAppearance = useAppearanceSettings().settings;
  Object.assign(settings, JSON.parse(JSON.stringify(defaultAppearance)));
  settings.layout.formWidth = '960px';
  form.value.form_data = JSON.stringify(settings);
  success(__('Settings reset to default values!', 'schedula-smart-appointment-booking'));
  originalSettings.value = JSON.parse(JSON.stringify(settings)); 
  originalForm.value = JSON.parse(JSON.stringify(form.value));
  resetting.value = false;
};

const cancel = () => {
  if (isDirty.value) {
    showUnsavedChangesModal.value = true;
    nextRoute = '/ServiceFormReservation';
  } else {
    router.push('/ServiceFormReservation');
  }
};

const confirmDiscardAndLeave = () => {
  showUnsavedChangesModal.value = false;
  if (nextRoute) {
    router.push(nextRoute);
  }
};

const cancelUnsavedChangesLeave = () => {
  showUnsavedChangesModal.value = false;
  nextRoute = null;
};

const openLabelEditor = ({ key, value }) => {
  currentEditingLabelKey.value = key;
  currentEditingLabelValue.value = value;
  showLabelEditorModal.value = true;
};

const updateLabel = ({ key, value }) => {
  const path = key.split('.');
  let current = settings.labels;
  for (let i = 0; i < path.length - 1; i++) {
    if (!current[path[i]]) current[path[i]] = {};
    current = current[path[i]];
  }
  current[path[path.length - 1]] = value;
};

const handleLabelSave = async (newText) => {
  if (currentEditingLabelKey.value) {
    updateLabel({ key: currentEditingLabelKey.value, value: newText });
    await saveForm();
  }
  showLabelEditorModal.value = false;
};

const handleLabelClose = () => {
  showLabelEditorModal.value = false;
  currentEditingLabelKey.value = '';
  currentEditingLabelValue.value = '';
};

onMounted(async () => {
  isInitialLoadComplete.value = false;

  // Fetch data sequentially for stability
  await fetchBookingFormData();

  if (formId.value && formEditState.form && formEditState.form.id == formId.value) {
    await loadForm();
    processLoadedForm(formEditState.form);
    
    formEditState.form = null;
    formEditState.services = null;
    formEditState.categories = null;
    formEditState.staff = null; 
  } else {
    await loadForm();
  }
  
  isInitialLoadComplete.value = true; 
});
</script>

<style scoped>
/* Scoped styles for this component */
.schedula-settings {
  font-family: var(--admin-font-family);
  background-color: var(--admin-page-bg-color);
  color: var(--admin-text-color);
}

.section-title {
  font-size: 1.125rem; /* text-lg */
  font-weight: 600; /* font-semibold */
  color: var(--admin-text-color);
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--admin-border-color);
  display: flex;
  align-items: center;
}

.section-subtitle {
  font-size: 1rem; /* text-base */
  font-weight: 600; /* font-semibold */
  color: var(--admin-text-color);
  margin-top: 1.5rem;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px dashed var(--admin-border-color);
  display: flex;
  align-items: center;
}

.form-checkbox {
  accent-color: var(--admin-link-indigo-bg);
  width: 1.125em; /* Default browser checkbox size */
  height: 1.125em; /* Default browser checkbox size */
  border-color: var(--admin-input-border-color);
  background-color: var(--admin-input-bg-color);
}

.schedula-form .form-select, 
.schedula-form .form-input,
.schedula-form input[type="text"],
.schedula-form input[type="color"] {
  width: 100% !important;
  height: 32px !important;
  line-height: 32px !important;
  padding: 0 8px !important;
  font-size: 13px !important;
  border-radius: 4px !important;
  vertical-align: middle !important;
  border: 1px solid var(--admin-input-border-color);
  background-color: var(--admin-input-bg-color);
  color: var(--admin-input-text-color);
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.schedula-form input[type="color"] {
  width: 60px !important;
  padding: 2px 4px !important;
}

.form-select:focus, .form-input:focus {
  border-color: var(--admin-link-indigo-bg);
  box-shadow: 0 0 0 1px var(--admin-link-indigo-bg);
  outline: none;
}

.checkbox-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.75rem; /* gap-3 */
}

@media (min-width: 768px) { /* md breakpoint */
  .checkbox-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.loading-container, .error-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 200px;
  background-color: var(--admin-card-bg-color);
  border-radius: 0.5rem;
  box-shadow: var(--admin-shadow);
  color: var(--admin-text-color);
}

.loading-spinner {
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.error-message {
  padding: 1rem;
  border-radius: 0.5rem;
  border: 1px solid;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  text-align: center;
}

.retry-btn {
  background-color: var(--admin-button-secondary-bg);
  color: var(--admin-button-secondary-text);
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s ease;
  margin-top: 1rem;
}

.retry-btn:hover {
  background-color: var(--admin-button-secondary-hover-bg);
}

/* Modal styles (Reset Confirmation & Label Editor) */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: var(--admin-modal-overlay-bg); /* Semi-transparent black overlay */
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 999;
}

.modal-dialog {
  background-color: var(--admin-card-bg-color);
  padding: 25px;
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
  border-bottom: 1px solid var(--admin-border-color);
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--admin-text-color);
}

.modal-close-button {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: var(--admin-card-text-color);
}

.modal-body {
  margin-bottom: 20px;
  color: var(--admin-card-text-color);
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
  background-color: var(--admin-button-secondary-bg);
  color: var(--admin-button-secondary-text);
  border: 1px solid var(--admin-input-border-color);
}

.modal-cancel-button:hover {
  background-color: var(--admin-button-secondary-hover-bg);
}

.modal-confirm-button {
  background-color: var(--admin-suggestion-red-bg);
  color: var(--admin-suggestion-red-text);
  border: 1px solid var(--admin-input-border-color);
}

.modal-confirm-button:hover {
  background-color: var(--admin-suggestion-red-hover-bg);
}

/* Transition styles */
.modal-fade-enter-active, .modal-fade-leave-active {
  transition: opacity 0.3s ease;
}
.modal-fade-enter-from, .modal-fade-leave-to {
  opacity: 0;
}

/* Further refinements for the preview area */
.schedula-preview {
  height: 100%;
}

.preview-title {
  font-size: 1.125rem; /* text-lg */
  font-weight: 600; /* font-semibold */
  color: var(--admin-text-color);
  display: flex;
  align-items: center;
}

.preview-wrapper {
  /* This ensures the iframe container takes full height and width */
  width: 100%;
  height: 100%;
  border-radius: inherit; /* Inherit border-radius from parent .flex-grow */
  overflow: hidden; /* Hide overflow for rounded corners */
}
</style>