<template>
  <div class="space-y-4 min-w-[350px]">
    <!-- Header with gradient background -->
    <div class="p-4 rounded-lg" :style="{ backgroundColor: 'var(--admin-card-bg-color)', borderColor: 'var(--admin-border-color)' }">
      <div class="flex items-center space-x-3">
        <div class="p-2 rounded-full" :style="{ backgroundColor: 'var(--admin-input-border-color)' }">
          <i class="fas fa-user-tie p-2 h-[30px] w-[30px] rounded-full flex items-center justify-center" :style="{ color: 'var(--admin-link-blue-bg)' }"></i>
        </div>
        <h3 class="text-xl font-semibold" :style="{ color: 'var(--admin-text-color)' }">{{ isEditing ? __( 'Edit Staff Member', 'schedula-smart-appointment-booking') : __( 'Create New Staff Member', 'schedula-smart-appointment-booking') }}</h3>
      </div>
    </div>
    
    <!-- Tab Navigation -->
    <div class="mb-6" :style="{ borderColor: 'var(--admin-border-color)' }">
      <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <button 
          @click="currentTab = 'general'" 
          :class="['whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center space-x-2', currentTab === 'general' ? 'font-semibold' : '']" :style="{ borderColor: currentTab === 'general' ? 'var(--admin-link-indigo-bg)' : 'transparent', color: currentTab === 'general' ? 'var(--admin-link-indigo-bg)' : 'var(--admin-card-text-color)' }">
          <i class="fas fa-user"></i>
          <span>{{ __( 'General', 'schedula-smart-appointment-booking') }}</span>
        </button>
        <button 
          @click="currentTab = 'services'" 
          :class="['whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center space-x-2', currentTab === 'services' ? 'font-semibold' : '']" :style="{ borderColor: currentTab === 'services' ? 'var(--admin-link-indigo-bg)' : 'transparent', color: currentTab === 'services' ? 'var(--admin-link-indigo-bg)' : 'var(--admin-card-text-color)' }">
          <i class="fas fa-cut"></i>
          <span>{{ __( 'Services', 'schedula-smart-appointment-booking') }}</span>
        </button>
        <button 
          @click="currentTab = 'schedule'" 
          :class="['whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center space-x-2', currentTab === 'schedule' ? 'font-semibold' : '']" :style="{ borderColor: currentTab === 'schedule' ? 'var(--admin-link-indigo-bg)' : 'transparent', color: currentTab === 'schedule' ? 'var(--admin-link-indigo-bg)' : 'var(--admin-card-text-color)' }">
          <i class="fas fa-calendar-alt"></i>
          <span>{{ __( 'Schedule', 'schedula-smart-appointment-booking') }}</span>
        </button>
        <button 
          @click="currentTab = 'holidays'" 
          :class="['whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center space-x-2', currentTab === 'holidays' ? 'font-semibold' : '']" :style="{ borderColor: currentTab === 'holidays' ? 'var(--admin-link-indigo-bg)' : 'transparent', color: currentTab === 'holidays' ? 'var(--admin-link-indigo-bg)' : 'var(--admin-card-text-color)' }">
          <i class="fas fa-umbrella-beach"></i>
          <span>{{ __( 'Holidays', 'schedula-smart-appointment-booking') }}</span>
        </button>
      </nav>
    </div>

    <form @submit.prevent="submitForm">
      <!-- General Tab Content -->
      <div v-if="currentTab === 'general'" class="p-6 rounded-lg shadow-md content-card">
        <h3 class="text-xl font-semibold mb-6" :style="{ color: 'var(--admin-text-color)' }">{{ __( 'Personal Information', 'schedula-smart-appointment-booking') }}</h3>
        
        <!-- Staff Image Uploader -->
        <div class="mb-6">
            <label for="staffImageUpload" class="block text-sm font-medium mb-2">
                {{ __( 'Staff Image', 'schedula-smart-appointment-booking') }}
            </label>
            <div class="relative w-40 h-40 border-2 border-dashed rounded-lg flex flex-col items-center justify-center cursor-pointer overflow-hidden group hover:border-blue-500 transition-colors duration-200"
                 @click="openMediaUploader"
                 id="staffImageUpload">
                
                <template v-if="form.image_url">
                    <img :src="form.image_url" alt="Staff Image Preview" 
                         class="absolute inset-0 w-full h-full object-cover"/>
                </template>
                
                <template v-else>
                    <i class="fas fa-camera text-gray-400 text-4xl group-hover:text-blue-500 transition-colors duration-200"></i>
                    <p class="text-sm text-gray-500 mt-2 group-hover:text-blue-500 transition-colors duration-200">{{ __( 'Image', 'schedula-smart-appointment-booking') }}</p>
                </template>

                <div v-if="form.image_url" class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    <button type="button" @click.stop="removeImage" 
                            class="p-2 rounded-full bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors duration-200">
                        <i class="fas fa-trash-alt text-lg"></i>
                    </button>
                </div>
            </div>
            <p class="mt-2 text-xs text-secondary">{{ __( 'Click the box to upload or select an image.', 'schedula-smart-appointment-booking') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Staff Name -->
          <BaseInput
            id="name"
            :label="__( 'Full Name', 'schedula-smart-appointment-booking')"
            icon="fas fa-user"
            v-model="form.name"
            :required="true"
            :validationMessage="formError && formError.includes('name') ? __( 'Name is required', 'schedula-smart-appointment-booking') : ''"
          />

          <!-- Staff Email -->
          <BaseInput
            id="email"
            :label="__( 'Email Address', 'schedula-smart-appointment-booking')"
            icon="fas fa-envelope"
            type="email"
            v-model="form.email"
            :required="true"
            :validationMessage="formError && formError.includes('email') ? __( 'Valid email is required', 'schedula-smart-appointment-booking') : ''"
          />

          <!-- Staff Phone -->
          <BasePhoneInput
            id="phone"
            :label="__( 'Phone Number', 'schedula-smart-appointment-booking')"
            icon="fas fa-phone"
            v-model="form.phone"
            :required="false"
          />

          <!-- Staff Status -->
          <div>
            <label class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-card-text-color)' }">
              <i :class="form.status === 'active' ? 'fas fa-user-check' : 'fas fa-user-slash'" class="mr-1" :style="{ color: form.status === 'active' ? 'var(--admin-status-green-text)' : 'var(--admin-status-red-text)' }"></i>{{ __( 'Status', 'schedula-smart-appointment-booking') }}
            </label>
            <BaseSelect
              id="status"
              v-model="form.status"
              :options="[
                { value: 'active', text: __( 'Active', 'schedula-smart-appointment-booking') },
                { value: 'inactive', text: __( 'Inactive', 'schedula-smart-appointment-booking') }
              ]"
              :required="true"
            />
          </div>

          <!-- Staff Description/Notes -->
          <div class="md:col-span-2">
            <BaseTextarea
              id="description"
              :label="__( 'Notes', 'schedula-smart-appointment-booking')"
              icon="fas fa-sticky-note"
              v-model="form.description"
              :rows="3"
              :required="false"
            />
          </div>
        </div>
      </div>

      <!-- Services Tab Content -->
      <div v-else-if="currentTab === 'services'" class="p-6 rounded-lg content-card">
        <div class="flex justify-between items-center mb-4">
          <h4 class="text-lg font-medium" :style="{ color: 'var(--admin-text-color)' }">{{ __( 'Services Provided', 'schedula-smart-appointment-booking') }}</h4>
          <div v-if="!loadingServices && !servicesError && allServices.length > 0" class="flex items-center">
            <label class="inline-flex items-center cursor-pointer">
              <input type="checkbox" 
                     @change="toggleAllServices" 
                     :checked="areAllServicesSelected"
                     :indeterminate="areSomeServicesSelected && !areAllServicesSelected"
                     class="form-checkbox h-4 w-4 rounded mr-2" 
                     :style="{ color: 'var(--admin-link-blue-bg)' }" />
              <span class="text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{ __( 'Select All', 'schedula-smart-appointment-booking') }}</span>
            </label>
          </div>
        </div>
        <p v-if="loadingServices" class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
          <i class="fas fa-spinner fa-spin mr-2"></i>{{ __( 'Loading services...', 'schedula-smart-appointment-booking') }}
        </p>
        <p v-else-if="servicesError" class="flex items-center" :style="{ color: 'var(--admin-suggestion-red-text)' }">
          <i class="fas fa-exclamation-triangle mr-2"></i>{{ __( 'Error loading services:', 'schedula-smart-appointment-booking') }} {{ servicesError }}
        </p>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          <div v-for="service in allServices" :key="service.id" 
               class="p-4 rounded-lg shadow-sm content-card">
            <label class="inline-flex items-center cursor-pointer w-full">
              <input type="checkbox" :value="service.id" @change="toggleService(service)" :checked="isServiceSelected(service.id)"
                     class="form-checkbox h-5 w-5 rounded mr-3" :style="{ color: 'var(--admin-link-blue-bg)' }" />
              <div class="flex items-center space-x-2">
                <span class="font-medium" :style="{ color: 'var(--admin-card-text-color)' }">{{ service.title }}</span>
              </div>
            </label>
          </div>
        </div>
        <p v-if="!allServices.length && !loadingServices && !servicesError" class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
          <i class="fas fa-info-circle mr-2"></i>{{ __( 'No services available. Please add services first.', 'schedula-smart-appointment-booking') }}
        </p>
      </div>

      <!-- Enhanced Schedule Tab Content -->
      <div v-else-if="currentTab === 'schedule'" class="p-6 rounded-lg content-card">
        <div class="flex items-center space-x-2 mb-4">
          <i class="fas fa-calendar-week" :style="{ color: 'var(--admin-link-blue-bg)' }"></i>
          <h4 class="text-lg font-medium" :style="{ color: 'var(--admin-text-color)' }">{{ __( 'Weekly Availability', 'schedula-smart-appointment-booking') }}</h4>
        </div>
        <p v-if="loadingSchedule" class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
          <i class="fas fa-spinner fa-spin mr-2"></i>{{ __( 'Loading schedule...', 'schedula-smart-appointment-booking') }}
        </p>
        <p v-else-if="scheduleError" class="flex items-center" :style="{ color: 'var(--admin-suggestion-red-text)' }">
          <i class="fas fa-exclamation-triangle mr-2"></i>{{ __( 'Error loading schedule:', 'schedula-smart-appointment-booking') }} {{ scheduleError }}
        </p>
        <div v-else class="space-y-4 max-h-[500px] overflow-y-auto pr-2">
          <div v-for="(daySchedule, index) in schedule" :key="index" 
               class="p-4 rounded-lg shadow-sm content-card">
            <div class="flex items-center justify-between mb-3">
              <h5 class="text-md font-semibold flex items-center space-x-2" :style="{ color: 'var(--admin-card-text-color)' }">
                <div class="w-3 h-3 rounded-full" :class="getDayColor(daySchedule.day_of_week)"></div>
                <span>{{ getDayName(daySchedule.day_of_week) }}</span>
              </h5>
              
              <!-- Duplicate Options Toggle -->
              <div class="relative" v-if="daySchedule.start_time || daySchedule.end_time" :ref="el => { if (el) duplicateContainers[index] = el }">
                <button type="button" @click="toggleDuplicateOptions(index)" 
                        class="px-3 py-1 rounded-md text-xs flex items-center space-x-1" :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-input-text-color)' }">
                  <i class="fas fa-copy"></i>
                  <span>{{ __( 'Duplicate', 'schedula-smart-appointment-booking') }}</span>
                </button>
                
                <!-- Duplicate Options Dropdown -->
                <div v-if="showDuplicateOptions[index]" 
                     class="absolute right-0 top-8 z-10 rounded-md shadow-lg p-2 min-w-[150px] modal-content">
                  <p class="text-xs mb-2" :style="{ color: 'var(--admin-card-text-color)' }">{{ __( 'Copy to:', 'schedula-smart-appointment-booking') }}</p>
                  <div class="space-y-1">
                    <button v-for="(otherDay, otherIndex) in schedule" :key="otherIndex"
                            v-if="otherIndex !== index"
                            type="button" 
                            @click="duplicateScheduleToDay(index, otherIndex)"
                            class="w-full text-left px-2 py-1 text-xs rounded flex items-center space-x-2" :style="{ backgroundColor: 'transparent', color: 'var(--admin-card-text-color)' }">
                      <div class="w-2 h-2 rounded-full" :class="getDayColor(otherDay.day_of_week)"></div>
                      <span>{{ getDayName(otherDay.day_of_week) }}</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Enhanced Time Selection with Select Dropdowns -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
              <div>
                <label :for="`start-time-${index}`" class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-card-text-color)' }">{{ __( 'From:', 'schedula-smart-appointment-booking') }}</label>
                <select :id="`start-time-${index}`" v-model="daySchedule.start_time"
                        class="block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2 input-field">
                  <option value="">{{ __( 'Off', 'schedula-smart-appointment-booking') }}</option>
                  <option v-for="timeOption in timeOptions" :key="timeOption.value" :value="timeOption.value">
                    {{ timeOption.label }}
                  </option>
                </select>
              </div>
              
              <div>
                <label :for="`end-time-${index}`" class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-card-text-color)' }">{{ __( 'To:', 'schedula-smart-appointment-booking') }}</label>
                <select :id="`end-time-${index}`" v-model="daySchedule.end_time"
                        class="block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-2 input-field">
                  <option value="">{{ __( 'Off', 'schedula-smart-appointment-booking') }}</option>
                  <option v-for="timeOption in timeOptions" :key="timeOption.value" :value="timeOption.value">
                    {{ timeOption.label }}
                  </option>
                </select>
              </div>
              
              <div class="sm:col-span-2 lg:col-span-2 flex items-end">
                <button type="button" @click="openAddBreakModal(daySchedule)" 
                        :disabled="!daySchedule.start_time || !daySchedule.end_time"
                        class="px-3 py-2 rounded-md text-xs disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-1 whitespace-nowrap" :style="{ backgroundColor: 'var(--admin-link-blue-bg)', color: 'var(--admin-link-blue-text)' }">
                  <i class="fas fa-coffee text-xs"></i>
                  <span>{{ __( 'Add Break', 'schedula-smart-appointment-booking') }}</span>
                </button>
              </div>
            </div>
            
            <!-- Breaks Display -->
            <div v-if="daySchedule.breaks && daySchedule.breaks.length > 0" class="mt-4 border-t pt-3" :style="{ borderColor: 'var(--admin-border-color)' }">
              <h6 class="text-sm font-medium mb-2 flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
                <i class="fas fa-pause-circle mr-1"></i>{{ __( 'Breaks:', 'schedula-smart-appointment-booking') }}
              </h6>
              <div class="flex flex-wrap gap-2">
                <div v-for="(b, bIndex) in daySchedule.breaks" :key="bIndex" 
                     class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium break-label" :style="{ backgroundColor: 'var(--admin-suggestion-yellow-bg)', color: 'var(--admin-suggestion-yellow-text)' }">
                  <i class="fas fa-coffee mr-1 text-xs"></i>
                  {{ formatTime(b.start_time) }} - {{ formatTime(b.end_time) }} 
                  <span v-if="b.description" class="ml-1">({{ b.description }})</span>
                  <button type="button" @click="removeBreak(daySchedule, bIndex)" 
                          class="ml-2 -mr-1 h-4 w-4 flex items-center justify-center rounded-full" :style="{ backgroundColor: 'var(--admin-suggestion-yellow-bg)', color: 'var(--admin-suggestion-yellow-text)' }">
                    <i class="fas fa-times text-xs"></i>
                  </button>
                </div>
              </div>
            </div>
            <div v-else-if="daySchedule.start_time && daySchedule.end_time" class="mt-3 text-sm flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
              <i class="fas fa-info-circle mr-1"></i>{{ __( 'No breaks configured.', 'schedula-smart-appointment-booking') }}
            </div>
            <div v-else class="mt-3 text-sm flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
              <i class="fas fa-minus-circle mr-1"></i>{{ __( 'This day is off.', 'schedula-smart-appointment-booking') }}
            </div>
          </div>
        </div>
      </div>

      <!-- Enhanced Holidays Tab Content -->
      <div v-else-if="currentTab === 'holidays'" class="p-6 rounded-lg content-card">
        <div class="flex items-center space-x-2 mb-4">
          <i class="fas fa-umbrella-beach" :style="{ color: 'var(--admin-suggestion-yellow-text)' }"></i>
          <h4 class="text-lg font-medium" :style="{ color: 'var(--admin-text-color)' }">{{ __( 'Staff Holidays', 'schedula-smart-appointment-booking') }}</h4>
        </div>
        <p v-if="loadingHolidays" class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
          <i class="fas fa-spinner fa-spin mr-2"></i>{{ __( 'Loading holidays...', 'schedula-smart-appointment-booking') }}
        </p>
        <p v-else-if="holidaysError" class="flex items-center" :style="{ color: 'var(--admin-suggestion-red-text)' }">
          <i class="fas fa-exclamation-triangle mr-2"></i>{{ __( 'Error loading holidays:', 'schedula-smart-appointment-booking') }} {{ holidaysError }}
        </p>
        <div v-else class="space-y-4">
          <div v-for="(holiday, hIndex) in holidays" :key="hIndex" 
               class="p-4 rounded-lg shadow-sm content-card flex items-center gap-2">
            <i class="fas fa-calendar-times" :style="{ color: 'var(--admin-suggestion-yellow-text)' }"></i>
            <span class="font-medium" :style="{ color: 'var(--admin-card-text-color)' }">{{ formatHolidayDate(holiday) }}</span>
            <input type="text" v-model="holiday.description" :placeholder="__( 'Holiday description', 'schedula-smart-appointment-booking')" 
                   class="flex-grow text-sm rounded-md p-2 input-field" />
            <button type="button" @click="removeHoliday(hIndex)" 
                    class="p-2 rounded-md" :style="{ color: 'var(--admin-suggestion-red-text)', backgroundColor: 'transparent' }">
              <i class="fas fa-trash"></i>
            </button>
          </div>
          <button type="button" @click="openAddHolidayModal" 
                  class="px-4 py-2 rounded-md text-sm flex items-center space-x-2" :style="{ backgroundColor: 'var(--admin-suggestion-yellow-bg)', color: 'var(--admin-suggestion-yellow-text)' }">
            <i class="fas fa-plus"></i>
            <span>{{ __( 'Add Holiday', 'schedula-smart-appointment-booking') }}</span>
          </button>
          <p v-if="!holidays.length && !loadingHolidays && !holidaysError" class="flex items-center" :style="{ color: 'var(--admin-card-text-color)' }">
            <i class="fas fa-info-circle mr-2"></i>{{ __( 'No holidays added yet.', 'schedula-smart-appointment-booking') }}
          </p>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="mt-6 flex justify-end space-x-3 pt-4" :style="{ borderColor: 'var(--admin-border-color)' }">
        <button type="button" @click="emit('cancel')"
                class="px-6 py-2 rounded-lg shadow-sm text-sm font-medium" :style="{ backgroundColor: 'var(--admin-button-third-bg)', color: 'var(--admin-button-secondary-text)' }">
          <i class="fas fa-times mr-2"></i>{{ __( 'Cancel', 'schedula-smart-appointment-booking') }}
        </button>
        <button type="submit"
                :disabled="isSaving"
                class="px-6 py-2 rounded-lg shadow-sm text-sm font-medium inline-flex items-center justify-center" :style="{ backgroundColor: 'var(--admin-link-indigo-bg)', color: 'var(--admin-link-indigo-text)' }">
          <svg v-if="isSaving" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <i v-else class="fas fa-save mr-2"></i>
          {{ isSaving ? __( 'Saving...', 'schedula-smart-appointment-booking') : (isEditing ? __( 'Update Staff', 'schedula-smart-appointment-booking') : __( 'Add Staff', 'schedula-smart-appointment-booking')) }}
        </button>
      </div>
    </form>
  </div>

  <!-- Enhanced Break Input Modal -->
  <transition name="modal-fade">
    <div v-if="showBreakModal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="closeAddBreakModal">
      <div class="rounded-lg shadow-xl w-full max-w-md mx-4 my-8 p-6 relative modal-content" @click.stop>
        <div class="flex items-center space-x-2 mb-4">
          <i class="fas fa-coffee" :style="{ color: 'var(--admin-link-blue-bg)' }"></i>
          <h4 class="text-lg font-semibold" :style="{ color: 'var(--admin-text-color)' }">{{ __( 'Add Break', 'schedula-smart-appointment-booking') }}</h4>
        </div>
        
        <!-- Break Preview -->
        <div v-if="currentDayScheduleForBreak" class="mb-4 p-3 rounded-lg" :style="{ backgroundColor: 'var(--admin-input-border-color)', borderColor: 'var(--admin-border-color)' }">
          <div class="text-sm mb-2" :style="{ color: 'var(--admin-card-text-color)' }">
            <i class="fas fa-calendar-day mr-1"></i>{{ getDayName(currentDayScheduleForBreak.day_of_week) }} 
            ({{ formatTime(currentDayScheduleForBreak.start_time) }} - {{ formatTime(currentDayScheduleForBreak.end_time) }})
          </div>
          <div class="text-xs" :style="{ color: 'var(--admin-card-text-color)' }">
            <i class="fas fa-info-circle mr-1"></i>{{ __( 'Break must be within working hours', 'schedula-smart-appointment-booking') }}
          </div>
        </div>

        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="break-start-time" class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-card-text-color)' }">
                <i class="fas fa-play mr-1" :style="{ color: 'var(--admin-input-text-color)' }"></i>{{ __( 'Start Time', 'schedula-smart-appointment-booking') }}
              </label>
              <select id="break-start-time" v-model="newBreak.start_time" required
                      class="block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 input-field">
                <option value="">{{ __( 'Select time', 'schedula-smart-appointment-booking') }}</option>
                <option v-for="timeOption in timeOptions" :key="timeOption.value" :value="timeOption.value">
                  {{ timeOption.label }}
                </option>
              </select>
            </div>
            <div>
              <label for="break-end-time" class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-card-text-color)' }">
                <i class="fas fa-stop mr-1" :style="{ color: 'var(--admin-input-text-color)' }"></i>{{ __( 'End Time', 'schedula-smart-appointment-booking') }}
              </label>
              <select id="break-end-time" v-model="newBreak.end_time" required
                      class="block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 input-field">
                <option value="">{{ __( 'Select time', 'schedula-smart-appointment-booking') }}</option>
                <option v-for="timeOption in timeOptions" :key="timeOption.value" :value="timeOption.value">
                  {{ timeOption.label }}
                </option>
              </select>
            </div>
          </div>
          <div>
            <div>
            <label for="break-description" class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-card-text-color)' }">
              <i class="fas fa-comment mr-1" :style="{ color: 'var(--admin-input-text-color)' }"></i>{{ __( 'Description (Optional)', 'schedula-smart-appointment-booking') }}
            </label>
            <input type="text" id="break-description" v-model="newBreak.description" :placeholder="__( 'e.g., Lunch break', 'schedula-smart-appointment-booking')"
                   class="block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 input-field" />
          </div>
          </div>
          <div v-if="breakModalError" class="px-4 py-3 rounded relative" role="alert" :style="{ backgroundColor: 'var(--admin-suggestion-red-bg)', borderColor: 'var(--admin-border-color)', color: 'var(--admin-suggestion-red-text)' }">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <span class="block sm:inline">{{ breakModalError }}</span>
          </div>
        </div>
        <div class="mt-6 flex justify-end space-x-3">
          <button type="button" @click="closeAddBreakModal"
                  class="px-4 py-2 rounded-md shadow-sm text-sm font-medium" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)' }">
            <i class="fas fa-times mr-1"></i>{{ __( 'Cancel', 'schedula-smart-appointment-booking') }}
          </button>
          <button type="button" @click="saveBreak"
                  class="px-4 py-2 rounded-md shadow-sm text-sm font-medium" :style="{ backgroundColor: 'var(--admin-link-blue-bg)', color: 'var(--admin-link-blue-text)' }">
            <i class="fas fa-plus mr-1"></i>{{ __( 'Add Break', 'schedula-smart-appointment-booking') }}
          </button>
        </div>
      </div>
    </div>
  </transition>

  <!-- Enhanced Holiday Input Modal with Flowbite Datepicker -->
  <transition name="modal-fade">
    <div v-if="showHolidayModal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="closeAddHolidayModal">
      <div class="rounded-lg shadow-xl w-full max-w-md mx-4 my-8 p-6 relative modal-content" @click.stop>
        <div class="flex items-center space-x-2 mb-4">
          <i class="fas fa-umbrella-beach" :style="{ color: 'var(--admin-suggestion-yellow-text)' }"></i>
          <h4 class="text-lg font-semibold" :style="{ color: 'var(--admin-text-color)' }">{{ __( 'Add Holiday Period', 'schedula-smart-appointment-booking') }}</h4>
        </div>
        <div class="space-y-4">
          <!-- Flowbite Date Range Picker -->
          <div ref="holidayDateRangeContainer" class="flex flex-col space-y-2">
            <label class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-card-text-color)' }">{{ __( 'Holiday Period', 'schedula-smart-appointment-booking') }}</label>
            <div class="flex items-center space-x-2">
              <div class="relative flex-1">
                <input name="start" type="text" 
                       class="text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 input-field" 
                       :placeholder="__( 'Start date', 'schedula-smart-appointment-booking')" readonly>
              </div>
              <span :style="{ color: 'var(--admin-card-text-color)' }">{{ __( 'to', 'schedula-smart-appointment-booking') }}</span>
              <div class="relative flex-1">
                <input name="end" type="text" 
                       class="text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 input-field" 
                       :placeholder="__( 'End date', 'schedula-smart-appointment-booking')" readonly>
              </div>
            </div>
          </div>
          
          <div>
            <label for="holiday-description" class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-card-text-color)' }">
              <i class="fas fa-comment mr-1" :style="{ color: 'var(--admin-input-text-color)' }"></i>{{ __( 'Description (Optional)', 'schedula-smart-appointment-booking') }}
            </label>
            <input type="text" id="holiday-description" v-model="newHoliday.description" :placeholder="__( 'e.g., Summer vacation', 'schedula-smart-appointment-booking')"
                   class="block w-full rounded-md shadow-sm focus:border-yellow-500 focus:ring-yellow-500 sm:text-sm p-2 input-field" />
          </div>
          <div v-if="holidayModalError" class="px-4 py-3 rounded relative" role="alert" :style="{ backgroundColor: 'var(--admin-suggestion-red-bg)', borderColor: 'var(--admin-border-color)', color: 'var(--admin-suggestion-red-text)' }">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <span class="block sm:inline">{{ holidayModalError }}</span>
          </div>
        </div>
        <div class="mt-6 flex justify-end space-x-3">
          <button type="button" @click="closeAddHolidayModal"
                  class="px-4 py-2 rounded-md shadow-sm text-sm font-medium" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)' }">
            <i class="fas fa-times mr-1"></i>{{ __( 'Cancel', 'schedula-smart-appointment-booking') }}
          </button>
          <button type="button" @click="saveHoliday"
                  class="px-4 py-2 rounded-md shadow-sm text-sm font-medium" :style="{ backgroundColor: 'var(--admin-suggestion-yellow-bg)', color: 'var(--admin-suggestion-yellow-text)' }">
            <i class="fas fa-plus mr-1"></i>{{ __( 'Add Holiday', 'schedula-smart-appointment-booking') }}
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>
<script setup>
import { __, sprintf } from '@wordpress/i18n';
import { ref, onMounted, watch, nextTick, reactive, computed, onUnmounted } from 'vue';
import { DateRangePicker } from 'flowbite-datepicker';
import BaseInput from '../common/BaseInput.vue';
import BaseSelect from '../common/BaseSelect.vue';
import BaseTextarea from '../common/BaseTextarea.vue';
import { staffApi, servicesCategoriesApi, settingsApi } from '../../api.js';
import { useToast } from '../../composables/useToast.js'; // Import useToast
import { useGlobalSettings } from '../../composables/useGlobalSettings.js';
import BasePhoneInput from '../common/BasePhoneInput.vue';

