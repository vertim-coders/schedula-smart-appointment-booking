<template>
  <div :class="{'dark-mode': appearanceSettings.adminDarkModeEnabled}" :style="adminCustomStyles">
    <AppearanceHomePage v-if="currentPage === 'home'" @navigate="navigateTo" />
    <ReservationFormSettings v-if="currentPage === 'reservation-form'" @back="navigateTo('home')" />
    <ServiceFormReservation v-if="currentPage === 'service-forms'" @back="navigateTo('home')" @back-to-home="navigateTo('home')" />
    <ChangeFormDesign v-if="currentPage === 'form-design'" @back="navigateTo('home')" />
    <ServiceFormCustomization v-if="currentPage === 'service-form-customization'" @back-to-forms-list="navigateTo('service-forms')" />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import AppearanceHomePage from '../components/appearance/AppearanceHomePage.vue';
import ReservationFormSettings from '../components/appearance/ReservationFormSettings.vue';
import ServiceFormReservation from '../components/appearance/ServiceFormReservation.vue';
import ChangeFormDesign from '../components/appearance/ChangeFormDesign.vue';
import ServiceFormCustomization from '../components/appearance/ServiceFormCustomization.vue';
import { useAdminAppearance } from '../composables/useAdminAppearance';

const { appearanceSettings, adminCustomStyles } = useAdminAppearance();

const route = useRoute();

const currentPage = ref('home');

const navigateTo = (page) => {
  currentPage.value = page;
};

watch(() => route.query.view, (newView) => {
  if (newView) {
    currentPage.value = newView;
  }
}, { immediate: true });
</script>

