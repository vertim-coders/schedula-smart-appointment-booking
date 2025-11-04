<template>
  <div class="p-3 rounded-lg shadow-md flex flex-col content-card">
    <h3 class="text-base font-semibold text-gray-800 mb-2">{{ __('Smart Suggestions', 'schedula-smart-appointment-booking') }}</h3>
    <div class="space-y-2 flex-grow">
      <!-- Suggestion 1: Next Appointment -->
      <div class="p-2 rounded-lg flex items-start space-x-2" :style="{ backgroundColor: 'var(--admin-suggestion-indigo-bg)' }">
        <i class="fas fa-clock text-base mt-0.5" :style="{ color: 'var(--admin-suggestion-indigo-text)' }"></i>
        <div>
          <p class="font-medium text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Your next appointment:', 'schedula-smart-appointment-booking') }}</p>
          <p v-if="nextAppointment" class="text-xs" :style="{ color: 'var(--admin-card-text-color)' }">
            <span class="font-semibold">{{ nextAppointment.customer_full_name }}</span> {{ __('for', 'schedula-smart-appointment-booking') }} 
            <span class="font-semibold">{{ nextAppointment.service_title }}</span> {{ __('with', 'schedula-smart-appointment-booking') }} 
            <span class="font-semibold">{{ nextAppointment.staff_name }}</span> {{ __('on', 'schedula-smart-appointment-booking') }} 
            <span class="font-semibold">{{ formatDateTime(nextAppointment.start_datetime, 'date') }}</span> {{ __('at', 'schedula-smart-appointment-booking') }} 
            <span class="font-semibold">{{ formatDateTime(nextAppointment.start_datetime, 'time') }}</span>.
            <span v-if="timeUntilNextAppointment !== 'passed'">({{ __('in', 'schedula-smart-appointment-booking') }} {{ timeUntilNextAppointment }})</span>
            <span v-else>({{ __('passed', 'schedula-smart-appointment-booking') }})</span>
          </p>
          <p v-else class="text-xs" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('No upcoming appointments found.', 'schedula-smart-appointment-booking') }}</p>
        </div>
      </div>

      <!-- Suggestion 2: Busy Days with Busiest Times and Day of Week -->
      <div class="p-2 rounded-lg flex items-start space-x-2" :style="{ backgroundColor: 'var(--admin-suggestion-yellow-bg)' }">
        <i class="fas fa-calendar-alt text-base mt-0.5" :style="{ color: 'var(--admin-suggestion-yellow-text)' }"></i>
        <div>
          <p class="font-medium text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Appointment Load:', 'schedula-smart-appointment-booking') }}</p>
          <div v-if="busiestDaysWithTimes.length > 0" class="text-xs" :style="{ color: 'var(--admin-card-text-color)' }">
            <span class="font-semibold">{{ __('Top 3 Busiest Days (next 30 days):', 'schedula-smart-appointment-booking') }}</span>
            <ul class="list-disc list-inside mt-0.5 text-xs">
              <li v-for="day in busiestDaysWithTimes" :key="day.date">
                <span class="font-semibold">{{ formatDateTime(day.date, 'dayOfWeek') }}</span>, 
                {{ formatDateTime(day.date, 'date') }}: 
                <span class="font-semibold text-red-700">
                  {{ day.busiest_time_slot ? day.busiest_time_slot : __('N/A', 'schedula-smart-appointment-booking') }} 
                  ({{ day.total_count }} {{ __('appointments', 'schedula-smart-appointment-booking') }})
                </span>
              </li>
            </ul>
          </div>
          <div v-else class="text-xs" :style="{ color: 'var(--admin-card-text-color)' }"> 
            <span class="font-semibold">{{ __('Low-charge days:', 'schedula-smart-appointment-booking') }}</span>
            <ul class="list-disc list-inside mt-0.5 text-xs">
              <li>{{ __('Wednesday 10h', 'schedula-smart-appointment-booking') }}</li>
              <li>{{ __('Thursday 14h', 'schedula-smart-appointment-booking') }}</li>
              <li>{{ __('Friday 9h', 'schedula-smart-appointment-booking') }}</li>
            </ul>
            <p class="text-xs mt-0.5" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Consider promoting services on these days.', 'schedula-smart-appointment-booking') }}</p>
          </div>
        </div>
      </div>

      <!-- Suggestion 3: Unconfirmed Appointments -->
      <div class="p-2 rounded-lg flex items-start space-x-2" :style="{ backgroundColor: 'var(--admin-suggestion-red-bg)' }">
        <i class="fas fa-exclamation-triangle text-base mt-0.5" :style="{ color: 'var(--admin-suggestion-red-text)' }"></i>
        <div>
          <p class="font-medium text-sm" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Action Required:', 'schedula-smart-appointment-booking') }}</p>
          <p v-if="unconfirmedAppointments > 0" class="text-xs" :style="{ color: 'var(--admin-card-text-color)' }">
            {{ __('You have', 'schedula-smart-appointment-booking') }} <span class="font-semibold text-red-700">{{ unconfirmedAppointments }} {{ __('pending appointments', 'schedula-smart-appointment-booking') }}</span>.
            <router-link to="/appointments?status=pending" class="text-blue-600 hover:underline">{{ __('Review them now', 'schedula-smart-appointment-booking') }}</router-link>.
          </p>
          <p v-else class="text-xs" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('All appointments are confirmed or completed!', 'schedula-smart-appointment-booking') }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { appointmentsApi } from '../../api.js'; // Import your API client
