<template>
  <div>
    <label v-if="label" :for="id" class="block text-sm font-medium mb-1">
      <i v-if="icon" :class="icon" class="mr-1" :style="{ color: 'var(--admin-input-text-muted)' }"></i>{{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <div class="standardized-input-container">
      <select
        :id="id"
        :value="modelValue"
        @change="$emit('update:modelValue', $event.target.value)"
        :disabled="disabled"
        :required="required"
        :class="['standardized-input', 'select-appearance', customClass]"
      >
        <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
        <option v-for="option in options" :key="option.value" :value="option.value">
          {{ option.text }}
        </option>
      </select>
    </div>
    <p v-if="validationMessage" class="text-xs text-red-600 mt-1">{{ validationMessage }}</p>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

defineProps({
  modelValue: [String, Number],
  id: String,
  label: String,
  placeholder: String,
  options: {
    type: Array,
    required: true,
    default: () => [],
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  icon: String,
  validationMessage: String,
  customClass: String,
});

defineEmits(['update:modelValue']);
</script>

<style scoped>
/* These styles are specific to BaseSelect's internal layout, not the select's appearance */
.standardized-input-container {
  display: flex;
  width: 100%;
  position: relative;
}
/* The standardized-input and select-appearance classes are now global. */
</style>