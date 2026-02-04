<script setup>
import { computed } from 'vue'

const props = defineProps({
  item: {
    type: Object,
    default: () => ({
      sub_status: 'PROCESSING',
      submitted_date: 'DD/MM/YYYY',
      s_validate_date: '',
      delivery_date: ''
    })
  },
  locale: {
    type: String,
    default: 'en'
  }
})

// Simple translation mock
const t = (key) => key

const stages = computed(() => [
  {
    id: 'submission',
    label: t('timeline_stage_submission'),
    date: props.item.submitted_date,
    status: 'COMPLETED',
    description: t('timeline_submission_received')
  },
  {
    id: 'processing',
    label: t('timeline_stage_processing'),
    date: props.item.s_validate_date,
    status: props.item.sub_status === 'APPROVED' ? 'COMPLETED' : (props.item.sub_status === 'REJECTED' ? 'REJECTED' : 'PENDING'),
    description: t('timeline_processing_desc')
  },
  {
    id: 'validated',
    label: t('timeline_stage_validated'),
    date: props.item.s_validate_date,
    status: props.item.sub_status === 'APPROVED' ? 'COMPLETED' : 'PENDING',
    description: t('timeline_validated_desc')
  },
  {
    id: 'delivery',
    label: t('timeline_stage_delivery'),
    date: props.item.delivery_date,
    status: props.item.sub_status === 'DELIVERED' ? 'COMPLETED' : 'PENDING',
    description: t('timeline_delivery_desc')
  }
])
</script>

<template>
  <div class="w-full py-8">
    <!-- Desktop Timeline (Simplified SVG-like structure using CSS) -->
    <div class="hidden md:flex justify-between items-start relative px-10">
      <div class="absolute top-12 left-20 right-20 h-1 bg-gray-300 -z-10"></div>
      
      <div v-for="(stage, index) in stages" :key="index" class="flex flex-col items-center text-center w-1/4 px-2">
        <div :class="['w-16 h-16 rounded-full border-4 flex items-center justify-center mb-4 bg-white', 
          stage.status === 'COMPLETED' ? 'border-primary bg-primary text-white' : 
          (stage.status === 'REJECTED' ? 'border-red-500 bg-red-100 text-red-500' : 'border-gray-300 text-gray-400')
        ]">
          <span v-if="stage.status === 'COMPLETED'">✓</span>
          <span v-else-if="stage.status === 'REJECTED'">✕</span>
          <span v-else>{{ index + 1 }}</span>
        </div>
        <h3 class="font-bold text-lg">{{ stage.label }}</h3>
        <p class="text-sm text-gray-600">{{ stage.date }}</p>
        <p class="text-xs mt-2">{{ stage.description }}</p>
      </div>
    </div>

    <!-- Mobile Timeline (Vertical) -->
    <div class="flex flex-col md:hidden space-y-8 px-6">
      <div v-for="(stage, index) in stages" :key="index" class="flex gap-4">
        <div class="flex flex-col items-center">
          <div :class="['w-10 h-10 rounded-full border-2 flex items-center justify-center bg-white', 
            stage.status === 'COMPLETED' ? 'border-primary bg-primary text-white' : 
            (stage.status === 'REJECTED' ? 'border-red-500 bg-red-100 text-red-500' : 'border-gray-300 text-gray-400')
          ]">
            <span v-if="stage.status === 'COMPLETED'">✓</span>
            <span v-else-if="stage.status === 'REJECTED'">✕</span>
            <span v-else>{{ index + 1 }}</span>
          </div>
          <div v-if="index < stages.length - 1" class="w-0.5 h-full bg-gray-300 my-1"></div>
        </div>
        <div class="pb-8">
          <h3 class="font-bold text-base">{{ stage.label }}</h3>
          <p class="text-xs text-gray-500">{{ stage.date }}</p>
          <p class="text-sm mt-1 text-gray-700">{{ stage.description }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
