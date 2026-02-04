<script setup>
import { onMounted, onUnmounted } from 'vue'
import AppInput from './AppInput.vue'
import { useUIStore } from '../stores/ui'
import { useSessionStore } from '../stores/session'
import { useI18n } from 'vue-i18n'

const uiStore = useUIStore()
const sessionStore = useSessionStore()
const { t } = useI18n()

const handlePopupForm = (event) => {
  const detail = event.detail || {}
  uiStore.openContestForm(detail.image || '', detail.fields || [])
}

onMounted(() => {
  window.addEventListener('vue:contest', handlePopupForm)
  window.addEventListener('alpine:contest', handlePopupForm)
})

onUnmounted(() => {
  window.removeEventListener('vue:contest', handlePopupForm)
  window.removeEventListener('alpine:contest', handlePopupForm)
})

const handleSubmit = () => {
  console.log('Form submitted', uiStore.contestForm.fields)
  // Logic for submission
  // Example using session store:
  // sessionStore.setItem('last_submission', new Date().toISOString())
}
</script>

<template>
  <Transition name="fade">
    <div v-if="uiStore.contestForm.isOpen" class="dialog-overlay fixed inset-0 z-50 flex items-center justify-center bg-black/50 overflow-y-auto" @click.self="uiStore.closeContestForm">
      <div class="relative min-h-full flex items-center justify-center w-full max-w-2xl mx-auto p-4">
        <div class="bg-primary rounded-xl shadow-2xl w-full overflow-hidden">
          <div class="bg-primary p-4 flex justify-end">
            <button class="text-white font-bold text-xl" @click="uiStore.closeContestForm">X</button>
          </div>
          
          <div class="p-6">
            <div v-if="uiStore.contestForm.image" class="w-full mb-6">
              <img :src="uiStore.contestForm.image" alt="Promo" class="w-full h-auto rounded" />
            </div>

            <form @submit.prevent="handleSubmit">
              <div v-for="(field, index) in uiStore.contestForm.fields" :key="index">
                <AppInput 
                  v-if="field.component === 'input'"
                  v-model="field.value"
                  :field="field"
                  :locale="sessionStore.locale"
                />
              </div>

              <div class="flex gap-4 mt-8">
                <button type="submit" class="bg-white text-primary font-bold py-3 px-6 rounded-xl grow hover:bg-gray-100 transition-colors">
                  {{ t('common.submit') }}
                </button>
                <button type="button" class="bg-transparent border border-white text-white font-bold py-3 px-6 rounded-xl grow hover:bg-white/10 transition-colors" @click="uiStore.closeContestForm">
                  {{ t('common.cancel') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
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
</style>
