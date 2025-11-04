<template>
  <!-- Main container with conditional dark mode class and dynamic styles from composable -->
  <div class="flex flex-col lg:flex-row min-h-screen px-4 lg:px-6 py-6 gap-6">
    
    <!-- Sidebar Navigation -->
    <aside class="w-full lg:w-64 shadow-md lg:shadow-none sidebar">
      <nav>
        <div class="header-container">
        <h2 class="text-lg font-semibold mt-4 mb-2 right-aligned-heading">{{ __('Settings', 'schedula-smart-appointment-booking') }}</h2>
        </div>
        <ul>
          <li class="mb-2">
            <a href="#" @click.prevent="activeTab = 'general'"
               :class="{
                 'text-white': activeTab === 'general',
                 'sidebar-link-active': activeTab === 'general',
               }"
               class="block px-4 py-2 rounded-md transition-colors duration-200 sidebar-link">
              {{ __('General', 'schedula-smart-appointment-booking') }}
            </a>
          </li>
          <li class="mb-2">
            <a href="#" @click.prevent="activeTab = 'company'"
               :class="{
                 'text-white': activeTab === 'company',
                 'sidebar-link-active': activeTab === 'company',
               }"
               class="block px-4 py-2 rounded-md transition-colors duration-200 sidebar-link">
              {{ __('Company', 'schedula-smart-appointment-booking') }}
            </a>
          </li>
          <li class="mb-2">
            <a href="#" @click.prevent="activeTab = 'appointment'"
               :class="{
                 'text-white': activeTab === 'appointment',
                 'sidebar-link-active': activeTab === 'appointment',
               }"
               class="block px-4 py-2 rounded-md transition-colors duration-200 sidebar-link">
              {{ __('Appointment', 'schedula-smart-appointment-booking') }}
            </a>
          </li>
          <li class="mb-2">
            <a href="#" @click.prevent="activeTab = 'payments'"
               :class="{
                 'text-white': activeTab === 'payments',
                 'sidebar-link-active': activeTab === 'payments',
               }"
               class="block px-4 py-2 rounded-md transition-colors duration-200 sidebar-link">
              {{ __('Payments', 'schedula-smart-appointment-booking') }}
            </a>
          </li>
          <li class="mb-2">
            <a href="#" @click.prevent="activeTab = 'workingHours'"
               :class="{
                 'text-white': activeTab === 'workingHours',
                 'sidebar-link-active': activeTab === 'workingHours',
               }"
               class="block px-4 py-2 rounded-md transition-colors duration-200 sidebar-link">
              {{ __('Working Hours', 'schedula-smart-appointment-booking') }}
            </a>
          </li>
          <li class="mb-2">
            <a href="#" @click.prevent="activeTab = 'holidays'"
               :class="{
                 'text-white': activeTab === 'holidays',
                 'sidebar-link-active': activeTab === 'holidays',
               }"
               class="block px-4 py-2 rounded-md transition-colors duration-200 sidebar-link">
              {{ __('Holidays', 'schedula-smart-appointment-booking') }}
            </a>
          </li>
          <li class="mb-2">
            <a href="#" @click.prevent="activeTab = 'appearance'"
               :class="{
                 'text-white': activeTab === 'appearance',
                 'sidebar-link-active': activeTab === 'appearance',
               }"
               class="block px-4 py-2 rounded-md transition-colors duration-200 sidebar-link">
              {{ __('Admin Appearance', 'schedula-smart-appointment-booking') }}
            </a>
          </li>
          <li class="mb-2">
            <a href="#" @click.prevent="activeTab = 'analytics'"
               :class="{
                 'text-white': activeTab === 'analytics',
                 'sidebar-link-active': activeTab === 'analytics',
               }"
               class="block px-4 py-2 rounded-md transition-colors duration-200 sidebar-link">
              {{ __('Analytics', 'schedula-smart-appointment-booking') }}
            </a>
          </li>
        </ul>
      </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1">
      <div v-if="isLoading" class="rounded-lg shadow-md p-8 content-card animate-pulse">
        <!-- Title Skeleton -->
        <div class="h-8 bg-gray-300 rounded w-1/3 mb-2"></div>
        <div class="h-1 bg-gray-300 rounded w-12 mb-8"></div>

        <!-- Settings Items Skeleton -->
        <div class="space-y-8">
          <div v-for="i in 5" :key="i" class="grid grid-cols-1 gap-3">
            <div class="h-4 bg-gray-300 rounded w-1/4"></div>
            <div class="h-10 bg-gray-300 rounded w-1/2"></div>
            <div class="h-3 bg-gray-300 rounded w-2/3"></div>
          </div>
        </div>
      </div>
      <div v-else class="rounded-lg shadow-md p-8 content-card">
        <div class="mb-8">
          <h1 class="text-2xl font-bold">
              {{ currentTabTitle }}
          </h1>
          <div class="mt-1 h-1 w-12 rounded-full" :style="{ backgroundColor: appearanceSettings?.colors?.primary || '#3b82f6' }"></div>
        </div>

        <!-- General Settings Tab -->
        <div v-if="activeTab === 'general'" class="space-y-6">
          <!-- Time Slot Length -->
          <div class="grid grid-cols-1 gap-4">
            <label for="timeSlotLength" class="block text-sm font-medium">
              {{ __('Time Slot Length (minutes)', 'schedula-smart-appointment-booking') }}
            </label>
            <select 
              id="timeSlotLength" 
              v-model="settings.general.timeSlotLength"
              class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
            >
              <option v-for="option in timeSlotLengthOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
            <p class="mt-2 text-xs text-secondary">{{ __('Clients can book appointments every X minutes.', 'schedula-smart-appointment-booking') }}</p>
          </div>

          <!-- Time Format -->
          <div class="grid grid-cols-1 gap-4">
            <label for="timeFormat" class="block text-sm font-medium">
              {{ __('Time Format', 'schedula-smart-appointment-booking') }}
            </label>
            <select 
              id="timeFormat" 
              v-model="settings.general.timeFormat"
              class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
            >
              <option v-for="option in timeFormatOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
            <p class="mt-2 text-xs text-secondary">{{ __('Choose how time is displayed across the application (e.g., 1:00 PM or 13:00).', 'schedula-smart-appointment-booking') }}</p>
          </div>

          <!-- Minimum Time Before Booking -->
          <div class="grid grid-cols-1 gap-4">
            <label for="minTimeBooking" class="block text-sm font-medium">
              {{ __('Minimum Time Before Booking', 'schedula-smart-appointment-booking') }}
            </label>
            <select 
              id="minTimeBooking" 
              v-model="settings.general.minTimeBooking"
              class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
            >
              <option v-for="option in minTimeBookingOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
            <p class="mt-2 text-xs text-secondary">{{ __('Bookings must be made at least this much time in advance.', 'schedula-smart-appointment-booking') }}</p>
          </div>

          <!-- Minimum Time Before Canceling -->
          <div class="grid grid-cols-1 gap-4">
            <label for="minTimeCanceling" class="block text-sm font-medium">
              {{ __('Minimum Time Before Canceling', 'schedula-smart-appointment-booking') }}
            </label>
            <select 
              id="minTimeCanceling" 
              v-model="settings.general.minTimeCanceling"
              class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
            >
              <option v-for="option in minTimeCancelingOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
            <p class="mt-2 text-xs text-secondary">{{ __('Cancellations are allowed up to this much time before an appointment.', 'schedula-smart-appointment-booking') }}</p>
          </div>

          <!-- Booking Buffer Time -->
          <div class="grid grid-cols-1 gap-4">
            <label for="bookingBufferTime" class="block text-sm font-medium">
              {{ __('Time Buffer Between Bookings', 'schedula-smart-appointment-booking') }}
            </label>
            <select 
              id="bookingBufferTime" 
              v-model="settings.general.bookingBufferTime"
              class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
            >
              <option v-for="option in bookingBufferTimeOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
            <p class="mt-2 text-xs text-secondary">{{ __('Adds a buffer after each appointment, during which the staff member cannot be booked.', 'schedula-smart-appointment-booking') }}</p>
          </div>

          

          <!-- Display Timezone -->
          <div class="flex items-center mb-4">
            <input
              id="displayTimezone"
              v-model="settings.general.displayTimezone"
              type="checkbox"
              class="h-4 w-4 rounded form-checkbox"
              :style="{borderColor: 'black', backgroundColor: settings.general.displayTimezone ? 'var(--admin-link-indigo-bg)' : 'white'}"
            />
            <label for="displayTimezone" class="ml-2 block text-sm font-medium">
              {{ __("Display Client's Timezone", 'schedula-smart-appointment-booking') }}
              <p class="text-xs text-secondary">
                {{ __("If checked, appointment times will be displayed in the client's local timezone.", 'schedula-smart-appointment-booking') }}
              </p>
            </label>
          </div>

          <!-- Instant Booking Option -->
          <div class="flex items-center mb-4">
            <input
              id="instantBookingEnabled"
              v-model="settings.general.instantBookingEnabled"
              type="checkbox"
              class="h-4 w-4 rounded form-checkbox"
              :style="{borderColor: 'black', backgroundColor: settings.general.instantBookingEnabled ? 'var(--admin-link-indigo-bg)' : 'white'}"
            />
            <label for="instantBookingEnabled" class="ml-2 block text-sm font-medium">
              {{ __('Enable Instant Booking', 'schedula-smart-appointment-booking') }}
              <p class="text-xs text-secondary">
                {{ __('If checked, all new bookings will be instantly confirmed. Otherwise, they will be marked as pending.', 'schedula-smart-appointment-booking') }}
              </p>
            </label>
          </div>

          <!-- Business Timezone -->
          <div class="grid grid-cols-1 gap-4">
            <label for="businessTimezone" class="block text-sm font-medium">
              {{ __('Business Timezone', 'schedula-smart-appointment-booking') }}
            </label>
            <div class="relative mt-1 w-full max-w-xs">
              <input
                id="businessTimezone"
                type="text"
                :value="showTimezoneResults ? timezoneSearchQuery : selectedTimezoneName"
                @input="timezoneSearchQuery = $event.target.value"
                @focus="showTimezoneResults = true; timezoneSearchQuery = ''"
                @blur="hideTimezoneResultsDelayed"
                :placeholder="__('Search or select a timezone...', 'schedula-smart-appointment-booking')"
                class="w-full border rounded-md shadow-sm p-2 input-field"
              />
              <div
                v-if="showTimezoneResults"
                class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto mt-1"
                :style="{ backgroundColor: 'var(--admin-input-bg-color)', borderColor: 'var(--admin-input-border-color)' }"
              >
                <button
                  v-for="timezone in filteredTimezones"
                  :key="timezone"
                  @mousedown.prevent="selectTimezone(timezone)"
                  class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100"
                  :style="{ color: 'var(--admin-input-text-color)', '--tw-ring-color': 'var(--admin-link-indigo-bg)' }"
                >
                  {{ timezone }}
                </button>
                <div v-if="!filteredTimezones.length" class="px-4 py-2 text-sm text-gray-500">
                  {{ __('No timezones found.', 'schedula-smart-appointment-booking') }}
                </div>
              </div>
            </div>
            <p class="mt-2 text-xs text-secondary">
              {{ __('Set the primary timezone for your business operations and staff schedules.', 'schedula-smart-appointment-booking') }}
            </p>
          </div>

          <!-- Follow Admin Timezone -->
          <div class="grid grid-cols-1 gap-4">
            <div class="flex items-center mb-1">
              <input
                id="followAdminTimezone"
                v-model="settings.general.followAdminTimezone"
                type="checkbox"
                class="h-4 w-4 rounded form-checkbox"
                :style="{borderColor: 'black', backgroundColor: settings.general.followAdminTimezone ? 'var(--admin-link-indigo-bg)' : 'white'}"
              />
              <label for="followAdminTimezone" class="ml-2 block text-sm font-medium">
                {{ __('Follow Admin Timezone', 'schedula-smart-appointment-booking') }}
              </label>
            </div>
            <p class="text-xs text-secondary">
              {{ __('If enabled, appointments will automatically follow your current WordPress timezone. If disabled, appointments will use the fixed business timezone above.', 'schedula-smart-appointment-booking') }}
            </p>
          </div>

          <!-- Auto-detect Timezone -->
          <div class="grid grid-cols-1 gap-4" v-if="settings.general.followAdminTimezone">
            <div class="rounded-md p-4" :style="{ backgroundColor: 'var(--admin-link-indigo-bg-light)', border: '1px solid var(--admin-input-border-color)'}">
              <div class="flex items-start">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5" :style="{ color: 'var(--admin-link-indigo-bg)' }" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3 flex-1">
                  <h3 class="text-sm font-medium" :style="{ color: 'var(--admin-text-color)' }">
                    {{ __('Auto-detect Timezone', 'schedula-smart-appointment-booking') }}
                  </h3>
                  <div class="mt-2 text-sm" :style="{ color: 'var(--admin-text-color)' }">
                    <p>{{ __('Detected timezone:', 'schedula-smart-appointment-booking') }} <strong>{{ detectedTimezone }}</strong></p>
                    <p class="mt-1">{{ __('Current time:', 'schedula-smart-appointment-booking') }} <strong>{{ currentTime }}</strong></p>
                  </div>
                  <div class="mt-3">
                    <button
                      @click="detectAndUpdateTimezone"
                      :disabled="isDetectingTimezone"
                      class="inline-flex items-center px-3 py-2 text-sm leading-4 font-medium rounded-md text-white disabled:opacity-50"
                      :style="{ backgroundColor: 'var(--admin-button-primary-bg)'}"
                    >
                      <svg v-if="isDetectingTimezone" class="animate-spin -ml-1 mr-2 h-4 w-4" :style="{ color: 'var(--admin-text-color)' }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      {{ isDetectingTimezone ? __('Detecting...', 'schedula-smart-appointment-booking') : __('Update WordPress Timezone', 'schedula-smart-appointment-booking') }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Delete Data on Uninstall -->
          <div class="grid grid-cols-1 gap-4">
            <label for="deleteDataOnUninstall" class="block text-sm font-medium">
              {{ __('Delete Schedula Data on Uninstall', 'schedula-smart-appointment-booking') }}
            </label>
            <select 
              id="deleteDataOnUninstall" 
              v-model="settings.general.deleteDataOnUninstall"
              class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
            >
              <option value="dont_delete">{{ __("Don't Delete", 'schedula-smart-appointment-booking') }}</option>
              <option value="delete">{{ __('Delete', 'schedula-smart-appointment-booking') }}</option>
            </select>
            <p class="mt-2 text-xs text-secondary">
              {{ __('If you choose Delete, all data associated with Schedula will be permanently deleted when uninstalling the plugin.', 'schedula-smart-appointment-booking') }}
            </p>
          </div>
        </div>

        <!-- Company Settings Tab -->
        <div v-else-if="activeTab === 'company'" class="space-y-6">
            
          <!-- Company Logo Section - NEW VISUAL LAYOUT -->
          <div class="border-b border-gray-200 dark:border-gray-600 pb-6 mb-6">
        
            <label for="companyLogoUpload" class="block text-sm font-medium mb-2">
              {{ __('Upload or Select Logo', 'schedula-smart-appointment-booking') }}
            </label>
            <div class="relative w-40 h-40 border-2 border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center cursor-pointer overflow-hidden group hover:border-blue-500 transition-colors duration-200 dark:border-gray-500 dark:hover:border-blue-400"
                 @click="openMediaUploader"
                 id="companyLogoUpload">
                
                <template v-if="settings.company.companyLogoUrl">
                    <img :src="settings.company.companyLogoUrl" :alt="__('Company Logo Preview', 'schedula-smart-appointment-booking')" 
                         class="absolute inset-0 w-full h-full object-contain p-1" @error="handleImageError"/>
                </template>
                
                <template v-else>
                    <i class="fas fa-camera text-gray-400 text-4xl group-hover:text-blue-500 transition-colors duration-200 dark:text-gray-400 dark:group-hover:text-blue-400"></i>
                    <p class="text-sm text-gray-500 mt-2 group-hover:text-blue-500 transition-colors duration-200 dark:text-gray-400 dark:group-hover:text-blue-400">{{ __('Image', 'schedula-smart-appointment-booking') }}</p>
                </template>

                <div v-if="settings.company.companyLogoUrl" class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    <button type="button" @click.stop="removeCompanyLogo" 
                            class="p-2 rounded-full bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors duration-200">
                        <i class="fas fa-trash-alt text-lg"></i>
                    </button>
                </div>
            </div>
            <p class="mt-2 text-xs text-secondary">{{ __('Click the box to upload or select an image from your WordPress media library.', 'schedula-smart-appointment-booking') }}</p>
          </div>

          <!-- Company Name -->
          <div class="grid grid-cols-1 gap-4">
            <label for="companyName" class="block text-sm font-medium">{{ __('Company Name', 'schedula-smart-appointment-booking') }}</label>
            <input 
              id="companyName" 
              v-model="settings.company.companyName" 
              type="text" 
              class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
              :class="validationErrors.companyName ? 'border-red-500' : 'border-gray-300'"
            />
            <p class="mt-2 text-xs text-secondary">{{ __('The official name of your business.', 'schedula-smart-appointment-booking') }}</p>
            <p v-if="validationErrors.companyName" class="text-red-500 text-sm mt-1">
              {{ validationErrors.companyName }}
            </p>
          </div>

          <!-- Address -->
          <div class="grid grid-cols-1 gap-4">
            <label for="address" class="block text-sm font-medium">{{ __('Address', 'schedula-smart-appointment-booking') }}</label>
            <input 
              id="address" 
              v-model="settings.company.address" 
              type="text" 
              class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
            />
            <p class="mt-2 text-xs text-secondary">{{ __('Your company\'s physical address.', 'schedula-smart-appointment-booking') }}</p>
          </div>

          <!-- Phone -->
            <div class="grid grid-cols-1 gap-4">
            <label for="phone" class="block text-sm font-medium">{{ __('Phone Number', 'schedula-smart-appointment-booking') }}</label>
            <BasePhoneInput
              id="phone"
              v-model="settings.company.phone"
              class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
            />
            <p class="mt-2 text-xs text-secondary">{{ __('Your company\'s main contact phone number.', 'schedula-smart-appointment-booking') }}</p>
            </div>

          <!-- Email -->
          <div class="grid grid-cols-1 gap-4">
            <label for="email" class="block text-sm font-medium">{{ __('Contact Email', 'schedula-smart-appointment-booking') }}</label>
            <input 
              id="email" 
              v-model="settings.company.email" 
              type="email" 
              class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
              :class="validationErrors.email ? 'border-red-500' : 'border-gray-300'"
            />
            <p class="mt-2 text-xs text-secondary">{{ __('Your company\'s primary contact email address.', 'schedula-smart-appointment-booking') }}</p>
            <p v-if="validationErrors.email" class="text-red-500 text-sm mt-1">
              {{ validationErrors.email }}
            </p>
          </div>

          <!-- Website -->
          <div class="grid grid-cols-1 gap-4">
            <label for="website" class="block text-sm font-medium">{{ __('Website URL', 'schedula-smart-appointment-booking') }}</label>
            <input 
              id="website" 
              v-model="settings.company.website" 
              type="url" 
              class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
            />
            <p class="mt-2 text-xs text-secondary">{{ __('Your company\'s official website address (e.g., https://yourcompany.com).', 'schedula-smart-appointment-booking') }}</p>
          </div>

          <!-- Industry -->
          <div class="grid grid-cols-1 gap-4">
            <label for="industry" class="block text-sm font-medium">{{ __('Industry', 'schedula-smart-appointment-booking') }}</label>
            <select 
              id="industry" 
              v-model="settings.company.industry"
              class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
            >
              <option value="">{{ __('Select Industry', 'schedula-smart-appointment-booking') }}</option>
              <optgroup v-for="group in groupedIndustries" :key="group.name" :label="group.name">
                <option v-for="option in group.options" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </optgroup>
            </select>
            <p class="mt-2 text-xs text-secondary">{{ __('The primary industry your business operates in.', 'schedula-smart-appointment-booking') }}</p>
          </div>

          <!-- Services Offered (Textarea for description) -->
          <div class="grid grid-cols-1 gap-4">
            <label for="servicesOffered" class="block text-sm font-medium">{{ __('Services Description', 'schedula-smart-appointment-booking') }}</label>
            <textarea 
              id="servicesOffered" 
              v-model="settings.company.servicesOffered" 
              rows="3" 
              class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
            ></textarea>
            <p class="mt-2 text-xs text-secondary">{{ __('A brief description of the main services your company provides.', 'schedula-smart-appointment-booking') }}</p>
          </div>

          <!-- Company Size -->
          <div class="grid grid-cols-1 gap-4">
            <label for="companySize" class="block text-sm font-medium">{{ __('Company Size', 'schedula-smart-appointment-booking') }}</label>
            <select 
              id="companySize" 
              v-model="settings.company.companySize"
              class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
            >
              <option value="">{{ __('Select Company Size', 'schedula-smart-appointment-booking') }}</option>
              <option v-for="option in utilityOptions.companySizes" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
            <p class="mt-2 text-xs text-secondary">{{ __('Approximate number of employees.', 'schedula-smart-appointment-booking') }}</p>
          </div>

        </div>

        <!-- Appointment Settings Tab -->
        <div v-else-if="activeTab === 'appointment'" class="space-y-6">
          <h3 class="text-xl font-semibold mb-6">{{ __('Booking Rules', 'schedula-smart-appointment-booking') }}</h3>

          <!-- Enable Group Booking -->
          <div class="flex items-center mb-4">
            <input
              id="enableGroupBooking"
              v-model="settings.general.enableGroupBooking"
              type="checkbox"
              class="h-4 w-4 rounded form-checkbox"
              :style="{borderColor: 'black', backgroundColor: settings.general.enableGroupBooking ? 'var(--admin-link-indigo-bg)' : 'white'}"
            />
            <label for="enableGroupBooking" class="ml-2 block text-sm font-medium">
              {{ __('Enable Group Booking', 'schedula-smart-appointment-booking') }}
              <p class="text-xs text-secondary">
                {{ __('Allow customers to book for more than one person at a time.', 'schedula-smart-appointment-booking') }}
              </p>
            </label>
          </div>

          <!-- Group Booking Options (Conditionally displayed) -->
          <div v-if="settings.general.enableGroupBooking" class="space-y-6 mt-4 p-4 border rounded-lg" :style="{ backgroundColor: 'var(--admin-card-bg-color)', borderColor: 'var(--admin-input-border-color)' }">
            <h4 class="text-lg font-semibold mb-4">{{ __('Group Booking Settings', 'schedula-smart-appointment-booking') }}</h4>

            <!-- Max Persons Per Booking -->
            <div class="grid grid-cols-1 gap-4">
              <label for="maxPersonsPerBooking" class="block text-sm font-medium">
                {{ __('Maximum Number of Persons', 'schedula-smart-appointment-booking') }}
              </label>
              <input 
                id="maxPersonsPerBooking" 
                v-model.number="settings.general.maxPersonsPerBooking"
                type="number" 
                min="1"
                class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
              />
              <p class="mt-2 text-xs text-secondary">{{ __('Set the maximum number of people that can be booked in a single appointment.', 'schedula-smart-appointment-booking') }}</p>
            </div>

            <!-- Group Booking Price Logic -->
            <div class="grid grid-cols-1 gap-4">
              <label for="groupBookingPriceLogic" class="block text-sm font-medium">
                {{ __('Group Booking Price Logic', 'schedula-smart-appointment-booking') }}
              </label>
              <select 
                id="groupBookingPriceLogic" 
                v-model="settings.general.groupBookingPriceLogic.type"
                class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
              >
                <option value="per_person_multiply">{{ __('Multiply by number of persons', 'schedula-smart-appointment-booking') }}</option>
                <option value="fixed_discount_per_person">{{ __('Fixed discount per person', 'schedula-smart-appointment-booking') }}</option>
                <option value="percentage_discount_total">{{ __('Percentage discount on total', 'schedula-smart-appointment-booking') }}</option>
              </select>
              <p class="mt-2 text-xs text-secondary">
                {{ __('Define how the price is calculated for group bookings.', 'schedula-smart-appointment-booking') }}
              </p>
            </div>

            <!-- Group Booking Price Adjustment Amount (Conditionally displayed) -->
            <div v-if="settings.general.groupBookingPriceLogic.type !== 'per_person_multiply'" class="grid grid-cols-1 gap-4">
              <label for="groupBookingPriceAdjustmentAmount" class="block text-sm font-medium">
                {{ __('Adjustment Amount', 'schedula-smart-appointment-booking') }}
              </label>
              <input 
                id="groupBookingPriceAdjustmentAmount" 
                v-model.number="settings.general.groupBookingPriceLogic.amount"
                type="number" 
                min="0"
                :step="settings.general.groupBookingPriceLogic.type.includes('percentage') ? 0.01 : 1"
                class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
              />
              <p class="mt-2 text-xs text-secondary" v-if="settings.general.groupBookingPriceLogic.type === 'fixed_discount_per_person'">
                {{ __('Enter the fixed amount to discount per person (e.g., 5 for $5 off per person).', 'schedula-smart-appointment-booking') }}
              </p>
              <p class="mt-2 text-xs text-secondary" v-else-if="settings.general.groupBookingPriceLogic.type === 'percentage_discount_total'">
                {{ __('Enter the percentage to discount from the total price (e.g., 10 for 10% off the total).', 'schedula-smart-appointment-booking') }}
              </p>
            </div>
          </div>

          <hr class="my-8 border-gray-200 dark:border-gray-700" />

          <h3 class="text-xl font-semibold mb-6">{{ __('Recurring Appointments', 'schedula-smart-appointment-booking') }}</h3>
          
          <!-- Enable Recurring Appointments -->
          <div class="flex items-center mb-4">
            <input
              id="enableRecurringAppointments"
              v-model="settings.general.enableRecurringAppointments"
              type="checkbox"
              class="h-4 w-4 rounded form-checkbox"
              :style="{borderColor: 'black', backgroundColor: settings.general.enableRecurringAppointments ? 'var(--admin-link-indigo-bg)' : 'white'}"
            />
            <label for="enableRecurringAppointments" class="ml-2 block text-sm font-medium">
              {{ __('Enable Recurring Appointments', 'schedula-smart-appointment-booking') }}
              <p class="text-xs text-secondary">
                {{ __('Allow clients to book appointments that repeat regularly.', 'schedula-smart-appointment-booking') }}
              </p>
            </label>
          </div>

          <!-- Recurring Appointment Options (Conditionally displayed) -->
          <div v-if="settings.general.enableRecurringAppointments" class="space-y-6 mt-4 p-4 border rounded-lg" :style="{ backgroundColor: 'var(--admin-card-bg-color)', borderColor: 'var(--admin-input-border-color)' }">
            <h4 class="text-lg font-semibold mb-4">{{ __('Recurrence Settings', 'schedula-smart-appointment-booking') }}</h4>

            <!-- Max Recurrences -->
            <div class="grid grid-cols-1 gap-4">
              <label for="maxRecurrences" class="block text-sm font-medium">
                {{ __('Maximum Number of Recurrences', 'schedula-smart-appointment-booking') }}
              </label>
              <input 
                id="maxRecurrences" 
                v-model.number="settings.general.recurrence.maxRecurrences"
                type="number" 
                min="0"
                class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
              />
              <p class="mt-2 text-xs text-secondary">
                {{ __('Set the maximum number of times a recurring appointment can be booked. Set to 0 for no limit.', 'schedula-smart-appointment-booking') }}
              </p>
            </div>

            <!-- Payment Behavior for Recurring Appointments -->
            <div class="grid grid-cols-1 gap-4 mt-4">
              <label for="recurringPaymentBehavior" class="block text-sm font-medium">
                {{ __('Online Payment for Recurring Bookings', 'schedula-smart-appointment-booking') }}
              </label>
              <select 
                id="recurringPaymentBehavior" 
                v-model="settings.general.recurrence.paymentBehavior"
                class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
              >
                <option value="charge_all">{{ __('Charge for the entire series at once', 'schedula-smart-appointment-booking') }}</option>
                <option value="charge_first">{{ __('Charge for the first appointment only', 'schedula-smart-appointment-booking') }}</option>
              <!-- <option value="manual">{{ __("Don't charge online (create as unpaid)", 'schedula-smart-appointment-booking') }}</option> -->
              </select>
              <p class="mt-2 text-xs text-secondary">
                {{ __('Choose how to handle online payments for recurring appointment series.', 'schedula-smart-appointment-booking') }}
              </p>
            </div>
          </div>

        </div>

        <!-- Payments Settings Tab -->
        <div v-else-if="activeTab === 'payments'" class="space-y-6">
          <h3 class="text-xl font-semibold mb-6">{{ __('General Payment Settings', 'schedula-smart-appointment-booking') }}</h3>
          <!-- Currency Selector -->
          <div class="grid grid-cols-1 gap-4">
            <label for="currencyCode" class="block text-sm font-medium">
              {{ __('Select Currency', 'schedula-smart-appointment-booking') }}
            </label>
            <div class="relative mt-1 w-full max-w-xs">
              <input
                id="currencyCode"
                type="text"
                :value="showCurrencyResults ? currencySearchQuery : selectedCurrencyName"
                @input="currencySearchQuery = $event.target.value"
                @focus="showCurrencyResults = true; currencySearchQuery = ''"
                @blur="hideCurrencyResultsDelayed"
                :placeholder="__('Search or select a currency...', 'schedula-smart-appointment-booking')"
                class="w-full border rounded-md shadow-sm p-2 input-field"
              />
              <div
                v-if="showCurrencyResults"
                class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto mt-1"
                :style="{ backgroundColor: 'var(--admin-input-bg-color)', borderColor: 'var(--admin-input-border-color)' }"
              >
                <button
                  v-for="currency in filteredCurrencies"
                  :key="currency.code"
                  @mousedown.prevent="selectCurrency(currency)"
                  class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100"
                  :style="{ color: 'var(--admin-input-text-color)', '--tw-ring-color': 'var(--admin-link-indigo-bg)' }"
                >
                  {{ currency.name }} ({{ currency.symbol }}) - {{ currency.code }}
                </button>
                <div v-if="!filteredCurrencies.length" class="px-4 py-2 text-sm text-gray-500">
                  {{ __('No currencies found.', 'schedula-smart-appointment-booking') }}
                </div>
              </div>
            </div>
            <p class="mt-2 text-xs text-secondary">
              {{ __('Choose the primary currency for all prices in the booking system.', 'schedula-smart-appointment-booking') }}
            </p>
           </div>

          <!-- Enable Local Payment -->
          <div class="flex items-center mt-6">
            <input
              id="enableLocalPayment"
              v-model="settings.general.enableLocalPayment"
              type="checkbox"
              class="h-4 w-4 rounded form-checkbox"
              :style="{borderColor: 'black', backgroundColor: settings.general.enableLocalPayment ? 'var(--admin-link-indigo-bg)' : 'white'}"
            />
            <label for="enableLocalPayment" class="ml-2 block text-sm font-medium">
              {{ __('Enable "Pay in cash" Option', 'schedula-smart-appointment-booking') }}
              <p class="text-xs text-secondary">
                {{ __('If checked, clients will have the option to pay for services directly at your business location (e.g., cash, in-person card payments).', 'schedula-smart-appointment-booking') }}
              </p>
            </label>
          </div>

          <hr class="my-8 border-gray-200 dark:border-gray-700" />

          <h3 class="text-xl font-semibold mb-6">{{ __('Stripe Settings', 'schedula-smart-appointment-booking') }}</h3>

          <div class="flex items-center mb-4">
            <input
              id="enableStripe"
              v-model="settings.stripe.enableStripe"
              type="checkbox"
              class="h-4 w-4 rounded form-checkbox"
              :style="{borderColor: 'black', backgroundColor: settings.stripe.enableStripe ? 'var(--admin-link-indigo-bg)' : 'white'}"
            />
            <label for="enableStripe" class="ml-2 block text-sm font-medium">
              {{ __('Enable Stripe Payments', 'schedula-smart-appointment-booking') }}
              <p class="text-xs text-secondary">
                {{ __('Integrate Stripe as a payment gateway for your booking system.', 'schedula-smart-appointment-booking') }}
              </p>
            </label>
          </div>

          <div v-if="settings.stripe.enableStripe" class="space-y-6 mt-4 p-4 border rounded-lg" :style="{ backgroundColor: 'var(--admin-card-bg-color)', borderColor: 'var(--admin-input-border-color)' }">
            <div class="space-y-4">
              <p class="text-sm font-semibold mb-2">{{ __('Stripe API Credentials', 'schedula-smart-appointment-booking') }}</p>
              <!-- Stripe Publishable Key -->
              <div>
                <label for="publishableKey" class="block text-sm font-medium">{{ __('Publishable Key', 'schedula-smart-appointment-booking') }}</label>
                <input 
                  id="publishableKey" 
                  v-model="settings.stripe.publishableKey" 
                  type="text" 
                  class="mt-1 block w-full max-w-lg border rounded-md shadow-sm p-2 input-field"
                  :placeholder="__('Your Stripe Publishable Key', 'schedula-smart-appointment-booking')"
                />
                <p class="mt-2 text-xs text-secondary">{{ __('Obtain this from your Stripe Dashboard.', 'schedula-smart-appointment-booking') }}</p>
              </div>

              <!-- Stripe Secret Key -->
              <div>
                <label for="secretKey" class="block text-sm font-medium">{{ __('Secret Key', 'schedula-smart-appointment-booking') }}</label>
                <div class="relative mt-1">
                  <input 
                    id="secretKey" 
                    v-model="settings.stripe.secretKey" 
                    :type="showStripeSecret ? 'text' : 'password'" 
                    class="block w-full max-w-lg border rounded-md shadow-sm p-2 pr-10 input-field"
                    :placeholder="__('Your Stripe Secret Key', 'schedula-smart-appointment-booking')"
                  />
                  <button type="button" @click="showStripeSecret = !showStripeSecret" class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5" :style="{ color: 'var(--admin-input-text-color)' }">
                    <i :class="showStripeSecret ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                  </button>
                </div>
                <p class="mt-2 text-xs text-secondary">{{ __('Keep this confidential. Do not share.', 'schedula-smart-appointment-booking') }}</p>
              </div>

              <!-- Stripe Webhook Secret -->
              <div>
                <label for="webhookSecret" class="block text-sm font-medium">{{ __('Webhook Signing Secret', 'schedula-smart-appointment-booking') }}</label>
                <input 
                  id="webhookSecret" 
                  v-model="settings.stripe.webhookSecret" 
                  type="text" 
                  class="mt-1 block w-full max-w-lg border rounded-md shadow-sm p-2 input-field"
                  :placeholder="__('Your Stripe Webhook Signing Secret', 'schedula-smart-appointment-booking')"
                />
                <p class="mt-2 text-xs text-secondary">{{ __('Create a webhook in your Stripe Dashboard and enter its signing secret here for secure event handling.', 'schedula-smart-appointment-booking') }}</p>
              </div>

              <!-- Sandbox Mode Toggle (Stripe uses test/live keys, but a toggle can be useful for UI) -->
              <div class="grid grid-cols-1 gap-4">
                <label for="stripeSandboxMode" class="block text-sm font-medium">
                  {{ __('Test Mode', 'schedula-smart-appointment-booking') }}
                </label>
                <select 
                  id="stripeSandboxMode" 
                  v-model="settings.stripe.sandboxMode"
                  class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
                >
                  <option :value="true">{{ __('Yes', 'schedula-smart-appointment-booking') }}</option>
                  <option :value="false">{{ __('No', 'schedula-smart-appointment-booking') }}</option>
                </select>
                <p class="mt-2 text-xs text-secondary">
                  {{ __('Use Stripe\'s test mode for testing payments without real money. (Note: This typically depends on your API keys).', 'schedula-smart-appointment-booking') }}
                </p>
              </div>

              <!-- Test Credentials Button -->
              <div class="mt-2">
                <button 
                  @click="testStripeCredentials"
                  :disabled="isTestingStripe"
                  class="inline-flex items-center px-4 py-2 rounded-md shadow-sm text-sm font-medium text-white disabled:cursor-not-allowed"
                  :style="{ backgroundColor: 'var(--admin-button-primary-bg)' }"
                >
                  <svg v-if="isTestingStripe" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <i v-else class="fas fa-vial mr-2"></i>
                  {{ isTestingStripe ? __('Testing...', 'schedula-smart-appointment-booking') : __('Test Credentials', 'schedula-smart-appointment-booking') }}
                </button>
              </div>
            </div>

            <hr class="my-6 border-gray-200 dark:border-gray-700" />

            <h4 class="text-lg font-semibold mb-4">{{ __('Price Correction', 'schedula-smart-appointment-booking') }}</h4>
            <p class="text-xs text-secondary mb-4">{{ __('Adjust the booking price when Stripe is used, e.g., to cover fees or offer discounts.', 'schedula-smart-appointment-booking') }}</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Price Correction Type -->
              <div>
                <label for="stripePriceCorrectionType" class="block text-sm font-medium">{{ __('Correction Type', 'schedula-smart-appointment-booking') }}</label>
                <select 
                  id="stripePriceCorrectionType" 
                  v-model="settings.stripe.priceCorrection.type"
                  class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
                >
                  <option value="none">{{ __('None', 'schedula-smart-appointment-booking') }}</option>
                  <option value="increase_percent">{{ __('Increase (%)', 'schedula-smart-appointment-booking') }}</option>
                  <option value="discount_percent">{{ __('Discount (%)', 'schedula-smart-appointment-booking') }}</option>
                  <option value="addition">{{ __('Add Fixed Amount', 'schedula-smart-appointment-booking') }}</option>
                  <option value="deduction">{{ __('Deduct Fixed Amount', 'schedula-smart-appointment-booking') }}</option>
                </select>
              </div>

              <!-- Price Correction Amount -->
              <div>
                <label for="stripePriceCorrectionAmount" class="block text-sm font-medium">{{ __('Amount', 'schedula-smart-appointment-booking') }}</label>
                <input 
                  id="stripePriceCorrectionAmount" 
                  v-model.number="settings.stripe.priceCorrection.amount" 
                  type="number" 
                  min="0"
                  :step="settings.stripe.priceCorrection.type.includes('percentage') ? 0.01 : 1"
                  class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
                  :placeholder="settings.stripe.priceCorrection.type.includes('percentage') ? __('e.g., 2.9 (for 2.9%)', 'schedula-smart-appointment-booking') : __('e.g., 0.30 (for $0.30)', 'schedula-smart-appointment-booking')"
                />
                <p class="mt-2 text-xs text-secondary" v-if="settings.stripe.priceCorrection.type === 'increase_percent'">
                  {{ __('Add a percentage increase to the base price (e.g., enter 2.9 for a 2.9% fee).', 'schedula-smart-appointment-booking') }}
                </p>
                <p class="mt-2 text-xs text-secondary" v-else-if="settings.stripe.priceCorrection.type === 'discount_percent'">
                  {{ __('Apply a percentage discount to the base price (e.g., enter 5 for a 5% discount).', 'schedula-smart-appointment-booking') }}
                </p>
                <p class="mt-2 text-xs text-secondary" v-else-if="settings.stripe.priceCorrection.type === 'addition'">
                  {{ __('Add a fixed amount to the base price (e.g., enter 0.30 for a $0.30 fee).', 'schedula-smart-appointment-booking') }}
                </p>
                <p class="mt-2 text-xs text-secondary" v-else-if="settings.stripe.priceCorrection.type === 'deduction'">
                  {{ __('Deduct a fixed amount from the base price (e.g., enter 1.50 for a $1.50 discount).', 'schedula-smart-appointment-booking') }}
                </p>
                <p class="mt-2 text-xs text-secondary" v-else>
                  {{ __('Enter the amount for the chosen price correction type.', 'schedula-smart-appointment-booking') }}
                </p>
              </div>
            </div>

            <hr class="my-6 border-gray-200 dark:border-gray-700" />

            <!-- Time Interval for Payment Gateway -->
            <div>
              <label for="stripeTimeIntervalPaymentGateway" class="block text-sm font-medium">
                {{ __('Time to Hold Booking for Payment (minutes)', 'schedula-smart-appointment-booking') }}
              </label>
              <select 
                id="stripeTimeIntervalPaymentGateway" 
                v-model="settings.stripe.timeIntervalPaymentGateway"
                class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
              >
                <option value="0">{{ __('OFF', 'schedula-smart-appointment-booking') }}</option>
                <option value="5">{{ __('5 minutes', 'schedula-smart-appointment-booking') }}</option>
                <option value="10">{{ __('10 minutes', 'schedula-smart-appointment-booking') }}</option>
                <option value="15">{{ __('15 minutes', 'schedula-smart-appointment-booking') }}</option>
                <option value="20">{{ __('20 minutes', 'schedula-smart-appointment-booking') }}</option>
                <option value="30">{{ __('30 minutes', 'schedula-smart-appointment-booking') }}</option>
                <option value="45">{{ __('45 minutes', 'schedula-smart-appointment-booking') }}</option>
                <option value="60">{{ __('60 minutes (1 hour)', 'schedula-smart-appointment-booking') }}</option>
                <option value="120">{{ __('120 minutes (2 hours)', 'schedula-smart-appointment-booking') }}</option>
                <option value="240">{{ __('240 minutes (4 hours)', 'schedula-smart-appointment-booking') }}</option>
                <option value="360">{{ __('360 minutes (6 hours)', 'schedula-smart-appointment-booking') }}</option>
                <option value="720">{{ __('720 minutes (12 hours)', 'schedula-smart-appointment-booking') }}</option>
                <option value="1440">{{ __('1440 minutes (1 day)', 'schedula-smart-appointment-booking') }}</option>
              </select>
              <p class="mt-2 text-xs text-secondary">
                {{ __('If a booking requires payment, this is how long the time slot will be held while the client completes payment. Set to OFF to not hold slots.', 'schedula-smart-appointment-booking') }}
              </p>
            </div>

          </div>
          <div v-else class="mt-4 p-4 text-sm border rounded-lg" :style="{ backgroundColor: 'var(--admin-card-bg-color)', borderColor: 'var(--admin-input-border-color)', color: 'var(--admin-text-color)' }">
            {{ __('Stripe payments are currently disabled. Check the box above to enable them and configure your credentials.', 'schedula-smart-appointment-booking') }}
          </div>
        </div>

        <!-- Working Hours Settings Tab -->
        <div v-else-if="activeTab === 'workingHours'" class="space-y-6">
            <div class="flex items-center mb-4">
                <input
                    id="enableDefaultBusinessSchedule"
                    v-model="settings.general.enableDefaultBusinessSchedule"
                    type="checkbox"
                    class="h-4 w-4 rounded form-checkbox"
                    :style="{borderColor: 'black', backgroundColor: settings.general.enableDefaultBusinessSchedule ? 'var(--admin-link-indigo-bg)' : 'white'}"
                />
                <label for="enableDefaultBusinessSchedule" class="ml-2 block text-sm font-medium">
                    {{ __('Enable Default Business Hours as Staff Template', 'schedula-smart-appointment-booking') }}
                    <p class="text-xs text-secondary">
                        {{ __('If checked, newly added staff members will automatically get this schedule as their initial working hours. Existing staff schedules will not be overwritten.', 'schedula-smart-appointment-booking') }}
                    </p>
                </label>
            </div>
            
            <h3 class="text-xl font-semibold mb-6">{{ __('Default Weekly Business Hours', 'schedula-smart-appointment-booking') }}</h3>

            <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2">
                <div v-for="(daySchedule, index) in settings.general.defaultBusinessSchedule" :key="index" 
                    class="p-4 border rounded-lg shadow-sm bg-white hover:bg-gray-50 transition-colors duration-200 day-schedule-card">
                    <div class="flex items-center justify-between mb-3">
                        <h5 class="text-md font-semibold text-gray-700 flex items-center space-x-2">
                            <div class="w-3 h-3 rounded-full" :class="getDayColor(daySchedule.day_of_week)"></div>
                            <span>{{ getDayName(daySchedule.day_of_week) }}</span>
                        </h5>
                        
                        <!-- Duplicate Options Toggle -->
                        <div class="relative" v-if="daySchedule.start_time || daySchedule.end_time" :ref="el => { if (el) duplicateContainers[index] = el }">
                            <button type="button" @click="toggleDuplicateOptions(index)" 
                                    class="px-3 py-1 rounded-md text-xs transition duration-150 ease-in-out duplicate-button">
                                <i class="fas fa-copy"></i>
                                <span>{{ __('Duplicate', 'schedula-smart-appointment-booking') }}</span>
                            </button>
                            
                            <!-- Duplicate Options Dropdown -->
                            <div v-if="showDuplicateOptions[index]" 
                                class="absolute right-0 top-8 z-10 bg-white border rounded-md shadow-lg p-2 min-w-[150px] dropdown-menu">
                                <p class="text-xs text-secondary mb-2">{{ __('Copy to:', 'schedula-smart-appointment-booking') }}</p>
                                <div class="space-y-1">
                                    <button v-for="(otherDay, otherIndex) in settings.general.defaultBusinessSchedule" :key="otherIndex"
                                            v-if="otherIndex !== index"
                                            type="button" 
                                            @click="duplicateScheduleToDay(index, otherIndex)"
                                            class="w-full text-left px-2 py-1 text-xs hover:bg-gray-100 rounded dropdown-item">
                                        <div class="w-2 h-2 rounded-full" :class="getDayColor(otherDay.day_of_week)"></div>
                                        <span>{{ getDayName(otherDay.day_of_week) }}</span>
                                    </button>
                                    <button type="button" @click="duplicateScheduleToAll(index)" 
                                            class="w-full text-left px-2 py-1 text-xs hover:bg-gray-100 rounded dropdown-item">
                                        <i class="fas fa-sync-alt mr-1"></i>
                                        <span>{{ __('All Days', 'schedula-smart-appointment-booking') }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Time Selection with Select Dropdowns and "Off" Option -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                        <div>
                            <label :for="`start-time-${index}`" class="block text-sm font-medium text-gray-600 mb-1">{{ __('From:', 'schedula-smart-appointment-booking') }}</label>
                            <select :id="`start-time-${index}`" v-model="daySchedule.start_time"
                                    class="block w-full rounded-md border shadow-sm text-sm p-2 input-field">
                                <option value="">{{ __('Off', 'schedula-smart-appointment-booking') }}</option>
                                <option v-for="timeOption in timeOptions" :key="timeOption.value" :value="timeOption.value">
                                    {{ timeOption.label }}
                                </option>
                            </select>
                        </div>
                        
                        <div>
                            <label :for="`end-time-${index}`" class="block text-sm font-medium text-gray-600 mb-1">{{ __('To:', 'schedula-smart-appointment-booking') }}</label>
                            <select :id="`end-time-${index}`" v-model="daySchedule.end_time"
                                    class="block w-full rounded-md border shadow-sm text-sm p-2 input-field">
                                <option value="">{{ __('Off', 'schedula-smart-appointment-booking') }}</option>
                                <option v-for="timeOption in timeOptions" :key="timeOption.value" :value="timeOption.value">
                                    {{ timeOption.label }}
                                </option>
                            </select>
                        </div>
                        
                        <div class="sm:col-span-2 lg:col-span-2 flex items-end">
                            <button type="button" @click="openAddBreakModal(daySchedule)" 
                                    :disabled="!daySchedule.start_time || !daySchedule.end_time"
                                    class="px-3 py-2 text-white rounded-md text-xs transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-1 whitespace-nowrap bg-blue-600 hover:bg-blue-700">
                                <i class="fas fa-coffee text-xs"></i>
                                <span>{{ __('Add Break', 'schedula-smart-appointment-booking') }}</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Breaks Display -->
                    <div v-if="daySchedule.breaks && daySchedule.breaks.length > 0" class="mt-4 border-t pt-3 border-gray-200 dark:border-gray-500">
                        <h6 class="text-sm font-medium text-gray-600 mb-2 flex items-center">
                            <i class="fas fa-pause-circle mr-1"></i>{{ __('Breaks:', 'schedula-smart-appointment-booking') }}
                        </h6>
                        <div class="flex flex-wrap gap-2">
                            <div v-for="(b, bIndex) in daySchedule.breaks" :key="bIndex" 
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800 break-label">
                                <i class="fas fa-coffee mr-1 text-xs"></i>
                                {{ formatTime(b.start_time) }} - {{ formatTime(b.end_time) }} 
                                <span v-if="b.description" class="ml-1">({{ b.description }})</span>
                                <button type="button" @click="removeBreak(daySchedule, bIndex)" 
                                        class="ml-2 -mr-1 h-4 w-4 flex items-center justify-center rounded-full bg-orange-200 text-orange-700 hover:bg-orange-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 break-remove-button">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="daySchedule.start_time && daySchedule.end_time" class="mt-3 text-sm text-secondary flex items-center">
                        <i class="fas fa-info-circle mr-1"></i>{{ __('No breaks configured.', 'schedula-smart-appointment-booking') }}
                    </div>
                     <div v-else class="mt-3 text-sm text-secondary flex items-center">
                        <i class="fas fa-minus-circle mr-1"></i>{{ __('This day is off.', 'schedula-smart-appointment-booking') }}
                    </div>
                </div>
            </div>

             <!-- Enhanced Break Input Modal (reused from EmployeForm) -->
            <transition name="modal-fade">
                <div v-if="showBreakModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
                    @click.self="closeAddBreakModal">
                <div class="rounded-lg shadow-xl w-full max-w-md mx-4 my-8 p-6 relative modal-content">
                    <div class="flex items-center space-x-2 mb-4">
                    <i class="fas fa-coffee text-blue-600"></i>
                    <h4 class="text-lg font-semibold">{{ __('Add Break', 'schedula-smart-appointment-booking') }}</h4>
                    </div>
                    
                    <!-- Break Preview -->
                    <div v-if="currentDayScheduleForBreak" class="mb-4 p-3 rounded-lg border preview-card">
                    <div class="text-sm text-gray-600 mb-2">
                        <i class="fas fa-calendar-day mr-1"></i>{{ getDayName(currentDayScheduleForBreak.day_of_week) }} 
                        ({{ formatTime(currentDayScheduleForBreak.start_time) }} - {{ formatTime(currentDayScheduleForBreak.end_time) }})
                    </div>
                    <div class="text-xs text-secondary">
                        <i class="fas fa-info-circle mr-1"></i>{{ __('Break must be within working hours', 'schedula-smart-appointment-booking') }}
                    </div>
                    </div>

                    <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                        <label for="break-start-time" class="block text-sm font-medium">
                            <i class="fas fa-play text-gray-400 mr-1"></i>{{ __('Start Time', 'schedula-smart-appointment-booking') }}
                        </label>
                        <select id="break-start-time" v-model="newBreak.start_time" required
                                class="block w-full rounded-md border shadow-sm sm:text-sm p-2 input-field">
                            <option value="">{{ __('Select time', 'schedula-smart-appointment-booking') }}</option>
                            <option v-for="timeOption in timeOptions" :key="timeOption.value" :value="timeOption.value">
                            {{ timeOption.label }}
                            </option>
                        </select>
                        </div>
                        <div>
                        <label for="break-end-time" class="block text-sm font-medium">
                            <i class="fas fa-stop text-gray-400 mr-1"></i>{{ __('End Time', 'schedula-smart-appointment-booking') }}
                        </label>
                        <select id="break-end-time" v-model="newBreak.end_time" required
                                class="block w-full rounded-md border shadow-sm sm:text-sm p-2 input-field">
                            <option value="">{{ __('Select time', 'schedula-smart-appointment-booking') }}</option>
                            <option v-for="timeOption in timeOptions" :key="timeOption.value" :value="timeOption.value">
                            {{ timeOption.label }}
                            </option>
                        </select>
                        </div>
                    </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="closeAddBreakModal"
                            class="px-4 py-2 border rounded-md shadow-sm text-sm font-medium bg-gray-600 text-white hover:bg-gray-700">
                        <i class="fas fa-times mr-1"></i>{{ __('Cancel', 'schedula-smart-appointment-booking') }}
                    </button>
                    <button type="button" @click="saveBreak"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-plus mr-1"></i>{{ __('Add Break', 'schedula-smart-appointment-booking') }}
                    </button>
                    </div>
                </div>
                </div>
            </transition>
        </div>

        <!-- Holidays Settings Tab -->
        <div v-else-if="activeTab === 'holidays'" class="space-y-6">
            <div class="flex items-center mb-4">
                <input
                    id="enableDefaultBusinessHolidays"
                    v-model="settings.general.enableDefaultBusinessHolidays"
                    type="checkbox"
                    class="h-4 w-4 rounded form-checkbox"
                    :style="{borderColor: 'black', backgroundColor: settings.general.enableDefaultBusinessHolidays ? 'var(--admin-link-indigo-bg)' : 'white'}"
                />
                <label for="enableDefaultBusinessHolidays" class="ml-2 block text-sm font-medium">
                    {{ __('Enable Default Business Holidays as Staff Template', 'schedula-smart-appointment-booking') }}
                    <p class="text-xs text-secondary">
                        {{ __('If checked, newly added staff members will automatically get these holidays as their initial unavailable periods. Existing staff holidays will not be overwritten.', 'schedula-smart-appointment-booking') }}
                    </p>
                </label>
            </div>

            <h3 class="text-xl font-semibold mb-6">{{ __('Default Business Holidays', 'schedula-smart-appointment-booking') }}</h3>
            <div class="space-y-4">
                <div v-for="(holiday, hIndex) in settings.general.defaultBusinessHolidays" :key="hIndex" 
                    class="p-4 border rounded-lg shadow-sm flex items-center gap-3 hover:bg-gray-50 transition-colors duration-200 day-schedule-card">
                    <i class="fas fa-calendar-times text-yellow-600"></i>
                    <span class="font-medium text-gray-700">{{ formatHolidayDate(holiday) }}</span>
                    <input type="text" v-model="holiday.description" :placeholder="__('Holiday description', 'schedula-smart-appointment-booking')" 
                        class="flex-grow text-sm rounded-md border p-2 input-field" />
                    <button type="button" @click="removeHoliday(hIndex)" 
                            class="text-red-500 hover:text-red-700 p-2 rounded-md hover:bg-red-50 transition-colors duration-200 delete-button">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <button type="button" @click="openAddHolidayModal" 
                        class="px-4 py-2 text-white rounded-md text-sm transition duration-150 ease-in-out flex items-center space-x-2 bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-plus"></i>
                    <span>{{ __('Add Holiday', 'schedula-smart-appointment-booking') }}</span>
                </button>
                <p v-if="!settings.general.defaultBusinessHolidays.length && !isLoading" class="text-gray-500 flex items-center text-secondary">
                    <i class="fas fa-info-circle mr-2"></i>{{ __('No holidays added yet.', 'schedula-smart-appointment-booking') }}
                </p>
            </div>

            <!-- Enhanced Holiday Input Modal with Flowbite Datepicker (reused from EmployeForm) -->
            <transition name="modal-fade">
                <div v-if="showHolidayModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
                    @click.self="closeAddHolidayModal">
                <div class="rounded-lg shadow-xl w-full max-w-md mx-4 my-8 p-6 relative modal-content">
                    <div class="flex items-center space-x-2 mb-4">
                    <i class="fas fa-umbrella-beach text-yellow-600"></i>
                    <h4 class="text-lg font-semibold">{{ __('Add Holiday Period', 'schedula-smart-appointment-booking') }}</h4>
                    </div>
                    <div class="space-y-4">
                    <!-- Flowbite Date Range Picker -->
                    <div ref="holidayDateRangeContainer" class="flex flex-col space-y-2">
                        <label class="block text-sm font-medium mb-1">{{ __('Holiday Period', 'schedula-smart-appointment-booking') }}</label>
                        <div class="flex items-center space-x-2">
                        <div class="relative flex-1">
                            <input name="start" type="text" 
                                class="bg-gray-50 border text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 input-field" 
                                :placeholder="__('Start date', 'schedula-smart-appointment-booking')" readonly>
                        </div>
                        <span class="text-gray-500 text-secondary">{{ __('to', 'schedula-smart-appointment-booking') }}</span>
                        <div class="relative flex-1">
                            <input name="end" type="text" 
                                class="bg-gray-50 border text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 input-field" 
                                :placeholder="__('End date', 'schedula-smart-appointment-booking')" readonly>
                        </div>
                        </div>
                    </div>
                    
                    <div>
                        <label for="holiday-description" class="block text-sm font-medium">
                        <i class="fas fa-comment text-gray-400 mr-1"></i>{{ __('Description (Optional)', 'schedula-smart-appointment-booking') }}
                        </label>
                        <input type="text" id="holiday-description" v-model="newHoliday.description" :placeholder="__('e.g., Summer vacation', 'schedula-smart-appointment-booking')"
                            class="block w-full rounded-md border shadow-sm sm:text-sm p-2 input-field" />
                    </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="closeAddHolidayModal"
                            class="px-4 py-2 border rounded-md shadow-sm text-sm font-medium bg-gray-600 text-white hover:bg-gray-700">
                        <i class="fas fa-times mr-1"></i>{{ __('Cancel', 'schedula-smart-appointment-booking') }}
                    </button>
                    <button type="button" @click="saveHoliday"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-plus mr-1"></i>{{ __('Add Holiday', 'schedula-smart-appointment-booking') }}
                    </button>
                    </div>
                </div>
                </div>
            </transition>
        </div>

        <!-- Appearance Settings Tab -->
        <div v-else-if="activeTab === 'appearance'" class="space-y-6">
          <p class="text-sm text-secondary mb-4">{{ __('Customize the look and feel of your Schedula admin panel.', 'schedula-smart-appointment-booking') }}</p>

          <!-- Font Family Selector -->
          <div class="grid grid-cols-1 gap-4 mb-4">
            <label for="adminFontFamily" class="block text-sm font-medium">
              {{ __('Global Font Family', 'schedula-smart-appointment-booking') }}
            </label>
            <select
              id="adminFontFamily"
              v-model="appearanceSettings.adminFontFamily"
              class="mt-1 block w-full max-w-xs border rounded-md shadow-sm p-2 input-field"
            >
              <option v-for="font in googleFonts" :key="font" :value="font">
                {{ font }}
              </option>
            </select>
            <p class="mt-2 text-xs text-secondary">{{ __('Select the primary font for the admin interface.', 'schedula-smart-appointment-booking') }}</p>
          </div>

          <!-- Header Color Pickers -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="headerBackgroundColor" class="block text-sm font-medium">{{ __('Header Background Color', 'schedula-smart-appointment-booking') }}</label>
              <input type="color" id="headerBackgroundColor" v-model="appearanceSettings.headerBackgroundColor" class="mt-1 h-10 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
              <label for="headerTextColor" class="block text-sm font-medium">{{ __('Header Text Color', 'schedula-smart-appointment-booking') }}</label>
              <input type="color" id="headerTextColor" v-model="appearanceSettings.headerTextColor" class="mt-1 h-10 w-full rounded-md border-gray-300 shadow-sm">
            </div>
            
          </div>
        </div>

        <!-- Analytics Settings Tab -->
        <div v-else-if="activeTab === 'analytics'">
          <AnalyticsSettings ref="analyticsSettingsComponent" />
        </div>

        <!-- Save Button, Reset Button and Status (applies to all tabs) -->
        <div class="mt-8 pt-4 border-t border-gray-200 dark:border-gray-600 flex justify-end items-center">
          <button 
            @click="resetCurrentTabSettings"
            :disabled="isSaving || isResetting"
            class="inline-flex items-center px-4 py-2 rounded-md shadow-sm text-sm font-medium text-white disabled:bg-gray-400 disabled:cursor-not-allowed mr-2 bg-green-600 hover:bg-green-700"
          >
            <svg v-if="isResetting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <i v-else class="fas fa-redo-alt mr-2"></i>
            {{ isResetting ? __('Resetting...', 'schedula-smart-appointment-booking') : __('Reset', 'schedula-smart-appointment-booking') }}
          </button>
          <button 
            @click="saveSettings"
            :disabled="isSaving"
            class="px-6 py-2 rounded-lg shadow-sm text-sm font-medium text-white disabled:bg-blue-400 disabled:cursor-not-allowed bg-blue-600 hover:bg-blue-700 transition-colors duration-200 flex items-center justify-center min-w-[120px]"
          >
            <svg v-if="isSaving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <i v-else class="fas fa-save mr-2"></i>{{ isSaving ? __('Saving...', 'schedula-smart-appointment-booking') : __('Save Settings', 'schedula-smart-appointment-booking') }}
          </button>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick, watch, onUnmounted } from 'vue';
