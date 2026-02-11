<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useUIStore } from '../stores/ui'
import { useI18n } from 'vue-i18n'
import { useSEO } from '../composables/useSEO'

const uiStore = useUIStore()
const { t } = useI18n()

useSEO('seo.promotions_title', 'seo.promotions_description')

const carouselImages = [
  new URL('../assets/images/placement_product_1.png', import.meta.url).href,
  new URL('../assets/images/placement_product_2.png', import.meta.url).href,
  new URL('../assets/images/placement_product_3.png', import.meta.url).href
]

const currentIndex = ref(0)
const autoplayTimer = ref(null)

const nextSlide = () => {
  currentIndex.value = (currentIndex.value + 1) % carouselImages.length
}

const prevSlide = () => {
  currentIndex.value = (currentIndex.value - 1 + carouselImages.length) % carouselImages.length
}

const startAutoplay = () => {
  autoplayTimer.value = setInterval(nextSlide, 5000)
}

const stopAutoplay = () => {
  if (autoplayTimer.value) {
    clearInterval(autoplayTimer.value)
    autoplayTimer.value = null
  }
}

onMounted(() => {
  startAutoplay()
})

onUnmounted(() => {
  stopAutoplay()
})

const promotionButtons = [
  { id: 'shm-wm', channel: 'SHM_WM', type: 'gwp' },
  { id: 'shm-em', channel: 'SHM_EM', type: 'gwp' },
  { id: 'bar_cafe', channel: 'MONT', type: 'cvs' },
  { id: 'cafe_court', channel: 'TONT', type: 'cvs' },
  { id: '99sm', channel: 'S99', type: 'cvs' },
  { id: 'cvs', channel: 'CVSTOFT', type: 'cvs' }
]

const openForm = (promo) => {
  if (promo.type === 'gwp') {
    uiStore.openGwpForm(promo.channel)
  } else {
    uiStore.openCvsForm(promo.channel)
  }
}
</script>

<template>
  <div class="min-h-screen bg-tertiary">
    <!-- Carousel Section -->
    <section class="w-full pt-12 px-4">
      <div class="w-full max-w-6xl mx-auto relative h-[300px] md:h-[500px] overflow-hidden bg-black rounded-3xl">
        <div 
          v-for="(img, index) in carouselImages" 
          :key="index"
          class="absolute inset-0 transition-opacity duration-1000 ease-in-out"
          :class="index === currentIndex ? 'opacity-100' : 'opacity-0'"
        >
          <img :src="img" class="w-full h-full object-cover opacity-60" alt="Banner" />
          <div class="absolute inset-0 flex flex-col items-center justify-center text-white px-4">
            <h2 class="text-3xl md:text-5xl font-ny-black mb-4 text-center">{{ t('promotions.banner_title') }}</h2>
            <p class="text-lg md:text-xl text-center max-w-2xl">{{ t('promotions.banner_subtitle') }}</p>
          </div>
        </div>

        <!-- Controls -->
        <button @click="prevSlide" class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/40 text-white p-2 rounded-full transition-all">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
        </button>
        <button @click="nextSlide" class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/40 text-white p-2 rounded-full transition-all">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
        </button>

        <!-- Indicators -->
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
          <div 
            v-for="(_, index) in carouselImages" 
            :key="index"
            @click="currentIndex = index"
            class="w-3 h-3 rounded-full cursor-pointer transition-all"
            :class="index === currentIndex ? 'bg-white w-8' : 'bg-white/50 hover:bg-white/80'"
          ></div>
        </div>
      </div>
    </section>

    <!-- Buttons Section -->
    <section class="w-full flex flex-col items-center py-10 px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 w-full max-w-6xl mx-auto justify-items-center">
          <button
            v-for="promo in promotionButtons"
            :key="promo.id"
            @click="openForm(promo)"
            class="w-full min-h-[100px] bg-primary text-white rounded-xl font-ny-black uppercase tracking-wider text-center hover:opacity-90 transition-all duration-300 shadow-lg"
            v-html="t(`promotions.${promo.id}`)"
          >
          </button>
        </div>
    </section>
  </div>
</template>

<style scoped>
/* Custom animations if needed */
</style>
