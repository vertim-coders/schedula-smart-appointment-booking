<template>
  <div class="min-h-screen pb-10 px-4 md:px-6 lg:px-8" 
       :class="{ 'dark': appearanceSettings.adminDarkModeEnabled }" 
       :style="adminCustomStyles">
    <h1 class="text-4xl font-semibold mb-8 text-left" :style="{ color: 'var(--admin-text-color)' }">{{ __('Dashboard Overview', 'schedula-smart-appointment-booking') }}</h1>

    <!-- Loading/error message (for API errors) -->
    <div v-if="loading" class="min-h-[60vh] flex items-center justify-center">
      <!-- Loading skeleton elements remain with neutral gray colors for contrast in both modes -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch w-full max-w-7xl px-4">
        <!-- Left Column Skeleton (lg:col-span-2) -->
        <div class="lg:col-span-2 flex flex-col gap-6">
          <!-- Date filters skeleton -->
          <div class="bg-[var(--admin-card-bg)] p-4 rounded-lg shadow-xl animate-pulse">
            <div class="h-6 bg-gray-300 rounded w-1/3 mb-4"></div>
            <div class="h-10 bg-gray-300 rounded-lg w-full mb-3"></div>
            <div class="h-8 bg-gray-300 rounded-lg w-1/4 self-end"></div>
          </div>

          <!-- Statistics cards skeleton -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div v-for="i in 4" :key="'stat-skeleton-' + i" class="bg-[var(--admin-card-bg)] p-6 rounded-lg shadow-md animate-pulse">
              <div class="h-5 bg-gray-300 rounded w-1/2 mb-3"></div>
              <div class="h-8 bg-gray-300 rounded w-3/4 mb-4"></div>
              <div class="h-4 bg-gray-300 rounded w-full"></div>
            </div>
          </div>

          <!-- Chart section skeleton -->
          <div class="flex-grow flex flex-col">
            <div class="bg-[var(--admin-card-bg)] p-6 rounded-lg shadow-md flex-1 animate-pulse">
              <div class="h-6 bg-gray-300 rounded w-2/3 mb-4"></div>
              <div class="h-64 bg-gray-300 rounded-lg"></div>
            </div>
          </div>
        </div>

        <!-- Right Column Skeleton (lg:col-span-1) -->
        <div class="lg:col-span-1 flex flex-col gap-6">
          <!-- Smart suggestions card skeleton -->
          <div class="bg-[var(--admin-card-bg)] p-6 rounded-lg shadow-md animate-pulse">
            <div class="h-6 bg-gray-300 rounded w-3/4 mb-4"></div>
            <div class="h-4 bg-gray-300 rounded w-full mb-2"></div>
            <div class="h-4 bg-gray-300 rounded w-5/6 mb-2"></div>
            <div class="h-4 bg-gray-300 rounded w-2/3"></div>
          </div>

          <!-- Quick links card skeleton -->
          <div class="bg-[var(--admin-card-bg)] p-6 rounded-lg shadow-md animate-pulse">
            <div class="h-6 bg-gray-300 rounded w-1/2 mb-4"></div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div v-for="i in 4" :key="'link-skeleton-' + i" class="h-12 bg-gray-300 rounded-md"></div>
            </div>
          </div>

          <!-- Newsletter subscription card skeleton -->
          <div class="bg-[var(--admin-card-bg)] p-6 rounded-lg shadow-md animate-pulse">
            <div class="h-6 bg-gray-300 rounded w-2/3 mb-2"></div>
            <div class="h-4 bg-gray-300 rounded w-full mb-4"></div>
            <div class="h-10 bg-gray-300 rounded-lg w-full mb-3"></div>
            <div class="h-4 bg-gray-300 rounded w-1/2"></div>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="error" 
         :class="['px-6 py-4 rounded-lg relative text-center shadow-md']" 
         :style="{ backgroundColor: 'var(--admin-suggestion-red-bg)', color: 'var(--admin-suggestion-red-text)' }" 
         role="alert">
      <span class="block text-lg font-semibold">Error: {{ error }}</span>
      <p class="text-sm mt-2">{{ __('Please try again later or contact support if the problem persists.', 'schedula-smart-appointment-booking') }}</p>
    </div>

    <!-- Date Error Popup -->
    <div v-if="showDateErrorPopup" class="fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg"
         :style="{ backgroundColor: 'var(--admin-suggestion-red-bg)', color: 'var(--admin-suggestion-red-text)' }">
      <div class="flex">
        <div class="flex-shrink-0">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium">{{ dateErrorMessage }}</p>
        </div>
        <div class="ml-auto pl-3">
          <div class="-mx-1.5 -my-1.5">
            <button @click="showDateErrorPopup = false" 
                    class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2"
                    :style="{ backgroundColor: 'var(--admin-suggestion-red-bg)', color: 'var(--admin-suggestion-red-text)' }">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Area (visible after loading/error check) -->
    <div v-if="!loading && !error" class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">
      <!-- Left Column: Date Filters, Stats Cards, Chart -->
      <div class="lg:col-span-2 flex flex-col gap-6">
        <!-- Date filters section - Enhanced Styling -->
        <div class="p-4 rounded-lg shadow-xl flex flex-col items-start content-card">
          <h3 class="text-xl font-semibold mb-3 text-left" :style="{ color: 'var(--admin-text-color)' }">{{__('Select Date Range', 'schedula-smart-appointment-booking')}}</h3>

          <!-- Container for period selector and reset button -->
          <div class="flex flex-col lg:flex-row items-start lg:items-center gap-3 w-full mb-0">
            <!-- Main dropdown-like button for current range display and quick selects -->
            <div ref="dropdownRef" class="relative w-full sm:w-80 lg:w-96"> 
              <button 
                @click="showDropdown = !showDropdown"
                class="flex justify-between items-center w-full px-4 py-2 border rounded-lg shadow-md font-medium text-base transition-all duration-200 ease-in-out hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-input-text-color)', borderColor: 'var(--admin-input-border-color)' }"
              >
                <span class="flex items-center">
                  <i class="fas fa-calendar-alt mr-2" :style="{ color: 'var(--admin-link-indigo-bg)' }"></i>
                  {{ displayDateRange }}
                </span>
                <i :class="['fas', showDropdown ? 'fa-chevron-up' : 'fa-chevron-down', 'text-[var(--admin-card-text-color)] text-sm']"></i>
              </button>

              <!-- Dropdown Content for Quick Selects and Custom Range Option -->
              <div v-if="showDropdown" class="absolute z-10 mt-2 w-full rounded-lg shadow-xl border overflow-hidden"
                   :style="{ backgroundColor: 'var(--admin-card-bg-color)', borderColor: 'var(--admin-border-color)' }">
                <div class="py-2">
                  <button
                    @click="selectPreset('7_days'); showDropdown = false;"
                    :class="['block w-full text-left px-4 py-2 text-sm transition-colors duration-150',
                             selectedPreset === '7_days' ? 'bg-blue-700 text-white font-semibold' : 'text-[var(--admin-card-text-color)] hover:bg-[var(--admin-input-border-color)]']">
                    <i class="fas fa-calendar-week mr-2"></i> {{__('Last 7 Days', 'schedula-smart-appointment-booking')}}
                  </button>
                  <button
                    @click="selectPreset('30_days'); showDropdown = false;"
                    :class="['block w-full text-left px-4 py-2 text-sm transition-colors duration-150',
                             selectedPreset === '30_days' ? 'bg-blue-700 text-white font-semibold' : 'text-[var(--admin-card-text-color)] hover:bg-[var(--admin-input-border-color)]']">
                    <i class="fas fa-calendar-alt mr-2"></i> {{__('Last 30 Days', 'schedula-smart-appointment-booking')}}
                  </button>
                  <button
                    @click="selectPreset('current_month'); showDropdown = false;"
                    :class="['block w-full text-left px-4 py-2 text-sm transition-colors duration-150',
                             selectedPreset === 'current_month' ? 'bg-blue-700 text-white font-semibold' : 'text-[var(--admin-card-text-color)] hover:bg-[var(--admin-input-border-color)]']">
                    <i class="fas fa-calendar-check mr-2"></i> {{__('Current Month', 'schedula-smart-appointment-booking')}}
                  </button>
                  <button
                    @click="selectedPreset = 'custom'; showDropdown = false;"
                    :class="['block w-full text-left px-4 py-2 text-sm transition-colors duration-150',
                             selectedPreset === 'custom' ? 'bg-blue-700 text-white font-semibold' : 'text-[var(--admin-card-text-color)] hover:bg-[var(--admin-input-border-color)]']">
                    <i class="fas fa-sliders-h mr-2"></i> {{__('Define Period', 'schedula-smart-appointment-booking')}}
                  </button>
                </div>
              </div>
            </div>

            <!-- Reset Filters Button (positioned to the right) -->
            <button @click="resetFilters"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-md text-white bg-gray-500 hover:bg-gray-600 focus:outline-none focus:ring-3 focus:ring-offset-2 focus:ring-gray-500 transition duration-200 ease-in-out hover:shadow-lg whitespace-nowrap"
                    :style="{ color: 'var(--admin-button-secondary-text)' }">
              <i class="fas fa-redo-alt mr-2 text-sm"></i> {{__('Reset Filters', 'schedula-smart-appointment-booking')}}
            </button>
          </div>

          <!-- Custom Range Selectors (conditionally displayed as a separate block) -->
          <div v-if="selectedPreset === 'custom'" ref="customDateRangeContainer" 
               class="flex flex-col md:flex-row items-center gap-3 w-full p-4 border rounded-lg shadow-sm mt-3"
               :style="{ backgroundColor: 'var(--admin-input-bg-color)', borderColor: 'var(--admin-input-border-color)' }">
               <div class="relative w-full md:w-1/2">
                  <input
                    type="text"
                    id="customStartDate"
                    name="start"
                    ref="customStartDateInput"
                    v-model="customStartDate"
                    class="block w-full p-2.5 input-field" 
                    :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': startDateError }"
                    :placeholder="__('Start Date', 'schedula-smart-appointment-booking')"
                    :style="startDateError ? 
                      { backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-input-text-color)', borderColor: '#ef4444' } :
                      { backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-input-text-color)', borderColor: 'var(--admin-input-border-color)' }"
                     />
            
            <!-- Start Date Error Message -->
            <div v-if="startDateError" class="absolute top-full left-0 mt-1 text-xs text-red-600 bg-red-50 px-2 py-1 rounded shadow-sm border border-red-200 z-10 whitespace-nowrap">
              <i class="fas fa-exclamation-circle mr-1"></i>{{ startDateError }}
            </div>
          </div>
            
            <span class="mx-4 text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{__('to', 'schedula-smart-appointment-booking')}}</span>
            <div class="relative w-full md:w-1/2">
                <input
              type="text"
              id="customEndDate"
              name="end"
              ref="customEndDateInput"
              v-model="customEndDate"
              class="block w-full p-2.5 input-field" 
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': endDateError }"
              :placeholder="__('End Date', 'schedula-smart-appointment-booking')"
              :style="endDateError ? 
                { backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-input-text-color)', borderColor: '#ef4444' } :
                { backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-input-text-color)', borderColor: 'var(--admin-input-border-color)' }"
              />

              <!-- End Date Error Message -->
       <div v-if="endDateError" class="absolute top-full left-0 mt-1 text-xs text-red-600 bg-red-50 px-2 py-1 rounded shadow-sm border border-red-200 z-10 whitespace-nowrap">
      <i class="fas fa-exclamation-circle mr-1"></i>{{ endDateError }}
      </div>
      </div>
            
      
      <!-- Apply and Cancel buttons for custom range -->
      <div class="flex flex-col md:flex-row gap-2 w-full md:w-auto">
      <button 
      @click.prevent="applyCustomRange"
      type="button"
      class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out w-full md:w-auto text-sm disabled:opacity-50 disabled:cursor-not-allowed"
      :disabled="!!startDateError || !!endDateError || !customStartDate || !customEndDate"
    >
      {{__('Apply', 'schedula-smart-appointment-booking')}}
    </button>
    <button 
      @click.prevent="cancelCustomRange"
      type="button"
      class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg shadow-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150 ease-in-out w-full md:w-auto text-sm"
      :style="{ backgroundColor: 'var(--admin-button-third-bg)', color: 'var(--admin-button-primary-text)' }"
    >
      {{__('Cancel', 'schedula-smart-appointment-booking')}}
    </button>
      </div>
      </div>
    </div>

        <!-- Statistics cards section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Card: Accepted/Completed Appointments -->
          <StatsCard 
            :title="__('Appointments Accepted', 'schedula-smart-appointment-booking')" 
            :value="stats.accepted_completed_appointments" 
            route-path="/appointments" 
            :route-query="{ status: 'accepted' }" 
            icon="fas fa-calendar-check" 
            icon-color="green"
          >
            <template #footer>
              {{__('Total confirmed/completed', 'schedula-smart-appointment-booking')}}
            </template>
          </StatsCard>

          <!-- Card: Pending Appointments -->
          <StatsCard 
            :title="__('Appointments Pending', 'schedula-smart-appointment-booking')" 
            :value="stats.pending_appointments" 
            route-path="/appointments" 
            :route-query="{ status: 'pending' }" 
            icon="fas fa-hourglass-half" 
            icon-color="yellow"
          >
            <template #footer>
              {{__('Awaiting confirmation', 'schedula-smart-appointment-booking')}}
            </template>
          </StatsCard>

          <!-- Card: Total Appointments -->
          <StatsCard 
            :title="__('Total Appointments', 'schedula-smart-appointment-booking')" 
            :value="stats.total_appointments" 
            route-path="/appointments" 
            icon="fas fa-calendar-alt" 
            icon-color="blue"
          >
            <template #footer>
              {{__('All non-cancelled appointments', 'schedula-smart-appointment-booking')}}
            </template>
          </StatsCard>

          <!-- Card: Total Earnings -->
          <StatsCard 
            :title="__('Total Earnings', 'schedula-smart-appointment-booking')" 
            :value="formatPrice(stats.total_earnings)" 
            route-path="/payments" 
            icon="fas fa-dollar-sign" 
            icon-color="indigo"
          >
            <template #footer>
              {{__('From completed payments', 'schedula-smart-appointment-booking')}}
            </template>
          </StatsCard>
        </div>

        <!-- Chart section with flex-grow to fill remaining space -->
        <div class="flex-grow flex flex-col">
          <div class="p-6 rounded-lg shadow-md flex-1 content-card">
            <h2 class="text-2xl font-bold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{__('Appointments & Earnings Trends', 'schedula-smart-appointment-booking')}}</h2>
            <div class="h-80">
              <LineChart v-if="chartData" :data="chartData" :options="dynamicChartOptions" />
              <div v-else class="h-full flex items-center justify-center text-[var(--admin-card-text-color)]">
                {{__('No chart data available', 'schedula-smart-appointment-booking')}}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Smart Suggestions & Quick Links -->
      <div class="lg:col-span-1 flex flex-col gap-6">
        <!-- Smart Suggestions Card -->
        <SmartSuggestionsCard :pending-appointments-count="stats.pending_appointments" />

        <!-- Quick Links Card -->
        <div class="p-6 rounded-lg shadow-md content-card">
          <h3 class="text-xl font-semibold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{__('Quick Links', 'schedula-smart-appointment-booking')}}</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <router-link to="/appointments"
                         class="inline-flex items-center justify-center px-4 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-[var(--admin-link-blue-hover-bg)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
              <i class="fas fa-plus-circle mr-2"></i> {{__('Manage Appointment', 'schedula-smart-appointment-booking')}}
            </router-link>
            <router-link to="/services-categories" class="inline-flex items-center justify-center px-4 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-[var(--admin-link-green-hover-bg)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
              <i class="fas fa-cut mr-2"></i> {{__('Manage Services', 'schedula-smart-appointment-booking')}}
            </router-link>
            <router-link to="/clients" class="inline-flex items-center justify-center px-4 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-[var(--admin-link-purple-bg)] hover:bg-[var(--admin-link-purple-hover-bg)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition duration-150 ease-in-out">
              <i class="fas fa-users mr-2"></i> {{__('Manage Clients', 'schedula-smart-appointment-booking')}}
            </router-link>
            <router-link to="/staff" class="inline-flex items-center justify-center px-4 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-[var(--admin-link-orange-bg)] hover:bg-[var(--admin-link-orange-hover-bg)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition duration-150 ease-in-out">
              <i class="fas fa-user-tie mr-2"></i> {{__('Manage Staff', 'schedula-smart-appointment-booking')}}
            </router-link>
          </div>
        </div>

        <!-- Newsletter Subscription Card -->
        <div class="p-6 rounded-lg shadow-md content-card">
          <h3 class="text-xl font-semibold mb-2" :style="{ color: 'var(--admin-text-color)' }">{{__('Get More Updates', 'schedula-smart-appointment-booking')}}</h3>
          <p class="text-sm mb-4" :style="{ color: 'var(--admin-card-text-color)' }">
            {{ __("Do you want to get notified when a new component is added to Schedula? Sign up for our newsletter and you'll be among the first to find out about new features, components, versions, and tools.", 'schedula-smart-appointment-booking') }}
          </p>
          <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-grow">
              <input
                type="email"
                v-model="newsletterEmail"
                placeholder="Your email address..."
                class="w-full pr-10 pl-4 py-2 border rounded-lg shadow-sm input-field"
                :disabled="isSubscribing"
                :style="{ backgroundColor: 'var(--admin-input-bg-color)', color: 'var(--admin-input-text-color)', borderColor: 'var(--admin-input-border-color)' }"
              />
              <i class="fas fa-envelope absolute right-3 top-1/2 transform -translate-y-1/2 text-[var(--admin-input-text-muted)]"></i>
            </div>
            <button
              @click="subscribeToNewsletter"
              :disabled="isSubscribing"
              class="px-5 py-2 bg-[var(--admin-link-indigo-bg)] text-[var(--admin-link-indigo-text)] font-medium rounded-lg shadow-md hover:bg-[var(--admin-link-indigo-hover-bg)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out flex items-center justify-center"
              :class="{ 'opacity-50 cursor-not-allowed': isSubscribing }"
            >
              <svg v-if="isSubscribing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ isSubscribing ? __("Subscribing...", 'schedula-smart-appointment-booking') : __("Subscribe", 'schedula-smart-appointment-booking') }}
            </button>
          </div>
          <p v-if="subscriptionSuccessMessage" class="text-sm mt-3" :style="{ color: 'var(--admin-success-text-color)' }">{{ subscriptionSuccessMessage }}</p>
          <p v-if="subscriptionErrorMessage" class="text-sm mt-3" :style="{ color: 'var(--admin-suggestion-red-text)' }">{{ subscriptionErrorMessage }}</p>
          <p class="text-xs mt-4" :style="{ color: 'var(--admin-text-color)' }">
            {{ __("By subscribing, you agree with Schedula's", 'schedula-smart-appointment-booking') }}
            <a href="https://vertimcoders.com/terms-of-service/" class="hover:underline" :style="{ color: 'var(--admin-link-indigo-bg)' }">{{ __("Terms of Service", 'schedula-smart-appointment-booking') }}</a>,
            <a href="https://vertimcoders.com/privacy-policy/" class="hover:underline" :style="{ color: 'var(--admin-link-indigo-bg)' }">{{ __("Privacy Policy", 'schedula-smart-appointment-booking') }}</a>,
            and <a href="https://vertimcoders.com/refund-policy/" class="hover:underline" :style="{ color: 'var(--admin-link-indigo-bg)' }">{{ __("Refund Policy", 'schedula-smart-appointment-booking') }}</a>.
          </p>
        </div>
      </div>
    </div>
  </div>

  
