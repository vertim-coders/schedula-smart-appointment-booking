<template>
  <div class="min-h-screen flex flex-col items-center justify-center p-4" :style="adminCustomStyles">
    <div class="w-full max-w-4xl rounded-lg shadow-2xl overflow-hidden" :style="{ backgroundColor: 'var(--admin-card-bg-color)' }">
      <div class="p-8">
        <!-- Stepper -->
        <div class="mb-8">
          <div class="flex items-center justify-center">
            <div v-for="(step, index) in steps" :key="index" class="flex items-center">
              <div class="flex flex-col items-center">
                <div
                  class="w-10 h-10 rounded-full flex items-center justify-center text-white"
                  :style="{ backgroundColor: currentStep >= index + 1 ? '#00183c' : 'var(--admin-link-gray-bg)' }"
                >
                  <i v-if="currentStep > index + 1" class="fas fa-check"></i>
                  <span v-else>{{ index + 1 }}</span>
                </div>
                <p class="text-sm mt-2" :class="{'font-semibold': currentStep === index + 1}">{{ step.name }}</p>
              </div>
              <div v-if="index < steps.length - 1" class="flex-auto border-t-2 mx-4"
                :style="{ borderColor: currentStep > index + 1 ? '#00183c' : 'var(--admin-link-gray-bg)' }"
              ></div>
            </div>
          </div>
        </div>

        <!-- Step Content -->
        <div :key="currentStep">
          <!-- Step 1: Welcome -->
          <div v-if="currentStep === 1" class="text-center">
            <h1 class="text-3xl font-bold mb-2">{{ __('Welcome to Schedula!', 'schedula-smart-appointment-booking') }}</h1>
            <p :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Let\'s get your business set up for online bookings in just a few steps.', 'schedula-smart-appointment-booking') }}</p>
            <p class="mt-4 max-w-2xl mx-auto" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Schedula is a powerful and easy-to-use appointment scheduling plugin for WordPress. It helps you manage your appointments, staff, and services, and allows your clients to book online 24/7.', 'schedula-smart-appointment-booking') }}</p>
          </div>

          <!-- Step 2: Company -->
          <div v-if="currentStep === 2">
            <h2 class="text-2xl font-bold mb-4">{{ __('Tell us about your company', 'schedula-smart-appointment-booking') }}</h2>
            <div class="space-y-6">
              <!-- Company Logo Section -->
              <div class="border-b pb-6 mb-6" :style="{ borderColor: 'var(--admin-border-color)' }">
                <label for="companyLogoUpload" class="block text-sm font-medium mb-2">
                  {{ __('Upload or Select Logo', 'schedula-smart-appointment-booking') }}
                </label>
                <div class="relative w-40 h-40 border-2 border-dashed rounded-lg flex flex-col items-center justify-center cursor-pointer overflow-hidden group"
                     @click="openMediaUploader"
                     id="companyLogoUpload"
                     :style="{ borderColor: 'var(--admin-input-border-color)' }">
                    <template v-if="settings.company.companyLogoUrl">
                        <img :src="settings.company.companyLogoUrl" :alt="__('Company Logo Preview', 'schedula-smart-appointment-booking')"
                             class="absolute inset-0 w-full h-full object-contain p-1" @error="handleImageError"/>
                    </template>
                    <template v-else>
                        <i class="fas fa-camera text-4xl" :style="{ color: 'var(--admin-input-text-muted)' }"></i>
                        <p class="text-sm mt-2" :style="{ color: 'var(--admin-input-text-muted)' }">{{ __('Image', 'schedula-smart-appointment-booking') }}</p>
                    </template>
                    <div v-if="settings.company.companyLogoUrl" class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                        <button type="button" @click.stop="removeCompanyLogo"
                                class="p-2 rounded-full text-white focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors duration-200"
                                :style="{ backgroundColor: 'var(--admin-button-third-bg)' }">
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
                  class="mt-1 block w-full max-w-xs rounded-md shadow-sm p-2 input-field"
                  :style="validationErrors.companyName ? { borderColor: 'var(--admin-status-red-text)' } : null"
                />
                <p class="mt-2 text-xs text-secondary">{{ __('The official name of your business.', 'schedula-smart-appointment-booking') }}</p>
                <p v-if="validationErrors.companyName" class="text-sm mt-1" :style="{ color: 'var(--admin-status-red-text)' }">
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
                  class="mt-1 block w-full max-w-xs rounded-md shadow-sm p-2 input-field"
                />
                <p class="mt-2 text-xs text-secondary">{{ __('Your company\'s physical address.', 'schedula-smart-appointment-booking') }}</p>
              </div>

              <!-- Phone -->
              <div class="grid grid-cols-1 gap-4">
                <label for="phone" class="block text-sm font-medium">{{ __('Phone Number', 'schedula-smart-appointment-booking') }}</label>
                <BasePhoneInput
                  id="phone"
                  v-model="settings.company.phone"
                  class="mt-1 block w-full max-w-xs rounded-md shadow-sm p-2 input-field"
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
                  class="mt-1 block w-full max-w-xs rounded-md shadow-sm p-2 input-field"
                  :style="validationErrors.email ? { borderColor: 'var(--admin-status-red-text)' } : null"
                />
                <p class="mt-2 text-xs text-secondary">{{ __("Your company's primary contact email address.", 'schedula-smart-appointment-booking') }}</p>
                <p v-if="validationErrors.email" class="text-sm mt-1" :style="{ color: 'var(--admin-status-red-text)' }">
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
                  class="mt-1 block w-full max-w-xs rounded-md shadow-sm p-2 input-field"
                />
                <p class="mt-2 text-xs text-secondary">{{ __('Your company\'s official website address (e.g., https://yourcompany.com).', 'schedula-smart-appointment-booking') }}</p>
              </div>

              <!-- Industry -->
              <div class="grid grid-cols-1 gap-4">
                <label for="industry" class="block text-sm font-medium">{{ __('Industry', 'schedula-smart-appointment-booking') }}</label>
                <select 
                  id="industry" 
                  v-model="settings.company.industry"
                  class="mt-1 block w-full max-w-xs rounded-md shadow-sm p-2 input-field"
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
                  class="mt-1 block w-full max-w-xs rounded-md shadow-sm p-2 input-field"
                ></textarea>
                <p class="mt-2 text-xs text-secondary">{{ __('A brief description of the main services your company provides.', 'schedula-smart-appointment-booking') }}</p>
              </div>

              <!-- Company Size -->
              <div class="grid grid-cols-1 gap-4">
                <label for="companySize" class="block text-sm font-medium">{{ __('Company Size', 'schedula-smart-appointment-booking') }}</label>
                <select 
                  id="companySize" 
                  v-model="settings.company.companySize"
                  class="mt-1 block w-full max-w-xs rounded-md shadow-sm p-2 input-field"
                >
                  <option value="">{{ __('Select Company Size', 'schedula-smart-appointment-booking') }}</option>
                  <option v-for="option in utilityOptions.companySizes" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
                <p class="mt-2 text-xs text-secondary">{{ __('Approximate number of employees.', 'schedula-smart-appointment-booking') }}</p>
              </div>
            </div>
          </div>

          <!-- Step 3: General Settings (Localization) -->
          <div v-if="currentStep === 3">
            <h2 class="text-2xl font-bold mb-4">{{ __('General Settings', 'schedula-smart-appointment-booking') }}</h2>
            <div class="space-y-6">
              <!-- Time Slot Length -->
              <div class="grid grid-cols-1 gap-4">
                <label for="timeSlotLength" class="block text-sm font-medium">
                  {{ __('Time Slot Length (minutes)', 'schedula-smart-appointment-booking') }}
                </label>
                <select 
                  id="timeSlotLength" 
                  v-model="settings.general.timeSlotLength"
                  class="mt-1 block w-full max-w-xs rounded-md shadow-sm p-2 input-field"
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
                  class="mt-1 block w-full max-w-xs rounded-md shadow-sm p-2 input-field"
                >
                  <option v-for="option in timeFormatOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
                <p class="mt-2 text-xs text-secondary">{{ __('Choose how time is displayed across the application (e.g., 1:00 PM or 13:00).', 'schedula-smart-appointment-booking') }}</p>
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
                    class="w-full rounded-md shadow-sm p-2 input-field"
                  />
                  <div
                    v-if="showTimezoneResults"
                    class="absolute z-10 w-full border rounded-md shadow-lg max-h-60 overflow-y-auto mt-1"
                    :style="{ backgroundColor: 'var(--admin-input-bg-color)', borderColor: 'var(--admin-input-border-color)' }"
                  >
                    <button
                      v-for="timezone in filteredTimezones"
                      :key="timezone"
                      @mousedown.prevent="selectTimezone(timezone)"
                      class="block w-full text-left px-4 py-2 text-sm dropdown-item"
                      :style="{ color: 'var(--admin-input-text-color)', '--tw-ring-color': 'var(--admin-link-indigo-bg)' }"
                    >
                      {{ timezone }}
                    </button>
                    <div v-if="!filteredTimezones.length" class="px-4 py-2 text-sm" :style="{color: 'var(--admin-input-text-muted)'}">
                      {{ __('No timezones found.', 'schedula-smart-appointment-booking') }}
                    </div>
                  </div>
                </div>
                <p class="mt-2 text-xs text-secondary">
                  {{ __('Set the primary timezone for your business operations and staff schedules.', 'schedula-smart-appointment-booking') }}
                </p>
              </div>

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
                    class="w-full rounded-md shadow-sm p-2 input-field"
                  />
                  <div
                    v-if="showCurrencyResults"
                    class="absolute z-10 w-full border rounded-md shadow-lg max-h-60 overflow-y-auto mt-1"
                    :style="{ backgroundColor: 'var(--admin-input-bg-color)', borderColor: 'var(--admin-input-border-color)' }"
                  >
                    <button
                      v-for="currency in filteredCurrencies"
                      :key="currency.code"
                      @mousedown.prevent="selectCurrency(currency)"
                      class="block w-full text-left px-4 py-2 text-sm dropdown-item"
                      :style="{ color: 'var(--admin-input-text-color)', '--tw-ring-color': 'var(--admin-link-indigo-bg)' }"
                    >
                      {{ currency.name }} ({{ currency.symbol }}) - {{ currency.code }}
                    </button>
                    <div v-if="!filteredCurrencies.length" class="px-4 py-2 text-sm" :style="{color: 'var(--admin-input-text-muted)'}">
                      {{ __('No currencies found.', 'schedula-smart-appointment-booking') }}
                    </div>
                  </div>
                </div>
                <p class="mt-2 text-xs text-secondary">
                  {{ __('Choose the primary currency for all prices in the booking system.', 'schedula-smart-appointment-booking') }}
                </p>
              </div>
            </div>
          </div>

          <!-- Step 4: Working Hours -->
          <div v-if="currentStep === 4">
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
            <h2 class="text-2xl font-bold mb-4">{{ __('Set your default business hours', 'schedula-smart-appointment-booking') }}</h2>
            <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2">
                <div v-for="(daySchedule, index) in settings.general.defaultBusinessSchedule" :key="index"
                    class="p-4 border rounded-lg shadow-sm transition-colors duration-200 day-schedule-card">
                    <div class="flex items-center justify-between mb-3">
                        <h5 class="text-md font-semibold flex items-center space-x-2" :style="{color: 'var(--admin-card-text-color)'}">
                            <div class="w-3 h-3 rounded-full" :class="getDayColor(daySchedule.day_of_week)"></div>
                            <span>{{ getDayName(daySchedule.day_of_week) }}</span>
                        </h5>

                        <!-- Duplicate Options Toggle -->
                        <div class="relative" v-if="daySchedule.start_time || daySchedule.end_time">
                            <button type="button" @click="toggleDuplicateOptions(index)"
                                    class="px-3 py-1 rounded-md text-xs transition duration-150 ease-in-out duplicate-button">
                                <i class="fas fa-copy"></i>
                                <span>{{ __('Duplicate', 'schedula-smart-appointment-booking') }}</span>
                            </button>

                            <!-- Duplicate Options Dropdown -->
                            <div v-if="showDuplicateOptions[index]"
                                class="absolute right-0 top-8 z-10 border rounded-md shadow-lg p-2 min-w-[150px] dropdown-menu">
                                <p class="text-xs text-secondary mb-2">{{ __('Copy to:', 'schedula-smart-appointment-booking') }}</p>
                                <div class="space-y-1">
                                    <button v-for="(otherDay, otherIndex) in settings.general.defaultBusinessSchedule" :key="otherIndex"
                                            v-if="otherIndex !== index"
                                            type="button"
                                            @click="duplicateScheduleToDay(index, otherIndex)"
                                            class="w-full text-left px-2 py-1 text-xs rounded dropdown-item">
                                        <div class="w-2 h-2 rounded-full" :class="getDayColor(otherDay.day_of_week)"></div>
                                        <span>{{ getDayName(otherDay.day_of_week) }}</span>
                                    </button>
                                    <button type="button" @click="duplicateScheduleToAll(index)"
                                            class="w-full text-left px-2 py-1 text-xs rounded dropdown-item">
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
                            <label :for="`start-time-${index}`" class="block text-sm font-medium mb-1" :style="{color: 'var(--admin-card-text-color)'}">{{ __('From:', 'schedula-smart-appointment-booking') }}</label>
                            <select :id="`start-time-${index}`" v-model="daySchedule.start_time"
                                    class="block w-full rounded-md border shadow-sm text-sm p-2 input-field">
                                <option value="">{{ __('Off', 'schedula-smart-appointment-booking') }}</option>
                                <option v-for="timeOption in timeOptions" :key="timeOption.value" :value="timeOption.value">
                                    {{ timeOption.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label :for="`end-time-${index}`" class="block text-sm font-medium mb-1" :style="{color: 'var(--admin-card-text-color)'}">{{ __('To:', 'schedula-smart-appointment-booking') }}</label>
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
                                    class="px-3 py-2 text-white rounded-md text-xs transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-1 whitespace-nowrap"
                                    :style="{backgroundColor: '#00183c'}">
                                <i class="fas fa-coffee text-xs"></i>
                                <span>{{ __('Add Break', 'schedula-smart-appointment-booking') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Breaks Display -->
                    <div v-if="daySchedule.breaks && daySchedule.breaks.length > 0" class="mt-4 border-t pt-3" :style="{borderColor: 'var(--admin-border-color)'}">
                        <h6 class="text-sm font-medium mb-2 flex items-center" :style="{color: 'var(--admin-card-text-color)'}">
                            <i class="fas fa-pause-circle mr-1"></i>{{ __('Breaks:', 'schedula-smart-appointment-booking') }}
                        </h6>
                        <div class="flex flex-wrap gap-2">
                            <div v-for="(b, bIndex) in daySchedule.breaks" :key="bIndex"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium break-label">
                                <i class="fas fa-coffee mr-1 text-xs"></i>
                                {{ formatTime(b.start_time) }} - {{ formatTime(b.end_time) }}
                                <span v-if="b.description" class="ml-1">({{ b.description }})</span>
                                <button type="button" @click="removeBreak(daySchedule, bIndex)"
                                        class="ml-2 -mr-1 h-4 w-4 flex items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 break-remove-button">
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

             <!-- Enhanced Break Input Modal -->
            <transition name="modal-fade">
                <div v-if="showBreakModal"
                    class="fixed inset-0 z-50 flex items-center justify-center"
                    style="background-color: rgba(0, 0, 0, 0.5)"
                    @click.self="closeAddBreakModal">
                <div class="rounded-lg shadow-xl w-full max-w-md mx-4 my-8 p-6 relative modal-content">
                    <div class="flex items-center space-x-2 mb-4">
                    <i class="fas fa-coffee" :style="{color: '#00183c'}"></i>
                    <h4 class="text-lg font-semibold">{{ __('Add Break', 'schedula-smart-appointment-booking') }}</h4>
                    </div>

                    <!-- Break Preview -->
                    <div v-if="currentDayScheduleForBreak" class="mb-4 p-3 rounded-lg border preview-card">
                    <div class="text-sm mb-2" :style="{color: 'var(--admin-card-text-color)'}">
                        <i class="fas fa-calendar-day mr-1"></i>{{ getDayName(currentDayScheduleForBreak.day_of_week) }}
                        ({{ formatTime(currentDayScheduleForBreak.start_time, { timeFormat: settings.general.timeFormat }) }} - {{ formatTime(currentDayScheduleForBreak.end_time, { timeFormat: settings.general.timeFormat }) }})
                    </div>
                    <div class="text-xs text-secondary">
                        <i class="fas fa-info-circle mr-1"></i>{{ __('Break must be within working hours', 'schedula-smart-appointment-booking') }}
                    </div>
                    </div>

                    <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                        <label for="break-start-time" class="block text-sm font-medium">
                            <i class="fas fa-play mr-1" :style="{color: 'var(--admin-input-text-muted)'}"></i>{{ __('Start Time', 'schedula-smart-appointment-booking') }}
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
                            <i class="fas fa-stop mr-1" :style="{color: 'var(--admin-input-text-muted)'}"></i>{{ __('End Time', 'schedula-smart-appointment-booking') }}
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
                            class="px-4 py-2 border rounded-md shadow-sm text-sm font-medium"
                             :style="{backgroundColor: 'var(--admin-link-gray-bg)', color: 'var(--admin-link-gray-text)'}">
                        <i class="fas fa-times mr-1"></i>{{ __('Cancel', 'schedula-smart-appointment-booking') }}
                    </button>
                    <button type="button" @click="saveBreak"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white"
                            :style="{backgroundColor: '#00183c'}">
                        <i class="fas fa-plus mr-1"></i>{{ __('Add Break', 'schedula-smart-appointment-booking') }}
                    </button>
                    </div>
                </div>
                </div>
            </transition>
          </div>

          <!-- Step 5: Holidays -->
          <div v-if="currentStep === 5">
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
            <h2 class="text-2xl font-bold mb-4">{{ __('Set your business holidays', 'schedula-smart-appointment-booking') }}</h2>
            <div class="space-y-4">
                <div v-for="(holiday, hIndex) in settings.general.defaultBusinessHolidays" :key="hIndex"
                    class="p-4 border rounded-lg shadow-sm flex items-center gap-3 transition-colors duration-200 day-schedule-card">
                    <i class="fas fa-calendar-times text-yellow-600"></i>
                    <span class="font-medium" :style="{color: 'var(--admin-card-text-color)'}">{{ formatHolidayDate(holiday) }}</span>
                    <input type="text" v-model="holiday.description" :placeholder="__('Holiday description', 'schedula-smart-appointment-booking')"
                        class="flex-grow text-sm rounded-md border p-2 input-field" />
                    <button type="button" @click="removeHoliday(hIndex)"
                            class="p-2 rounded-md transition-colors duration-200 delete-button">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <button type="button" @click="openAddHolidayModal"
                        class="px-4 py-2 text-white rounded-md text-sm transition duration-150 ease-in-out flex items-center space-x-2"
                        :style="{backgroundColor: '#00183c'}">
                    <i class="fas fa-plus"></i>
                    <span>{{ __('Add Holiday', 'schedula-smart-appointment-booking') }}</span>
                </button>
                <p v-if="!settings.general.defaultBusinessHolidays.length && !isLoading" class="flex items-center text-secondary">
                    <i class="fas fa-info-circle mr-2"></i>{{ __('No holidays added yet.', 'schedula-smart-appointment-booking') }}
                </p>
            </div>

            <!-- Enhanced Holiday Input Modal with Flowbite Datepicker -->
            <transition name="modal-fade">
                <div v-if="showHolidayModal"
                    class="fixed inset-0 z-50 flex items-center justify-center"
                    style="background-color: rgba(0, 0, 0, 0.5)"
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
                                class="text-sm rounded-lg block w-full p-2.5 input-field"
                                :placeholder="__('Start date', 'schedula-smart-appointment-booking')" readonly>
                        </div>
                        <span class="text-secondary">{{ __('to', 'schedula-smart-appointment-booking') }}</span>
                        <div class="relative flex-1">
                            <input name="end" type="text"
                                class="text-sm rounded-lg block w-full p-2.5 input-field"
                                :placeholder="__('End date', 'schedula-smart-appointment-booking')" readonly>
                        </div>
                        </div>
                    </div>

                    <div>
                        <label for="holiday-description" class="block text-sm font-medium">
                        <i class="fas fa-comment mr-1" :style="{color: 'var(--admin-input-text-muted)'}"></i>{{ __('Description (Optional)', 'schedula-smart-appointment-booking') }}
                        </label>
                        <input type="text" id="holiday-description" v-model="newHoliday.description" :placeholder="__('e.g., Summer vacation', 'schedula-smart-appointment-booking')"
                            class="block w-full rounded-md border shadow-sm sm:text-sm p-2 input-field" />
                    </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="closeAddHolidayModal"
                            class="px-4 py-2 border rounded-md shadow-sm text-sm font-medium"
                             :style="{backgroundColor: 'var(--admin-link-gray-bg)', color: 'var(--admin-link-gray-text)'}">
                        <i class="fas fa-times mr-1"></i>{{ __('Cancel', 'schedula-smart-appointment-booking') }}
                    </button>
                    <button type="button" @click="saveHoliday"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white"
                            :style="{backgroundColor: '#00183c'}">
                        <i class="fas fa-plus mr-1"></i>{{ __('Add Holiday', 'schedula-smart-appointment-booking') }}
                    </button>
                    </div>
                </div>
                </div>
            </transition>
          </div>

          <!-- Step 6: Finish -->
          <div v-if="currentStep === 6" class="text-center">
            <h1 class="text-3xl font-bold mb-2">{{ __('You\'re all set!', 'schedula-smart-appointment-booking') }}</h1>
            <p :style="{ color: 'var(--admin-card-text-color)' }">{{ __('You can always change these settings later from the Settings page.', 'schedula-smart-appointment-booking') }}</p>
            <p class="mt-4 max-w-2xl mx-auto" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('To display your booking form, add the following shortcode to any page or post:', 'schedula-smart-appointment-booking') }}</p>
            <div class="mt-2 p-2 inline-flex items-center rounded" :style="{ backgroundColor: 'var(--admin-link-gray-bg)' }">
              <code :style="{ color: 'var(--admin-text-color)' }">[schesab_reservation_form]</code>
              <button @click="copyShortcode" class="ml-2 px-2 py-1 rounded-md text-sm font-medium text-white" :style="{backgroundColor: '#00183c'}">
                <i class="fas fa-copy"></i>
              </button>
            </div>
            <p class="mt-4" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('We invite you to add your first service and staff members to get started.', 'schedula-smart-appointment-booking') }}</p>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <div class="px-8 py-4 flex justify-between items-center" :style="{ backgroundColor: 'var(--admin-page-bg-color)' }">
        <div>
          <button v-if="currentStep > 1" @click="skip" class="px-4 py-2 rounded-md text-sm font-medium text-white" :style="{backgroundColor: 'var(--admin-link-green-bg)'}">{{ __('Skip for now', 'schedula-smart-appointment-booking') }}</button>
        </div>
        <div class="flex items-center space-x-2">
          <button v-if="currentStep > 1" @click="prevStep" class="px-4 py-2 rounded-md text-sm font-medium bg-gray-200 text-gray-800 hover:bg-gray-300">
            {{ __('Previous', 'schedula-smart-appointment-booking') }}
          </button>
          <button @click="nextStep" :disabled="isSaving" class="px-4 py-2 rounded-md text-sm font-medium text-white flex items-center justify-center min-w-[150px]" :style="{backgroundColor: '#00183c'}">
            <svg v-if="isSaving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-if="isSaving">{{ currentStep === steps.length ? __('Finishing...', 'schedula-smart-appointment-booking') : __('Saving...', 'schedula-smart-appointment-booking') }}</span>
            <span v-else>{{ currentStep === steps.length ? __('Finish', 'schedula-smart-appointment-booking') : __('Save & Continue', 'schedula-smart-appointment-booking') }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { settingsApi, utilityApi } from '@/admin/api.js';
import { useAdminAppearance } from '@/admin/composables/useAdminAppearance.js';
import { DateRangePicker } from 'flowbite-datepicker';
import { useToast } from '@/admin/composables/useToast.js';
import { useGlobalSettings } from '@/admin/composables/useGlobalSettings.js';
import BasePhoneInput from '@/admin/components/common/BasePhoneInput.vue';
import { __, sprintf } from '@wordpress/i18n';

const { adminCustomStyles, fetchAppearanceSettings } = useAdminAppearance();
const { success, error: toastError, info } = useToast();
const { formatTime, fetchGlobalSettings } = useGlobalSettings();
const router = useRouter();

const currentStep = ref(1);
const isLoading = ref(false); // For general loading states
const isSaving = ref(false); // For save & continue button

const steps = [
  { name: __('Welcome', 'schedula-smart-appointment-booking') },
  { name: __('Company', 'schedula-smart-appointment-booking') },
  { name: __('General', 'schedula-smart-appointment-booking') },
  { name: __('Working Hours', 'schedula-smart-appointment-booking') },
  { name: __('Holidays', 'schedula-smart-appointment-booking') },
  { name: __('Finish', 'schedula-smart-appointment-booking') },
];

// --- Initial default states for each settings section ---
const initialGeneralSettings = {
  timeFormat: '24h',
  timeSlotLength: '30',
  businessTimezone: 'Africa/Lagos', // Default from SettingsPage
  currencyCode: 'USD', // Default from SettingsPage
  enableDefaultBusinessSchedule: false, // NEW
  defaultBusinessSchedule: Array.from({ length: 7 }, (_, i) => ({
    day_of_week: i,
    start_time: '',
    end_time: '',
    breaks: [],
  })),
  enableDefaultBusinessHolidays: false, // NEW
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

// Initialize settings structure with default values
const settings = reactive({
  general: { ...initialGeneralSettings },
  company: { ...initialCompanySettings },
});

// Reactive object to hold dynamically fetched utility options
const utilityOptions = reactive({
  timezones: [],
  currencies: [],
  companySizes: [],
  industries: [],
});

// Reactive object to hold frontend validation error messages
const validationErrors = reactive({
  companyName: '',
  email: '',
});

// --- WordPress Media Uploader Logic ---
let mediaUploader = null;

const openMediaUploader = () => {
  if (typeof wp === 'undefined' || !wp.media) {
    toastError(__('WordPress media uploader is not available. This feature requires WordPress environment.', 'schedula-smart-appointment-booking'));
    console.error(__('WordPress media uploader (wp.media) not found. This feature requires WordPress environment.', 'schedula-smart-appointment-booking'));
    return;
  }

  if (mediaUploader) {
    mediaUploader.open();
    return;
  }

  mediaUploader = wp.media({
    title: __('Select Company Logo', 'schedula-smart-appointment-booking'),
    button: {
      text: __('Use this image', 'schedula-smart-appointment-booking'),
    },
    multiple: false,
  });

  mediaUploader.on('select', function() {
    const attachment = mediaUploader.state().get('selection').first().toJSON();
    settings.company.companyLogoUrl = attachment.url;
    success(__('Company logo selected successfully.', 'schedula-smart-appointment-booking'));
  });

  mediaUploader.open();
};

const removeCompanyLogo = () => {
  settings.company.companyLogoUrl = '';
  success(__('Company logo removed.', 'schedula-smart-appointment-booking'));
};

const handleImageError = (event) => {
  event.target.src = window.schedulaData.pluginUrl + 'assets/images/placeholders/no-image-placeholder.png';
  event.target.alt = __('Logo could not be loaded', 'schedula-smart-appointment-booking');
  toastError(__('Company logo could not be loaded.', 'schedula-smart-appointment-booking'));
};

// --- Searchable Currency Dropdown Logic ---
const currencySearchQuery = ref('');
const showCurrencyResults = ref(false);
let currencyBlurTimeout = null;

const groupedIndustries = computed(() => {
  const groups = {};
  if (Array.isArray(utilityOptions.industries)) {
    utilityOptions.industries.forEach(industry => {
      const groupName = industry.group || 'Other';
      if (!groups[groupName]) {
        groups[groupName] = { name: groupName, options: [] };
      }
      groups[groupName].options.push(industry);
    });
  }
  return Object.values(groups);
});

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
  currencySearchQuery.value = '';
  showCurrencyResults.value = false;
};

const hideCurrencyResultsDelayed = () => {
  currencyBlurTimeout = setTimeout(() => {
    showCurrencyResults.value = false;
  }, 150);
};

// --- Working Hours Logic ---
const timeSlotLengthOptions = computed(() => [
  { value: '15', label: __('15 minutes', 'schedula-smart-appointment-booking') },
  { value: '30', label: __('30 minutes', 'schedula-smart-appointment-booking') },
  { value: '45', label: __('45 minutes', 'schedula-smart-appointment-booking') },
  { value: '60', label: __('60 minutes (1 hour)', 'schedula-smart-appointment-booking') },
]);

const timeFormatOptions = computed(() => [
  { value: '12h', label: __('12-hour (AM/PM)', 'schedula-smart-appointment-booking') },
  { value: '24h', label: __('24-hour', 'schedula-smart-appointment-booking') },
]);

const timeOptions = computed(() => {
  const options = [];
  const step = parseInt(settings.general.timeSlotLength, 10) || 30;
  const use12h = settings.general.timeFormat === '12h';

  for (let i = 0; i < 24; i++) {
    for (let j = 0; j < 60; j += step) {
      const hour = String(i).padStart(2, '0');
      const minute = String(j).padStart(2, '0');
      const timeValue = `${hour}:${minute}`;
      
      let label = timeValue;
      if (use12h) {
        const h = i % 12 === 0 ? 12 : i % 12;
        const ampm = i < 12 ? 'AM' : 'PM';
        label = `${h}:${minute} ${ampm}`;
      }
      
      options.push({ value: timeValue, label: label });
    }
  }
  return options;
});

const getDayName = (dayIndex) => {
  const days = [
    __('Sunday', 'schedula-smart-appointment-booking'),
    __('Monday', 'schedula-smart-appointment-booking'),
    __('Tuesday', 'schedula-smart-appointment-booking'),
    __('Wednesday', 'schedula-smart-appointment-booking'),
    __('Thursday', 'schedula-smart-appointment-booking'),
    __('Friday', 'schedula-smart-appointment-booking'),
    __('Saturday', 'schedula-smart-appointment-booking'),
  ];
  return days[dayIndex];
};

const getDayColor = (dayIndex) => {
  const colors = [
    'bg-red-500', 'bg-blue-500', 'bg-green-500', 'bg-yellow-500',
    'bg-purple-500', 'bg-indigo-500', 'bg-pink-500'
  ];
  return colors[dayIndex];
};



const showBreakModal = ref(false);
const currentDayScheduleForBreak = ref(null);
const newBreak = reactive({
  start_time: '',
  end_time: '',
  description: '',
});

const openAddBreakModal = (daySchedule) => {
  currentDayScheduleForBreak.value = daySchedule;
  newBreak.start_time = '';
  newBreak.end_time = '';
  newBreak.description = '';
  showBreakModal.value = true;
};

const closeAddBreakModal = () => {
  showBreakModal.value = false;
  currentDayScheduleForBreak.value = null;
};

const saveBreak = () => {
  if (!newBreak.start_time || !newBreak.end_time) {
    toastError(__('Please select both start and end times for the break.', 'schedula-smart-appointment-booking'));
    return;
  }

  if (newBreak.start_time >= newBreak.end_time) {
    toastError(__('Break start time must be before end time.', 'schedula-smart-appointment-booking'));
    return;
  }

  const dayStartTime = currentDayScheduleForBreak.value.start_time;
  const dayEndTime = currentDayScheduleForBreak.value.end_time;

  if (dayStartTime && dayEndTime) {
    if (newBreak.start_time < dayStartTime || newBreak.end_time > dayEndTime) {
      toastError(__('Break must be within the day\'s working hours.', 'schedula-smart-appointment-booking'));
      return;
    }
  }

  if (!currentDayScheduleForBreak.value.breaks) {
    currentDayScheduleForBreak.value.breaks = [];
  }
  currentDayScheduleForBreak.value.breaks.push({ ...newBreak });
  closeAddBreakModal();
  success(__('Break added successfully!', 'schedula-smart-appointment-booking'));
};

const removeBreak = (daySchedule, breakIndex) => {
  daySchedule.breaks.splice(breakIndex, 1);
  success(__('Break removed successfully!', 'schedula-smart-appointment-booking'));
};

const showDuplicateOptions = reactive(Array(7).fill(false));

const toggleDuplicateOptions = (index) => {
  showDuplicateOptions[index] = !showDuplicateOptions[index];
};

const duplicateScheduleToDay = (sourceIndex, targetIndex) => {
  const sourceDay = settings.general.defaultBusinessSchedule[sourceIndex];
  const targetDay = settings.general.defaultBusinessSchedule[targetIndex];

  targetDay.start_time = sourceDay.start_time;
  targetDay.end_time = sourceDay.end_time;
  targetDay.breaks = JSON.parse(JSON.stringify(sourceDay.breaks)); // Deep copy breaks

  showDuplicateOptions[sourceIndex] = false; // Close dropdown
  success(__('Schedule duplicated successfully!', 'schedula-smart-appointment-booking'));
};

const duplicateScheduleToAll = (sourceIndex) => {
  const sourceDay = settings.general.defaultBusinessSchedule[sourceIndex];
  settings.general.defaultBusinessSchedule.forEach((day, index) => {
    if (index !== sourceIndex) {
      day.start_time = sourceDay.start_time;
      day.end_time = sourceDay.end_time;
      day.breaks = JSON.parse(JSON.stringify(sourceDay.breaks));
    }
  });
  showDuplicateOptions[sourceIndex] = false; // Close dropdown
  success(__('Schedule duplicated to all days successfully!', 'schedula-smart-appointment-booking'));
};

// --- Holidays Logic ---
const showHolidayModal = ref(false);
const newHoliday = reactive({
  start_date: '',
  end_date: '',
  description: '',
});
const holidayDateRangeContainer = ref(null);
let dateRangePickerInstance = null;

const openAddHolidayModal = () => {
  newHoliday.start_date = '';
  newHoliday.end_date = '';
  newHoliday.description = '';
  showHolidayModal.value = true;
  nextTick(() => {
    if (holidayDateRangeContainer.value) {
      const dateInputs = holidayDateRangeContainer.value.querySelectorAll('input[name="start"], input[name="end"]');
      if (dateInputs.length === 2) {
        dateRangePickerInstance = new DateRangePicker(holidayDateRangeContainer.value, {
          format: 'yyyy-mm-dd',
          autohide: true,
        });
        dateInputs[0].addEventListener('changeDate', (event) => {
          newHoliday.start_date = event.detail.date.toISOString().split('T')[0];
        });
        dateInputs[1].addEventListener('changeDate', (event) => {
          newHoliday.end_date = event.detail.date.toISOString().split('T')[0];
        });
      }
    }
  });
};

const closeAddHolidayModal = () => {
  showHolidayModal.value = false;
  if (dateRangePickerInstance) {
    dateRangePickerInstance.destroy();
    dateRangePickerInstance = null;
  }
};

const saveHoliday = () => {
  if (!newHoliday.start_date || !newHoliday.end_date) {
    toastError(__('Please select both start and end dates for the holiday.', 'schedula-smart-appointment-booking'));
    return;
  }

  const startDate = new Date(newHoliday.start_date);
  const endDate = new Date(newHoliday.end_date);

  if (startDate > endDate) {
    toastError(__('Holiday end date must be after or on the start date.', 'schedula-smart-appointment-booking'));
    return;
  }

  settings.general.defaultBusinessHolidays.push({ ...newHoliday });
  closeAddHolidayModal();
  success(__('Holiday added successfully!', 'schedula-smart-appointment-booking'));
};

const removeHoliday = (index) => {
  settings.general.defaultBusinessHolidays.splice(index, 1);
  success(__('Holiday removed successfully!', 'schedula-smart-appointment-booking'));
};

const formatHolidayDate = (holiday) => {
  if (!holiday.start_date || !holiday.end_date) return '';
  const start = new Date(holiday.start_date).toLocaleDateString();
  const end = new Date(holiday.end_date).toLocaleDateString();
  return start === end ? start : `${start} - ${end}`;
};

const copyShortcode = () => {
  const shortcode = '[schesab_reservation_form]';
  navigator.clipboard.writeText(shortcode).then(() => {
    success(__('Shortcode copied to clipboard!', 'schedula-smart-appointment-booking'));
  }).catch(err => {
    console.error('Failed to copy shortcode: ', err);
    toastError(__('Failed to copy shortcode.', 'schedula-smart-appointment-booking'));
  });
};

// --- Navigation and Save Logic ---
const fetchInitialData = async () => {
  isLoading.value = true;
  try {
    await fetchAppearanceSettings(); // Fetch appearance settings for styling

    const utilityResponse = await utilityApi.getUtilityData();
    utilityOptions.timezones = utilityResponse.data.timezones;
    utilityOptions.currencies = utilityResponse.data.currencies;
    utilityOptions.companySizes = utilityResponse.data.company_sizes;
    utilityOptions.industries = utilityResponse.data.industries;

    // Fetch existing settings if any (e.g., if user started onboarding and left)
    const generalResponse = await settingsApi.getGeneralSettings();
    const companyResponse = await settingsApi.getCompanySettings();

    // Apply fetched general settings, ensuring correct types for v-model
    const fetchedGeneralSettings = { ...initialGeneralSettings, ...generalResponse.data };
    fetchedGeneralSettings.timeSlotLength = String(fetchedGeneralSettings.timeSlotLength);
    fetchedGeneralSettings.defaultBusinessSchedule = fetchedGeneralSettings.defaultBusinessSchedule.map(day => ({
        ...day,
        day_of_week: Number(day.day_of_week),
        start_time: day.start_time ? day.start_time.slice(0, 5) : '',
        end_time: day.end_time ? day.end_time.slice(0, 5) : '',
    }));
    Object.assign(settings.general, fetchedGeneralSettings);

    // Apply fetched company settings
    Object.assign(settings.company, { ...initialCompanySettings, ...companyResponse.data });

  } catch (error) {
    console.error('Error fetching initial data for onboarding:', error);
    toastError(__('Failed to load initial data.', 'schedula-smart-appointment-booking'));
  } finally {
    isLoading.value = false;
  }
};

const nextStep = async () => {
  isSaving.value = true;
  let isValid = true;

  try {
    if (currentStep.value === 2) { // Company Settings
      if (!validateCompanySettings()) {
        isValid = false;
        toastError(__('Please correct the errors in the Company form.', 'schedula-smart-appointment-booking'));
      } else {
        await settingsApi.saveCompanySettings(settings.company);
        success(__('Company settings saved!', 'schedula-smart-appointment-booking'));
      }
    } else if (currentStep.value === 3) { // General Settings
      // No specific validation needed beyond what v-model handles for selects
      // Save general settings (timezone, currency)
      const dataToSave = {
        businessTimezone: settings.general.businessTimezone,
        currencyCode: settings.general.currencyCode,
        timeSlotLength: Number(settings.general.timeSlotLength),
        timeFormat: settings.general.timeFormat,
      };
      await settingsApi.saveGeneralSettings(dataToSave);
      await fetchGlobalSettings(true);
      success(__('General settings saved!', 'schedula-smart-appointment-booking'));
    } else if (currentStep.value === 4) { // Working Hours
      // Client-side validation for defaultBusinessSchedule
      for (const day of settings.general.defaultBusinessSchedule) {
        if ((day.start_time && !day.end_time) || (!day.start_time && day.end_time)) {
            toastError(__('Please provide both start and end times for %s or leave both empty.', 'schedula-smart-appointment-booking').replace('%s', getDayName(day.day_of_week)));
            isValid = false;
            break;
        }
        if (day.start_time && day.end_time && day.start_time >= day.end_time) {
            toastError(__('For %s, start time must be before end time.', 'schedula-smart-appointment-booking').replace('%s', getDayName(day.day_of_week)));
            isValid = false;
            break;
        }
        for (const b of day.breaks) {
            if (!b.start_time || !b.end_time) {
                toastError(__('Please provide both start and end times for all breaks on %s.', 'schedula-smart-appointment-booking').replace('%s', getDayName(day.day_of_week)));
                isValid = false;
                break;
            }
            if (b.start_time >= b.end_time) {
                toastError(__('For %s, a break\'s start time must be before its end time.', 'schedula-smart-appointment-booking').replace('%s', getDayName(day.day_of_week)));
                isValid = false;
                break;
            }
            if (day.start_time && day.end_time) {
                const dayStartTime = new Date(`2000-01-01T${day.start_time}`);
                const dayEndTime = new Date(`2000-01-01T${day.end_time}`);
                const breakStartTime = new Date(`2000-01-01T${b.start_time}`);
                const breakEndTime = new Date(`2000-01-01T${b.end_time}`);

                if (breakStartTime < dayStartTime || breakEndTime > dayEndTime) {
                    toastError(__('For %s, a break must be within the day\'s working hours (%s-%s).', 'schedula-smart-appointment-booking').replace('%s', getDayName(day.day_of_week)).replace('%s', day.start_time).replace('%s', day.end_time));
                    isValid = false;
                    break;
                }
            }
        }
        if (!isValid) break;
      }
      if (isValid) {
        await settingsApi.saveGeneralSettings({
          defaultBusinessSchedule: settings.general.defaultBusinessSchedule,
          enableDefaultBusinessSchedule: settings.general.enableDefaultBusinessSchedule // NEW
        });
        success(__('Working hours saved!', 'schedula-smart-appointment-booking'));
      }
    } else if (currentStep.value === 5) { // Holidays
      // Client-side validation for defaultBusinessHolidays
      for (const holiday of settings.general.defaultBusinessHolidays) {
          if (!holiday.start_date || !holiday.end_date) {
              toastError(__('Please provide both start and end dates for all holidays.', 'schedula-smart-appointment-booking'));
              isValid = false;
              break;
          }
          const startDate = new Date(holiday.start_date + 'T00:00:00');
          const endDate = new Date(holiday.end_date + 'T00:00:00');
          if (startDate > endDate) {
              toastError(__('Holiday end date must be after or on the start date.', 'schedula-smart-appointment-booking'));
              isValid = false;
              break;
          }
      }
      if (isValid) {
        await settingsApi.saveGeneralSettings({
          defaultBusinessHolidays: settings.general.defaultBusinessHolidays,
          enableDefaultBusinessHolidays: settings.general.enableDefaultBusinessHolidays // NEW
        });
        success(__('Holidays saved!', 'schedula-smart-appointment-booking'));
      }
    }

    if (isValid) {
      if (currentStep.value < steps.length) {
        currentStep.value++;
      } else {
        await setOnboardingComplete();
      }
    }
  } catch (error) {
    console.error('Error saving onboarding step:', error);
    toastError(error.response?.data?.message || __('Failed to save settings for this step.', 'schedula-smart-appointment-booking'));
    isValid = false;
  } finally {
    isSaving.value = false;
  }
};

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
};