import { settingsApi, utilityApi, stripeApi } from '@/admin/api.js'; // Import stripeApi
import { useGlobalSettings } from '@/admin/composables/useGlobalSettings.js';
import { useAdminAppearance } from '@/admin/composables/useAdminAppearance.js'; 
import { DateRangePicker } from 'flowbite-datepicker';
import AnalyticsSettings from '@/admin/components/settings/AnalyticsSettings.vue';
import BasePhoneInput from '@/admin/components/common/BasePhoneInput.vue';
import { useToast } from '@/admin/composables/useToast.js'; // Import useToast
import { __ } from '@wordpress/i18n';

const { fetchGlobalSettings, formatTime } = useGlobalSettings();
const analyticsSettingsComponent = ref(null);
const activeTab = ref('general');
const isLoading = ref(false);
const isSaving = ref(false);
const isResetting = ref(false);
const isTestingStripe = ref(false); // For Stripe test button

// Reactive object to hold frontend validation error messages
const validationErrors = reactive({
  companyName: '',
  email: '',
});

const showClientSecret = ref(false); // NEW: For Client Secret visibility toggle
const showStripeSecret = ref(false); // NEW: For Stripe Secret visibility toggle

// --- Use the useAdminAppearance composable ---
const { 
  appearanceSettings, 
  adminCustomStyles, 
  fetchAppearanceSettings: fetchAdminAppearanceSettings, 
  saveAppearanceSettings: saveAdminAppearanceSettings, 
  resetAppearanceSettings: resetAdminAppearanceSettings 
} = useAdminAppearance();

