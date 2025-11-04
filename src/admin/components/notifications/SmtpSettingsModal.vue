<template>
  <transition name="modal-fade">
    <div class="fixed inset-0 z-50 flex items-start justify-center pt-16 bg-black bg-opacity-50 overflow-y-auto" @click.self="$emit('close')">
      <div class="rounded-lg shadow-xl w-full max-w-2xl mx-4 my-4 p-6 relative modal-content" @click.stop :style="{ backgroundColor: 'var(--admin-card-bg-color)' }">
        <h3 class="text-lg font-bold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{ __('Configure SMTP', 'schedula-smart-appointment-booking') }}</h3>
        
        <div class="space-y-4">
          <div>
            <label class="flex items-center">
              <input type="checkbox" v-model="localSettings.smtpEnabled" class="h-4 w-4 rounded form-checkbox" :style="{borderColor: 'black', backgroundColor: localSettings.smtpEnabled ? 'var(--admin-link-indigo-bg)' : 'white'}" />
              <span class="ml-2 text-sm font-medium">{{__('Enable SMTP', 'schedula-smart-appointment-booking')}}</span>
            </label>
          </div>

          <template v-if="localSettings.smtpEnabled">
            <div>
              <label for="smtpHost" class="block text-sm font-medium mb-1">{{__('SMTP Host', 'schedula-smart-appointment-booking')}}</label>
              <BaseInput id="smtpHost" v-model="localSettings.smtpHost" class="w-full" />
            </div>
            <div>
              <label for="smtpPort" class="block text-sm font-medium mb-1">{{__('SMTP Port', 'schedula-smart-appointment-booking')}}</label>
              <BaseInput id="smtpPort" v-model.number="localSettings.smtpPort" type="number" class="w-full" />
            </div>
            <div>
              <label for="smtpEncryption" class="block text-sm font-medium mb-1">{{__('Encryption', 'schedula-smart-appointment-booking')}}</label>
              <select id="smtpEncryption" v-model="localSettings.smtpEncryption" class="w-full rounded-md p-2 border input-field">
                <option value="none">None</option>
                <option value="ssl">SSL</option>
                <option value="tls">TLS</option>
              </select>
            </div>
            <div>
              <label class="flex items-center">
                <input type="checkbox" v-model="localSettings.smtpAuth" class="h-4 w-4 rounded form-checkbox" :style="{borderColor: 'black', backgroundColor: localSettings.smtpAuth ? 'var(--admin-link-indigo-bg)' : 'white'}" />
                <span class="ml-2 text-sm font-medium">{{__('Use Authentication', 'schedula-smart-appointment-booking')}}</span>
              </label>
            </div>
            <template v-if="localSettings.smtpAuth">
              <div>
                <label for="smtpUsername" class="block text-sm font-medium mb-1">{{__('SMTP Username', 'schedula-smart-appointment-booking')}}</label>
                <BaseInput id="smtpUsername" v-model="localSettings.smtpUsername" class="w-full" />
              </div>
              <div>
                <label for="smtpPassword" class="block text-sm font-medium mb-1">{{__('SMTP Password', 'schedula-smart-appointment-booking')}}</label>
                <BaseInput id="smtpPassword" v-model="localSettings.smtpPassword" type="password" class="w-full" :placeholder="localSettings.smtpPasswordSet ? '********' : ''" />
              </div>
            </template>
          </template>
        </div>

        <div class="flex justify-between items-center mt-6">
          <div>
            <button @click="sendTestEmail" :disabled="isSendingTest" class="px-4 py-2 rounded-md text-sm" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)' }">
              <span v-if="!isSendingTest">{{ __('Send Test Email', 'schedula-smart-appointment-booking') }}</span>
              <span v-else>{{ __('Sending...', 'schedula-smart-appointment-booking') }}</span>
            </button>
          </div>
          <div class="flex space-x-4">
            <button @click="$emit('close')" class="px-4 py-2 rounded-md" :style="{ backgroundColor: 'var(--admin-button-third-bg)', color: 'var(--admin-button-secondary-text)' }">{{ __('Cancel', 'schedula-smart-appointment-booking') }}</button>
            <button @click="save" :disabled="isSaving" class="px-4 py-2 rounded-md w-24 flex justify-center items-center" :style="{ backgroundColor: 'var(--admin-link-indigo-bg)', color: 'var(--admin-link-indigo-text)' }">
              <span v-if="!isSaving">{{ __('Save', 'schedula-smart-appointment-booking') }}</span>
              <svg v-else class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { __ } from '@wordpress/i18n';
import BaseInput from '../common/BaseInput.vue';
import { notificationsApi } from '../../api.js';
import { useToast } from '../../composables/useToast.js';

const props = defineProps({
  modelValue: {
    type: Object,
    required: true
  }
});
const emit = defineEmits(['update:modelValue', 'close']);
const { success, error } = useToast();

const localSettings = reactive({ ...props.modelValue });
const isSaving = ref(false);
const isSendingTest = ref(false);

watch(() => props.modelValue, (newVal) => {
  Object.assign(localSettings, newVal);
}, { deep: true });

const save = async () => {
  isSaving.value = true;
  try {
    await notificationsApi.saveSettings(localSettings);
    success(__('SMTP settings saved successfully!', 'schedula-smart-appointment-booking'));
    emit('update:modelValue', localSettings);
    emit('close');
  } catch (err) {
    const errorMessage = err.response?.data?.message || err.message || __('An unknown error occurred.', 'schedula-smart-appointment-booking');
    error(errorMessage);
  } finally {
    isSaving.value = false;
  }
};

const sendTestEmail = async () => {
  isSendingTest.value = true;
  try {
    // We need to save the settings before sending the test email
    await notificationsApi.saveSettings(localSettings);
    const response = await notificationsApi.sendTestEmail();
    success(response.data.message);
  } catch (err) {
    const errorMessage = err.response?.data?.message || err.message || __('An unknown error occurred.', 'schedula-smart-appointment-booking');
    error(errorMessage);
  } finally {
    isSendingTest.value = false;
  }
};

</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
.modal-content {
  background-color: var(--admin-card-bg-color);
}
.input-field {
  background-color: var(--admin-input-bg-color);
  color: var(--admin-input-text-color);
  border-color: var(--admin-input-border-color);
}

</style>
