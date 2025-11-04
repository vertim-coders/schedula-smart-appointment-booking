<template>
  <transition name="modal-fade">
    <div class="fixed inset-0 z-50 flex items-start justify-center pt-16 bg-black bg-opacity-50 overflow-y-auto" @click.self="$emit('close')">
      <div class="rounded-lg shadow-xl w-full max-w-2xl mx-4 my-4 p-6 relative modal-content" @click.stop :style="{ backgroundColor: 'var(--admin-card-bg-color)' }">
        <h3 class="text-lg font-bold mb-4" :style="{ color: 'var(--admin-text-color)' }">{{ __('Edit Email Body', 'schedula-smart-appointment-booking') }}</h3>
        
        <EmailTemplateSelector @select="onTemplateSelected" :current-body="content" />

        <div class="flex items-center space-x-4 my-4">
          <div>
            <label class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-text-color)' }">{{ __('Header Background', 'schedula-smart-appointment-booking') }}</label>
            <input type="color" v-model="headerBgColor" class="p-1 h-8 w-14 block bg-white border border-gray-200 cursor-pointer rounded-lg disabled:opacity-50 disabled:pointer-events-none">
          </div>
          <div>
            <label class="block text-sm font-medium mb-1" :style="{ color: 'var(--admin-text-color)' }">{{ __('Header Text', 'schedula-smart-appointment-booking') }}</label>
            <input type="color" v-model="headerTextColor" class="p-1 h-8 w-14 block bg-white border border-gray-200 cursor-pointer rounded-lg disabled:opacity-50 disabled:pointer-events-none">
          </div>
        </div>

        <div class="editor-toolbar mb-4 p-2 rounded-md" :style="{ backgroundColor: 'var(--admin-input-bg-color)' }">
          <button @click="execCmd('bold')" class="px-3 py-1 rounded-md mr-2" :style="{ backgroundColor: 'var(--admin-button-secondary-bg)', color: 'var(--admin-button-secondary-text)' }"><b>{{ __('B', 'schedula-smart-appointment-booking') }}</b></button>
          <select @change="execCmd('fontName', $event.target.value)" class="px-3 py-1 rounded-md mr-2 input-field">
            <option value="Arial">Arial</option>
            <option value="Verdana">Verdana</option>
            <option value="Times New Roman">Times New Roman</option>
            <option value="Courier New">Courier New</option>
            <option value="Georgia">Georgia</option>
          </select>
          <select @change="execCmd('fontSize', $event.target.value)" class="px-3 py-1 rounded-md mr-2 input-field">
            <option v-for="size in 7" :key="size" :value="size">{{ size }}</option>
          </select>
        </div>

        <div ref="editor" class="editor-content p-4 border rounded-md min-h-[300px] overflow-y-auto" contenteditable="true" :style="{ borderColor: 'var(--admin-border-color)', color: 'var(--admin-text-color)' }" v-html="content"></div>

        <div class="flex justify-end space-x-4 mt-6">
          <button @click="$emit('close')" class="px-4 py-2 rounded-md" :style="{ backgroundColor: 'var(--admin-button-third-bg)', color: 'var(--admin-button-secondary-text)' }">{{ __('Cancel', 'schedula-smart-appointment-booking') }}</button>
          <button @click="save" class="px-4 py-2 rounded-md" :style="{ backgroundColor: 'var(--admin-link-indigo-bg)', color: 'var(--admin-link-indigo-text)' }">{{ __('Save', 'schedula-smart-appointment-booking') }}</button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, onMounted, defineProps, defineEmits, watch } from 'vue';
import { __ } from '@wordpress/i18n';
import EmailTemplateSelector from './EmailTemplateSelector.vue';
import { useEmailTemplates } from '../../composables/useEmailTemplates.js';

const props = defineProps({
    modelValue: Object, // Now expects an object { body, headerBgColor, headerTextColor }
    subject: String,
});
const emit = defineEmits(['update:modelValue', 'close']);

