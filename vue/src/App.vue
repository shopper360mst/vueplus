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
import GwpForm from './components/GwpForm.vue'
import CvsForm from './components/CvsForm.vue'
import CampaignSelector from './components/CampaignSelector.vue'

// Import campaign images
import cnyD from './assets/images/cny-d.png'
import cnyM from './assets/images/cny-m.png'
import wosD from './assets/images/wos-d.png'
import wosM from './assets/images/wos-m.png'

const btn1 = {
  desktop: cnyD,
  mobile: cnyM
}
const btn2 = {
  desktop: wosD,
  mobile: wosM
}
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
      <GwpForm />
      <CvsForm />
      <CampaignSelector 
        :btn1="btn1"
        :btn2="btn2"
      />
    </div>
</template>

<style>
</style>
