<template>
  <!-- WordPress-compatible container with admin bar support -->
  <div 
    class="flex items-center justify-center py-4 sm:py-8" 
    :class="{ 'pt-8': hasWordPressAdminBar, 'min-h-screen': !isPreview, 'is-preview': isPreview }"
    :style="globalFormStyles"
  >
    <!-- Main form container (the "card") -->
    <div 
      :style="formContainerStyles"
      class="relative rounded-lg overflow-hidden shadow-lg w-full max-w-xl mx-auto flex flex-col" 
    >
      <!-- Large Header for Summary View -->
      <div v-if="showSummary" 
        :class="[
          'header-section text-center flex-shrink-0 relative',
          isSmallForm 
            ? 'px-6 py-8' 
            : 'px-6 py-8 sm:px-8 sm:py-12'
        ]" 
        :style="headerStyles"
      >
        <!-- Category name in bottom right corner -->
        <div class="absolute bottom-4 right-4 sm:bottom-6 sm:right-6 bg-white px-2 py-1 rounded-lg shadow-sm">
          <h2 
            class="text-sm sm:text-base font-bold text-gray-800"
          >
            {{ selectedCategory?.name || labels.category || '' }}
          </h2>
        </div>
        
        <!-- Main header content -->
        <div class="flex items-center justify-center h-full">
          <h1 
            :class="[
              'font-bold leading-tight',
              { 'preview-editable-label': isPreview },
              isSmallForm 
                ? 'text-3xl' 
                : 'text-3xl sm:text-4xl lg:text-5xl'
            ]"
            :style="headerTextStyles" 
            @click="handleLabelClick('service_form_title', labels.service_form_title)"
          >
            {{ labels.service_form_title || 'Book Appointment' }}
          </h1>
        </div>
      </div>

      <!-- Regular Header for Steps -->
      <div v-else class="header-section px-4 py-3 sm:px-6 sm:py-4 text-center flex-shrink-0" :style="headerStyles">
        <h1 
          class="text-lg sm:text-xl lg:text-2xl font-bold leading-tight" 
          :style="headerTextStyles" 
          @click="handleLabelClick('service_form_title', labels.service_form_title)"
          :class="{'preview-editable-label': isPreview}"
        >
          {{ labels.service_form_title || 'Book Appointment' }}
        </h1>
        <p class="text-xs sm:text-sm mt-1 sm:mt-2" :style="headerSubtextStyles" @click="handleLabelClick('service_info_description', labels.service_info_description)"
           :class="{'preview-editable-label': isPreview}">
          {{ labels.service_info_description }}
        </p>
      </div>

      <!-- Mobile Progress Indicator (Visible only on small screens) - Fixed after header -->
      <div v-if="!showSummary" :class="['mobile-progress', isPreview ? 'hidden' : 'block sm:hidden', 'px-4 py-3 border-b flex-shrink-0']" :style="progressBarStyles">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium" :style="textStyles">
            Step {{ currentStep }} of {{ visibleStepsCount }}
          </span>
          <span class="text-xs text-gray-600">{{ currentStepInfo?.title }}</span>
        </div>
        <!-- Progress bar fill -->
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div 
            class="h-2 rounded-full transition-all duration-500 ease-out" 
            :style="{ 
              backgroundColor: settings.colors.primary, 
              width: `${(currentStep / visibleStepsCount) * 100}%` 
            }"
          ></div>
        </div>
      </div>

      <!-- Tablet/Desktop Progress Steps (Hidden on small screens, visible on larger) - Fixed after header -->
      <div v-if="!showSummary" :class="['desktop-progress', isPreview ? 'block' : 'hidden sm:block', 'px-2 sm:px-4 lg:px-6 py-3 sm:py-4 flex-shrink-0']" :style="progressBarStyles">
        <div class="flex items-center justify-between max-w-4xl mx-auto">
          <div 
            v-for="(step, index) in visibleSteps" 
            :key="step.id" 
            class="flex items-center"
            :class="{ 'flex-1': isPreview || index < visibleSteps.length - 1 }"
          >
            <!-- Step Circle -->
            <div 
              :class="[
                'rounded-full flex items-center justify-center text-xs sm:text-sm font-semibold transition-all duration-300 flex-shrink-0',
                isPreview ? 'w-6 h-6' : 'w-6 h-6 sm:w-8 sm:h-8 lg:w-10 lg:h-10',
                currentStep > step.id ? 'text-white' : 
                currentStep === step.id ? 'text-white' : 'text-gray-500'
              ]" 
              :style="getStepCircleStyles(step.id)"
            >
              <svg 
                v-if="currentStep > step.id" 
                class="w-3 h-3 sm:w-4 sm:h-4 lg:w-5 sm:h-5" 
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
            <div class="ml-2 sm:ml-3 text-left min-w-0 flex-shrink">
              <p 
                :class="[
                  'font-medium transition-colors duration-300',
                  isPreview ? 'text-xs' : 'text-xs',
                  currentStep >= step.id ? 'text-gray-800' : 'text-gray-500',
                  {'preview-editable-label': isPreview}
                ]"
                class="truncate sm:whitespace-normal"
                @click="handleLabelClick(`step_${step.id}_title`, step.title.value)"
              >
                {{ step.title }}
              </p>
              <p 
                v-if="!isSmallForm"
                :class="['text-xs', 'text-gray-500', 'hidden', 'lg:block', { 'truncate': !isPreview, 'preview-editable-label': isPreview } ]"
                 @click="handleLabelClick(`step_${step.id}_subtitle`, step.subtitle.value)"
              >
                {{ step.subtitle }}
              </p>
            </div>

            <!-- Connection Line -->
            <div 
              v-if="index < visibleSteps.length - 1" 
              class="flex-1 mx-2 sm:mx-4 min-w-8"
            >
              <div 
                :class="[
                  'h-0.5 sm:h-1 rounded-full transition-all duration-300',
                  currentStep > step.id ? '' : 'bg-gray-200'
                ]" 
                :style="getStepLineStyles(step.id)"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Form Content Wrapper - This section will NOT scroll internally -->
      <div class="form-body flex-grow w-full max-w-none px-6 py-6 md:max-w-2xl md:mx-auto lg:max-w-3xl lg:px-4 lg:py-4" :style="formBodyStyles">
        <form @submit.prevent="handleNext" class="h-full flex flex-col justify-between">
          
          <!-- Summary View -->
          <div v-if="showSummary" class="step-content flex-shrink-0">
            <!-- Service Title -->
            <div class="mb-8">
              <h2 
                class="text-2xl font-bold text-gray-800 mb-2" 
                :style="textStyles"
              >
                {{ selectedService?.title || labels.service || '' }}
              </h2>
            </div>

            <!-- Service Details Card -->
            <div class="rounded-lg border p-6 mb-6" :style="cardStyles">
              <!-- Staff Name -->
              <div v-if="settings.forms.serviceForm.displayStaffNames" class="flex items-center justify-between py-3 border-b border-gray-100">
                <span class="text-gray-600 font-medium" @click="handleLabelClick('staff_member_label', labels.staff_member_label)"
                  :class="{'preview-editable-label': isPreview}">{{ labels.staff_member_label || 'Staff Member' }}</span>
                <span 
                  class="text-gray-800 font-semibold"
                >
                  {{ selectedStaff?.name || 'Any Available Staff' }}
                </span>
              </div>

              <!-- Duration -->
              <div class="flex items-center justify-between py-3 border-b border-gray-100">
                <span class="text-gray-600 font-medium" @click="handleLabelClick('duration_label', labels.duration_label)"
                  :class="{'preview-editable-label': isPreview}">{{ labels.duration_label || 'Duration' }}</span>
                <span 
                  class="text-gray-800 font-semibold"
                >
                  {{ formData.duration || 60 }} minutes
                </span>
              </div>

              <!-- Price -->
              <div class="flex items-center justify-between py-3">
                <span class="text-gray-600 font-medium" @click="handleLabelClick('price_label', labels.price_label)"
                  :class="{'preview-editable-label': isPreview}">{{ labels.price_label || 'Price' }}</span>
                <span 
                  class="text-gray-800 font-bold text-lg"
                  :style="{ color: settings.colors.primary }"
                >
                  {{ formatPrice(displayPriceInCard || 0) }}
                </span>
              </div>
            </div>

            <!-- Service Description (Optional) -->
            <div v-if="selectedService?.description" class="mb-6">
              <h3 class="text-lg font-semibold text-gray-800 mb-2" :style="textStyles" @click="handleLabelClick('service_description_heading', labels.service_description_heading)" :class="{'preview-editable-label': isPreview}">
                {{ labels.service_description_heading || 'Service Description' }}
              </h3>
              <p 
                class="text-gray-600 leading-relaxed"
              >
                {{ selectedService.description }}
              </p>
            </div>
          </div>
          
          <!-- Step 1: Date & Time Selection -->
          <div v-if="!showSummary && currentStep === 1" class="step-content flex-shrink-0">

            <!-- Recurrence Options -->
            <div v-if="globalSettings.enableRecurringAppointments" class="recurrence-options-section mb-6">
              <h3 class="text-lg font-semibold text-gray-800 mb-3" :style="labelStyles">
                {{ labels.recurrence_options || 'Recurrence Options' }}
              </h3>
              <div class="p-4 border rounded-lg shadow-sm" :style="cardStyles">
                <label class="inline-flex items-center cursor-pointer mb-4">
                  <input type="checkbox" v-model="formData.is_recurring" class="form-checkbox h-5 w-5 text-blue-600" :style="checkboxRadioStyles" />
                  <span class="ml-2 text-base font-medium" :style="textStyles">
                    {{ labels.make_recurring || 'Make this a recurring appointment' }}
                  </span>
                </label>

                <div v-if="formData.is_recurring" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <!-- Recurrence Frequency -->
                  <div class="form-group">
                    <label class="form-label block text-sm font-semibold text-gray-700 mb-2" :style="labelStyles">
                      {{ labels.recurrence_frequency || 'Frequency' }} *
                    </label>
                    <select v-model="formData.recurrence_frequency" required class="form-select w-full px-3 py-2 border border-gray-300 rounded-lg transition-all duration-200 text-base sm:text-sm bg-white shadow-sm hover:shadow-md" :style="inputStyles">
                      <option value="">{{ labels.select_frequency || 'Select Frequency' }}</option>
                      <option value="daily">{{ labels.daily || 'Daily' }}</option>
                      <option value="weekly">{{ labels.weekly || 'Weekly' }}</option>
                      <option value="monthly">{{ labels.monthly || 'Monthly' }}</option>
                      <option value="yearly">{{ labels.yearly || 'Yearly' }}</option>
                    </select>
                    <p v-if="showError('recurrence_frequency')" class="text-red-500 text-xs mt-1">{{ validationErrors.recurrence_frequency }}</p>
                  </div>

                  <!-- Recurrence Interval -->
                  <div class="form-group">
                    <label class="form-label block text-sm font-semibold text-gray-700 mb-2" :style="labelStyles">
                      {{ labels.recurrence_interval || 'Every (interval)' }} *
                    </label>
                    <input type="number" v-model.number="formData.recurrence_interval" min="1" required class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg transition-all duration-200 text-base sm:text-sm shadow-sm hover:shadow-md" :style="inputStyles" />
                    <p v-if="showError('recurrence_interval')" class="text-red-500 text-xs mt-1">{{ validationErrors.recurrence_interval }}</p>
                  </div>

                  <!-- Recurrence Count -->
                  <div class="form-group sm:col-span-2">
                      <label class="form-label block text-sm font-semibold text-gray-700 mb-2" :style="labelStyles">
                          {{ labels.after_occurrences || 'Number of Occurrences' }} *
                      </label>
                      <input type="number" v-model.number="formData.recurrence_count" min="1" required class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg transition-all duration-200 text-base sm:text-sm shadow-sm hover:shadow-md" :style="inputStyles" />
                      <p v-if="showError('recurrence_count')" class="text-red-500 text-xs mt-1">
                        {{ validationErrors.recurrence_count }}
                      </p>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              
              <!-- Calendar Section -->
              <div v-if="settings.calendar.showCalendar" class="calendar-section">
                <h3 class="text-lg font-semibold text-gray-800 mb-4" :style="labelStyles" @click="handleLabelClick('select_date', labels.select_date)"
                    :class="{'preview-editable-label': isPreview}">
                  {{ labels.select_date }}
                </h3>

                <!-- List View (conditional on settings.calendar.layoutStyle === 'list') -->
                <div 
                  v-if="settings.calendar.layoutStyle === 'list'" 
                  class="list-calendar border rounded-lg shadow-sm" 
                  :style="cardStyles"
                >
                  <!-- Days List View -->
                  <div v-if="listViewStep === 'days'" class="days-list-view">
                    <div class="p-4 border-b">
                      <h4 class="text-base font-semibold text-gray-800 mb-1" @click="handleLabelClick('select_date', labels.select_date)"
                          :class="{'preview-editable-label': isPreview}">
                        {{ labels.select_date }}
                      </h4>
                      <p class="text-sm text-gray-600" @click="handleLabelClick('select_date_time_description', labels.select_date_time_description)"
                         :class="{'preview-editable-label': isPreview}">
                        {{ labels.select_date_time_description }}
                      </p>
                    </div>
                    <div class="max-h-[320px] overflow-y-auto">
                      <button 
                        v-for="dateEntry in calendarDatesGrouped" 
                        :key="dateEntry.date" 
                        type="button" 
                        @click="selectDayForListView(dateEntry)" 
                        class="w-full text-left p-4 border-b last:border-b-0 hover:bg-gray-50 transition-colors duration-200 flex items-center justify-between"
                        :class="{'bg-blue-50': formData.appointment_date === dateEntry.date}"
                      >
                        <span class="text-base font-medium text-gray-800">{{ formatDate(dateEntry.date) }}</span>
                        <span class="text-sm text-gray-500">{{ dateEntry.slots.length }} slots</span>
                      </button>
                      
                      <div v-if="calendarDatesGrouped.length === 0" class="p-8 text-center">
                        <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v doros12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-gray-500 font-medium" @click="handleLabelClick('no_appointments_available', labels.no_appointments_available)"
                           :class="{'preview-editable-label': isPreview}">
                          {{ labels.no_appointments_available }}
                        </p>
                        <p class="text-sm text-gray-400 mt-1" @click="handleLabelClick('check_back_later', labels.check_back_later)"
                           :class="{'preview-editable-label': isPreview}">
                          {{ labels.check_back_later }}
                        </p>
                      </div>
                    </div>
                  </div>

                  <!-- Times for Selected Day View -->
                  <div v-else-if="listViewStep === 'times'" class="times-list-view">
                    <div class="p-4 border-b flex items-center">
                      <button type="button" @click="goBackToDaysList" class="p-2 mr-2 rounded-full hover:bg-gray-100 transition-colors">
                        <i class="fas fa-arrow-left text-gray-600"></i>
                      </button>
                      <div>
                        <h4 class="text-base font-semibold text-gray-800 mb-1">{{ formatDate(selectedDateForListView.date) }}</h4>
                        <p class="text-sm text-gray-600" @click="handleLabelClick('available_times_title', labels.available_times_title)"
                           :class="{'preview-editable-label': isPreview}">
                          {{ labels.available_times_title }}
                        </p>
                      </div>
                    </div>
                    <div class="max-h-[320px] overflow-y-auto p-4 grid grid-cols-2 sm:grid-cols-3 gap-3">
                      <button 
                        v-for="slot in filteredTimeSlotsForListView" 
                        :key="slot.value" 
                        type="button" 
                        @click="selectDateTimeFromList(selectedDateForListView.date, slot.value)" 
                        class="time-slot-btn px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 border-2 touch-manipulation"
                        :class="[
                          formData.appointment_date === selectedDateForListView.date && formData.appointment_time === slot.value
                            ? 'selected text-white shadow-lg' 
                            : 'text-gray-700 hover:bg-blue-50 hover:border-blue-200'
                        ]"
                        :style="getTimeSlotButtonStyles(slot.value, selectedDateForListView.date)"
                      >
                        {{ slot.label }}
                      </button>
                      <div v-if="filteredTimeSlotsForListView.length === 0" class="col-span-full p-4 text-center">
                        <p class="text-gray-500 font-medium">{{ labels.no_available_times }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Desktop Calendar Grid (Default View - always visible if showCalendar is true) -->
                <div 
                  v-else-if="settings.calendar.layoutStyle === 'default'" 
                  class="desktop-calendar border rounded-lg shadow-sm p-4" 
                  :style="cardStyles"
                >
                  <!-- Calendar Header -->
                  <div class="calendar-header flex items-center justify-between mb-6">
                    <button 
                      type="button" 
                      @click="previousMonth" 
                      class="nav-btn p-3 hover:bg-gray-100 rounded-full transition-colors touch-manipulation"
                      :style="buttonSecondaryStyles"
                    >
                      <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                      </svg>
                    </button>
                    
                    <h4 class="text-xl font-bold text-gray-900 flex-grow text-center" :style="textStyles">
                      {{ currentMonthYear }}
                    </h4>
                    
                    <button 
                      type="button" 
                      @click="nextMonth" 
                      class="nav-btn p-3 hover:bg-gray-100 rounded-full transition-colors touch-manipulation"
                      :style="buttonSecondaryStyles"
                    >
                      <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                      </svg>
                    </button>
                  </div>

                  <!-- Days of Week -->
                  <div class="grid grid-cols-7 gap-1 mb-3">
                    <div 
                      v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" 
                      :key="day" 
                      class="text-center text-sm font-semibold text-gray-500 py-3"
                      :style="labelStyles"
                    >
                      {{ day }}
                    </div>
                  </div>

                  <!-- Calendar Grid -->
                  <div class="calendar-grid grid grid-cols-7 gap-1">
                    <button 
                      v-for="date in calendarDates" 
                      :key="date.key" 
                      type="button" 
                      @click="selectDate(date)" 
                      :disabled="date.disabled || date.isOtherMonth" 
                      class="calendar-date h-10 w-full text-sm rounded-lg flex items-center justify-center font-medium touch-manipulation"
                      :class="[
                        date.isSelected ? 'selected text-white shadow-lg' : '',
                        date.isToday && !date.isSelected ? 'today font-bold' : '',
                        date.disabled || date.isOtherMonth ? 'disabled:text-gray-300 disabled:cursor-not-allowed' : 'text-gray-700 hover:bg-blue-50 hover:border-blue-200'
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
                v-if="settings.calendar.layoutStyle === 'default'" 
                class="time-slots-section"
              >
                <h3 class="text-lg font-semibold text-gray-800 mb-4" :style="labelStyles" @click="handleLabelClick('available_times', labels.available_times)"
                    :class="{'preview-editable-label': isPreview}">
                  {{ labels.available_times }}
                </h3>
                
                <div class="time-slots-container border rounded-lg shadow-sm min-h-[320px] overflow-y-auto" :style="cardStyles">
                  <!-- No Date Selected -->
                  <div v-if="!formData.appointment_date" class="flex items-center justify-center h-full p-8">
                    <div class="text-center">
                      <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                      </svg>
                      <p class="text-gray-500 font-medium" @click="handleLabelClick('select_a_date', labels.select_a_date)"
                         :class="{'preview-editable-label': isPreview}">
                        {{ labels.select_a_date }}
                      </p>
                      <p class="text-sm text-gray-400 mt-1" @click="handleLabelClick('choose_date_to_see_times', labels.choose_date_to_see_times)"
                         :class="{'preview-editable-label': isPreview}">
                        {{ labels.choose_date_to_see_times }}
                      </p>
                    </div>
                  </div>

                  <!-- Loading State -->
                  <div v-else-if="loadingTimeSlots" class="flex items-center justify-center h-full p-8">
                    <div class="text-center">
                      <svg 
                        class="animate-spin mx-auto h-12 w-12 mb-4" 
                        :style="{ color: settings.colors.primary }" 
                        xmlns="http://www.w3.org/2000/svg" 
                        fill="none" 
                        viewBox="0 0 24 24"
                      >
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      <p class="text-gray-600 font-medium" @click="handleLabelClick('loading_available_times', labels.loading_available_times)"
                         :class="{'preview-editable-label': isPreview}">
                        {{ labels.loading_available_times }}
                      </p>
                    </div>
                  </div>

                  <!-- No Times Available -->
                  <div v-else-if="availableTimeSlots.length === 0" class="flex items-center justify-center h-full p-8">
                    <div class="text-center">
                      <svg class="mx-auto h-16 w-16 text-yellow-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                      </svg>
                      <p class="text-yellow-700 font-medium" @click="handleLabelClick('no_available_times', labels.no_available_times)"
                         :class="{'preview-editable-label': isPreview}">
                        {{ labels.no_available_times }}
                      </p>
                      <p class="text-sm text-yellow-600 mt-1" @click="handleLabelClick('select_different_date', labels.select_different_date)"
                         :class="{'preview-editable-label': isPreview}">
                        {{ labels.select_different_date }}
                      </p>
                    </div>
                  </div>

                  <!-- Time Slots Grid -->
                  <div v-else class="p-4">
                    <div class="grid grid-cols-3 lg:grid-cols-4 gap-3">
                      <button 
                        v-for="slot in availableTimeSlots" 
                        :key="slot.value" 
                        type="button" 
                        @click="selectTimeSlot(slot.value)" 
                        class="time-slot px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 border-2 touch-manipulation"
                        :class="[
                          formData.appointment_time === slot.value
                            ? 'selected text-white shadow-lg' 
                            : 'text-gray-700 hover:bg-blue-50 hover:border-blue-200'
                        ]"
                        :style="getTimeSlotButtonStyles(slot.value, formData.appointment_date)"
                      >
                        {{ slot.label }}
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Error message for appointment date/time selection -->
                <p v-if="showError('appointment_date') || showError('appointment_time') || showError('available_slots')" class="text-red-500 text-xs mt-2 text-center">
                  {{ validationErrors.appointment_date || validationErrors.appointment_time || validationErrors.available_slots }}
                </p>

                <!-- Selected Time Confirmation -->
                <div 
                  v-if="formData.appointment_time && !validationErrors.appointment_time && !validationErrors.appointment_date" 
                  class="selected-time-confirmation mt-4 p-4 border rounded-lg bg-green-50 border-green-200" 
                  :style="cardStyles"
                >
                  <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <p class="font-semibold text-sm text-green-800" :style="textStyles">
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
          <div v-if="!showSummary && currentStep === 2" class="step-content flex-shrink-0">
            <div class="max-w-2xl mx-auto">
              <h3 class="text-xl font-semibold text-gray-800 mb-6 text-center" :style="textStyles" @click="handleLabelClick('your_information', labels.your_information)"
                  :class="{'preview-editable-label': isPreview}">
                {{ labels.your_information }}
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div class="md:col-span-2 form-group" v-if="settings.customer.showEmailField">
                  <label class="form-label block text-sm font-semibold text-gray-700 mb-2" :style="labelStyles" @click="handleLabelClick('email', labels.email)"
                         :class="{'preview-editable-label': isPreview}">
                    {{ labels.email }} *
                  </label>
                  <div class="relative">
                    <input 
                      type="email" 
                      v-model="formData.customer_email" 
                      required 
                      class="form-input w-full pr-10 px-3 py-2 border border-gray-300 rounded-lg transition-all duration-200 text-base sm:text-sm shadow-sm hover:shadow-md" 
                      :placeholder="labels.enter_email_placeholder" 
                      :style="inputStyles"
                      :class="{ 'border-red-500': showError('customer_email') }"
                      @input="debouncedHandleEmailBlur"
                    />
                    <div v-if="isLoadingCustomerDetails" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                      <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                    </div>
                  </div>
                  <p v-if="showError('customer_email')" class="text-red-500 text-xs mt-1">{{ validationErrors.customer_email }}</p>
                </div>
                <div v-if="settings.customer.showFirstNameField" class="form-group">
                  <label class="form-label block text-sm font-semibold text-gray-700 mb-2" :style="labelStyles" @click="handleLabelClick('first_name', labels.first_name)"
                         :class="{'preview-editable-label': isPreview}">
                    {{ labels.first_name }} *
                  </label>
                  <input 
                    type="text" 
                    v-model="formData.customer_first_name" 
                    required 
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg transition-all duration-200 text-base sm:text-sm shadow-sm hover:shadow-md" 
                    :placeholder="labels.enter_first_name_placeholder" 
                    :style="inputStyles"
                    :class="{ 'border-red-500': showError('customer_first_name') }"
                    @input="clearError('customer_first_name')"
                  />
                  <p v-if="showError('customer_first_name')" class="text-red-500 text-xs mt-1">{{ validationErrors.customer_first_name }}</p>
                </div>
                <div v-if="settings.customer.showLastNameField" class="form-group">
                  <label class="form-label block text-sm font-semibold text-gray-700 mb-2" :style="labelStyles" @click="handleLabelClick('last_name', labels.last_name)"
                         :class="{'preview-editable-label': isPreview}">
                    {{ labels.last_name }} *
                  </label>
                  <input 
                    type="text" 
                    v-model="formData.customer_last_name" 
                    required 
                    class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg transition-all duration-200 text-base sm:text-sm shadow-sm hover:shadow-md" 
                    :placeholder="labels.enter_last_name_placeholder" 
                    :style="inputStyles"
                    :class="{ 'border-red-500': showError('customer_last_name') }"
                    @input="clearError('customer_last_name')"
                  />
                  <p v-if="showError('customer_last_name')" class="text-red-500 text-xs mt-1">{{ validationErrors.customer_last_name }}</p>
                </div>
                <div class="md:col-span-2 form-group" v-if="settings.customer.showPhoneField">
                  <label class="form-label block text-sm font-semibold text-gray-700 mb-2" :style="labelStyles" @click="handleLabelClick('phone', labels.phone)"
                         :class="{'preview-editable-label': isPreview}">
                    {{ labels.phone }}
                  </label>
                   <BasePhoneInputForm
                    :placeholder="labels.enter_phone_placeholder" 
                    v-model="formData.customer_phone"
                    :style="inputStyles"
                    customClass="form-input w-full px-3 py-2 border border-gray-300 rounded-lg transition-all duration-200 text-base sm:text-sm shadow-sm hover:shadow-md" 
                  />
                  <p v-if="showError('customer_phone')" class="text-red-500 text-xs mt-1">{{ validationErrors.customer_phone }}</p>
                </div>
                <div class="md:col-span-2 form-group" v-if="settings.customer.showNotesField">
                  <label class="form-label block text-sm font-semibold text-gray-700 mb-2" @click="handleLabelClick('notes_label', labels.notes_label)"
                         :class="{'preview-editable-label': isPreview}">{{ labels.notes_label }}</label>
                  <textarea v-model="formData.notes" rows="3" class="form-textarea w-full px-3 py-2 border border-gray-300 rounded-lg transition-all duration-200 text-base sm:text-sm shadow-sm hover:shadow-md" :placeholder="labels.notes_placeholder" :style="inputStyles"></textarea>
                </div>
                <!-- Number of Persons (Checkbox + +/- controls) -->
                <div v-if="globalSettings.enableGroupBooking" class="md:col-span-2 form-group">
                  <label
                    class="form-label block text-sm font-semibold text-gray-700 mb-2"
                    :style="labelStyles" @click="handleLabelClick('number_of_persons', labels.number_of_persons)"
                    :class="{ 'preview-editable-label': isPreview }">
                    {{ __('Number of People', 'schedula-smart-appointment-booking') }} *
                  </label>
                  <div class="flex items-center space-x-6">
                    <div class="flex items-center">
                      <input
                        id="isBringingGuests"
                        v-model="isBringingGuests"
                        type="checkbox"
                        class="form-checkbox h-4 w-4 rounded border-gray-300"
                        :style="[checkboxRadioStyles, { color: settings.colors.primary }]"
                      />
                      <label for="isBringingGuests" class="ml-2 block text-sm font-medium text-gray-700">
                        {{ __('Bring people with you?', 'schedula-smart-appointment-booking') }}
                      </label>
                    </div>
                    <div v-if="isBringingGuests" class="ml-4 flex items-center space-x-2">
                      <button
                        v-if="additionalGuests > 1"
                        type="button"
                        @click="decrementGuests"
                        class="px-2 py-1 rounded text-sm focus:outline-none"
                        :style="{ backgroundColor: settings.colors.primary, color: settings.colors.headerText, border: 'none' }"
                      >
                        -
                      </button>
                      <span class="px-2 text-sm text-gray-700">{{ additionalGuests }}</span>
                      <button
                        type="button"
                        @click="incrementGuests"
                        :disabled="additionalGuests >= (globalSettings.maxPersonsPerBooking - 1)"
                        class="px-2 py-1 rounded text-sm disabled:cursor-not-allowed focus:outline-none"
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
          <div v-if="!showSummary && currentStep === 3 && settings.payment.showPaymentStep" class="step-content flex-shrink-0">
            <div class="max-w-2xl mx-auto">
                  <h3 class="text-xl font-semibold text-gray-800 mb-6 text-center" :style="textStyles" @click="handleLabelClick('payment_method', labels.payment_method)"
                      :class="{'preview-editable-label': isPreview}">
                    {{ __('Choose Payment Method', 'schedula-smart-appointment-booking') }}
                  </h3>
                  
                <div class="ml-6 mr-4"></div>
             
              <div class="schedula-payment-options-container flex flex-wrap justify-center gap-4">
                  <!-- Cash Payment Option -->
                  <div v-if="globalSettings.enableLocalPayment" :class="['schedula-payment-option', 'relative', 'w-full', enabledPaymentMethodsCount === 1 ? 'md:w-1/2' : 'md:w-[30%]']">
                    <input 
                      type="radio" 
                      v-model="formData.payment_method" 
                      value="cash" 
                      id="payment-cash"
                      class="sr-only" 
                    />
                    <label 
                      for="payment-cash"
                      class="schedula-payment-option-label flex items-center p-2 border-2 rounded-xl cursor-pointer transition-all duration-200 hover:shadow-md h-full"
                      :style="[cardStyles, formData.payment_method === 'cash' ? { backgroundColor: settings.colors.primary + '1A', borderColor: settings.colors.primary } : { borderColor: settings.colors.textColor + '33' }]"
                    >
                      <div class="flex items-center justify-center w-10 h-10 rounded-full mr-3 flex-shrink-0"
                           :class="formData.payment_method === 'cash' ? 'bg-blue-100' : 'bg-gray-100'">
                        <!-- Cash Icon -->
                        <svg class="w-5 h-5" :class="formData.payment_method === 'cash' ? 'text-blue-600' : 'text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                      </div>
                      <div class="flex-1">
                        <h4 class="text-base font-semibold text-gray-800 mb-0" :style="textStyles">
                          {{ __('Pay with Cash', 'schedula-smart-appointment-booking') }}
                        </h4>
                        <p v-if="settings.payment.showPaymentMethodDescription" class="text-xs text-gray-600">
                          {{ __('Pay at your appointment', 'schedula-smart-appointment-booking') }}
                        </p>
                      </div>
                      <div v-if="formData.payment_method === 'cash'" class="ml-3">
                        <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                      </div>
                    </label>
                  </div>


                  <!-- Stripe Payment Option -->
                  <div v-if="stripeSettings && stripeSettings.enableStripe" :class="['schedula-payment-option', 'relative', 'w-full', enabledPaymentMethodsCount === 1 ? 'md:w-1/2' : 'md:w-[30%]']">
                    <input 
                      type="radio" 
                      v-model="formData.payment_method" 
                      value="stripe" 
                      id="payment-stripe"
                      class="sr-only" 
                    />
                    <label 
                      for="payment-stripe"
                      class="schedula-payment-option-label flex items-center p-2 border-2 rounded-xl cursor-pointer transition-all duration-200 hover:shadow-md h-full"
                      :style="[cardStyles, formData.payment_method === 'stripe' ? { backgroundColor: settings.colors.primary + '1A', borderColor: settings.colors.primary } : { borderColor: settings.colors.textColor + '33' }]"
                    >
                      <div class="flex items-center justify-center w-10 h-10 rounded-full mr-3 flex-shrink-0"
                           :class="formData.payment_method === 'stripe' ? 'bg-blue-100' : 'bg-gray-100'">
                        <!-- Stripe/Credit Card Icon -->
                        <svg class="w-5 h-5" :class="formData.payment_method === 'stripe' ? 'text-blue-600' : 'text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                      </div>
                      <div class="flex-1">
                        <h4 class="text-base font-semibold text-gray-800 mb-0" :style="textStyles">
                          {{ __('Pay with Card', 'schedula-smart-appointment-booking') }}
                        </h4>
                        <p v-if="settings.payment.showPaymentMethodDescription" class="text-xs text-gray-600">
                          {{ __('Secure payment with credit/debit card', 'schedula-smart-appointment-booking') }}
                        </p>
                      </div>
                      <div v-if="formData.payment_method === 'stripe'" class="ml-3">
                        <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                      </div>
                    </label>
                  </div>

                  <!-- Price Summary -->
                  <div v-if="settings.payment.showPriceBreakdown" class="mt-6 p-4 rounded-xl border" :style="cardStyles">
                    <div class="flex justify-between items-center">
                      <span class="text-lg font-semibold text-gray-700" :style="labelStyles">
                        {{ __('Total Amount', 'schedula-smart-appointment-booking') }}
                      </span>
                      <span class="text-2xl font-bold ml-2" :style="{ color: settings.colors.primary }">
                        {{ formData.price ? formatPrice(formData.price) : '--' }}
                      </span>
                    </div>
                  </div>
                </div>
            </div>
          </div>

          <!-- Step 4: Confirmation -->
          <div v-if="!showSummary && currentStep === 4 && settings.confirmation.showSummaryStep" class="step-content flex-shrink-0">
            <div class="max-w-2xl mx-auto">
              <h3 class="text-xl font-semibold text-gray-800 mb-5 text-center" :style="textStyles" @click="handleLabelClick('confirm_appointment', labels.confirm_appointment)"
                  :class="{'preview-editable-label': isPreview}">
                {{ labels.confirm_appointment }}
              </h3>
              <div class="confirmation-summary rounded-xl p-6 border" :style="cardStyles">
                <div class="flex flex-col sm:flex-row items-center mb-4 text-center sm:text-left">
                  <div v-if="settings.confirmation.showServiceImage && selectedService && selectedService.image_url" class="w-24 h-24 rounded-lg overflow-hidden shadow-md mb-3 sm:mb-0 sm:mr-4 flex-shrink-0">
                    <!-- Adjusted image display for confirmation step -->
                    <div style="padding-bottom: 100%;" class="relative w-full h-full">
                      <img 
                        :src="selectedService.image_url" 
                        :alt="selectedService.title" 
                        class="absolute inset-0 w-full h-full object-cover"
                        @error="handleImageError"
                      />
                    </div>
                  </div>
                  <div>
                    <h4 class="font-bold text-lg text-gray-800" :style="textStyles">{{ selectedService?.title || 'N/A' }}</h4>
                    <p class="text-sm text-gray-600" :style="labelStyles">{{ selectedCategory?.name || 'N/A' }}</p>
                  </div>
                </div>
                <div class="space-y-3" v-if="settings.confirmation.showConfirmationDetails">
                  <div class="summary-item flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-3 rounded-md shadow-sm" :style="cardStyles">
                    <span class="text-xs font-semibold text-gray-600" :style="labelStyles" @click="handleLabelClick('date_time', labels.date_time)"
                          :class="{'preview-editable-label': isPreview}">
                      {{ labels.date_time }}:
                    </span>
                    <span class="text-sm text-gray-800 font-medium text-right" :style="textStyles">{{ formatDate(formData.appointment_date) || 'N/A' }} at {{ formatTimeForDisplay(formData.appointment_time, globalSettings.displayTimezone) || 'N/A' }}</span>
                  </div>
                  <div class="summary-item flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-3 rounded-md shadow-sm" :style="cardStyles" v-if="settings.forms.serviceForm.displayStaffNames">
                    <span class="text-xs font-semibold text-gray-600" :style="labelStyles" @click="handleLabelClick('employee_confirmation', labels.employee_confirmation)"
                          :class="{'preview-editable-label': isPreview}">
                      {{ labels.employee_confirmation }}:
                    </span>
                    <span class="text-sm text-gray-800 font-medium" :style="textStyles">{{ selectedStaff?.name || 'Any Available' }}</span>
                  </div>
                  <div v-if="globalSettings.enableGroupBooking && formData.number_of_persons > 0" class="summary-item flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-3 rounded-md shadow-sm" :style="cardStyles">
                    <span class="text-xs font-semibold text-gray-600" :style="labelStyles" @click="handleLabelClick('number_of_persons_confirmation', labels.number_of_persons_confirmation)"
                          :class="{'preview-editable-label': isPreview}">
                      {{ labels.number_of_persons_confirmation || 'Number of People' }}:
                    </span>
                    <span class="text-sm text-gray-800 font-medium" :style="textStyles">{{ formData.number_of_persons }}</span>
                  </div>
                  <div v-if="formData.is_recurring" class="summary-item flex flex-col sm:flex-row justify-between items-start sm:items-centersm:items-center bg-white p-3 rounded-md shadow-sm" :style="cardStyles">
                    <span class="text-xs font-semibold text-gray-600" :style="labelStyles">
                      {{ labels.recurrence || 'Recurrence' }}:
                    </span>
                    <span class="text-sm text-gray-800 font-medium" :style="textStyles">
                      {{ formatRecurrenceSummary }}
                    </span>
                  </div>
                  <div class="summary-item flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-3 rounded-md shadow-sm" :style="cardStyles">
                    <span class="text-xs font-semibold text-gray-600" :style="labelStyles" @click="handleLabelClick('duration_confirmation', labels.duration_confirmation)"
                          :class="{'preview-editable-label': isPreview}">
                      {{ labels.duration_confirmation }}:
                    </span>
                    <span class="text-sm text-gray-800 font-medium" :style="textStyles">{{ formData.duration || '0' }} {{ labels.minutes_suffix_confirmation }}</span>
                  </div>
                  <div class="summary-item flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-3 rounded-md shadow-sm" :style="cardStyles">
                    <span class="text-xs font-semibold text-gray-600" :style="labelStyles" @click="handleLabelClick('total_price_confirmation', labels.total_price_confirmation)"
                          :class="{'preview-editable-label': isPreview}">
                      {{ labels.total_price_confirmation }}:
                    </span>
                    <span class="font-bold text-base text-blue-600" :style="{ color: settings.colors.primary }">{{ formatPrice(formData.price || 0) }}</span>
                  </div>
                </div>
                <button 
                    v-if="isPreview && settings.confirmation.allowEditing" 
                    type="button" 
                    @click="editConfirmedDetails"
                    class="mt-4 w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out"
                >
                    <i class="fas fa-edit mr-2"></i> <span @click="handleLabelClick('edit_details_button', labels.edit_details_button)"
                                                            :class="{'preview-editable-label': isPreview}">{{ labels.edit_details_button }}</span>
                </button>
              </div>
              <!-- Success/Error Messages -->
              <div v-if="submissionError" class="mt-5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg" role="alert">
                <div class="flex items-center">
                  <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                  <span class="font-medium text-sm">{{ submissionError }}</span>
                </div>
              </div>
              <div v-if="submissionSuccess" class="mt-5 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-center" role="alert">
                <div class="flex justify-center items-center mb-2">
                  <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                  <span class="font-medium text-base">{{ submissionSuccess }}</span>
                </div>
                <button v-if="settings.confirmation.showBookAgainButton" type="button" @click="bookAgain" class="mt-3 inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out text-sm">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                  <span @click="handleLabelClick('book_again', labels.book_again)"
                        :class="{'preview-editable-label': isPreview}">{{ labels.book_again }}</span>
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- Navigation Buttons - Fixed at bottom -->
      <div :class="['flex flex-col sm:flex-row items-center py-3 sm:py-5 border-t border-gray-200 justify-between space-y-3 sm:space-y-0 flex-shrink-0 px-6 sm:px-6 md:px-6 lg:px-4']">
        <!-- Back button (shown on all steps except summary) -->
        <button type="button" @click="prevStep" v-if="!showSummary && !submissionSuccess" class="w-full sm:w-auto inline-flex items-center px-5 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium transition duration-150 ease-in-out" :style="buttonSecondaryStyles">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
          <span @click="handleLabelClick('previous', labels.previous)" :class="{'preview-editable-label': isPreview}">{{ labels.previous }}</span>
        </button>
        <div v-else class="w-full sm:w-auto"></div> <!-- Placeholder to maintain justify-between spacing -->
        
        <button type="submit" @click="isPreview ? null : handleNext" :disabled="submitting" :class="['w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-transparent text-sm font-semibold rounded-lg shadow-md transition duration-150 ease-in-out transform hover:scale-105', isStepValid && !submitting ? 'bg-blue-600 text-white' : 'bg-gray-400 text-gray-200 disabled:cursor-not-allowed']" :style="buttonPrimaryStyles">
          <svg v-if="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          <span v-if="currentStep === visibleStepsCount" @click="handleLabelClick(submitting ? 'confirming' : 'confirm', submitting ? labels.confirming : labels.confirm)"
                :class="{'preview-editable-label': isPreview}">
            {{ submitting ? labels.confirming : labels.confirm }}
          </span>
          <span velse @click="handleLabelClick('continue', labels.continue)"
                :class="{'preview-editable-label': isPreview}">
            {{ labels.continue }}
          </span>
          <svg v-if="currentStep < visibleStepsCount && !submitting" class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </button>
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref, reactive, computed, onMounted, toRefs, watch, onUnmounted } from 'vue';
import { servicesCategoriesApi, appointmentsApi, staffApi, paymentsApi, stripeApi } from '@/admin/api.js'; // Import staffApi
import { useAppearanceSettings } from '@/admin/composables/useAppearanceSettings.js';
import { useGlobalSettings } from '@/admin/composables/useGlobalSettings.js';
import { __ } from '@wordpress/i18n';
import BasePhoneInputForm from './BasePhoneInputForm.vue';

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

