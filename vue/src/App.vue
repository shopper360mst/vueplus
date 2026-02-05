<script setup>
import { RouterView } from 'vue-router'
import { useI18n } from 'vue-i18n'
import NavBar from './components/NavBar.vue'
import AppFooter from './components/AppFooter.vue'
import AppToast from './components/AppToast.vue'
import AppPopup from './components/AppPopup.vue'
import AppGate from './components/AppGate.vue'
import AppForm from './components/AppForm.vue'

const { locale } = useI18n()
</script>

<template>
    <div :lang="locale" class="min-h-screen flex flex-col bg-gray-50 dark:bg-black overflow-x-hidden">
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
