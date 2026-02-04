<script setup>
defineProps({
  videoSrc: {
    type: String,
    default: '/videos/footer/mark2.mp4'
  }
})
</script>

<template>
  <div class="app-marque-container overflow-x-hidden">
    <video :src="videoSrc" autoplay loop muted playsinline class="app-marque-svg" style="height:100%;width:100%; object-fit: cover;"></video>
  </div>
</template>

<style scoped>
.app-marque-container {
  /* Add any specific styles if needed */
}
</style>
