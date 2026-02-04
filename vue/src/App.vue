<script setup>
import { RouterView } from 'vue-router'
import NavBar from './components/NavBar.vue'
import AppFooter from './components/AppFooter.vue'
import AppToast from './components/AppToast.vue'
import AppPopup from './components/AppPopup.vue'
import AppGate from './components/AppGate.vue'
import AppForm from './components/AppForm.vue'
</script>

<template>
  <div class="min-h-screen flex flex-col bg-gray-50 dark:bg-black">
    <AppGate />
    <NavBar />
    <main class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center flex-grow">
      <RouterView />
    </main>
    <AppFooter />
    
    <!-- Global Overlays -->
    <AppToast />
    <AppPopup />
    <AppForm />
  </div>
</template>

<style>
/* Global styles if needed, but Tailwind is preferred */
</style>
