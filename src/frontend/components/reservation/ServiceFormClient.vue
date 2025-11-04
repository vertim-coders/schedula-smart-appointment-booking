<template>
  <!-- WordPress-compatible container with admin bar support -->
  <div v-if="canShowForm"
    class="wp-reservation-form schedula-flex schedula-items-center schedula-justify-center schedula-py-4 sm:schedula-py-8" 
    :class="{ 'schedula-pt-8': hasWordPressAdminBar, 'schedula-min-h-screen': !isPreview, 'is-preview': isPreview }"
    :style="globalFormStyles"
   >
    <!-- Main form container (the "card") with fixed height -->
    <div 
      :style="[formContainerStyles, { 
        height: isPreview ? '900px' : '98vh', 
        maxHeight: '1400px' 
      }]"
      class="schedula-relative schedula-rounded-lg schedula-overflow-hidden schedula-shadow-lg schedula-w-full schedula-flex schedula-flex-col form-container-responsive" 
    >   
     
        <!-- Large Header for Summary View -->
        <div v-if="showSummary" 
          :class="[
            'header-section schedula-text-center schedula-flex-shrink-0 schedula-relative',
            isSmallForm 
              ? 'schedula-px-6 schedula-py-8' 
              : 'schedula-px-6 schedula-py-8 sm:schedula-px-8 sm:schedula-py-12'
          ]" 
          :style="headerStyles"
        >
          <!-- Category name in bottom right corner -->
          <div class="schedula-absolute schedula-bottom-4 schedula-right-4 sm:schedula-bottom-6 sm:schedula-right-6 schedula-bg-white schedula-px-2 schedula-py-1 schedula-rounded-lg schedula-shadow-sm">
            <h2 
              class="schedula-text-xs sm:schedula-text-base schedula-font-bold schedula-text-gray-800" 
              @click="handleLabelClick('category_name', selectedCategory?.name || labels.category)"
              :class="{'preview-editable-label': isPreview}"
              :style="{
                padding:'0 !important'
              }"
            >
              {{ selectedCategory?.name || labels.category || '' }}
            </h2>
          </div>
          
          <!-- Main header content -->
          <div class="schedula-flex schedula-items-center schedula-justify-center schedula-h-full">
            <h1 
              :class="[
                'schedula-font-bold schedula-leading-tight',
                { 'preview-editable-label': isPreview },
                isSmallForm 
                  ? 'schedula-text-3xl' 
                  : 'schedula-text-3xl sm:schedula-text-4xl lg:schedula-text-5xl'
              ]"
              :style="headerTextStyles" 
              @click="handleLabelClick('service_form_title', labels.service_form_title)"
            >
              {{ labels.service_form_title || 'Book Appointment' }}
            </h1>
          </div>
        </div>

        <!-- Regular Header for Steps -->
        <div v-else class="header-section schedula-px-4 schedula-py-3 sm:schedula-px-6 sm:schedula-py-4 schedula-text-center schedula-flex-shrink-0" :style="headerStyles">
          <h1 
            class="schedula-text-lg sm:schedula-text-xl lg:schedula-text-2xl schedula-font-bold schedula-leading-tight" 
            :style="headerTextStyles" 
            @click="handleLabelClick('service_form_title', labels.service_form_title)"
            :class="{'preview-editable-label': isPreview}"
          >
            {{ labels.service_form_title || 'Book Appointment' }}
          </h1>
          <p class="schedula-text-xs sm:schedula-text-sm schedula-mt-1 sm:schedula-mt-2" :style="headerSubtextStyles" @click="handleLabelClick('service_info_description', labels.service_info_description)"
             :class="{'preview-editable-label': isPreview}">
            {{ labels.service_info_description }}
          </p>
        </div>

        <!-- Mobile Progress Indicator (Visible only on small screens) - Fixed after header -->
        <div v-if="!showSummary" :class="['mobile-progress', isPreview ? 'schedula-hidden' : 'schedula-block sm:schedula-hidden', 'schedula-px-4 schedula-py-3 schedula-border-b schedula-flex-shrink-0']" :style="progressBarStyles">
          <div class="schedula-flex schedula-items-center schedula-justify-between schedula-mb-2">
            <span class="schedula-text-sm schedula-font-medium" :style="textStyles">
              Step {{ currentStep }} of {{ visibleStepsCount }}
            </span>
            <span class="schedula-text-xs schedula-text-gray-600">{{ currentStepInfo?.title }}</span>
          </div>
          <!-- Progress bar fill -->
          <div class="schedula-w-full schedula-bg-gray-200 schedula-rounded-full schedula-h-2">
            <div 
              class="schedula-h-2 schedula-rounded-full schedula-transition-all schedula-duration-500 schedula-ease-out" 
              :style="{ 
                backgroundColor: settings.colors.primary, 
                width: `${(currentStep / visibleStepsCount) * 100}%` 
              }"
            ></div>
          </div>
        </div>

        <!-- Tablet/Desktop Progress Steps (Hidden on small screens, visible on larger) - Fixed after header -->
        <div v-if="!showSummary" :class="['desktop-progress', isPreview ? 'schedula-block' : 'schedula-hidden sm:schedula-block', 'schedula-px-2 sm:schedula-px-4 lg:schedula-px-6 schedula-py-3 sm:schedula-py-4 schedula-flex-shrink-0']" :style="progressBarStyles">
          <div class="schedula-flex schedula-items-center schedula-justify-between schedula-max-w-4xl schedula-mx-auto">
            <div 
              v-for="(step, index) in visibleSteps" 
              :key="step.id" 
              class="schedula-flex schedula-items-center"
               :style="{ flex: !isSmallForm && (isPreview || index < visibleSteps.length - 1) ? '1 1 0%' : '0 1 auto' }"
            >
              <!-- Step Circle -->
              <div 
                :class="[
                  'schedula-rounded-full schedula-flex schedula-items-center schedula-justify-center schedula-text-xs sm:schedula-text-sm schedula-font-semibold schedula-transition-all schedula-duration-300 schedula-flex-shrink-0',
                  isPreview ? 'schedula-w-6 schedula-h-6' : 'schedula-w-6 schedula-h-6 sm:schedula-w-8 sm:schedula-h-8 lg:schedula-w-10 lg:schedula-h-10',
                  currentStep > step.id ? 'schedula-text-white' : 
                  currentStep === step.id ? 'schedula-text-white' : 'schedula-text-gray-500'
                ]" 
                :style="getStepCircleStyles(step.id)"
              >
                <svg 
                  v-if="currentStep > step.id" 
                  class="schedula-w-3 schedula-h-3 sm:schedula-w-4 sm:schedula-h-4 lg:schedula-w-5 sm:schedula-h-5" 
                  fill="currentColor" 
                  viewBox="0 0 20 20"
                >
                  <path 
                    fill-rule="evenodd" 
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" 
                    clip-rule="evenodd"
                  />
                </svg>
                <span v-else>{{ step.id }}</span>
              </div>

              <!-- Step Text -->
              <div class="schedula-ml-2 sm:schedula-ml-3 schedula-text-left schedula-min-w-0 schedula-flex-shrink">
                <p 
                  :class="[
                    'schedula-font-medium schedula-transition-colors schedula-duration-300',
                    isPreview ? 'schedula-text-xs' : 'schedula-text-xs',
                    currentStep >= step.id ? 'schedula-text-gray-800' : 'schedula-text-gray-500',
                    {'preview-editable-label': isPreview}
                  ]"
                  class="schedula-truncate sm:schedula-whitespace-normal"
                  style="margin: 0px;"
                  @click="handleLabelClick(`step_${step.id}_title`, step.title.value)"
                >
                  {{ step.title }}
                </p>
                <p 
                  v-if="!isSmallForm"
                  :class="['schedula-text-xs', 'schedula-text-gray-500', 'schedula-hidden', 'lg:schedula-block', { 'schedula-truncate': !isPreview, 'preview-editable-label': isPreview } ]"
                   @click="handleLabelClick(`step_${step.id}_subtitle`, step.subtitle.value)"
                   style="margin: 0px;"
                >
                  {{ step.subtitle }}
                </p>
              </div>

              <!-- Connection Line -->
              <div 
                v-if="index < visibleSteps.length - 1" 
                class="schedula-flex-1 schedula-mx-2 sm:schedula-mx-4 schedula-min-w-8"
              >
                <div 
                  :class="[
                    'schedula-h-0.5 sm:schedula-h-1 schedula-rounded-full schedula-transition-all schedula-duration-300',
                    currentStep > step.id ? '' : 'schedula-bg-gray-200'
                  ]" 
                  :style="getStepLineStyles(step.id)"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Main Form Content Wrapper - This section will scroll internally -->
        <div class="schedula-form-body schedula-flex-grow schedula-w-full schedula-max-w-none schedula-py-6 lg:schedula-px-4 lg:schedula-py-4 schedula-overflow-y-auto schedula-scrollbar-thin schedula-scrollbar-thumb-gray-300 schedula-scrollbar-track-gray-100" 
        :style="formBodyStyles"
        :class="formBodyPaddingClass"
        >
          <form @submit.prevent="handleNext" class="schedula-h-full schedula-flex schedula-flex-col schedula-justify-between">
            
            <!-- Summary View -->
            <div v-if="showSummary" class="step-content schedula-flex-shrink-0">
              <!-- Service Title -->
              <div class="schedula-mb-8">
                <h2 
                  class="schedula-text-2xl schedula-font-bold schedula-text-gray-800 schedula-mb-2" 
                  :style="textStyles"
                  @click="handleLabelClick('service_title', selectedService?.title || 'Service Title')"
                  :class="{'preview-editable-label': isPreview}"
                >
                  {{ selectedService?.title || labels.service || '' }}
                </h2>
              </div>

              <!-- Service Details Card -->
              <div class="schedula-bg-white schedula-rounded-lg schedula-border schedula-p-6 schedula-mb-6" :style="cardStyles">
                <!-- Staff Name -->
                <div v-if="settings.forms.serviceForm.displayStaffNames" class="schedula-flex schedula-items-center schedula-justify-between schedula-py-3 schedula-border-b schedula-border-gray-100">
                  <span class="schedula-text-gray-600 schedula-font-medium" @click="handleLabelClick('staff_member_label', labels.staff_member_label)"
                    :class="{'preview-editable-label': isPreview}">{{ labels.staff_member_label || 'Staff Member' }}</span>
                  <span 
                    class="schedula-text-gray-800 schedula-font-semibold"
                    @click="handleLabelClick('staff_name', selectedStaff?.name || 'Any Available Staff')"
                    :class="{'preview-editable-label': isPreview}"
                  >
                    {{ selectedStaff?.name || 'Any Available Staff' }}
                  </span>
                </div>

                <!-- Duration -->
                <div class="schedula-flex schedula-items-center schedula-justify-between schedula-py-3 schedula-border-b schedula-border-gray-100">
                  <span class="schedula-text-gray-600 schedula-font-medium" @click="handleLabelClick('duration_label', labels.duration_label)"
                    :class="{'preview-editable-label': isPreview}">{{ labels.duration_label || 'Duration' }}</span>
                  <span 
                    class="schedula-text-gray-800 schedula-font-semibold"
                    @click="handleLabelClick('duration_display', formattedDuration)"
                    :class="{'preview-editable-label': isPreview}"
                  >
                    {{ formattedDuration }}
                  </span>
                </div>

                <!-- Price -->
                <div class="schedula-flex schedula-items-center schedula-justify-between schedula-py-3">
                  <span class="schedula-text-gray-600 schedula-font-medium" @click="handleLabelClick('price_label', labels.price_label)"
                    :class="{'preview-editable-label': isPreview}">{{ labels.price_label || 'Price' }}</span>
                  <span 
                    class="schedula-text-gray-800 schedula-font-bold schedula-text-lg"
                    :style="{ color: settings.colors.primary }"
                    @click="handleLabelClick('price_display', formatPrice(displayPriceInCard || 0) )"
                    :class="{'preview-editable-label': isPreview}"
                  >
                    {{ formatPrice(displayPriceInCard || 0) }}
                  </span>
                </div>
              </div>

              <!-- Service Description (Optional) -->
              <div v-if="selectedService?.description" class="schedula-mb-6">
                <h3 class="schedula-text-lg schedula-font-semibold schedula-text-gray-800 schedula-mb-2" :style="textStyles" @click="handleLabelClick('service_description_heading', labels.service_description_heading)" :class="{'preview-editable-label': isPreview}">
                  {{ labels.service_description_heading || 'Service Description' }}
                </h3>
                <p 
                  class="schedula-text-gray-600 schedula-leading-relaxed"
                  @click="handleLabelClick('service_description_content', selectedService.description)"
                  :class="{'preview-editable-label': isPreview}"
                >
                  {{ selectedService.description }}
                </p>
              </div>
            </div>
            
            <!-- Step 1: Date & Time Selection -->
            <div v-if="!showSummary && currentStep === 1" class="step-content schedula-flex-shrink-0">

              <!-- Recurrence Options -->
              <div v-if="globalSettings.enableRecurringAppointments" class="recurrence-options-section schedula-mb-6">
                <h3 class="schedula-text-lg schedula-font-semibold schedula-text-gray-800 schedula-mb-3" :style="labelStyles">
                  {{ labels.recurrence_options || 'Recurrence Options' }}
                </h3>
                <div class="schedula-p-4 schedula-border schedula-rounded-lg schedula-bg-white schedula-shadow-sm" :style="cardStyles">
                  <label class="schedula-inline-flex schedula-items-center schedula-cursor-pointer schedula-mb-4">
                    <input type="checkbox" v-model="formData.is_recurring" class="form-checkbox schedula-h-5 schedula-w-5 schedula-text-blue-600" :style="checkboxRadioStyles" />
                    <span class="schedula-ml-2 schedula-text-base schedula-font-medium" :style="textStyles">
                      {{ labels.make_recurring || 'Make this a recurring appointment' }}
                    </span>
                  </label>

                  <div v-if="formData.is_recurring" class="schedula-grid schedula-grid-cols-1 sm:schedula-grid-cols-2 schedula-gap-4">
                    <!-- Recurrence Frequency -->
                    <div class="form-group">
                      <label class="form-label schedula-block schedula-text-sm schedula-font-semibold schedula-text-gray-700 schedula-mb-2" :style="labelStyles">
                        {{ labels.recurrence_frequency || 'Frequency' }} *
                      </label>
                      <select v-model="formData.recurrence_frequency" required class="form-select schedula-w-full schedula-px-3 schedula-py-2 schedula-border schedula-border-gray-300 schedula-rounded-lg schedula-transition-all schedula-duration-200 schedula-text-base sm:schedula-text-sm schedula-bg-white schedula-shadow-sm hover:schedula-shadow-md" :style="inputStyles">
                        <option value="">{{ labels.select_frequency || 'Select Frequency' }}</option>
                        <option value="daily">{{ labels.daily || 'Daily' }}</option>
                        <option value="weekly">{{ labels.weekly || 'Weekly' }}</option>
                        <option value="monthly">{{ labels.monthly || 'Monthly' }}</option>
                        <option value="yearly">{{ labels.yearly || 'Yearly' }}</option>
                      </select>
                      <p v-if="showError('recurrence_frequency')" class="schedula-text-red-500 schedula-text-xs schedula-mt-1">{{ validationErrors.recurrence_frequency }}</p>
                    </div>

                    <!-- Recurrence Interval -->
                    <div class="form-group">
                      <label class="form-label schedula-block schedula-text-sm schedula-font-semibold schedula-text-gray-700 schedula-mb-2" :style="labelStyles">
                        {{ labels.recurrence_interval || 'Every (interval)' }} *
                      </label>
                      <input type="number" v-model.number="formData.recurrence_interval" min="1" required class="form-input schedula-w-full schedula-px-3 schedula-py-2 schedula-border schedula-border-gray-300 schedula-rounded-lg schedula-transition-all schedula-duration-200 schedula-text-base sm:schedula-text-sm schedula-shadow-sm hover:schedula-shadow-md" :style="inputStyles" />
                      <p v-if="showError('recurrence_interval')" class="schedula-text-red-500 schedula-text-xs schedula-mt-1">{{ validationErrors.recurrence_interval }}</p>
                    </div>

                    <!-- Recurrence Count -->
                    <div class="form-group sm:schedula-col-span-2">
                        <label class="form-label schedula-block schedula-text-sm schedula-font-semibold schedula-text-gray-700 schedula-mb-2" :style="labelStyles">
                            {{ labels.after_occurrences || 'Number of Occurrences' }} *
                        </label>
                        <select v-model.number="formData.recurrence_count" required class="form-select schedula-w-full schedula-px-3 schedula-py-2 schedula-border schedula-border-gray-300 schedula-rounded-lg schedula-transition-all schedula-duration-200 schedula-text-base sm:schedula-text-sm schedula-bg-white schedula-shadow-sm hover:schedula-shadow-md" :style="inputStyles">
                          <option v-for="option in recurrenceCountOptions" :key="option" :value="option">
                            {{ option }}
                          </option>
                        </select>
                        <p v-if="showError('recurrence_count')" class="schedula-text-red-500 schedula-text-xs schedula-mt-1">
                          {{ validationErrors.recurrence_count }}
                        </p>
                    </div>
                  </div>

                  <!-- Recurrence Conflict Warning -->
                  <div v-if="isCheckingConflicts" class="schedula-mt-4 schedula-p-3 schedula-bg-blue-50 schedula-border schedula-border-blue-200 schedula-rounded-lg schedula-text-sm schedula-text-blue-700">
                    <i class="fas fa-sync fa-spin schedula-mr-2"></i>
                    {{ labels.checking_availability || __('Checking availability for recurring appointments...', 'schedula-smart-appointment-booking') }}
                  </div>
                  <div v-if="recurrenceConflicts.length > 0" class="schedula-mt-4 schedula-p-3 schedula-bg-yellow-50 schedula-border schedula-border-yellow-300 schedula-rounded-lg schedula-text-sm schedula-text-yellow-800">
                    <h4 class="schedula-font-bold"><i class="fas fa-exclamation-triangle schedula-mr-2"></i>{{ labels.recurrence_conflict_title || __('Scheduling Conflict', 'schedula-smart-appointment-booking') }}</h4>
                    <p class="schedula-mt-1">{{ labels.recurrence_conflict_message || __('The selected staff member is not available on the following dates:', 'schedula-smart-appointment-booking') }}</p>
                    <ul class="schedula-list-disc schedula-list-inside schedula-mt-2">
                      <li v-for="date in recurrenceConflicts" :key="date">{{ formatDate(date) }}</li>
                    </ul>
                    <p class="schedula-mt-2">{{ labels.recurrence_conflict_suggestion || __('Please choose a different start date, time, or recurrence pattern.', 'schedula-smart-appointment-booking') }}</p>
                  </div>
                </div>
              </div>
              
              <div v-if="!isDesktopView && step1SubView === 'time'" class="schedula-mb-4">
                  <button type="button" @click="step1SubView = 'date'" class="schedula-inline-flex schedula-items-center schedula-px-3 schedula-py-1 schedula-border schedula-border-transparent schedula-text-sm schedula-font-medium schedula-rounded-md schedula-text-gray-700 schedula-bg-gray-100 hover:schedula-bg-gray-200">
                      <i class="fas fa-arrow-left schedula-mr-2"></i>
                      Back to Date
                  </button>
              </div>

              <div class="schedula-grid schedula-grid-cols-1 lg:schedula-grid-cols-2 schedula-gap-6">
                
                <!-- Calendar Section -->
                <div v-if="settings.calendar.showCalendar && (isDesktopView || step1SubView === 'date')" class="calendar-section">
                  <h3 class="schedula-text-lg schedula-font-semibold schedula-text-gray-800 schedula-mb-4" :style="labelStyles" @click="handleLabelClick('select_date', labels.select_date)"
                      :class="{'preview-editable-label': isPreview}">
                    {{ labels.select_date }}
                  </h3>

                  <!-- List View (conditional on settings.calendar.layoutStyle === 'list') -->
                  <div 
                    v-if="settings.calendar.layoutStyle === 'list'" 
                    class="list-calendar schedula-border schedula-rounded-lg schedula-bg-white schedula-shadow-sm" 
                    :style="cardStyles"
                  >
                    <!-- Days List View -->
                    <div v-if="listViewStep === 'days'" class="days-list-view">
                      <div class="schedula-p-4 schedula-border-b">
                        <h4 class="schedula-text-base schedula-font-semibold schedula-text-gray-800 schedula-mb-1" @click="handleLabelClick('select_date', labels.select_date)"
                            :class="{'preview-editable-label': isPreview}">
                          {{ labels.select_date }}
                        </h4>
                        <p class="schedula-text-sm schedula-text-gray-600" @click="handleLabelClick('select_date_time_description', labels.select_date_time_description)"
                           :class="{'preview-editable-label': isPreview}">
                          {{ labels.select_date_time_description }}
                        </p>
                      </div>
                      <div class="schedula-max-h-[320px] schedula-overflow-y-auto">
                        <button 
                          v-for="dateEntry in calendarDatesGrouped" 
                          :key="dateEntry.date" 
                          type="button" 
                          @click="selectDayForListView(dateEntry)" 
                          class="schedula-w-full schedula-text-left schedula-p-4 schedula-border-b last:schedula-border-b-0 hover:schedula-bg-gray-50 schedula-transition-colors schedula-duration-200 schedula-flex schedula-items-center schedula-justify-between"
                          :class="{'schedula-bg-blue-50': formData.appointment_date === dateEntry.date}"
                        >
                          <span class="schedula-text-base schedula-font-medium schedula-text-gray-800">{{ formatDate(dateEntry.date) }}</span>
                          <span class="schedula-text-sm schedula-text-gray-500">{{ dateEntry.slots.length }} slots</span>
                        </button>
                        
                        <div v-if="calendarDatesGrouped.length === 0" class="schedula-p-8 schedula-text-center">
                          <svg class="schedula-mx-auto schedula-h-16 schedula-w-16 schedula-text-gray-400 schedula-mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                          </svg>
                          <p class="schedula-text-gray-500 schedula-font-medium" @click="handleLabelClick('no_appointments_available', labels.no_appointments_available)"
                             :class="{'preview-editable-label': isPreview}">
                            {{ labels.no_appointments_available }}
                          </p>
                          <p class="schedula-text-sm schedula-text-gray-400 schedula-mt-1" @click="handleLabelClick('check_back_later', labels.check_back_later)"
                             :class="{'preview-editable-label': isPreview}">
                            {{ labels.check_back_later }}
                          </p>
                        </div>
                      </div>
                    </div>

                    <!-- Times for Selected Day View -->
                    <div v-else-if="listViewStep === 'times'" class="times-list-view">
                      <div class="schedula-p-4 schedula-border-b schedula-flex schedula-items-center">
                        <button type="button" @click="goBackToDaysList" class="schedula-p-2 schedula-mr-2 schedula-rounded-full hover:schedula-bg-gray-100 schedula-transition-colors">
                          <i class="fas fa-arrow-left schedula-text-gray-600"></i>
                        </button>
                        <div>
                          <h4 class="schedula-text-base schedula-font-semibold schedula-text-gray-800 schedula-mb-1">{{ formatDate(selectedDateForListView.date) }}</h4>
                          <p class="schedula-text-sm schedula-text-gray-600" @click="handleLabelClick('available_times_title', labels.available_times_title)"
                             :class="{'preview-editable-label': isPreview}">
                            {{ labels.available_times_title }}
                          </p>
                        </div>
                      </div>
                      <div class="schedula-max-h-[320px] schedula-overflow-y-auto schedula-p-4 schedula-grid schedula-grid-cols-2 sm:schedula-grid-cols-3 schedula-gap-3">
                        <button 
                          v-for="slot in filteredTimeSlotsForListView" 
                          :key="slot.value" 
                          type="button" 
                          @click="selectDateTimeFromList(selectedDateForListView.date, slot.value)" 
                          class="time-slot-btn schedula-px-3 schedula-py-2 schedula-rounded-lg schedula-text-sm schedula-font-medium schedula-transition-all schedula-duration-200 schedula-border-2 schedula-touch-manipulation"
                          :class="[
                            formData.appointment_date === selectedDateForListView.date && formData.appointment_time === slot.value
                              ? 'selected schedula-text-white schedula-shadow-lg' 
                              : 'schedula-text-gray-700'
                          ]"
                          :style="getTimeSlotButtonStyles(slot.value, selectedDateForListView.date)"
                        >
                          {{ slot.label }}
                        </button>
                        <div v-if="filteredTimeSlotsForListView.length === 0" class="schedula-col-span-full schedula-p-4 schedula-text-center">
                          <p class="schedula-text-gray-500 schedula-font-medium">{{ labels.no_available_times }}</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Desktop Calendar Grid (Default View - always visible if showCalendar is true) -->
                  <div 
                    v-else-if="settings.calendar.layoutStyle === 'default'" 
                    class="desktop-calendar schedula-border schedula-rounded-lg schedula-bg-white schedula-shadow-sm schedula-p-4" 
                    :style="cardStyles"
                  >
                    <!-- Calendar Header -->
                    <div class="calendar-header schedula-flex schedula-items-center schedula-justify-between schedula-mb-6">
                      <button 
                        type="button" 
                        @click="previousMonth" 
                        class="nav-btn schedula-p-3 hover:schedula-bg-gray-100 schedula-rounded-full schedula-transition-colors schedula-touch-manipulation"
                        :style="buttonSecondaryStyles"
                      >
                        <svg class="schedula-w-5 schedula-h-5 schedula-text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                      </button>
                      
                      <h4 class="schedula-text-xl schedula-font-bold schedula-text-gray-900 schedula-flex-grow schedula-text-center" :style="textStyles">
                        {{ currentMonthYear }}
                      </h4>
                      
                      <button 
                        type="button" 
                        @click="nextMonth" 
                        class="nav-btn schedula-p-3 hover:schedula-bg-gray-100 schedula-rounded-full schedula-transition-colors schedula-touch-manipulation"
                        :style="buttonSecondaryStyles"
                      >
                        <svg class="schedula-w-5 schedula-h-5 schedula-text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                      </button>
                    </div>

                    <!-- Days of Week -->
                    <div class="schedula-grid schedula-grid-cols-7 schedula-gap-1 schedula-mb-3">
                      <div 
                        v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" 
                        :key="day" 
                        class="schedula-text-center schedula-text-sm schedula-font-semibold schedula-text-gray-500 schedula-py-3"
                        :style="labelStyles"
                      >
                        {{ day }}
                      </div>
                    </div>

                    <!-- Calendar Grid -->
                    <div class="calendar-grid schedula-grid schedula-grid-cols-7 schedula-gap-1">
                      <button 
                        v-for="date in calendarDates" 
                        :key="date.key" 
                        type="button" 
                        @click="selectDate(date)" 
                        :disabled="date.disabled || date.isOtherMonth" 
                        class="calendar-date schedula-h-10 schedula-w-full schedula-text-sm schedula-rounded-lg schedula-flex schedula-items-center schedula-justify-center schedula-font-medium schedula-touch-manipulation"
                        :class="[
                          date.isSelected ? 'selected schedula-text-white schedula-shadow-lg' : '',
                          date.isToday && !date.isSelected ? 'today schedula-font-bold' : '',
                          date.disabled || date.isOtherMonth ? 'disabled:schedula-text-gray-300 disabled:schedula-cursor-not-allowed' : 'schedula-text-gray-700'
                        ]" 
                        :style="getCalendarDateStyles(date)"
                      >
                        {{ date.day }}
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Time Slots Section (Always visible when default calendar is active) -->
                <div 
                  v-if="settings.calendar.layoutStyle === 'default' && (isDesktopView || step1SubView === 'time')" 
                  class="time-slots-section"
                >
                  <h3 class="schedula-text-lg schedula-font-semibold schedula-text-gray-800 schedula-mb-4" :style="labelStyles" @click="handleLabelClick('available_times', labels.available_times)"
                      :class="{'preview-editable-label': isPreview}">
                    {{ labels.available_times }}
                  </h3>
                  
                  <div class="time-slots-container schedula-border schedula-rounded-lg schedula-bg-white schedula-shadow-sm schedula-min-h-[320px] schedula-overflow-y-auto" :style="cardStyles">
                    <!-- No Date Selected -->
                    <div v-if="!formData.appointment_date" class="schedula-flex schedula-items-center schedula-justify-center schedula-h-full schedula-p-8">
                      <div class="schedula-text-center">
                        <svg class="schedula-mx-auto schedula-h-16 schedula-w-16 schedula-text-gray-400 schedula-mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="schedula-text-gray-500 schedula-font-medium" @click="handleLabelClick('select_a_date', labels.select_a_date)"
                           :class="{'preview-editable-label': isPreview}">
                          {{ labels.select_a_date }}
                        </p>
                        <p class="schedula-text-sm schedula-text-gray-400 schedula-mt-1" @click="handleLabelClick('choose_date_to_see_times', labels.choose_date_to_see_times)"
                           :class="{'preview-editable-label': isPreview}">
                          {{ labels.choose_date_to_see_times }}
                        </p>
                      </div>
                    </div>

                    <!-- Loading State -->
                    <div v-else-if="loadingTimeSlots" class="schedula-flex schedula-items-center schedula-justify-center schedula-h-full schedula-p-8">
                      <div class="schedula-text-center">
                        <svg 
                          class="schedula-animate-spin schedula-mx-auto schedula-h-12 schedula-w-12 schedula-mb-4" 
                          :style="{ color: settings.colors.primary }" 
                          xmlns="http://www.w3.org/2000/svg" 
                          fill="none" 
                          viewBox="0 0 24 24"
                        >
                          <circle class="schedula-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="schedula-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="schedula-text-gray-600 schedula-font-medium" @click="handleLabelClick('loading_available_times', labels.loading_available_times)"
                           :class="{'preview-editable-label': isPreview}">
                          {{ labels.loading_available_times }}
                        </p>
                      </div>
                    </div>

                    <!-- No Times Available -->
                    <div v-else-if="availableTimeSlots.length === 0" class="schedula-flex schedula-items-center schedula-justify-center schedula-h-full schedula-p-8">
                      <div class="schedula-text-center">
                        <svg class="schedula-mx-auto schedula-h-16 schedula-w-16 schedula-text-yellow-500 schedula-mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <p class="schedula-text-yellow-700 schedula-font-medium" @click="handleLabelClick('no_available_times', labels.no_available_times)"
                           :class="{'preview-editable-label': isPreview}">
                          {{ labels.no_available_times }}
                        </p>
                        <p class="schedula-text-sm schedula-text-yellow-600 schedula-mt-1" @click="handleLabelClick('select_different_date', labels.select_different_date)"
                           :class="{'preview-editable-label': isPreview}">
                          {{ labels.select_different_date }}
                        </p>
                      </div>
                    </div>

                    <!-- Time Slots Grid -->
                    <div v-else class="schedula-p-4">
                      <div class="schedula-grid schedula-grid-cols-3 lg:schedula-grid-cols-4 schedula-gap-3">
                        <button 
                          v-for="slot in formattedAvailableTimeSlots" 
                          :key="slot.value" 
                          type="button" 
                          @click="selectTimeSlot(slot.value)" 
                          class="time-slot schedula-px-3 schedula-py-2 schedula-rounded-lg schedula-text-sm schedula-font-medium schedula-transition-all schedula-duration-200 schedula-border-2 schedula-touch-manipulation"
                          :class="[
                            formData.appointment_time === slot.value
                              ? 'selected schedula-text-white schedula-shadow-lg' 
                              : 'schedula-text-gray-700'
                          ]"
                          :style="getTimeSlotButtonStyles(slot.value)"
                        >
                          {{ slot.label }}
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Error message for appointment date/time selection -->
                  <p v-if="showError('appointment_date') || showError('appointment_time') || showError('available_slots')" class="schedula-text-red-500 schedula-text-xs schedula-mt-2 schedula-text-center">
                    {{ validationErrors.appointment_date || validationErrors.appointment_time || validationErrors.available_slots }}
                  </p>

                  <!-- Selected Time Confirmation -->
                  <div 
                    v-if="formData.appointment_time && !validationErrors.appointment_time && !validationErrors.appointment_date" 
                    class="selected-time-confirmation schedula-mt-4 schedula-p-4 schedula-border schedula-rounded-lg schedula-bg-green-50 schedula-border-green-200" 
                    :style="cardStyles"
                  >
                    <div class="schedula-flex schedula-items-center">
                      <svg class="schedula-w-5 schedula-h-5 schedula-mr-3 schedula-flex-shrink-0 schedula-text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                      </svg>
                      <p class="schedula-font-semibold schedula-text-sm schedula-text-green-800" :style="textStyles">
                        <span @click="handleLabelClick('selected_time_prefix', labels.selected_time_prefix)"
                              :class="{'preview-editable-label': isPreview}">{{ labels.selected_time_prefix }}</span>
                        {{ formatDate(formData.appointment_date) }} at {{ formatTimeForDisplay(formData.appointment_time, formData.appointment_date) }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 2: Personal Details -->
            <div v-if="!showSummary && currentStep === 2" class="step-content schedula-flex-shrink-0">
                <div class="schedula-max-w-2xl schedula-mx-auto">
                  <h3 class="schedula-text-xl schedula-font-semibold schedula-text-gray-800 schedula-mb-6 schedula-text-center" :style="textStyles" @click="handleLabelClick('your_information', labels.your_information)"
                      :class="{'preview-editable-label': isPreview}">
                    {{ __('Your Informations', 'schedula-smart-appointment-booking') }}
                  </h3>
                  <!-- Updated grid layout and input sizing for step 3 -->
                  <div class="schedula-grid schedula-grid-cols-1 md:schedula-grid-cols-2 schedula-gap-x-6 schedula-gap-y-4">
                    <div class="md:schedula-col-span-2 form-group" v-if="settings.customer.showEmailField">
                      <label class="form-label schedula-block schedula-text-sm schedula-font-semibold schedula-text-gray-700 schedula-mb-2" :style="labelStyles" @click="handleLabelClick('email', labels.email)"
                            :class="{'preview-editable-label': isPreview}">
                        {{ __('Email', 'schedula-smart-appointment-booking') }} *
                      </label>
                      <div class="schedula-relative">
                        <input 
                          type="email" 
                          v-model="formData.customer_email" 
                          required 
                          class="form-input schedula-w-full schedula-pr-10 schedula-px-4 schedula-py-3 schedula-border schedula-border-gray-300 schedula-rounded-lg schedula-transition-all schedula-duration-200 schedula-text-base schedula-shadow-sm hover:schedula-shadow-md focus:schedula-ring-2 focus:schedula-ring-blue-500 focus:schedula-border-blue-500" 
                          :placeholder="__('Enter your email address', 'schedula-smart-appointment-booking')" 
                          :style="inputStyles"
                          :class="{ 'schedula-border-red-500': showError('customer_email') }"
                          @input="debouncedFetchCustomer"
                        />
                        <div v-if="isLoadingCustomerDetails" class="schedula-absolute schedula-inset-y-0 schedula-right-0 schedula-pr-3 schedula-flex schedula-items-center schedula-pointer-events-none">
                          <svg class="schedula-animate-spin schedula-h-5 schedula-w-5 schedula-text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="schedula-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="schedula-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                          </svg>
                        </div>
                      </div>
                      <p v-if="showError('customer_email')" class="schedula-text-red-500 schedula-text-xs schedula-mt-1">{{ validationErrors.customer_email }}</p>
                    </div>
                    <div v-if="settings.customer.showFirstNameField" class="form-group">
                      <label class="form-label schedula-block schedula-text-sm schedula-font-semibold schedula-text-gray-700 schedula-mb-2" :style="labelStyles" @click="handleLabelClick('first_name', labels.first_name)"
                            :class="{'preview-editable-label': isPreview}">
                        {{ __('First Name', 'schedula-smart-appointment-booking') }} *
                      </label>
                      <input 
                        type="text" 
                        v-model="formData.customer_first_name" 
                        required 
                        class="form-input schedula-w-full schedula-px-4 schedula-py-3 schedula-border schedula-border-gray-300 schedula-rounded-lg schedula-transition-all schedula-duration-200 schedula-text-base schedula-shadow-sm hover:schedula-shadow-md focus:schedula-ring-2 focus:schedula-ring-blue-500 focus:schedula-border-blue-500" 
                        :placeholder="__('Enter your first name', 'schedula-smart-appointment-booking')" 
                        :style="inputStyles"
                        :class="{ 'schedula-border-red-500': showError('customer_first_name') }"
                        @input="clearError('customer_first_name')"
                      />
                      <p v-if="showError('customer_first_name')" class="schedula-text-red-500 schedula-text-xs schedula-mt-1">{{ validationErrors.customer_first_name }}</p>
                    </div>
                    <div v-if="settings.customer.showLastNameField" class="form-group">
                      <label class="form-label schedula-block schedula-text-sm schedula-font-semibold schedula-text-gray-700 schedula-mb-2" :style="labelStyles" @click="handleLabelClick('last_name', labels.last_name)"
                            :class="{'preview-editable-label': isPreview}">
                        {{ __('Last Name', 'schedula-smart-appointment-booking') }} *
                      </label>
                      <input 
                        type="text" 
                        v-model="formData.customer_last_name" 
                        required 
                        class="form-input schedula-w-full schedula-px-4 schedula-py-3 schedula-border schedula-border-gray-300 schedula-rounded-lg schedula-transition-all schedula-duration-200 schedula-text-base schedula-shadow-sm hover:schedula-shadow-md focus:schedula-ring-2 focus:schedula-ring-blue-500 focus:schedula-border-blue-500" 
                        :placeholder="__('Enter your last name', 'schedula-smart-appointment-booking')" 
                        :style="inputStyles"
                        :class="{ 'schedula-border-red-500': showError('customer_last_name') }"
                        @input="clearError('customer_last_name')"
                      />
                      <p v-if="showError('customer_last_name')" class="schedula-text-red-500 schedula-text-xs schedula-mt-1">{{ validationErrors.customer_last_name }}</p>
                    </div>
                    <div class="md:schedula-col-span-2 form-group" v-if="settings.customer.showPhoneField">
                      <label class="form-label schedula-block schedula-text-sm schedula-font-semibold schedula-text-gray-700 schedula-mb-2" :style="labelStyles" @click="handleLabelClick('phone', labels.phone)"
                            :class="{'preview-editable-label': isPreview}">
                        {{ __('Phone', 'schedula-smart-appointment-booking') }} *
                      </label>                    
                      <BasePhoneInput
                        :placeholder="labels.enter_phone_placeholder" 
                        v-model="formData.customer_phone"
                        :style="inputStyles"
                        :customClass="`form-input schedula-w-full schedula-px-3 schedula-py-2 schedula-border schedula-border-gray-300 schedula-rounded-lg schedula-transition-all schedula-duration-200 schedula-text-base sm:schedula-text-sm schedula-shadow-sm hover:schedula-shadow-md ${showError('customer_phone') ? 'schedula-border-red-500' : ''}`" 
                      />
                      <p v-if="showError('customer_phone')" class="schedula-text-red-500 schedula-text-xs schedula-mt-1">{{ validationErrors.customer_phone }}</p>
                    </div>
                    <div class="md:schedula-col-span-2 form-group" v-if="settings.customer.showNotesField">
                      <label class="form-label schedula-block schedula-text-sm schedula-font-semibold schedula-text-gray-700 schedula-mb-2" @click="handleLabelClick('notes_label', labels.notes_label)"
                            :class="{'preview-editable-label': isPreview}">{{ __('Additional Notes', 'schedula-smart-appointment-booking') }}</label>
                      <textarea v-model="formData.notes" rows="4" class="form-textarea schedula-w-full schedula-px-4 schedula-py-3 schedula-border schedula-border-gray-300 schedula-rounded-lg schedula-transition-all schedula-duration-200 schedula-text-base schedula-shadow-sm hover:schedula-shadow-md focus:schedula-ring-2 focus:schedula-ring-blue-500 focus:schedula-border-blue-500" :placeholder="__('Any special requests or notes...', 'schedula-smart-appointment-booking')" :style="inputStyles"></textarea>
                    </div>

                    <!-- Number of Persons (Checkbox + +/- controls) -->
                    <div v-if="globalSettings.enableGroupBooking" class="md:schedula-col-span-2 form-group">
                      <label 
                        class="form-label schedula-block schedula-text-sm schedula-font-semibold schedula-text-gray-700 schedula-mb-2" 
                        :style="labelStyles"
                        @click="handleLabelClick('number_of_persons', labels.number_of_persons)"
                        :class="{'preview-editable-label': isPreview}"
                      >
                        {{ __('Number of People', 'schedula-smart-appointment-booking') }} *
                      </label>
                      <div class="schedula-flex schedula-items-center schedula-space-x-6">
                        <div class="schedula-flex schedula-items-center">
                          <input
                            id="isBringingGuests"
                            v-model="isBringingGuests"
                            type="checkbox"
                            class="form-checkbox schedula-h-4 schedula-w-4 schedula-rounded schedula-border-gray-300"
                            :style="[checkboxRadioStyles, { color: settings.colors.primary }]"
                          />
                          <label for="isBringingGuests" class="schedula-ml-2 schedula-block schedula-text-sm schedula-font-medium schedula-text-gray-700">
                            {{ __('Bring people with you?', 'schedula-smart-appointment-booking') }}
                          </label>
                        </div>
                        <div v-if="isBringingGuests" class="schedula-ml-4 schedula-flex schedula-items-center schedula-space-x-2">
                          <button
                            v-if="additionalGuests > 1"
                            type="button"
                            @click="decrementGuests"
                            class="schedula-px-2 schedula-py-1 schedula-rounded schedula-text-sm focus:outline-none"
                            :style="{ backgroundColor: settings.colors.primary, color: settings.colors.headerText, border: 'none' }"
                          >
                            -
                          </button>
                          <span class="schedula-px-2 schedula-text-sm schedula-text-gray-700">{{ additionalGuests }}</span>
                          <button
                            type="button"
                            @click="incrementGuests"
                            :disabled="additionalGuests >= (globalSettings.maxPersonsPerBooking - 1)"
                            class="schedula-px-2 schedula-py-1 schedula-rounded schedula-text-sm disabled:schedula-cursor-not-allowed focus:outline-none"
                            :style="{
                              backgroundColor: (additionalGuests >= (globalSettings.maxPersonsPerBooking - 1)) ? '#cccccc' : settings.colors.primary,
                              color: (additionalGuests >= (globalSettings.maxPersonsPerBooking - 1)) ? '#666666' : settings.colors.headerText,
                              border: 'none'
                            }"
                          >
                            +
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            <!-- Step 3: Payment -->
            <div v-if="!showSummary && currentStep === 3 && settings.payment.showPaymentStep" class="step-content schedula-flex-shrink-0">
              <div class="schedula-max-w-2xl schedula-mx-auto">
              <h3
                class="schedula-text-xl schedula-font-semibold schedula-text-gray-800 schedula-mb-6 schedula-text-center"
                :style="textStyles" @click="handleLabelClick('payment_method', labels.payment_method)"
                :class="{ 'preview-editable-label': isPreview }">
                {{ __('Choose Payment Method', 'schedula-smart-appointment-booking') }}
              </h3>

              <!-- Redesigned payment options with icons and horizontal layout -->
              <div
                class="schedula-payment-options-container schedula-flex schedula-flex-wrap schedula-justify-center schedula-gap-4">
                <!-- Cash Payment Option -->
                <div v-if="globalSettings.enableLocalPayment" :class="['schedula-payment-option', 'schedula-relative', 'schedula-w-full', enabledPaymentMethodsCount === 1 ? 'md:schedula-w-1/2' : 'md:schedula-w-[30%]']">
                  <input type="radio" v-model="formData.payment_method" value="cash" id="payment-cash"
                    class="schedula-sr-only" />
                  <label for="payment-cash"
                    class="schedula-payment-option-label schedula-flex schedula-items-center schedula-p-2 schedula-border-2 schedula-rounded-xl schedula-cursor-pointer schedula-transition-all schedula-duration-200 hover:schedula-shadow-md schedula-h-full"
                    :class="formData.payment_method === 'cash' ? 'schedula-border-blue-500 schedula-bg-blue-50' : 'schedula-border-gray-200 schedula-bg-white hover:schedula-border-gray-300'"
                    :style="cardStyles">
                    <div
                      class="schedula-flex schedula-items-center schedula-justify-center schedula-w-10 schedula-h-10 schedula-rounded-full schedula-mr-3 schedula-flex-shrink-0"
                      :class="formData.payment_method === 'cash' ? 'schedula-bg-blue-100' : 'schedula-bg-gray-100'">
                      <!-- Cash Icon -->
                      <svg class="schedula-w-5 schedula-h-5"
                        :class="formData.payment_method === 'cash' ? 'schedula-text-blue-600' : 'schedula-text-gray-600'"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z">
                        </path>
                      </svg>
                    </div>
                    <div class="schedula-flex-1">
                      <h4 class="schedula-text-base schedula-font-semibold schedula-text-gray-800 schedula-mb-0"
                        :style="textStyles">
                        {{ __('Pay with Cash', 'schedula-smart-appointment-booking') }}
                      </h4>
                      <p v-if="settings.payment.showPaymentMethodDescription"
                        class="schedula-text-xs schedula-text-gray-600">
                        {{ __('Pay at your appointment', 'schedula-smart-appointment-booking') }}
                      </p>
                    </div>
                    <div v-if="formData.payment_method === 'cash'" class="schedula-ml-3">
                      <svg class="schedula-w-4 schedula-h-4 schedula-text-blue-600" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                          clip-rule="evenodd"></path>
                      </svg>
                    </div>
                  </label>
                </div>


                <!-- Stripe Payment Option -->
                <div v-if="stripeSettings && stripeSettings.enableStripe"
                  :class="['schedula-payment-option', 'schedula-relative', 'schedula-w-full', enabledPaymentMethodsCount === 1 ? 'md:schedula-w-1/2' : 'md:schedula-w-[30%]']">
                  <input type="radio" v-model="formData.payment_method" value="stripe" id="payment-stripe"
                    class="schedula-sr-only" />
                  <label for="payment-stripe"
                    class="schedula-payment-option-label schedula-flex schedula-items-center schedula-p-2 schedula-border-2 schedula-rounded-xl schedula-cursor-pointer schedula-transition-all schedula-duration-200 hover:schedula-shadow-md schedula-h-full"
                    :class="formData.payment_method === 'stripe' ? 'schedula-border-blue-500 schedula-bg-blue-50' : 'schedula-border-gray-200 schedula-bg-white hover:schedula-border-gray-300'"
                    :style="cardStyles">
                    <div
                      class="schedula-flex schedula-items-center schedula-justify-center schedula-w-10 schedula-h-10 schedula-rounded-full schedula-mr-3 schedula-flex-shrink-0"
                      :class="formData.payment_method === 'stripe' ? 'schedula-bg-blue-100' : 'schedula-bg-gray-100'">
                      <!-- Stripe/Credit Card Icon -->
                      <svg class="schedula-w-5 schedula-h-5"
                        :class="formData.payment_method === 'stripe' ? 'schedula-text-blue-600' : 'schedula-text-gray-600'"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                        </path>
                      </svg>
                    </div>
                    <div class="schedula-flex-1">
                      <h4 class="schedula-text-base schedula-font-semibold schedula-text-gray-800 schedula-mb-0"
                        :style="textStyles">
                        {{ __('Pay with Card', 'schedula-smart-appointment-booking') }}
                      </h4>
                      <p v-if="settings.payment.showPaymentMethodDescription"
                        class="schedula-text-xs schedula-text-gray-600">
                        {{ __('Secure payment with credit/debit card', 'schedula-smart-appointment-booking') }}
                      </p>
                    </div>
                    <div v-if="formData.payment_method === 'stripe'" class="schedula-ml-3">
                      <svg class="schedula-w-4 schedula-h-4 schedula-text-blue-600" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                          clip-rule="evenodd"></path>
                      </svg>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Price Summary -->
              <div v-if="settings.payment.showPriceBreakdown" class="schedula-w-full schedula-text-center">
                <div
                  class="schedula-inline-block schedula-my-2 schedula-p-4 schedula-bg-gray-50 schedula-rounded-xl schedula-border"
                  :style="cardStyles">
                  <div class="schedula-flex schedula-justify-center schedula-items-center">
                    <span class="schedula-text-lg schedula-font-semibold schedula-text-gray-700 schedula-mr-2" :style="labelStyles">
                      {{ __('Total Amount', 'schedula-smart-appointment-booking') }}
                    </span>
                    <span class="schedula-text-2xl schedula-font-bold"
                      :style="{ color: settings.colors.primary }">
                      {{ formData.price ? formatPrice(formData.price) : '--' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            </div>

            <!-- Step 4: Confirmation -->
            <div v-if="!showSummary && currentStep === 4 && settings.confirmation.showSummaryStep"
                class="step-content schedula-flex-shrink-0">
                <div :class="stepContentMaxWidthClass" class="schedula-mx-auto">
                <h3 class="schedula-text-xl schedula-font-semibold schedula-text-gray-800 schedula-mb-5 schedula-text-center" :style="textStyles" @click="handleLabelClick('confirm_appointment', labels.confirm_appointment)"
                    :class="{'preview-editable-label': isPreview}">
                  {{ labels.confirm_appointment }}
                </h3>
                <div class="confirmation-summary schedula-rounded-xl schedula-p-6 schedula-border" 
                :class="innerCardPaddingClass" 
                :style="cardStyles">
                  <div class="schedula-flex schedula-flex-col sm:schedula-flex-row schedula-items-center schedula-mb-4 schedula-text-center sm:schedula-text-left">
                    <div v-if="settings.confirmation.showServiceImage && selectedService && selectedService.image_url" class="schedula-w-24 schedula-h-24 schedula-rounded-lg schedula-overflow-hidden schedula-shadow-md schedula-mb-3 sm:schedula-mb-0 sm:schedula-mr-4 schedula-flex-shrink-0">
                      <!-- Adjusted image display for confirmation step -->
                      <div style="padding-bottom: 100%;" class="schedula-relative schedula-w-full schedula-h-full">
                        <img 
                          :src="selectedService.image_url" 
                          :alt="selectedService.title" 
                          class="schedula-absolute schedula-inset-0 schedula-w-full schedula-h-full schedula-object-cover"
                          onerror="this.onerror=null;this.src=window.schedulaFrontendData?.pluginUrl + 'assets/images/placeholders/no-image-placeholder.png';"
                        />
                      </div>
                    </div>
                    <div>
                      <h4 class="schedula-font-bold schedula-text-lg schedula-text-gray-800" :style="textStyles">{{ selectedService?.title || 'N/A' }}</h4>
                      <p class="schedula-text-sm schedula-text-gray-600" :style="labelStyles">{{ selectedCategory?.name || 'N/A' }}</p>
                    </div>
                  </div>
                  <div class="schedula-space-y-3" v-if="settings.confirmation.showConfirmationDetails">
                    <div class="summary-item schedula-flex schedula-flex-col sm:schedula-flex-row schedula-justify-between schedula-items-start sm:schedula-items-center schedula-bg-white schedula-p-3 schedula-rounded-md schedula-shadow-sm" :style="cardStyles">
                      <span class="schedula-text-xs schedula-font-semibold schedula-text-gray-600" :style="labelStyles" @click="handleLabelClick('date_time', labels.date_time)"
                            :class="{'preview-editable-label': isPreview}">
                        {{ labels.date_time }}:
                      </span>
                      <span class="schedula-text-sm schedula-text-gray-800 schedula-font-medium schedula-text-right" :style="textStyles">{{ formatDate(formData.appointment_date) || 'N/A' }} at {{ formatTimeForDisplay(formData.appointment_time, formData.appointment_date) || 'N/A' }}</span>
                    </div>
                    <div class="summary-item schedula-flex schedula-flex-col sm:schedula-flex-row schedula-justify-between schedula-items-start sm:schedula-items-center schedula-bg-white schedula-p-3 schedula-rounded-md schedula-shadow-sm" :style="cardStyles" v-if="settings.forms.serviceForm.displayStaffNames">
                      <span class="schedula-text-xs schedula-font-semibold schedula-text-gray-600" :style="labelStyles" @click="handleLabelClick('employee_confirmation', labels.employee_confirmation)"
                            :class="{'preview-editable-label': isPreview}">
                        {{ labels.employee_confirmation }}:
                      </span>
                      <span class="schedula-text-sm schedula-text-gray-800 schedula-font-medium" :style="textStyles">{{ selectedStaff?.name || 'Any Available' }}</span>
                    </div>
                    <div v-if="globalSettings.enableGroupBooking && formData.number_of_persons > 0" class="summary-item schedula-flex schedula-flex-col sm:schedula-flex-row schedula-justify-between schedula-items-start sm:schedula-items-center schedula-bg-white schedula-p-3 schedula-rounded-md schedula-shadow-sm" :style="cardStyles">
                      <span class="schedula-text-xs schedula-font-semibold schedula-text-gray-600" :style="labelStyles" @click="handleLabelClick('number_of_persons_confirmation', labels.number_of_persons_confirmation)"
                            :class="{'preview-editable-label': isPreview}">
                        {{ labels.number_of_persons_confirmation || 'Number of People' }}:
                      </span>
                      <span class="schedula-text-sm schedula-text-gray-800 schedula-font-medium" :style="textStyles">{{ formData.number_of_persons }}</span>
                    </div>
                    <div v-if="formData.is_recurring" class="summary-item schedula-flex schedula-flex-col sm:schedula-flex-row schedula-justify-between schedula-items-start sm:schedula-items-centersm:schedula-items-center schedula-bg-white schedula-p-3 schedula-rounded-md schedula-shadow-sm" :style="cardStyles">
                      <span class="schedula-text-xs schedula-font-semibold schedula-text-gray-600" :style="labelStyles">
                        {{ labels.recurrence || 'Recurrence' }}:
                      </span>
                      <span class="schedula-text-sm schedula-text-gray-800 schedula-font-medium" :style="textStyles">
                        {{ formatRecurrenceSummary }}
                      </span>
                    </div>
                    <div class="summary-item schedula-flex schedula-flex-col sm:schedula-flex-row schedula-justify-between schedula-items-start sm:schedula-items-center schedula-bg-white schedula-p-3 schedula-rounded-md schedula-shadow-sm" :style="cardStyles">
                      <span class="schedula-text-xs schedula-font-semibold schedula-text-gray-600" :style="labelStyles" @click="handleLabelClick('duration_confirmation', labels.duration_confirmation)"
                            :class="{'preview-editable-label': isPreview}">
                        {{ labels.duration_confirmation }}:
                      </span>
                      <span class="schedula-text-sm schedula-text-gray-800 schedula-font-medium" :style="textStyles">{{ formattedDuration }}</span>
                    </div>
                    <div class="summary-item schedula-flex schedula-flex-col sm:schedula-flex-row sm:schedula-justify-center sm:schedula-items-center schedula-bg-white schedula-p-3 schedula-rounded-md schedula-shadow-sm" :style="cardStyles">
                      <span class="schedula-text-xs schedula-font-semibold schedula-text-gray-600" :style="labelStyles" @click="handleLabelClick('total_price_confirmation', labels.total_price_confirmation)"
                            :class="{'preview-editable-label': isPreview}">
                        {{ labels.total_price_confirmation }}:
                      </span>
                      <span class="schedula-font-bold schedula-text-base schedula-text-blue-600" :style="{ color: settings.colors.primary }">{{ formatPrice(formData.price || 0) }}</span>
                    </div>
                  </div>
                  <button 
                      v-if="isPreview && settings.confirmation.allowEditing" 
                      type="button" 
                      @click="editConfirmedDetails"
                      class="schedula-mt-4 schedula-w-full schedula-inline-flex schedula-items-center schedula-justify-center schedula-px-4 schedula-py-2 schedula-border schedula-border-transparent schedula-text-sm schedula-font-medium schedula-rounded-md schedula-shadow-sm schedula-text-white schedula-bg-indigo-600 hover:schedula-bg-indigo-700 focus:schedula-outline-none focus:schedula-ring-2 focus:schedula-ring-offset-2 focus:schedula-ring-indigo-500 schedula-transition schedula-duration-150 schedula-ease-in-out"
                  >
                      <i class="fas fa-edit schedula-mr-2"></i> <span @click="handleLabelClick('edit_details_button', labels.edit_details_button)"
                                                              :class="{'preview-editable-label': isPreview}">{{ labels.edit_details_button }}</span>
                  </button>
                </div>
                                <!-- Waiting for Payment Message -->
                  <div v-if="isWaitingForPayment" class="schedula-mt-5 schedula-bg-blue-50 schedula-border schedula-border-blue-200 schedula-text-blue-700 schedula-px-4 schedula-py-3 schedula-rounded-lg schedula-text-center" role="alert">
                      <div class="schedula-flex schedula-justify-center schedula-items-center">
                          <svg class="schedula-animate-spin schedula-h-5 schedula-w-5 schedula-mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                          </svg>
                          <span class="schedula-font-medium schedula-text-base">{{ labels.waiting_for_payment_message || 'Waiting for payment confirmation...' }}</span>
                      </div>
                  </div>

                  <!-- Success/Error Messages -->
                <div v-if="submissionError" class="schedula-mt-5 schedula-bg-red-50 schedula-border schedula-border-red-200 schedula-text-red-700 schedula-px-4 schedula-py-3 schedula-rounded-lg" role="alert">
                  <div class="schedula-flex schedula-items-center">
                    <svg class="schedula-w-4 schedula-h-4 schedula-mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    <span class="schedula-font-medium schedula-text-sm">{{ submissionError }}</span>
                  </div>
                </div>
                <div v-if="submissionSuccess" class="schedula-mt-5 schedula-bg-green-50 schedula-border schedula-border-green-200 schedula-text-green-700 schedula-px-6 schedula-py-5 schedula-rounded-lg schedula-text-center" role="alert">
                  <div class="schedula-flex schedula-justify-center schedula-items-center schedula-mb-2">
                    <svg class="schedula-w-5 schedula-h-5 schedula-mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span class="schedula-font-medium schedula-text-base">{{ submissionSuccess }}</span>
                  </div>
                  <button v-if="settings.confirmation.showBookAgainButton" type="button" @click="bookAgain" class="schedula-mt-3 schedula-inline-flex schedula-items-center schedula-px-6 schedula-py-3 schedula-bg-green-600 schedula-text-white schedula-font-medium schedula-rounded-md hover:schedula-bg-green-700 focus:schedula-outline-none focus:schedula-ring-2 focus:schedula-ring-offset-2 focus:schedula-ring-green-500 schedula-transition schedula-duration-150 schedula-ease-in-out schedula-text-base">
                    <svg class="schedula-w-4 schedula-h-4 schedula-mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    <span @click="handleLabelClick('book_again', labels.book_again)"
                          :class="{'preview-editable-label': isPreview}">{{ labels.book_again }}</span>
                  </button>
                </div>
              </div>
            </div>
          </form>
        </div>

        <!-- Navigation Buttons - Fixed at bottom -->
        <div :class="['schedula-flex schedula-space-x-4 lg:schedula-space-x-0 schedula-items-center schedula-py-4  schedula-border-t schedula-border-gray-200 schedula-justify-between schedula-flex-shrink-0 schedula-px-6  lg:schedula-px-4']">
          <!-- Back button (shown on all steps except summary) -->
          <button type="button" @click="prevStep" v-if="!showSummary && !submissionSuccess" class="schedula-navigation-btn schedula-w-full sm:schedula-w-auto schedula-inline-flex schedula-items-center schedula-px-5 schedula-py-2 schedula-border schedula-border-gray-300 schedula-rounded-lg schedula-shadow-sm schedula-text-sm schedula-font-medium schedula-transition schedula-duration-150 schedula-ease-in-out" :style="buttonSecondaryStyles">
            <svg class="schedula-w-4 schedula-h-4 schedula-mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            <span @click="handleLabelClick('previous', labels.previous)" :class="{'preview-editable-label': isPreview}">{{ labels.previous }}</span>
          </button>
          <div v-else class="schedula-w-full sm:schedula-w-auto"></div> <!-- Placeholder to maintain justify-between spacing -->
          
                  <button type="submit" @click="handleNext" v-if="!submissionSuccess && !isWaitingForPayment" :disabled="submitting" class="schedula-navigation-btn" :class="['schedula-w-full sm:schedula-w-auto schedula-inline-flex schedula-items-center schedula-justify-center schedula-px-8 schedula-py-4 schedula-border schedula-border-transparent schedula-text-base schedula-font-semibold schedula-rounded-lg schedula-shadow-md schedula-transition schedula-duration-150 schedula-ease-in-out schedula-transform hover:schedula-scale-105', isStepValid && !submitting ? 'schedula-bg-blue-600 schedula-text-white' : 'schedula-bg-gray-400 schedula-text-gray-200 disabled:schedula-cursor-not-allowed']" :style="buttonPrimaryStyles">
            <svg v-if="submitting" class="schedula-animate-spin schedula--ml-1 schedula-mr-2 schedula-h-4 schedula-w-4 schedula-text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="schedula-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span v-if="currentStep === visibleStepsCount" @click="handleLabelClick(submitting ? 'confirming' : 'confirm', submitting ? labels.confirming : labels.confirm)"
                  :class="{'preview-editable-label': isPreview}">
              {{ submitting ? labels.confirming : labels.confirm }}
            </span>
            <span v-else @click="handleLabelClick('continue', labels.continue)"
                  :class="{'preview-editable-label': isPreview}">
              {{ labels.continue }}
            </span>
            <svg v-if="currentStep < visibleStepsCount && !submitting" class="schedula-w-4 schedula-h-4 schedula-ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
          </button>
        </div>
      </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, toRefs, watch, onUnmounted, getCurrentInstance } from 'vue';
import { servicesCategoriesApi, appointmentsApi, staffApi, paymentsApi, stripeApi } from '@/admin/api.js'; // Import staffApi
import { useAppearanceSettings } from '@/frontend/components/composables/useAppearanceSettings.js';
import { useGlobalSettings } from '@/admin/composables/useGlobalSettings.js';
import BasePhoneInput from '@/frontend/components/common/BasePhoneInput.vue';
import { __ } from '@wordpress/i18n';

const isInitializing = ref(true);
const uniqueId = getCurrentInstance().uid;
const canShowForm = ref(false);
const props = defineProps({
  previewSettings: { type: Object, default: () => ({}) },
  previewStep: { type: Number, default: 1 },
  serviceId: { type: [Number, String], default: null },
  categoryId: { type: [Number, String], default: null },
  staffId: { type: [Number, String], default: null },
  isPreview: { type: Boolean, default: false },
  formSettings: { type: Object, default: () => ({}) }, // Keep this from ServiceFormClient
  initialCategories: { type: Array, default: () => [] }, // New prop
  initialServices: { type: Array, default: () => [] },   // New prop
});

const emit = defineEmits(['update-label', 'edit-label']); // Add 'update-label' from ClientReservationForm

const { previewSettings } = toRefs(props);

// Use the appearance settings composable for frontend styling (colors, layout, etc.)
// This `settings` object will hold the appearance-specific configurations
const { settings: defaultAppearanceSettings, isReady } = useAppearanceSettings(); // Renamed to avoid conflict

// Use the global settings composable for general business logic (like currency format, timezone)
// This `globalSettings` object will hold `general` settings from the backend.
const { generalSettings: globalSettings, fetchGlobalSettings: fetchGlobalSettingsComposable, formatPrice, formatTime } = useGlobalSettings();

const stripeSettings = ref(null);


// Create a computed property to format time slot labels based on the global timeFormat setting
const formattedAvailableTimeSlots = computed(() => {
  if (!availableTimeSlots.value) return [];
  return availableTimeSlots.value.map(slot => ({
    ...slot,
    label: formatTime(slot.value, { date: formData.appointment_date, includeTimezone: true })
  }));
});


// Reactive state for WordPress admin bar and mobile view detection
const hasWordPressAdminBar = ref(false);
const windowWidth = ref(window.innerWidth);
const isMobileView = computed(() => windowWidth.value < 640); // Tailwind's 'sm' breakpoint
const isDesktopView = computed(() => windowWidth.value >= 1024); // Tailwind's 'lg' breakpoint
const step1SubView = ref('date'); // 'date' or 'time'

const isSmallForm = computed(() => settings.value.layout.formWidth === '640px');


// Function to check for WordPress admin bar
const checkAdminBar = () => {
  hasWordPressAdminBar.value = document.getElementById('wpadminbar') !== null;
};

// Function to handle window resize for mobile view detection
const handleResize = () => {
  windowWidth.value = window.innerWidth;
};

// Override default settings with preview settings if available


// Helper for deep merging objects
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

// Combined settings from default, formSettings, and previewSettings
const settings = computed(() => {
  // Ensure defaultAppearanceSettings is available before merging
  const baseSettings = defaultAppearanceSettings || {}; // Provide an empty object fallback
  const merged = mergeDeep(baseSettings, props.formSettings);
  return mergeDeep(merged, props.previewSettings);
});

const labels = computed(() => {
  return settings.value.labels;
});

const updateLabel = (key, value) => {
  emit('update-label', { key, value });
};

const handleLabelClick = (key, currentValue) => {
  if (props.isPreview) { // Use props.isPreview here
    emit('edit-label', { key, value: currentValue });
  }
};


// Apply general styles from settings
const globalFormStyles = computed(() => ({
  fontFamily: settings.value.layout.fontFamily,
  fontSize: (settings.value.layout.fontSize === 'small' ? '14px' : settings.value.layout.fontSize === 'large' ? '18px' : '16px') + ' !important',
  color: settings.value.colors.textColor,
}));

// Adjusted formContainerStyles to apply rounded corners and shadows directly
const formContainerStyles = computed(() => ({
  
  borderRadius: settings.value.theme.roundedCorners ? (settings.value.layout.borderRadius === 'small' ? '4px' : settings.value.layout.borderRadius === 'medium' ? '8px' : '12px') : '0',
  boxShadow: settings.value.theme.shadows ? getShadowValue(settings.value.layout.shadowStrength) : 'none',
  backgroundColor: 'transparent',
  // Removed explicit max-height and height to let content define height,
  // allowing the entire page to scroll if content is long.
}));

const headerStyles = computed(() => ({
  backgroundColor: settings.value.colors.primary, // Header background matches primary color
}));

const headerTextStyles = computed(() => ({
  color: settings.value.colors.headerText,
}));

const headerSubtextStyles = computed(() => ({
  color: settings.value.colors.headerText + 'CC', // Slightly transparent
}));

const progressBarStyles = computed(() => ({
  backgroundColor: settings.value.colors.background, // Progress bar area uses background color
  borderBottom: `1px solid ${settings.value.colors.textColor}33`, // Added a subtle but visible border
}));

const formBodyStyles = computed(() => ({
  backgroundColor: settings.value.colors.background, // Main form content area uses background color
}));

const labelStyles = computed(() => ({
  color: settings.value.colors.textColor,
}));

const inputStyles = computed(() => ({
  borderRadius: settings.value.theme.roundedCorners ? (settings.value.layout.borderRadius === 'small' ? '4px' : settings.value.layout.borderRadius === 'medium' ? '8px' : '12px') : '0',
  fontSize: (settings.value.layout.fontSize === 'small' ? '14px' : settings.value.layout.fontSize === 'large' ? '18px' : '16px') + ' !important',
  // Reduced padding for a more compact look on mobile
  padding: (isMobileView.value
             ? (settings.value.layout.inputSize === 'small' ? '0.2rem 0.4rem' : settings.value.layout.inputSize === 'large' ? '0.4rem 0.8rem' : '0.3rem 0.6rem')
             : (settings.value.layout.inputSize === 'small' ? '0.3rem 0.5rem' : settings.value.layout.inputSize === 'large' ? '0.5rem 0.9rem' : '0.4rem 0.7rem')) + ' !important',
  backgroundColor: settings.value.colors.background, // Use new background color
  color: settings.value.colors.textColor,
  borderColor: settings.value.colors.textColor + '33', // Light border from text color
  appearance: 'none !important',
  '-webkit-appearance': 'none !important',
  '-moz-appearance': 'none !important',
}));

const cardStyles = computed(() => ({
  backgroundColor: settings.value.colors.background, // Use new background color
  border: `1px solid ${settings.value.colors.textColor}1A`, // Light border from text color
  borderRadius: settings.value.theme.roundedCorners ? (settings.value.layout.borderRadius === 'small' ? '4px' : settings.value.layout.borderRadius === 'medium' ? '8px' : '12px') : '0',
  // Conditional shadows for cards based on settings.value.theme.shadows
  boxShadow: settings.value.theme.shadows ? getShadowValue(settings.value.layout.shadowStrength) : 'none',
  // Animations controlled by settings.value.theme.animations
  transition: 'all 0.2s ease-in-out',
}));

const cardStylesDashed = computed(() => ({
  backgroundColor: settings.value.colors.background + '80', // Slightly transparent background
  borderColor: settings.value.colors.textColor + '33', // Light border from text color
  borderRadius: settings.value.theme.roundedCorners ? (settings.value.layout.borderRadius === 'small' ? '4px' : settings.value.layout.borderRadius === 'medium' ? '8px' : '12px') : '0',
}));

const checkboxRadioStyles = computed(() => ({
  accentColor: settings.value.colors.primary,
}));

const buttonPrimaryStyles = computed(() => ({
  backgroundColor: settings.value.colors.primary,
  color: settings.value.colors.headerText, // Assuming button text is header text color (white)
  borderRadius: settings.value.theme.roundedCorners ? (settings.value.layout.borderRadius === 'small' ? '4px' : settings.value.layout.borderRadius === 'medium' ? '8px' : '12px') : '0',
  fontSize: (settings.value.layout.fontSize === 'small' ? '14px' : settings.value.layout.fontSize === 'large' ? '18px' : '16px') + ' !important',
  padding: (settings.value.layout.inputSize === 'small' ? '0.375rem 0.75rem' : settings.value.layout.inputSize === 'large' ? '0.625rem 1.25rem' : '0.5rem 1rem') + ' !important',
  boxShadow: settings.value.theme.shadows ? getShadowValue(settings.value.layout.shadowStrength) : 'none',
  transition: 'all 0.2s ease-in-out',
}));

const buttonSecondaryStyles = computed(() => ({
  backgroundColor: settings.value.colors.background, // Secondary button uses background color
  color: settings.value.colors.textColor, // Secondary button text uses text color
  border: `1px solid ${settings.value.colors.textColor}33`, // Light border
  borderRadius: settings.value.theme.roundedCorners ? (settings.value.layout.borderRadius === 'small' ? '4px' : settings.value.layout.borderRadius === 'medium' ? '8px' : '12px') : '0',
  fontSize: (settings.value.layout.fontSize === 'small' ? '14px' : settings.value.layout.fontSize === 'large' ? '18px' : '16px') + ' !important',
  padding: (settings.value.layout.inputSize === 'small' ? '0.375rem 0.75rem' : settings.value.layout.inputSize === 'large' ? '0.625rem 1.25rem' : '0.5rem 1rem') + ' !important',
  boxShadow: settings.value.theme.shadows ? getShadowValue(settings.value.layout.shadowStrength) : 'none',
  transition: 'all 0.2s ease-in-out',
}));

const textStyles = computed(() => ({
  color: settings.value.colors.textColor,
  transition: 'all 0.2s ease-in-out',
}));

// New computed property for form body padding
const formBodyPaddingClass = computed(() => {
  // Apply consistent padding for both small and large forms
  // schedula-pl-6 provides 1.5rem (24px) padding on the left
  // schedula-pr-8 provides 2rem (32px) padding on the right
  return 'schedula-pl-6 schedula-pr-8';
});

// New computed property for step content max-width
const stepContentMaxWidthClass = computed(() => {
  if (settings.value.layout.formWidth === '640px') { // Small form width
    return 'schedula-max-w-md'; // Constrain to 448px for small forms
  }
  return 'schedula-max-w-2xl'; // Default max-width for larger forms
});

// New computed property for inner card padding
const innerCardPaddingClass = computed(() => {
  if (settings.value.layout.formWidth === '640px') { // Small form width
    return 'schedula-p-4'; // Reduced padding for inner cards (1rem = 16px)
  }
  return 'schedula-p-6'; // Default padding (1.5rem = 24px)
});

// Helper to get shadow value based on strength
const getShadowValue = (strength) => {
  switch (strength) {
    case 'none': return 'none';
    case 'small': return '0 1px 2px 0 rgba(0, 0, 0, 0.05)';
    case 'medium': return '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';
    case 'large': return '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
    default: return '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';
  }
};

const getStepCircleStyles = (stepId) => {
  const base = {
    borderRadius: settings.value.theme.roundedCorners ? '9999px' : '0', // Full rounded
    transition: 'all 0.3s ease-in-out',
  };
  // Hardcoding progress bar colors to use primary/background/text colors
  const completedColor = settings.value.colors.primary;
  const activeColor = settings.value.colors.primary;
  const pendingColor = settings.value.colors.textColor + '33'; // Light grey from text color

  if (currentStep.value > stepId) {
    return { ...base, backgroundColor: completedColor };
  } else if (currentStep.value === stepId) {
    return { ...base, backgroundColor: activeColor };
  } else {
    return { ...base, backgroundColor: pendingColor };
  }
};

const getStepLineStyles = (stepId) => {
  const base = {
    borderRadius: settings.value.theme.roundedCorners ? '9999px' : '0', // Full rounded
    transition: 'all 0.3s ease-in-out',
  };
  const completedColor = settings.value.colors.primary;
  const pendingColor = settings.value.colors.textColor + '33';

  if (currentStep.value > stepId) {
    return { ...base, backgroundColor: completedColor };
  } else {
    return { ...base, backgroundColor: pendingColor };
  }
};

const getCalendarDateStyles = (date) => {
  const base = {
    borderRadius: settings.value.theme.roundedCorners ? '9999px' : '0', // Full rounded
    transition: 'background-color 0.2s ease-in-out, color 0.2s ease-in-out, box-shadow 0.2s ease-in-out',
    boxShadow: 'none', // Ensure no persistent shadow
    transform: 'scale(1.0)', // Ensure no scaling
  };

  if (date.isSelected) {
    return {
      ...base,
      backgroundColor: settings.value.colors.primary + ' !important',
      color: settings.value.colors.headerText + ' !important',
      boxShadow: settings.value.theme.shadows && !isMobileView.value ? getShadowValue(settings.value.layout.shadowStrength) + ' !important' : 'none !important',
      transform: !isMobileView.value ? 'scale(1.05) !important' : 'scale(1.0) !important' // Scale on desktop selected
    };
  } else if (date.isToday && !date.isSelected) {
    return {
      ...base,
      backgroundColor: settings.value.colors.primary + '1A !important',
      color: settings.value.colors.primary + ' !important',
      fontWeight: 'bold !important'
    };
  } else if (date.disabled || date.isOtherMonth) {
    return {
      ...base,
      color: '#9CA3AF !important', // Fixed grey color for disabled dates
      cursor: 'not-allowed !important',
      backgroundColor: 'transparent !important'
    }; // Lighter text color for disabled
  } else {
    return {
      ...base,
      backgroundColor: settings.value.colors.background + ' !important',
      color: settings.value.colors.textColor + ' !important'
    };
  }
};

const getTimeSlotButtonStyles = (slot, date) => {
  const base = {
    borderRadius: settings.value.theme.roundedCorners ? (settings.value.layout.borderRadius === 'small' ? '4px' : settings.value.layout.borderRadius === 'medium' ? '8px' : '12px') : '0',
    transition: 'all 0.2s ease-in-out',
    fontSize: (settings.value.layout.fontSize === 'small' ? '12px' : settings.value.layout.fontSize === 'large' ? '16px' : '14px') + ' !important',
    // Adjusted padding for compactness
    padding: (settings.value.layout.inputSize === 'small' ? '0.2rem 0.4rem' : settings.value.layout.inputSize === 'large' ? '0.4rem 0.8rem' : '0.3rem 0.6rem') + ' !important',
    boxShadow: 'none', // Ensure no persistent shadow
    transform: 'scale(1.0)', // Ensure no scaling
  };

  const isSelected = (formData.appointment_date === date && formData.appointment_time === slot) || (formData.appointment_time === slot && !date); // For list view or default view

  if (isSelected) {
    return {
      ...base,
      backgroundColor: settings.value.colors.primary,
      color: settings.value.colors.headerText,
      borderColor: settings.value.colors.primary,
      boxShadow: settings.value.theme.shadows && !isMobileView.value ? getShadowValue(settings.value.layout.shadowStrength) : 'none',
      transform: !isMobileView.value ? 'scale(1.05)' : 'scale(1.0)' // Scale on desktop selected
    };
  } else {
    return {
      ...base,
            backgroundColor: settings.value.colors.background, 
      color: settings.value.colors.textColor, 
      borderColor: settings.value.colors.textColor + '33'
    };
  }
};


// Steps configuration (using IDs for easier mapping with currentStep)
const allSteps = [
  { id: 1, title: computed(() => labels.value.step_2_title), subtitle: computed(() => labels.value.step_2_subtitle) },
  { id: 2, title: computed(() => labels.value.step_3_title), subtitle: computed(() => labels.value.step_3_subtitle) },
  { id: 3, title: computed(() => labels.value.step_4_title), subtitle: computed(() => labels.value.step_4_subtitle) },
  { id: 4, title: computed(() => labels.value.step_5_title), subtitle: computed(() => labels.value.step_5_subtitle) }
];

// Filter steps based on settings (e.g., hide payment step if not enabled)
const visibleSteps = computed(() => {
  return allSteps.filter(step => {
    if (step.id === 3 && !settings.value.payment.showPaymentStep) {
      return false; // Hide payment step if disabled
    }
    if (step.id === 4 && !settings.value.confirmation.showSummaryStep) {
      return false; // Hide confirmation step if disabled
    }
    return true;
  });
});

const visibleStepsCount = computed(() => visibleSteps.value.length);
const currentStepInfo = computed(() => visibleSteps.value.find(step => step.id === currentStep.value));

const formatRecurrenceSummary = computed(() => {
  if (!formData.is_recurring) return '';
  
  const interval = formData.recurrence_interval > 1 ? `${formData.recurrence_interval} ${formData.recurrence_frequency}` : formData.recurrence_frequency;
  
  let summary = `Repeats ${interval}`;
  if (formData.recurrence_count) {
    summary += ` for ${formData.recurrence_count} occurrences`;
  }
  return summary;
});


// Calendar state
const currentCalendarDate = ref(new Date());

// Reactive state for the multi-step form
const formData = reactive({
  category_id: props.categoryId, // Initialize from prop
  service_id: props.serviceId,   // Initialize from prop
  staff_id: props.staffId || '0', // Initialize from prop, default to '0'
  appointment_date: '',
  appointment_time: '',
  customer_first_name: '',
  customer_last_name: '',
  customer_email: '',
  customer_phone: '',
  payment_method: 'cash',
  duration: 0,
  price: 0.00,
  notes: '',
  marketing_consent: false,
  is_recurring: false,
  recurrence_frequency: '',
  recurrence_interval: 1,
  recurrence_end_date: '',
  recurrence_count: null,
  number_of_persons: 1,
});

const baseServicePrice = ref(0);
  const isBringingGuests = ref(false);
  const additionalGuests = ref(1);

const displayPriceInCard = computed(() => baseServicePrice.value);

// Keep number_of_persons in sync with guest checkbox and count
watch([isBringingGuests, additionalGuests], () => {
  if (isBringingGuests.value) {
    formData.number_of_persons = 1 + additionalGuests.value;
  } else {
    formData.number_of_persons = 1;
  }
});

// Controls for incrementing/decrementing additional guests
const incrementGuests = () => {
  const maxGuests = globalSettings.value?.maxPersonsPerBooking || 1;
  if (additionalGuests.value < (maxGuests - 1)) {
    additionalGuests.value++;
    formData.number_of_persons = 1 + additionalGuests.value;
  }
};

const decrementGuests = () => {
  if (additionalGuests.value > 1) {
    additionalGuests.value--;
    formData.number_of_persons = 1 + additionalGuests.value;
  }
};

const personOptions = computed(() => {
  if (!globalSettings.value || !globalSettings.value.enableGroupBooking) {
    return [1];
  }
  const max = globalSettings.value.maxPersonsPerBooking || 1;
  return Array.from({ length: max }, (_, i) => i + 1);
});

const recurrenceCountOptions = computed(() => {
  const max = globalSettings.value?.recurrence?.maxRecurrences;
  if (!max || max <= 0) {
    // If no limit (0) or not set, provide a default range up to 52 (for weekly appointments in a year).
    return Array.from({ length: 52 }, (_, i) => i + 1);
  }
  return Array.from({ length: max }, (_, i) => i + 1);
});

const calculateGroupBookingPrice = (basePrice, numberOfPersons, groupBookingLogic) => {
  if (!groupBookingLogic) {
    return basePrice * numberOfPersons;
  }
  let adjustedPrice = basePrice;
  const type = groupBookingLogic.type || 'per_person_multiply';
  const amount = parseFloat(groupBookingLogic.amount || 0);

  switch (type) {
    case 'per_person_multiply':
      adjustedPrice = basePrice * numberOfPersons;
      break;
    case 'fixed_discount_per_person': {
      const priceAfterDiscountPerPerson = basePrice - amount;
      adjustedPrice = (priceAfterDiscountPerPerson > 0 ? priceAfterDiscountPerPerson : 0) * numberOfPersons;
      break;
    }
    case 'percentage_discount_total': {
      const totalInitialPrice = basePrice * numberOfPersons;
      const discountAmount = (totalInitialPrice * amount) / 100;
      adjustedPrice = totalInitialPrice - discountAmount;
      break;
    }
    default:
      adjustedPrice = basePrice * numberOfPersons;
      break;
  }

  return adjustedPrice < 0 ? 0 : adjustedPrice;
};

const updateTotalPrice = () => {
  let singleAppointmentPrice = baseServicePrice.value;

  if (globalSettings.value?.enableGroupBooking && formData.number_of_persons > 1) {
    singleAppointmentPrice = calculateGroupBookingPrice(
      baseServicePrice.value,
      formData.number_of_persons,
      globalSettings.value.groupBookingPriceLogic
    );
  } else {
    singleAppointmentPrice = baseServicePrice.value;
  }

  if (formData.is_recurring && formData.recurrence_count > 0) {
    formData.price = singleAppointmentPrice * formData.recurrence_count;
  } else {
    formData.price = singleAppointmentPrice;
  }
  formData.price = parseFloat(formData.price.toFixed(2));
};

watch(() => formData.number_of_persons, updateTotalPrice);
watch(() => formData.is_recurring, updateTotalPrice);
watch(() => formData.recurrence_count, updateTotalPrice);
watch(baseServicePrice, updateTotalPrice);

// Reactive object for validation errors
const validationErrors = reactive({});
// New flag to control when errors are shown
const formSubmitted = ref(false);

// Computed property to decide whether to show error for a specific field
const showError = (fieldName) => {
  return formSubmitted.value && validationErrors[fieldName];
};

// Function to clear error when user types/changes input
const clearError = (fieldName) => {
  if (validationErrors[fieldName]) {
    delete validationErrors[fieldName];
  }
};


// Data for dropdowns and availability
const loadingInitialData = ref(false);
const initialDataError = ref(null);

const categories = ref([]);
const allServices = ref([]);
const allStaff = ref([]);

const availableStaffForService = ref([]);

// availableTimeSlots now stores objects { value: "HH:MM", label: "HH:MM AM/PM (Timezone)" }
const availableTimeSlots = ref([]);
const loadingTimeSlots = ref(false);
const timeSlotsError = ref(null);

// Computed properties for summary display
const selectedCategory = computed(() => {
  return categories.value.find(cat => cat.id == formData.category_id);
});

const selectedService = computed(() => {
  return allServices.value.find(svc => svc.id == formData.service_id);
});

const selectedStaff = computed(() => {
  if (formData.staff_id && formData.staff_id !== '0') {
    return allStaff.value.find(staff => staff.id == formData.staff_id);
  }
  return null;
});

const formattedDuration = computed(() => {
  const totalMinutes = formData.duration;
  if (!totalMinutes || totalMinutes <= 0) {
    return '--';
  }
  if (totalMinutes >= 60 && totalMinutes % 60 === 0) {
    const hours = totalMinutes / 60;
    return `${hours} ${hours > 1 ? __('hours', 'schedula-smart-appointment-booking') : __('hour', 'schedula-smart-appointment-booking')}`;
  }
  return `${totalMinutes} ${__('minutes', 'schedula-smart-appointment-booking')}`;
});

// Calendar computed properties
const currentMonthYear = computed(() => {
  return currentCalendarDate.value.toLocaleDateString('en-US', {
    month: 'long',
    year: 'numeric'
  });
});

const calendarDates = computed(() => {
  const year = currentCalendarDate.value.getFullYear();
  const month = currentCalendarDate.value.getMonth();

  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const startDate = new Date(firstDay);
  startDate.setDate(startDate.getDate() - firstDay.getDay());

  const dates = [];
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  // Calculate minDate based on minTimeBooking (in minutes)
  // Ensure globalSettings is available before accessing its properties
  const minTimeBooking = globalSettings.value?.minTimeBooking ?? 60; // Default to 60 if undefined
  const daysAvailableBooking = globalSettings.value?.daysAvailableBooking ?? 365; // Default to 365 if undefined

  const minDateForBooking = new Date();
  minDateForBooking.setMinutes(minDateForBooking.getMinutes() + minTimeBooking);
  minDateForBooking.setHours(0,0,0,0); // Consider only the date part for min selectable date

  // Calculate maxDate based on daysAvailableBooking
  const maxDateForBooking = new Date();
  maxDateForBooking.setDate(maxDateForBooking.getDate() + daysAvailableBooking);
  maxDateForBooking.setHours(23,59,59,999); // Consider end of day

  for (let i = 0; i < 42; i++) {
    const date = new Date(startDate);
    date.setDate(startDate.getDate() + i);

    const isOtherMonth = date.getMonth() !== month;
    const isPastOrTooSoon = date < minDateForBooking; // Check against calculated minDateForBooking
    const isTooFar = date > maxDateForBooking; // Check against calculated maxDateForBooking

    const isToday = date.getTime() === today.getTime();
    const isSelected = formData.appointment_date === formatDateForInput(date);

    dates.push({
      key: `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`,
      day: date.getDate(),
      date: date,
      isOtherMonth,
      disabled: isPastOrTooSoon || isTooFar, // Disable if too soon or too far
      isToday,
      isSelected
    });
  }

  return dates;
});

// State for the new list view
const listViewStep = ref('days'); // 'days' or 'times'
const selectedDateForListView = ref(null); // Stores the date object for the selected day in list view

const filteredTimeSlotsForListView = computed(() => {
  if (selectedDateForListView.value && selectedDateForListView.value.slots) {
    return selectedDateForListView.value.slots;
  }
  return [];
});

// For 'list' calendar layout: group available time slots by date
const calendarDatesGrouped = computed(() => {
  const grouped = {};
  formattedAvailableTimeSlots.value.forEach(slot => {
    // Ensure slot has a date property, which is set during fetchAvailableTimeSlots
    const date = slot.date;
    if (!grouped[date]) {
      grouped[date] = { date: date, slots: [] };
    }
    grouped[date].slots.push({ value: slot.value, label: slot.label });
  });

  // Sort dates
  const sortedDates = Object.values(grouped).sort((a, b) => new Date(a.date) - new Date(b.date));

  // Sort slots within each date
  sortedDates.forEach(dateEntry => {
    dateEntry.slots.sort((a, b) => {
      const timeA = new Date(`2000-01-01T${a.value}`);
      const timeB = new Date(`2000-01-01T${b.value}`);
      return timeA - timeB;
    });
  });

  return sortedDates;
});

// Submission state
const submitting = ref(false);
const submissionError = ref(null);
const submissionSuccess = ref(null);
const isWaitingForPayment = ref(false);
const showSummary = ref(true);
const recurrenceConflicts = ref([]);
const isCheckingConflicts = ref(false);
const isLoadingCustomerDetails = ref(false);

// Current step management
const currentStep = ref(props.previewStep || 1);

// Watch for changes in previewStep prop and update currentStep
watch(() => props.previewStep, (newStep) => {
  currentStep.value = newStep;
  console.log('ServiceFormClient: previewStep prop changed to', newStep, 'currentStep is now', currentStep.value);
  // Re-fetch time slots if navigating to step 2 and date is already selected or if list view is active
  if (newStep === 2) {
    if (settings.value.calendar.layoutStyle === 'list') {
      listViewStep.value = 'days'; // Reset to days view when entering step 2 in list layout
    }
    if (formData.appointment_date || settings.value.calendar.layoutStyle === 'list') {
      fetchAvailableTimeSlots();
    }
  }
  // If navigating to confirmation step, ensure all data is ready
  if (newStep === 5) {
    console.log('ServiceFormClient: Navigating to step 5 (Confirmation). Current settings.confirmation.showSummaryStep:', settings.value.confirmation.showSummaryStep);
    // Pre-fill some data for confirmation preview if needed when directly navigating to step 5
    if (props.isPreview && !formData.service_id) { // Only pre-fill if not already filled by user interaction
      formData.category_id = 1;
      formData.service_id = 101;
      formData.staff_id = 1;
      formData.appointment_date = formatDateForInput(new Date());
      formData.appointment_time = '10:00'; // Raw time for internal
      formData.duration = 60;
      formData.price = 50.00;
      formData.customer_first_name = 'John';
      formData.customer_last_name = 'Doe';
      formData.customer_email = 'john.doe@example.com';
      formData.customer_phone = '123-456-7890';
    }
  }
});

watch(currentStep, (newStep, oldStep) => {
    if (newStep === 1 && !isDesktopView.value) {
        if (oldStep === 2 && formData.appointment_date) {
            step1SubView.value = 'time';
        } else {
            step1SubView.value = 'date';
        }
    }
});

watch(submissionSuccess, (newValue, oldValue) => {
  if (oldValue === true && newValue === null) { // Transition from success state to non-success (e.g., after bookAgain)
    // This is where bookAgain would have reset the form.
    // Re-initialize price and duration here.
    // Ensure formData.service_id is set from props if it was reset
    if (!formData.service_id && props.serviceId) {
        formData.service_id = props.serviceId;
    }

    const service = allServices.value.find(s => s.id == formData.service_id);
    if (service) {
        formData.duration = service.duration;
        baseServicePrice.value = parseFloat(service.price);
        formData.price = parseFloat(service.price);
        
        formData.number_of_persons = 1;
        updateTotalPrice();
    } else {
        // If service not found (e.g., allServices not loaded yet or serviceId is invalid)
        // Reset price/duration to 0 to avoid displaying old values
        formData.duration = 0;
        baseServicePrice.value = 0;
        formData.price = 0;
    }
  }
});

// Calendar methods
const previousMonth = async () => {
  currentCalendarDate.value = new Date(
    currentCalendarDate.value.getFullYear(),
    currentCalendarDate.value.getMonth() - 1,
    1
  );
  if (settings.value.calendar.layoutStyle === 'list') {
    await fetchAvailableTimeSlots();
  }
};

const nextMonth = async () => {
  currentCalendarDate.value = new Date(
    currentCalendarDate.value.getFullYear(),
    currentCalendarDate.value.getMonth() + 1,
    1

  );
  if (settings.value.calendar.layoutStyle === 'list') {
    await fetchAvailableTimeSlots();
  }
};

const selectDate = async(dateObj) => {
  if (dateObj.disabled || dateObj.isOtherMonth) return;

  formData.appointment_date = formatDateForInput(dateObj.date);
  formData.appointment_time = '';
  availableTimeSlots.value = [];
  await fetchAvailableTimeSlots(); // This will populate availableTimeSlots
  clearError('appointment_date'); // Clear error on date selection
  clearError('appointment_time'); // Clear time error
  clearError('available_slots'); // Clear slot error
  if (!isDesktopView.value) {
    step1SubView.value = 'time';
  }
};

const selectTimeSlot = (time) => {
  formData.appointment_time = time;
  clearError('appointment_time');
  clearError('available_slots');
};

const selectDayForListView = (dateEntry) => {
  selectedDateForListView.value = dateEntry;
  listViewStep.value = 'times';
  formData.appointment_date = dateEntry.date;
  formData.appointment_time = ''; // Reset time when changing day
};

const goBackToDaysList = () => {
  listViewStep.value = 'days';
};

const selectDateTimeFromList = (date, time) => {
  formData.appointment_date = date;
  formData.appointment_time = time;
  clearError('appointment_date');
  clearError('appointment_time');
  clearError('available_slots');
};

const formatDateForInput = (date) => {
  const year = date.getFullYear();
  const month = (date.getMonth() + 1).toString().padStart(2, '0');
  const day = date.getDate().toString().padStart(2, '0');
  return `${year}-${month}-${day}`;
};

// Utility functions
const formatDate = (dateString) => {
  if (!dateString) return '';
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(dateString).toLocaleDateString(undefined, options);
};

const formatTimeForDisplay = (timeString, dateString) => {
  if (!timeString || !dateString) return '';
  // Use the global formatTime function, which respects the timeFormat setting and handles timezone display
  return formatTime(timeString, { date: dateString, includeTimezone: true });
};


// Step validation
const validateCurrentStep = () => {
  // Clear only errors related to the current step before re-validating.
  Object.keys(currentStepFields[currentStep.value] || {}).forEach(key => {
    if (validationErrors[key]) {
      delete validationErrors[key];
    }
  });

  let isValid = true;

  switch (currentStep.value) {
    case 1: // Date & Time
      if (!formData.appointment_date) {
        validationErrors.appointment_date = 'Please select a date.';
        isValid = false;
      }
      if (!formData.appointment_time) {
        validationErrors.appointment_time = 'Please select a time slot.';
        isValid = false;
      }
      if (availableTimeSlots.value.length === 0 && formData.appointment_date && formData.service_id) {
          validationErrors.available_slots = 'No available time slots for the selected date and staff.';
          isValid = false;
      }
      if (formData.is_recurring) {
        if (!formData.recurrence_frequency) {
          validationErrors.recurrence_frequency = 'Please select a recurrence frequency.';
          isValid = false;
        }
        if (!formData.recurrence_interval || formData.recurrence_interval < 1) {
          validationErrors.recurrence_interval = 'Recurrence interval must be at least 1.';
          isValid = false;
        }
        if (!formData.recurrence_count || formData.recurrence_count < 1) {
          validationErrors.recurrence_count = 'Recurrence count must be at least 1.';
          isValid = false;
        }
        const maxRecurrences = globalSettings.value?.recurrence?.maxRecurrences;
        if (maxRecurrences > 0 && formData.recurrence_count > maxRecurrences) {
            validationErrors.recurrence_count = `The number of recurrences cannot exceed the maximum of ${maxRecurrences}.`;
            isValid = false;
        }
      }
      break;
    case 2: // Personal Details
      if (settings.value.customer.showFirstNameField && !formData.customer_first_name.trim()) {
        validationErrors.customer_first_name = 'First name is required.';
        isValid = false;
      }
      if (settings.value.customer.showLastNameField && !formData.customer_last_name.trim()) {
        validationErrors.customer_last_name = 'Last name is required.';
        isValid = false;
      }
      if (settings.value.customer.showEmailField) {
        if (!formData.customer_email.trim()) {
            validationErrors.customer_email = 'Email is required.';
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.customer_email)) {
            validationErrors.customer_email = 'Please enter a valid email address.';
            isValid = false;
        }
      }
      break;
    case 3: // Payment
      break;
    case 4: // Confirmation
      break;
  }
  return isValid;
};