const props = defineProps({
  staff: {
    type: Object,
    default: null, 
  },
  isSaving: { // NEW PROP: To receive saving state from parent
    type: Boolean,
    default: false,
  }
});

const emit = defineEmits(['submit', 'cancel']);

const { success, error } = useToast(); // Use the toast composable

const { formatTime } = useGlobalSettings();

// Form state
const form = ref({
  id: null,
  name: '',
  email: '',
  phone: '',
  description: '',
  status: 'active',
  image_url: '',
});

const isEditing = ref(false);
const currentTab = ref('general'); 

// Global settings state - crucial for defaults
const globalSettings = ref({
  timeSlotLength: 30, // Default to 30 minutes (fallback)
  enableDefaultBusinessSchedule: false,
  defaultBusinessSchedule: [],
  enableDefaultBusinessHolidays: false,
  defaultBusinessHolidays: [],
});

// Time options for select dropdowns (now globally managed)
const timeOptions = ref([]);

const showDuplicateOptions = ref({});
const duplicateContainers = ref([]);

const closeAllDuplicateOptions = () => {
  showDuplicateOptions.value = {};
};

const handleClickOutside = (event) => {
  const isClickInside = duplicateContainers.value.some(container => container && container.contains(event.target));
  if (!isClickInside) {
    closeAllDuplicateOptions();
  }
};