const schedulaData = window.schedulaData || window.schedulaFrontendData || window.schedulaServiceFormData || {};
const placeholderImage = schedulaData.pluginUrl ? schedulaData.pluginUrl + 'assets/images/placeholders/no-image-placeholder.png' : '';

const handleImageError = (event) => {
  event.target.onerror = null;
  event.target.src = placeholderImage;
};

const { previewSettings } = toRefs(props);

// Use the appearance settings composable for frontend styling (colors, layout, etc.)
// This `settings` object will hold the appearance-specific configurations
const { settings: defaultAppearanceSettings } = useAppearanceSettings(); // Renamed to avoid conflict

// Use the global settings composable for general business logic (like currency format, timezone)
// This `globalSettings` object will hold `general` settings from the backend.
const { generalSettings: globalSettings, fetchGlobalSettings: fetchGlobalSettingsComposable, formatPrice } = useGlobalSettings();

const stripeSettings = ref(null);


// Reactive state for WordPress admin bar and mobile view detection
const hasWordPressAdminBar = ref(false);
const windowWidth = ref(window.innerWidth);
const isMobileView = computed(() => windowWidth.value < 640); // Tailwind's 'sm' breakpoint

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

const enabledPaymentMethodsCount = computed(() => {
  let count = 0;
  if (globalSettings.value && globalSettings.value.enableLocalPayment) {
    count++;
  }
  if (stripeSettings.value && stripeSettings.value.enableStripe) {
    count++;
  }
  return count;
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
  fontFamily: `var(--font-family-form, ${settings.value.layout.fontFamily})`,
  fontSize: settings.value.layout.fontSize === 'small' ? '14px' : settings.value.layout.fontSize === 'large' ? '18px' : '16px',
  color: settings.value.colors.textColor,
}));

