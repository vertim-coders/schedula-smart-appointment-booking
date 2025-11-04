<template>
  <div class="schedula-app relative w-full" :style="adminCustomStyles" :class="{'dark-mode': appearanceSettings.adminDarkModeEnabled, 'appearance-page': isAppearancePage}">
    <HeaderPage />  
    <main class="schedula-main">
      <router-view></router-view>      
    </main>
    
    <!-- Add ToastContainer at the end -->
    <ToastContainer />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import HeaderPage from './components/header/HeaderPage.vue';
import ToastContainer from './components/shared/ToastContainer.vue';
import './components/common/BaseFormStyles.vue';
import { useAdminAppearance } from '@/admin/composables/useAdminAppearance.js';

const { appearanceSettings, adminCustomStyles } = useAdminAppearance();

const route = useRoute();
const isAppearancePage = ref(false);

watch(() => route.path, (newPath) => {
  isAppearancePage.value = newPath.startsWith('/appearance');
}, { immediate: true });
</script>

<style scoped>
.appearance-page {
  /* Add any styles to disable or override the admin styles on the appearance page */
}
</style>