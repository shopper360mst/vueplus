<script setup>
import { ref, computed } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useSessionStore } from '../stores/session'
import { useI18n } from 'vue-i18n'
import navigation from '../data/navigation.json'

const isMenuOpen = ref(false)
const sessionStore = useSessionStore()
const { locale, t } = useI18n()
const route = useRoute()
const router = useRouter()

const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value
}

const currentLocale = computed(() => route.params.locale || locale.value)

const toggleLocale = () => {
  const newLocale = currentLocale.value === 'en' ? 'ch' : 'en'
  sessionStore.setLocale(newLocale)
  
  // Navigate to the same path but with the new locale
  const newPath = route.path.replace(`/${currentLocale.value}`, `/${newLocale}`)
  router.push(newPath)
}

const campaignCode = import.meta.env.VITE_CAMPAIGN_CODE || 'cny'

const navLinks = computed(() => navigation.map(link => ({
  id: link.id,
  name: t(`nav.${link.id}`),
  path: `/${campaignCode}/${currentLocale.value}/${link.path}`
})))
</script>

<template>
  <header
    class="w-full flex justify-center bg-secondary sticky top-0 z-50 transition-all duration-300"
  >
    <div class="w-full px-6 lg:px-12">
      <div class="flex justify-between h-16 items-center">
        <!-- Logo -->
        <div class="flex items-center">
          <RouterLink :to="`/${campaignCode}/${currentLocale}`" class="flex items-center gap-2 group transition-opacity">
            <img src="@/assets/images/carlsberg_logo.png" alt="Carlsberg - Probably the best beer in the world" class="h-10 w-auto" />
          </RouterLink>
        </div>

        <!-- Desktop Nav -->
        <nav class="hidden md:flex items-center gap-10">
          <div class="flex items-center gap-8">
            <RouterLink
              v-for="link in navLinks"
              :key="link.id"
              :to="link.path"
              :class="['font-semibold text-white hover:text-tertiary transition-colors', locale === 'ch' ? 'text-[15px]' : 'text-[13px]']"
            >
              {{ link.name }}
            </RouterLink>
          </div>

           <button 
            @click="toggleLocale"
            class="text-xs text-white hover:text-tertiary transition-colors uppercase font-bold border border-white hover:border-tertiary px-2 py-0.5 rounded"
          >
            {{ currentLocale }}
          </button>

        </nav>

        <!-- Mobile Menu Button -->
        <div class="flex items-center md:hidden">
          <button
            @click="toggleMenu"
            class="p-2 text-white hover:text-tertiary transition-colors focus:outline-none"
          >
            <span class="sr-only">Toggle menu</span>
            <svg
              v-if="!isMenuOpen"
              class="w-6 h-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"
              />
            </svg>
            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </header>

  <!-- Mobile Menu -->
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="opacity-0 -translate-y-4"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 -translate-y-4"
  >
    <div
      v-show="isMenuOpen"
      class="md:hidden fixed inset-x-0 top-16 z-[40] bg-secondary border-b border-white/10 shadow-xl"
    >
      <div class="px-6 py-8 flex flex-col gap-6">
        <RouterLink
          v-for="link in navLinks"
          :key="link.id"
          :to="link.path"
          @click="isMenuOpen = false"
          :class="['font-medium text-white hover:text-tertiary transition-colors', locale === 'ch' ? 'text-[22px]' : 'text-xl']"
        >
          {{ link.name }}
        </RouterLink>
         <button 
            @click="toggleLocale"
            class="text-xs text-white hover:text-tertiary transition-colors uppercase font-bold border border-white hover:border-tertiary px-2 py-0.5 rounded self-start"
          >
            {{ currentLocale }}
          </button>

      </div>
    </div>
  </Transition>
</template>
