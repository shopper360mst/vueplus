<script setup>
defineProps({
  modelValue: [String, Number],
  prefixValue: [String, Number],
  label: String,
  placeholder: String,
  maxlength: [String, Number],
  required: Boolean,
  options: {
    type: Array,
    default: () => []
  }
})

defineEmits(['update:modelValue', 'update:prefixValue'])
</script>

<template>
  <div class="flex flex-col gap-2">
    <label v-if="label" class="text-white text-xs font-semibold mb-2 uppercase tracking-wider" v-html="label"></label>
    <div class="flex gap-2">
      <select 
        :value="prefixValue" 
        @change="$emit('update:prefixValue', $event.target.value)"
        class="p-2.5 rounded bg-white text-black w-24 outline-none"
      >
        <option v-for="opt in options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
      <input 
        :value="modelValue" 
        @input="$emit('update:modelValue', $event.target.value)"
        type="tel" 
        :placeholder="placeholder" 
        :maxlength="maxlength" 
        class="p-2.5 rounded bg-white text-black grow outline-none" 
        :required="required" 
      />
    </div>
  </div>
</template>