// Generate time options based on globalSettings.timeSlotLength
const generateTimeOptions = (intervalMinutes) => {
  const options = [];
  for (let hour = 0; hour < 24; hour++) {
    for (let minute = 0; minute < 60; minute += intervalMinutes) {
      const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
      const displayTime = formatTime(timeString);
      options.push({ value: timeString, label: displayTime });
    }
  }
  return options;
};

// Data for Services Tab
const allServices = ref([]);
const loadingServices = ref(false);
const servicesError = ref(null);
const selectedStaffServices = ref(new Map()); 

// Data for Schedule Tab
const daysOfWeek = [__('Sunday', 'schedula-smart-appointment-booking'), __('Monday', 'schedula-smart-appointment-booking'),
 __('Tuesday', 'schedula-smart-appointment-booking'), __('Wednesday', 'schedula-smart-appointment-booking'),
 __('Thursday', 'schedula-smart-appointment-booking'), __('Friday', 'schedula-smart-appointment-booking'), __('Saturday', 'schedula-smart-appointment-booking')];
const schedule = ref(
  Array.from({ length: 7 }, (_, i) => ({
    id: null, 
    day_of_week: i,
    start_time: '', 
    end_time: '',   
    breaks: [],     
  }))
);
const loadingSchedule = ref(false);
const scheduleError = ref(null);

