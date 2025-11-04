<template>
  <div>
    <label v-if="label" :for="id" class="block text-sm font-medium mb-1">
      <i v-if="icon" :class="icon" class="mr-1" :style="{ color: 'var(--admin-input-text-muted)' }"></i>{{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <div class="standardized-input-container">
      <!-- Slot for the main input element (could be input or select) -->
      <!-- Expose relevant props to the slot content -->
      <slot name="main-content" :id="id" :modelValue="modelValue" :disabled="disabled" :required="required" :placeholder="placeholder">
        <!-- Default input if no slot content is provided -->
        <input
          :type="type"
          :id="id"
          :value="modelValue"
          @input="$emit('update:modelValue', $event.target.value)"
          :placeholder="placeholder"
          :disabled="disabled"
          :required="required"
          :class="['standardized-input-main', customClass]"
        />
      </slot>
      <button
        type="button"
        @click="$emit('button-click')"
        class="standardized-button"
        :disabled="disabled || buttonDisabled"
      >
        <i v-if="buttonIcon" :class="[buttonIcon, 'mr-1']"></i> {{ buttonText }}
      </button>
    </div>
    <p v-if="validationMessage" class="text-xs text-red-600 mt-1">{{ validationMessage }}</p>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

defineProps({
  modelValue: [String, Number],
  id: String, // This prop needs to be defined
  label: String,
  type: { // Type is still useful for the default input slot
    type: String,
    default: 'text',
    validator: (value) => ['text', 'email', 'tel', 'number', 'date', 'time', 'password'].includes(value),
  },
  placeholder: String,
  buttonText: {
    type: String,
    default: 'Button',
  },
  buttonIcon: String,
  disabled: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  buttonDisabled: {
    type: Boolean,
    default: false,
  },
  icon: String,
  validationMessage: String,
  customClass: String,
});

defineEmits(['update:modelValue', 'button-click']);
</script>

<style scoped>
/* The standardized-input-container, standardized-input-main, and standardized-button classes are now global. */
/* No specific scoped styles needed here, as the global styles handle the layout and appearance. */
</style>