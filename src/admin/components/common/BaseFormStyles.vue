<template>
  <!-- This component doesn't render any visible content. -->
  <!-- It's purely for centralizing and importing global styles. -->
  <div style="display: none;"></div>
</template>

<script setup>
// No script logic needed for a pure style component
</script>

<style>
/* These styles are intentionally NOT scoped.
   They are meant to be global and provide consistent base styling
   for form elements across your application.
*/

/* Standardized Input System Containers */
.standardized-input-container {
  display: flex !important; /* Ensure flex behavior */
  width: 100% !important;
  min-height: 44px !important; /* Consistent height for containers */
  position: relative !important;
  align-items: center !important; /* Vertically align content */
  box-sizing: border-box !important; /* Ensure consistent box model */
}

/* Base styling for all single-line inputs (input, select, textarea) */
.standardized-input {
  width: 100% !important;
  height: 44px !important;
  min-height: 44px !important;
  padding: 12px 16px !important;
  border: 1px solid var(--admin-input-border-color) !important;
  border-radius: 8px !important; /* Fully rounded for standalone inputs */
  font-size: 14px !important;
  line-height: 1.25 !important;
  color: var(--admin-input-text-color) !important;
  background-color: var(--admin-input-bg-color) !important;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important; /* Tailwind shadow-sm */
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
  box-sizing: border-box !important;
}

.standardized-input:focus {
  outline: none !important;
  border-color: var(--admin-link-indigo-bg) !important;
  box-shadow: 0 0 0 3px var(--admin-link-indigo-bg-light) !important;
}

.standardized-input:disabled {
  background-color: var(--admin-border-color) !important;
  color: var(--admin-input-text-muted) !important;
  cursor: not-allowed !important;
}

.vue-tel-input{
  border: 1px solid var(--admin-input-border-color) !important;
  border-radius: 8px !important;
  font-size: 14px !important;
  background-color: var(--admin-input-bg-color) !important;
}
.vue-tel-input:focus-within {
  outline: none !important;
  border-color: var(--admin-link-indigo-bg) !important;
  box-shadow: 0 0 0 3px var(--admin-link-indigo-bg-light) !important;
}

.vue-tel-input .vti__input {
  background-color: var(--admin-input-bg-color) !important;
  color: var(--admin-input-text-color) !important;
}

.vue-tel-input .vti__dropdown, .vue-tel-input .vti__dropdown-list {
  background-color: var(--admin-card-bg-color) !important;
  border-color: var(--admin-border-color) !important;
}

.vue-tel-input .vti__dropdown-item.highlighted {
  background-color: var(--admin-link-indigo-bg) !important;
}

.standardized-input.phone-input{
  border: none !important;
  border-radius: 8px !important;
  box-shadow: none !important;
}
.standardized-input.phone-input:focus{
  border: none !important;
  box-shadow: none !important;
}

/* Specific style for main input within a container (e.g., with a button) */
.standardized-input-main {
  flex: 1 !important;
  min-width: 0 !important;
  height: 44px !important;
  min-height: 44px !important;
  padding: 12px 16px !important;
  border: 1px solid var(--admin-input-border-color) !important;
  border-radius: 8px !important;
  border-top-right-radius: 0 !important;
  border-bottom-right-radius: 0 !important;
  border-right: none !important;
  font-size: 14px !important;
  line-height: 1.25 !important;
  color: var(--admin-input-text-color) !important;
  background-color: var(--admin-input-bg-color) !important;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
  box-sizing: border-box !important;
}

.standardized-input-main:focus {
  outline: none !important;
  border-color: var(--admin-link-indigo-bg) !important;
  box-shadow: 0 0 0 3px var(--admin-link-indigo-bg-light) !important;
}

/* Button that sits next to the main input */
.standardized-button {
  flex-shrink: 0 !important;
  height: 44px !important;
  min-height: 44px !important;
  padding: 0 16px !important;
  border: 1px solid var(--admin-button-primary-bg) !important;
  border-radius: 8px !important;
  border-top-left-radius: 0 !important;
  border-bottom-left-radius: 0 !important;
  margin-left: 0 !important;
  font-size: 14px !important;
  font-weight: 500 !important;
  color: var(--admin-button-primary-text) !important;
  background-color: var(--admin-button-primary-bg) !important;
  cursor: pointer !important;
  transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out !important;
  white-space: nowrap !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
}

.standardized-button:hover {
  opacity: 0.9;
}

.standardized-button:focus {
  outline: none !important;
  box-shadow: 0 0 0 3px var(--admin-link-indigo-bg-light) !important;
}

.standardized-button:disabled {
  opacity: 0.6;
  cursor: not-allowed !important;
}

/* Special handling for textarea to allow vertical resizing */
.standardized-input.resize-vertical {
  height: auto !important;
  min-height: 80px !important;
  resize: vertical !important;
  padding: 10px 12px !important;
}

.standardized-input.select-appearance,
.standardized-input-main.select-appearance {
  appearance: none !important;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e") !important;
  background-position: right 12px center !important;
  background-repeat: no-repeat !important;
  background-size: 16px 12px !important;
  padding-right: 40px !important;
  height: 44px !important;
  min-height: 44px !important;
  max-width : 100% !important; 
}

.standardized-input select,
.standardized-input-main select {
  width: 100% !important;
  flex-grow: 1 !important;
}

.wp-core-ui .standardized-input select,
.wp-core-ui .standardized-input-main select {
  max-width: none !important;
}


/* Global resets to prevent external style interference */
input[type="text"],
input[type="email"],
input[type="tel"],
input[type="number"],
input[type="date"],
input[type="time"],
select,
textarea {
  font-family: inherit !important;
  font-size: 14px !important;
  box-sizing: border-box !important;
}
</style>