<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useBaseForm } from '../composables/useBaseForm'
import { useUIStore } from '../stores/ui'
import FormDispatcher from './form/FormDispatcher.vue'
import cvsStructure from '../data/cvs-structure.json'

const uiStore = useUIStore()
const route = useRoute()
const router = useRouter()

const {
  formData,
  isSubmitting,
  fileUploaded,
  uploadReceiptValue,
  filteredPostcodes,
  showPostcodeDropdown,
  handleFileChange,
  resetForm,
  selectPostcode,
  baseSubmit,
  t,
  locale,
  bURL
} = useBaseForm({
  formCode: 'CVSTOFT'
})

const channel = ref('')
const formImage = ref('')
const showReceiptGuide = ref(false)

const receiptGuideImage = computed(() => {
  let baseChannel = channel.value
  const regionMatch = channel.value.match(/_(WM|EM)/)
  const region = regionMatch ? regionMatch[1].toUpperCase() : ''
  
  if (region) {
    baseChannel = channel.value.replace(`_${region}`, '')
  }
  
  // Use CVSTOFT for CVS channel based on folder structure
  if (baseChannel === 'CVS') {
    baseChannel = 'CVSTOFT'
  }
  
  try {
    return new URL(`../assets/images/receipt/${baseChannel}/receipt_1.png`, import.meta.url).href
  } catch (e) {
    return new URL(`../assets/images/receipt/CVSTOFT/receipt_1.png`, import.meta.url).href
  }
})

const fallbackReceiptImage = new URL('../assets/images/receipt/CVSTOFT/receipt_1.png', import.meta.url).href

const currentStructure = computed(() => {
  return cvsStructure[locale.value] || cvsStructure['en']
})

const isOpen = computed(() => uiStore.cvsForm.isOpen)

// Title mapping logic
const mappedTitle = computed(() => {
  let baseChannel = channel.value
  const regionMatch = channel.value.match(/_(WM|EM)/)
  const region = regionMatch ? regionMatch[1].toUpperCase() : ''
  
  if (region) {
    baseChannel = channel.value.replace(`_${region}`, '')
  }

  let finalRegion = ''
  if (region === 'WM') {
    finalRegion = locale.value === 'ch' ? '西马' : 'WM'
  } else if (region === 'EM') {
    finalRegion = locale.value === 'ch' ? '东马' : 'EM'
  }

  let titleKey = ''
  switch (baseChannel) {
    case 'MONT': titleKey = 'form.mont_title'; break
    case 'SHM': titleKey = 'form.shm_title'; break
    case 'TONT': titleKey = 'form.tont_title'; break
    case 'ECOMM': titleKey = 'form.ecomm_title'; break
    case 'S99': titleKey = 'form.s99_title'; break
    case 'CVS': titleKey = 'form.cvs_title'; break
    default: return ''
  }

  const titleText = t(titleKey)
  return finalRegion ? `[${finalRegion}] ${titleText}` : titleText
})

// Sync title to formData
watch(mappedTitle, (newTitle) => {
  formData.channel_name = newTitle
})

const updateFormImage = () => {
  const regionMatch = channel.value.match(/_(WM|EM)/)
  const region = regionMatch ? regionMatch[1].toLowerCase() : ''
  const currentLocale = locale.value.toLowerCase()
  
  let base = channel.value
  if (region) {
    base = channel.value.replace(`_${region.toUpperCase()}`, '')
  }
  
  base = base.toLowerCase()
  if (base === 'cvs') base = 'cvstoft'
  if (base === 's99') base = '99sm'

  if (region) {
    formImage.value = new URL(`../assets/images/form_${base}_${region}_${currentLocale}.png`, import.meta.url).href
  } else {
    formImage.value = new URL(`../assets/images/form_${base}_${currentLocale}.png`, import.meta.url).href
  }
}

// Watch store for changes to launch form
watch(isOpen, (val) => {
  if (val) {
    document.body.classList.add('overflow-hidden')
    const data = uiStore.cvsForm
    channel.value = data.channel ? data.channel.trim().toUpperCase() : ''
    updateFormImage()
  } else if (!showReceiptGuide.value) {
    document.body.classList.remove('overflow-hidden')
  }
})

watch(showReceiptGuide, (val) => {
  if (val) {
    document.body.classList.add('overflow-hidden')
  } else if (!isOpen.value) {
    document.body.classList.remove('overflow-hidden')
  }
})

const cvsTriggers = ['CVSTOFT', 'MONT', 'TONT', 'S99', 'CVS', 'TOFT']

