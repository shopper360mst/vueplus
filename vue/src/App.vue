<script setup>
import { RouterView } from 'vue-router'
import NavBar from './components/NavBar.vue'
</script>

<template>
  <div class="min-h-screen flex flex-col bg-gray-50 dark:bg-black">
    <NavBar />
    <main class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center">
      <RouterView />
    </main>
  </div>
</template>

<style>
/* Global styles if needed, but Tailwind is preferred */
</style>
