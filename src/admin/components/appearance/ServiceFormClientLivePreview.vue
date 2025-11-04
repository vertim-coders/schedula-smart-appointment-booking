<template>
  <div class="preview-wrapper w-full h-full">
    <ServiceFormClient 
      :preview-settings="previewSettings" 
      :preview-step="previewStep"
      :service-id="serviceId"
      :category-id="categoryId"
      :staff-id="staffId"
      :is-preview="isPreview"
      @edit-label="$emit('edit-label', $event)"
    />
  </div>
</template>

<script setup>
import ServiceFormClient from '@/admin/components/form/ServiceFormClient.vue';

defineProps({
  previewSettings: { type: Object, required: true },
  previewStep: { type: [Number, String], required: true },
  serviceId: { type: [Number, String], default: null },
  categoryId: { type: [Number, String], default: null },
  staffId: { type: [Number, String], default: null },
  isPreview: { type: Boolean, default: false },
});

defineEmits(['edit-label']);
</script>

<style scoped>
.preview-wrapper {
  /* Ensure this wrapper takes full height and width of its parent */
  width: 100%;
  height: 100%;
  /* You can add any specific preview styling here, e.g., borders or background */
  border-radius: 0.75rem; /* Rounded corners for the preview */
  overflow: hidden; /* Hide any overflow from the child component */
}
</style>