const isStepValid = computed(() => {
  const fieldsForStep = Object.keys(currentStepFields[currentStep.value] || {});
  for (const field of fieldsForStep) {
    if (validationErrors[field]) {
      return false;
    }
  }
  
  switch (currentStep.value) {
    case 1:
      if (!formData.appointment_date || !formData.appointment_time) return false;
      if (formData.is_recurring && (!formData.recurrence_frequency || !formData.recurrence_interval || !formData.recurrence_count)) return false;
      break;
    case 2:
      if (settings.value.customer.showFirstNameField && !formData.customer_first_name) return false;
      if (settings.value.customer.showLastNameField && !formData.customer_last_name) return false;
      if (settings.value.customer.showEmailField && !formData.customer_email) return false;
      break;
  }

  return true;
});

const enabledPaymentMethodsCount = computed(() => {
  let count = 0;
  if (globalSettings.value.enableLocalPayment) count++;
  if (stripeSettings.value?.enableStripe) count++;
  return count;
});

const fetchCustomerByEmail = async () => {
  clearError('customer_email');
  if (formData.customer_email && /^[^@S@]+@[^@S]+\.[^@S]+$/.test(formData.customer_email)) {
    isLoadingCustomerDetails.value = true; // Set loading to true
    try {
            const response = await appointmentsApi.getCustomerByEmail(formData.customer_email);
      if (response.data) {
        const customer = response.data;
        formData.customer_first_name = customer.first_name || formData.customer_first_name;
        formData.customer_last_name = customer.last_name || formData.customer_last_name;
        formData.customer_phone = customer.phone || formData.customer_phone;
      }
    } catch (error) {
      if (error.response && error.response.status !== 404) {
        console.error('Error fetching customer details:', error);
      }
    } finally {
      isLoadingCustomerDetails.value = false; // Set loading to false
    }
  }
};

