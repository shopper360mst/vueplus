<script setup>
import { useUIStore } from '../stores/ui'
import { useI18n } from 'vue-i18n'
import { onMounted, onUnmounted } from 'vue'

const uiStore = useUIStore()
const { t, locale } = useI18n()

// Keep backward compatibility for window events
const handleAppearance = (event) => {
  const detail = event.detail || {}
  uiStore.showToast(detail.message || '', detail.classes || '')
}

onMounted(() => {
  window.addEventListener('vue:toast', handleAppearance)
  window.addEventListener('alpine:toast', handleAppearance)
})

onUnmounted(() => {
  window.removeEventListener('vue:toast', handleAppearance)
  window.removeEventListener('alpine:toast', handleAppearance)
})
</script>

<template>
  <Transition name="fade">
    <div v-if="uiStore.toast.isOpen" class="toast-overlay fixed inset-0 z-[100] flex items-center justify-center bg-black/50" @click.self="uiStore.hideToast">
      <Transition name="scale">
        <div v-if="uiStore.toast.isOpen" :class="['toast-dialog bg-white rounded-lg shadow-xl max-w-sm w-full mx-4', uiStore.toast.classes]">
          <div class="px-4 py-3 text-center">
            <p :class="['p-5 text-gray-800', locale === 'ch' ? 'font-ch' : 'font-ny-black']">{{ uiStore.toast.message }}</p>
            <button class="btn-primary-alt rounded-xl w-full" @click="uiStore.hideToast">
              {{ t('common.ok') }}
            </button>
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.scale-enter-active {
  transition: transform 0.2s ease 0.1s;
}
.scale-leave-active {
  transition: transform 0.2s ease;
}
.scale-enter-from, .scale-leave-to {
  transform: scale(0);
}
</style>
