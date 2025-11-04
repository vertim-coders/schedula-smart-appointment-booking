<template>
  <transition name="modal-fade">
    <div v-if="show"
         class="fixed inset-0 z-50 flex items-center justify-center bg-opacity-60 p-4"
         @click.self="closeModal" :style="{ backgroundColor: 'rgba(0, 0, 0, 0.6)' }">
      <div class="rounded-lg shadow-xl w-full max-w-xl flex flex-col max-h-[95vh] modal-content">
        
        <!-- Modal Header -->
        <div class="flex-shrink-0 flex justify-between items-center p-3 border-b" :style="{ borderColor: 'var(--admin-border-color)' }">
          <h3 class="text-xl font-semibold" :style="{ color: 'var(--admin-text-color)' }">
            <i class="fas fa-receipt mr-3" :style="{ color: 'var(--admin-link-indigo-bg)' }"></i>
            {{ __('Payment Details', 'schedula-smart-appointment-booking') }}
          </h3>
          <button @click="closeModal"
                  class="bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm p-1.5 ml-auto inline-flex items-center">
            <i class="fas fa-times fa-lg"></i>
          </button>
        </div>

        <!-- Scrollable Modal Body -->
        <div class="p-6 md:p-8 overflow-y-auto">
          <div v-if="loading" class="text-center py-10">
            <i class="fas fa-spinner fa-spin text-4xl" :style="{ color: 'var(--admin-link-indigo-bg)' }"></i>
            <p class="mt-4" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Loading Receipt...', 'schedula-smart-appointment-booking') }}</p>
          </div>
          <div v-else-if="error" class="text-center py-10 p-6 rounded-lg" :style="{ backgroundColor: 'var(--admin-suggestion-red-bg)' }">
            <i class="fas fa-exclamation-circle text-4xl" :style="{ color: 'var(--admin-suggestion-red-text)' }"></i>
            <p class="mt-4 font-semibold" :style="{ color: 'var(--admin-suggestion-red-text)' }">{{ __('Error Loading Receipt', 'schedula-smart-appointment-booking') }}</p>
            <p :style="{ color: 'var(--admin-suggestion-red-text)' }">{{ error }}</p>
          </div>
          
          <!-- Receipt Content -->
          <div v-else-if="receiptData" class="receipt-content p-6" ref="receiptContent" :style="{ backgroundColor: 'var(--admin-card-bg-color)' }">
            <!-- Header -->
            <div class="text-center mb-10">
              <h1 class="text-4xl font-bold tracking-wider" :style="{ color: 'var(--admin-text-color)' }">SCHEDULA</h1>
              <p class="mt-1" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Appointment & Payment Receipt', 'schedula-smart-appointment-booking') }}</p>
            </div>
            
            <div class="p-4 rounded-lg text-center mb-8" :style="{ backgroundColor: 'var(--admin-badge-green-bg)' }">
              <span class="font-semibold" :style="{ color: 'var(--admin-badge-green-text)' }">
                <i class="fas fa-check-circle mr-2"></i>{{ __('Payment Completed', 'schedula-smart-appointment-booking') }}
              </span>
            </div>
            
            <!-- Main Info Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
              <!-- Client Information -->
              <div class="info-section">
                <h4 class="section-title"><i class="fas fa-user mr-3"></i>{{ __('Client Information', 'schedula-smart-appointment-booking') }}</h4>
                <div class="section-content">
                  <p :style="{ color: 'var(--admin-card-text-color)' }">{{ receiptData.customer_full_name }}</p>
                  <p :style="{ color: 'var(--admin-card-text-color)' }">{{ receiptData.customer_email }}</p>
                  <p :style="{ color: 'var(--admin-card-text-color)' }">{{ receiptData.customer_phone || __('N/A', 'schedula-smart-appointment-booking') }}</p>
                </div>
              </div>

              <!-- Appointment Details -->
              <div class="info-section">
                <h4 class="section-title"><i class="fas fa-calendar-alt mr-3"></i>{{ __('Appointment Details', 'schedula-smart-appointment-booking') }}</h4>
                <div class="section-content">
                  <p :style="{ color: 'var(--admin-card-text-color)' }"><strong>{{ __('Date:', 'schedula-smart-appointment-booking') }}</strong> {{ formatDate(receiptData.start_datetime) }}</p>
                  <p :style="{ color: 'var(--admin-card-text-color)' }"><strong>{{ __('Time:', 'schedula-smart-appointment-booking') }}</strong> {{ formatTime(receiptData.start_datetime) }} - {{ formatTime(receiptData.end_datetime) }}</p>
                  <p :style="{ color: 'var(--admin-card-text-color)' }"><strong>{{ __('Duration:', 'schedula-smart-appointment-booking') }}</strong> {{ receiptData.duration }} {{ __('minutes', 'schedula-smart-appointment-booking') }}</p>
                </div>
              </div>

              <!-- Service Information -->
              <div class="info-section">
                <h4 class="section-title"><i class="fas fa-cut mr-3"></i>{{ __('Service Information', 'schedula-smart-appointment-booking') }}</h4>
                <div class="section-content">
                  <p :style="{ color: 'var(--admin-card-text-color)' }">{{ receiptData.service_title || __('N/A', 'schedula-smart-appointment-booking') }}</p>
                </div>
              </div>

              <!-- Staff Information -->
              <div class="info-section">
                <h4 class="section-title"><i class="fas fa-user-tie mr-3"></i>{{ __('Staff Information', 'schedula-smart-appointment-booking') }}</h4>
                <div class="section-content">
                  <p :style="{ color: 'var(--admin-card-text-color)' }">{{ receiptData.staff_name || __('N/A', 'schedula-smart-appointment-booking') }}</p>
                  <p :style="{ color: 'var(--admin-card-text-color)' }">{{ receiptData.staff_email || __('N/A', 'schedula-smart-appointment-booking') }}</p>
                </div>
              </div>
            </div>

            <!-- Payment Information -->
            <div class="mt-8">
              <div class="p-6 rounded-lg" :style="{ backgroundColor: 'var(--admin-input-border-color)' }">
                <h4 class="section-title border-none mb-4"><i class="fas fa-credit-card mr-3"></i>{{ __('Payment Information', 'schedula-smart-appointment-booking') }}</h4>
                <div class="flex justify-between items-start">
                  <div>
                    <p :style="{ color: 'var(--admin-card-text-color)' }"><strong>{{ __('Payment Date:', 'schedula-smart-appointment-booking') }}</strong> {{ formatDate(receiptData.payment_date) }}</p>
                    <p :style="{ color: 'var(--admin-card-text-color)' }"><strong>{{ __('Payment Type:', 'schedula-smart-appointment-booking') }}</strong> {{ receiptData.payment_type_display }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-lg" :style="{ color: 'var(--admin-card-text-color)' }">{{ __('Total', 'schedula-smart-appointment-booking') }}</p>
                    <p class="text-4xl font-bold" :style="{ color: 'var(--admin-text-color)' }">{{ formatPrice(receiptData.amount) }}</p>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Notes -->
            <div v-if="receiptData.appointment_notes" class="mt-6 info-section border-t pt-6">
              <h4 class="section-title"><i class="fas fa-sticky-note mr-3"></i>{{ __('Notes', 'schedula-smart-appointment-booking') }}</h4>
              <p class="section-content" :style="{ color: 'var(--admin-card-text-color)' }">{{ receiptData.appointment_notes }}</p>
            </div>
            
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex-shrink-0 flex items-center justify-end p-4 border-t rounded-b-lg" :style="{ borderColor: 'var(--admin-border-color)', backgroundColor: 'var(--admin-input-border-color)' }">
          <button @click="closeModal"
                  class="px-5 py-2.5 text-sm font-medium rounded-lg border focus:z-10 focus:ring-4" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', borderColor: 'var(--admin-border-color)', color: 'var(--admin-button-secondary-text)' }">
            {{ __('Close', 'schedula-smart-appointment-booking') }}
          </button>
          <button @click="downloadAsPDF"
                  class="ml-3 px-5 py-2.5 text-sm font-medium rounded-lg focus:ring-4 focus:outline-none" :style="{ backgroundColor: 'var(--admin-link-indigo-bg)', color: 'var(--admin-link-indigo-text)' }"
                  :disabled="isDownloading">
            <i v-if="!isDownloading" class="fas fa-download mr-2"></i>
            <i v-else class="fas fa-spinner fa-spin mr-2"></i>
            {{ isDownloading ? __('Downloading...', 'schedula-smart-appointment-booking') : __('Download PDF', 'schedula-smart-appointment-booking') }}
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { __ } from '@wordpress/i18n';
import { ref } from 'vue';
import html2canvas from 'html2canvas';
import { useGlobalSettings } from '../../composables/useGlobalSettings.js';

const props = defineProps({
  show: Boolean,
  loading: Boolean,
  error: String,
  receiptData: Object,
});

const emit = defineEmits(['close', 'download-success', 'download-error']);
const isDownloading = ref(false);
const receiptContent = ref(null);

const { formatPrice, formatTime } = useGlobalSettings();

const closeModal = () => {
  emit('close');
};

const formatDate = (datetime) => {
  if (!datetime) return __('N/A', 'schedula-smart-appointment-booking');
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(datetime).toLocaleDateString(undefined, options);
};

const downloadAsPDF = async () => {
  if (!receiptContent.value) {
    emit('download-error', __('Receipt content not available.', 'schedula-smart-appointment-booking'));
    return;
  }

  // Vérifier si jsPDF est disponible
  if (typeof window.jspdf === 'undefined') {
    emit('download-error', __('PDF library not loaded. Please refresh the page.', 'schedula-smart-appointment-booking'));
    return;
  }

  isDownloading.value = true;
  try {
    const canvas = await html2canvas(receiptContent.value, {
      scale: 2,
      useCORS: true,
      backgroundColor: '#ffffff',
    });
    const imgData = canvas.toDataURL('image/png');
    
    // Accéder à jsPDF depuis l'objet global window
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF('p', 'mm', 'a4');
    const pdfWidth = pdf.internal.pageSize.getWidth();
    const imgProps = pdf.getImageProperties(imgData);
    const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
    
    pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
    
    const fileName = `receipt-${props.receiptData.payment_id}-${new Date().toISOString().split('T')[0]}.pdf`;
    pdf.save(fileName);
    
    emit('download-success', __('Receipt downloaded successfully!', 'schedula-smart-appointment-booking'));
  } catch (error) {
    console.error(__('Failed to generate PDF:', 'schedula-smart-appointment-booking'), error);
    emit('download-error', __('Failed to generate PDF. See console for details.', 'schedula-smart-appointment-booking'));
  } finally {
    isDownloading.value = false;
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
.modal-fade-enter-active .bg-white,
.modal-fade-leave-active .bg-white {
  transition: transform 0.3s ease;
}
.modal-fade-enter-from .bg-white,
.modal-fade-leave-to .bg-white {
  transform: translateY(20px) scale(0.98);
}

.receipt-content {
  font-family: 'Inter', sans-serif;
  color: #374151;
}

.section-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #4338ca;
  padding-bottom: 0.75rem;
  margin-bottom: 0.75rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
}
.section-title i {
  color: #818cf8;
}

.section-content {
  font-size: 0.95rem;
  line-height: 1.6;
}
.section-content p {
  margin-bottom: 0.25rem;
}
.section-content strong {
  font-weight: 500;
  color: #1f2937;
}
</style>