// Adjusted formContainerStyles to apply rounded corners and shadows directly
const formContainerStyles = computed(() => ({
  maxWidth: settings.value.layout.formWidth === 'full' ? '100%' : settings.value.layout.formWidth,
  borderRadius: settings.value.theme.roundedCorners ? (settings.value.layout.borderRadius === 'small' ? '4px' : settings.value.layout.borderRadius === 'medium' ? '8px' : '12px') : '0',
  boxShadow: settings.value.theme.shadows ? getShadowValue(settings.value.layout.shadowStrength) : 'none',
  backgroundColor: settings.value.colors.background,
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
  fontSize: settings.value.layout.fontSize === 'small' ? '14px' : settings.value.layout.fontSize === 'large' ? '18px' : '16px',
  // Reduced padding for a more compact look on mobile
  padding: isMobileView.value
             ? (settings.value.layout.inputSize === 'small' ? '0.2rem 0.4rem' : settings.value.layout.inputSize === 'large' ? '0.4rem 0.8rem' : '0.3rem 0.6rem')
             : (settings.value.layout.inputSize === 'small' ? '0.3rem 0.5rem' : settings.value.layout.inputSize === 'large' ? '0.5rem 0.9rem' : '0.4rem 0.7rem'),
  backgroundColor: settings.value.colors.background, // Use new background color
  color: settings.value.colors.textColor,
  borderColor: settings.value.colors.textColor + '33', // Light border from text color
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
  fontSize: settings.value.layout.fontSize === 'small' ? '14px' : settings.value.layout.fontSize === 'large' ? '18px' : '16px',
  padding: settings.value.layout.inputSize === 'small' ? '0.375rem 0.75rem' : settings.value.layout.inputSize === 'large' ? '0.625rem 1.25rem' : '0.5rem 1rem',
  boxShadow: settings.value.theme.shadows ? getShadowValue(settings.value.layout.shadowStrength) : 'none',
  transition: 'all 0.2s ease-in-out',
}));

