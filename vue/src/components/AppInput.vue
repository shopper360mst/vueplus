<script setup>
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  locale: {
    type: String,
    default: 'en'
  }
})

const emit = defineEmits(['update:modelValue', 'remove-error', 'input'])

const handleInput = (e) => {
  emit('update:modelValue', e.target.value)
  emit('input', e)
}

const handleFocus = () => {
  emit('remove-error', props.field.name)
}
</script>

<template>
  <div v-if="field.component === 'input'" class="form-group mb-4">
    <label 
      class="block text-white text-xs md:text-sm font-bold mb-2 mt-2" 
      :for="field.name" 
      v-html="field.label"
    ></label>
    <div class="flex flex-row relative">
      <input 
        :id="field.name"
        :type="field.type"
        :value="field.value"
        :placeholder="field.placeholder"
        :maxlength="field.maxlength"
        :required="field.required"
        :disabled="field.disabled"
        class="form-input w-full bg-white border border-gray-300 rounded px-3 py-2 text-gray-800"
        @focus="handleFocus"
        @input="handleInput"
      >
    </div>
    <label v-if="field.error_message" class="text-red-500 text-xs mt-1 block">
      {{ field.error_message }}
    </label>
  </div>
</template>
