<template>
  <div>
    <!-- Main Calendar View -->
    <div v-if="!showAppointmentFormModal" class="max-w-7xl mx-auto">
      <h1 class="text-3xl font-bold text-gray-900 mb-8" :style="{ color: 'var(--admin-text-color)' }">{{ __('Appointments Calendar', 'schedula-smart-appointment-booking') }}</h1>
      
      <div class="rounded-2xl shadow-xl overflow-hidden content-card">
        <!-- Calendar Header -->
        <div class="p-4 md:p-6 border-b" :style="{ borderColor: 'var(--admin-border-color)' }">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex items-center space-x-2 md:space-x-4 mb-4 md:mb-0">
              <button @click="previousMonth" class="p-2 rounded-lg transition-colors" :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-input-text-color)' }">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
              </button>
              <h2 class="text-xl md:text-2xl font-bold" :style="{ color: 'var(--admin-text-color)' }">
                {{ currentMonthYear }}
              </h2>
              <button @click="nextMonth" class="p-2 rounded-lg transition-colors" :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-input-text-color)' }">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </button>
            </div>
            
            <!-- View Toggle Buttons -->
            <div class="flex items-center space-x-2">
              <div class="flex rounded-lg p-1" :style="{ backgroundColor: 'var(--admin-input-border-color)' }">
                <button 
                  @click="changeView('month')"
                  :class="[
                    'px-3 py-1 rounded text-sm font-medium transition-colors',
                    currentView === 'month' ? 'shadow-sm' : '']" :style="{ backgroundColor: currentView === 'month' ? 'var(--admin-card-bg-color)' : 'transparent', color: currentView === 'month' ? 'var(--admin-text-color)' : 'var(--admin-card-text-color)' }">
                  {{ __('Month', 'schedula-smart-appointment-booking') }}
                </button>
                <button 
                  @click="changeView('week')"
                  :class="[
                    'px-3 py-1 rounded text-sm font-medium transition-colors',
                    currentView === 'week' ? 'shadow-sm' : '']" :style="{ backgroundColor: currentView === 'week' ? 'var(--admin-card-bg-color)' : 'transparent', color: currentView === 'week' ? 'var(--admin-text-color)' : 'var(--admin-card-text-color)' }">
                  {{ __('Week', 'schedula-smart-appointment-booking') }}
                </button>
                <button 
                  @click="changeView('day')"
                  :class="[
                    'px-3 py-1 rounded text-sm font-medium transition-colors',
                    currentView === 'day' ? 'shadow-sm' : '']" :style="{ backgroundColor: currentView === 'day' ? 'var(--admin-card-bg-color)' : 'transparent', color: currentView === 'day' ? 'var(--admin-text-color)' : 'var(--admin-card-text-color)' }">
                  {{ __('Day', 'schedula-smart-appointment-booking') }}
                </button>
              </div>
              
              <button 
                @click="openAppointmentFormModal"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" :style="{ backgroundColor: 'var(--admin-link-blue-bg)', color: 'var(--admin-link-blue-text)' }">
                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="hidden md:inline">{{ __('Add Appointment', 'schedula-smart-appointment-booking') }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Calendar Content -->
        <div class="relative">
          <!-- Loading State -->
          <div v-if="loading" class="flex items-center justify-center py-20">
            <div class="text-center">
              <svg class="animate-spin h-12 w-12 mx-auto mb-4" :style="{ color: 'var(--admin-link-blue-bg)' }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <p class="text-lg" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Loading calendar...', 'schedula-smart-appointment-booking') }}</p>
            </div>
          </div>

          <!-- Month View -->
          <div v-else-if="currentView === 'month'" class="p-2 md:p-6">
            <div class="grid grid-cols-7 gap-1">
              <!-- Day Headers -->
              <div v-for="day in dayHeaders" :key="day" class="p-2 md:p-3 text-center font-semibold rounded-lg text-xs md:text-sm" :style="{ backgroundColor: 'var(--admin-input-border-color)', color: 'var(--admin-card-text-color)' }">
                {{ day }}
              </div>
              
              <!-- Calendar Days -->
              <div 
                v-for="day in calendarDays" 
                :key="`${day.date}-${day.month}`"
                @click="selectDate(day)"
                :class="[
                  'min-h-[60px] md:min-h-[140px] p-1 md:p-2 border rounded-lg cursor-pointer transition-all duration-200',
                  day.isToday ? 'ring-2 ring-blue-500' : '' ]" :style="{ backgroundColor: day.isCurrentMonth ? 'var(--admin-card-bg-color)' : 'var(--admin-input-border-color)', borderColor: 'var(--admin-border-color)', color: day.isCurrentMonth ? 'var(--admin-card-text-color)' : 'var(--admin-input-text-muted)' }">
                <div class="flex justify-between items-start mb-1 md:mb-2">
                  <span :class="[
                    'text-xs md:text-sm font-medium',
                    day.isToday ? 'px-2 py-1 rounded-full' : '' ]" :style="{ backgroundColor: day.isToday ? 'var(--admin-link-blue-bg)' : 'transparent', color: day.isToday ? 'var(--admin-link-blue-text)' : 'var(--admin-card-text-color)' }">
                    {{ day.date }}
                  </span>
                </div>
                
                <!-- Events for this day -->
                <div class="space-y-1">
                  <div 
                    v-for="event in getEventsForDay(day)" 
                    :key="event.id"
                    class="text-xs p-1 md:p-2 rounded-lg text-white cursor-pointer transition-all duration-200 hover:scale-105 hover:shadow-md"
                    :style="getEventColor(event.extendedProps.status)"
                    @click.stop="showEventPreview(event, $event)"
                    @mouseenter="showQuickPreview(event, $event)"
                    @mouseleave="hideQuickPreview"
                  >
                    <div class="font-medium truncate">{{ event.title }}</div>
                    <div class="text-xs opacity-90 hidden md:block">{{ formatEventTime(event.start) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Week View -->
          <div v-else-if="currentView === 'week'" class="p-2 md:p-6">
            <!-- Empty State for Week View -->
            <div v-if="appointments.length === 0" class="flex flex-col items-center justify-center py-16 px-4 text-center">
              <div class="p-4 rounded-full mb-4" :style="{ backgroundColor: 'var(--admin-link-purple-bg)' }">
                <svg class="h-10 w-10" :style="{ color: 'var(--admin-link-purple-text)' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('No appointments this week', 'schedula-smart-appointment-booking') }}</h3>
              <p class="max-w-md mb-6" :style="{ color: 'var(--admin-card-text-color)' }">
                {{ __('You don\'t have any appointments scheduled for this week. Click the button below to create a new appointment.', 'schedula-smart-appointment-booking') }}
              </p>
              <button 
                @click="openAppointmentFormModal"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
              >
                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('New Appointment', 'schedula-smart-appointment-booking') }}
              </button>
            </div>
            
            <!-- Week View Content -->
            <div v-else class="overflow-x-auto">
              <div class="grid grid-cols-8 gap-1 min-w-max">
                <!-- Time column header -->
                <div class="p-2 md:p-3 text-center font-semibold rounded-lg text-xs md:text-sm" :style="{ backgroundColor: 'var(--admin-input-border-color)', color: 'var(--admin-card-text-color)' }">{{ __('Time', 'schedula-smart-appointment-booking') }}</div>
                
                <!-- Day headers for week -->
                <div v-for="day in weekDays" :key="day.date" class="p-2 md:p-3 text-center font-semibold rounded-lg text-xs md:text-sm" :style="{ backgroundColor: 'var(--admin-input-border-color)', color: 'var(--admin-card-text-color)' }">
                  <div>{{ day.dayName }}</div>
                  <div :class="[
                    'text-base md:text-lg font-bold mt-1',
                    day.isToday ? 'px-2 py-1 rounded-full' : ''
                  ]" :style="{ backgroundColor: day.isToday ? 'var(--admin-link-purple-bg)' : 'transparent', color: day.isToday ? 'var(--admin-link-purple-text)' : 'var(--admin-card-text-color)' }">
                    {{ day.date }}
                  </div>
                </div>
                
                <!-- Time slots -->
                <div v-for="hour in timeSlots" :key="hour" class="contents">
                  <div class="p-1 md:p-2 text-xs md:text-sm border-r text-center" :style="{ backgroundColor: 'var(--admin-input-border-color)', borderColor: 'var(--admin-border-color)', color: 'var(--admin-card-text-color)' }">
                    {{ formatHour(hour) }}
                  </div>
                  <div
                    v-for="day in weekDays"
                    :key="`${day.date}-${hour}`"
                    class="min-h-[40px] md:min-h-[60px] p-1 border cursor-pointer transition-colors" :style="{ borderColor: 'var(--admin-border-color)', backgroundColor: 'var(--admin-card-bg-color)' }">
                    <!-- Events for this time slot -->
                    <div
                      v-for="event in getEventsForTimeSlot(day, hour)"
                      :key="event.id"
                      class="text-xs p-1 rounded text-white cursor-pointer mb-1 transition-all hover:scale-105"
                      :style="getEventColor(event.extendedProps.status)"
                      @click.stop="showEventPreview(event, $event)"
                      @mouseenter="showQuickPreview(event, $event)"
                      @mouseleave="hideQuickPreview"
                    >
                      <div class="font-medium truncate">{{ event.title }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Day View -->
          <div v-else-if="currentView === 'day'" class="p-2 md:p-6">
            <!-- Empty State for Day View -->
            <div v-if="appointments.length === 0" class="flex flex-col items-center justify-center py-16 px-4 text-center">
              <div class="p-4 rounded-full mb-4" :style="{ backgroundColor: 'var(--admin-link-purple-bg)' }">
                <svg class="h-10 w-10" :style="{ color: 'var(--admin-link-purple-text)' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h3 class="text-lg font-medium mb-2" :style="{ color: 'var(--admin-text-color)' }">{{ __('No appointments today', 'schedula-smart-appointment-booking') }}</h3>
              <p class="max-w-md mb-6" :style="{ color: 'var(--admin-card-text-color)' }">
                {{ __('You don\'t have any appointments scheduled for today. Click the button below to create a new appointment.', 'schedula-smart-appointment-booking') }}
              </p>
              <div class="flex space-x-3">
                <button 
                  @click="openAppointmentFormModal"
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500" :style="{ backgroundColor: 'var(--admin-link-purple-bg)', color: 'var(--admin-link-purple-text)' }">
                  <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  {{ __('New Appointment', 'schedula-smart-appointment-booking') }}
                </button>
                <button 
                  @click="goToToday"
                  class="inline-flex items-center px-4 py-2 border rounded-md shadow-sm text-sm font-medium" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', borderColor: 'var(--admin-border-color)', color: 'var(--admin-button-secondary-text)' }">
                  <svg class="-ml-1 mr-2 h-4 w-4" :style="{ color: 'var(--admin-card-text-color)' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  {{ __('Go to Today', 'schedula-smart-appointment-booking') }}
                </button>
              </div>
            </div>
            
            <!-- Day View Content -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Time column -->
              <div class="space-y-1">
                <div v-for="hour in timeSlots" :key="hour" class="flex">
                  <div class="w-16 md:w-20 p-2 text-xs md:text-sm text-right" :style="{ color: 'var(--admin-card-text-color)' }">
                    {{ formatHour(hour) }}
                  </div>
                  <div 
                    class="flex-1 min-h-[50px] md:min-h-[60px] p-2 border cursor-pointer transition-colors" :style="{ borderColor: 'var(--admin-border-color)', backgroundColor: 'var(--admin-card-bg-color)' }">
                    <!-- Events for this hour -->
                    <div 
                      v-for="event in getEventsForTimeSlot(selectedDay, hour)" 
                      :key="event.id"
                      class="text-sm p-2 rounded text-white cursor-pointer mb-1 transition-all hover:scale-105"
                      :style="getEventColor(event.extendedProps.status)"
                      @click.stop="showEventPreview(event, $event)"
                      @mouseenter="showQuickPreview(event, $event)"
                      @mouseleave="hideQuickPreview"
                    >
                      <div class="font-medium">{{ event.title }}</div>
                      <div class="text-xs opacity-90 hidden md:block">{{ event.extendedProps.service_title }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Preview Tooltip -->
          <div 
            v-if="quickPreview.show"
            :style="{ top: quickPreview.y + 'px', left: quickPreview.x + 'px', backgroundColor: 'var(--admin-card-bg-color)', color: 'var(--admin-card-text-color)' }"
            class="fixed z-50 p-3 rounded-lg shadow-xl max-w-xs pointer-events-none transform -translate-x-1/2 -translate-y-full"
          >
            <div class="font-semibold">{{ quickPreview.event?.title }}</div>
            <div class="text-sm mt-1" :style="{ color: 'var(--admin-card-text-color)' }">{{ quickPreview.event?.extendedProps?.service_title }}</div>
            <div class="text-xs mt-1" :style="{ color: 'var(--admin-card-text-color)' }">{{ formatEventTime(quickPreview.event?.start) }} - {{ formatEventTime(quickPreview.event?.end) }}</div>
            <div class="text-xs" :style="{ color: 'var(--admin-card-text-color)' }">{{ quickPreview.event?.extendedProps?.staff_name }}</div>
            <div 
              class="text-xs px-2 py-1 rounded mt-2 inline-block"
              :style="getStatusColor(quickPreview.event?.extendedProps?.status)"
            >
              {{ translateStatus(quickPreview.event?.extendedProps?.status) }}
            </div>
          </div>
        </div>
      </div>

      <!-- No Scroll Event Preview Modal -->
      <transition 
        enter-active-class="transition-opacity duration-300 ease-out"
        leave-active-class="transition-opacity duration-200 ease-in"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div 
          v-if="eventPreview.show"
          class="fixed inset-0 z-[99999] flex items-center justify-center p-4"
          @click.self="closeEventPreview"
          style="background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px);"
        >
          <div class="rounded-xl shadow-2xl w-full max-w-xl h-auto flex flex-col transform transition-all duration-300 ease-out scale-95 modal-content"
               :class="{ 'scale-100': eventPreview.show }">
            
            <!-- Header with gradient background -->
            <div class="px-6 py-4 rounded-t-xl" :style="{ backgroundColor: 'var(--admin-link-blue-bg)' }">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold" :style="{ color: 'var(--admin-link-blue-text)' }">{{ __('Appointment Details', 'schedula-smart-appointment-booking') }}</h3>
                <button 
                  @click="closeEventPreview"
                  class="p-1 rounded-full hover:bg-white/20 transition-colors" aria-label="Close" :style="{ color: 'var(--admin-link-blue-text)' }">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              </div>
              
              <!-- Status Badge -->
              <div v-if="eventPreview.event" class="mt-2">
                <span 
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :style="getStatusColor(eventPreview.event.extendedProps.status)"
                >
                  {{ translateStatus(eventPreview.event.extendedProps.status) }}
                </span>
              </div>
            </div>
            
            <!-- Content - No Scroll -->
            <div class="p-4 space-y-2"> <!-- Reduced padding and spacing -->
              <div v-if="eventPreview.event" class="grid grid-cols-1 md:grid-cols-2 gap-3"> <!-- Main 2-column grid -->

                <!-- Left Column: Customer Info & Service/Staff -->
                <div class="flex flex-col gap-3"> <!-- Vertical stack for left column items -->
                  <!-- Customer Info -->
                  <div class="rounded-lg p-3 border" :style="{ backgroundColor: 'var(--admin-input-border-color)', borderColor: 'var(--admin-border-color)' }"> <!-- Reduced padding -->
                    <div class="flex items-start space-x-3">
                      <div class="flex-shrink-0 h-8 w-8 rounded-full flex items-center justify-center" :style="{ backgroundColor: 'var(--admin-link-purple-bg)' }">
                        <svg class="h-4 w-4" :style="{ color: 'var(--admin-link-purple-text)' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                      </div>
                      <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-medium mb-1" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('CUSTOMER', 'schedula-smart-appointment-booking') }}</h4>
                        <p class="font-medium text-sm" :style="{ color: 'var(--admin-text-color)' }">{{ eventPreview.event.title }}</p>
                        <div class="mt-1 space-y-0.5">
                          <p v-if="eventPreview.event.extendedProps.originalData?.customer_phone" class="text-xs truncate" :style="{ color: 'var(--admin-card-text-color)' }">
                            {{ eventPreview.event.extendedProps.originalData.customer_phone }}
                          </p>
                          <p v-if="eventPreview.event.extendedProps.originalData?.customer_email" class="text-xs truncate" :style="{ color: 'var(--admin-card-text-color)' }">
                            {{ eventPreview.event.extendedProps.originalData.customer_email }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Service & Staff -->
                  <div class="rounded-lg p-3 border" :style="{ backgroundColor: 'var(--admin-input-border-color)', borderColor: 'var(--admin-border-color)' }"> <!-- Reduced padding -->
                    <h4 class="text-xs font-medium mb-1" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('SERVICE', 'schedula-smart-appointment-booking') }}</h4>
                    <p class="font-medium text-sm" :style="{ color: 'var(--admin-text-color)' }">{{ eventPreview.event.extendedProps.service_title || 'N/A' }}</p>
                    <p class="text-xs mt-1" :style="{ color: 'var(--admin-card-text-color)' }">
                      {{ __('Staff:', 'schedula-smart-appointment-booking') }} {{ eventPreview.event.extendedProps.staff_name || 'Not assigned' }}
                    </p>
                  </div>
                </div>

                <!-- Right Column: Time & Notes -->
                <div class="flex flex-col gap-3"> <!-- Vertical stack for right column items -->
                  <!-- Time -->
                  <div class="rounded-lg p-3 border" :style="{ backgroundColor: 'var(--admin-input-border-color)', borderColor: 'var(--admin-border-color)' }"> <!-- Reduced padding -->
                    <h4 class="text-xs font-medium mb-1" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('TIME', 'schedula-smart-appointment-booking') }}</h4>
                    <p class="font-medium text-sm" :style="{ color: 'var(--admin-text-color)' }">{{ formatEventDateTime(eventPreview.event.start) }}</p>
                    <p class="text-xs mt-1" :style="{ color: 'var(--admin-card-text-color)' }">
                      {{ __('Duration:', 'schedula-smart-appointment-booking') }} {{ calculateDuration(eventPreview.event.start, eventPreview.event.end) }}
                    </p>
                  </div>

                  <!-- Notes (if exists) -->
                  <div v-if="eventPreview.event.extendedProps.originalData?.notes" class="rounded-lg p-3 border" :style="{ backgroundColor: 'var(--admin-input-border-color)', borderColor: 'var(--admin-border-color)' }"> <!-- Reduced padding -->
                    <h4 class="text-xs font-medium mb-1" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('NOTES', 'schedula-smart-appointment-booking') }}</h4>
                    <p class="text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{ eventPreview.event.extendedProps.originalData.notes }}</p>
                  </div>
                </div>

              </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="p-4 border-t rounded-b-xl" :style="{ borderColor: 'var(--admin-border-color)', backgroundColor: 'var(--admin-input-border-color)' }">
              <div class="flex space-x-3">
                <button 
                  @click="editAppointment(eventPreview.event)"
                  class="flex-1 px-4 py-2 rounded-lg transition-colors flex items-center justify-center space-x-2 text-sm font-medium" :style="{ backgroundColor: 'var(--admin-link-blue-bg)', color: 'var(--admin-link-blue-text)' }">
                  <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                  <span>{{ __('Edit', 'schedula-smart-appointment-booking') }}</span>
                </button>
                <button 
                  @click="confirmDeleteAppointment(eventPreview.event)"
                  class="flex-1 px-4 py-2 rounded-lg transition-colors flex items-center justify-center space-x-2 text-sm font-medium" :style="{ backgroundColor: '#dc2626', borderColor: '#dc2626', color: 'white' }">
                  <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1-1H8a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  <span>{{ __('Delete', 'schedula-smart-appointment-booking') }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </transition>

      <!-- Enhanced Delete Confirmation Modal -->
      <transition 
        enter-active-class="transition-opacity duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div 
          v-if="showDeleteConfirmation"
          class="fixed inset-0 z-[110] flex items-center justify-center p-4"
          @click.self="cancelDelete"
          :style="{ backgroundColor: 'rgba(0, 0, 0, 0.5)', backdropFilter: 'blur(4px)' }">
          <div class="rounded-xl shadow-2xl w-full max-w-md transform transition-all duration-200 scale-95 modal-content"
               :class="{ 'scale-100': showDeleteConfirmation }">
            
            <!-- Header with warning icon -->
            <div class="px-6 py-5 border-b" :style="{ borderColor: 'var(--admin-border-color)' }">
              <div class="flex items-start">
                <div class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center" :style="{ backgroundColor: 'var(--admin-badge-red-bg)' }">
                  <svg class="h-5 w-5" :style="{ color: 'var(--admin-badge-red-text)' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                  </svg>
                </div>
                <div class="ml-4">
                  <h3 class="text-lg font-semibold" :style="{ color: 'var(--admin-text-color)' }">{{ __('Delete Appointment', 'schedula-smart-appointment-booking') }}</h3>
                  <p class="text-sm mt-1" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('This action cannot be undone.', 'schedula-smart-appointment-booking') }}</p>
                </div>
              </div>
            </div>
            
            <!-- Content -->
            <div class="px-6 py-5">
              <p :style="{ color: 'var(--admin-card-text-color)' }">
                {{ __('Are you sure you want to delete the appointment for', 'schedula-smart-appointment-booking') }} 
                <span class="font-semibold" :style="{ color: 'var(--admin-text-color)' }">{{ appointmentToDelete?.title }}</span>?
              </p>
              
              <div v-if="appointmentToDelete" class="mt-4 p-3 rounded-lg border" :style="{ backgroundColor: 'var(--admin-input-border-color)', borderColor: 'var(--admin-border-color)' }">
                <div class="flex items-start space-x-3">
                  <svg class="h-5 w-5 mt-0.5 flex-shrink-0" :style="{ color: 'var(--admin-input-text-color)' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  <div>
                    <p class="text-sm" :style="{ color: 'var(--admin-card-text-color)' }">
                      <span class="font-medium">{{ __('Date & Time:', 'schedula-smart-appointment-booking') }}</span> {{ formatEventDateTime(appointmentToDelete.start) }}
                    </p>
                    <p class="text-sm mt-1" :style="{ color: 'var(--admin-card-text-color)' }">
                      <span class="font-medium">{{ __('Service:', 'schedula-smart-appointment-booking') }}</span> {{ appointmentToDelete.extendedProps?.service_title || 'N/A' }}
                    </p>
                    <p v-if="appointmentToDelete.extendedProps?.staff_name" class="text-sm mt-1" :style="{ color: 'var(--admin-card-text-color)' }">
                      <span class="font-medium">{{ __('Staff:', 'schedula-smart-appointment-booking') }}</span> {{ appointmentToDelete.extendedProps.staff_name }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Actions -->
            <div class="px-6 py-4 border-t rounded-b-xl flex justify-end space-x-3" :style="{ borderColor: 'var(--admin-border-color)', backgroundColor: 'var(--admin-input-border-color)' }">
              <button 
                @click="cancelDelete"
                class="px-4 py-2.5 text-sm font-medium rounded-lg border focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors"
                :disabled="isDeleting"
              >
                {{ __('Cancel', 'schedula-smart-appointment-booking') }}
              </button>
              <button 
                @click="executeDelete"
                class="px-4 py-2.5 text-sm font-medium rounded-lg border focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors flex items-center space-x-2"
                :disabled="isDeleting"
                :class="{ 'opacity-75 cursor-not-allowed': isDeleting }" :style="{ backgroundColor: 'var(--admin-suggestion-red-bg)', borderColor: 'var(--admin-border-color)', color: 'var(--admin-suggestion-red-text)' }">
                <svg v-if="isDeleting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>{{ isDeleting ? __('Deleting...', 'schedula-smart-appointment-booking') : __('Delete Appointment', 'schedula-smart-appointment-booking') }}</span>
              </button>
            </div>
          </div>
        </div>
      </transition>
    </div>

    <!-- FULL PAGE: Appointment Form -->
    <div v-else class="min-h-screen" :style="{ backgroundColor: 'var(--admin-page-bg-color)' }">
      <div class="max-w-6xl mx-auto p-4">
        <!-- FIXED: Back Button -->
        <div class="flex justify-between items-center mb-6">
          <button 
            @click="handleCloseForm"
            class="px-4 py-2 border rounded-lg transition-colors flex items-center space-x-2" :style="{ backgroundColor: 'white', borderColor: 'var(--admin-border-color)', color: 'var(--admin-text-color)' }">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span>{{ __('Back to Calendar', 'schedula-smart-appointment-booking') }}</span>
          </button>
        </div>
        
        <!-- Full Page Form -->
        <div class="rounded-xl shadow-lg content-card">
          <div class="p-8">
            <AppointmentForm
              :initialData="selectedAppointment"
              :initialDate="eventStartDate"
              :initialTime="eventStartTime"
              @submit="handleAppointmentFormSubmit"
              @cancel="handleCloseForm"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { __ } from '@wordpress/i18n';
import AppointmentForm from '../components/appointments/AppointmentsForm.vue';
import { ref, computed, onMounted, reactive } from 'vue';
import { appointmentsApi } from '../api.js';
import { useToast } from '../composables/useToast.js'; // Import useToast

// Refs and reactive data
const currentDate = ref(new Date());
const currentView = ref('month');
const appointments = ref([]);
const showAppointmentFormModal = ref(false);
const selectedAppointment = ref(null);
const eventStartDate = ref('');
const eventStartTime = ref('');
const loading = ref(true);

// Delete confirmation
const showDeleteConfirmation = ref(false);
const appointmentToDelete = ref(null);
const isDeleting = ref(false);

// Quick preview tooltip
const quickPreview = reactive({
  show: false,
  event: null,
  x: 0,
  y: 0
});

// Event preview modal
const eventPreview = reactive({
  show: false,
  event: null
});

// Time slots for week/day view
const timeSlots = Array.from({ length: 12 }, (_, i) => i + 8); // 8 AM to 7 PM

// --- Use the toast composable ---
const { success, error: toastError } = useToast(); // Destructure toast functions


// Computed properties
const currentMonthYear = computed(() => {
  return currentDate.value.toLocaleDateString('en-US', { 
    month: 'long', 
    year: 'numeric' 
  });
});

const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const selectedDay = computed(() => {
  return {
    date: currentDate.value.getDate(),
    month: currentDate.value.getMonth(),
    year: currentDate.value.getFullYear(),
    fullDate: currentDate.value,
    isCurrentMonth: true,
    isToday: currentDate.value.toDateString() === new Date().toDateString()
  };
});

const weekDays = computed(() => {
  const startOfWeek = new Date(currentDate.value);
  startOfWeek.setDate(currentDate.value.getDate() - currentDate.value.getDay());
  
  const days = [];
  const today = new Date();
  
  for (let i = 0; i < 7; i++) {
    const date = new Date(startOfWeek);
    date.setDate(startOfWeek.getDate() + i);
    
    days.push({
      date: date.getDate(),
      month: date.getMonth(),
      year: date.getFullYear(),
      fullDate: date,
      dayName: date.toLocaleDateString('en-US', { weekday: 'short' }),
      isToday: date.toDateString() === today.toDateString()
    });
  }
  
  return days;
});

const calendarDays = computed(() => {
  const year = currentDate.value.getFullYear();
  const month = currentDate.value.getMonth();
  
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const startDate = new Date(firstDay);
  startDate.setDate(startDate.getDate() - firstDay.getDay());
  
  const days = [];
  const today = new Date();
  
  for (let i = 0; i < 42; i++) {
    const date = new Date(startDate);
    date.setDate(startDate.getDate() + i);
    
    days.push({
      date: date.getDate(),
      month: date.getMonth(),
      year: date.getFullYear(),
      fullDate: date,
      isCurrentMonth: date.getMonth() === month,
      isToday: date.toDateString() === today.toDateString()
    });
  }
  
  return days;
});



const getEventColor = (status) => {
  switch (status) {
    case 'pending': return { backgroundColor: 'var(--admin-badge-yellow-bg)', color: 'var(--admin-badge-yellow-text)' };
    case 'confirmed': return { backgroundColor: 'var(--admin-badge-green-bg)', color: 'var(--admin-badge-green-text)' };
    case 'completed': return { backgroundColor: 'var(--admin-badge-purple-bg)', color: 'var(--admin-badge-purple-text)' };
    case 'cancelled': return { backgroundColor: 'var(--admin-badge-red-bg)', color: 'var(--admin-badge-red-text)' };
    default: return { backgroundColor: 'var(--admin-badge-gray-bg)', color: 'var(--admin-badge-gray-text)' };
  }
};

const getStatusColor = (status) => {
  switch (status) {
    case 'pending': return { backgroundColor: 'var(--admin-badge-yellow-bg)', color: 'var(--admin-badge-yellow-text)' };
    case 'confirmed': return { backgroundColor: 'var(--admin-badge-green-bg)', color: 'var(--admin-badge-green-text)' };
    case 'completed': return { backgroundColor: 'var(--admin-badge-purple-bg)', color: 'var(--admin-badge-purple-text)' };
    case 'cancelled': return { backgroundColor: 'var(--admin-badge-red-bg)', color: 'var(--admin-badge-red-text)' };
    default: return { backgroundColor: 'var(--admin-badge-gray-bg)', color: 'var(--admin-badge-gray-text)' };
  }
};

const translateStatus = (status) => {
  if (!status) return '';
  const lowerStatus = status.toLowerCase();
  switch (lowerStatus) {
    case 'pending':
      return __('Pending', 'schedula-smart-appointment-booking');
    case 'confirmed':
      return __('Confirmed', 'schedula-smart-appointment-booking');
    case 'completed':
      return __('Completed', 'schedula-smart-appointment-booking');
    case 'cancelled':
      return __('Cancelled', 'schedula-smart-appointment-booking');
    default:
      return __(status.charAt(0).toUpperCase() + status.slice(1), 'schedula-smart-appointment-booking');
  }
};

const formatEventTime = (dateTime) => {
  if (!dateTime) return '';
  const date = new Date(dateTime);
  return date.toLocaleTimeString('en-US', { 
    hour: '2-digit', 
    minute: '2-digit',
    hour12: true 
  });
};

const formatEventDateTime = (dateTime) => {
  if (!dateTime) return '';
  const date = new Date(dateTime);
  return date.toLocaleString('en-US', { 
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit', 
    minute: '2-digit',
    hour12: true 
  });
};

const formatHour = (hour) => {
  const date = new Date();
  date.setHours(hour, 0, 0, 0);
  return date.toLocaleTimeString('en-US', { 
    hour: '2-digit', 
    minute: '2-digit',
    hour12: true 
  });
};

const calculateDuration = (start, end) => {
  if (!start || !end) return '';
  const startDate = new Date(start);
  const endDate = new Date(end);
  const diffMs = endDate - startDate;
  const diffMins = Math.round(diffMs / 60000);
  const hours = Math.floor(diffMins / 60);
  const minutes = diffMins % 60;
  
  if (hours > 0) {
    return `${hours}h ${minutes}m`;
  }
  return `${minutes}m`;
};

const getEventsForDay = (day) => {
  return appointments.value.filter(event => {
    const eventDate = new Date(event.start);
    return eventDate.toDateString() === day.fullDate.toDateString();
  });
};

const getEventsForTimeSlot = (day, hour) => {
  return appointments.value.filter(event => {
    const eventDate = new Date(event.start);
    return eventDate.toDateString() === day.fullDate.toDateString() && 
           eventDate.getHours() === hour;
  });
};

// FIXED: View change function
const changeView = (view) => {
  currentView.value = view;
};

// Navigation functions
const previousMonth = () => {
  if (currentView.value === 'month') {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1);
  } else if (currentView.value === 'week') {
    currentDate.value = new Date(currentDate.value.getTime() - 7 * 24 * 60 * 60 * 1000);
  } else if (currentView.value === 'day') {
    currentDate.value = new Date(currentDate.value.getTime() - 24 * 60 * 60 * 1000);
  }
};