import { __ } from '@wordpress/i18n';

const nextAppointment = ref(null);
const busiestDaysWithTimes = ref([]);
const unconfirmedAppointments = ref(0);
const currentTime = ref(new Date());
let timeUpdateInterval = null;
let appointmentCheckInterval = null;

const props = defineProps({
  pendingAppointmentsCount: {
    type: Number,
    default: 0
  }
});

watch(() => props.pendingAppointmentsCount, (newVal) => {
  unconfirmedAppointments.value = newVal;
}, { immediate: true });

// Function to fetch the next upcoming appointment
const fetchNextUpcomingAppointment = async () => {
  try {
    const response = await appointmentsApi.getNextUpcomingAppointment();
    nextAppointment.value = response.data;
  } catch (err) {
    console.error('Error fetching next upcoming appointment:', err);
    nextAppointment.value = null;
  }
};

// Function to fetch appointment load analysis (top 3 busiest days with times)
const fetchAppointmentLoadAnalysis = async () => {
  try {
    const response = await appointmentsApi.getAppointmentLoadAnalysis();
    busiestDaysWithTimes.value = response.data.busiest_days_with_times;
  } catch (err) {
    console.error('Error fetching appointment load analysis:', err);
    busiestDaysWithTimes.value = [];
  }
};

const formatDateTime = (datetimeString, type) => {
  if (!datetimeString) return '';
  const date = new Date(datetimeString.replace(' ', 'T'));
  if (isNaN(date.getTime())) return '';

  if (type === 'date') {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString(undefined, options);
  } else if (type === 'time') {
    const options = { hour: '2-digit', minute: '2-digit', hour12: false };
    return date.toLocaleTimeString([], options);
  } else if (type === 'dayOfWeek') {
    const options = { weekday: 'long' };
    return date.toLocaleDateString(undefined, options);
  }
  return '';
};

const timeUntilNextAppointment = computed(() => {
    if (!nextAppointment.value) return '';
    const targetDate = new Date(nextAppointment.value.start_datetime.replace(' ', 'T'));
    const diffMs = targetDate.getTime() - currentTime.value.getTime();

    if (diffMs < 0) return 'passed';

    const diffSeconds = Math.floor(diffMs / 1000);
    const minutes = Math.floor(diffSeconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (days > 0) return `${days} day(s) and ${hours % 24} hour(s)`;
    if (hours > 0) return `${hours} hour(s) and ${minutes % 60} minute(s)`;
    if (minutes > 0) return `${minutes} minute(s)`;
    return `${diffSeconds} second(s)`;
});

onMounted(() => {
  fetchNextUpcomingAppointment();
  fetchAppointmentLoadAnalysis();

  timeUpdateInterval = setInterval(() => {
    currentTime.value = new Date();
  }, 1000);

  appointmentCheckInterval = setInterval(() => {
    if (nextAppointment.value) {
      const appointmentTime = new Date(nextAppointment.value.start_datetime.replace(' ', 'T'));
      if (appointmentTime < new Date()) {
        fetchNextUpcomingAppointment();
      }
    }
  }, 60000);
});

onUnmounted(() => {
  clearInterval(timeUpdateInterval);
  clearInterval(appointmentCheckInterval);
});

</script>

<style scoped>
/* No specific styles needed beyond Tailwind */
</style>
