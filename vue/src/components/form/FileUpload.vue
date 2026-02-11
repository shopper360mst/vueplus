<script setup>
import { useI18n } from 'vue-i18n'
const { locale } = useI18n()
defineProps({
  label: String,
  required: Boolean,
  fileUploaded: Boolean,
  uploadReceiptValue: String
})

defineEmits(['change'])
</script>

<template>
  <div class="flex flex-col gap-2">
    <label v-if="label" :class="['text-white text-xs mb-2 uppercase tracking-wider', locale === 'ch' ? 'font-normal' : 'font-ny-black']">{{ label }}</label>
    <div class="relative">
      <input 
        type="file" 
        @change="$emit('change', $event)" 
        accept="image/*" 
        :class="['block w-full text-xs text-white file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:bg-yellow-500 file:text-white hover:file:bg-yellow-600 file:cursor-pointer', locale === 'ch' ? 'file:font-normal' : 'file:font-ny-black']" 
        :required="required && !fileUploaded" 
      />
      <p 
        v-if="fileUploaded" 
        class="text-yellow-400 text-[10px] mt-1 font-medium bg-black/20 p-1.5 rounded truncate"
      >
        Selected: {{ uploadReceiptValue }}
      </p>
    </div>
  </div>
</template>