// --- Use the toast composable ---
const { success, error: toastError, info } = useToast(); 


// List of supported Google Fonts
const googleFonts = ref([
  'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Oswald',
  'Source Sans Pro', 'Poppins', 'Noto Sans', 'Raleway', 'Ubuntu',
  'Merriweather', 'Playfair Display', 'Lora', 'Exo 2', 'PT Sans',
  'Fira Sans', 'Work Sans', 'Nunito', 'Rubik',
]);


// --- Initial default states for each settings section ---
const initialGeneralSettings = {
  timeFormat: '24h',
  timeSlotLength: 30,
  minTimeBooking: 60,
  minTimeCanceling: 1440,
  bookingBufferTime: 0, 
  daysAvailableBooking: 365,
  displayTimezone: true,
  deleteDataOnUninstall: 'dont_delete',
  businessTimezone: 'Africa/Lagos',
  followAdminTimezone: false, // NEW: Follow admin's current timezone
  currencyCode: 'USD',
  enableLocalPayment: true,
  instantBookingEnabled: false,
  enableGroupBooking: false,
  maxPersonsPerBooking: 1,
  groupBookingPriceLogic: { // Default for group booking price logic
    type: 'per_person_multiply',
    amount: 0,
  },
  enableRecurringAppointments: false, // Add recurring appointments toggle
  recurrence: { // Default recurrence settings (removed type)
    maxRecurrences: 0, // Changed default to 0 for no limit
    paymentBehavior: 'charge_all',
  },
  enableDefaultBusinessSchedule: true, // Changed to true
  defaultBusinessSchedule: Array.from({ length: 7 }, (_, i) => ({ 
    day_of_week: i,
    start_time: '',
    end_time: '',
    breaks: [],
  })),
  enableDefaultBusinessHolidays: true, // Changed to true
  defaultBusinessHolidays: [], 
};

