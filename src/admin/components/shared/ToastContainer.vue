<template>
    <teleport to="body">
      <div class="toast-container">
        <transition-group name="toast" tag="div">
          <div
            v-for="toast in toasts"
            :key="toast.id"
            :class="['toast', 'toast-' + toast.type, { 'toast-visible': toast.visible }]"
            @click="removeToast(toast.id)"
          >
            <div class="toast-icon">
              <i :class="getToastIcon(toast.type)"></i>
            </div>
            <div class="toast-content">
              <div class="toast-message">{{ toast.message }}</div>
            </div>
            <button class="toast-close" @click.stop="removeToast(toast.id)">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </transition-group>
      </div>
    </teleport>
  </template>
  
  <script setup>
  import { useToast } from '../../composables/useToast.js';
  
  const { toasts, removeToast } = useToast();
  
  const getToastIcon = (type) => {
    const icons = {
      success: 'fas fa-check-circle',
      error: 'fas fa-exclamation-circle',
      warning: 'fas fa-exclamation-triangle',
      info: 'fas fa-info-circle'
    };
    return icons[type] || icons.info;
  };
  </script>
  
  <style scoped>
  .toast-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    pointer-events: none;
    max-width: 420px;
  }
  
  .toast {
    display: flex;
    align-items: center;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    margin-bottom: 12px;
    padding: 16px;
    min-height: 60px;
    pointer-events: auto;
    cursor: pointer;
    transition: all 0.3s ease;
    border-left: 4px solid;
    max-width: 100%;
    word-wrap: break-word;
  }
  
  .toast-success {
    border-left-color: #10b981;
    background-color: #f0fdf4;
  }
  
  .toast-error {
    border-left-color: #ef4444;
    background-color: #fef2f2;
  }
  
  .toast-warning {
    border-left-color: #f59e0b;
    background-color: #fffbeb;
  }
  
  .toast-info {
    border-left-color: #3b82f6;
    background-color: #eff6ff;
  }
  
  .toast-icon {
    margin-right: 12px;
    font-size: 20px;
    flex-shrink: 0;
  }
  
  .toast-success .toast-icon { color: #10b981; }
  .toast-error .toast-icon { color: #ef4444; }
  .toast-warning .toast-icon { color: #f59e0b; }
  .toast-info .toast-icon { color: #3b82f6; }
  
  .toast-content {
    flex: 1;
    min-width: 0;
  }
  
  .toast-message {
    font-weight: 500;
    color: #374151;
    line-height: 1.4;
  }
  
  .toast-close {
    margin-left: 12px;
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    font-size: 14px;
    padding: 4px;
    border-radius: 4px;
    transition: background-color 0.2s ease;
    flex-shrink: 0;
  }
  
  .toast-close:hover {
    background-color: rgba(0, 0, 0, 0.05);
    color: #374151;
  }
  
  .toast-enter-active { transition: all 0.3s ease-out; }
  .toast-leave-active { transition: all 0.3s ease-in; }
  .toast-enter-from { opacity: 0; transform: translateX(100%); }
  .toast-leave-to { opacity: 0; transform: translateX(100%); }
  .toast-move { transition: transform 0.3s ease; }
  </style>