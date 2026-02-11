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

const setLocale = (newLocale) => {
  if (currentLocale.value === newLocale) return
  sessionStore.setLocale(newLocale)
  const newPath = route.path.replace(`/${currentLocale.value}`, `/${newLocale}`)
  router.push(newPath)
}

const campaignCode = import.meta.env.VITE_CAMPAIGN_CODE || 'cny'

const navLinks = computed(() => navigation.map(link => ({
  id: link.id,
  name: t(`nav.${link.id}`),
  path: `/${campaignCode}/${currentLocale.value}/${link.path}`
})))

const isLinkActive = (link) => {
  return route.path === link.path || route.name === link.id
}
</script>

<template>
  <header
    class="w-full flex justify-center bg-secondary sticky top-0 z-50 transition-all duration-300"
  >
    <div class="w-full px-6 lg:px-12">
      <div class="flex justify-between h-16 items-center relative">
        <!-- Mobile Menu Button -->
        <div class="flex items-center md:hidden z-10">
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

        <!-- Logo -->
        <div class="flex items-center md:static absolute left-1/2 -translate-x-1/2 md:translate-x-0">
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
              :class="[
                'transition-colors',
                isLinkActive(link) ? 'text-tertiary' : 'text-white hover:text-tertiary',
                locale === 'ch' ? 'text-[15px] font-normal' : 'text-[13px] font-ny-black'
              ]"
            >
              {{ link.name }}
            </RouterLink>
          </div>

          <button
            @click="setLocale(currentLocale === 'en' ? 'ch' : 'en')"
            class="text-xs text-white hover:text-tertiary transition-colors uppercase font-ny-black border border-white hover:border-tertiary px-2 py-0.5 rounded"
          >
            {{ currentLocale }}
          </button>
        </nav>
      </div>
    </div>
  </header>

  <!-- Mobile Menu -->
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="-translate-x-full"
    enter-to-class="translate-x-0"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="translate-x-0"
    leave-to-class="-translate-x-full"
  >
    <div
      v-show="isMenuOpen"
      class="md:hidden fixed inset-x-0 bottom-0 top-16 z-[40] bg-secondary flex flex-col overflow-y-auto"
    >
      <!-- Menu Content -->
      <div class="px-8 py-10 flex flex-col gap-6 flex-grow">
        <RouterLink
          v-for="link in navLinks"
          :key="link.id"
          :to="link.path"
          @click="isMenuOpen = false"
          :class="[
            'transition-colors uppercase tracking-wider',
            isLinkActive(link) ? 'text-tertiary' : 'text-white hover:text-tertiary',
            locale === 'ch' ? 'text-2xl font-normal' : 'text-xl font-ny-black'
          ]"
        >
          {{ link.name }}
        </RouterLink>

        <!-- Separator -->
        <hr class="border-t-2 border-gray-200 my-2" />

        <!-- Locale Selector -->
        <div class="flex gap-3 items-center">
          <button
            @click="setLocale('en')"
            :class="[
              'text-sm font-ny-black w-32 py-2 rounded-full border-2 transition-colors uppercase text-center',
              currentLocale === 'en' 
                ? 'bg-tertiary border-tertiary text-secondary' 
                : 'border-white text-white hover:border-tertiary hover:text-tertiary'
            ]"
          >
            English
          </button>
          <button
            @click="setLocale('ch')"
            :class="[
              'text-sm font-normal w-32 py-2 rounded-full border-2 transition-colors text-center',
              currentLocale === 'ch' 
                ? 'bg-tertiary border-tertiary text-secondary' 
                : 'border-white text-white hover:border-tertiary hover:text-tertiary'
            ]"
          >
            中文
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>