// Break Modal State
const showBreakModal = ref(false);
const newBreak = ref({ start_time: '', end_time: '', description: '' });
const currentDayScheduleForBreak = ref(null); 
const breakModalError = ref(null);

// Data for Holidays Tab
const holidays = ref([]);
const loadingHolidays = ref(false);
const holidaysError = ref(null);

// Holiday Modal State with Flowbite
const showHolidayModal = ref(false);
const newHoliday = ref({ id: null, start_date: '', end_date: '', description: '' });
const holidayModalError = ref(null);
const holidayDateRangeContainer = ref(null);
let flowbiteHolidayDatepickerInstance = null;

const openMediaUploader = () => {
  if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
    error(__( 'The WordPress media uploader is not available.', 'schedula-smart-appointment-booking'));
    return;
  }

  const mediaUploader = wp.media({
    title: __( 'Select or Upload Staff Image', 'schedula-smart-appointment-booking'),
    button: {
      text: __( 'Use this image', 'schedula-smart-appointment-booking')
    },
    multiple: false
  });

  mediaUploader.on('select', () => {
    const attachment = mediaUploader.state().get('selection').first().toJSON();
    form.value.image_url = attachment.url;
    success(__( 'Image selected.', 'schedula-smart-appointment-booking'));
  });

  mediaUploader.open();
};

