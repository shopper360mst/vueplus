<script setup>
import { useUIStore } from '../stores/ui'
import { useSessionStore } from '../stores/session'
import { useI18n } from 'vue-i18n'
import { onMounted, onUnmounted, watch } from 'vue'

defineProps({
  checkStatusPath: {
    type: String,
    default: '/check-status'
  }
})

const uiStore = useUIStore()
const sessionStore = useSessionStore()
const { t, locale } = useI18n()

// Sync i18n locale with session store locale
watch(() => sessionStore.locale, (newLocale) => {
  locale.value = newLocale
}, { immediate: true })

const openPopup = (event) => {
  const detail = event.detail || {}
  uiStore.openPopup(detail.formType || 'ty')
}

onMounted(() => {
  window.addEventListener('vue:ty', openPopup)
  window.addEventListener('alpine:ty', openPopup)
})

onUnmounted(() => {
  window.removeEventListener('vue:ty', openPopup)
  window.removeEventListener('alpine:ty', openPopup)
})

const getImageUrl = () => {
  const suffix = sessionStore.locale === 'en' ? 'en' : 'ch'
  const imageName = uiStore.popup.formType === 'cvs' ? `popup_contest_${suffix}.png` : `popup_ty_${suffix}.png`
  return new URL(`../assets/images/${imageName}`, import.meta.url).href
}
</script>

<template>
  <Transition name="fade">
    <div v-if="uiStore.popup.isOpen" class="dialog-overlay fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="uiStore.closePopup">
      <Transition name="scale">
        <div v-if="uiStore.popup.isOpen" class="relative max-w-lg w-full">
          <!-- Close Button Outside -->
          <div class="flex justify-end mb-2">
            <button 
              type="button" 
              class="text-white font-black text-xs md:text-sm tracking-widest hover:text-secondary transition-colors flex items-center gap-2" 
              @click="uiStore.closePopup"
            >
              {{ t('common.close') }} | X
            </button>
          </div>

          <div class="popup-content relative overflow-hidden rounded-xl shadow-2xl">
            <img :src="getImageUrl()" alt="Popup Content" class="w-full h-auto block" />
            
            <a 
              v-if="uiStore.popup.formType !== 'cvs'" 
              :href="checkStatusPath" 
              class="absolute inset-0 opacity-0 cursor-pointer" 
              aria-label="Check Status"
            ></a>
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.scale-enter-active, .scale-leave-active {
  transition: transform 0.3s ease;
}
.scale-enter-from, .scale-leave-to {
  transform: scale(0.9);
  opacity: 0;
}
</style>
