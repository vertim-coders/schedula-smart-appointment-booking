<template>
  <!-- Overlay du modal -->
  <transition name="modal-fade">
    <div
      v-if="show"
      :class="[
        'fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-50 overflow-y-auto',
        { 'p-4': !fullPage, 'p-0': fullPage } // Add padding only if not full page
      ]"
      @click.self="closeModal"
    >
      <!-- Conteneur du contenu du modal -->
      <div
        :class="[
          'bg-white rounded-xl shadow-2xl transform transition-all duration-300 ease-out-expo',
          {
            'w-full h-full rounded-none': fullPage, // Full page styles
            'max-h-[95vh] overflow-y-auto': !fullPage, // Max height and scroll for non-full page
            'relative': true // Ensure relative for any absolute positioning inside
          },
          maxWidthClass // Apply max width only if not full page
        ]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title"
      >
        <!-- En-tête du modal -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200 sticky top-0 bg-white z-10">
          <h3 id="modal-title" class="text-2xl font-semibold text-gray-900">
            <slot name="header">
              {{ title }}
            </slot>
          </h3>
          <button
            v-if="closeable"
            @click="closeModal"
            class="text-gray-400 hover:text-gray-600 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-full p-1"
            aria-label="Fermer le modal"
          >
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Corps du modal (où ira votre formulaire) -->
        <div class="p-6" :class="{ 'flex-1 overflow-y-auto': fullPage }">
          <slot name="body"></slot>
        </div>

        <!-- Pied de page du modal (pour les boutons d'action) -->
        <div v-if="$slots.footer" class="p-6 border-t border-gray-200 flex justify-end space-x-3 sticky bottom-0 bg-white z-10">
          <slot name="footer"></slot>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Modal Title',
  },
  closeable: {
    type: Boolean,
    default: true,
  },
  maxWidth: {
    type: String,
    default: '2xl', // Options: 'xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl'
  },
  fullPage: { // NEW PROP for full-page modal
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close']);

// Calcule la classe CSS pour la largeur maximale du modal
const maxWidthClass = computed(() => {
  if (props.fullPage) return ''; // No max-width for full page

  return {
    'sm:max-w-xs': props.maxWidth === 'xs',
    'sm:max-w-sm': props.maxWidth === 'sm',
    'sm:max-w-md': props.maxWidth === 'md',
    'sm:max-w-lg': props.maxWidth === 'lg',
    'sm:max-w-xl': props.maxWidth === 'xl',
    'sm:max-w-2xl': props.maxWidth === '2xl',
    'sm:max-w-3xl': props.maxWidth === '3xl',
    'sm:max-w-4xl': props.maxWidth === '4xl',
    'sm:max-w-5xl': props.maxWidth === '5xl',
    'sm:max-w-6xl': props.maxWidth === '6xl',
    'sm:max-w-7xl': props.maxWidth === '7xl',
  }[props.maxWidth];
});

// Gère la fermeture du modal
const closeModal = () => {
  if (props.closeable) {
    emit('close');
  }
};

// Gère la touche Échap pour fermer le modal
const handleEscape = (e) => {
  if (e.key === 'Escape' && props.show) {
    closeModal();
  }
};

// Empêche le défilement du corps de la page lorsque le modal est ouvert
watch(
  () => props.show,
  (newValue) => {
    if (newValue) {
      document.body.style.overflow = 'hidden'; // Prevents double scrollbar
      document.addEventListener('keydown', handleEscape);
    } else {
      document.body.style.overflow = null;
      document.removeEventListener('keydown', handleEscape);
    }
  },
  { immediate: true }
);

// Nettoyage des écouteurs d'événements lors du démontage du composant
onUnmounted(() => {
  document.body.style.overflow = null;
  document.removeEventListener('keydown', handleEscape);
});
</script>

<style scoped>
/* Transitions pour le modal */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

/* Animation pour le contenu du modal (légère échelle) */
.modal-fade-enter-active > div,
.modal-fade-leave-active > div {
  transition: transform 0.3s ease;
}

.modal-fade-enter-from > div,
.modal-fade-leave-to > div {
  transform: scale(0.95);
}

/* Override scale for full-page modal to prevent weird animation */
.modal-fade-enter-active.full-page > div,
.modal-fade-leave-active.full-page > div {
  transform: none; /* No scaling for full page */
}
</style>
