import { ref } from 'vue';

const toasts = ref([]);
let toastId = 0;

export const useToast = () => {
  const addToast = (message, type = 'success', duration = 4000) => {
    const id = ++toastId;
    const toast = {
      id,
      message,
      type,
      duration,
      visible: true
    };
    
    toasts.value.push(toast);
    
    if (duration > 0) {
      setTimeout(() => {
        removeToast(id);
      }, duration);
    }
    
    return id;
  };
  
  const removeToast = (id) => {
    const index = toasts.value.findIndex(toast => toast.id === id);
    if (index > -1) {
      toasts.value[index].visible = false;
      setTimeout(() => {
        toasts.value.splice(index, 1);
      }, 300);
    }
  };
  
  const success = (message, duration = 4000) => addToast(message, 'success', duration);
  const error = (message, duration = 6000) => addToast(message, 'error', duration);
  const warning = (message, duration = 5000) => addToast(message, 'warning', duration);
  const info = (message, duration = 4000) => addToast(message, 'info', duration);
  
  return {
    toasts,
    removeToast,
    success,
    error,
    warning,
    info
  };
};