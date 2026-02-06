<script setup>
defineProps({
  modelValue: Boolean,
  id: String,
  label: String,
  required: Boolean
})

defineEmits(['update:modelValue'])
</script>

<template>
  <div class="flex items-start gap-3">
    <input 
      type="checkbox" 
      :id="id" 
      :checked="modelValue"
      @change="$emit('update:modelValue', $event.target.checked)"
      class="mt-1 w-5 h-5 rounded border-white/30 bg-white/10 text-yellow-500 focus:ring-yellow-500 cursor-pointer" 
      :required="required" 
    />
    <label :for="id" class="text-white text-xs leading-relaxed select-none cursor-pointer" v-html="label"></label>
  </div>
</template>