const editor = ref(null);
const content = ref(props.modelValue.body || '');
const selectedTemplateHtml = ref('');
const headerBgColor = ref(props.modelValue.headerBgColor || '#3498db');
const headerTextColor = ref(props.modelValue.headerTextColor || '#ffffff');

const { templates } = useEmailTemplates();

onMounted(() => {
  if (editor.value) {
    editor.value.innerHTML = content.value;
    
    // If the content seems to be using a template, find the raw template HTML.
    // This allows color changes to work on existing templated emails.
    if (content.value && content.value.includes('id="email-body-content"')) {
        // Try to parse colors from existing HTML. This is a fallback for older saved templates.
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = content.value;
        const headerCell = tempDiv.querySelector('td[style*="background-color"]');
        if (headerCell && !props.modelValue.headerBgColor) {
            headerBgColor.value = headerCell.style.backgroundColor;
        }
        const headerText = tempDiv.querySelector('h1[style*="color"]');
        if (headerText && !props.modelValue.headerTextColor) {
            headerTextColor.value = headerText.style.color;
        }

        let foundTemplate = null;
        if (content.value.includes("font-family: Georgia, 'Times New Roman', Times, serif;")) {
            foundTemplate = templates.value.find(t => t.id === 'elegant');
        } else if (content.value.includes("box-shadow: 0 2px 4px rgba(0,0,0,0.1);")) {
            foundTemplate = templates.value.find(t => t.id === 'modern');
        } else if (content.value.includes("border: 1px solid #dddddd;")) {
            foundTemplate = templates.value.find(t => t.id === 'simple');
        }

        if (foundTemplate) {
            selectedTemplateHtml.value = foundTemplate.html;
            updateEditorOnColorChange(); // Re-apply template with correct colors
        }
    } else if (!content.value) {
        // If content is empty, apply the 'simple' template by default
        const simpleTemplate = templates.value.find(t => t.id === 'simple');
        if (simpleTemplate) {
            onTemplateSelected(simpleTemplate.html);
        }
    }
  }
});

const execCmd = (command, value = null) => {
  document.execCommand(command, false, value);
  editor.value.focus();
};

const save = () => {
  emit('update:modelValue', {
    body: editor.value.innerHTML,
    headerBgColor: headerBgColor.value,
    headerTextColor: headerTextColor.value,
  });
  emit('close');
};

const onTemplateSelected = (html) => {
  selectedTemplateHtml.value = html; // Keep track of the current raw template

  if (!editor.value) return;

  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = editor.value.innerHTML;
  const bodyContentElement = tempDiv.querySelector('#email-body-content');
  const userContent = bodyContentElement ? bodyContentElement.innerHTML : editor.value.innerHTML;

  if (html) { // A new template is selected
    let newContent = html;
    newContent = newContent.replace(/{header_bg_color}/g, headerBgColor.value);
    newContent = newContent.replace(/{header_text_color}/g, headerTextColor.value);
    newContent = newContent.replace(/{email_subject}/g, props.subject || '');
    newContent = newContent.replace('{email_body}', userContent);
    editor.value.innerHTML = newContent;
  } else { // This case should ideally not be reached if 'simple' is always default
    editor.value.innerHTML = userContent;
  }
};

const updateEditorOnColorChange = () => {
    if (!editor.value || !selectedTemplateHtml.value) return;

    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = editor.value.innerHTML;
    const bodyContentElement = tempDiv.querySelector('#email-body-content');
    const userContent = bodyContentElement ? bodyContentElement.innerHTML : editor.value.innerHTML;

    let newContent = selectedTemplateHtml.value;
    newContent = newContent.replace(/{header_bg_color}/g, headerBgColor.value);
    newContent = newContent.replace(/{header_text_color}/g, headerTextColor.value);
    newContent = newContent.replace(/{email_subject}/g, props.subject || '');
    newContent = newContent.replace('{email_body}', userContent);
    editor.value.innerHTML = newContent;
}

watch([headerBgColor, headerTextColor], () => {
  // When colors change, re-apply the current template with the new colors
  updateEditorOnColorChange();
});

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