const initialCompanySettings = {
  companyName: '',
  address: '',
  phone: '',
  website: '',
  industry: '',
  servicesOffered: '',
  companySize: '',
  email: '',
  companyLogoUrl: '',
};


// Initial default state for Stripe settings
const initialStripeSettings = {
  enableStripe: false,
  publishableKey: '',
  secretKey: '',
  webhookSecret: '',
  sandboxMode: true,
  priceCorrection: {
    type: 'none',
    amount: 0,
  },
  timeIntervalPaymentGateway: 0,
};


// Initialize settings structure with default values for each tab (appearance handled by composable)
const settings = reactive({
  general: { ...initialGeneralSettings },
  company: { ...initialCompanySettings },
  stripe: { ...initialStripeSettings }, // Add Stripe settings
});

// Reactive object to hold dynamically fetched utility options
const utilityOptions = reactive({
  timezones: [],
  companySizes: [],
  industries: [],
  currencies: [],
});

// --- Searchable Currency Dropdown Logic ---
const currencySearchQuery = ref('');
const showCurrencyResults = ref(false);
let currencyBlurTimeout = null;

const filteredCurrencies = computed(() => {
  if (!currencySearchQuery.value) {
    return utilityOptions.currencies;
  }
  const query = currencySearchQuery.value.toLowerCase();
  return utilityOptions.currencies.filter(c =>
    c.name.toLowerCase().includes(query) ||
    c.code.toLowerCase().includes(query) ||
    (c.symbol && c.symbol.toLowerCase().includes(query))
  );
});