const debouncedFetchCustomer = debounce(fetchCustomerByEmail, 500);



function debounce(fn, delay) {
  let timeoutID = null;
  return function (...args) {
    clearTimeout(timeoutID);
    timeoutID = setTimeout(() => {
      fn(...args);
    }, delay);
  };
}

const checkRecurrence = async () => {
  if (
    !formData.is_recurring ||
    !formData.service_id ||
    !formData.staff_id ||
    !formData.appointment_date ||
    !formData.appointment_time ||
    !formData.recurrence_frequency ||
    !formData.recurrence_interval ||
    !formData.recurrence_count
  ) {
    recurrenceConflicts.value = [];
    return;
  }

  isCheckingConflicts.value = true;
  recurrenceConflicts.value = [];
  try {
    const response = await appointmentsApi.checkRecurrenceConflicts({
      service_id: formData.service_id,
      staff_id: formData.staff_id,
      appointment_date: formData.appointment_date,
      appointment_time: formData.appointment_time,
      recurrence_frequency: formData.recurrence_frequency,
      recurrence_interval: formData.recurrence_interval,
      recurrence_count: formData.recurrence_count,
    });
    recurrenceConflicts.value = response.data.conflicting_dates || [];
  } catch (error) {
    console.error('Error checking recurrence conflicts:', error);
  } finally {
    isCheckingConflicts.value = false;
  }
};