const removeImage = () => {
  form.value.image_url = '';
  success(__( 'Image removed.', 'schedula-smart-appointment-booking'));
};

const resetForm = () => {
  form.value = {
    id: null,
    name: '',
    email: '',
    phone: '',
    description: '',
    status: 'active',
    image_url: '',
  };
  selectedStaffServices.value = new Map(); 
  schedule.value = Array.from({ length: 7 }, (_, i) => ({ 
    id: null,
    day_of_week: i,
    start_time: '',
    end_time: '',
    breaks: [],
  }));
  holidays.value = []; 
  currentTab.value = 'general'; 
  showDuplicateOptions.value = {};
};

// --- Helper Functions ---
const getDayName = (dayIndex) => {
  return daysOfWeek[dayIndex];
};

const getDayColor = (dayIndex) => {
  const colors = [
    'bg-red-400',    // Sunday
    'bg-blue-400',   // Monday
    'bg-green-400',  // Tuesday
    'bg-yellow-400', // Wednesday
    'bg-purple-400', // Thursday
    'bg-pink-400',   // Friday
    'bg-indigo-400'  // Saturday
  ];
  return colors[dayIndex] || 'bg-gray-400';
};



const formatHolidayDate = (holiday) => {
  if (holiday.start_date && holiday.end_date && holiday.start_date !== holiday.end_date) {
      const startDate = new Date(holiday.start_date + 'T00:00:00');
      const endDate = new Date(holiday.end_date + 'T00:00:00');
      const options = { year: 'numeric', month: 'short', day: 'numeric' };
      return `${startDate.toLocaleDateString('en-US', options)} - ${endDate.toLocaleDateString('en-US', options)}`;
  } else if (holiday.holiday_date) { // This might be from existing data if single day was stored as 'holiday_date'
      const date = new Date(holiday.holiday_date + 'T00:00:00');
      const options = { year: 'numeric', month: 'short', day: 'numeric' };
      return date.toLocaleDateString('en-US', options);
  } else if (holiday.start_date) { // For cases where start_date is treated as single day
      const date = new Date(holiday.start_date + 'T00:00:00');
      const options = { year: 'numeric', month: 'short', day: 'numeric' };
      return date.toLocaleDateString('en-US', options);
  }
  return __( 'Invalid Date', 'schedula-smart-appointment-booking');
};

// --- Duplication Functions ---
const toggleDuplicateOptions = (dayIndex) => {
  const wasOpen = showDuplicateOptions.value[dayIndex];
  closeAllDuplicateOptions();
  if (!wasOpen) {
    showDuplicateOptions.value[dayIndex] = true;
  }
};

const duplicateScheduleToDay = (fromDayIndex, toDayIndex) => {
  const fromDay = schedule.value[fromDayIndex];
  const toDay = schedule.value[toDayIndex];
  
  toDay.start_time = fromDay.start_time;
  toDay.end_time = fromDay.end_time;
  toDay.breaks = JSON.parse(JSON.stringify(fromDay.breaks)); // Deep copy breaks
  
  showDuplicateOptions.value[fromDayIndex] = false;
};

// --- API Calls ---
// Function to fetch global settings (including timeSlotLength, default schedule/holidays)
const fetchGlobalSettings = async () => {
  try {
    const response = await settingsApi.getGeneralSettings();
    globalSettings.value = response.data;
    // After fetching settings, generate time options based on the received timeSlotLength
    timeOptions.value = generateTimeOptions(globalSettings.value.timeSlotLength);
    console.log('Fetched global settings in EmployeForm:', globalSettings.value);
  } catch (err) {
    console.error('Error fetching global settings in EmployeForm:', err);
    // Fallback to default 30-minute intervals if settings API fails
    timeOptions.value = generateTimeOptions(30); 
    error(__( 'Failed to load global settings for staff form.', 'schedula-smart-appointment-booking')); // Use toast for error
  }
};


const fetchAllStaffData = async (staffId) => {
  await Promise.all([
    fetchAllServices(), // Moved here to ensure services are loaded before staff services
    fetchStaffAssociatedServices(staffId),
    fetchStaffSchedule(staffId),
    fetchStaffHolidays(staffId)
  ]);
  console.log('All staff data fetched for staffId:', staffId);
};

const fetchAllServices = async () => {
  if (allServices.value.length > 0) return;
  loadingServices.value = true;
  servicesError.value = null;
  try {
    const response = await servicesCategoriesApi.getServices({ per_page: 999, page: 1 }); 
    if (response.data && Array.isArray(response.data.services)) {
      allServices.value = response.data.services;
    } else if (Array.isArray(response.data)) {
      allServices.value = response.data;
    } else {
      allServices.value = [];
    }
    console.log('Fetched all services:', allServices.value);
  } catch (err) {
    servicesError.value = err.response?.data?.message || err.message || __( 'Failed to fetch all services.', 'schedula-smart-appointment-booking');
    console.error('Error fetching all services:', err);
    error(__( 'Failed to load services.', 'schedula-smart-appointment-booking')); // Use toast for error
  } finally {
    loadingServices.value = false;
  }
};

