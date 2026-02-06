<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

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

const stages = computed(() => {
  const s = []
  
  // 1. Submission
  s.push({
    id: 'submission',
    label: t('timeline_stage_submission'),
    date: props.item.submitted_date,
    status: 'COMPLETED',
    description: t('timeline_submission_received')
  })

  // 2. Validation
  let validationStatus = 'PENDING'
  let validationDesc = t('timeline_entry_received') + ' ' + t('timeline_entry_validation_completed')
  
  if (props.item.sub_status === 'APPROVED' || props.item.sub_status === 'DELIVERED') {
    validationStatus = 'COMPLETED'
    validationDesc = t('timeline_entry_proof_valid')
  } else if (props.item.sub_status === 'REJECTED') {
    validationStatus = 'REJECTED'
    validationDesc = t('timeline_entry_didnt_meet') + ' ' + t('timeline_entry_criteria_failed')
  }

  s.push({
    id: 'validation',
    label: t('timeline_stage_validation'),
    date: props.item.s_validate_date,
    status: validationStatus,
    description: validationDesc
  })

  // 3. Redemption
  let redemptionStatus = 'PENDING'
  let redemptionDesc = t('timeline_redemption_update_eligibility')
  
  if (props.item.sub_status === 'APPROVED' || props.item.sub_status === 'DELIVERED') {
    redemptionStatus = 'COMPLETED'
    redemptionDesc = t('timeline_redemption_eligible_msg')
  } else if (props.item.sub_status === 'REJECTED') {
    redemptionStatus = 'REJECTED'
    redemptionDesc = t('timeline_redemption_not_available')
  }

  s.push({
    id: 'redemption',
    label: t('timeline_stage_redemption'),
    date: props.item.s_validate_date,
    status: redemptionStatus,
    description: redemptionDesc
  })

  // 4. Processing
  let processingStatus = 'PENDING'
  let processingDesc = t('timeline_processing_packing_gift_new')
  
  if (props.item.sub_status === 'APPROVED' || props.item.sub_status === 'DELIVERED') {
    processingStatus = 'COMPLETED'
    processingDesc = t('timeline_processing_packing_gift_new') + ' ' + t('timeline_processing_tracking_shared_new')
  } else if (props.item.sub_status === 'REJECTED') {
    processingStatus = 'REJECTED'
    processingDesc = t('timeline_redemption_not_available')
  }

  s.push({
    id: 'processing',
    label: t('timeline_stage_processing'),
    date: props.item.s_validate_date,
    status: processingStatus,
    description: processingDesc
  })

  // 5. On the Way
  let deliveryStatus = 'PENDING'
  let deliveryDesc = t('timeline_ontheway_on_way')
  
  if (props.item.sub_status === 'DELIVERED') {
    deliveryStatus = 'COMPLETED'
    deliveryDesc = t('timeline_ontheway_your_gift') + ' ' + t('timeline_ontheway_on_way')
  }

  s.push({
    id: 'delivery',
    label: t('timeline_stage_on_the_way'),
    date: props.item.delivery_date,
    status: deliveryStatus,
    description: deliveryDesc
  })

  return s
})
</script>

<template>
  <div class="w-full py-8">
    <!-- Desktop Timeline (Simplified SVG-like structure using CSS) -->
    <div class="hidden md:flex justify-between items-start relative px-10">
      <div class="absolute top-12 left-20 right-20 h-1 bg-gray-300 -z-10"></div>
      
      <div v-for="(stage, index) in stages" :key="index" class="flex flex-col items-center text-center w-1/5 px-2">
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