const selectedCurrencyName = computed(() => {
    const currency = utilityOptions.currencies.find(c => c.code === settings.general.currencyCode);
    return currency ? `${currency.name} (${currency.symbol}) - ${currency.code}` : '';
});

const selectCurrency = (currency) => {
  settings.general.currencyCode = currency.code;
  currencySearchQuery.value = ''; // Clear search query after selection
  showCurrencyResults.value = false;
};

const hideCurrencyResultsDelayed = () => {
  currencyBlurTimeout = setTimeout(() => {
    showCurrencyResults.value = false;
  }, 150); // Delay to allow click on results to register
};

onUnmounted(() => {
    if (currencyBlurTimeout) clearTimeout(currencyBlurTimeout);
    if (timezoneBlurTimeout) clearTimeout(timezoneBlurTimeout);
});
// --- Searchable Timezone Dropdown Logic ---
const timezoneSearchQuery = ref('');
const showTimezoneResults = ref(false);

// Timezone detection
const detectedTimezone = ref('');
const currentTime = ref('');
const isDetectingTimezone = ref(false);
let timezoneBlurTimeout = null;

const filteredTimezones = computed(() => {
  if (!timezoneSearchQuery.value) {
    return utilityOptions.timezones;
  }
  const query = timezoneSearchQuery.value.toLowerCase();
  return utilityOptions.timezones.filter(tz =>
    tz.toLowerCase().includes(query)
  );
});