const skip = () => {
  if (currentStep.value < steps.length) {
    currentStep.value++;
  }
};

const setOnboardingComplete = async () => {
  try {
    await settingsApi.setOnboardingComplete();
    
    if (window.schedulaData) {
        window.schedulaData.onboardingComplete = true;
    }
    
    router.push({ name: 'Dashboard' });
  } catch (error) {
    console.error('Failed to mark onboarding as complete', error);
    toastError(__('Failed to complete onboarding. Please try again.', 'schedula-smart-appointment-booking'));
  }
};

// --- Validation Functions ---
const validateCompanySettings = () => {
  let isValid = true;
  Object.keys(validationErrors).forEach(key => validationErrors[key] = '');

  if (!settings.company.companyName.trim()) {
    validationErrors.companyName = __('Please enter the company name.', 'schedula-smart-appointment-booking');
    isValid = false;
  }
  if (!settings.company.email.trim()) {
    validationErrors.email = __('Please enter the contact email address.', 'schedula-smart-appointment-booking');
    isValid = false;
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(settings.company.email)) {
    validationErrors.email = __('Please enter a valid email address.', 'schedula-smart-appointment-booking');
    isValid = false;
  }
  return isValid;
};

onMounted(() => {
  fetchInitialData();
});

onUnmounted(() => {
  if (mediaUploader) {
    try {
      mediaUploader.dispose();
    } catch (e) {
      console.warn('Error disposing media uploader on unmount:', e);
    }
    mediaUploader = null;
  }
  if (currencyBlurTimeout) clearTimeout(currencyBlurTimeout);
  if (timezoneBlurTimeout) clearTimeout(timezoneBlurTimeout);
  if (dateRangePickerInstance) {
    dateRangePickerInstance.destroy();
    dateRangePickerInstance = null;
  }
});

// --- Searchable Timezone Dropdown Logic ---
const timezoneSearchQuery = ref('');
const showTimezoneResults = ref(false);
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
// --- End Searchable Timezone Dropdown Logic ---
</script>

<style scoped>
/* Add any specific styles for the wizard here */
.modal-fade-enter-active, .modal-fade-leave-active {
  transition: opacity 0.3s ease;
}
.modal-fade-enter-from, .modal-fade-leave-to {
  opacity: 0;
}

/* Custom dropdown item hover effect */
.dropdown-item:hover {
  background-color: #f3f4f6; /* gray-100 */
}
.dark-mode .dropdown-item:hover {
  background-color: #374151; /* gray-700 */
}

/* Dark mode styles for native select options */
.dark-mode .input-field option,
.dark-mode .input-field optgroup {
  background-color: var(--admin-card-bg-color, #1f2937);
  color: var(--admin-text-color, #f9fafb);
}
</style>