const fetchStaffAssociatedServices = async (staffId) => {
  if (!staffId) {
    selectedStaffServices.value = new Map();
    return;
  }
  try {
    const response = await staffApi.getStaffServices(staffId); 
    selectedStaffServices.value = new Map(
      response.data.map(s => [s.service_id, { 
        price: s.price, 
        duration: s.duration ? Number(s.duration) : 0, 
        staff_service_id: s.id 
      }])
    );
    console.log('Fetched staff services:', response.data);
  } catch (err) {
    console.error('Error fetching staff services:', err.response?.data?.message || err.message);
    selectedStaffServices.value = new Map();
    error(__( 'Failed to load staff associated services.', 'schedula-smart-appointment-booking')); // Use toast for error
  }
};

const fetchStaffSchedule = async (staffId) => {
  loadingSchedule.value = true;
  scheduleError.value = null;
  let fetchedSchedule = [];

  if (staffId) {
    try {
      const response = await staffApi.getStaffSchedule(staffId); 
      fetchedSchedule = response.data;
      console.log('Fetched staff schedule:', fetchedSchedule);
      
      await Promise.all(fetchedSchedule.map(async (item) => {
        if (item.id) {
          item.breaks = await fetchScheduleItemBreaks(item.id);
        } else {
          item.breaks = [];
        }
      }));
      console.log('Staff schedule with breaks:', fetchedSchedule);
    } catch (err) {
      scheduleError.value = err.response?.data?.message || err.message || __( 'Failed to fetch staff schedule.', 'schedula-smart-appointment-booking');
      console.error('Error fetching staff schedule:', err);
      error(__( 'Failed to load staff schedule.', 'schedula-smart-appointment-booking')); // Use toast for error
    }
  }

  // Determine if we should use default business schedule
  const useDefaultSchedule = 
    !staffId || // Always use default for new staff
    (fetchedSchedule.length === 0 && globalSettings.value.enableDefaultBusinessSchedule) ||
    (fetchedSchedule.every(s => !s.start_time && !s.end_time && (!s.breaks || s.breaks.length === 0)) && globalSettings.value.enableDefaultBusinessSchedule); 
    // If staff has entries but they're all "off" or empty, and default is enabled, apply default.

  let finalScheduleData;
  if (useDefaultSchedule && globalSettings.value.enableDefaultBusinessSchedule) {
    finalScheduleData = JSON.parse(JSON.stringify(globalSettings.value.defaultBusinessSchedule)); // Deep copy
    // Ensure IDs are null for new staff or if applying template
    finalScheduleData.forEach(day => day.id = null);
    console.log('Applying default business schedule:', finalScheduleData);
  } else if (staffId) { // If editing existing staff, use their fetched schedule
    finalScheduleData = fetchedSchedule;
  } else { // New staff, no default enabled, create empty schedule
    finalScheduleData = Array.from({ length: 7 }, (_, i) => ({
      id: null, day_of_week: i, start_time: '', end_time: '', breaks: [],
    }));
  }

  const newSchedule = Array.from({ length: 7 }, (_, i) => {
    const existingDay = finalScheduleData.find(item => Number(item.day_of_week) === i);
    if (existingDay) {
      return {
        ...existingDay,
        start_time: existingDay.start_time ? existingDay.start_time.slice(0, 5) : '',
        end_time: existingDay.end_time ? existingDay.end_time.slice(0, 5) : '',
        breaks: existingDay.breaks || [],
      };
    }
    return { id: null, day_of_week: i, start_time: '', end_time: '', breaks: [] };
  });
  schedule.value = newSchedule;
  loadingSchedule.value = false;
};


const fetchScheduleItemBreaks = async (scheduleItemId) => {
  if (!scheduleItemId) return [];
  try {
      const response = await staffApi.getScheduleItemBreaks(scheduleItemId); 
      console.log(`Fetched breaks for schedule item ${scheduleItemId}:`, response.data);
      return response.data;
  } catch (err) {
      console.error(`Error fetching breaks for schedule item ${scheduleItemId}:`, err.response?.data?.message || err.message);
      error(sprintf(__( 'Failed to load breaks for schedule item %d.', 'schedula-smart-appointment-booking'), scheduleItemId)); // Use toast for error
      return [];
  }
};

const fetchStaffHolidays = async (staffId) => {
  loadingHolidays.value = true;
  holidaysError.value = null;
  let fetchedHolidays = [];

  if (staffId) {
    try {
      const response = await staffApi.getHolidays({ staff_id: staffId }); 
      fetchedHolidays = response.data;
      console.log('Fetched staff holidays:', fetchedHolidays);
    } catch (err) {
      holidaysError.value = err.response?.data?.message || err.message || __( 'Failed to fetch staff holidays.', 'schedula-smart-appointment-booking');
      console.error('Error fetching staff holidays:', err);
      error(__( 'Failed to load staff holidays.', 'schedula-smart-appointment-booking')); // Use toast for error
    }
  }

  // Determine if we should use default business holidays
  if (!staffId && globalSettings.value.enableDefaultBusinessHolidays) {
    holidays.value = JSON.parse(JSON.stringify(globalSettings.value.defaultBusinessHolidays)); // Deep copy
    holidays.value.forEach(h => h.id = null); // Ensure IDs are null for new entries
    console.log('Applying default business holidays:', holidays.value);
  } else if (staffId) { // Existing staff, use their fetched holidays
    holidays.value = fetchedHolidays;
  } else { // New staff, no default enabled, start empty
    holidays.value = [];
  }
  loadingHolidays.value = false;
};

// --- Services Tab Logic ---
const isServiceSelected = (serviceId) => {
  return selectedStaffServices.value.has(serviceId);
};

const areAllServicesSelected = computed(() => {
  return allServices.value.length > 0 && selectedStaffServices.value.size === allServices.value.length;
});

const areSomeServicesSelected = computed(() => {
  return selectedStaffServices.value.size > 0 && selectedStaffServices.value.size < allServices.value.length;
});

const toggleAllServices = () => {
  if (areAllServicesSelected.value) {
    // Deselect all
    selectedStaffServices.value.clear();
  } else {
    // Select all
    allServices.value.forEach(service => {
      if (!selectedStaffServices.value.has(service.id)) {
        selectedStaffServices.value.set(service.id, {
          price: service.price,
          duration: service.duration ? Number(service.duration) : 0,
          staff_service_id: null
        });
      }
    });
  }
};

const toggleService = (service) => {
  if (isServiceSelected(service.id)) {
    selectedStaffServices.value.delete(service.id);
  } else {
    selectedStaffServices.value.set(service.id, {
      price: service.price, 
      duration: service.duration ? Number(service.duration) : 0, 
      staff_service_id: null 
    });
  }
};

// --- Schedule Tab Logic (Break Modal Integration) ---
const openAddBreakModal = (daySchedule) => {
  if (!daySchedule.start_time || !daySchedule.end_time) {
    error(__( 'Please set working hours for this day before adding breaks.', 'schedula-smart-appointment-booking')); // Use toast for error
    return;
  }
  currentDayScheduleForBreak.value = daySchedule;
  newBreak.value = { start_time: '', end_time: '', description: '' }; 
  breakModalError.value = null;
  showBreakModal.value = true;
};

const closeAddBreakModal = () => {
  showBreakModal.value = false;
  currentDayScheduleForBreak.value = null;
  newBreak.value = { start_time: '', end_time: '', description: '' };
  breakModalError.value = null;
};