const debouncedCheckRecurrence = debounce(checkRecurrence, 1000);

watch(
  () => [
    formData.is_recurring,
    formData.recurrence_frequency,
    formData.recurrence_interval,
    formData.recurrence_count,
    formData.appointment_date,
    formData.appointment_time,
    formData.staff_id,
  ],
  () => {
    debouncedCheckRecurrence();
  },
  { deep: true }
);

// Define which fields belong to which step for more precise error clearing
const currentStepFields = {
  1: { appointment_date: true, appointment_time: true, available_slots: true },
  2: { customer_first_name: true, customer_last_name: true, customer_email: true, customer_phone: true },
  3: { payment_method: true }, // Add other payment fields if applicable
};

// Navigation methods
const handleNext = async () => {
    // 1. If we are on the summary view, just proceed to the first step.
    if (showSummary.value) {
      showSummary.value = false;
      return;
    }

    // 2. Set the form as submitted to display validation errors.
    formSubmitted.value = true;

    // 3. Run the basic validation checks for the current step's fields.
    if (!validateCurrentStep()) {
      return; // Stop if basic checks (like required fields) fail.
    }

    // 4. On the personal details step, perform the extra backend email validation.
    if (currentStep.value === 2) {
      submitting.value = true;
      submissionError.value = null;
      try {
        await appointmentsApi.getCustomerByEmail(formData.customer_email);
        // Customer exists, that's fine. Clear any previous errors.
        clearError('customer_email');
      } catch (error) {
        if (error.response && error.response.status === 404) {
            // This is expected for a new customer. Clear any previous errors and proceed.
            clearError('customer_email');
        } else {
            // For any other error (e.g., server error), show an error and stop.
            console.error(__('Error validating customer email:', 'schedula-smart-appointment-booking'), error);
            validationErrors.customer_email = error.response?.data?.message || __('There was a problem validating your email. Please try again.', 'schedula-smart-appointment-booking');
            submitting.value = false;
            return; // Stop progress
        }
      } finally {
        submitting.value = false;
      }
    }

    // 5. Handle preview mode on the final step to prevent actual submission.
    if (props.isPreview && currentStep.value === visibleSteps.value.length) {
      console.log('PREVIEW MODE: Appointment submission prevented.');
      submissionSuccess.value = 'PREVIEW: Appointment would have been booked successfully!';
      setTimeout(() => {
        submissionSuccess.value = null;
      }, 3000);
      return;
    }

    // 6. If on the final step, submit the form. Otherwise, go to the next step.
    if (currentStep.value === visibleSteps.value.length) {
      await submitAppointment();
    } else {
      currentStep.value++;
      formSubmitted.value = false; // Reset for the next step.
    }
  };

