<script setup>
import { useI18n } from 'vue-i18n'
const { locale } = useI18n()
defineProps({
  modelValue: [String, Number],
  label: String,
  placeholder: String,
  maxlength: [String, Number],
  required: Boolean,
  showDropdown: Boolean,
  filteredPostcodes: {
    type: Array,
    default: () => []
  }
})

defineEmits(['update:modelValue', 'select'])
</script>

<template>
  <div class="flex flex-col relative">
    <label v-if="label" :class="['text-white text-xs mb-2 uppercase tracking-wider', locale === 'ch' ? 'font-normal' : 'font-ny-black']">{{ label }}</label>
    <input 
      :value="modelValue" 
      @input="$emit('update:modelValue', $event.target.value)"
      type="text" 
      :placeholder="placeholder" 
      :maxlength="maxlength" 
      class="p-2.5 rounded bg-white text-black outline-none" 
      :required="required" 
    />
    
    <div 
      v-if="showDropdown" 
      class="absolute z-10 w-full mt-1 top-full bg-white rounded shadow-xl border border-gray-200 overflow-hidden max-h-48 overflow-y-auto"
    >
      <div 
        v-for="item in filteredPostcodes" 
        :key="item.postcode + item.city" 
        @click="$emit('select', item)" 
        class="p-3 hover:bg-gray-100 cursor-pointer text-sm border-b last:border-0 border-gray-100 flex justify-between"
      >
        <span :class="['text-primary', locale === 'ch' ? 'font-normal' : 'font-ny-black']">{{ item.postcode }}</span>
        <span class="text-gray-600">{{ item.city }}, {{ item.state }}</span>
      </div>
    </div>
  </div>
</template>
