<template>
  <div class="mb-4">
    <label for="template-selector" class="block text-sm font-medium mb-1">{{ __('Select a Template', 'schedula-smart-appointment-booking') }}</label>
    <select id="template-selector" v-model="selectedTemplateId" @change="onTemplateSelect" class="px-3 py-1 rounded-md mr-2 input-field">
      <option v-for="template in templates" :key="template.id" :value="template.id">{{ template.name }}</option>
    </select>
  </div>
</template>

<script setup>
import { ref, defineEmits, defineProps, onMounted, nextTick } from 'vue';
import { __ } from '@wordpress/i18n';
import { useEmailTemplates } from '../../composables/useEmailTemplates.js';

const props = defineProps({
  currentBody: String,
});
const emit = defineEmits(['select']);
const selectedTemplateId = ref('simple'); // Default to 'simple'

const { templates } = useEmailTemplates();

onMounted(() => {
    let initialTemplateId = 'simple'; // Always default to simple

    if (props.currentBody) {
        if (props.currentBody.includes("font-family: Georgia, 'Times New Roman', Times, serif;")) {
            initialTemplateId = 'elegant';
        } else if (props.currentBody.includes("box-shadow: 0 2px 4px rgba(0,0,0,0.1);")) {
            initialTemplateId = 'modern';
        } else if (props.currentBody.includes("border: 1px solid #dddddd;")) {
            initialTemplateId = 'simple';
        }
    }
    selectedTemplateId.value = initialTemplateId;
    
    // Emit the selected template's HTML after the component has rendered and selectedTemplateId is set
    nextTick(() => {
        onTemplateSelect({ target: { value: selectedTemplateId.value } });
    });
});

const onTemplateSelect = (event) => {
  const selectedId = event.target.value;
  const selectedTemplate = templates.value.find(t => t.id === selectedId);
  if (selectedTemplate) {
    emit('select', selectedTemplate.html);
  } else {
    // Fallback to simple template if for some reason selectedId is not found (shouldn't happen with default 'simple')
    const simpleTemplate = templates.value.find(t => t.id === 'simple');
    if (simpleTemplate) {
        emit('select', simpleTemplate.html);
    } else {
        emit('select', ''); // Should not happen if 'simple' template always exists
    }
  }
};
</script>

<style scoped>
.input-field {
  background-color: var(--admin-input-bg-color);
  color: var(--admin-input-text-color);
  border-color: var(--admin-input-border-color);
}
</style>