</template>

<script setup>
import { ref, onMounted, computed, watch, nextTick } from 'vue';
import StatsCard from '../components/dashboard/StatsCard.vue'; 
import LineChart from '../components/charts/LineChart.vue'; 
import SmartSuggestionsCard from '../components/dashboard/SmartSuggestionsCard.vue'; 

import { appointmentsApi, newsletterApi } from '../api.js';
import { useGlobalSettings } from '@/admin/composables/useGlobalSettings.js';
import { useAdminAppearance } from '@/admin/composables/useAdminAppearance.js'; 

import { Datepicker } from 'flowbite-datepicker';
import {__} from '@wordpress/i18n';

const { generalSettings, formatPrice, fetchGlobalSettings } = useGlobalSettings();
const { appearanceSettings, adminCustomStyles } = useAdminAppearance(); 



 

// EXISTING VARIABLES (keep these as they are)
const stats = ref({
  accepted_completed_appointments: 0,
  pending_appointments: 0,
  total_appointments: 0,
  total_earnings: 0,
});

const selectedPreset = ref(null);
const customStartDate = ref('');
const customEndDate = ref('');
const showDropdown = ref(false);

const loading = ref(true);
const error = ref(null);
const chartData = ref(null);

const showDateErrorPopup = ref(false);
const dateErrorMessage = ref('');
let dateErrorTimeout = null;

