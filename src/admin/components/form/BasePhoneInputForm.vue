<template>
    <div> 
      <label v-if="label && label.trim()!=''" class="block text-sm font-medium text-gray-700">
        {{ label }} <span v-if="required" class="text-red-500">*</span>
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
        :wrapperClasses="customClass"
      />
    </div>
  </template>
  
  <script setup>
  import { ref, watch, computed } from 'vue' 
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
  
  
  
  watch(phone, (newPhone) => {
    emit('update:modelValue', newPhone);
  });
  
  watch(() => props.modelValue, (newValue) => {
    phone.value = newValue || '';
  }, { immediate: true });
  </script>
  
  <style scoped>
  .vue-tel-input {
    background-color: v-bind('style?.backgroundColor || "#fff"') !important; 
    color: v-bind('style?.color || "#000"') !important; 
    border-color: v-bind('style?.borderColor || "#ccc"') !important; 
  }
  
  .vue-tel-input .vti__input {
    background-color: v-bind('style?.backgroundColor || "#fff"') !important;
    color: v-bind('style?.color || "#000"') !important;
    border-color: v-bind('style?.borderColor || "#ccc"') !important;
  }
  
  .vue-tel-input .vti__dropdown {
    background-color: v-bind('style?.backgroundColor || "#fff"') !important;
    color: v-bind('style?.color || "#000"') !important;
    border-color: v-bind('style?.borderColor || "#ccc"') !important;
  }
  
  .vue-tel-input .vti__dropdown-list {
    background-color: v-bind('style?.backgroundColor || "#fff"') !important;
    color: v-bind('style?.color || "#000"') !important;
    border-color: v-bind('style?.borderColor || "#ccc"') !important;
  }
  
  .vue-tel-input .vti__dropdown-item {
    color: v-bind('style?.color || "#000"') !important;
  }
  
  .vue-tel-input .vti__selected-flag {
    background-color: v-bind('style?.backgroundColor || "#fff"') !important;
  }
  
  .vue-tel-input.vue-tel-input {
    border: 1px solid v-bind('style?.borderColor || "#ccc"') !important;
  }
  
  .vue-tel-input .vti__input,
  .vue-tel-input .vti__dropdown {
    border: none !important; 
  }
  
  .vue-tel-input {
    padding: 0 !important; 
  }
  
  .vue-tel-input .vti__input {
    padding: v-bind('style?.padding') !important; 
    height: v-bind('style?.height') !important; 
    line-height: v-bind('style?.lineHeight') !important; 
  }
  
  .vue-tel-input .vti__dropdown {
    padding: v-bind('style?.padding') !important; 
  }
  
  .vue-tel-input .vti__dropdown-arrow {
    border-top-color: v-bind('style?.color || "#000"') !important;
  }
  
  </style>