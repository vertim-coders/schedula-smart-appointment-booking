<template>
  <div :style="computedStyleVars"> 
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
        style: props.style,
      }"
      :styleClass="props.style"
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

const computedStyleVars = computed(() => {
  const vars = {};
  if (props.style) {
    if (props.style.backgroundColor) {
      vars['--vti-bg-color'] = props.style.backgroundColor;
    }
    if (props.style.color) {
      vars['--vti-text-color'] = props.style.color;
    }
    if (props.style.borderColor) {
      vars['--vti-border-color'] = props.style.borderColor;
    }
  }
  return vars;
});

watch(phone, (newPhone) => {
  emit('update:modelValue', newPhone);
});

watch(() => props.modelValue, (newValue) => {
  phone.value = newValue || '';
}, { immediate: true });
</script>

<style scoped>
.vue-tel-input {
  background-color: var(--vti-bg-color, #fff) !important; 
  color: var(--vti-text-color, #000) !important; 
  border-color: var(--vti-border-color, #ccc) !important; 
}

.vue-tel-input .vti__input {
  background-color: var(--vti-bg-color, #fff) !important;
  color: var(--vti-text-color, #000) !important;
  border-color: var(--vti-border-color, #ccc) !important;
}

.vue-tel-input .vti__dropdown {
  background-color: var(--vti-bg-color, #fff) !important;
  color: var(--vti-text-color, #000) !important;
  border-color: var(--vti-border-color, #ccc) !important;
}

.vue-tel-input .vti__dropdown-list {
  background-color: var(--vti-bg-color, #fff) !important;
  color: var(--vti-text-color, #000) !important;
  border-color: var(--vti-border-color, #ccc) !important;
}

.vue-tel-input .vti__dropdown-item {
  color: var(--vti-text-color, #000) !important;
}

.vue-tel-input .vti__selected-flag {
  background-color: var(--vti-bg-color, #fff) !important;
}

.vue-tel-input.vue-tel-input {
  border: 1px solid var(--vti-border-color, #ccc) !important;
}

.vue-tel-input .vti__input,
.vue-tel-input .vti__dropdown {
  border: none !important; 
}

.vue-tel-input {
  padding: 0 !important; 
}

.vue-tel-input .vti__input {
  padding: v-bind('props.style.padding') !important; 
  height: v-bind('props.style.height') !important; 
  line-height: v-bind('props.style.lineHeight') !important; 
}

.vue-tel-input .vti__dropdown {
  padding: v-bind('props.style.padding') !important; 
}

.vue-tel-input .vti__dropdown-arrow {
  border-top-color: var(--vti-text-color, #000) !important;
}

</style>