const prevStep = () => {
  if (currentStep.value === 1 && !isDesktopView.value && step1SubView.value === 'time') {
    step1SubView.value = 'date';
    return;
  }
  // If on the first step of the form, go back to the summary view
  if (currentStep.value === 1) {
    showSummary.value = true;
    return;
  }

  // Otherwise, navigate to the previous step in the sequence
  const currentStepIndex = visibleSteps.value.findIndex(step => step.id === currentStep.value);
  if (currentStepIndex > 0) {
    currentStep.value = visibleSteps.value[currentStepIndex - 1].id;
  } else {
    // Fallback, though should be covered by the summary check
    currentStep.value--;
  }
  submissionError.value = null;
  submissionSuccess.value = null;
  formSubmitted.value = false; // Reset flag when going back
};

const editConfirmedDetails = () => {
  currentStep.value = 3; // Go back to personal details step
  submissionSuccess.value = null; // Clear success message
  submissionError.value = null; // Clear any submission errors
  formSubmitted.value = false; // Reset form submitted state
};


const bookAgain = () => {
  Object.assign(formData, {
    category_id: props.categoryId,
    service_id: props.serviceId,
    staff_id: props.staffId || '0',
    appointment_date: '',
    appointment_time: '',
    customer_first_name: '',
    customer_last_name: '',
    customer_email: '',
    customer_phone: '',
    payment_method: 'cash',
    duration: 0,
    price: 0.00,
    notes: '',
    marketing_consent: false,
    number_of_persons: 1,
    is_recurring: false,
    recurrence_frequency: '',
    recurrence_interval: 1,
    recurrence_end_date: '',
    recurrence_count: null,
  });

  isWaitingForPayment.value = false;
  availableTimeSlots.value = [];
  availableStaffForService.value = [];
  submissionError.value = null;
  submissionSuccess.value = null;
  submitting.value = false;
  currentStep.value = 1;
  formSubmitted.value = false;

  // Re-fetch initial data to ensure fresh service/category/staff details
  fetchBookingFormData().then(async() => {
    if (props.serviceId) {
      const service = allServices.value.find(s => s.id == props.serviceId);
      if (service) {
        formData.category_id = service.category_id;

        const staffOverride = service.staff_overrides?.find(o => o.staff_id == props.staffId);

        formData.duration = (staffOverride?.duration !== null && staffOverride?.duration !== undefined)
          ? parseInt(staffOverride.duration)
          : parseInt(service.duration) || 0;

        const price = (staffOverride?.price !== null && staffOverride?.price !== undefined)
          ? parseFloat(staffOverride.price)
          : parseFloat(service.price) || 0.00;

        baseServicePrice.value = price;
        formData.price = price;

        updateTotalPrice();
        await fetchAvailableTimeSlots();
      }
    }
  });
};

