<script setup>
import { useI18n } from 'vue-i18n'
import { useSEO } from '../composables/useSEO'

const { t } = useI18n()

useSEO('seo.about_title', 'seo.about_description')
</script>

<template>
  <section
    aria-label="about page"
    class="flex flex-col items-center justify-center min-h-[calc(100vh-60px)]"
  >
    <h1>{{ t('about.title') }}</h1>
  </section>
</template>

<style></style>
