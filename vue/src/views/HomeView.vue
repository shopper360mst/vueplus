<script setup>
import { useUIStore } from '../stores/ui'
import { useI18n } from 'vue-i18n'
import { useSEO } from '../composables/useSEO'

const uiStore = useUIStore()
const { t, locale } = useI18n()

useSEO('seo.home_title', 'seo.home_description')
// Opens GWP form for a specific channel and product
// uiStore.openGwpForm('SHM_WM', 1);

</script>

<template>
  <div class="flex flex-col items-center justify-center min-h-[calc(100vh-60px)] bg-tertiary">
    <!-- Hero Section -->
    <section class="w-full p-5 flex justify-center relative overflow-hidden">
      <div class="container px-5">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
          <div class="lg:col-span-7">
            <h1
              :class="['text-5xl lg:text-[80px] leading-[1.1] tracking-tight text-black dark:text-white mb-8', locale === 'ch' ? 'font-normal' : 'font-extrabold']"
            >
              {{ t('home.title') }} <br />
              <span class="text-gray-400 dark:text-gray-600">{{ t('home.subtitle') }}</span>
            </h1>
            <p
              class="text-xl lg:text-2xl text-gray-600 max-w-2xl p-2 mb-12 leading-relaxed"
            >
              {{ t('home.description') }}
            </p>
            <div class="flex flex-col sm:flex-row gap-4 py-3">
              <button
                @click="uiStore.showToast(t('home.closed'), 'bg-white')"
                :class="['btn-primary-alt rounded-full text-lg', locale === 'ch' ? 'font-bold' : 'font-extrabold']"
              >
                {{ t('home.signup') }}
              </button>
              <button
                @click="uiStore.openPopup('ty')"
                :class="['btn-primary-alt bg-transparent border-white rounded-full text-lg', locale === 'ch' ? 'font-bold' : 'font-extrabold']"
              >
                {{ t('home.documentation') }}
              </button>
            </div>
          </div>

          <div class="lg:col-span-5 relative">
            <div
              class="bg-gray-50 dark:bg-zinc-900 rounded-2xl p-8 border border-gray-100 dark:border-white/10 shadow-2xl transform lg:rotate-2 hover:rotate-0 transition-transform duration-500"
            >
              <div class="flex gap-2 mb-6">
                <div class="w-3 h-3 rounded-full bg-red-400"></div>
                <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                <div class="w-3 h-3 rounded-full bg-green-400"></div>
              </div>
              <pre class="text-sm font-mono text-gray-800 dark:text-gray-200 overflow-x-auto">
                <code>{
                  "project": "VuePlus",
                  "status": "scaling",
                  "nodes": ["us-east", "eu-west", "ap-south"],
                  "metrics": {
                    "latency": "12ms",
                    "uptime": "99.999%"
                  }
                }</code></pre>
            </div>

            <!-- Abstract background element -->
            <div
              class="absolute -top-10 -right-10 w-64 h-64 bg-gray-100 dark:bg-white/5 rounded-full -z-10 blur-3xl"
            ></div>
          </div>
        </div>
      </div>
    </section>

    <!-- Trusted By Section -->
    <section
      class="w-full p-10 flex justify-center py-20 border-t border-gray-100 dark:border-white/10"
    >
      <div class="w-full max-w-[1600px] px-6 lg:px-12">
        <p
          class="block text-xs font-bold tracking-[0.3em] uppercase text-gray-400 dark:text-gray-600 pb-2"
        >
          {{ t('home.trustedBy') }}
        </p>
        <div
          class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-12 items-center opacity-50 grayscale hover:grayscale-0 transition-all duration-500"
        >
          <div class="text-2xl font-black italic tracking-tighter">BREX</div>
          <div class="text-2xl font-black tracking-widest">MERCURY</div>
          <div class="text-2xl font-black">Ramp</div>
          <div class="text-2xl font-black uppercase">Flex</div>
          <div class="text-2xl font-black tracking-tight">Best Egg</div>
          <div class="text-2xl font-black italic">Affirm</div>
        </div>
      </div>
    </section>
  </div>
</template>

<style></style>
