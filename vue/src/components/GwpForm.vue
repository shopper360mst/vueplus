<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useBaseForm } from '../composables/useBaseForm'
import { useUIStore } from '../stores/ui'
import FormDispatcher from './form/FormDispatcher.vue'
import gwpStructure from '../data/gwp-structure.json'

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
  locale
} = useBaseForm({
  formCode: 'GWP',
  initialData: {
    products: []
  }
})

const channel = ref('')
const currentProductIndex = ref(1)
const formImages = reactive({ 1: '', 2: '', 3: '' })
const imagesLoaded = reactive({ 1: false, 2: false, 3: false })
const carouselAutoplayTimer = ref(null)
const detectedRegion = ref('')
const showReceiptGuide = ref(false)

const receiptGuideImage = computed(() => {
  let baseChannel = channel.value
  if (channel.value.startsWith('SHM_')) {
    baseChannel = 'SHM'
  }
  if (baseChannel === 'CVSTOFT') {
    baseChannel = 'CVSTOFT'
  }
  try {
    return new URL(`../assets/images/receipt/${baseChannel}/receipt_1.png`, import.meta.url).href
  } catch {
    return new URL(`../assets/images/receipt/SHM/receipt_1.png`, import.meta.url).href
  }
})

const fallbackReceiptImage = new URL('../assets/images/receipt/SHM/receipt_1.png', import.meta.url).href

const currentStructure = computed(() => {
  return gwpStructure[locale.value] || gwpStructure['en']
})

const isOpen = computed(() => uiStore.gwpForm.isOpen)
const isSHMVariant = computed(() => channel.value.startsWith('SHM_'))