const handlePaymentRedirect = () => {
  isWaitingForPayment.value = true;
  submissionSuccess.value = null;
  submissionError.value = null;
};

const handleStorageChange = (event) => {
  if (event.key === 'schesab_payment_status') {
    isWaitingForPayment.value = false;
    if (event.newValue === 'success') {
      submissionSuccess.value = labels.value.booking_confirmed_message || 'Your appointment has been successfully booked!';
    } else {
      submissionError.value = labels.value.booking_failed_message || 'Payment failed or was cancelled. Please try again.';
    }
    submitting.value = false;
    localStorage.removeItem('schesab_payment_status');
    window.removeEventListener('storage', handleStorageChange);
  }
};

const submitAppointment = async () => {
  submitting.value = true;
  submissionError.value = null;
  submissionSuccess.value = null;

  try {
    // Based on payment method, either create appointment directly or initiate payment flow
    if (formData.payment_method === 'stripe') {
      const response = await stripeApi.createCheckoutSession({
        form_data: { ...formData }
      });
      if (response.data.checkout_url) {
        handlePaymentRedirect();
        window.open(response.data.checkout_url, '_blank');
      } else {
        throw new Error('Stripe Checkout URL not received.');
      }
    } else { // 'cash' or other local methods
      const response = await appointmentsApi.createAppointment({ ...formData });
      submissionSuccess.value = settings.value.labels.booking_success_message || 'Your appointment has been successfully booked!';
      submitting.value = false;
    }
  } catch (err) {
    submissionError.value = err.response?.data?.message || err.message || 'An unknown error occurred.';
    submitting.value = false;
  }
};

