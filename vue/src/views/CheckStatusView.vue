<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useUIStore } from '../stores/ui'
import { postTo } from '../utils/api'
import AppTimeline from '../components/AppTimeline.vue'
import AppToast from '../components/AppToast.vue'
import AppPopup from '../components/AppPopup.vue'
import MobileInput from '../components/form/MobileInput.vue'

const { t, locale } = useI18n()
const uiStore = useUIStore()

const results = ref([])
const noResults = ref(false)

const form = ref({
  mobile_prefix: '60',
  mobile_number: ''
})

const form_fields = ref([
  {
    form_group: [
      {
        name: 'mobile_no',
        label: 'form.mobile_number',
        placeholder: 'form.mobile_placeholder',
        options: [{ label: '+60', value: '60' }, { label: '+65', value: '65' }]
      }
    ]
  }
])

// Sample Data from chkstatus.alpine.js
const sampleData = [
  {
    id: 1,
    channel: 'CHANNEL NAME HERE',
    submit_code: 'SHM',
    submit_type: 'MONT',
    submitted_date: '30th Sept 2024',
    sub_status: 'REJECTED',
    invalid_sub_reason: 'UNCLEAR IMAGE/NOT A RECEIPT/INCOMPLETE RECEIPT',
    limit_reached: null,
    instore_redeem: null,
    s_validate_date: '30th Sept 2024',
    r_status: '',
    locked_date: '',
    r_checked_date: '',
    r_approved_date: '',
    winner_status: '',
    product_ref: null,
    delivery_status: '',
    delivery_details: '',
    delivered_date: ''
  },
  {
    id: 2,
    channel: 'CHANNEL NAME HERE',
    submit_code: 'SHM',
    submit_type: 'MONT',
    submitted_date: '30th Sept 2024',
    sub_status: 'APPROVED',
    invalid_sub_reason: null,
    limit_reached: null,
    instore_redeem: null,
    s_validate_date: '1st Oct 2024',
    r_status: 'APPROVED',
    r_checked_date: '2nd Oct 2024',
    r_approved_date: '2nd Oct 2024',
    winner_status: '',
    product_ref: 12345,
    delivery_status: 'PROCESSING',
    delivery_details: 'DHL Express',
    delivered_date: '',
    locked_date: '3rd Oct 2024'
  },
  {
    id: 3,
    channel: 'CHANNEL NAME HERE',
    submit_code: 'SHM',
    submit_type: 'MONT',
    submitted_date: '29th Sept 2024',
    sub_status: 'APPROVED',
    invalid_sub_reason: null,
    limit_reached: null,
    instore_redeem: null,
    s_validate_date: '30th Sept 2024',
    r_status: 'APPROVED',
    r_checked_date: '1st Oct 2024',
    r_approved_date: '1st Oct 2024',
    winner_status: '',
    product_ref: 1,
    delivery_status: 'OUT FOR DELIVERY',
    delivery_details: 'MY88888888888',
    delivered_date: '5th Oct 2024',
    locked_date: '2nd Oct 2024'
  }
]

const handleProcessForm = async () => {
  const prefix = form.value.mobile_prefix
  const number = form.value.mobile_number

  let invalidMY = false
  let invalidSG = false

  if (prefix === '60') {
    if (number[0] !== '1' || number.length < 8) {
      invalidMY = true
    }
  } else if (prefix === '65' && number.length !== 8) {
    invalidSG = true
  }

  if (invalidMY) {
    uiStore.showToast(t('form.invalid_my_phone'), 'bg-red-500 text-white')
    return
  }
  if (invalidSG) {
    uiStore.showToast(t('form.invalid_sg_phone'), 'bg-red-500 text-white')
    return
  }

  try {
    const result = await postTo('/endpoint/check', {
      mobile_no: prefix + number
    })
    
    if (result && result.data) {
      results.value = result.data
      noResults.value = results.value.length === 0
    }
  } catch (error) {
    console.error('Check status error:', error)
    uiStore.showToast(t('error.connection_failed'), 'bg-red-500 text-white')
  }
}