const nextMonth = () => {
  if (currentView.value === 'month') {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1);
  } else if (currentView.value === 'week') {
    currentDate.value = new Date(currentDate.value.getTime() + 7 * 24 * 60 * 60 * 1000);
  } else if (currentView.value === 'day') {
    currentDate.value = new Date(currentDate.value.getTime() + 24 * 60 * 60 * 1000);
  }
};

const goToToday = () => {
  currentDate.value = new Date();
};

// Event handlers
const selectDate = (day) => {
  if (!day.isCurrentMonth) return;
  
  const dateStr = day.fullDate.toISOString().split('T')[0];
  eventStartDate.value = dateStr;
  eventStartTime.value = '';
  selectedAppointment.value = null;
  openAppointmentFormModal();
};

const selectTimeSlot = (day, hour) => {
  const dateStr = day.fullDate.toISOString().split('T')[0];
  const timeStr = `${hour.toString().padStart(2, '0')}:00`;
  eventStartDate.value = dateStr;
  eventStartTime.value = timeStr;
  selectedAppointment.value = null;
  openAppointmentFormModal();
};

// Quick preview functions
const showQuickPreview = (event, mouseEvent) => {
  if (window.innerWidth < 640) { // Assuming 640px is the mobile breakpoint
    return; // Do not show quick preview on small screens
  }
  quickPreview.show = true;
  quickPreview.event = event;
  quickPreview.x = mouseEvent.clientX;
  quickPreview.y = mouseEvent.clientY - 10;
};