const customStartDateInput = ref(null);
const customEndDateInput = ref(null);

let startDatePickerInstance = null;
let endDatePickerInstance = null;

const newsletterEmail = ref('');
const isSubscribing = ref(false);
const subscriptionSuccessMessage = ref('');
const subscriptionErrorMessage = ref('');

// ADD THESE NEW VALIDATION VARIABLES HERE
const startDateError = ref('');
const endDateError = ref('');
let validationTimeout = null;

// EXISTING COMPUTED PROPERTIES (keep these)
const dynamicChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    y: {
      display: true,
      beginAtZero: true,
      suggestedMax: Math.max(...(chartData.value?.datasets[0]?.data || [0]), ...(chartData.value?.datasets[1]?.data || [0])) * 1.2 || 10
    },
    x: {
      display: true,
    },
  },
  plugins: {
    legend: {
      display: true,
      position: 'top',
    },
    tooltip: {
      mode: 'index',
      intersect: false,
      callbacks: {
        label: function(context) {
          let label = context.dataset.label || '';
          if (label) {
            label += ': ';
          }
          if (context.datasetIndex === 1) {
            label += formatPrice(context.parsed.y);
          } else {
            label += context.parsed.y;
          }
          return label;
        }
      }
    }
  },
})); 

const displayDateRange = computed(() => {
  const start = currentStartDate.value;
  const end = currentEndDate.value;

  if (selectedPreset.value === null) {
    return 'Pick a period';
  }
  if (selectedPreset.value === __('7_days', 'schedula-smart-appointment-booking')) return __('Last 7 Days', 'schedula-smart-appointment-booking');
  if (selectedPreset.value === __('30_days', 'schedula-smart-appointment-booking')) return __('Last 30 Days', 'schedula-smart-appointment-booking');
  if (selectedPreset.value === __('current_month', 'schedula-smart-appointment-booking')) return __('Current Month', 'schedula-smart-appointment-booking');
  
  if (start && end) {
    const startDateObj = new Date(start + 'T00:00:00');
    const endDateObj = new Date(end + 'T00:00:00');
    
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return `${startDateObj.toLocaleDateString('en-US', options)} - ${endDateObj.toLocaleDateString('en-US', options)}`;
  }
  return 'Pick a period';
});

