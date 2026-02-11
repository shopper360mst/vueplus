<script setup>
import { useI18n } from 'vue-i18n'
const { locale } = useI18n()
defineProps({
  modelValue: [String, Number],
  prefixValue: String,
  label: String,
  required: Boolean,
  helper: Boolean,
  options: {
    type: Array,
    default: () => []
  }
})

defineEmits(['update:modelValue', 'update:prefixValue', 'show-helper'])
</script>

<template>
  <div class="flex flex-col gap-2">
    <div v-if="label" class="flex items-center gap-1.5 mb-2">
      <label :class="['text-white text-xs uppercase tracking-wider', locale === 'ch' ? 'font-normal' : 'font-ny-black']">{{ label }}</label>
      <button 
        v-if="helper" 
        type="button" 
        @click="$emit('show-helper')" 
        class="text-yellow-500 hover:text-yellow-400"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
          <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm8.706-1.442c1.146-.573 2.437.463 2.126 1.706l-.709 2.836.042-.02a.75.75 0 0 1 .67 1.34l-.04.022c-1.147.573-2.438-.463-2.127-1.706l.71-2.836-.042.02a.75.75 0 1 1-.671-1.34l.041-.022ZM12 9a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
    <div class="flex gap-2">
      <select 
        :value="prefixValue" 
        @change="$emit('update:prefixValue', $event.target.value)"
        class="p-2.5 rounded bg-white text-black w-32 outline-none"
      >
        <option v-for="opt in options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
      <input 
        :value="modelValue" 
        @input="$emit('update:modelValue', $event.target.value)"
        type="text" 
        :placeholder="prefixValue === 'NRIC' ? options[0]?.placeholder : options[1]?.placeholder" 
        class="p-2.5 rounded bg-white text-black grow outline-none" 
        :required="required" 
      />
    </div>
  </div>
</template>