onMounted(() => {
  results.value = sampleData
})
</script>

<template>
  <div class="bg-tertiary w-full min-h-screen flex flex-col">
    <!-- ALL COMMON COMPONENTS -->
    <AppToast />
    <AppPopup />
    <!-- Loader logic can be added to uiStore if needed -->
    
    <div class="flex-grow flex flex-col items-center py-20">
      <section class="flex flex-col items-center w-full max-w-4xl px-4">
        <div class="bg-secondary rounded-xl w-full py-10 px-6 shadow-2xl">
          <div class="flex flex-col items-center gap-4">
            <img class="w-48 mb-6" src="@/assets/images/carlsberg_logo.png" alt="Carlsberg Logo" />
            <p :class="['w-full text-center mb-8 text-white', locale === 'ch' ? 'font-normal' : 'font-ny-black']">{{ t('check_status.desc') }}</p>
            
            <div class="w-full max-w-3xl flex flex-col items-center">
              <form class="w-full flex flex-col items-center gap-4" @submit.prevent="handleProcessForm">
                  <div v-for="(group, gIdx) in form_fields" :key="gIdx" class="w-full">
                    <div v-for="(field, fIdx) in group.form_group" :key="fIdx" class="mb-6">
                      <MobileInput 
                        v-model:modelValue="form.mobile_number"
                        v-model:prefixValue="form.mobile_prefix"
                        :label="t(field.label)"
                        :placeholder="t(field.placeholder)"
                        :options="field.options"
                        required
                      />
                    </div>
                  </div>
                  
                  <button type="submit" 
                    :class="['mx-auto block w-[165px] bg-primary text-white rounded-xl py-3 shadow-lg hover:opacity-90 transition-all duration-300', 
                    locale === 'ch' ? 'font-normal' : 'font-ny-black']"
                  >
                    {{ t('check_status.button') }}
                  </button>
              </form>
            </div>

            <div class="w-full mt-12">
              <div v-if="results.length > 0" class="flex flex-col gap-5">
                <template v-for="item in results" :key="item.id">
                  <div v-if="item.submit_code !== 'CVSTOFT'" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                    <div class="w-full flex flex-col mb-4">
                      <div class="flex justify-between items-center">
                        <p class="text-xs md:text-sm font-mono text-gray-500">
                          {{ t('check_status.submission_id') }}: CNY{{ String(item.id).padStart(4, '0') }}
                        </p>
                      </div>
                    </div>
                    <AppTimeline :item="item" :locale="locale" />
                    <div class="mt-6 flex justify-end">
                      <a v-if="(item.delivered_date || item.delivery_date) && (item.delivery_status === 'OUT FOR DELIVERY' || item.delivery_status === 'DELIVERED') && item.delivery_details"
                        :href="item.delivery_assign === 'SMX' ? `https://spx.com.my/track?${item.delivery_details}` : `https://gdexpress.com/tracking/?consignmentno=${item.delivery_details}`"
                        target="_blank"
                        :class="['rounded-xl text-sm bg-secondary text-white px-6 py-2 hover:opacity-90 transition-all duration-300 shadow-md', locale === 'ch' ? 'font-normal' : 'font-ny-black']"
                      >
                        {{ t('check_status.track_prize') }}
                      </a>
                    </div>
                  </div>
                </template>
              </div>

              <div v-else-if="noResults" class="bg-white rounded-xl p-10 text-center border border-dashed border-gray-300">
                <p class="text-gray-500">{{ t('check_status.no_results') }}</p>
              </div>
            </div>
            
            <span :class="['mt-12 text-sm text-white text-center', locale === 'ch' ? 'font-normal' : 'font-ny-black']" v-html="t('check_status.contact_desc')"></span>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>

</style>
