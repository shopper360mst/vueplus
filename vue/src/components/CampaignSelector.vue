<script setup>
import { useUIStore } from '../stores/ui'
import { useI18n } from 'vue-i18n'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import greenBg from '../assets/images/green_texture_bg.png'

const uiStore = useUIStore()
const { t } = useI18n()

const isMobile = ref(false)
const updateIsMobile = () => {
  isMobile.value = window.innerWidth < 768
}

onMounted(() => {
  updateIsMobile()
  window.addEventListener('resize', updateIsMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', updateIsMobile)
})

const currentBg = computed(() => {
  return isMobile.value ? greenBg : greenBg
})

defineProps({
  // 9-tile background images
  bgImages: {
    type: Object,
    default: () => ({
      tl: '', t: '', tr: '',
      l: '', c: '', r: '',
      bl: '', b: '', br: ''
    })
  },
  // Button 1 images (Closes popup)
  btn1: {
    type: Object,
    default: () => ({
      desktop: '', // 960x1556
      mobile: ''   // 16:9
    })
  },
  // Button 2 images (Redirects)
  btn2: {
    type: Object,
    default: () => ({
      desktop: '', // 960x1556
      mobile: ''   // 16:9
    })
  }
})

const handleClose = () => {
  uiStore.closeCampaignSelector()
}

const handleRedirect = () => {
  if (uiStore.campaignSelector.campaignUrl) {
    uiStore.closeCampaignSelector()
    window.location.href = uiStore.campaignSelector.campaignUrl
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div 
        v-if="uiStore.campaignSelector.isOpen" 
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/70 p-4"
        @click.self="handleClose"
      >
        <div 
          class="relative w-full max-w-[95%] md:max-w-[800px] max-h-[90vh] overflow-y-auto md:overflow-hidden bg-cover bg-center"
          :style="{ backgroundImage: `url(${currentBg})` }"
        >
          <!-- 9-Tiled Background (Overlay if images provided) -->
          <div 
            class="absolute inset-0 grid grid-cols-[40px_1fr_40px] grid-rows-[40px_1fr_40px] pointer-events-none z-0"
            :class="{ 'hidden': !Object.values(bgImages).some(img => img) }"
          >
            <div class="bg-tl" :style="{ backgroundImage: bgImages.tl ? `url(${bgImages.tl})` : '' }"></div>
            <div class="bg-t" :style="{ backgroundImage: bgImages.t ? `url(${bgImages.t})` : '' }"></div>
            <div class="bg-tr" :style="{ backgroundImage: bgImages.tr ? `url(${bgImages.tr})` : '' }"></div>
            
            <div class="bg-l" :style="{ backgroundImage: bgImages.l ? `url(${bgImages.l})` : '' }"></div>
            <div class="bg-c" :style="{ backgroundImage: bgImages.c ? `url(${bgImages.c})` : '' }"></div>
            <div class="bg-r" :style="{ backgroundImage: bgImages.r ? `url(${bgImages.r})` : '' }"></div>
            
            <div class="bg-bl" :style="{ backgroundImage: bgImages.bl ? `url(${bgImages.bl})` : '' }"></div>
            <div class="bg-b" :style="{ backgroundImage: bgImages.b ? `url(${bgImages.b})` : '' }"></div>
            <div class="bg-br" :style="{ backgroundImage: bgImages.br ? `url(${bgImages.br})` : '' }"></div>
          </div>

          <!-- Content Area -->
          <div class="relative z-10 h-full w-full flex flex-col items-center justify-center p-6 md:p-12">
            <!-- Title -->
            <div class="mb-8 text-center text-white">
              <h2 class="text-2xl md:text-4xl font-black uppercase tracking-widest mb-1">{{ t('campaign_selector') }}</h2>
            </div>

            <div class="flex flex-col md:flex-row items-center justify-center gap-4 md:gap-8 w-full">
              <!-- Button 1: Stay/Close -->
              <button 
                type="button"
                class="w-full md:w-1/2 transition-transform cursor-pointer overflow-hidden rounded-lg shadow-lg animate-pulse-1"
                @click="handleClose"
              >
                <div class="aspect-[16/9] md:aspect-[960/1556] w-full relative">
                  <img v-if="btn1.mobile" :src="btn1.mobile" class="absolute inset-0 w-full h-full object-cover block md:hidden" alt="Stay" />
                  <img v-if="btn1.desktop" :src="btn1.desktop" class="absolute inset-0 w-full h-full object-cover hidden md:block" alt="Stay" />
                  <div v-if="!btn1.mobile && !btn1.desktop" class="absolute inset-0 bg-gray-200 flex items-center justify-center text-gray-500">Stay Here</div>
                </div>
              </button>
              
              <!-- Button 2: Redirect -->
              <button 
                type="button"
                class="w-full md:w-1/2 transition-transform cursor-pointer overflow-hidden rounded-lg shadow-lg animate-pulse-2"
                @click="handleRedirect"
              >
                <div class="aspect-[16/9] md:aspect-[960/1556] w-full relative">
                  <img v-if="btn2.mobile" :src="btn2.mobile" class="absolute inset-0 w-full h-full object-cover block md:hidden" alt="Go to Campaign" />
                  <img v-if="btn2.desktop" :src="btn2.desktop" class="absolute inset-0 w-full h-full object-cover hidden md:block" alt="Go to Campaign" />
                  <div v-if="!btn2.mobile && !btn2.desktop" class="absolute inset-0 bg-blue-200 flex items-center justify-center text-blue-500 text-center px-4">Go to Campaign</div>
                </div>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.bg-tl, .bg-t, .bg-tr, .bg-l, .bg-c, .bg-r, .bg-bl, .bg-b, .bg-br {
  background-position: center;
  background-repeat: no-repeat;
  background-size: 100% 100%;
}

.bg-t, .bg-b { background-repeat: repeat-x; }
.bg-l, .bg-r { background-repeat: repeat-y; }
.bg-c { background-repeat: repeat; }

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

@keyframes pulse-1 {
  0%, 40% { transform: scale(1); }
  20% { transform: scale(1.03); }
  40%, 100% { transform: scale(1); }
}

@keyframes pulse-2 {
  0%, 50% { transform: scale(1); }
  70% { transform: scale(1.03); }
  90%, 100% { transform: scale(1); }
}

.animate-pulse-1 {
  animation: pulse-1 5s ease-in-out infinite;
}

.animate-pulse-2 {
  animation: pulse-2 5s ease-in-out infinite;
}

/* Ensure font consistency if needed */
.font-ch { font-family: 'Noto Sans SC', sans-serif; }
.font-ny-black { font-family: 'New York', serif; font-weight: 900; }
</style>