const selectedTimezoneName = computed(() => {
    const timezone = utilityOptions.timezones.find(tz => tz === settings.general.businessTimezone);
    return timezone ? timezone : '';
});

const selectTimezone = (timezone) => {
  settings.general.businessTimezone = timezone;
  timezoneSearchQuery.value = ''; // Clear search query after selection
  showTimezoneResults.value = false;
};

const hideTimezoneResultsDelayed = () => {
  timezoneBlurTimeout = setTimeout(() => {
    showTimezoneResults.value = false;
  }, 150); // Delay to allow click on results to register
};

// Timezone detection methods
const detectBrowserTimezone = () => {
  try {
    // Get browser timezone
    const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    return browserTimezone;
  } catch (error) {
    console.warn('Browser timezone detection failed:', error);
    return null;
  }
};

const detectTimezoneFromIP = async () => {
  try {
    // Use ipapi.co (free tier: 1000 requests/month)
    const response = await fetch('https://ipapi.co/json/');
    const data = await response.json();
    
    if (data.timezone) {
      return data.timezone;
    }
    return null;
  } catch (error) {
    console.warn('IP timezone detection failed:', error);
    return null;
  }
};

const updateCurrentTime = () => {
  const now = new Date();
  const timezone = detectedTimezone.value || 'UTC';
  
  try {
    const formatter = new Intl.DateTimeFormat('en-US', {
      timeZone: timezone,
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: false
    });
    
    currentTime.value = formatter.format(now);
  } catch (error) {
    currentTime.value = now.toLocaleString();
  }
};

const detectAndUpdateTimezone = async () => {
  isDetectingTimezone.value = true;
  
  try {
    // Try browser detection first (instant)
    let timezone = detectBrowserTimezone();
    
    // If browser detection fails, try IP detection
    if (!timezone) {
      timezone = await detectTimezoneFromIP();
    }
    
    if (timezone) {
      detectedTimezone.value = timezone;
      updateCurrentTime();
      
      // Update WordPress timezone via API
      try {
        const response = await fetch(`${window.schedulaApi.apiUrl}/schesab/v1/update-wordpress-timezone`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.schedulaApi.nonce
          },
          body: JSON.stringify({ timezone: timezone })
        });
        
        const result = await response.json();
        
        if (result.success) {
          alert(`${__('WordPress timezone updated successfully to:', 'schedula-smart-appointment-booking')} ${timezone}`);
        } else {
          alert(`${__('Timezone detected:', 'schedula-smart-appointment-booking')} ${timezone}\n${__('Please update your WordPress timezone in Settings > General', 'schedula-smart-appointment-booking')}`);
        }
      } catch (apiError) {
        console.warn('API update failed, showing manual instruction:', apiError);
        alert(`${__('Timezone detected:', 'schedula-smart-appointment-booking')} ${timezone}\n${__('Please update your WordPress timezone in Settings > General', 'schedula-smart-appointment-booking')}`);
      }
    } else {
      alert(__('Unable to detect timezone automatically. Please set it manually.', 'schedula-smart-appointment-booking'));
    }
  } catch (error) {
    console.error('Timezone detection error:', error);
    alert(__('Error detecting timezone. Please set it manually.', 'schedula-smart-appointment-booking'));
  } finally {
    isDetectingTimezone.value = false;
  }
};

// Auto-detect on component mount if followAdminTimezone is enabled
onMounted(() => {
  if (settings.general.followAdminTimezone) {
    const browserTz = detectBrowserTimezone();
    if (browserTz) {
      detectedTimezone.value = browserTz;
      updateCurrentTime();
    }
  }
});

// --- End Searchable Timezone Dropdown Logic ---
// --- End Searchable Currency Dropdown Logic ---

// Computed options for General Settings (Time Slot, Booking, Canceling)
// All values are strings to handle v-model quirks with 0.
const timeSlotLengthOptions = computed(() => [
  { value: '15', label: __('15 minutes', 'schedula-smart-appointment-booking') },
  { value: '30', label: __('30 minutes', 'schedula-smart-appointment-booking') },
  { value: '45', label: __('45 minutes', 'schedula-smart-appointment-booking') },
  { value: '60', label: __('60 minutes (1 hour)', 'schedula-smart-appointment-booking') },
]);

const minTimeBookingOptions = computed(() => [
  { value: '0', label: __('No minimum', 'schedula-smart-appointment-booking') },
  { value: '30', label: __('30 minutes', 'schedula-smart-appointment-booking') },
  { value: '60', label: __('1 hour', 'schedula-smart-appointment-booking') },
  { value: '120', label: __('2 hours', 'schedula-smart-appointment-booking') },
  { value: '240', label: __('4 hours', 'schedula-smart-appointment-booking') },
  { value: '720', label: __('12 hours', 'schedula-smart-appointment-booking') },
  { value: '1440', label: __('1 day', 'schedula-smart-appointment-booking') },
  { value: '2880', label: __('2 days', 'schedula-smart-appointment-booking') },
]);

const minTimeCancelingOptions = computed(() => [
  { value: '0', label: __('No minimum', 'schedula-smart-appointment-booking') },
  { value: '30', label: __('30 minutes', 'schedula-smart-appointment-booking') },
  { value: '60', label: __('1 hour', 'schedula-smart-appointment-booking') },
  { value: '120', label: __('2 hours', 'schedula-smart-appointment-booking') },
  { value: '240', label: __('4 hours', 'schedula-smart-appointment-booking') },
  { value: '720', label: __('12 hours', 'schedula-smart-appointment-booking') },
  { value: '1440', label: __('1 day', 'schedula-smart-appointment-booking') },
  { value: '2880', label: __('2 days', 'schedula-smart-appointment-booking') },
]);

const bookingBufferTimeOptions = computed(() => [
  { value: '0', label: __('No buffer', 'schedula-smart-appointment-booking') },
  { value: '15', label: __('15 minutes', 'schedula-smart-appointment-booking') },
  { value: '30', label: __('30 minutes', 'schedula-smart-appointment-booking') },
  { value: '45', label: __('45 minutes', 'schedula-smart-appointment-booking') },
  { value: '60', label: __('1 hour', 'schedula-smart-appointment-booking') },
  { value: '120', label: __('2 hours', 'schedula-smart-appointment-booking') },
]);

const timeFormatOptions = computed(() => [
  { value: '12h', label: __('12-hour (AM/PM)', 'schedula-smart-appointment-booking') },
  { value: '24h', label: __('24-hour', 'schedula-smart-appointment-booking') },
]);

// Computed property to group industries for <optgroup> in the select
const groupedIndustries = computed(() => {
  const groups = {};
  utilityOptions.industries.forEach(industry => {
    const groupName = industry.group || 'Other';
    if (!groups[groupName]) {
      groups[groupName] = { name: groupName, options: [] };
    }
    groups[groupName].options.push(industry);
  });
  return Object.values(groups);
});


const currentTabTitle = computed(() => {
  switch (activeTab.value) {
    case 'general':
      return __('General Settings', 'schedula-smart-appointment-booking');
    case 'company':
      return __('Company Settings', 'schedula-smart-appointment-booking');
    case 'appointment':
      return __('Appointment Settings', 'schedula-smart-appointment-booking');
    case 'payments': 
      return __('Payments Settings', 'schedula-smart-appointment-booking');
    case 'workingHours':
      return __('Working Hours Settings', 'schedula-smart-appointment-booking');
    case 'holidays':
      return __('Holidays Settings', 'schedula-smart-appointment-booking');
    case 'appearance':
      return __('Admin Appearance Settings', 'schedula-smart-appointment-booking');
    case 'analytics':
      return __('Analytics Settings', 'schedula-smart-appointment-booking');
    default:
      return __('Settings', 'schedula-smart-appointment-booking');
  }
});

/**
 * Handles image error for company logo preview.
 */
const handleImageError = (event) => {
  event.target.src = window.schedulaData.pluginUrl + 'assets/images/placeholders/no-image-placeholder.png';
  event.target.alt = __('Logo could not be loaded', 'schedula-smart-appointment-booking');
  toastError(__('Company logo could not be loaded.', 'schedula-smart-appointment-booking')); // Toast for image error
};

/**
 * Performs client-side validation for the Company settings tab.
 * @returns {boolean} True if all required fields are valid, false otherwise.
 */
const validateCompanySettings = () => {
  let isValid = true;
  // Clear previous errors
  Object.keys(validationErrors).forEach(key => validationErrors[key] = ''); 

  if (!settings.company.companyName.trim()) {
    validationErrors.companyName = __('Please enter the company name.', 'schedula-smart-appointment-booking');
    isValid = false;
  }
  if (!settings.company.email.trim()) {
    validationErrors.email = __('Please enter the contact email address.', 'schedula-smart-appointment-booking');
    isValid = false;
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(settings.company.email)) {
    // Basic email format validation
    validationErrors.email = __('Please enter a valid email address.', 'schedula-smart-appointment-booking');
    isValid = false;
  }
  return isValid;
};

// --- WordPress Media Uploader Logic ---
let mediaUploader = null;

const openMediaUploader = () => {
  // Ensure wp.media exists
  if (typeof wp === 'undefined' || !wp.media) {
    toastError(__('WordPress media uploader is not available. This feature requires WordPress environment.', 'schedula-smart-appointment-booking'));
    console.error(__('WordPress media uploader (wp.media) not found. This feature requires WordPress environment.', 'schedula-smart-appointment-booking'));
    return;
  }

  // If the uploader already exists, open it
  if (mediaUploader) {
    mediaUploader.open();
    return;
  }

  // Create the media frame.
  mediaUploader = wp.media({
    title: __('Select Company Logo', 'schedula-smart-appointment-booking'),
    button: {
      text: __('Use this image', 'schedula-smart-appointment-booking'),
    },
    multiple: false, // Set to true to allow multiple files to be selected
  });

  // When an image is selected, run a callback.
  mediaUploader.on('select', function() {
    const attachment = mediaUploader.state().get('selection').first().toJSON();
    // Set the logo URL to the selected attachment's URL
    settings.company.companyLogoUrl = attachment.url;
    success(__('Company logo selected successfully.', 'schedula-smart-appointment-booking'));
  });

  // Open the media dialog
  mediaUploader.open();
};

const removeCompanyLogo = () => {
  settings.company.companyLogoUrl = '';
  success(__('Company logo removed.', 'schedula-smart-appointment-booking'));
};

// Cleanup media uploader on component unmount
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


const fetchSettings = async () => {
  isLoading.value = true;
  try {
    const generalResponse = await settingsApi.getGeneralSettings();
    // When assigning, ensure that integer 0 values from backend are treated as strings for select binding
    // This is a common workaround for v-model quirks with 0 in selects
    const fetchedGeneralSettings = { ...initialGeneralSettings, ...generalResponse.data };
    fetchedGeneralSettings.minTimeBooking = String(fetchedGeneralSettings.minTimeBooking);
    fetchedGeneralSettings.minTimeCanceling = String(fetchedGeneralSettings.minTimeCanceling);
    fetchedGeneralSettings.bookingBufferTime = String(fetchedGeneralSettings.bookingBufferTime);
    fetchedGeneralSettings.timeSlotLength = String(fetchedGeneralSettings.timeSlotLength);

    // Ensure groupBookingPriceLogic is correctly initialized and its amount is a number
    if (fetchedGeneralSettings.groupBookingPriceLogic && typeof fetchedGeneralSettings.groupBookingPriceLogic.amount === 'string') {
        fetchedGeneralSettings.groupBookingPriceLogic.amount = Number(fetchedGeneralSettings.groupBookingPriceLogic.amount);
    } else if (!fetchedGeneralSettings.groupBookingPriceLogic) {
        fetchedGeneralSettings.groupBookingPriceLogic = { ...initialGeneralSettings.groupBookingPriceLogic };
    }

    // Ensure recurrence settings are correctly initialized
    if (fetchedGeneralSettings.recurrence && typeof fetchedGeneralSettings.recurrence.maxRecurrences === 'string') {
        fetchedGeneralSettings.recurrence.maxRecurrences = Number(fetchedGeneralSettings.recurrence.maxRecurrences);
    } else if (!fetchedGeneralSettings.recurrence) {
        fetchedGeneralSettings.recurrence = { ...initialGeneralSettings.recurrence };
    }

    // Ensure paymentBehavior for recurrence is never null to prevent errors
    if (fetchedGeneralSettings.recurrence && !fetchedGeneralSettings.recurrence.paymentBehavior) {
        fetchedGeneralSettings.recurrence.paymentBehavior = 'charge_all';
    }


    Object.assign(settings.general, fetchedGeneralSettings);


    // Ensure defaultBusinessSchedule has 7 days, filling in missing ones
    const fetchedSchedule = generalResponse.data.defaultBusinessSchedule || [];
    const newSchedule = Array.from({ length: 7 }, (_, i) => {
        const existingDay = fetchedSchedule.find(item => Number(item.day_of_week) === i);
        if (existingDay) {
            return {
                day_of_week: Number(existingDay.day_of_week),
                start_time: existingDay.start_time ? existingDay.start_time.slice(0, 5) : '',
                end_time: existingDay.end_time ? existingDay.end_time.slice(0, 5) : '',
                breaks: existingDay.breaks || [],
            };
        }
        return { day_of_week: i, start_time: '', end_time: '', breaks: [] };
    });
    settings.general.defaultBusinessSchedule = newSchedule;

    // Ensure defaultBusinessHolidays is an array
    settings.general.defaultBusinessHolidays = generalResponse.data.defaultBusinessHolidays || [];


    const companyResponse = await settingsApi.getCompanySettings();
    Object.assign(settings.company, companyResponse.data);

   

    // Fetch Stripe settings
    const stripeResponse = await stripeApi.getStripeSettings();
    const fetchedStripeSettings = { ...initialStripeSettings, ...stripeResponse.data };
    if (fetchedStripeSettings.priceCorrection) {
        fetchedStripeSettings.priceCorrection.amount = Number(fetchedStripeSettings.priceCorrection.amount);
    }
    fetchedStripeSettings.timeIntervalPaymentGateway = Number(fetchedStripeSettings.timeIntervalPaymentGateway);
    Object.assign(settings.stripe, fetchedStripeSettings);


    // Fetch appearance settings using the composable
    await fetchAdminAppearanceSettings();

    const utilityResponse = await utilityApi.getUtilityData();
    utilityOptions.timezones = utilityResponse.data.timezones;
    utilityOptions.companySizes = utilityResponse.data.company_sizes;
    utilityOptions.industries = utilityResponse.data.industries;
    utilityOptions.currencies = utilityResponse.data.currencies;

  } catch (error) {
    console.error('Error fetching settings or utility data:', error);
    toastError('Failed to load settings or dynamic options.');
  } finally {
    isLoading.value = false;
  }
};

