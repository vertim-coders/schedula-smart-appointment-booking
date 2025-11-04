<template>
  <div :style="adminCustomStyles" :class="{ 'dark-mode': appearanceSettings.adminDarkModeEnabled }" class="min-h-screen px-4 sm:px-6 lg:px-8 pb-6">
    <h1 class="text-3xl font-semibold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{ __('Notifications', 'schedula-smart-appointment-booking') }}</h1>

    <div class="flex border-b mb-4" :style="{ borderColor: 'var(--admin-border-color)' }">
      <button v-for="tab in tabs" :key="tab.id" @click="currentTab = tab.id" :class="['px-4 py-2 text-sm font-medium', currentTab === tab.id ? 'border-b-2 font-semibold' : '' ]" :style="{ borderColor: currentTab === tab.id ? 'var(--admin-link-indigo-bg)' : 'transparent', color: currentTab === tab.id ? 'var(--admin-link-indigo-bg)' : 'var(--admin-card-text-color)' }">
        {{ tab.name }}
      </button>
    </div>

    <div v-if="isLoading" class="min-h-[40vh] flex items-center justify-center">
      <AppLoader />
    </div>

    <div v-else>
      <!-- Notifications Tab -->
      <div v-if="currentTab === 'notifications'">
        <div class="space-y-4">
          <div v-for="(notification, key) in notificationTemplates" :key="key" class="p-4 rounded-lg shadow-md content-card">
            <h2 class="text-xl font-bold mb-2">{{ notification.title }}</h2>
            <p class="text-sm mb-2" :style="{ color: 'var(--admin-card-text-color)' }">{{ notification.description }}</p>
            <div class="flex items-center mb-2">
              <input :id="`enable-${key}`" type="checkbox" v-model="notificationSettings[notification.enableKey]" class="h-4 w-4 rounded form-checkbox" :style="{borderColor: 'black', backgroundColor: notificationSettings[notification.enableKey] ? 'var(--admin-link-indigo-bg)' : 'white'}" />
              <label :for="`enable-${key}`" class="ml-2 text-sm font-medium">{{__('Enable Notification', 'schedula-smart-appointment-booking')}}</label>
            </div>
            <div v-if="notificationSettings[notification.enableKey]" class="space-y-2">
              <div>
                <label :for="`${key}-subject`" class="block text-sm font-medium mb-1">{{__('Email Subject', 'schedula-smart-appointment-booking')}}</label>
                <BaseInput :id="`${key}-subject`" v-model="notificationSettings[notification.subjectKey]" class="w-full" />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">{{__('Email Body', 'schedula-smart-appointment-booking')}}</label>
                <div @click="openEditor(notification.bodyKey)" class="w-full p-2 border rounded-md cursor-pointer min-h-[100px]" :style="{ backgroundColor: 'var(--admin-input-bg-color)', borderColor: 'var(--admin-input-border-color)', color: 'var(--admin-input-text-color)' }" v-html="notificationSettings[notification.bodyKey]"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Settings Tab -->
      <div v-if="currentTab === 'settings'" class="p-4 rounded-lg shadow-md content-card space-y-4">
        <div>
          <label for="senderName" class="block text-sm font-medium mb-1">{{__('Sender Name', 'schedula-smart-appointment-booking')}}</label>
          <BaseInput id="senderName" v-model="notificationSettings.senderName" placeholder="Your Company Name" class="w-full" />
        </div>
        <div>
          <label for="senderEmail" class="block text-sm font-medium mb-1">{{__('Sender Email', 'schedula-smart-appointment-booking')}}</label>
          <BaseInput id="senderEmail" v-model="notificationSettings.senderEmail" placeholder="your@email.com" class="w-full" />
        </div>
        <div>
          <label for="emailContentType" class="block text-sm font-medium mb-1">{{__('Email Format', 'schedula-smart-appointment-booking')}}</label>
          <select id="emailContentType" v-model="notificationSettings.emailContentType" class="w-full rounded-md p-2 border input-field">
            <option value="html">HTML</option>
            <option value="text">{{__('Plain Text', 'schedula-smart-appointment-booking')}}</option>
          </select>
        </div>
        <div>
          <label for="adminRecipientEmail" class="block text-sm font-medium mb-1">{{__('Admin Recipient Email', 'schedula-smart-appointment-booking')}}</label>
          <BaseInput id="adminRecipientEmail" v-model="notificationSettings.adminRecipientEmail" placeholder="admin@yourcompany.com" class="w-full" />
        </div>
        <div>
            <button @click="showSmtpModal = true" class="px-4 py-2 rounded-md text-sm font-medium" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)' }">
                {{__('Configure SMTP', 'schedula-smart-appointment-booking')}}
            </button>
        </div>
      </div>

      <!-- Email Log Tab -->
      <div v-if="currentTab === 'email_log'" class="p-4 rounded-lg shadow-md content-card">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
          <div class="flex-grow">
            <h2 class="text-xl font-bold">{{ __('Email Log', 'schedula-smart-appointment-booking') }}</h2>
          </div>

          <!-- Rows per page -->
           <div class="flex items-center gap-2 mb-4 sm:mb-0">
                <span class="text-sm">{{ __('Rows per page:', 'schedula-smart-appointment-booking') }}</span>
                <select v-model="emailItemsPerPage" @change="handleEmailItemsPerPageChange" class="rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm p-1.5 input-field">
                    <option v-for="option in emailItemsPerPageOptions" :key="option" :value="option">{{ option }}</option>
                </select>
            </div>

          <div class="relative w-full sm:w-72">
            <BaseInput
              id="search-email-log"
              v-model="emailSearchQuery"
              :placeholder="__('Search by recipient or subject', 'schedula-smart-appointment-booking')"
              @input="debouncedFetchEmailLog"
              icon="fas fa-search"
            />
          </div>
          <button @click="openBulkDeleteEmailModal" :disabled="selectedEmails.length === 0 || isDeletingEmails" class="px-3 py-1.5 rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:bg-gray-400">
            <svg v-if="isDeletingEmails" class="animate-spin h-4 w-4 mr-2 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <i v-else class="fas fa-trash mr-1"></i>
            {{__('Delete Selected', 'schedula-smart-appointment-booking')}} ({{ selectedEmails.length }})
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y" :style="{ borderColor: 'var(--admin-border-color)' }">
            <thead :style="{ backgroundColor: 'var(--admin-card-bg-color)' }">
              <tr>
                <th scope="col" class="p-4"><input type="checkbox" @change="selectAllEmails" :checked="allEmailsSelected" :indeterminate="isEmailSelectionIndeterminate" class="h-4 w-4 rounded form-checkbox" :style="{borderColor: 'black', backgroundColor: allEmailsSelected ? 'var(--admin-link-indigo-bg)' : 'white'}" /></th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider cursor-pointer" :style="{ color: 'var(--admin-card-text-color)' }" @click="handleEmailSort('recipient')">{{__('Recipient', 'schedula-smart-appointment-booking')}} <i v-if="emailSortBy === 'recipient'" :class="['fas', emailSortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i><i v-else class="fas fa-sort"></i></th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider cursor-pointer" :style="{ color: 'var(--admin-card-text-color)' }" @click="handleEmailSort('subject')">{{__('Subject', 'schedula-smart-appointment-booking')}} <i v-if="emailSortBy === 'subject'" :class="['fas', emailSortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i><i v-else class="fas fa-sort"></i></th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider cursor-pointer" :style="{ color: 'var(--admin-card-text-color)' }" @click="handleEmailSort('sent_at')">{{__('Date Created', 'schedula-smart-appointment-booking')}} <i v-if="emailSortBy === 'sent_at'" :class="['fas', emailSortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i><i v-else class="fas fa-sort"></i></th>
                <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider" :style="{ color: 'var(--admin-card-text-color)' }">{{__('Actions', 'schedula-smart-appointment-booking')}}</th>
              </tr>
            </thead>
            <tbody class="divide-y" :style="{ backgroundColor: 'var(--admin-card-bg-color)', borderColor: 'var(--admin-border-color)' }">
                <tr v-if="isEmailLogLoading"><td :colspan="5" class="text-center p-8"><AppLoader /></td></tr>
                <tr v-else-if="emailLog.length === 0"><td :colspan="5" class="text-center p-8">{{ __('No email logs found.', 'schedula-smart-appointment-booking') }}</td></tr>
                <tr v-for="email in emailLog" :key="email.id" class="cursor-pointer hover:opacity-80">
                    <td class="p-4" @click.stop><input type="checkbox" v-model="selectedEmails" :value="email.id" class="h-4 w-4 rounded form-checkbox" :style="{borderColor: 'black', backgroundColor: selectedEmails.includes(email.id) ? 'var(--admin-link-indigo-bg)' : 'white'}" /></td>
                    <td class="px-4 py-3 text-sm break-words" :style="{ color: 'var(--admin-card-text-color)' }" @click="openEmailViewModal(email)">{{ email.recipient }}</td>
                    <td class="px-4 py-3 text-sm break-words" :style="{ color: 'var(--admin-card-text-color)' }" @click="openEmailViewModal(email)">{{ email.subject }}</td>
                    <td class="px-4 py-3 text-sm" :style="{ color: 'var(--admin-card-text-color)' }" @click="openEmailViewModal(email)">{{ new Date(email.sent_at).toLocaleString([], { year: 'numeric', month: 'numeric', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true }) }}</td>
                    <td class="px-4 py-3 text-sm text-center">
                      <button @click.stop="handleDeleteEmail(email)" class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                </tr>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <div v-if="emailTotalPages > 1" class="flex flex-col sm:flex-row items-center justify-between mt-4">
            <div class="flex items-center gap-2 mb-4 sm:mb-0">
                <span class="text-sm">{{ __('Rows per page:', 'schedula-smart-appointment-booking') }}</span>
                <select v-model="emailItemsPerPage" @change="handleEmailItemsPerPageChange" class="rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm p-1.5 input-field">
                    <option v-for="option in emailItemsPerPageOptions" :key="option" :value="option">{{ option }}</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm">{{ __('Page', 'schedula-smart-appointment-booking') }} {{ emailCurrentPage }} of {{ emailTotalPages }}</span>
                <button @click="goToEmailPage(1)" :disabled="emailCurrentPage === 1" class="p-2 rounded-md disabled:opacity-50"><i class="fas fa-angle-double-left"></i></button>
                <button @click="prevEmailPage" :disabled="emailCurrentPage === 1" class="p-2 rounded-md disabled:opacity-50"><i class="fas fa-angle-left"></i></button>
                <button @click="nextEmailPage" :disabled="emailCurrentPage === emailTotalPages" class="p-2 rounded-md disabled:opacity-50"><i class="fas fa-angle-right"></i></button>
                <button @click="goToEmailPage(emailTotalPages)" :disabled="emailCurrentPage === emailTotalPages" class="p-2 rounded-md disabled:opacity-50"><i class="fas fa-angle-double-right"></i></button>
            </div>
        </div>
      </div>

      <!-- Save/Reset Buttons -->
      <div class="mt-4 pt-2 border-t flex justify-end items-center" :style="{ borderColor: 'var(--admin-border-color)' }">
        <button @click="resetSettings" :disabled="isLoading || isSaving || isResetting" class="px-4 py-2 rounded-md text-sm font-medium mr-2 relative" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)' }">
          <span :class="{ 'opacity-0': isResetting }">{{__('Reset', 'schedula-smart-appointment-booking')}}</span>
          <div v-if="isResetting" class="absolute inset-0 flex items-center justify-center">
            <svg class="animate-spin h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
        </button>
        <button @click="saveSettings" :disabled="isLoading || isSaving || isResetting" class="px-4 py-2 rounded-md text-sm font-medium relative" :style="{ backgroundColor: 'var(--admin-link-indigo-bg)', color: 'var(--admin-link-indigo-text)' }">
          <span :class="{ 'opacity-0': isSaving }">{{__('Save Settings', 'schedula-smart-appointment-booking')}}</span>
          <div v-if="isSaving" class="absolute inset-0 flex items-center justify-center">
            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
        </button>
      </div>
    </div>

    <EmailBodyEditor v-if="showEditor" :modelValue="editingEmailBody.content" :subject="editingEmailBody.subject" @update:modelValue="updateEmailBody" @close="closeEditor" />

    <SmtpSettingsModal v-if="showSmtpModal" :modelValue="notificationSettings" @update:modelValue="updateSmtpSettings" @close="showSmtpModal = false" />

    <!-- Email View Modal -->
    <transition name="modal-fade">
      <div v-if="showEmailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeEmailViewModal">
        <div class="rounded-lg shadow-xl p-4 sm:p-6 w-full max-w-2xl max-h-[90vh] flex flex-col modal-content" :style="{ backgroundColor: 'var(--admin-card-bg-color)' }" @click.stop>
          <div class="flex justify-between items-center border-b pb-3 mb-3" :style="{ borderColor: 'var(--admin-border-color)' }">
            <h3 class="text-lg font-bold" :style="{ color: 'var(--admin-text-color)' }">{{__('Email Details', 'schedula-smart-appointment-booking')}}</h3>
            <button @click="closeEmailViewModal" class="p-2 rounded-full text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700 dark:text-gray-300">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div v-if="viewingEmail" class="space-y-4 overflow-y-auto">
            <div class="text-sm"><strong>{{__('To', 'schedula-smart-appointment-booking')}}:</strong> {{ viewingEmail.recipient }}</div>
            <div class="text-sm"><strong>{{__('Subject', 'schedula-smart-appointment-booking')}}:</strong> {{ viewingEmail.subject }}</div>
            <div class="text-sm"><strong>{{__('Date', 'schedula-smart-appointment-booking')}}:</strong> {{ new Date(viewingEmail.sent_at).toLocaleString([], { year: 'numeric', month: 'numeric', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true }) }}</div>
            <div class="border-t pt-4 mt-4" :style="{ borderColor: 'var(--admin-border-color)' }">
              <h4 class="font-bold mb-2 text-sm">{{__("Body:", 'schedula-smart-appointment-booking')}}</h4>
              <div v-if="isFetchingEmailBody" class="flex justify-center items-center min-h-[100px]">
                <AppLoader />
              </div>
              <div v-else class="p-4 border rounded-md text-sm" :style="{ backgroundColor: 'var(--admin-input-bg-color)', borderColor: 'var(--admin-input-border-color)' }" v-html="viewingEmail.body"></div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Unified Delete Confirmation Modal for Emails -->
    <transition name="modal-fade">
      <div v-if="showDeleteEmailModal || showBulkDeleteEmailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeEmailDeleteModals">
        <div class="rounded-lg shadow-xl p-6 max-w-sm mx-auto modal-content" @click.stop :style="{ backgroundColor: 'var(--admin-card-bg-color)' }">
          <h3 class="text-lg font-bold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{ __('Confirm Deletion', 'schedula-smart-appointment-booking') }}</h3>
          
          <div v-if="showDeleteEmailModal">
            <p class="mb-4" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Are you sure you want to delete this email log?', 'schedula-smart-appointment-booking') }}</p>
          </div>

          <div v-if="showBulkDeleteEmailModal">
            <p class="mb-4" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Are you sure you want to delete the selected', 'schedula-smart-appointment-booking') }} {{ selectedEmails.length }} {{ __('email logs?', 'schedula-smart-appointment-booking') }}</p>
          </div>

          <div class="flex justify-end space-x-4">
            <button @click="closeEmailDeleteModals" class="px-4 py-2 rounded-md" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)' }">{{ __('Cancel', 'schedula-smart-appointment-booking') }}</button>
            <button @click="showDeleteEmailModal ? confirmDeleteEmail() : confirmBulkDeleteEmails()" 
            :disabled="isDeletingEmails" 
            class="px-4 py-2 rounded-md bg-red-600 text-white hover:bg-red-700 flex items-center justify-center w-24">
              <svg v-if="isDeletingEmails" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span v-else>{{ __('Delete', 'schedula-smart-appointment-booking') }}</span>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue';
import { useAdminAppearance } from '../composables/useAdminAppearance.js';
import { useEmailTemplates } from '../composables/useEmailTemplates.js';
import { notificationsApi } from '../api.js';
import BaseInput from '../components/common/BaseInput.vue';
import AppLoader from '../components/shared/AppLoader.vue';
import EmailBodyEditor from '../components/notifications/EmailBodyEditor.vue';
import SmtpSettingsModal from '../components/notifications/SmtpSettingsModal.vue';
import { useToast } from '../composables/useToast.js';
import { __ } from '@wordpress/i18n';

const { appearanceSettings, adminCustomStyles } = useAdminAppearance();
const { templates } = useEmailTemplates();

const isLoading = ref(false);
const isSaving = ref(false); 
const isResetting = ref(false); // New state for resetting
const isEmailLogLoading = ref(false);
const { success, error, info } = useToast();
const currentTab = ref('notifications');    
const tabs = [
  { id: 'notifications', name: __('Notifications', 'schedula-smart-appointment-booking') },
  { id: 'settings', name: __('Settings', 'schedula-smart-appointment-booking') },
  { id: 'email_log', name: __('Email Log', 'schedula-smart-appointment-booking') },
];

const notificationSettings = reactive({});
const emailLog = ref([]);
const selectedEmails = ref([]);
const showDeleteEmailModal = ref(false);
const showBulkDeleteEmailModal = ref(false);
const emailToDelete = ref(null);
const isDeletingEmails = ref(false);

// Pagination and Sorting for Email Log
const emailCurrentPage = ref(1);
const emailItemsPerPageOptions = [5, 10, 50, 100];
const emailItemsPerPage = ref(emailItemsPerPageOptions[1]);
const totalEmails = ref(0);
const emailSortBy = ref('sent_at');
const emailSortDirection = ref('desc');
const emailSearchQuery = ref('');
let emailDebounceTimeout = null;

const emailTotalPages = computed(() => {
  if (emailItemsPerPage.value === 0) return 0;
  return Math.ceil(totalEmails.value / emailItemsPerPage.value);
});

const debouncedFetchEmailLog = () => {
  clearTimeout(emailDebounceTimeout);
  emailDebounceTimeout = setTimeout(() => {
    emailCurrentPage.value = 1;
    fetchEmailLog();
  }, 300);
};

const handleEmailSort = (column) => {
  if (emailSortBy.value === column) {
    emailSortDirection.value = emailSortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    emailSortBy.value = column;
    emailSortDirection.value = 'desc';
  }
  fetchEmailLog();
};

const goToEmailPage = (page) => {
  if (page >= 1 && page <= emailTotalPages.value) {
    emailCurrentPage.value = page;
    fetchEmailLog();
  }
};

const nextEmailPage = () => {
  if (emailCurrentPage.value < emailTotalPages.value) {
    emailCurrentPage.value++;
    fetchEmailLog();
  }
};

const prevEmailPage = () => {
  if (emailCurrentPage.value > 1) {
    emailCurrentPage.value--;
    fetchEmailLog();
  }
};

const handleEmailItemsPerPageChange = () => {
  emailCurrentPage.value = 1;
  fetchEmailLog();
};

const allEmailsSelected = computed(() => {
    if (emailLog.value.length === 0) return false;
    return selectedEmails.value.length === emailLog.value.length;
});

const isEmailSelectionIndeterminate = computed(() => {
    return selectedEmails.value.length > 0 && selectedEmails.value.length < emailLog.value.length;
});

const showEmailModal = ref(false);
const viewingEmail = ref(null);
const isFetchingEmailBody = ref(false);

const showEditor = ref(false);
const editingEmailBody = reactive({ key: null, content: '', subject: '' });
const showSmtpModal = ref(false);

const notificationTemplates = {
    newBookingToClient: { title: __('New Booking (to Client)', 'schedula-smart-appointment-booking'), description: __('Sent to the client when a booking is first made (pending).', 'schedula-smart-appointment-booking'), enableKey: 'enableNewBookingToClient', subjectKey: 'newBookingToClientSubject', bodyKey: 'newBookingToClientBody' },
    newBookingToStaff: { title: __('New Booking (to Staff)', 'schedula-smart-appointment-booking'), description: __('Sent to staff when a new booking is requested.', 'schedula-smart-appointment-booking'), enableKey: 'enableNewBookingToStaff', subjectKey: 'newBookingToStaffSubject', bodyKey: 'newBookingToStaffBody' },

    pendingGroupBookingToClient: { title: __('Group Booking (Pending, to Client)', 'schedula-smart-appointment-booking'), description: __('Sent to the client when a group booking is made and is pending confirmation.', 'schedula-smart-appointment-booking'), enableKey: 'enablePendingGroupBookingToClient', subjectKey: 'pendingGroupBookingToClientSubject', bodyKey: 'pendingGroupBookingToClientBody' },
    pendingGroupBookingToStaff: { title: __('Group Booking (Pending, to Staff)', 'schedula-smart-appointment-booking'), description: __('Sent to staff when a group booking is made and is pending confirmation.', 'schedula-smart-appointment-booking'), enableKey: 'enablePendingGroupBookingToStaff', subjectKey: 'pendingGroupBookingToStaffSubject', bodyKey: 'pendingGroupBookingToStaffBody' },

    confirmedGroupBookingToClient: { title: __('Group Booking (Confirmed, to Client)', 'schedula-smart-appointment-booking'), description: __('Sent to the client when a group booking is confirmed.', 'schedula-smart-appointment-booking'), enableKey: 'enableConfirmedGroupBookingToClient', subjectKey: 'confirmedGroupBookingToClientSubject', bodyKey: 'confirmedGroupBookingToClientBody' },
    confirmedGroupBookingToStaff: { title: __('Group Booking (Confirmed, to Staff)', 'schedula-smart-appointment-booking'), description: __('Sent to staff when a group booking is confirmed.', 'schedula-smart-appointment-booking'), enableKey: 'enableConfirmedGroupBookingToStaff', subjectKey: 'confirmedGroupBookingToStaffSubject', bodyKey: 'confirmedGroupBookingToStaffBody' },

    pendingRecurringBookingToClient: { title: __('Recurring Booking (Pending, to Client)', 'schedula-smart-appointment-booking'), description: __('Sent to the client when a recurring booking series is created and is pending confirmation.', 'schedula-smart-appointment-booking'), enableKey: 'enablePendingRecurringBookingToClient', subjectKey: 'pendingRecurringBookingToClientSubject', bodyKey: 'pendingRecurringBookingToClientBody' },
    pendingRecurringBookingToStaff: { title: __('Recurring Booking (Pending, to Staff)', 'schedula-smart-appointment-booking'), description: __('Sent to staff when a recurring booking series is created and is pending confirmation.', 'schedula-smart-appointment-booking'), enableKey: 'enablePendingRecurringBookingToStaff', subjectKey: 'pendingRecurringBookingToStaffSubject', bodyKey: 'pendingRecurringBookingToStaffBody' },

    confirmedRecurringBookingToClient: { title: __('Recurring Booking (Confirmed, to Client)', 'schedula-smart-appointment-booking'), description: __('Sent to the client when a recurring booking series is confirmed.', 'schedula-smart-appointment-booking'), enableKey: 'enableConfirmedRecurringBookingToClient', subjectKey: 'confirmedRecurringBookingToClientSubject', bodyKey: 'confirmedRecurringBookingToClientBody' },
    confirmedRecurringBookingToStaff: { title: __('Recurring Booking (Confirmed, to Staff)', 'schedula-smart-appointment-booking'), description: __('Sent to staff when a recurring booking series is confirmed.', 'schedula-smart-appointment-booking'), enableKey: 'enableConfirmedRecurringBookingToStaff', subjectKey: 'confirmedRecurringBookingToStaffSubject', bodyKey: 'confirmedRecurringBookingToStaffBody' },

    pendingRecurringGroupBookingToClient: { title: __('Recurring Group Booking (Pending, to Client)', 'schedula-smart-appointment-booking'), description: __('Sent to the client when a recurring group booking series is created and is pending confirmation.', 'schedula-smart-appointment-booking'), enableKey: 'enablePendingRecurringGroupBookingToClient', subjectKey: 'pendingRecurringGroupBookingToClientSubject', bodyKey: 'pendingRecurringGroupBookingToClientBody' },
    pendingRecurringGroupBookingToStaff: { title: __('Recurring Group Booking (Pending, to Staff)', 'schedula-smart-appointment-booking'), description: __('Sent to staff when a recurring group booking series is created and is pending confirmation.', 'schedula-smart-appointment-booking'), enableKey: 'enablePendingRecurringGroupBookingToStaff', subjectKey: 'pendingRecurringGroupBookingToStaffSubject', bodyKey: 'pendingRecurringGroupBookingToStaffBody' },

    confirmedRecurringGroupBookingToClient: { title: __('Recurring Group Booking (Confirmed, to Client)', 'schedula-smart-appointment-booking'), description: __('Sent to the client when a recurring group booking series is confirmed.', 'schedula-smart-appointment-booking'), enableKey: 'enableConfirmedRecurringGroupBookingToClient', subjectKey: 'confirmedRecurringGroupBookingToClientSubject', bodyKey: 'confirmedRecurringGroupBookingToClientBody' },
    confirmedRecurringGroupBookingToStaff: { title: __('Recurring Group Booking (Confirmed, to Staff)', 'schedula-smart-appointment-booking'), description: __('Sent to staff when a recurring group booking series is confirmed.', 'schedula-smart-appointment-booking'), enableKey: 'enableConfirmedRecurringGroupBookingToStaff', subjectKey: 'confirmedRecurringGroupBookingToStaffSubject', bodyKey: 'confirmedRecurringGroupBookingToStaffBody' },

    confirmedToClient: { title: __('Appointment Confirmed (to Client)', 'schedula-smart-appointment-booking'), description: __('Sent to the client when an admin confirms their appointment.', 'schedula-smart-appointment-booking'), enableKey: 'enableConfirmedToClient', subjectKey: 'confirmedToClientSubject', bodyKey: 'confirmedToClientBody' },
    confirmedToStaff: { title: __('Appointment Confirmed (to Staff)', 'schedula-smart-appointment-booking'), description: __('Sent to staff when an appointment is confirmed.', 'schedula-smart-appointment-booking'), enableKey: 'enableConfirmedToStaff', subjectKey: 'confirmedToStaffSubject', bodyKey: 'confirmedToStaffBody' },

    cancelledToClient: { title: __('Appointment Cancelled (to Client)', 'schedula-smart-appointment-booking'), description: __('Email to customer when an appointment is cancelled.', 'schedula-smart-appointment-booking'), enableKey: 'enableCancelledToClient', subjectKey: 'cancelledToClientSubject', bodyKey: 'cancelledToClientBody' },
    cancelledToStaff: { title: __('Appointment Cancelled (to Staff)', 'schedula-smart-appointment-booking'), description: __('Email to staff when an appointment is cancelled.', 'schedula-smart-appointment-booking'), enableKey: 'enableCancelledToStaff', subjectKey: 'cancelledToStaffSubject', bodyKey: 'cancelledToStaffBody' },

    rejectedToClient: { title: __('Appointment Rejected (to Client)', 'schedula-smart-appointment-booking'), description: __('Email to customer when an appointment is rejected.', 'schedula-smart-appointment-booking'), enableKey: 'enableRejectedToClient', subjectKey: 'rejectedToClientSubject', bodyKey: 'rejectedToClientBody' },
    rejectedToStaff: { title: __('Appointment Rejected (to Staff)', 'schedula-smart-appointment-booking'), description: __('Email to staff when an appointment is rejected.', 'schedula-smart-appointment-booking'), enableKey: 'enableRejectedToStaff', subjectKey: 'rejectedToStaffSubject', bodyKey: 'rejectedToStaffBody' },

    // Admin Notifications
    newBookingToAdmin: { title: __('New Booking (to Admin)', 'schedula-smart-appointment-booking'), description: __('Sent to the admin when a new booking is requested.', 'schedula-smart-appointment-booking'), enableKey: 'enableNewBookingToAdmin', subjectKey: 'newBookingToAdminSubject', bodyKey: 'newBookingToAdminBody' },

    pendingGroupBookingToAdmin: { title: __('Group Booking (Pending, to Admin)', 'schedula-smart-appointment-booking'), description: __('Sent to the admin when a group booking is made and is pending confirmation.', 'schedula-smart-appointment-booking'), enableKey: 'enablePendingGroupBookingToAdmin', subjectKey: 'pendingGroupBookingToAdminSubject', bodyKey: 'pendingGroupBookingToAdminBody' },
    confirmedGroupBookingToAdmin: { title: __('Group Booking (Confirmed, to Admin)', 'schedula-smart-appointment-booking'), description: __('Sent to the admin when a group booking is confirmed.', 'schedula-smart-appointment-booking'), enableKey: 'enableConfirmedGroupBookingToAdmin', subjectKey: 'confirmedGroupBookingToAdminSubject', bodyKey: 'confirmedGroupBookingToAdminBody' },

    pendingRecurringBookingToAdmin: { title: __('Recurring Booking (Pending, to Admin)', 'schedula-smart-appointment-booking'), description: __('Sent to the admin when a recurring booking series is created and is pending confirmation.', 'schedula-smart-appointment-booking'), enableKey: 'enablePendingRecurringBookingToAdmin', subjectKey: 'pendingRecurringBookingToAdminSubject', bodyKey: 'pendingRecurringBookingToAdminBody' },
    confirmedRecurringBookingToAdmin: { title: __('Recurring Booking (Confirmed, to Admin)', 'schedula-smart-appointment-booking'), description: __('Sent to the admin when a recurring booking series is confirmed.', 'schedula-smart-appointment-booking'), enableKey: 'enableConfirmedRecurringBookingToAdmin', subjectKey: 'confirmedRecurringBookingToAdminSubject', bodyKey: 'confirmedRecurringBookingToAdminBody' },

    pendingRecurringGroupBookingToAdmin: { title: __('Recurring Group Booking (Pending, to Admin)', 'schedula-smart-appointment-booking'), description: __('Sent to the admin when a recurring group booking series is created and is pending confirmation.', 'schedula-smart-appointment-booking'), enableKey: 'enablePendingRecurringGroupBookingToAdmin', subjectKey: 'pendingRecurringGroupBookingToAdminSubject', bodyKey: 'pendingRecurringGroupBookingToAdminBody' },
    confirmedRecurringGroupBookingToAdmin: { title: __('Recurring Group Booking (Confirmed, to Admin)', 'schedula-smart-appointment-booking'), description: __('Sent to the admin when a recurring group booking series is confirmed.', 'schedula-smart-appointment-booking'), enableKey: 'enableConfirmedRecurringGroupBookingToAdmin', subjectKey: 'confirmedRecurringGroupBookingToAdminSubject', bodyKey: 'confirmedRecurringGroupBookingToAdminBody' },

    confirmedToAdmin: { title: __('Appointment Confirmed (to Admin)', 'schedula-smart-appointment-booking'), description: __('Sent to the admin when an appointment is confirmed.', 'schedula-smart-appointment-booking'), enableKey: 'enableConfirmedToAdmin', subjectKey: 'confirmedToAdminSubject', bodyKey: 'confirmedToAdminBody' },
    cancelledToAdmin: { title: __('Appointment Cancelled (to Admin)', 'schedula-smart-appointment-booking'), description: __('Sent to the admin when an appointment is cancelled.', 'schedula-smart-appointment-booking'), enableKey: 'enableCancelledToAdmin', subjectKey: 'cancelledToAdminSubject', bodyKey: 'cancelledToAdminBody' },
    rejectedToAdmin: { title: __('Appointment Rejected (to Admin)', 'schedula-smart-appointment-booking'), description: __('Sent to the admin when an appointment is rejected.', 'schedula-smart-appointment-booking'), enableKey: 'enableRejectedToAdmin', subjectKey: 'rejectedToAdminSubject', bodyKey: 'rejectedToAdminBody' },
};

const fetchSettings = async () => {
  isLoading.value = true;
  try {
    const response = await notificationsApi.getSettings();
    Object.assign(notificationSettings, response.data);

    const simpleTemplate = templates.value.find(t => t.id === 'simple');
    if (simpleTemplate) {
      for (const key in notificationTemplates) {
        const bodyKey = notificationTemplates[key].bodyKey;
        if (!notificationSettings[bodyKey] || (typeof notificationSettings[bodyKey] === 'string' && notificationSettings[bodyKey].trim() === '')) {
          notificationSettings[bodyKey] = simpleTemplate.html;
        }
      }
    }
  } catch (error) {
    error(__('Failed to load settings.', 'schedula-smart-appointment-booking'));
  } finally {
    isLoading.value = false;
  }
};

const fetchEmailLog = async () => {
    isEmailLogLoading.value = true;
    try {
        const params = {
            search: emailSearchQuery.value,
            page: emailCurrentPage.value,
            per_page: emailItemsPerPage.value,
            sort_by: emailSortBy.value,
            sort_direction: emailSortDirection.value,
        };
        const response = await notificationsApi.getEmailLog(params);
        emailLog.value = response.data.logs;
        totalEmails.value = response.data.total_items;
    } catch (err) {
        error(err.response?.data?.message || __('Failed to load email log.', 'schedula-smart-appointment-booking'));
    } finally {
        isEmailLogLoading.value = false;
    }
};

const saveSettings = async () => {
  isSaving.value = true;
  try {
    await notificationsApi.saveSettings(notificationSettings);
    success(__('Settings saved successfully!', 'schedula-smart-appointment-booking'));
  } catch (error) {
    error(__('Failed to save settings.', 'schedula-smart-appointment-booking'));
  } finally {
    isSaving.value = false;
  }
};

const resetSettings = async () => {
    isResetting.value = true; // Start resetting state
    try {
        await notificationsApi.resetSettings();
        await fetchSettings();
        success(__('Settings reset to default!', 'schedula-smart-appointment-booking'));
    } catch (error) {
        error(__('Failed to reset settings.', 'schedula-smart-appointment-booking'));
    } finally {
        isResetting.value = false; // End resetting state
    }
};

const deleteSelectedEmails = async () => {
  // This method now just opens the bulk delete modal
  openBulkDeleteEmailModal();
};

const openBulkDeleteEmailModal = () => {
  if (selectedEmails.value.length === 0) {
    info(__('Please select at least one email to delete.', 'schedula-smart-appointment-booking'));
    return;
  }
  showBulkDeleteEmailModal.value = true;
};

const confirmBulkDeleteEmails = async () => {
  isDeletingEmails.value = true;
  try {
    await notificationsApi.deleteEmailLog({ ids: selectedEmails.value });
    selectedEmails.value = [];
    await fetchEmailLog();
    success(__('Selected emails deleted successfully!', 'schedula-smart-appointment-booking'));
  } catch (err) {
    error(err.response?.data?.message || __('Failed to delete emails.', 'schedula-smart-appointment-booking'));
  } finally {
    isDeletingEmails.value = false;
    closeEmailDeleteModals();
  }
};

const handleDeleteEmail = (email) => {
  emailToDelete.value = email;
  showDeleteEmailModal.value = true;
};

const confirmDeleteEmail = async () => {
  if (!emailToDelete.value) return;
  isDeletingEmails.value = true;
  try {
    await notificationsApi.deleteEmailLog({ ids: [emailToDelete.value.id] });
    // Remove the deleted email from selectedEmails if it was selected
    selectedEmails.value = selectedEmails.value.filter(id => id !== emailToDelete.value.id);
    await fetchEmailLog();
    success(__('Email deleted successfully!', 'schedula-smart-appointment-booking'));
  } catch (err) {
    error(err.response?.data?.message || __('Failed to delete email.', 'schedula-smart-appointment-booking'));
  } finally {
    isDeletingEmails.value = false;
    closeEmailDeleteModals();
  }
};

const closeEmailDeleteModals = () => {
  showDeleteEmailModal.value = false;
  showBulkDeleteEmailModal.value = false;
  emailToDelete.value = null;
};

const openEmailViewModal = async (email) => {
  showEmailModal.value = true;
  viewingEmail.value = email; // Show basic data immediately
  isFetchingEmailBody.value = true;
  try {
    const response = await notificationsApi.getSingleEmailLog(email.id);
    viewingEmail.value = response.data; // Update with full data including body
  } catch (err) {
    error(err.response?.data?.message || __('Failed to load email body.', 'schedula-smart-appointment-booking'));
  } finally {
    isFetchingEmailBody.value = false;
  }
};

const closeEmailViewModal = () => {
  showEmailModal.value = false;
  viewingEmail.value = null;
};

const selectAllEmails = (event) => {
    if (event.target.checked) {
        selectedEmails.value = emailLog.value.map(email => email.id);
    } else {
        selectedEmails.value = [];
    }
};

const openEditor = (bodyKey) => {
  const subjectKey = bodyKey.replace('Body', 'Subject');
  const headerBgColorKey = bodyKey.replace('Body', 'HeaderBgColor');
  const headerTextColorKey = bodyKey.replace('Body', 'HeaderTextColor');
  editingEmailBody.key = bodyKey;
  editingEmailBody.content = {
      body: notificationSettings[bodyKey],
      headerBgColor: notificationSettings[headerBgColorKey] || '#3498db',
      headerTextColor: notificationSettings[headerTextColorKey] || '#ffffff'
  };
  editingEmailBody.subject = notificationSettings[subjectKey];
  showEditor.value = true;
};

const closeEditor = () => {
  showEditor.value = false;
};

const updateEmailBody = (newContent) => {
  if (editingEmailBody.key) {
    const bodyKey = editingEmailBody.key;
    const headerBgColorKey = bodyKey.replace('Body', 'HeaderBgColor');
    const headerTextColorKey = bodyKey.replace('Body', 'HeaderTextColor');

    notificationSettings[bodyKey] = newContent.body;
    notificationSettings[headerBgColorKey] = newContent.headerBgColor;
    notificationSettings[headerTextColorKey] = newContent.headerTextColor;

    saveSettings();
  }
};

const updateSmtpSettings = (newSettings) => {
    Object.assign(notificationSettings, newSettings);
    saveSettings();
};

watch(currentTab, (newTab) => {
    if (newTab === 'email_log') {
        fetchEmailLog();
    }
});

onMounted(() => {
  fetchSettings();
  useAdminAppearance().fetchAppearanceSettings();
});
</script>

<style scoped>
.content-card {
  background-color: var(--admin-card-bg-color);
  color: var(--admin-card-text-color);
  border-radius: 0.5rem;
}
.input-field {
  background-color: var(--admin-input-bg-color);
  color: var(--admin-input-text-color);
  border-color: var(--admin-input-border-color);
}
</style>
