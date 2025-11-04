<template>
  <div class="max-w-4xl mx-auto p-4 sm:p-6">
    <!-- Hero strip with enhanced design -->
    <div class="relative p-6 sm:p-8 rounded-2xl shadow-lg mb-8 overflow-hidden content-card"
         :style="{ backgroundColor: 'var(--admin-brand-hero, #00183C)' }">
      <!-- Background pattern -->
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 right-0 w-32 h-32 rounded-full bg-white/20 transform translate-x-16 -translate-y-16"></div>
        <div class="absolute bottom-0 left-0 w-24 h-24 rounded-full bg-white/10 transform -translate-x-12 translate-y-12"></div>
      </div>
      
      <div class="relative flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <div class="flex items-center gap-4">
          <div class="p-3 rounded-xl bg-white/10 backdrop-blur-sm border border-white/20">
            <img src="@/assets/icons/logo.png" alt="Schedula" class="h-8 w-8 sm:h-10 sm:w-10 object-contain">
          </div>
        </div>
        <div class="flex-1">
          <div class="text-white font-bold text-xl sm:text-2xl leading-tight mb-2">
            {{ __('Unlock Pro features for faster, smarter booking.', 'schedula-smart-appointment-booking') }}
          </div>
          <div class="text-white/80 text-sm sm:text-base">
            {{ __('Built for service businesses that want to scale without friction.', 'schedula-smart-appointment-booking') }}
          </div>
        </div>
      </div>
    </div>

    <!-- Main content card -->
    <div class="rounded-2xl shadow-xl overflow-hidden content-card"
         :style="{ backgroundColor: 'var(--admin-card-bg-color)', border: '1px solid var(--admin-border-color)' }">

      <!-- Header -->
      <div class="p-6 sm:p-8 pb-4">
        <div class="text-center mb-8">
          <h3 class="text-2xl sm:text-3xl font-bold mb-3" :style="{ color: 'var(--admin-text-color)' }">
            {{ __('Why Go Pro?', 'schedula-smart-appointment-booking') }}
          </h3>
          <p class="text-lg opacity-80" :style="{ color: 'var(--admin-card-text-color)' }">
            {{ __('Transform your scheduling experience with Schedula Pro. Get access to powerful features designed to save you time, streamline your operations, and delight your clients', 'schedula-smart-appointment-booking') }}
          </p>
        </div>

        <!-- Enhanced feature grid -->
        <div class="grid gap-4 sm:gap-6 md:grid-cols-2 lg:grid-cols-3">
          <div v-for="(f, index) in features" :key="f.key"
               class="group relative p-5 sm:p-6 rounded-xl transition-all duration-300 hover:scale-105 hover:shadow-lg"
               :style="{ 
                 backgroundColor: 'var(--admin-input-bg-color)', 
                 border: '1px solid var(--admin-border-color)' 
               }">
            
            <!-- Hover effect overlay -->
            <div class="absolute inset-0 rounded-xl bg-gradient-to-br opacity-0 group-hover:opacity-10 transition-opacity duration-300"
                 :style="{ background: `linear-gradient(135deg, ${f.color}, ${f.color}80)` }"></div>
            
            <div class="relative">
              <!-- Icon with enhanced styling -->
              <div class="inline-flex items-center justify-center h-12 w-12 rounded-xl text-white mb-4 group-hover:scale-110 transition-transform duration-300"
                   :style="{ backgroundColor: f.color, boxShadow: `0 4px 20px ${f.color}40` }">
                <i :class="f.icon" class="text-lg"></i>
              </div>
              
              <!-- Content -->
              <div>
                <h4 class="font-bold text-lg mb-2 group-hover:text-opacity-90 transition-all duration-300" 
                    :style="{ color: 'var(--admin-text-color)' }">
                  {{ __(f.title, 'schedula-smart-appointment-booking') }}
                </h4>
                <p class="text-sm leading-relaxed opacity-80" 
                   :style="{ color: 'var(--admin-card-text-color)' }">
                  {{ __(f.desc, 'schedula-smart-appointment-booking') }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- CTA Section at bottom -->
      <div class="border-t p-6 sm:p-8 bg-gradient-to-r"
           :style="{ 
             borderColor: 'var(--admin-border-color)',
             background: 'linear-gradient(135deg, var(--admin-input-bg-color) 0%, var(--admin-card-bg-color) 100%)'
           }">
        <div class="text-center max-w-md mx-auto">
          <div class="mb-6">
            <div class="text-2xl font-bold mb-2" :style="{ color: 'var(--admin-text-color)' }">
              {{ __('Ready to Transform Your Business?', 'schedula-smart-appointment-booking') }}
            </div>
          </div>
          
          <!-- Enhanced CTA Button -->
          <a href="https://vertimcoders.com/tests-product-landing/apps/schedula-for-woocommerce/" 
             target="_blank" 
             rel="noopener noreferrer" 
             class="inline-block w-full sm:w-auto bg-[#00183C] hover:bg-[#001a42] text-white font-bold px-8 py-4 rounded-xl text-lg transition-colors duration-300">
            <div class="flex items-center justify-center gap-2">
              <span>{{ __('Upgrade to Schedula Pro Now', 'schedula-smart-appointment-booking') }}</span>
              <i class="fas fa-arrow-right text-sm"></i>
            </div>
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { __ } from '@wordpress/i18n'

/**
 * Enhanced features with better descriptions
 */
const features = [
  {
    key: 'gcal',
    title: 'Google Calendar Integration',
    desc: 'Automatically sync appointments and avoid double bookings with real-time calendar updates.',
    color: '#4285F4',
    icon: 'fab fa-google'
  },
  {
    key: 'multi',
    title: 'Multi-Service Booking',
    desc: 'Allow clients to book multiple services in one seamless flow, increasing your average order value.',
    color: '#F59E0B',
    icon: 'fas fa-layer-group'
  },
  {
    key: 'forms',
    title: 'Modern Booking Forms',
    desc: 'Beautiful, conversion-optimized forms with guided steps that turn more visitors into customers.',
    color: '#10B981',
    icon: 'fas fa-wpforms'
  },
  {
    key: 'unlm',
    title: 'Unlimited Everything',
    desc: 'No limits on services, staff, or bookings. Scale your business without worrying about restrictions.',
    color: '#8B5CF6',
    icon: 'fas fa-infinity'
  },
  {
    key: 'pay',
    title: 'Online Payments',
    desc: 'Accept payments instantly via Stripe & PayPal with automatic booking confirmations.',
    color: '#EF4444',
    icon: 'fas fa-credit-card'
  },
  {
    key: 'cust',
    title: 'Advanced Customization',
    desc: 'Complete control over colors, fonts, and layouts to match your unique brand identity.',
    color: '#14B8A6',
    icon: 'fas fa-palette'
  }
]
</script>

<style scoped>
/* Enhanced animations and effects */
.content-card {
  transition: all 0.3s ease;
}

/* Responsive improvements */
@media (max-width: 640px) {
  .group:hover {
    transform: scale(1.02);
  }
}

/* Custom hover effects */
.group:hover .fas,
.group:hover .fab {
  animation: bounce 0.6s ease-in-out;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-2px); }
}
</style>