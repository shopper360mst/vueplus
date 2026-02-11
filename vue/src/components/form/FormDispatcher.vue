<script setup>
import { useI18n } from 'vue-i18n'
import BaseInput from './BaseInput.vue'
import MobileInput from './MobileInput.vue'
import NricInput from './NricInput.vue'
import FileUpload from './FileUpload.vue'
import PostcodeInput from './PostcodeInput.vue'
import BaseCheckbox from './BaseCheckbox.vue'

defineProps({
  field: {
    type: Object,
    required: true
  },
  formData: {
    type: Object,
    required: true
  },
  fileUploaded: Boolean,
  uploadReceiptValue: String,
  showPostcodeDropdown: Boolean,
  filteredPostcodes: Array
})

const { locale } = useI18n()
const emit = defineEmits(['file-change', 'show-helper', 'select-postcode'])
</script>

<template>
  <div class="form-field-wrapper">
    <!-- Standard Input -->
    <BaseInput
      v-if="field.component === 'input'"
      v-model="formData[field.alpine_model]"
      :label="field.label"
      :type="field.type"
      :placeholder="field.placeholder"
      :disabled="field.disabled"
      :maxlength="field.maxlength"
      :required="field.required"
      :helper="field.helper"
      :readonly="field.readonly"
      @show-helper="emit('show-helper')"
    />

    <!-- Mobile Prefix -->
    <MobileInput
      v-else-if="field.component === 'mobile-prefix'"
      v-model="formData[field.alpine_model]"
      v-model:prefixValue="formData[field.prefix_alpine_model]"
      :label="field.label"
      :placeholder="field.placeholder"
      :maxlength="field.maxlength"
      :required="field.required"
      :options="field.options"
    />

    <!-- NRIC / Passport -->
    <NricInput
      v-else-if="field.component === 'nricppt'"
      v-model="formData[field.alpine_model]"
      v-model:prefixValue="formData[field.prefix_alpine_model]"
      :label="field.label"
      :required="field.required"
      :helper="field.helper"
      :options="field.options"
      @show-helper="emit('show-helper')"
    />

    <!-- File Upload -->
    <FileUpload
      v-else-if="field.component === 'file-upload'"
      :label="field.label"
      :required="field.required"
      :fileUploaded="fileUploaded"
      :uploadReceiptValue="uploadReceiptValue"
      @change="emit('file-change', $event)"
    />

    <!-- Postcode / Adv-Select -->
    <PostcodeInput
      v-else-if="field.component === 'adv-select'"
      v-model="formData[field.alpine_model]"
      :label="field.label"
      :placeholder="field.placeholder"
      :maxlength="field.maxlength"
      :required="field.required"
      :showDropdown="showPostcodeDropdown"
      :filteredPostcodes="filteredPostcodes"
      @select="emit('select-postcode', $event)"
    />

    <!-- Checkbox -->
    <BaseCheckbox
      v-else-if="field.component === 'checkbox'"
      v-model="formData[field.name]"
      :id="field.name"
      :label="field.label"
      :required="field.required"
    />

    <!-- Label -->
    <div v-else-if="field.component === 'label'" :class="['text-white text-xs uppercase tracking-wider mb-2', locale === 'ch' ? 'font-normal' : 'font-ny-black']">
      {{ field.label }}
    </div>

    <!-- Hidden -->
    <input 
      v-else-if="field.component === 'hidden'" 
      type="hidden" 
      v-model="formData[field.alpine_model]" 
    />
  </div>
</template>
