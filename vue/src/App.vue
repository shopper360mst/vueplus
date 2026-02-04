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
      
      <!-- PAGES VIEW -->
      <main class="w-full flex-grow"><RouterView /></main>
      <!-- PAGES VIEW -->

      <AppFooter />
      
      <!-- Global Overlays -->
      <AppToast />
      <AppPopup />
      <AppForm />
    </div>
</template>

<style>
</style>