const saveSettings = async () => {
  isSaving.value = true;
  
  // Clear any previous validation errors from the frontend
  Object.keys(validationErrors).forEach(key => validationErrors[key] = '');

  try {
    if (activeTab.value === 'general' || activeTab.value === 'workingHours' || activeTab.value === 'holidays' || activeTab.value === 'appointment') {
      // General, Working Hours, Holidays, and Appointment settings are saved via the same API endpoint
      
      // Client-side validation for defaultBusinessSchedule
      for (const day of settings.general.defaultBusinessSchedule) {
        if ((day.start_time && !day.end_time) || (!day.start_time && day.end_time)) {
            toastError(__('Please provide both start and end times for ${getDayName(day.day_of_week)} or leave both empty.', 'schedula-smart-appointment-booking'));
            isLoading.value = false;
            return;
        }
        if (day.start_time && day.end_time && day.start_time >= day.end_time) {
            toastError(__('For ${getDayName(day.day_of_week)}, start time must be before end time.', 'schedula-smart-appointment-booking'));
            isLoading.value = false;
            return;
        }
        for (const b of day.breaks) {
            if (!b.start_time || !b.end_time) {
                toastError(__('Please provide both start and end times for all breaks on ${getDayName(day.day_of_week)}.', 'schedula-smart-appointment-booking'));
                isLoading.value = false;
                return;
            }
            if (b.start_time >= b.end_time) {
                toastError(__('For ${getDayName(day.day_of_week)}, a break\'s start time must be before its end time.', 'schedula-smart-appointment-booking'));
                isLoading.value = false;
                return;
            }
            if (day.start_time && day.end_time) {
                const dayStartTime = new Date(`2000-01-01T${day.start_time}`);
                const dayEndTime = new Date(`2000-01-01T${day.end_time}`);
                const breakStartTime = new Date(`2000-01-01T${b.start_time}`);
                const breakEndTime = new Date(`2000-01-01T${b.end_time}`);

                if (breakStartTime < dayStartTime || breakEndTime > dayEndTime) {
                    toastError(__('For ${getDayName(day.day_of_week)}, a break must be within the day\'s working hours (${day.start_time}-${day.end_time}).', 'schedula-smart-appointment-booking'));
                    isLoading.value = false;
                    return;
                }
            }
        }
      }

      // Client-side validation for defaultBusinessHolidays
      for (const holiday of settings.general.defaultBusinessHolidays) {
          if (!holiday.start_date || !holiday.end_date) {
              toastError(__('Please provide both start and end dates for all holidays.', 'schedula-smart-appointment-booking'));
              isLoading.value = false;
              return;
          }
          const startDate = new Date(holiday.start_date + 'T00:00:00');
          const endDate = new Date(holiday.end_date + 'T00:00:00');
          if (startDate > endDate) {
              toastError(__('Holiday end date must be after or on the start date.', 'schedula-smart-appointment-booking'));
              isLoading.value = false;
              return;
          }
      }

      // Prepare data for saving: convert '0' strings back to numbers for backend
      const dataToSave = { ...settings.general };
      dataToSave.minTimeBooking = Number(dataToSave.minTimeBooking);
      dataToSave.minTimeCanceling = Number(dataToSave.minTimeCanceling);
      dataToSave.bookingBufferTime = Number(dataToSave.bookingBufferTime);
      dataToSave.timeSlotLength = Number(dataToSave.timeSlotLength);
      // Ensure groupBookingPriceLogic.amount is a number when saving
      if (dataToSave.groupBookingPriceLogic) {
          dataToSave.groupBookingPriceLogic = { ...settings.general.groupBookingPriceLogic };
          dataToSave.groupBookingPriceLogic.amount = Number(dataToSave.groupBookingPriceLogic.amount);
      }
      // Ensure recurrence.maxRecurrences is a number when saving
      if (dataToSave.recurrence) {
          dataToSave.recurrence = { ...settings.general.recurrence };
          dataToSave.recurrence.maxRecurrences = Number(dataToSave.recurrence.maxRecurrences);
      }


      await settingsApi.saveGeneralSettings(dataToSave); // Send converted data
      // After saving, re-fetch global settings to ensure any other components are updated
      fetchGlobalSettings(true); 
    } else if (activeTab.value === 'company') {
      // Perform frontend validation specifically for company settings
      if (!validateCompanySettings()) {
        isLoading.value = false;
        toastError(__('Please correct the errors in the form.', 'schedula-smart-appointment-booking')); // Use toast for validation error
        return; // Stop if validation fails
      }
      await settingsApi.saveCompanySettings(settings.company);
    } else if (activeTab.value === 'payments') {

      // When saving payment settings, we need to save both the general settings part (currency)
      // and the specific PayPal settings. It's better to send the whole general settings object.
      const dataToSave = { ...settings.general };
      dataToSave.minTimeBooking = Number(dataToSave.minTimeBooking);
      dataToSave.minTimeCanceling = Number(dataToSave.minTimeCanceling);
      dataToSave.bookingBufferTime = Number(dataToSave.bookingBufferTime);
      dataToSave.timeSlotLength = Number(dataToSave.timeSlotLength);
      // Ensure groupBookingPriceLogic.amount is a number when saving (if it exists)
      if (dataToSave.groupBookingPriceLogic) {
          dataToSave.groupBookingPriceLogic = { ...settings.general.groupBookingPriceLogic };
          dataToSave.groupBookingPriceLogic.amount = Number(dataToSave.groupBookingPriceLogic.amount);
      }
      // Ensure recurrence.maxRecurrences is a number when saving (if it exists)
      if (dataToSave.recurrence) {
          dataToSave.recurrence = { ...settings.general.recurrence };
          dataToSave.recurrence.maxRecurrences = Number(dataToSave.recurrence.maxRecurrences);
      }

      await settingsApi.saveGeneralSettings(dataToSave);

      // Save Stripe settings
      if (settings.stripe.enableStripe) {
        if (!settings.stripe.publishableKey || !settings.stripe.secretKey) {
          toastError(__('When Stripe is enabled, Publishable Key and Secret Key are required.', 'schedula-smart-appointment-booking'));
          isLoading.value = false;
          return;
        }
      }
      await stripeApi.updateStripeSettings(settings.stripe);
      // After saving, re-fetch global settings to ensure any other components are updated
      fetchGlobalSettings(true);
    } else if (activeTab.value === 'appearance') { // Save appearance settings using the composable
      const result = await saveAdminAppearanceSettings(); // Call the composable's save method
      if (!result.success) {
        toastError( result.message);
        isLoading.value = false;
        return;
      }
    }
    
    success(__('Settings saved successfully!', 'schedula-smart-appointment-booking'));
  } catch (error) {
    console.error('Error saving settings:', error);
    // Attempt to parse validation errors from the backend for more specific messages
    if (error.response && error.response.data && error.response.data.message) {
      toastError(error.response.data.message);
    } else {
      toastError(__('Failed to save settings.', 'schedula-smart-appointment-booking'));
    }
  } finally {
    isSaving.value = false;
  }
};

/**
 * Resets all input fields in the currently active settings section to their initial default states.
 */
const resetCurrentTabSettings = async () => {
  isResetting.value = true;
  let resetMessage = '';

  // Reset settings based on the active tab
  if (activeTab.value === 'general') {
    Object.assign(settings.general, JSON.parse(JSON.stringify(initialGeneralSettings)));
    // Ensure that integer 0 values are converted to string '0' for select binding on reset
    settings.general.minTimeBooking = String(settings.general.minTimeBooking);
    settings.general.minTimeCanceling = String(settings.general.minTimeCanceling);
    settings.general.bookingBufferTime = String(settings.general.bookingBufferTime);
    settings.general.timeSlotLength = String(settings.general.timeSlotLength);
    // Ensure groupBookingPriceLogic.amount is a number after reset
    if (settings.general.groupBookingPriceLogic) {
        settings.general.groupBookingPriceLogic.amount = Number(settings.general.groupBookingPriceLogic.amount);
    }
    // Ensure recurrence.maxRecurrences is a number after reset
    if (settings.general.recurrence) {
        settings.general.recurrence.maxRecurrences = Number(settings.general.recurrence.maxRecurrences);
    }


    resetMessage = __('General settings have been reset.', 'schedula-smart-appointment-booking');
  } else if (activeTab.value === 'appointment') {
    settings.general.enableGroupBooking = initialGeneralSettings.enableGroupBooking;
    settings.general.maxPersonsPerBooking = initialGeneralSettings.maxPersonsPerBooking;
    settings.general.groupBookingPriceLogic = JSON.parse(JSON.stringify(initialGeneralSettings.groupBookingPriceLogic));
    settings.general.enableRecurringAppointments = initialGeneralSettings.enableRecurringAppointments; // Reset recurring appointments
    settings.general.recurrence = JSON.parse(JSON.stringify(initialGeneralSettings.recurrence)); // Reset recurrence settings
    resetMessage = __('Appointment settings have been reset.', 'schedula-smart-appointment-booking');
  } else if (activeTab.value === 'company') {
    Object.assign(settings.company, initialCompanySettings);
    Object.keys(validationErrors).forEach(key => validationErrors[key] = '');
    resetMessage = __('Company settings have been reset.', 'schedula-smart-appointment-booking');
  } else if (activeTab.value === 'payments') {
    // Reset general payment settings
    settings.general.currencyCode = initialGeneralSettings.currencyCode;
    settings.general.enableLocalPayment = initialGeneralSettings.enableLocalPayment;
    // Reset Stripe settings
    Object.assign(settings.stripe, JSON.parse(JSON.stringify(initialStripeSettings)));
    resetMessage = __('Payment settings have been reset.', 'schedula-smart-appointment-booking');
  } else if (activeTab.value === 'workingHours') {
    settings.general.enableDefaultBusinessSchedule = initialGeneralSettings.enableDefaultBusinessSchedule;
    settings.general.defaultBusinessSchedule = JSON.parse(JSON.stringify(initialGeneralSettings.defaultBusinessSchedule));
    resetMessage = __('Working hours settings have been reset.', 'schedula-smart-appointment-booking');
  } else if (activeTab.value === 'holidays') {
    settings.general.enableDefaultBusinessHolidays = initialGeneralSettings.enableDefaultBusinessHolidays;
    settings.general.defaultBusinessHolidays = JSON.parse(JSON.stringify(initialGeneralSettings.defaultBusinessHolidays));
    resetMessage = __('Holidays settings have been reset.', 'schedula-smart-appointment-booking');
  } else if (activeTab.value === 'appearance') {
    const result = await resetAdminAppearanceSettings();
    resetMessage = result.message;
  } else if (activeTab.value === 'analytics') {
    if (analyticsSettingsComponent.value) {
      analyticsSettingsComponent.value.reset();
    }
    resetMessage = __('Analytics settings have been reset.', 'schedula-smart-appointment-booking');
  }

  // Use a short delay to make the loader visible
  await new Promise(r => setTimeout(r, 200));
  isResetting.value = false;

  success(resetMessage); // Use toast for reset success
};


const testStripeCredentials = async () => {
  if (!settings.stripe.publishableKey || !settings.stripe.secretKey) {
    toastError( __('Please enter both Publishable Key and Secret Key before testing.', 'schedula-smart-appointment-booking'));
    return;
  }

  isTestingStripe.value = true;
  try {
    const credentials = {
      publishableKey: settings.stripe.publishableKey,
      secretKey: settings.stripe.secretKey,
      sandboxMode: settings.stripe.sandboxMode,
    };
    const response = await stripeApi.testStripeCredentials(credentials);
    success(response.data.message); // Display success message from backend
  } catch (error) {
    console.error('Error testing Stripe credentials:', error);
    if (error.response && error.response.data && error.response.data.message) {
      toastError(error.response.data.message); // Display error message from backend
    } else {
      toastError(__('An unknown error occurred during the test.', 'schedula-smart-appointment-booking'));
    }
  } finally {
    isTestingStripe.value = false;
  }
};

