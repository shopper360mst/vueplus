<script setup>
import { RouterView } from 'vue-router'
import NavBar from './components/NavBar.vue'
</script>

<template>
  <div class="min-h-screen flex flex-col bg-white dark:bg-black">
    <NavBar />
    <main class="w-full flex-grow">
      <RouterView />
    </main>
  </div>
</template>

<style>
/* Global styles if needed, but Tailwind is preferred */
</style>