const currentStartDate = computed(() => {
  const today = new Date();
  let date = new Date(today);

  if (selectedPreset.value === null) return '';

  switch (selectedPreset.value) {
    case __('7_days', 'schedula-smart-appointment-booking'):
      date.setDate(today.getDate() - 6);
      return formatDate(date);
    case __('30_days', 'schedula-smart-appointment-booking'):
      date.setDate(today.getDate() - 29);
      return formatDate(date);
    case __('current_month', 'schedula-smart-appointment-booking'):
      date.setDate(1);
      return formatDate(date);
    case __('custom', 'schedula-smart-appointment-booking'):
      return customStartDate.value;
    default:
      return '';
  }
});

const currentEndDate = computed(() => {
  const today = new Date();
  
  if (selectedPreset.value === null) return '';

  switch (selectedPreset.value) {
    case __('7_days', 'schedula-smart-appointment-booking'):
    case __('30_days', 'schedula-smart-appointment-booking'):
    case __('current_month', 'schedula-smart-appointment-booking'):
      return formatDate(today);
    case __('custom', 'schedula-smart-appointment-booking'):
      return customEndDate.value;
    default:
      return '';
  }
});

// EXISTING FUNCTIONS (keep these as they are)
const formatDate = (date) => {
  if (!date) return '';
  const d = new Date(date);
  if (isNaN(d.getTime())) {
    console.warn('Invalid date provided to formatDate:', date);
    return '';
  }
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const formatDateForDisplay = (date) => {
  if (!date) return '';
  const d = new Date(date + 'T00:00:00');
  if (isNaN(d.getTime())) return '';
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return d.toLocaleDateString('en-US', options);
};

const generateChartData = (chartDataFromApi) => {
    if (!chartDataFromApi || !chartDataFromApi.labels || !chartDataFromApi.appointments || !chartDataFromApi.earnings) {
        chartData.value = null;
        return;
    }

    chartData.value = {
        labels: chartDataFromApi.labels,
        datasets: [
            {
                label: __('Appointments', 'schedula-smart-appointment-booking'),
                backgroundColor: 'rgba(8, 26, 48, 0.2)',
                borderColor: '#081a30',
                data: chartDataFromApi.appointments,
                tension: 0.4,
                fill: true,
            },
            {
                label: `${__('Earnings', 'schedula-smart-appointment-booking')} (${generalSettings.value.currencySymbol})`,
                backgroundColor: 'rgba(255, 159, 64, 0.2)',
                borderColor: 'rgb(255, 159, 64)',
                data: chartDataFromApi.earnings,
                tension: 0.4,
                fill: true,
            }
        ]
    };
};

const fetchDashboardSummary = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await appointmentsApi.getDashboardSummary(currentStartDate.value, currentEndDate.value);
    const data = response.data;
    
    stats.value.accepted_completed_appointments = data.accepted_completed_appointments;
    stats.value.pending_appointments = data.pending_appointments;
    stats.value.total_appointments = data.total_appointments;
    stats.value.total_earnings = data.total_earnings;

    generateChartData(data.chart_data);

  } catch (err) {
    error.value = err.response?.data?.message || err.message || 'An error occurred while fetching dashboard summary.';
    console.error('Error fetching dashboard summary:', err);
  } finally {
    loading.value = false;
  }
};