const saveBreak = () => {
  breakModalError.value = null;
  if (!newBreak.value.start_time || !newBreak.value.end_time) {
    breakModalError.value = __( 'Break start and end times are required.', 'schedula-smart-appointment-booking');
    return;
  }

  if (newBreak.value.start_time >= newBreak.value.end_time) {
    breakModalError.value = __( 'Break end time must be after start time.', 'schedula-smart-appointment-booking');
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
      breakModalError.value = __( 'This break overlaps with an existing break.', 'schedula-smart-appointment-booking');
      return;
  }

  const dayStartTime = new Date(`2000-01-01T${currentDayScheduleForBreak.value.start_time}`);
  const dayEndTime = new Date(`2000-01-01T${currentDayScheduleForBreak.value.end_time}`);

  if (newBreakStart < dayStartTime || newBreakEnd > dayEndTime) {
      breakModalError.value = __( 'Break must be within the set working hours for this day.', 'schedula-smart-appointment-booking');
      return;
  }

  currentDayScheduleForBreak.value.breaks.push({ ...newBreak.value });
  success(__( 'Break added successfully!', 'schedula-smart-appointment-booking')); // Use toast for success
  closeAddBreakModal();
};

const removeBreak = (daySchedule, index) => {
  daySchedule.breaks.splice(index, 1);
  success(__( 'Break removed successfully!', 'schedula-smart-appointment-booking')); // Use toast for success
};