// Data fetching functions
const fetchBookingFormData = async () => {
  loadingInitialData.value = true;
  initialDataError.value = null;
  try {
    const response = await appointmentsApi.getBookingFormData();
    allServices.value = response.data.services;
    allStaff.value = response.data.staff;
    categories.value = response.data.categories;
  } catch (err) {
    initialDataError.value = err.response?.data?.message || err.message || 'Error fetching form data.';
    console.error('Error fetching booking form data:', err);
  } finally {
    loadingInitialData.value = false;
  }
};

const fetchAvailableTimeSlots = async () => {
  loadingTimeSlots.value = true;
  timeSlotsError.value = null;
  availableTimeSlots.value = [];
  formData.appointment_time = ''; // Clear selected time when fetching new slots

  console.log('fetchAvailableTimeSlots: Called. Layout Style:', settings.value.calendar.layoutStyle);

  if (settings.value.calendar.layoutStyle === 'list') {
    console.log('fetchAvailableTimeSlots: Handling list layout.');
    if (!formData.service_id) {
      console.log('fetchAvailableTimeSlots: service_id not selected for list layout.');
      loadingTimeSlots.value = false;
      return;
    }
    
    const year = currentCalendarDate.value.getFullYear();
    const month = currentCalendarDate.value.getMonth();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const minTimeBooking = globalSettings.value?.minTimeBooking ?? 60;
    const daysAvailableBooking = globalSettings.value?.daysAvailableBooking ?? 365;
    const minDateForBooking = new Date();
    minDateForBooking.setMinutes(minDateForBooking.getMinutes() + minTimeBooking);
    minDateForBooking.setHours(0,0,0,0);
    const maxDateForBooking = new Date();
    maxDateForBooking.setDate(maxDateForBooking.getDate() + daysAvailableBooking);
    maxDateForBooking.setHours(23,59,59,999);

    const promises = [];
    for (let i = 1; i <= daysInMonth; i++) {
      const date = new Date(year, month, i);
      if (date < minDateForBooking || date > maxDateForBooking) {
        continue;
      }

      const dateStr = formatDateForInput(date);
      const params = {
        service_id: formData.service_id,
        date: dateStr,
        staff_id: formData.staff_id === '0' ? 0 : formData.staff_id,
      };
      console.log('fetchAvailableTimeSlots: Fetching for date:', dateStr, 'with params:', params);
      promises.push(appointmentsApi.getAvailableTimeSlots(params).then(response => {
        console.log('fetchAvailableTimeSlots: API response for date', dateStr, ':', response.data);
        if (response.data && response.data.length > 0) {
          return response.data.map(timeStr => ({
            value: timeStr,
            label: formatTimeForDisplay(timeStr, dateStr),
            date: dateStr,
          }));
        }
        return [];
      }));
    }
    try {
      const results = await Promise.all(promises);
      availableTimeSlots.value = results.flat();
      console.log('fetchAvailableTimeSlots: availableTimeSlots after processing (list):', availableTimeSlots.value);
      console.log('fetchAvailableTimeSlots: calendarDatesGrouped after processing (list):', calendarDatesGrouped.value);
    } catch (err) {
      timeSlotsError.value = err.response?.data?.message || err.message || 'Error fetching available time slots for list view.';
      console.error('Error fetching time slots for list view:', err);
    }

  } else {
    console.log('fetchAvailableTimeSlots: Handling default layout.');
    // For live mode or if layout style is 'default' (single date fetch)
    if (!formData.service_id || !formData.appointment_date) {
      console.log('fetchAvailableTimeSlots: service_id or appointment_date not selected for default layout.');
      availableTimeSlots.value = [];
      timeSlotsError.value = null; // Clear error
      loadingTimeSlots.value = false;
      return;
    }

    try {
      const params = {
        service_id: formData.service_id,
        date: formData.appointment_date, // Use the selected date
        staff_id: formData.staff_id === '0' ? 0 : formData.staff_id, // Ensure staff_id is number 0 or actual ID
      };
      console.log('fetchAvailableTimeSlots: Fetching for single date:', formData.appointment_date, 'with params:', params);
      const response = await appointmentsApi.getAvailableTimeSlots(params);
      console.log('fetchAvailableTimeSlots: API response for single date:', response.data);

      let fetchedSlots = response.data.map(timeStr => ({
        value: timeStr,
        label: formatTimeForDisplay(timeStr, formData.appointment_date, globalSettings.value.displayTimezone),
        date: formData.appointment_date, // Crucial for list view grouping
      }));

      if (settings.value.calendar.showOnlyNearestTimeslot && fetchedSlots.length > 0) {
        // Sort slots by time and take only the first one
        fetchedSlots.sort((a, b) => {
          const timeA = new Date(`2000-01-01T${a.value}`);
          const timeB = new Date(`2000-01-01T${b.value}`);
          return timeA - timeB;
        });
        availableTimeSlots.value = [fetchedSlots[0]];
      } else {
        availableTimeSlots.value = fetchedSlots;
      }

      if (availableTimeSlots.value.length > 0) {
        const currentlySelectedTimeIsValid = availableTimeSlots.value.some(
          slot => slot.value === formData.appointment_time
        );
        if (!currentlySelectedTimeIsValid) {
          formData.appointment_time = availableTimeSlots.value[0].value;
        }
      } else {
        formData.appointment_time = ''; // Clear selection if no slots
      }
      console.log('fetchAvailableTimeSlots: availableTimeSlots after processing (default):', availableTimeSlots.value);

    } catch (err) {
      timeSlotsError.value = err.response?.data?.message || err.message || 'Error fetching available time slots.';
      console.error('Error fetching time slots:', err);
    }
  }
  loadingTimeSlots.value = false;
  
};

