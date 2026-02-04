<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'

const isMenuOpen = ref(false)

const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value
}

const navLinks = [
  { name: 'Home', path: '/' },
  { name: 'About', path: '/about' }
]
</script>

<template>
  <nav class="bg-white/80 dark:bg-black/80 backdrop-blur-md border-b border-apple-border sticky top-0 z-50 transition-colors duration-300">
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-12 items-center">
        <!-- Logo -->
        <div class="flex items-center">
          <RouterLink to="/" class="flex items-center gap-2 group opacity-80 hover:opacity-100 transition-opacity">
            <svg class="w-5 h-5 text-current" fill="currentColor" viewBox="0 0 24 24">
              <path d="M18.71 15.19a6.11 6.11 0 0 1-3.12 5.33 6.46 6.46 0 0 1-3.22.82c-.93 0-1.76-.27-2.61-.27-.85 0-1.8.27-2.67.27a6.41 6.41 0 0 1-5.46-6.48c0-4 2.57-6.23 5.12-6.23.82 0 1.5.21 2.22.21.71 0 1.63-.25 2.57-.25a5.52 5.52 0 0 1 4.5 2.47 5.33 5.33 0 0 0-2.61 4.57 5.2 5.2 0 0 0 2.73 4.54l.55.3zM12.91 3.25a5.03 5.03 0 0 1-1.18 3.47 4.14 4.14 0 0 1-3.41 1.69 4.3 4.3 0 0 1 .1-3.52 4.41 4.41 0 0 1 3.31-1.64 4.61 4.61 0 0 1 1.18 0z" />
            </svg>
            <span class="text-lg font-semibold">
              VuePlus
            </span>
          </RouterLink>
        </div>

        <!-- Desktop Nav -->
        <div class="hidden md:flex items-center gap-6">
          <RouterLink
            v-for="link in navLinks"
            :key="link.path"
            :to="link.path"
            class="px-3 py-1 text-xs font-normal transition-all duration-200 opacity-80 hover:opacity-100"
            active-class="opacity-100"
          >
            {{ link.name }}
          </RouterLink>

          <a href="https://github.com" target="_blank" class="text-xs opacity-80 hover:opacity-100 transition-opacity">
            GitHub
          </a>
          <button class="bg-black dark:bg-white text-white dark:text-black px-3 py-1 rounded-full text-xs font-medium hover:opacity-90 transition-opacity">
            Get Started
          </button>
        </div>

        <!-- Mobile Menu Button -->
        <div class="flex items-center md:hidden">
          <button
            @click="toggleMenu"
            class="p-2 opacity-80 hover:opacity-100 transition-opacity focus:outline-none"
          >
            <span class="sr-only">Toggle menu</span>
            <svg
              v-if="!isMenuOpen"
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg
              v-else
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile Menu -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-show="isMenuOpen" class="md:hidden bg-white dark:bg-black fixed inset-0 top-12 z-40">
        <div class="px-6 py-8 space-y-4 flex flex-col items-center">
          <RouterLink
            v-for="link in navLinks"
            :key="link.path"
            :to="link.path"
            @click="isMenuOpen = false"
            class="text-2xl font-semibold opacity-90 hover:opacity-100"
            active-class="opacity-100"
          >
            {{ link.name }}
          </RouterLink>
          <div class="pt-8 w-full border-t border-gray-100 dark:border-gray-900 flex flex-col items-center gap-4">
            <a href="https://github.com" target="_blank" class="text-xl opacity-90">GitHub</a>
            <button class="w-full max-w-xs bg-black dark:bg-white text-white dark:text-black px-6 py-3 rounded-full text-lg font-medium">
              Get Started
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </nav>
</template>