const buttonSecondaryStyles = computed(() => ({
  backgroundColor: settings.value.colors.background, // Secondary button uses background color
  color: settings.value.colors.textColor, // Secondary button text uses text color
  border: `1px solid ${settings.value.colors.textColor}33`, // Light border
  borderRadius: settings.value.theme.roundedCorners ? (settings.value.layout.borderRadius === 'small' ? '4px' : settings.value.layout.borderRadius === 'medium' ? '8px' : '12px') : '0',
  fontSize: settings.value.layout.fontSize === 'small' ? '14px' : settings.value.layout.fontSize === 'large' ? '18px' : '16px',
  padding: settings.value.layout.inputSize === 'small' ? '0.375rem 0.75rem' : settings.value.layout.inputSize === 'large' ? '0.625rem 1.25rem' : '0.5rem 1rem',
  boxShadow: settings.value.theme.shadows ? getShadowValue(settings.value.layout.shadowStrength) : 'none',
  transition: 'all 0.2s ease-in-out',
}));

const textStyles = computed(() => ({
  color: settings.value.colors.textColor,
  transition: 'all 0.2s ease-in-out',
}));

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
      backgroundColor: settings.value.colors.primary,
      color: settings.value.colors.headerText,
      boxShadow: settings.value.theme.shadows && !isMobileView.value ? getShadowValue(settings.value.layout.shadowStrength) : 'none',
      transform: !isMobileView.value ? 'scale(1.05)' : 'scale(1.0)' // Scale on desktop selected
    };
  } else if (date.isToday && !date.isSelected) {
    return {
      ...base,
      backgroundColor: settings.value.colors.primary + '1A',
      color: settings.value.colors.primary,
      fontWeight: 'bold'
    };
  } else if (date.disabled || date.isOtherMonth) {
    return {
      ...base,
      color: settings.value.colors.textColor + '66',
      cursor: 'not-allowed'
    }; // Lighter text color for disabled
  } else {
    return {
      ...base,
      backgroundColor: settings.value.colors.background,
      color: settings.value.colors.textColor,
      // Apply subtle hover effect only on non-mobile and if animations are enabled
      '&:hover': !isMobileView.value
        ? { backgroundColor: settings.value.colors.primary + '08', transform: 'scale(1.02)' }
        : {}
    };
  }
};

