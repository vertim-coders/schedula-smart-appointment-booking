<template>
  <div>    
    <label v-if="label && label.trim()!=''" class="block text-sm font-medium">
      {{ label }} 
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <vue-tel-input 
      v-model="phone"
      :preferred-countries="preferredCountries"
      :default-country="defaultCountry"
      :disabled="disabled"
      :placeholder="placeholder || __('Enter phone number','schedula-smart-appointment-booking')"
      :inputOptions="{
        styleClasses: ['standardized-input phone-input', customClass],
        placeholder: placeholder || __('Enter phone number','schedula-smart-appointment-booking'),
        required: required,
      }"
      :styleClass="props.style"
    />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { __ } from '@wordpress/i18n';

const props = defineProps({
  modelValue: [String, Number],
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
  icon: String,
  validationMessage: String,
  customClass: String,
  style:Object,
  preferredCountries: {
    type: Array,
  },
  defaultCountry: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:modelValue']);

const phone = ref(props.modelValue || '');

// Watch le v-model de vue-tel-input et emit
watch(phone, (newPhone) => {
  emit('update:modelValue', newPhone);
});

// Watch les changements du modelValue parent
watch(() => props.modelValue, (newValue) => {
  phone.value = newValue || '';
}, { immediate: true });
</script>

<style scoped>

</style>