// ADD THE NEW VALIDATION FUNCTION HERE
const validateDateInputs = () => {
  startDateError.value = '';
  endDateError.value = '';
  
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  // Validate start date
  if (!customStartDate.value) {
    startDateError.value = __('Start date is required', 'schedula-smart-appointment-booking');
    return false;
  }
  
  const startDate = new Date(customStartDate.value + 'T00:00:00');
  if (isNaN(startDate.getTime())) {
    startDateError.value = __('Invalid start date format', 'schedula-smart-appointment-booking');
    return false;
  }
  
  // Check if start date is too far in the past (2 years)
  const twoYearsAgo = new Date();
  twoYearsAgo.setFullYear(today.getFullYear() - 2);
  if (startDate < twoYearsAgo) {
    startDateError.value = __('Start date cannot be more than 2 years ago', 'schedula-smart-appointment-booking');
    return false;
  }
  
  // Check if start date is too far in the future (1 year)
  const oneYearFromNow = new Date();
  oneYearFromNow.setFullYear(today.getFullYear() + 1);
  if (startDate > oneYearFromNow) {
    startDateError.value = __('Start date cannot be more than 1 year in the future', 'schedula-smart-appointment-booking');
    return false;
  }
  
  // Validate end date
  if (!customEndDate.value) {
    endDateError.value = __('End date is required', 'schedula-smart-appointment-booking');
    return false;
  }
  
  const endDate = new Date(customEndDate.value + 'T00:00:00');
  if (isNaN(endDate.getTime())) {
    endDateError.value = __('Invalid end date format', 'schedula-smart-appointment-booking');
    return false;
  }
  
  // Check if end date is too far in the future
  if (endDate > oneYearFromNow) {
    endDateError.value = __('End date cannot be more than 1 year in the future', 'schedula-smart-appointment-booking');
    return false;
  }
  
  // Check if start date is after end date
  if (startDate > endDate) {
    startDateError.value = __('Start date cannot be after end date', 'schedula-smart-appointment-booking');
    endDateError.value = __('End date cannot be before start date', 'schedula-smart-appointment-booking');
    return false;
  }
  
  // Check if the date range is too long (1 year)
  const maxRangeMs = 365 * 24 * 60 * 60 * 1000;
  if (endDate - startDate > maxRangeMs) {
    startDateError.value = __('Date range cannot exceed 1 year', 'schedula-smart-appointment-booking');
    endDateError.value = __('Date range cannot exceed 1 year', 'schedula-smart-appointment-booking');
    return false;
  }
  
  return true;
};

