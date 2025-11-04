<template>
  <div>
    <label v-if="label" :for="id" class="block text-sm font-medium mb-1">
      <i v-if="icon" :class="icon" class="mr-1" :style="{ color: 'var(--admin-input-text-muted)' }"></i>{{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <div class="standardized-input-container">
      <textarea
        :id="id"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :rows="rows"
        :class="['standardized-input resize-vertical', customClass]"
      ></textarea>
    </div>
    <p v-if="validationMessage" class="text-xs text-red-600 mt-1">{{ validationMessage }}</p>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

defineProps({
  modelValue: String,
  id: String,
  label: String,
  placeholder: String,
  disabled: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  rows: {
    type: [String, Number],
    default: 3,
  },
  icon: String,
  validationMessage: String,
  customClass: String,
});

defineEmits(['update:modelValue']);
</script>

<style scoped>
/* These styles are specific to BaseTextarea's internal layout, not the textarea's appearance */
.standardized-input-container {
  display: flex;
  width: 100%;
  min-height: 44px; /* Ensure consistent height for container */
  position: relative;
}
/* The standardized-input and resize-vertical classes are now global. */
</style>