const hideQuickPreview = () => {
  quickPreview.show = false;
  quickPreview.event = null;
};

// Event preview functions
const showEventPreview = (event, mouseEvent) => {
  hideQuickPreview();
  eventPreview.show = true;
  eventPreview.event = event;
};

const closeEventPreview = () => {
  eventPreview.show = false;
  eventPreview.event = null;
};

const editAppointment = async (event) => {
  try {
    const response = await appointmentsApi.getAppointment(event.id);
    const appointmentDetails = response.data;
    selectedAppointment.value = appointmentDetails;

    if (appointmentDetails.start_datetime) {
      const dateTime = new Date(appointmentDetails.start_datetime);
      eventStartDate.value = dateTime.toISOString().split('T')[0];
      eventStartTime.value = dateTime.toTimeString().split(' ')[0].substring(0, 5);
    }

    closeEventPreview();
    openAppointmentFormModal();
  } catch (error) {
    toastError(__('Error loading appointment details.', 'schedula-smart-appointment-booking')); // Use toast for error
    console.error('Error loading appointment details for edit:', error);
  }
};

// Delete confirmation functions
const confirmDeleteAppointment = (event) => {
  appointmentToDelete.value = event;
  showDeleteConfirmation.value = true;
  closeEventPreview();
};

const cancelDelete = () => {
  if (isDeleting.value) return;
  showDeleteConfirmation.value = false;
  appointmentToDelete.value = null;
};