const selectPreset = (preset) => {
  selectedPreset.value = preset;
  customStartDate.value = '';
  customEndDate.value = '';
  // Clear validation errors when switching presets
  startDateError.value = '';
  endDateError.value = '';
  showDateErrorPopup.value = false;
  clearTimeout(dateErrorTimeout);
  fetchDashboardSummary();
};

const setupDatepickers = () => {
    if (customStartDateInput.value && !startDatePickerInstance) {
        startDatePickerInstance = new Datepicker(customStartDateInput.value, {
            format: 'yyyy-mm-dd',
            autohide: true,
            todayHighlight: true,
        });
        if (customStartDate.value) {
            startDatePickerInstance.setDate(new Date(customStartDate.value + 'T00:00:00'));
        }
        customStartDateInput.value.addEventListener('changeDate', (e) => {
            customStartDate.value = formatDate(e.detail.date);
        });
    }

    if (customEndDateInput.value && !endDatePickerInstance) {
        endDatePickerInstance = new Datepicker(customEndDateInput.value, {
            format: 'yyyy-mm-dd',
            autohide: true,
            todayHighlight: true,
        });
        if (customEndDate.value) {
            endDatePickerInstance.setDate(new Date(customEndDate.value + 'T00:00:00'));
        }
        customEndDateInput.value.addEventListener('changeDate', (e) => {
            customEndDate.value = formatDate(e.detail.date);
        });
    }
};