// Title mapping logic
const mappedTitle = computed(() => {
  let baseChannel = channel.value
  if (channel.value.startsWith('SHM_')) {
    baseChannel = 'SHM'
  }

  const regionMatch = channel.value.match(/_(WM|EM)/)
  const region = regionMatch ? regionMatch[1].toUpperCase() : ''
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

  if (channel.value === 'SHM' || isSHMVariant.value) {
    for (let idx = 1; idx <= 3; idx++) {
      if (isSHMVariant.value) {
        formImages[idx] = new URL(`../assets/images/form_shm_${region}_${currentLocale}_${idx}.png`, import.meta.url).href
      } else {
        formImages[idx] = new URL(`../assets/images/form_shm_${currentLocale}_${idx}.png`, import.meta.url).href
      }
    }
  } else {
    // Handle other channels
    let base = channel.value.toLowerCase()
    if (base === 's99') base = '99sm'
    if (base === 'cvs') base = 'cvstoft'
    
    try {
      formImages[1] = new URL(`../assets/images/form_${base}_${currentLocale}.png`, import.meta.url).href
      formImages[2] = ''
      formImages[3] = ''
    } catch {
      formImages[1] = ''
    }
  }
}

// Watch store for changes to launch form
watch(isOpen, (val) => {
  if (val) {
    document.body.classList.add('overflow-hidden')
    const data = uiStore.gwpForm
    channel.value = data.channel ? data.channel.trim().toUpperCase() : ''
    currentProductIndex.value = data.product || 1
    formData.products = [currentProductIndex.value]
    
    const regionMatch = channel.value.match(/_(WM|EM)/)
    detectedRegion.value = regionMatch ? regionMatch[1].toLowerCase() : ''
    
    updateFormImage()
    
    if (channel.value === 'SHM' || isSHMVariant.value) {
      startCarouselAutoplay()
    }
  } else if (!showReceiptGuide.value) {
    document.body.classList.remove('overflow-hidden')
    stopCarouselAutoplay()
  }
})

watch(showReceiptGuide, (val) => {
  if (val) {
    document.body.classList.add('overflow-hidden')
  } else if (!isOpen.value) {
    document.body.classList.remove('overflow-hidden')
  }
})

const handlePathDetection = () => {
  const channelParam = route.params.channel
  if (channelParam) {
    const upperChannel = channelParam.toUpperCase()
    if (upperChannel.startsWith('SHM')) {
      uiStore.openGwpForm(upperChannel)
      router.replace({ name: 'home', params: { locale: route.params.locale } })
    }
  }
}

watch(() => route.path, () => {
  handlePathDetection()
}, { immediate: true })

const startCarouselAutoplay = () => {
  if (!carouselAutoplayTimer.value) {
    carouselAutoplayTimer.value = setInterval(() => {
      carouselNext()
    }, 5000)
  }
}

const stopCarouselAutoplay = () => {
  if (carouselAutoplayTimer.value) {
    clearInterval(carouselAutoplayTimer.value)
    carouselAutoplayTimer.value = null
  }
}

const carouselNext = () => {
  currentProductIndex.value = currentProductIndex.value === 3 ? 1 : currentProductIndex.value + 1
}

const carouselPrev = () => {
  currentProductIndex.value = currentProductIndex.value === 1 ? 3 : currentProductIndex.value - 1
}

const isProductAvailable = () => {
  return true
}

const selectProduct = (id, event) => {
  if (event.target.checked) {
    if (!formData.products.includes(id)) {
      formData.products.push(id)
    }
  } else {
    if (formData.products.length <= 1) {
      event.target.checked = true
      uiStore.showToast(t('form.min_redemption'))
      return
    }
    formData.products = formData.products.filter((i) => i !== id)
  }
}

const getPlacementImage = (i) => {
  return new URL(`../assets/images/placement_product_${i}.png`, import.meta.url).href
}

const handleFormClose = () => {
  uiStore.closeGwpForm()
  resetForm()
}

const handleSubmit = async () => {
  try {
    const result = await baseSubmit({
      channel: channel.value,
      product: formData.products.join(',')
    })
    
    if (result && (result.success || result.message === 'ALLGOOD')) {
      alert('Success!')
      handleFormClose()
    } else if (result) {
      alert(result.message || t('form.server_error'))
    }
  } catch {
    alert(t('form.server_error'))
  }
}

onMounted(() => {
})

onUnmounted(() => {
  document.body.classList.remove('overflow-hidden')
  stopCarouselAutoplay()
})
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 w-screen h-screen overflow-hidden bg-black/70 flex justify-center items-start">
    <div class="relative w-full max-w-2xl bg-primary shadow-2xl flex flex-col max-h-[100vh]">
      <div class="bg-primary h-12 flex-shrink-0 flex items-center justify-center relative">
        <!-- <h1 class="text-white font-bold text-lg uppercase tracking-wider">{{ mappedTitle }}</h1> -->
        <button class="absolute right-4 text-white text-2xl hover:text-gray-300 transition-colors" @click="handleFormClose">&times;</button>
      </div>
      <div class="bg-primary overflow-y-auto custom-scrollbar">
        <div v-if="formImages[currentProductIndex]" class="relative group mb-6 overflow-hidden">
          <img :src="formImages[currentProductIndex]" class="w-full h-auto transition-opacity duration-300 ps-2" :class="{'opacity-0': !imagesLoaded[currentProductIndex]}" @load="imagesLoaded[currentProductIndex] = true" alt="Product" />
          
          <template v-if="formImages[2]">
            <button @click="carouselPrev" class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-3 rounded-full opacity-0 group-hover:opacity-100 transition-all">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            </button>
            <button @click="carouselNext" class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-3 rounded-full opacity-0 group-hover:opacity-100 transition-all">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </button>
          </template>
        </div>
        
        <form @submit.prevent="handleSubmit" autocomplete="off" class="flex flex-col gap-4 p-5">
          <div class="flex flex-col gap-4">
            <!-- Product selection component -->
            <div v-show="channel === 'SHM' || channel.startsWith('SHM_')" class="product-checkbox-group mt-4 mb-6">
              <span class="text-white font-bold text-center mb-4 block text-left">
                {{ locale === "ch" ? "请选择您的兑换选项" : "Please select your redemption options:" }}
              </span>

              <div class="flex flex-col gap-3">
                <template v-for="i in [1, 2, 3]" :key="i">
                  <label
                    class="product-checkbox-label flex items-center gap-3 p-4 rounded-lg transition-all"
                    :class="
                      !isProductAvailable()
                        ? 'bg-gray-400 opacity-50 grayscale cursor-not-allowed'
                        : formData.products.includes(i)
                          ? 'bg-carlsberg-green ring-2 ring-secondary cursor-pointer'
                          : 'bg-carlsberg-green hover:bg-carlsberg-green/90 cursor-pointer'
                    "
                  >
                    <input
                      type="checkbox"
                      name="product_selection"
                      :value="i"
                      class="form-checkbox h-5 w-5 text-secondary flex-shrink-0"
                      :class="!isProductAvailable() ? 'cursor-not-allowed' : 'cursor-pointer'"
                      :checked="formData.products.includes(i)"
                      :disabled="!isProductAvailable()"
                      @change="isProductAvailable() && selectProduct(i, $event)"
                    />
                    <img
                      :src="getPlacementImage(i)"
                      alt="Product"
                      class="w-16 h-16 md:w-20 md:h-20 object-cover rounded-md flex-shrink-0"
                    />
                    <span class="text-white font-semibold text-sm md:text-base flex-1">
                      <span v-if="!isProductAvailable()">{{
                        locale === "ch" ? "已全数兑换 - " : "FULLY REDEEMED - "
                      }}</span>
                      <span>{{ i === 1 ? t("form.luggage") : i === 2 ? t("form.rummy") : t("form.grill") }}</span>
                    </span>
                  </label>
                </template>
              </div>
            </div>

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
            <button type="submit" :disabled="isSubmitting" class="btn-cta w-full max-w-[240px] min-h-[4.5vh] disabled:opacity-50 disabled:cursor-not-allowed uppercase tracking-wider text-sm">
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