const executeDelete = async () => {
  if (!appointmentToDelete.value || isDeleting.value) return;
  
  isDeleting.value = true;
  
  try {
    await appointmentsApi.deleteAppointment(appointmentToDelete.value.id);
    showDeleteConfirmation.value = false;
    appointmentToDelete.value = null;
    await loadCalendarData();
    success(__('Appointment deleted successfully', 'schedula-smart-appointment-booking')); // Use toast for success
  } catch (error) {
    console.error('Error deleting appointment:', error);
    toastError(error.response?.data?.message || error.message || __('Failed to delete appointment.', 'schedula-smart-appointment-booking')); // Use toast for error
  } finally {
    isDeleting.value = false;
  }
};

// Modal functions
const openAppointmentFormModal = () => {
  showAppointmentFormModal.value = true;
};

// FIXED: Close form function
const handleCloseForm = () => {
  showAppointmentFormModal.value = false;
  selectedAppointment.value = null;
  eventStartDate.value = '';
  eventStartTime.value = '';
};

const handleAppointmentFormSubmit = async (formData) => {
  try {
    let result;
    if (formData.id) {
      result = await appointmentsApi.updateAppointment(formData.id, formData);
    } else {
      result = await appointmentsApi.createAppointment(formData);
    }

    success(result.message || __(`Appointment ${formData.id ? 'updated' : 'created'} successfully!`, 'schedula-smart-appointment-booking')); // Use toast for success
    handleCloseForm();
    loadCalendarData();
    
  } catch (error) {
    toastError(error.response?.data?.message || error.message || __('Failed to save appointment.', 'schedula-smart-appointment-booking')); // Use toast for error
    console.error('Error saving appointment:', error);
  }
};