const destroyDatepickers = () => {
    if (startDatePickerInstance) {
        startDatePickerInstance.destroy();
        startDatePickerInstance = null;
    }
    if (endDatePickerInstance) {
        endDatePickerInstance.destroy();
        endDatePickerInstance = null;
    }
};

// REVISED applyCustomRange to handle re-initialization after loading
const applyCustomRange = async () => {
    showDateErrorPopup.value = false;
    clearTimeout(dateErrorTimeout);

    if (!validateDateInputs()) {
        return;
    }

    selectedPreset.value = 'custom';
    
    // Destroy pickers before fetch, as loading state will remove inputs from DOM
    destroyDatepickers();
    
    await fetchDashboardSummary();
    
    // After fetch, loading is false, and inputs are back in the DOM.
    // We wait for the next DOM update cycle to ensure refs are available, then re-initialize pickers.
    await nextTick();
    setupDatepickers();
};


const resetFilters = () => {
  selectedPreset.value = null;
  customStartDate.value = '';
  customEndDate.value = '';
  // Clear validation errors
  startDateError.value = '';
  endDateError.value = '';
  showDateErrorPopup.value = false;
  clearTimeout(dateErrorTimeout);
  clearTimeout(validationTimeout);
  
  destroyDatepickers();
  fetchDashboardSummary();
};

const cancelCustomRange = () => {
  selectedPreset.value = null;
  customStartDate.value = '';
  customEndDate.value = '';
  startDateError.value = '';
  endDateError.value = '';
  showDropdown.value = false;
  showDateErrorPopup.value = false;
  clearTimeout(dateErrorTimeout);
  clearTimeout(validationTimeout);
  
  destroyDatepickers();
  
  fetchDashboardSummary();
};

// Newsletter function (keep this as is)
const subscribeToNewsletter = async () => {
  if (!newsletterEmail.value) return;
  
  isSubscribing.value = true;
  subscriptionSuccessMessage.value = '';
  subscriptionErrorMessage.value = '';
  
  try {
    await newsletterApi.subscribe(newsletterEmail.value);
    subscriptionSuccessMessage.value = __('Successfully subscribed to newsletter!', 'schedula-smart-appointment-booking');
    newsletterEmail.value = '';
  } catch (err) {
    subscriptionErrorMessage.value = err.response?.data?.message || __('Failed to subscribe. Please try again.', 'schedula-smart-appointment-booking');
  } finally {
    isSubscribing.value = false;
  }
};

const dropdownRef = ref(null);

// EXISTING onMounted (keep this)
onMounted(() => {
  fetchGlobalSettings();
  document.addEventListener('click', (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
      showDropdown.value = false;
    }
  });

  fetchDashboardSummary();
});

