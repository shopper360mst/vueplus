<script setup>
import { useUIStore } from '../stores/ui'

const uiStore = useUIStore()

const triggerToast = () => {
  uiStore.showToast('Hello from Vue Store!', 'bg-blue-50')
}

const triggerPopup = () => {
  uiStore.openPopup('ty')
}
</script>

<template>
  <section aria-label="home page" class="w-full">
    <h1 class="text-3xl font-bold mb-8">Welcome to VuePlus</h1>

    <div class="space-x-4">
      <button 
        @click="triggerToast"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
      >
        Show Toast
      </button>
      <button 
        @click="triggerPopup"
        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
      >
        Show Popup
      </button>
    </div>
  </section>
</template>