// --- Enhanced Holidays Tab Logic with Flowbite Datepicker ---
const openAddHolidayModal = async () => {
  newHoliday.value = { id: null, start_date: '', end_date: '', description: '' };
  holidayModalError.value = null;
  showHolidayModal.value = true;
  await nextTick();
  
  if (holidayDateRangeContainer.value && !flowbiteHolidayDatepickerInstance) {
    flowbiteHolidayDatepickerInstance = new DateRangePicker(holidayDateRangeContainer.value, {
      format: 'yyyy-mm-dd',
      autohide: true,
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
      flowbiteHolidayDatepickerInstance.setDates(null, null);
  }
};

const closeAddHolidayModal = () => {
  showHolidayModal.value = false;
  newHoliday.value = { id: null, start_date: '', end_date: '', description: '' };
  holidayModalError.value = null;
  if (flowbiteHolidayDatepickerInstance) {
      flowbiteHolidayDatepickerInstance.destroy();
      flowbiteHolidayDatepickerInstance = null;
  }
};

const saveHoliday = () => {
  holidayModalError.value = null;
  if (!newHoliday.value.start_date || !newHoliday.value.end_date) {
    holidayModalError.value = __( 'Holiday start and end dates are required.', 'schedula-smart-appointment-booking');
    return;
  }

  const startDate = new Date(newHoliday.value.start_date);
  const endDate = new Date(newHoliday.value.end_date);

  if (startDate > endDate) {
    holidayModalError.value = __( 'Holiday end date must be after or on the start date.', 'schedula-smart-appointment-booking');
    return;
  }
  
  // Check for overlaps with existing holidays
  const hasOverlap = holidays.value.some(existingHoliday => {
      const existingStart = new Date(existingHoliday.start_date);
      const existingEnd = new Date(existingHoliday.end_date);

      // Check for overlap: (StartA <= EndB) && (EndA >= StartB)
      return (startDate <= existingEnd && endDate >= existingStart);
  });

  if (hasOverlap) {
      holidayModalError.value = __( 'This holiday period overlaps with an existing holiday.', 'schedula-smart-appointment-booking');
      return;
  }

  holidays.value.push({ ...newHoliday.value });
  success(__( 'Holiday added successfully!', 'schedula-smart-appointment-booking')); // Use toast for success
  closeAddHolidayModal();
};

const removeHoliday = (index) => {
  holidays.value.splice(index, 1);
  success(__( 'Holiday removed successfully!', 'schedula-smart-appointment-booking')); // Use toast for success
};

// --- Form Submission ---
const submitForm = async () => {
  console.log('EmployeForm: submitForm triggered.');

  if (!form.value.name || !form.value.name.trim()) {
    error(__( 'Please enter a staff member name.', 'schedula-smart-appointment-booking')); // Use toast for error
    currentTab.value = 'general';
    console.log('EmployeForm: Validation failed: Name is required.');
    return;
  }

  if (!form.value.email || !form.value.email.trim()) {
    error(__( 'Please enter a staff member email address.', 'schedula-smart-appointment-booking')); // Use toast for error
    currentTab.value = 'general';
    console.log('EmployeForm: Validation failed: Email is required.');
    return;
  }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(form.value.email.trim())) {
    error(__( 'Please enter a valid email address.', 'schedula-smart-appointment-booking')); // Use toast for error
    currentTab.value = 'general';
    console.log('EmployeForm: Validation failed: Invalid email format.');
    return;
  }

  for (const day of schedule.value) {
      if ((day.start_time && !day.end_time) || (!day.start_time && day.end_time)) {
          error(sprintf(__( 'Please provide both start and end times for %s or leave both empty.', 'schedula-smart-appointment-booking'), getDayName(day.day_of_week))); // Use toast for error
          currentTab.value = 'schedule';
          console.log(`EmployeForm: Validation failed: Incomplete times for ${getDayName(day.day_of_week)}.`);
          return;
      }
      if (day.start_time && day.end_time && day.start_time >= day.end_time) {
          error(sprintf(__( 'For %s, start time must be before end time.', 'schedula-smart-appointment-booking'), getDayName(day.day_of_week))); // Use toast for error
          currentTab.value = 'schedule';
          console.log(`EmployeForm: Validation failed: Start time after end time for ${getDayName(day.day_of_week)}.`);
          return;
      }
      for (const b of day.breaks) {
          if (!b.start_time || !b.end_time) {
              error(sprintf(__( 'Please provide both start and end times for all breaks on %s.', 'schedula-smart-appointment-booking'), getDayName(day.day_of_week))); // Use toast for error
              currentTab.value = 'schedule';
              console.log(`EmployeForm: Validation failed: Incomplete break times for ${getDayName(day.day_of_week)}.`);
              return;
          }
          if (b.start_time >= b.end_time) {
              error(sprintf(__( "For %s, a break's start time must be before its end time.", 'schedula-smart-appointment-booking'), getDayName(day.day_of_week))); // Use toast for error
              currentTab.value = 'schedule';
              console.log(`EmployeForm: Validation failed: Break start time after end time for ${getDayName(day.day_of_week)}.`);
              return;
          }
          if (day.start_time && day.end_time) {
              const dayStartTime = new Date(`2000-01-01T${day.start_time}`);
              const dayEndTime = new Date(`2000-01-01T${day.end_time}`);
              const breakStartTime = new Date(`2000-01-01T${b.start_time}`);
              const breakEndTime = new Date(`2000-01-01T${b.end_time}`);

              if (breakStartTime < dayStartTime || breakEndTime > dayEndTime) {
                  error(sprintf(__( "For %s, a break must be within the day's working hours (%s-%s).", 'schedula-smart-appointment-booking'), getDayName(day.day_of_week), day.start_time, day.end_time)); // Use toast for error
                  currentTab.value = 'schedule';
                  console.log(`EmployeForm: Validation failed: Break outside working hours for ${getDayName(day.day_of_week)}.`);
                  return;
              }
          }
      }
  }

  for (const holiday of holidays.value) {
      if (!holiday.start_date || !holiday.end_date) {
          error(__( 'Please provide both start and end dates for all holidays.', 'schedula-smart-appointment-booking')); // Use toast for error
          currentTab.value = 'holidays';
          console.log('EmployeForm: Validation failed: Incomplete holiday dates.');
          return;
      }
      const startDate = new Date(holiday.start_date);
      const endDate = new Date(holiday.end_date);
      if (startDate > endDate) {
          error(__( 'Holiday end date must be after or on the start date.', 'schedula-smart-appointment-booking')); // Use toast for error
          currentTab.value = 'holidays';
          console.log('EmployeForm: Validation failed: Holiday end date before start date.');
          return;
      }
  }

  const validSchedule = schedule.value
    .filter(day => day.start_time && day.end_time)
    .map(day => ({
      id: day.id,
      day_of_week: day.day_of_week,
      start_time: day.start_time,
      end_time: day.end_time,
      breaks: day.breaks,
    }));

  const cleanGeneralData = {
    name: form.value.name.toString().trim(),
    email: form.value.email.toString().trim().toLowerCase(),
    phone: form.value.phone ? form.value.phone.toString().trim() : '',
    image_url: form.value.image_url || '',
    description: form.value.description ? form.value.description.toString().trim() : '',
    status: form.value.status || 'active'
  };

  if (form.value.id) {
    cleanGeneralData.id = form.value.id;
  }
  
  console.log('EmployeForm: Validation passed. Emitting submit event:', {
    general: cleanGeneralData,
    services: Array.from(selectedStaffServices.value.entries()).map(([service_id, data]) => ({
      service_id,
      price: data.price,
      duration: data.duration,
      staff_service_id: data.staff_service_id 
    })),
    schedule: validSchedule,
    holidays: holidays.value.map(h => ({ 
        id: h.id,
        staff_id: h.staff_id,
        holiday_date: h.start_date === h.end_date ? h.start_date : null, // Store as single date if applicable
        start_date: h.start_date,
        end_date: h.end_date,
        description: h.description,
    })),
  });

  emit('submit', { 
    general: cleanGeneralData,
    services: Array.from(selectedStaffServices.value.entries()).map(([service_id, data]) => ({
      service_id,
      price: data.price,
      duration: data.duration,
      staff_service_id: data.staff_service_id 
    })),
    schedule: validSchedule,
    holidays: holidays.value.map(h => ({ 
        id: h.id,
        staff_id: h.staff_id,
        holiday_date: h.start_date === h.end_date ? h.start_date : null, // Store as single date if applicable
        start_date: h.start_date,
        end_date: h.end_date,
        description: h.description,
    })),
  });
};

// --- Watchers and Lifecycle Hooks ---
watch(() => props.staff, async (newVal) => {
  console.log("Employee Form has received this staff data for editing:", newVal);
  if (newVal) {
    isEditing.value = true;
    form.value.id = newVal.id;
    form.value.name = newVal.name ? newVal.name.toString() : '';
    form.value.email = newVal.email ? newVal.email.toString() : '';
    form.value.phone = newVal.phone ? newVal.phone.toString() : '';
    form.value.image_url = newVal.image_url || '';
    form.value.description = newVal.description ? newVal.description.toString() : '';
    form.value.status = newVal.status || 'active';
    currentTab.value = 'general'; 
    await fetchAllStaffData(newVal.id);
  } else {
    resetForm();
    isEditing.value = false;
    // For new staff, if defaults are enabled, apply them after reset
    if (globalSettings.value.enableDefaultBusinessSchedule) {
      schedule.value = JSON.parse(JSON.stringify(globalSettings.value.defaultBusinessSchedule));
      schedule.value.forEach(day => day.id = null); // Ensure IDs are null for new template entries
    }
    if (globalSettings.value.enableDefaultBusinessHolidays) {
      holidays.value = JSON.parse(JSON.stringify(globalSettings.value.defaultBusinessHolidays));
      holidays.value.forEach(h => h.id = null); // Ensure IDs are null for new template entries
    }
    // For new staff, also fetch all services so they can be selected
    fetchAllServices();
  }
}, { immediate: true });

// Watch for holiday modal visibility to manage datepicker instance
watch(showHolidayModal, async (newVal) => {
  if (newVal) {
      await nextTick();
      if (holidayDateRangeContainer.value && !flowbiteHolidayDatepickerInstance) {
          flowbiteHolidayDatepickerInstance = new DateRangePicker(holidayDateRangeContainer.value, {
              format: 'yyyy-mm-dd',
              autohide: true,
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
          flowbiteHolidayDatepickerInstance.setDates(null, null);
      }
  } else {
      if (flowbiteHolidayDatepickerInstance) {
          flowbiteHolidayDatepickerInstance.destroy();
          flowbiteHolidayDatepickerInstance = null;
      }
  }
});

onMounted(async () => {
  document.addEventListener('click', handleClickOutside);
  // 1. Fetch global settings FIRST (crucial for defaults)
  await fetchGlobalSettings(); 
  
  // 2. Then fetch all services (needed regardless of staff or new/edit mode)
  // Moved fetchAllServices from here to fetchAllStaffData to ensure it runs
  // before staff-specific services are fetched, but after global settings.

  // 3. Then fetch staff-specific data or apply defaults
  if (props.staff) {
    await fetchAllStaffData(props.staff.id);
  } else {
    // If it's a new staff member, apply defaults if enabled
    if (globalSettings.value.enableDefaultBusinessSchedule) {
      schedule.value = JSON.parse(JSON.stringify(globalSettings.value.defaultBusinessSchedule));
      schedule.value.forEach(day => day.id = null); // Ensure IDs are null for new template entries
    }
    if (globalSettings.value.enableDefaultBusinessHolidays) {
      holidays.value = JSON.parse(JSON.stringify(globalSettings.value.defaultBusinessHolidays));
      holidays.value.forEach(h => h.id = null); // Ensure IDs are null for new template entries
    }
    // For new staff, also fetch all services so they can be selected
    fetchAllServices();
  }
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
/* Scoped styles specific to EmployeForm.vue */
.content-card {
  background-color: var(--admin-card-bg-color);
  box-shadow: var(--admin-shadow);
  border: 1px solid var(--admin-border-color);
}

.input-field {
  background-color: var(--admin-input-bg-color);
  color: var(--admin-input-text-color);
  border-color: var(--admin-input-border-color);
}
.input-field:focus {
  border-color: var(--admin-link-indigo-bg);
  box-shadow: 0 0 0 1px var(--admin-link-indigo-bg);
}

.form-checkbox {
  accent-color: var(--admin-link-indigo-bg);
  border-color: var(--admin-input-border-color);
  background-color: var(--admin-input-bg-color);
}

.break-label {
  border: 1px solid var(--admin-suggestion-yellow-border);
}

/* Modal styles - ensure they match the global toast styling if possible for consistency */
.modal-fade-enter-active, .modal-fade-leave-active {
  transition: opacity 0.3s ease;
}
.modal-fade-enter-from, .modal-fade-leave-to {
  opacity: 0;
}
.modal-fade-enter-active .modal-content,
.modal-fade-leave-active .modal-content {
  transition: transform 0.3s ease, opacity 0.3s ease;
}
.modal-fade-enter-from .modal-content,
.modal-fade-leave-to .modal-content {
  transform: translateY(-20px) scale(0.95);
  opacity: 0;
}
.modal-content {
  background-color: var(--admin-card-bg-color);
  color: var(--admin-text-color);
  border: 1px solid var(--admin-border-color);
}
</style>