// REVISED watch for selectedPreset to use helper functions
watch(selectedPreset, async (newPreset) => {
    if (newPreset === 'custom') {
        await nextTick();
        setupDatepickers();
    } else {
        destroyDatepickers();
    }
}, { immediate: true });

// EXISTING watch for custom dates (keep this)
watch([customStartDate, customEndDate], ([newStart, newEnd]) => {
  if (selectedPreset.value === 'custom') {
    if (startDatePickerInstance && newStart) {
      const currentPickerDate = formatDate(startDatePickerInstance.getDate());
      if (currentPickerDate !== newStart) {
        startDatePickerInstance.setDate(new Date(newStart + 'T00:00:00'));
      }
    }
    if (endDatePickerInstance && newEnd) {
      const currentPickerDate = formatDate(endDatePickerInstance.getDate());
      if (currentPickerDate !== newEnd) {
        endDatePickerInstance.setDate(new Date(newEnd + 'T00:00:00'));
      }
    }
  }
});

// ADD THIS NEW WATCH FOR REAL-TIME VALIDATION
watch([customStartDate, customEndDate], () => {
  if (selectedPreset.value === 'custom' && (customStartDate.value || customEndDate.value)) {
    // Clear errors first
    startDateError.value = '';
    endDateError.value = '';
    
    // Only validate if both dates are provided
    if (customStartDate.value && customEndDate.value) {
      // Add a small delay to avoid excessive validation while typing
      clearTimeout(validationTimeout);
      validationTimeout = setTimeout(() => {
        validateDateInputs();
      }, 500);
    }
  }
});


</script>

<style>
/* Global styles for admin theme variables are assumed to be loaded from global-admin.css */

/* Flowbite Datepicker styles - Reverted to original hardcoded colors as per user request */
.datepicker-dropdown,
.datepicker-picker,
.datepicker-panel {
  background-color: #ffffff !important;
  border-color: #e5e7eb !important;
  box-shadow: 0 10px 15px -3px rgba(240, 233, 233, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
}

.datepicker-header,
.datepicker-footer {
  background-color: #ffffff !important;
  border-bottom: 1px solid #e5e7eb !important;
  border-top: 1px solid #e5e7eb !important;
}

.datepicker-controls .datepicker-title,
.datepicker-controls .datepicker-view-selected,
.datepicker-controls .button,
.datepicker-controls .button:hover,
.datepicker-grid .datepicker-cell,
.datepicker-grid .datepicker-cell.focused,
.datepicker-grid .datepicker-cell.selected,
.datepicker-grid .datepicker-cell.range-start,
.datepicker-grid .datepicker-cell.range-end,
.datepicker-grid .datepicker-cell.range-middle,
.datepicker-grid .datepicker-cell.range-start.selected,
.datepicker-grid .datepicker-cell.range-end.selected {
  color: #374151 !important;
}

.datepicker-grid .datepicker-cell.selected,
.datepicker-grid .datepicker-cell.range-start,
.datepicker-grid .datepicker-cell.range-end {
  background-color: #4f46e5 !important;
  color: #ffffff !important;
}

.datepicker-grid .datepicker-cell.range-middle {
  background-color: #c7d2fe !important;
  color: #374151 !important;
}

.datepicker-grid .datepicker-cell:hover:not(.selected):not(.range-middle) {
  background-color: #e0e7ff !important;
}

/* Hide native date picker indicator */
input[type="text"]::-webkit-calendar-picker-indicator {
  display: none;
}
input[type="text"]::-moz-calendar-picker-indicator {
  display: none;
}
input[type="text"]::-ms-calendar-picker-indicator {
  display: none;
}

* Enhanced error validation styles */
.input-field.border-red-500 {
  border-color: #ef4444 !important;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
}

.input-field.border-red-500:focus {
  border-color: #ef4444 !important;
  ring-color: #ef4444 !important;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2) !important;
}

/* Error message styling */
.absolute.top-full {
  z-index: 20;
}

/* Enhanced animation for error messages */
.fade-in-error {
  animation: fadeInError 0.3s ease-in-out;
}

@keyframes fadeInError {
  from {
    opacity: 0;
    transform: translateY(-5px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Button disabled state enhancement */
button:disabled {
  opacity: 0.6 !important;
  cursor: not-allowed !important;
  transform: none !important;
}

button:disabled:hover {
  background-color: inherit !important;
  box-shadow: inherit !important;
}

/* Dark mode error message styles */
.dark .text-red-600 {
  color: #f87171 !important;
}

.dark .bg-red-50 {
  background-color: rgba(239, 68, 68, 0.1) !important;
  color: #f87171 !important;
}

.dark .border-red-200 {
  border-color: rgba(239, 68, 68, 0.3) !important;
}

</style>
