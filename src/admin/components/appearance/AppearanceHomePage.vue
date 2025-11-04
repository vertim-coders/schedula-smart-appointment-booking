<template>
  <div class="p-8 min-h-screen" :class="{'dark-mode': appearanceSettings.adminDarkModeEnabled}" :style="adminCustomStyles">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-4xl font-bold text-bold mb-6" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Customize Your Forms', 'schedula-smart-appointment-booking') }}</h1>
      <p class="mb-8 max-w-3xl" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Select a form type to customize its appearance and settings. Make your forms match your brand and improve user experience.', 'schedula-smart-appointment-booking') }}</p>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Customize Reservation Form Card -->
        <div 
          @click="$emit('navigate', 'reservation-form')" 
          class="group rounded-lg shadow-sm border overflow-hidden hover:shadow-md transition-all duration-300 cursor-pointer hover:border-blue-500" :style="{ backgroundColor: 'var(--admin-card-bg-color)', borderColor: 'var(--admin-border-color)' }"
        >
          <div class="h-48 overflow-hidden flex justify-center items-center">
            <img 
              :src="reservationFormImage" 
              alt="Reservation Form" 
              class="w-[80%] h-[80%] object-cover transition-transform duration-500 group-hover:scale-105"
            />
          </div>
          <div class="p-6">
            <h2 class="text-xl font-semibold mb-2 group-hover:text-blue-600 transition-colors" :style="{ color: 'var(--admin-card-text-color)' }">
              {{ __('Customize Reservation Form', 'schedula-smart-appointment-booking') }}
            </h2>
            <p class="text-sm leading-relaxed" :style="{ color: 'var(--admin-card-text-color)' }">
              {{ __("Modify the main reservation form's appearance, fields, and layout to match your business needs.", 'schedula-smart-appointment-booking') }}
            </p>
          </div>
        </div>

        <!-- Service Forms Card -->
        <div 
          @click="navigateToServiceFormReservation" 
          class="group rounded-lg shadow-sm border overflow-hidden hover:shadow-md transition-all duration-300 cursor-pointer hover:border-blue-500" :style="{ backgroundColor: 'var(--admin-card-bg-color)', borderColor: 'var(--admin-border-color)' }"
        >
          <div class="h-48 overflow-hidden flex justify-center items-center">
            <img 
              :src="serviceFormsImage" 
              alt="Service Forms" 
              class="w-[80%] h-[80%] object-cover transition-transform duration-500 group-hover:scale-105"
            />
          </div>
          <div class="p-6">
            <h2 class="text-xl font-semibold mb-2 group-hover:text-blue-600 transition-colors" :style="{ color: 'var(--admin-card-text-color)' }">
              {{ __('Customize Service Forms', 'schedula-smart-appointment-booking') }}
            </h2>
            <p class="text-sm leading-relaxed" :style="{ color: 'var(--admin-card-text-color)' }">
              {{ __('Create and manage forms for specific services or categories with custom fields and validations.', 'schedula-smart-appointment-booking') }}
            </p>
          </div>
        </div>

        <!-- Change Form Design Card 
        <div 
          @click="$emit('navigate', 'form-design')" 
          class="group rounded-lg shadow-sm border overflow-hidden hover:shadow-md transition-all duration-300 cursor-pointer hover:border-blue-500" :style="{ backgroundColor: 'var(--admin-card-bg-color)', borderColor: 'var(--admin-border-color)' }"
        >
          <div class="h-48 bg-gray-100 overflow-hidden">
            <img 
              :src="formDesignImage" 
              alt="Form Design" 
              class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
            />
          </div>
          
          <div class="p-6">
            <h2 class="text-xl font-semibold mb-2 group-hover:text-blue-600 transition-colors" :style="{ color: 'var(--admin-card-text-color)' }">
              {{ __('Change Form Design', 'schedula-smart-appointment-booking') }}
            </h2>
            <p class="text-sm leading-relaxed" :style="{ color: 'var(--admin-card-text-color)' }">
              {{ __('Select from pre-designed templates to change the look and feel of your forms instantly.', 'schedula-smart-appointment-booking') }}
            </p>
          </div>
        </div>
      -->
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAdminAppearance } from '../../composables/useAdminAppearance';
import { __ } from '@wordpress/i18n';

const { appearanceSettings, adminCustomStyles } = useAdminAppearance();
const router = useRouter();

const navigateToServiceFormReservation = () => {
  router.push({ name: 'ServiceFormReservation' });
};

// Import images
import reservationFormImage from '@/assets/images/reservationform.png';
import serviceFormsImage from '@/assets/images/serviceform.png';
//import formDesignImage from '@/assets/images/designchange.jpg';

// Remove the navigate emit since we're using router directly
</script>

<style scoped>
h1 {
  font-weight: bold;
  font-size: 2.0rem;
}
</style>
