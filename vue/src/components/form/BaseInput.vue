<script setup>
defineProps({
  modelValue: [String, Number],
  label: String,
  type: {
    type: String,
    default: 'text'
  },
  placeholder: String,
  disabled: Boolean,
  maxlength: [String, Number],
  required: Boolean,
  helper: Boolean,
  readonly: Boolean
})

defineEmits(['update:modelValue', 'show-helper'])
</script>

<template>
  <div class="flex flex-col gap-2">
    <div v-if="label" class="flex items-center gap-1.5 mb-2">
      <label class="text-white text-xs font-semibold uppercase tracking-wider" v-html="label"></label>
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
    <input
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      :type="type"
      :placeholder="placeholder"
      :disabled="disabled"
      :maxlength="maxlength"
      :required="required"
      :readonly="readonly"
      class="p-2.5 rounded bg-white text-black focus:ring-2 focus:ring-yellow-500 outline-none transition-all"
      :class="{'cursor-not-allowed bg-white/20 text-white': readonly || disabled}"
    />
  </div>
</template>