// Event handlers & Watchers are not needed as the service is pre-selected.

// Lifecycle Hook
onMounted(async () => {
  isInitializing.value = true;
  checkAdminBar();
  window.addEventListener('resize', handleResize);
  window.addEventListener('storage', handleStorageChange);

  try {
    // Use pre-loaded data if available, but not in preview mode
    if (window.schedulaServiceFormData && !props.isPreview) {
        const data = window.schedulaServiceFormData;
        if (data.global_settings) {
            Object.assign(globalSettings.value, data.global_settings);
        }
        
        if (data.stripe_settings) {
            stripeSettings.value = data.stripe_settings;
        }

        if(data?.global_settings?.enableLocalPayments){
          formData.payment_method = 'cash';
        } else if(data?.stripe_settings?.enableStripe) {
          formData.payment_method = 'stripe';
        } else {
          formData.payment_method = 'cash';
        }
        
        if (data.booking_form_data) {
            categories.value = data.booking_form_data.categories || [];
            allServices.value = data.booking_form_data.services || [];
            allStaff.value = data.booking_form_data.staff || [];
        }
    } else {
        // Fallback for preview mode or if data is missing
        await Promise.all([
            fetchGlobalSettingsComposable(),
            fetchBookingFormData(),
            stripeApi.getStripeSettings().then(res => stripeSettings.value = res.data).catch(err => console.error('Error fetching Stripe settings:', err)),
        ]);
    }

    // Initialize form data now that all data is available
    if (props.serviceId) {
      const service = allServices.value.find(s => s.id == props.serviceId);
      if (service) {
        // Set category and service IDs
        formData.category_id = service.category_id;
        
        // Find any staff-specific overrides for the pre-selected staff member
        const staffOverride = service.staff_overrides?.find(o => o.staff_id == props.staffId);

        // Set duration, using override if available
        formData.duration = (staffOverride?.duration !== null && staffOverride?.duration !== undefined)
          ? parseInt(staffOverride.duration)
          : parseInt(service.duration) || 0;

        // Set base price, using override if available
        const price = (staffOverride?.price !== null && staffOverride?.price !== undefined)
          ? parseFloat(staffOverride.price)
          : parseFloat(service.price) || 0.00;
          
        baseServicePrice.value = price;
        formData.price = price;
        
        // Populate the list of available staff for this service
        updateTotalPrice();
        await fetchAvailableTimeSlots();
        document.querySelector("#schedula-skeleton")?.remove();
      }
    }
    canShowForm.value = true;
    // Remove any existing skeleton loader if present
     document.querySelector("#schedula-skeleton")?.remove();
     console.log('Component initialized successfully.');
  } catch (error) {
      console.error("Error during component initialization:", error);
      initialDataError.value = "Failed to load form configuration. Please try again later.";
  } finally {
      isInitializing.value = false;
  }
   
});

onUnmounted(() => {
  window.removeEventListener('resize', handleResize);
  window.removeEventListener('storage', handleStorageChange);
});
</script>

<style scoped>
/* Global styles applied via CSS variables from useAppearanceSettings */
/* These are default values, they will be overridden by inline styles from computed properties */
/* Ensure these are in sync with useAppearanceSettings.js */
:root {
  /* Use CSS variables from useAppearanceSettings.js for global theme */
  /* These are NOT default values, they are the actual values applied by the composable */
  font-family: var(--font-family-form, 'Inter', sans-serif);
  font-size: var(--base-font-size-form, 1rem);
  --primary-color: var(--primary-color);
  --background-color: var(--background-color);
  --text-color: var(--text-color);
  --header-background-color: var(--header-background-color);
  --header-text-color: var(--header-text-color);
  --border-radius-form: var(--border-radius-form);
  --shadow-form: var(--shadow-form);
}

.schedula {

  /* Base container for the whole form */
  .wp-reservation-form {
    background-color: transparent !important;
    font-family: var(--font-family-form) !important; /* Apply font family */
    font-size: var(--base-font-size-form) !important; /* Apply base font size */
    color: var(--text-color) !important;
    /* min-h-screen is already applied via Tailwind in template */
    /* transition-all duration-300 is already applied via Tailwind in template */
  }

  /* Main form container (the "card") */
  /* Apply rounded corners and shadows directly from CSS variables */
  .relative.rounded-lg.overflow-hidden.shadow-lg.w-full.max-w-xl.mx-auto.flex.flex-col {
    border-radius: var(--border-radius-form) !important; /* Apply rounded corners */
    box-shadow: var(--shadow-form) !important; /* Apply shadows */
    height: auto !important;
    max-height: none !important;
  }

  /* Main Form Content Area - NO internal scrolling */
  .form-body {
    /* Styles are applied via inline styles from computed properties */
    flex-grow: 1 !important; /* Allows it to take available space */
    overflow-y: visible !important; /* Prevents internal scrolling */
    -webkit-overflow-scrolling: auto !important; /* Revert to default scrolling behavior */
  }

  /* Adjust desktop-progress for better spacing */
  .desktop-progress {
    padding-left: 1rem; /* px-4 */
    padding-right: 1rem; /* px-4 */
    padding-top: 1rem; /* py-4 */
    padding-bottom: 1rem; /* py-4 */
  }

  @media (min-width: 640px) { /* sm breakpoint */
    .desktop-progress {
      padding-left: 1.5rem; /* sm:px-6 */
      padding-right: 1.5rem; /* sm:px-6 */
      padding-top: 1.25rem; /* sm:py-5 */
      padding-bottom: 1.25rem; /* sm:py-5 */
    }
  }

  @media (min-width: 1024px) { /* lg breakpoint */
    .desktop-progress {
      padding-left: 1.5rem; /* lg:px-6 */
      padding-right: 1.5rem; /* lg:px-6 */
    }
  }

  .is-preview .desktop-progress {
    padding-left: 0.75rem !important; /* Slightly reduced for preview */
    padding-right: 0.75rem !important;
    padding-top: 0.75rem !important;
    padding-bottom: 0.75rem !important;
  }

  .is-preview .desktop-progress .ml-2 {
    margin-left: 0.375rem !important; /* Tighter margin for preview */
  }

  .is-preview .desktop-progress .flex-1 {
    flex: 1 1 0% !important;
    margin-left: 0.375rem !important;
    margin-right: 0.375rem !important;
  }

  .is-preview .desktop-progress .text-xs {
    font-size: 0.65rem !important; /* Match subtitle size */
    white-space: normal !important;
    word-break: break-word !important;
  }

  /* Form Group Styles (labels, inputs, selects, textareas) */
  .form-group {
    margin-bottom: 0.75rem;
  }

  .form-label {
    /* Styles applied via inline styles from computed properties */
    color: inherit;
  }

  .form-input,
  .form-select,
  .form-textarea {
    border-radius: var(--border-radius-form); /* Apply rounded corners */
    font-size: var(--base-font-size-form); /* Apply base font size */
    /* Padding adjusted in JS computed property: inputStyles */
    background-color: var(--background-color);
    color: var(--text-color);
    border-color: var(--text-color);
  }

  /* Remove all focus outlines and rings */
  .form-input:focus,
  .form-select:focus,
  .form-textarea:focus,
  button:focus,
  .form-checkbox:focus,
  .form-radio:focus {
    outline: none !important;
    box-shadow: none !important;
    border-color: inherit !important;
    --tw-ring-color: transparent !important;
  }

  /* Styles for editable labels in preview mode */
  .preview-editable-label {
    cursor: pointer;
    opacity: 0.6;
    transition: opacity 0.2s ease-in-out;
    font-size: 0.75rem; /* Smaller font size */
  }

  .preview-editable-label:hover {
    opacity: 1;
  }

  .form-textarea:focus,
  button:focus,
  input:focus,
  select:focus,
  textarea:focus,
  [tabindex]:focus,
  a:focus {
    outline: none !important;
    box-shadow: none !important;
    --tw-ring-color: transparent !important;
    --tw-ring-offset-shadow: none !important;
    --tw-ring-shadow: none !important;
    border-color: inherit !important;
  }

  /* For WebKit browsers like Chrome and Safari */
  *:focus {
    outline: none !important;
    box-shadow: none !important;
  }

  /* For Firefox */
  *::-moz-focus-inner {
    border: 0 !important;
  }

  /* For IE10+ */
  *:-ms-input-placeholder {
    color: #9CA3AF !important;
  }

  /* For Edge */
  *::-ms-input-placeholder {
    color: #9CA3AF !important;
  }

  .form-help-text {
    /* Tailwind classes handle this */
    color: inherit;
  }

  /* Checkbox/Radio Group */
  .checkbox-group {
    /* Tailwind classes handle this */
    display: flex;
    flex-direction: column;
  }

  .form-checkbox,
  .form-radio {
    accent-color: var(--primary-color);
  }

  /* Color Picker Specifics */
  .color-picker-group {
    /* Tailwind classes handle this */
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .color-input {
    /* Custom styles for color input */
    width: 2.5rem; /* w-10 */
    height: 2.5rem; /* h-10 */
    border: 1px solid #d1d5db; /* border-gray-300 */
    border-radius: 0.375rem; /* rounded-md */
    cursor: pointer;
    padding: 0;
    -webkit-appearance: none;
    appearance: none;
  }

  .color-input::-webkit-color-swatch-wrapper {
    padding: 0;
  }

  .color-input::-webkit-color-swatch {
    border: none;
    border-radius: 0.25rem; /* slightly less rounded than wrapper */
  }

  /* Buttons */
  .inline-flex.items-center {
    /* Common styles for all buttons, specific styles via :style */
    display: inline-flex;
    align-items: center;
  }

  /* This applies to all buttons globally, including nav buttons */
  button:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: var(--shadow-form); /* Apply shadows */
  }

  button:active:not(:disabled) {
    transform: translateY(0);
  }

  .time-slot:hover:not(.selected):not(:disabled),
  .time-slot-btn:hover:not(.selected):not(:disabled),
  .calendar-date:hover:not(.selected):not(:disabled) {
    background-color: v-bind("settings.colors.primary") !important;
    color: v-bind("settings.colors.headerText") !important;
    border-color: v-bind("settings.colors.primary") !important;
  }

  /* Prevent theme from affecting disabled date hovers */
  .calendar-date.disabled:hover,
  .calendar-date[disabled]:hover,
  .calendar-date.isOtherMonth:hover {
    color: #9CA3AF !important;
    background-color: transparent !important;
    cursor: not-allowed !important;
    opacity: 0.5 !important;
  }

  .calendar-date.selected,
  .time-slot.selected,
  .time-slot-btn.selected {
    background-color: v-bind("settings.colors.primary") !important;
    color: v-bind("settings.colors.headerText") !important;
    border-color: v-bind("settings.colors.primary") !important;
  }

  /* Force disabled calendar dates to be grey */
  .calendar-date.disabled,
  .calendar-date[disabled] {
    color: #9CA3AF !important;
    background-color: transparent !important;
    cursor: not-allowed !important;
    opacity: 0.5 !important;
  }

  /* Force other month dates to be grey */
  .calendar-date.isOtherMonth {
    color: #9CA3AF !important;
    background-color: transparent !important;
    cursor: not-allowed !important;
    opacity: 0.5 !important;
  }


  /* Cards (e.g., info-card, detail-card, confirmation-summary) */
  .info-card, .detail-card, .confirmation-summary {
    /* Styles applied via inline styles from computed properties */
    border-radius: var(--border-radius-form); /* Apply rounded corners */
    box-shadow: var(--shadow-form); /* Apply shadows */
  }

  /* Aspect Ratio for Service Preview Image */
  .relative.rounded-lg.overflow-hidden.shadow-lg > div[style*="padding-bottom"] {
    position: relative;
    width: 100%;
  }

  .relative.rounded-lg.overflow-hidden.shadow-lg img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  /* Time Slot Buttons & Calendar Dates */
  /* No scaling or strong shadows on hover/active for mobile */
  @media (max-width: 639px) { /* sm breakpoint */
    .calendar-date,
    .time-slot-btn,
    .time-slot {
      transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out !important; /* Keep subtle color transition */
      transform: scale(1.0) !important; /* Prevent scaling on mobile */
      box-shadow: none !important; /* Remove shadows on mobile */
    }

    /* Ensure selected state still looks good on mobile without scaling */
    .calendar-date.selected,
    .time-slot-btn.selected,
    .time-slot.selected {
      transform: scale(1.0) !important; /* Override selected scaling for mobile */
      box-shadow: none !important; /* Override selected shadow for mobile */
    }
  }

  /* Responsive adjustments for smaller screens (Tailwind breakpoints) */
  @media (max-width: 639px) { /* sm breakpoint */
    .header-section h1 {
      font-size: 1.125rem; /* text-lg */
    }
    .header-section p {
      font-size: 0.75rem; /* text-xs */
    }
    /* Calendar date height reduction on smaller screens */
    .calendar-date {
      height: 2.5rem; /* h-10 */
    }

    /* Adjust padding for the form body on mobile for compactness */
    .form-body {
      padding-left: 1rem; /* Smaller px-4 */
      padding-right: 1rem; /* Smaller px-4 */
      padding-top: 1rem; /* Smaller py-4 */
      padding-bottom: 1rem; /* Smaller py-4 */
    }
    
    /* Adjust padding for confirmation summary and its items on mobile */
    .confirmation-summary {
      padding: 1rem; /* Smaller p-4 */
    }

    .confirmation-summary .summary-item {
      padding: 0.75rem; /* Smaller p-2 */
    }
    
    /* Adjust padding for navigation buttons container on mobile */
    .flex.flex-col.sm\:flex-row.items-center.py-3.sm\:py-5 {
      padding-top: 0.75rem; /* py-3 equivalent */
      padding-bottom: 0.75rem; /* py-3 equivalent */
    }
  }

  @media (min-width: 640px) and (max-width: 1023px) { /* sm to lg breakpoints */
    .header-section h1 {
      font-size: 1.25rem; /* sm:text-xl */
    }
    .header-section p {
      font-size: 0.875rem; /* sm:text-sm */
    }
  }

  .is-preview .header-section h1 {
    font-size: 20px !important;
  }

  .is-preview .header-section p {
    font-size: 14px !important;
  }
  
  .schedula-service-form-container {
    background-color: transparent !important;
  }

  /* New styles for select and textarea to match inputStyles */
  select,
  textarea {
    background-color: v-bind('inputStyles.backgroundColor') !important;
    color: v-bind('inputStyles.color') !important;
    border-color: v-bind('inputStyles.borderColor') !important; /* Also apply border color */
  }

  /* Ensure the select arrow color matches text color */
  select {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1.5em 1.5em;
  }

  /* Force grid container consistency */
  .step-content .schedula-grid.schedula-grid-cols-1.lg\:schedula-grid-cols-2 {
    min-height: 500px !important;
    max-height: 500px !important;
  }

  /* Override the default arrow color with the text color */
  select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none' stroke='%23666' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
  }
}

</style>