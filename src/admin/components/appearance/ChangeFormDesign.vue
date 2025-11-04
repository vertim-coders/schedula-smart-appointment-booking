<template>
  <div class="p-8" :class="{'dark-mode': appearanceSettings.adminDarkModeEnabled}" :style="adminCustomStyles">
    <h1 class="text-3xl font-bold mb-6" :style="{ color: 'var(--admin-text-color)' }">{{ __('Change Form Design', 'schedula-smart-appointment-booking') }}</h1>
    <p :style="{ color: 'var(--admin-card-text-color)' }">{{ __('This feature is coming soon. You will be able to select from pre-designed templates to change the look and feel of your forms here.', 'schedula-smart-appointment-booking') }}</p>
  </div>
</template>

<script setup>
import { useAdminAppearance } from '../../composables/useAdminAppearance';
import { __ } from '@wordpress/i18n';

const { appearanceSettings, adminCustomStyles } = useAdminAppearance();
</script>