const getTimeSlotButtonStyles = (slot, date) => {
  const base = {
    borderRadius: settings.value.theme.roundedCorners ? (settings.value.layout.borderRadius === 'small' ? '4px' : settings.value.layout.borderRadius === 'medium' ? '8px' : '12px') : '0',
    transition: 'all 0.2s ease-in-out',
    fontSize: settings.value.layout.fontSize === 'small' ? '12px' : settings.value.layout.fontSize === 'large' ? '16px' : '14px',
    // Adjusted padding for compactness
    padding: settings.value.layout.inputSize === 'small' ? '0.2rem 0.4rem' : settings.value.layout.inputSize === 'large' ? '0.4rem 0.8rem' : '0.3rem 0.6rem',
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
      borderColor: settings.value.colors.textColor + '33',
      // Apply subtle hover effect only on non-mobile and if animations are enabled
      '&:hover': !isMobileView.value
        ? { backgroundColor: settings.value.colors.primary + '08', borderColor: settings.value.colors.primary + '40', transform: 'scale(1.02)' }
        : {}
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

const displayPriceInCard = computed(() => baseServicePrice.value);

const isBringingGuests = ref(false);
const additionalGuests = ref(1);

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

const updatePriceForGroupBooking = () => {
  if (globalSettings.value?.enableGroupBooking && baseServicePrice.value > 0) {
    formData.price = calculateGroupBookingPrice(
      baseServicePrice.value,
      formData.number_of_persons,
      globalSettings.value.groupBookingPriceLogic
    );
  }
};

watch(() => formData.number_of_persons, (newValue, oldValue) => {
  if (newValue !== oldValue) {
    updatePriceForGroupBooking();
  }
});

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
const categories = ref([]);
const loadingCategories = ref(false);
const categoriesError = ref(null);

const services = ref([]);
const filteredServices = computed(() => {
  return services.value.filter(service => service.category_id == formData.category_id); // Use == for type coercion
});
const loadingServices = ref(false);
const servicesError = ref(null);

const availableStaffForService = ref([]);
const loadingStaff = ref(false);
const staffError = ref(null);

// availableTimeSlots now stores objects { value: "HH:MM", label: "HH:MM AM/PM (Timezone)" }
const availableTimeSlots = ref([]);
const loadingTimeSlots = ref(false);
const timeSlotsError = ref(null);

// Computed properties for summary display
const selectedCategory = computed(() => {
  return categories.value.find(cat => cat.id == formData.category_id);
});

const selectedService = computed(() => {
  return services.value.find(svc => svc.id == formData.service_id);
});

const selectedStaff = computed(() => {
  if (formData.staff_id && formData.staff_id !== '0') {
    return availableStaffForService.value.find(staff => staff.id == formData.staff_id);
  }
  return null;
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
  availableTimeSlots.value.forEach(slot => {
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
const showSummary = ref(true);
const isLoadingCustomerDetails = ref(false);

function debounce(fn, delay) {
  let timeoutID = null;
  return function (...args) {
    clearTimeout(timeoutID);
    timeoutID = setTimeout(() => {
      fn(...args);
    }, delay);
  };
}

const handleEmailBlur = async () => {
  clearError('customer_email');
  if (formData.customer_email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.customer_email)) {
    isLoadingCustomerDetails.value = true;
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
      isLoadingCustomerDetails.value = false;
    }
  }
};

const debouncedHandleEmailBlur = debounce(handleEmailBlur, 500);

// Current step management
const currentStep = ref(props.previewStep || 1);

// Watch for changes in previewStep prop and update currentStep
watch(() => props.previewStep, (newStep) => {
  if (newStep === 0) {
    showSummary.value = true;
  } else {
    showSummary.value = false;
    currentStep.value = newStep;
  }

  // Special handling for preview mode to ensure steps have data
  if (props.isPreview) {
    // When switching to Date/Time step
    if (newStep === 1) {
      if (settings.value.calendar.layoutStyle === 'list') {
        listViewStep.value = 'days';
      }
      if (formData.appointment_date || settings.value.calendar.layoutStyle === 'list') {
        fetchAvailableTimeSlots();
      }
    }
    // When switching to Confirmation step, pre-fill data if it's missing
    if (newStep === 4 && !formData.appointment_date) {
        formData.appointment_date = formatDateForInput(new Date());
        formData.appointment_time = '10:00';
        formData.customer_first_name = 'John';
        formData.customer_last_name = 'Doe';
        formData.customer_email = 'john.doe@example.com';
    }
  }
}, { immediate: true });

// Calendar methods
const previousMonth = () => {
  currentCalendarDate.value = new Date(
    currentCalendarDate.value.getFullYear(),
    currentCalendarDate.value.getMonth() - 1,
    1
  );
  if (settings.value.calendar.layoutStyle === 'list') {
    fetchAvailableTimeSlots();
  }
};

const nextMonth = () => {
  currentCalendarDate.value = new Date(
    currentCalendarDate.value.getFullYear(),
    currentCalendarDate.value.getMonth() + 1,
    1

  );
  if (settings.value.calendar.layoutStyle === 'list') {
    fetchAvailableTimeSlots();
  }
};

const selectDate = (dateObj) => {
  if (dateObj.disabled || dateObj.isOtherMonth) return;

  formData.appointment_date = formatDateForInput(dateObj.date);
  formData.appointment_time = '';
  availableTimeSlots.value = [];
  fetchAvailableTimeSlots(); // This will populate availableTimeSlots
  clearError('appointment_date'); // Clear error on date selection
  clearError('appointment_time'); // Clear time error
  clearError('available_slots'); // Clear slot error
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

const formatTimeForDisplay = (timeString, dateString, displayTimezone = false) => {
  // Ensure both date and time strings are present and valid
  if (!timeString || !dateString || !/^\d{2}:\d{2}$/.test(timeString) || !/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
    console.warn('formatTimeForDisplay: Missing or invalid date/time string.', { date: dateString, time: timeString });
    return ''; // Return empty string or a default placeholder like 'N/A'
  }

  try {
    // Construct a full date-time string in ISO format for reliable Date object creation
    const dateTimeString = `${dateString}T${timeString}:00`;
    const dateTime = new Date(dateTimeString);

    if (isNaN(dateTime.getTime())) {
      throw new Error('Invalid date-time for parsing. Input: ' + dateTimeString);
    }

    const shouldIncludeTimezone = displayTimezone && globalSettings.value?.businessTimezone && Intl.DateTimeFormat().resolvedOptions().timeZone;

    let displayOptions = {
      hour: '2-digit',
      minute: '2-digit',
      hour12: true,
    };

    if (shouldIncludeTimezone) {
      try {
        const formattedTime = new Intl.DateTimeFormat('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
        }).format(dateTime);

        const timezoneAbbr = new Intl.DateTimeFormat('en-US', {
            timeZoneName: 'short',
            timeZone: globalSettings.value.businessTimezone
        }).format(dateTime).split(' ').pop();

        return `${formattedTime} ${timezoneAbbr || ''}`;

      } catch (tzError) {
          console.warn('Could not format with specific timezone, falling back:', tzError);
          const [hours, minutes] = timeString.split(':');
          const dt = new Date();
          dt.setHours(parseInt(hours), parseInt(minutes), 0, 0);
          return dt.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
      }

    } else {
      const [hours, minutes] = timeString.split(':');
      const dt = new Date();
      dt.setHours(parseInt(hours), parseInt(minutes), 0, 0);
      return dt.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
    }

  } catch (e) {
    console.error("Error formatting time with timezone:", e);
    const [hours, minutes] = timeString.split(':');
    const dt = new Date();
    dt.setHours(parseInt(hours), parseInt(minutes), 0, 0);
    return dt.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
  }
};


// Step validation
const isStepValid = computed(() => {
  // Always run validation to populate `validationErrors`, but don't clear them here
  // Clearing happens when an input is changed via `clearError` or on `handleNext` attempt.
  let currentStepIsValid = true;

  // Clear only errors related to the current step *before* validating it
  // This prevents leftover errors from previous steps or unrelated fields
  for (const key in validationErrors) {
    if (Object.keys(currentStepFields[currentStep.value] || {}).includes(key)) {
      delete validationErrors[key];
    }
  }


  switch (currentStep.value) {
    case 1: // Formerly Step 2
      if (!formData.appointment_date) {
        validationErrors.appointment_date = 'Please select a date.';
        currentStepIsValid = false;
      }
      if (!formData.appointment_time) {
        validationErrors.appointment_time = 'Please select a time slot.';
        currentStepIsValid = false;
      }
      if (availableTimeSlots.value.length === 0 && formData.appointment_date && formData.service_id) {
          validationErrors.available_slots = 'No available time slots for the selected date and staff.';
          currentStepIsValid = false;
      }
      if (formData.is_recurring) {
        if (!formData.recurrence_frequency) {
          validationErrors.recurrence_frequency = 'Please select a recurrence frequency.';
          currentStepIsValid = false;
        }
        if (!formData.recurrence_interval || formData.recurrence_interval < 1) {
          validationErrors.recurrence_interval = 'Recurrence interval must be at least 1.';
          currentStepIsValid = false;
        }
        if (!formData.recurrence_count || formData.recurrence_count < 1) {
          validationErrors.recurrence_count = 'Recurrence count must be at least 1.';
          currentStepIsValid = false;
        }

        // Check against global max recurrences
        const maxRecurrences = globalSettings.value?.recurrence?.maxRecurrences;
        if (maxRecurrences > 0 && formData.recurrence_count > maxRecurrences) {
            validationErrors.recurrence_count = `The number of recurrences cannot exceed the maximum of ${maxRecurrences}.`;
            currentStepIsValid = false;
        }
      }
      break;
    case 2: // Formerly Step 3
      if (settings.value.customer.showFirstNameField && !formData.customer_first_name.trim()) {
        validationErrors.customer_first_name = 'First name is required.';
        currentStepIsValid = false;
      }
      if (settings.value.customer.showLastNameField && !formData.customer_last_name.trim()) {
        validationErrors.customer_last_name = 'Last name is required.';
        currentStepIsValid = false;
      }
      if (settings.value.customer.showEmailField) {
        if (!formData.customer_email.trim()) {
            validationErrors.customer_email = 'Email is required.';
            currentStepIsValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.customer_email)) {
            validationErrors.customer_email = 'Please enter a valid email address.';
            currentStepIsValid = false;
        }
      }
      // Phone field is optional by default in your code, so no required validation for it
      break;
    case 3: // Formerly Step 4
      // Payment validation logic if needed
      break;
    case 4: // Formerly Step 5
      // No validation needed for confirmation step, it's just a summary
      break;
  }
  return currentStepIsValid;
});

// Define which fields belong to which step for more precise error clearing
const currentStepFields = {
  1: { appointment_date: true, appointment_time: true, available_slots: true },
  2: { customer_first_name: true, customer_last_name: true, customer_email: true, customer_phone: true },
  3: { payment_method: true }, // Add other payment fields if applicable
};

// Navigation methods
const handleNext = async () => {
  if (showSummary.value) {
    showSummary.value = false;
    return;
  }

  formSubmitted.value = true; // Set flag to true to show errors now

  if (!isStepValid.value) {
    // If validation fails, stay on the current step and errors will be visible
    return;
  }

  // If in preview mode and attempting to submit, prevent actual submission
  if (props.isPreview && currentStep.value === visibleStepsCount.value) {
    console.log('PREVIEW MODE: Appointment submission prevented.');
    submissionSuccess.value = 'PREVIEW: Appointment would have been booked successfully!';
    setTimeout(() => { submissionSuccess.value = null; }, 3000);
    return; // Stop here, do not proceed with actual submission
  }

  // If validation passes, reset formSubmitted for the next step and proceed
  formSubmitted.value = false;

  if (currentStep.value === visibleStepsCount.value) { // Use visibleStepsCount here
    await submitAppointment();
  } else {
    // Find the next visible step
    const currentStepIndex = visibleSteps.value.findIndex(step => step.id === currentStep.value);
    if (currentStepIndex !== -1 && currentStepIndex < visibleSteps.value.length - 1) {
      currentStep.value = visibleSteps.value[currentStepIndex + 1].id;
    } else {
      // Fallback if somehow at the end but not step 5, or if step 5 is hidden
      currentStep.value++;
    }

    submissionError.value = null;
    submissionSuccess.value = null;
  }
};

const prevStep = () => {
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
    category_id: props.categoryId, // Reset to initial prop value
    service_id: props.serviceId,   // Reset to initial prop value
    staff_id: props.staffId || '0', // Reset to initial prop value
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

  availableTimeSlots.value = [];
  availableStaffForService.value = [];
  submissionError.value = null;
  submissionSuccess.value = null;
  submitting.value = false;
  // Determine the initial step based on whether service/category are pre-selected
  currentStep.value = 1; // Always reset to step 1 (summary card)
  formSubmitted.value = false; // Reset flag
};

// Data fetching functions
const fetchCategories = async () => {
  loadingCategories.value = true;
  categoriesError.value = null;
  try {
    const response = await servicesCategoriesApi.getCategories();
    const data = response.data || response;
    if (Array.isArray(data)) {
      categories.value = data;
    } else if (data && Array.isArray(data.categories)) {
      categories.value = data.categories;
    } else {
      categories.value = [];
    }
  } catch (err) {
    categoriesError.value = err.response?.data?.message || err.message || 'Error fetching categories.';
    console.error('Error fetching categories:', err);
  } finally {
    loadingCategories.value = false;
  }
};

const fetchServices = async () => {
  loadingServices.value = true;
  servicesError.value = null;
  try {
    const response = await servicesCategoriesApi.getServices({ per_page: -1 });
    const data = response.data || response; // Adjust based on API response structure
    if (data && Array.isArray(data.services)) {
      services.value = data.services;
    } else if (Array.isArray(data)) {
      services.value = data;
    } else {
      services.value = [];
    }
  } catch (err) {
    servicesError.value = err.response?.data?.message || err.message || 'Error fetching services.';
    console.error('Error fetching services:', err);
  } finally {
    loadingServices.value = false;
  }
};

const fetchStaffForSelectedService = async (serviceId) => {
  loadingStaff.value = true;
  staffError.value = null;
  availableStaffForService.value = [];

  if (!serviceId) {
    loadingStaff.value = false;
    return;
  }

  try {
    const response = await appointmentsApi.getStaffForService(serviceId);
    const data = response.data || response;
    if (data && Array.isArray(data.staff)) {
      availableStaffForService.value = data.staff;
    } else if (Array.isArray(data)) {
      availableStaffForService.value = data;
    } else {
      availableStaffForService.value = [];
    }
  } catch (err) {
    staffError.value = err.response?.data?.message || err.message || 'Error fetching employees for service.';
    console.error('Error fetching staff for service:', err);
  } finally {
    loadingStaff.value = false;
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

// Event handlers
const onCategoryChange = () => {
  formData.service_id = '';
  formData.staff_id = '0';
  formData.duration = 0;
  formData.price = 0.00;
  formData.appointment_date = '';
  formData.appointment_time = '';
  availableTimeSlots.value = [];
  availableStaffForService.value = [];
  // Reset calendar to current month when category changes
  currentCalendarDate.value = new Date();
  clearError('category_id'); // Clear error on change
  clearError('service_id'); // Clear service error (as it's dependent)
  // Re-fetch time slots if list view is active and category/service/staff has changed
  if (settings.value.calendar.layoutStyle === 'list' && currentStep.value === 2) {
      listViewStep.value = 'days'; // Reset to days view
      fetchAvailableTimeSlots();
  }
};

const onServiceChange = async () => {
  const selectedSvc = services.value.find(s => s.id == formData.service_id);
  if (selectedSvc) {
    formData.duration = parseInt(selectedSvc.duration) || 0;
    formData.price = parseFloat(selectedSvc.price) || 0.00;
    // Only fetch staff if showStaffInfo is enabled
    if (settings.value.forms.serviceForm.displayStaffNames) {
      await fetchStaffForSelectedService(formData.service_id);
    }
  } else {
    formData.duration = 0;
    formData.price = 0.00;
    availableStaffForService.value = [];
  }
  formData.staff_id = '0'; // Reset staff when service changes
  formData.appointment_date = ''; // Reset date when service changes
  formData.appointment_time = ''; // Reset time when service changes
  availableTimeSlots.value = []; // Clear time slots
  // Reset calendar to current month when service changes
  currentCalendarDate.value = new Date();
  clearError('service_id'); // Clear error on change
  clearError('appointment_date'); // Clear dependent errors
  clearError('appointment_time');
  // Re-fetch time slots if list view is active and category/service/staff has changed
  if (settings.value.calendar.layoutStyle === 'list' && currentStep.value === 2) {
      listViewStep.value = 'days'; // Reset to days view
      fetchAvailableTimeSlots();
  }
};

const onStaffChange = async () => {
  formData.appointment_date = ''; // Reset date when staff changes
  formData.appointment_time = ''; // Reset time when staff changes
  availableTimeSlots.value = []; // Clear time slots
  // Reset calendar to current month when staff changes
  currentCalendarDate.value = new Date();
  // Automatically fetch time slots if already on step 2 and a date was previously selected
  // This is now handled by watchers if formData.appointment_date is set.
  // No direct error to clear for staff_id if it's optional, but might clear dependent errors
  clearError('appointment_date');
  clearError('appointment_time');
  // Re-fetch time slots if list view is active and category/service/staff has changed
  if (settings.value.calendar.layoutStyle === 'list' && currentStep.value === 2) {
      listViewStep.value = 'days'; // Reset to days view
      fetchAvailableTimeSlots();
  }
};



// Final submission
const submitAppointment = async () => {
  submitting.value = true;
  submissionError.value = null;
  submissionSuccess.value = null;

  try {
    const payload = {
      // Changed to send first_name and last_name separately as per API error
      customer_first_name: formData.customer_first_name,
      customer_last_name: formData.customer_last_name,
      customer_email: formData.customer_email,
      customer_phone: formData.customer_phone || null,
      service_id: formData.service_id,
      staff_id: formData.staff_id === '0' ? null : formData.staff_id, // Send null if 'any staff'
      appointment_date: formData.appointment_date,
      appointment_time: formData.appointment_time,
      duration: formData.duration,
      total_price: formData.price,
      notes: formData.notes,
      status: 'pending', // Default status for new bookings
      payment_status: 'unpaid', // Default payment status
      number_of_persons: formData.number_of_persons,
      is_recurring: formData.is_recurring,
      recurrence_frequency: formData.recurrence_frequency,
      recurrence_interval: formData.recurrence_interval,
      recurrence_end_date: formData.recurrence_end_date,
      recurrence_count: formData.recurrence_count,
    };

    const appointmentResponse = await appointmentsApi.createAppointment(payload);
    console.log('Appointment API Response Data:', appointmentResponse.data);
    if (!appointmentResponse.data || !appointmentResponse.data.id) {
      throw new Error('Failed to create appointment or retrieve appointment ID.');
    }
    const appointment_id = appointmentResponse.data.id;
    console.log('Appointment created:', appointmentResponse.data);

    // Step 2: Handle payment based on selected method
    if (formData.payment_method === 'stripe' && stripeSettings.value?.enableStripe) {
      console.log('Payment method is Stripe. Initiating Stripe payment...');
      const stripeOrderPayload = {
        amount: formData.price,
        currency_code: globalSettings.value.currencyCode,
        appointment_id: appointment_id,
        description: `Booking for ${selectedService.value?.title || 'Service'} (ID: ${appointment_id})`,
      };

      const stripeOrderResponse = await stripeApi.createCheckoutSession(stripeOrderPayload);
      const checkout_url = stripeOrderResponse.data.checkout_url;

      if (checkout_url) {
        window.location.href = checkout_url;
      } else {
        throw new Error('Stripe checkout URL not received.');
      }
    } else { // Default to 'cash' or 'local' payment
      // Create a payment record for local payment
      const paymentPayload = {
        appointment_id: appointment_id,
        amount: formData.price,
        payment_type: 'local',
        transaction_id: null,
        status: globalSettings.value.instantBookingEnabled ? 'completed' : 'pending',
        notes: 'Payment to be made locally.',
      };
      await paymentsApi.createPayment(paymentPayload);
      console.log('Local payment record created.');

      if (globalSettings.value.instantBookingEnabled) {
        submissionSuccess.value = `Appointment booked successfully! A confirmation email has been sent.`;
      } else {
        submissionSuccess.value = `Appointment booked successfully! It is pending confirmation.`;
      }
    }
  } catch (err) {
    submissionError.value = err.response?.data?.message || err.message || 'Failed to place booking. Please try again.';
    console.error('Appointment submission error:', err);
  } finally {
    submitting.value = false;
  }
};


// --- Watchers ---
watch(() => formData.service_id, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    // Update duration and price based on the new service
    const selectedSvc = services.value.find(s => s.id == newVal);
    if (selectedSvc) {
      formData.duration = parseInt(selectedSvc.duration) || 0;
      const price = parseFloat(selectedSvc.price) || 0.00;
      baseServicePrice.value = price;
      
      if (globalSettings.value?.enableGroupBooking) {
        formData.price = calculateGroupBookingPrice(
          price,
          formData.number_of_persons,
          globalSettings.value.groupBookingPriceLogic
        );
      } else {
        formData.price = price;
      }
    } else {
      formData.duration = 0;
      baseServicePrice.value = 0;
      formData.price = 0.00;
    }

    // When service changes, clear related fields and re-fetch staff
    formData.staff_id = '0';
    formData.appointment_date = '';
    formData.appointment_time = '';
    availableTimeSlots.value = [];
    if (newVal) {
      fetchStaffForSelectedService(newVal);
      // If current step is 2, re-fetch time slots when service changes (especially important for list view)
      if (currentStep.value === 2) {
          fetchAvailableTimeSlots();
      }
    } else {
      availableStaffForService.value = [];
    }
  }
});

watch(() => formData.appointment_date, (newVal, oldVal) => {
  if (newVal !== oldVal && newVal) {
    fetchAvailableTimeSlots();
  } else {
    // Only clear time slots if default layout is active and date is cleared
    // For list layout, time slots are fetched based on category/service/staff
    if (settings.value.calendar.layoutStyle === 'default') {
      availableTimeSlots.value = [];
      formData.appointment_time = '';
    }
  }
});

watch(() => formData.staff_id, (newVal, oldVal) => {
  // When staff changes, re-fetch time slots if a date is already selected or if list view is active
  if (newVal !== oldVal && (formData.appointment_date || settings.value.calendar.layoutStyle === 'list')) {
    fetchAvailableTimeSlots();
  }
});

watch(() => settings.value.calendar.layoutStyle, (newStyle) => {
    if (currentStep.value === 2) { // Only if on the calendar step
        fetchAvailableTimeSlots();
        if (newStyle === 'list') {
            listViewStep.value = 'days'; // Reset to days view when switching to list layout
        }
    }
});

watch(() => [props.serviceId, props.categoryId, props.staffId], ([newServiceId, newCategoryId, newStaffId]) => {
  formData.service_id = newServiceId;
  formData.category_id = newCategoryId;
  formData.staff_id = newStaffId || '0';

  // Always start at step 1 (summary card)
  currentStep.value = 1;
  // Fetch staff and time slots immediately if pre-selected (needed for summary and next steps)
  if (newServiceId) {
    fetchStaffForSelectedService(newServiceId);
    fetchAvailableTimeSlots();
  }
}, { immediate: true }); // Run immediately on component mount


// Lifecycle Hook
onMounted(async () => {
  checkAdminBar();
  window.addEventListener('resize', handleResize);

  await fetchGlobalSettingsComposable(); // Call the composable's fetch function
  await Promise.all([
    fetchCategories(),
    fetchServices()
  ]);

  // Fetch Stripe settings
  try {
    const response = await stripeApi.getStripeSettings();
    stripeSettings.value = response.data;
  } catch (err) {
    console.error('Error fetching Stripe settings:', err);
  }

  // If service and category are pre-selected, fetch initial data
  if (props.serviceId !== null && props.categoryId !== null) {
    const selectedSvc = services.value.find(s => s.id == props.serviceId);
    if (selectedSvc) {
      formData.duration = parseInt(selectedSvc.duration) || 0;
      const price = parseFloat(selectedSvc.price) || 0.00;
      formData.price = price;
      baseServicePrice.value = price; // Set baseServicePrice here
      updatePriceForGroupBooking(); // Recalculate price based on initial number_of_persons
    }
    if (props.serviceId) {
      fetchStaffForSelectedService(props.serviceId);
      fetchAvailableTimeSlots();
    }
  }
});

onUnmounted(() => {
  window.removeEventListener('resize', handleResize);
});
</script>

<style scoped>
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

/* Base container for the whole form */
.wp-reservation-form {
  background-color: var(--background-color);
  font-family: var(--font-family-form); /* Apply font family */
  font-size: var(--base-font-size-form); /* Apply base font size */
  color: var(--text-color);
  /* min-h-screen is already applied via Tailwind in template */
  /* transition-all duration-300 is already applied via Tailwind in template */
}

/* Main form container (the "card") */
/* Apply rounded corners and shadows directly from CSS variables */
.relative.rounded-lg.overflow-hidden.shadow-lg.w-full.max-w-xl.mx-auto.flex.flex-col {
  border-radius: var(--border-radius-form); /* Apply rounded corners */
  box-shadow: var(--shadow-form); /* Apply shadows */
  height: auto;
  max-height: none;
}

/* Main Form Content Area - NO internal scrolling */
.form-body {
  /* Styles are applied via inline styles from computed properties */
  flex-grow: 1; /* Allows it to take available space */
  overflow-y: visible; /* Prevents internal scrolling */
  -webkit-overflow-scrolling: auto; /* Revert to default scrolling behavior */
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
}

/* Checkbox/Radio Group */
.checkbox-group {
  /* Tailwind classes handle this */
}

.form-checkbox,
.form-radio {
  accent-color: var(--primary-color);
}

/* Color Picker Specifics */
.color-picker-group {
  /* Tailwind classes handle this */
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
}

/* This applies to all buttons globally, including nav buttons */
button:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: var(--shadow-form); /* Apply shadows */
}

button:active:not(:disabled) {
  transform: translateY(0);
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

  .calendar-date:hover,
  .time-slot-btn:hover,
  .time-slot:hover {
    background-color: var(--background-color) !important; /* Revert to normal background on hover */
    border-color: var(--text-color)33 !important; /* Revert to normal border on hover */
    color: var(--text-color) !important; /* Revert to normal text color on hover */
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
</style>>/style>>>>