// Data loading
const loadCalendarData = async () => {
  loading.value = true;
  try {
    const response = await appointmentsApi.getAppointments({ per_page: 1000 });
    
    let appointmentsData = [];
    if (response.data && typeof response.data === 'object') {
      if (Array.isArray(response.data.appointments)) {
        appointmentsData = response.data.appointments;
      } else if (Array.isArray(response.data)) {
        appointmentsData = response.data;
      }
    }

    appointments.value = appointmentsData.map(app => ({
      id: String(app.id),
      title: `${app.customer_first_name || ''} ${app.customer_last_name || ''}`.trim() || __('Appointment', 'schedula-smart-appointment-booking'),
      start: app.start_datetime.replace(' ', 'T'),
      end: app.end_datetime.replace(' ', 'T'),
      extendedProps: {
        originalData: app,
        service_title: app.service_title || __('Service', 'schedula-smart-appointment-booking'),
        staff_name: app.staff_name || __('Staff', 'schedula-smart-appointment-booking'),
        status: app.status || 'pending'
      }
    }));
    
  } catch (error) {
    toastError(__('Error loading appointments.', 'schedula-smart-appointment-booking')); // Use toast for error
    console.error('Error loading calendar data:', error);
  } finally {
    loading.value = false;
  }
};

// Lifecycle
onMounted(() => {
  loadCalendarData();
});
</script>

<style scoped>
/* Smooth transitions and hover effects */
.transition-all {
  transition: all 0.2s ease-in-out;
}

/* Custom scrollbar for modal content */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Prevent text selection on calendar elements */
.cursor-pointer {
  user-select: none;
}

/* Animation for quick preview */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.fixed.z-50.bg-gray-900 {
  animation: fadeIn 0.2s ease-out;
}
</style>