// --- Working Hours & Holidays tab logic (reused from EmployeForm.vue) ---
const timeOptions = ref([]); 
const showDuplicateOptions = ref({});
const duplicateContainers = ref([]);
const daysOfWeek = [__('Sunday', 'schedula-smart-appointment-booking'), __('Monday', 'schedula-smart-appointment-booking'),
 __('Tuesday', 'schedula-smart-appointment-booking'), __('Wednesday', 'schedula-smart-appointment-booking'),
 __('Thursday', 'schedula-smart-appointment-booking'), __('Friday', 'schedula-smart-appointment-booking'), __('Saturday', 'schedula-smart-appointment-booking')];

const getDayName = (dayIndex) => daysOfWeek[dayIndex];
const getDayColor = (dayIndex) => {
  const colors = [
    'bg-red-400', 'bg-blue-400', 'bg-green-400', 'bg-yellow-400', 
    'bg-purple-400', 'bg-pink-400', 'bg-indigo-400'
  ];
  return colors[dayIndex] || 'bg-gray-400';
};



// Generate time options based on `timeSlotLength`
const generateTimeOptions = (intervalMinutes) => {
  const options = [];
  for (let hour = 0; hour < 24; hour++) {
    for (let minute = 0; minute < 60; minute += intervalMinutes) {
      const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
      const displayTime = formatTime(timeString, settings.general.timeFormat);
      options.push({ value: timeString, label: displayTime });
    }
  }
  return options;
};

// Watch for timeSlotLength changes to regenerate timeOptions
watch(() => settings.general.timeSlotLength, (newVal) => {
  // Ensure newVal is treated as a number here for calculation
  timeOptions.value = generateTimeOptions(Number(newVal));
}, { immediate: true });

// Watch for timeFormat changes to regenerate timeOptions
watch(() => settings.general.timeFormat, () => {
  timeOptions.value = generateTimeOptions(Number(settings.general.timeSlotLength));
}, { immediate: true });


const closeAllDuplicateOptions = () => {
  showDuplicateOptions.value = {};
};

const handleClickOutside = (event) => {
  const isClickInside = duplicateContainers.value.some(container => container && container.contains(event.target));
  if (!isClickInside) {
    closeAllDuplicateOptions();
  }
};

const toggleDuplicateOptions = (dayIndex) => {
    const wasOpen = showDuplicateOptions.value[dayIndex];
    closeAllDuplicateOptions();
    if (!wasOpen) {
        showDuplicateOptions.value[dayIndex] = true;
    }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});


const duplicateScheduleToDay = (fromDayIndex, toDayIndex) => {
  const fromDay = settings.general.defaultBusinessSchedule[fromDayIndex];
  const toDay = settings.general.defaultBusinessSchedule[toDayIndex];
  
  toDay.start_time = fromDay.start_time;
  toDay.end_time = fromDay.end_time;
  toDay.breaks = JSON.parse(JSON.stringify(fromDay.breaks)); // Deep copy breaks
  
  showDuplicateOptions.value[fromDayIndex] = false;
};

const duplicateScheduleToAll = (fromDayIndex) => {
  const fromDay = settings.general.defaultBusinessSchedule[fromDayIndex];
  settings.general.defaultBusinessSchedule.forEach((day, index) => {
    if (index !== fromDayIndex) {
      day.start_time = fromDay.start_time;
      day.end_time = fromDay.end_time;
      day.breaks = JSON.parse(JSON.stringify(fromDay.breaks)); // Deep copy breaks
    }
  });
  showDuplicateOptions.value[fromDayIndex] = false;
  success(__('Schedule duplicated to all days!', 'schedula-smart-appointment-booking'));
};


// Break Modal State (for Working Hours)
const showBreakModal = ref(false);
const newBreak = ref({ start_time: '', end_time: '', description: '' });
const currentDayScheduleForBreak = ref(null);

const openAddBreakModal = (daySchedule) => {
  if (!daySchedule.start_time || !daySchedule.end_time) {
    toastError(__('Please set working hours for this day before adding breaks.', 'schedula-smart-appointment-booking')); 
    return;
  }
  currentDayScheduleForBreak.value = daySchedule;
  newBreak.value = { start_time: '', end_time: '', description: '' }; 
  showBreakModal.value = true;
};

const closeAddBreakModal = () => {
  showBreakModal.value = false;
  currentDayScheduleForBreak.value = null;
  newBreak.value = { start_time: '', end_time: '', description: '' };
};

const saveBreak = () => {
  if (!newBreak.value.start_time || !newBreak.value.end_time) {
    toastError(__('Break start and end times are required.', 'schedula-smart-appointment-booking')); 
    return;
  }

  if (newBreak.value.start_time >= newBreak.value.end_time) {
    toastError(__('Break end time must be after start time.', 'schedula-smart-appointment-booking')); 
    return;
  }

  const newBreakStart = new Date(`2000-01-01T${newBreak.value.start_time}`);
  const newBreakEnd = new Date(`2000-01-01T${newBreak.value.end_time}`);

  const hasOverlap = currentDayScheduleForBreak.value.breaks.some(existingBreak => {
      const existingStart = new Date(`2000-01-01T${existingBreak.start_time}`);
      const existingEnd = new Date(`2000-01-01T${existingBreak.end_time}`);

      return (newBreakStart < existingEnd && newBreakEnd > existingStart);
  });

  if (hasOverlap) {
      toastError(__('This break overlaps with an existing break.', 'schedula-smart-appointment-booking')); 
      return;
  }

  const dayStartTime = new Date(`2000-01-01T${currentDayScheduleForBreak.value.start_time}`);
  const dayEndTime = new Date(`2000-01-01T${currentDayScheduleForBreak.value.end_time}`);

  if (newBreakStart < dayStartTime || newBreakEnd > dayEndTime) {
      toastError(__('Break must be within the set working hours for this day.', 'schedula-smart-appointment-booking')); 
      return;
  }

  currentDayScheduleForBreak.value.breaks.push({ ...newBreak.value });
  success(__('Break added successfully!', 'schedula-smart-appointment-booking')); 
  closeAddBreakModal();
};

const removeBreak = (daySchedule, index) => {
  daySchedule.breaks.splice(index, 1);
  success(__('Break removed successfully!', 'schedula-smart-appointment-booking')); 
};


// Holiday Modal State (for Holidays)
const showHolidayModal = ref(false);
const newHoliday = ref({ start_date: '', end_date: '', description: '' });
const holidayDateRangeContainer = ref(null);
let flowbiteHolidayDatepickerInstance = null; 


const formatHolidayDate = (holiday) => {
  if (holiday.start_date && holiday.end_date && holiday.start_date !== holiday.end_date) {
      const startDate = new Date(holiday.start_date + 'T00:00:00');
      const endDate = new Date(holiday.end_date + 'T00:00:00');
      const options = { year: 'numeric', month: 'short', day: 'numeric' };
      return `${startDate.toLocaleDateString('en-US', options)} - ${endDate.toLocaleDateString('en-US', options)}`;
  } else if (holiday.start_date) { // Single day holiday
      const date = new Date(holiday.start_date + 'T00:00:00');
      const options = { year: 'numeric', month: 'short', day: 'numeric' };
      return date.toLocaleDateString('en-US', options);
  }
  return 'Invalid Date';
};

const openAddHolidayModal = async () => {
  newHoliday.value = { start_date: '', end_date: '', description: '' };
  showHolidayModal.value = true;
  await nextTick(); 
  
  if (holidayDateRangeContainer.value && !flowbiteHolidayDatepickerInstance) {
    flowbiteHolidayDatepickerInstance = new DateRangePicker(holidayDateRangeContainer.value, {
      format: 'yyyy-mm-dd',
      autohide: true,
      // Apply dark mode styles to datepicker if enabled via appearance settings
      theme: appearanceSettings.adminDarkModeEnabled ? 'dark' : 'light', 
    });

    const startInput = holidayDateRangeContainer.value.querySelector('input[name="start"]');
    const endInput = holidayDateRangeContainer.value.querySelector('input[name="end"]');

    if (startInput) {
      startInput.addEventListener('changeDate', (event) => {
        const date = event.detail.date;
        newHoliday.value.start_date = date ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}` : '';
      });
    }
    if (endInput) {
      endInput.addEventListener('changeDate', (event) => {
        const date = event.detail.date;
        newHoliday.value.end_date = date ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}` : '';
      });
    }
  } else if (flowbiteHolidayDatepickerInstance) {
      // If instance exists, reset its dates for new entry
      flowbiteHolidayDatepickerInstance.setDates(null, null);
  }
};

const closeAddHolidayModal = () => {
  showHolidayModal.value = false;
  newHoliday.value = { start_date: '', end_date: '', description: '' };
  if (flowbiteHolidayDatepickerInstance) {
      flowbiteHolidayDatepickerInstance.destroy();
      flowbiteHolidayDatepickerInstance = null;
  }
};

const saveHoliday = () => {
  if (!newHoliday.value.start_date || !newHoliday.value.end_date) {
    toastError( __('Holiday start and end dates are required.', 'schedula-smart-appointment-booking') ); 
    return;
  }

  const startDate = new Date(newHoliday.value.start_date + 'T00:00:00');
  const endDate = new Date(newHoliday.value.end_date + 'T00:00:00');

  if (startDate > endDate) {
    toastError( __('Holiday end date must be after or on the start date.', 'schedula-smart-appointment-booking') ); 
    return;
  }
  
  // Check for overlaps with existing holidays
  const hasOverlap = settings.general.defaultBusinessHolidays.some(existingHoliday => {
      const existingStart = new Date(existingHoliday.start_date + 'T00:00:00');
      const existingEnd = new Date(existingHoliday.end_date + 'T00:00:00');

      // Check for overlap: (StartA <= EndB) && (EndA >= StartB)
      return (startDate <= existingEnd && endDate >= existingStart);
  });

  if (hasOverlap) {
      toastError( __('This holiday period overlaps with an existing holiday.', 'schedula-smart-appointment-booking') ); 
      return;
  }

  settings.general.defaultBusinessHolidays.push({ ...newHoliday.value });
  success( __('Holiday added successfully!', 'schedula-smart-appointment-booking') ); 
  closeAddHolidayModal();
};

const removeHoliday = (index) => {
  settings.general.defaultBusinessHolidays.splice(index, 1);
  success( __('Holiday removed successfully!', 'schedula-smart-appointment-booking') ); 
};


onMounted(async () => {
  try {
    await fetchSettings();
    await fetchGlobalSettings(); // Ensure global settings are loaded on component mount
  } catch (error) {
    console.error('Error during SettingsPage mounted hook:', error);
    toastError( __('An error occurred during page initialization.', 'schedula-smart-appointment-booking') );   
  }
});

// Watch for dark mode change to update datepicker theme
watch(() => appearanceSettings.adminDarkModeEnabled, (newVal) => { 
    if (flowbiteHolidayDatepickerInstance) {
        flowbiteHolidayDatepickerInstance.setOptions({
            theme: newVal ? 'dark' : 'light',
        });
    }
});


</script>

<style scoped>
.header-container {
  display: flex;
  padding-left: 5mm;
  align-items: center; /* Vertically centers items if needed */
}

/* Styles for input fields to ensure dark mode compatibility */
.input-field {
  background-color: var(--admin-input-bg-color);
  color: var(--admin-input-text-color);
  border-color: var(--admin-input-border-color);
}

.input-field:focus {
  border-color: var(--admin-link-indigo-bg);
  box-shadow: 0 0 0 1px var(--admin-link-indigo-bg);
  outline: none;
}


/* Specific styles for the working hours/holidays cards */
.day-schedule-card {
  background-color: var(--admin-card-bg-color);
  color: var(--admin-text-color);
  border-color: var(--admin-border-color);
}

.day-schedule-card:hover {
  background-color: var(--admin-input-bg-color); /* Lighter hover for dark mode */
}

.duplicate-button {
  background-color: var(--admin-button-secondary-bg);
  color: var(--admin-button-secondary-text);
  border: 1px solid var(--admin-input-border-color);
}

.duplicate-button:hover {
  background-color: var(--admin-button-secondary-hover-bg);
}

.dropdown-menu {
  background-color: var(--admin-card-bg-color);
  border-color: var(--admin-border-color);
  color: var(--admin-text-color);
}

.dropdown-item {
  color: var(--admin-text-color);
}

.dropdown-item:hover {
  background-color: var(--admin-input-bg-color);
}

.break-label {
  background-color: var(--admin-suggestion-yellow-bg);
  color: var(--admin-suggestion-yellow-text);
  border: 1px solid var(--admin-suggestion-yellow-border);
}

.break-remove-button {
  background-color: var(--admin-suggestion-yellow-bg);
  color: var(--admin-suggestion-yellow-text);
}

.break-remove-button:hover {
  background-color: var(--admin-suggestion-yellow-hover-bg);
}

.preview-card {
  background-color: var(--admin-input-bg-color);
  border-color: var(--admin-input-border-color);
  color: var(--admin-input-text-color);
}

.modal-content {
  background-color: var(--admin-card-bg-color);
  color: var(--admin-text-color);
}
</style>