<template>
  <div class="flex items-center">
    <input
      :id="id"
      type="checkbox"
      :checked="modelValue"
      @change="$emit('update:modelValue', $event.target.checked)"
      :disabled="disabled"
      :required="required"
      :class="['h-4 w-4 border rounded checkbox-field', customClass, { 'cursor-not-allowed': disabled }]"
    />
    <label v-if="label" :for="id" class="ml-2 block text-sm font-medium">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <p v-if="validationMessage" class="text-xs text-red-600 mt-1">{{ validationMessage }}</p>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

defineProps({
  modelValue: Boolean,
  id: String,
  label: String,
  disabled: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  customClass: String,
  validationMessage: String,
});

defineEmits(['update:modelValue']);
</script>

<style scoped>
/* Add any specific scoped styles for BaseCheckbox here if needed */
</style>
