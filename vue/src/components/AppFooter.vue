<script setup>
import AppMarquee from './AppMarquee.vue'
import { useI18n } from 'vue-i18n'
import { computed } from 'vue'

const props = defineProps({
  customClass: {
    type: String,
    default: ''
  },
  proxyUrl: {
    type: String,
    default: ''
  }
})

const { t, locale } = useI18n()

const tcUrl = computed(() => {
  if (props.proxyUrl.includes('bestwithcarlsberg.my')) {
    return 'https://bestwithcarlsberg.my/download/pdfs/CNY_2026_TNC.pdf?v=2'
  }
  return `${props.proxyUrl}build/pdfs/CNY_2026_TNC.pdf?v=2`
})
</script>

<template>
  <footer :class="['w-full flex flex-col items-center relative bg-primary h-[200px] md:h-[120px]', customClass]">
    <AppMarquee />
    
    <!-- MOBILE LINKS (shown only on small screens) -->
    <div data-nosnippet 
      class="container 
      flex 
      md:hidden 
      mx-auto 
      p-2 
      flex-col 
      items-center 
      justify-center 
      gap-x-1 
      gap-y-2 
      max-w-full
      "
      :class="[locale === 'ch' ? 'font-ch text-[11px] font-normal' : 'text-[9px] font-ny-black']"
    >
      <div aria-label="mobile links"  class="flex flex-row gap-3 items-center">
        <a :href="tcUrl" target="_blank" class="text-white underline uppercase text-center">
          {{ t('footer.terms_conditions') }}
        </a>
        <div class="w-[1px] bg-white h-[20px] "></div>
        <a :href="`${proxyUrl}${locale}/faq`" class="text-white underline uppercase text-center">
          {{ t('footer.faq') }}
        </a>
        <div class="w-[1px] bg-white h-[20px] "></div>
        <a href="https://carlsbergmalaysia.com.my/privacy-policy/" target="_blank" class="text-white underline uppercase text-center">
          {{ t('footer.privacy_policy') }}
        </a>
        <div class="w-[1px] bg-white h-[20px] "></div>
        <a :href="`${proxyUrl}${locale}/beersyoulove`" class="text-white underline uppercase text-center">
          {{ t('footer.beers_love') }}
        </a>
        <div class="w-[1px] bg-white h-[20px] "></div>
        <a href="https://carlsbergmalaysia.com.my/sustainability/social/zero-irresponsible-drinking/" target="_blank" class="text-white underline uppercase text-center">
          {{ t('footer.responsible_drinking') }}
        </a>
      </div>
      <div class="flex py-1">
        <div class="flex flex-row gap-2 justify-end">
          <a href="https://www.instagram.com/carlsbergmy" target="_blank" class="transition-transform hover:scale-110">
            <img src="@/assets/images/logo-ig.png" alt="Instagram" width="24" height="24" />
          </a>
          <a href="https://www.facebook.com/share/18f9GqW8rt" target="_blank" class="transition-transform hover:scale-110">
            <img src="@/assets/images/logo-fb.png" alt="Facebook" width="24" height="24" />
          </a>
        </div>
      </div>
      <div data-nosnippet
        class="container flex items-center justify-center text-center gap-3">
        <p class="text-white">
          {{ t('footer.faq_footer_1') }} {{ t('footer.faq_footer_2') }}
        </p>
      </div>
      <div class="flex justify-center text-white items-center"
        :class="[locale === 'ch' ? 'font-ch text-[14px] font-normal' : 'text-[10px] font-ny-black']"
      >
        {{ t('footer.disclaimer_2') }} 
        <img src="@/assets/svgs/disclaimer_new.svg" alt="Celebrate Responsibly" class="px-1 h-[10px] w-auto" />
      </div>
    </div>

    <!-- DESKTOP LINKS (shown from md up) container -->
    <div data-nosnippet class="w-full px-6  hidden md:flex flex-col justify-start">
        <div 
          class="flex py-2 flex-row items-center justify-start gap-3"
          :class="[locale === 'ch' ? 'font-ch text-[14px] font-normal' : 'text-[12px] font-ny-black']"
        >
            <div aria-label="desktop links" class="flex flex-row gap-3">
              <a :href="tcUrl" target="_blank" class="text-white underline uppercase">
                {{ t('footer.terms_conditions') }}
              </a>
              <div class="w-[1px] bg-white h-[15px] "></div>
              <a :href="`${proxyUrl}${locale}/faq`" class="text-white underline uppercase">
                {{ t('footer.faq') }}
              </a>
              <div class="w-[1px] bg-white h-[15px] "></div>
              <a href="https://carlsbergmalaysia.com.my/privacy-policy/" target="_blank" class="text-white underline uppercase">
                {{ t('footer.privacy_policy') }}
              </a>
              <div class="w-[1px] bg-white h-[15px] "></div>
              <a :href="`${proxyUrl}${locale}/beersyoulove`" class="text-white underline uppercase">
                {{ t('footer.beers_love') }}
              </a>
              <div class="w-[1px] bg-white h-[15px] "></div>
              <a href="https://carlsbergmalaysia.com.my/sustainability/social/zero-irresponsible-drinking/" target="_blank" class="text-white underline uppercase">
                {{ t('footer.responsible_drinking') }}
              </a>
            </div>
            <div class="flex-grow">
              <div class="flex flex-row gap-2 justify-end">
                <a href="https://www.instagram.com/carlsbergmy" target="_blank" class="transition-transform hover:scale-110">
                  <img src="@/assets/images/logo-ig.png" alt="Instagram" width="20" height="20" />
                </a>
                <a href="https://www.facebook.com/share/18f9GqW8rt" target="_blank" class="transition-transform hover:scale-110">
                  <img src="@/assets/images/logo-fb.png" alt="Facebook" width="20" height="20" />
                </a>
              </div>
            </div>
        </div>
        <div class="w-full flex-row justify-center items-center">
          <div 
            class="text-white w-auto"
            :class="[locale === 'ch' ? 'font-ch text-[14px] font-normal' : 'text-[12px] font-ny-black']"
          >
            {{ t('footer.faq_footer_1') }} {{ t('footer.faq_footer_2') }}
          </div>
          <div class="flex justify-start text-white"
            :class="[locale === 'ch' ? 'font-ch text-[14px] font-normal' : 'text-[12px] font-ny-black']"
          >
            {{ t('footer.disclaimer_2') }} 
            <img src="@/assets/svgs/disclaimer_new.svg" alt="Celebrate Responsibly" class="px-1 h-[13px] w-auto" />
          </div>
        </div>
         
    </div>
    
  

   
    <!-- Footer Disclaimer Text -->
    <!-- <div class="w-full bg-primary pb-8 px-4">
      <div class="container mx-auto text-center flex flex-col gap-2"
        :class="[locale === 'ch' ? 'font-ch text-[10px]' : 'text-[8px]']"
      >
        <p class="text-white opacity-70">
          {{ t('footer.faq_footer_1') }} {{ t('footer.faq_footer_2') }}
        </p>
        <p class="text-white font-bold uppercase tracking-wider">
          {{ t('footer.disclaimer_2') }}
        </p>
        <div class="flex justify-center mt-3">
          <img src="@/assets/svgs/disclaimer_new.svg" alt="Celebrate Responsibly" class="h-8 w-auto" />
        </div>
      </div>
    </div> -->
  </footer>
</template>

<style scoped>
/* Scoped styles removed in favor of Tailwind classes */
</style>
