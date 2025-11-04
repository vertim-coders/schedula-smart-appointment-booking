<template>
  <div class="schedula-live-preview-container">
    <ClientReservationForm 
      :preview-settings="settings" 
      :preview-step="previewStep" 
      @edit-label="$emit('edit-label', $event)"
      :service-id="serviceId"
      :category-id="categoryId"
      :staff-id="staffId"
    />
  </div>
</template>

<script setup>
import ClientReservationForm from '@/admin/components/form/ClientReservationForm.vue';

defineProps({
  settings: { type: Object, required: true },
  previewStep: { type: Number, required: true },
  serviceId: { type: [Number, String], default: null },
  categoryId: { type: [Number, String], default: null },
  staffId: { type: [Number, String], default: null },
});

defineEmits(['edit-label']);
</script>

<style scoped>
.schedula-live-preview-container {
  width: 100%;
  height: 100%;
  background: #fff;
  border-radius: 0.75rem; /* Corresponds to rounded-xl */
  font-size: 16px; /* Set a base font size for rem calculation */
}
</style>