const handlePathDetection = () => {
  const channelParam = route.params.channel
  if (channelParam) {
    const upperChannel = channelParam.toUpperCase()
    const baseSegment = upperChannel.split('_')[0]
    if (cvsTriggers.includes(baseSegment)) {
      uiStore.openCvsForm(upperChannel)
      router.replace({ name: 'home', params: { locale: route.params.locale } })
    }
  }
}

watch(() => route.path, () => {
  handlePathDetection()
}, { immediate: true })

const handleFormClose = () => {
  uiStore.closeCvsForm()
  resetForm()
}

const handleSubmit = async () => {
  try {
    const result = await baseSubmit({
      channel: channel.value
    })
    
    if (result && (result.success || result.message === 'ALLGOOD')) {
      alert('Success!')
      handleFormClose()
    } else if (result) {
      alert(result.message || t('form.server_error'))
    }
  } catch (error) {
    alert(t('form.server_error'))
  }
}

onMounted(() => {
})

onUnmounted(() => {
  document.body.classList.remove('overflow-hidden')
})
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 w-screen h-screen overflow-hidden bg-black/70 flex justify-center items-start px-4">
    <div class="relative w-full max-w-2xl bg-primary shadow-2xl my-8 border border-white/10 flex flex-col max-h-[100vh]">
      <div class="bg-primary h-12 flex-shrink-0 flex items-center justify-center relative border-b border-white/10">
        <h1 class="text-white font-bold text-lg uppercase tracking-wider">{{ mappedTitle }}</h1>
        <button class="absolute right-4 text-white text-2xl hover:text-gray-300 transition-colors" @click="handleFormClose">&times;</button>
      </div>
      <div class="bg-primary overflow-y-auto custom-scrollbar">
        <div v-if="formImage" class="relative group overflow-hidden border border-white/20">
          <img :src="formImage" class="w-full h-auto" alt="Banner" />
        </div>
        
        <form @submit.prevent="handleSubmit" autocomplete="off" class="flex flex-col gap-4 p-5">          
          <div class="flex flex-col gap-4">
            <template v-for="field in currentStructure.form_group" :key="field.name">
              <FormDispatcher
                :field="field"
                :form-data="formData"
                :file-uploaded="fileUploaded"
                :upload-receipt-value="uploadReceiptValue"
                @file-change="handleFileChange"
                @show-helper="showReceiptGuide = true"
              />
            </template>
          </div>

          <div v-if="currentStructure.delivery_group && currentStructure.delivery_group.length > 0" class="border-white/30 border-2 rounded-lg p-5 bg-black/10">
            <h3 class="text-white text-lg font-bold text-center mb-1">{{ currentStructure.translations.delivery_details }}</h3>
            <p class="text-white/80 text-xs text-center mb-6 leading-relaxed">{{ currentStructure.translations.delivery_subtitle }}</p>
            <div class="flex flex-col gap-4">
              <template v-for="field in currentStructure.delivery_group" :key="field.name">
                <FormDispatcher
                  :field="field"
                  :form-data="formData"
                  :show-postcode-dropdown="showPostcodeDropdown"
                  :filtered-postcodes="filteredPostcodes"
                  @select-postcode="selectPostcode"
                />
              </template>
            </div>
          </div>

          <div class="flex flex-col gap-4 py-4">
            <template v-for="field in currentStructure.checkbox_group" :key="field.name">
              <FormDispatcher
                :field="field"
                :form-data="formData"
              />
            </template>
          </div>

          <div class="bg-primary pt-2 pb-8 flex justify-center mt-auto">
            <button type="submit" :disabled="isSubmitting" class="w-full max-w-[240px] bg-yellow-500 hover:bg-yellow-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-3 rounded-lg shadow-lg transition-all active:scale-95 uppercase tracking-wider text-sm">
              <span v-if="isSubmitting">{{ t('form.submitting') }}...</span>
              <span v-else>{{ t('form.submit') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Receipt Guide Modal -->
    <div v-if="showReceiptGuide" class="fixed inset-0 z-[60] bg-black/90 flex flex-col items-center justify-center p-4" @click="showReceiptGuide = false">
      <div class="relative w-full max-w-lg bg-white rounded-lg overflow-hidden shadow-2xl" @click.stop>
        <div class="p-4 border-b flex justify-between items-center">
          <h3 class="font-bold text-gray-800">Receipt Guide</h3>
          <button @click="showReceiptGuide = false" class="text-gray-500 hover:text-black text-2xl leading-none">&times;</button>
        </div>
        <div class="p-4 max-h-[80vh] overflow-y-auto bg-gray-100 flex items-center justify-center">
          <img :src="receiptGuideImage" class="max-w-full h-auto shadow-sm" alt="Receipt Guide" @error="(e) => e.target.src = fallbackReceiptImage" />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.3);
}
</style>
