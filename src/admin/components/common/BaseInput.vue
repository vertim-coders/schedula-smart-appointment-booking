<template>
  <div>
    <label v-if="label" :for="id" class="block text-sm font-medium mb-1">
      <i v-if="icon" :class="icon" class="mr-1" :style="{ color: 'var(--admin-input-text-muted)' }"></i>{{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <div class="standardized-input-container">
      <input
        :type="type"
        :id="id"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :class="['standardized-input', customClass]"
      />
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
  type: {
    type: String,
    default: 'text',
    validator: (value) => ['text', 'email', 'tel', 'number', 'date', 'time', 'password'].includes(value),
  },
  placeholder: String,
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
/* These styles are specific to BaseInput's internal layout, not the input's appearance */
.standardized-input-container {
  display: flex;
  width: 100%;
  position: relative;
}

/* The standardized-input class itself is now global, so no need to redefine it here. */
/* If you had other specific layout needs for the input itself, they would go here. */
</style>