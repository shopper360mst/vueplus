<script setup>
import { ref, onMounted } from 'vue'
import { useUIStore } from '../stores/ui'
import { useSessionStore } from '../stores/session'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  promoterCode: {
    type: String,
    default: ''
  }
})

const uiStore = useUIStore()
const sessionStore = useSessionStore()
const { t, locale } = useI18n()

const rememberMe = ref(false)

const handleYesClick = () => {
  if (rememberMe.value) {
    localStorage.setItem('gate_remembered', 'true')
  }
  sessionStorage.setItem('app.camAppGate', 'true')
  uiStore.closeGate()
}

const handleNoClick = () => {
  window.location.href = 'https://www.google.com'
}

const handleRmbMe = () => {
  rememberMe.value = !rememberMe.value
}

onMounted(() => {
  if (props.promoterCode) {
    sessionStore.setItem('promoter_code', props.promoterCode)
  }
})
</script>

<template>
  <Transition name="gate-transition">
    <section 
      v-if="uiStore.gateOpen" 
      class="fixed inset-0 z-[9000] flex h-svh w-full flex-col items-center justify-center bg-black/20 text-white backdrop-blur-md"
    >
      <div 
        class="flex h-svh w-full flex-col items-center justify-center gap-4 overflow-hidden bg-[#006937]"
      >
        <div aria-label="center div" class="relative inset-0 flex h-full w-full flex-col items-center justify-center bg-[#006937] bg-cover bg-center md:scale-[0.5] lg:scale-[0.55] xl:scale-[0.68] 2xl:scale-[0.8] 3xl:scale-[0.9]">
          <div class="flex flex-col items-center justify-start max-w-[94%] sm:max-w-[96%] lg:max-w-[920px]">
            <!-- Spacer 30 -->
            <div class="h-[30px]"></div>
            
            <img alt="Celebration #BestWithCarlsberg" src="@/assets/images/carlsberg_logo.png" width="52%" />
            
            <!-- Spacer 25 -->
            <div class="h-[25px]"></div>
            
            <h1 :class="['mb-[10px] text-center text-[22px] font-medium text-white lg:text-[43px]', locale === 'ch' ? 'font-ch' : 'font-ny-black']">
              {{ t('gate.simpleTitle1') }}
            </h1>
            <h1 :class="['text-center font-normal text-white mb-2 md:mb-4 lg:text-[20px]', locale === 'ch' ? 'font-ch' : 'font-ny-black']">
              {{ t('gate.simpleTitle2') }}
            </h1>
            
            <!-- Spacer 25 -->
            <div class="h-[25px]"></div>
            
            <div class="flex flex-row w-full justify-center gap-8">
              <button 
                class="btn-primary-alt border-white p-[1em] text-[14px] w-[38%] lg:text-[20px] lg:p-[0.6em] lg:w-[25%]"
                @click="handleYesClick"
              >
                {{ t('gate.yes') }}
              </button>
              <button 
                class="btn-primary-alt border-white p-[1em] text-[14px] w-[38%] lg:text-[20px] lg:p-[0.6em] lg:w-[25%]"
                @click="handleNoClick"
              >
                {{ t('gate.no') }}
              </button>
            </div>
            
            <!-- Spacer 25 -->
            <div class="h-[25px]"></div>
            
            <div class="flex flex-row mt-[10px] max-w-[97%] items-center cursor-pointer gap-2" @click="handleRmbMe">
              <input 
                id="ageCheck"
                type="checkbox"
                :checked="rememberMe"
                class="rounded-[8px] w-[25px] h-[25px] border-white bg-[#dddddd] focus:ring-0"
                @click.stop="handleRmbMe"
              >
              <label :class="['ml-3 text-[14px] lg:text-[18px] cursor-pointer select-none', locale === 'ch' ? 'font-ch' : 'font-ny-black']" for="ageCheck">
                {{ t('gate.rememberMe') }}
              </label>
            </div>
            <footer class="relative bottom-0 left-0 flex w-full flex-col items-center justify-end gap-0 px-3 py-0 h-[150px] lg:h-[190px]">
              <div class="flex flex-row items-center justify-center lg:max-w-[600px] gap-2">
                <div :class="['text-[12px] lg:text-[16px] whitespace-nowrap uppercase tracking-wider', locale === 'ch' ? 'font-ch' : 'font-ny-black']">
                  {{ t('gate.disclaimer') }}
                </div>
                <div class="w-[120px] min-[340px]:w-[150px] min-[410px]:w-[180px] lg:w-[200px]">
                  <img src="@/assets/svgs/disclaimer_new.svg" alt="Disclaimer" class="w-full">
                </div>
              </div>

              <h3 :class="['mt-2 text-[12px] text-center min-[360px]:text-center lg:text-[16px] lg:max-w-[920px]', locale === 'ch' ? 'font-ch' : 'font-ny-black']">
                {{ t('gate.subtitle') }}
              </h3>
              
              <div :class="['mt-2 text-[11px] lg:text-[14px] lg:max-w-[920px]', locale === 'ch' ? 'font-ch' : 'font-ny-black']">
                <h3 class="text-center font-bold">{{ t('gate.company') }} 198501008089 (140534-M)</h3>
                <p class="text-center">55, Persiaran Selangor, Seksyen 15,</p>
                <p class="text-center">40200 Shah Alam, Selangor, Malaysia.</p>
              </div>            
            </footer>
          </div>
        </div>
      </div>
    </section>
  </Transition>
</template>

<style scoped>
.gate-transition-enter-active, .gate-transition-leave-active {
  transition: transform 1s ease;
}
.gate-transition-enter-from, .gate-transition-leave-to {
  transform: translateY(100%);
}

/* Ensure checkbox looks correct in tailwind */
input[type="checkbox"] {
  color-scheme: light;
  accent-color: #006937;
  background-color: #dddddd;
}
</style>
