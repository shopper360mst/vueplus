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
  },
  locale: {
    type: String,
    default: 'en'
  }
})

const { t } = useI18n()

const tcUrl = computed(() => {
  if (props.proxyUrl.includes('bestwithcarlsberg.my')) {
    return 'https://bestwithcarlsberg.my/download/pdfs/CNY_2026_TNC.pdf?v=2'
  }
  return `${props.proxyUrl}build/pdfs/CNY_2026_TNC.pdf?v=2`
})
</script>

<template>
  <!-- Desktop Footer -->
  <footer :class="['w-full flex flex-col items-center h-[120px] hidden lg:flex relative', customClass, 'bg-primary']">
    <AppMarquee></AppMarquee>
    <div data-nosnippet class="container relative max-w-full flex flex-col items-start justify-center footer-responsive-sizing overflow-hidden bg-primary p-4">
      <div :class="['flex flex-row gap-2 w-full items-center text-white justify-start', locale === 'ch' ? 'text-[7.5px] min-[400px]:text-[9px] lg:text-[14px]' : 'text-[5.5px] min-[400px]:text-[7px] lg:text-[12px]']">
        <div :class="['flex flex-row gap-1 w-full items-center text-white justify-start', locale === 'ch' ? 'font-ch' : 'font-black', `footer-gap-${locale}`]">
          <a :href="tcUrl" target="_blank" :class="['text-white underline uppercase block', `navbar-${locale}`, `app-label-font-footer-${locale}`]">
            {{ t('footer.terms_conditions') }}
          </a>
          |
          <a :href="`${proxyUrl}${locale}/faq`" :class="['text-white underline uppercase block', `navbar-${locale}`, `app-label-font-footer-${locale}`]">
            {{ t('footer.faq') }}
          </a>
          |
          <a href="https://carlsbergmalaysia.com.my/privacy-policy/" target="_blank" :class="['text-white underline uppercase block', `navbar-${locale}`, `app-label-font-footer-${locale}`]">
            {{ t('footer.privacy_policy') }}
          </a>
          |
          <a :href="`${proxyUrl}${locale}/beersyoulove`" :class="['text-white underline uppercase block', `navbar-${locale}`, `app-label-font-footer-${locale}`]">
            {{ t('footer.beers_love') }}
          </a>
          |
          <a href="https://carlsbergmalaysia.com.my/sustainability/social/zero-irresponsible-drinking/" target="_blank" :class="['text-white underline uppercase block', `navbar-${locale}`, `app-label-font-footer-${locale}`]">
            {{ t('footer.responsible_drinking') }}
          </a>
        </div>
      </div>
      
      <div aria-label="desktop footer" class="flex flex-col w-full md:w-auto justify-between footer-social-icons-container">
        <div class="flex flex-row gap-2 grow justify-start items-center">
          <p data-nosnippet :class="['text-white', locale === 'ch' ? 'text-[7.5px] min-[400px]:text-[9px] lg:text-[14px]' : 'text-[5.5px] min-[400px]:text-[7px] lg:text-[12px]']">{{ t('footer.faq_footer_1') }}</p>
          <p data-nosnippet :class="['text-white', locale === 'ch' ? 'text-[7.5px] min-[400px]:text-[9px] lg:text-[14px]' : 'text-[5.5px] min-[400px]:text-[7px] lg:text-[12px]']">{{ t('footer.faq_footer_2') }}</p>
        </div>
        <div class="flex flex-row gap-2 grow justify-start items-center">
          <p data-nosnippet :class="['text-white', locale === 'ch' ? 'text-[7.5px] min-[400px]:text-[9px] lg:text-[14px]' : 'text-[5.5px] min-[400px]:text-[7px] lg:text-[12px]']">{{ t('footer.faq_footer_3') }}</p>
          <p data-nosnippet :class="['text-white', locale === 'ch' ? 'text-[7.5px] min-[400px]:text-[9px] lg:text-[14px]' : 'text-[5.5px] min-[400px]:text-[7px] lg:text-[12px]']">{{ t('footer.disclaimer_2') }}</p>
          <img src="@/assets/svgs/disclaimer_new.svg" alt="footer" width="19%" class="max-w-[142px]"/>
        </div>
      </div>
    </div>
    <div class="absolute bottom-[0%] right-[1%] flex flex-row gap-2 items-center justify-end w-[100px] h-1/2">
      <a href="https://www.instagram.com/carlsbergmy?igsh=ZWZmdngybDM3N2x5" target="_blank" class="block">
        <img src="@/assets/images/logo-ig.png" alt="ig" width="20" height="20" />
      </a>
      <a href="https://www.facebook.com/share/18f9GqW8rt/?mibextid=wwXIfr" target="_blank" class="block">
        <img src="@/assets/images/logo-fb.png" alt="fb" width="20" height="20" />
      </a>
    </div>
  </footer>

  <!-- Mobile Footer -->
  <footer :class="['w-full flex flex-col items-center lg:hidden relative', customClass, 'bg-primary home-mobile-footer-new']">
    <AppMarquee></AppMarquee>
    <div class="absolute bottom-[6%] right-[1%] flex flex-row gap-2 items-center justify-end w-[100px] h-1/2">
      <a href="https://www.instagram.com/carlsbergmy?igsh=ZWZmdngybDM3N2x5" target="_blank" class="block">
        <img src="@/assets/images/logo-ig.png" width="18" height="18">
      </a>
      <a href="https://www.facebook.com/share/18f9GqW8rt/?mibextid=wwXIfr" target="_blank" class="block">
        <img src="@/assets/images/logo-fb.png" width="18" height="18">
      </a>
    </div>
    <div class="flex flex-row gap-3 justify-center py-3">
    </div>
    <div :class="['flex flex-row gap-2 md:gap-3 items-center justify-center w-full md:max-w-[40%] px-1', locale === 'ch' ? 'font-ch' : 'font-black']">
      <div class="flex items-start px-2 w-auto justify-center">
        <a :href="tcUrl" target="_blank" data-nosnippet :class="['text-white underline uppercase block', `navbar-${locale}`, `home-footer-new-${locale}`]">
          {{ t('footer.terms_conditions') }}
        </a>
      </div>
      <div data-nosnippet class="flex text-white h-full" style="width:3px;border-left:1px solid currentColor"></div>
      <div class="flex items-start px-2 w-auto justify-center">
        <a :href="`${proxyUrl}${locale}/faq`" data-nosnippet :class="['text-white underline uppercase block', `navbar-${locale}`, `home-footer-new-${locale}`]">
          {{ t('footer.faq') }}
        </a>
      </div>
      <div data-nosnippet class="flex text-white h-full" style="width:3px;border-left:1px solid currentColor"></div>
      <div class="flex items-start px-2 w-auto justify-center">
        <a href="https://carlsbergmalaysia.com.my/privacy-policy/" target="_blank" data-nosnippet :class="['text-white underline uppercase block', `navbar-${locale}`, `home-footer-new-${locale}`]">
          {{ t('footer.privacy_policy') }}
        </a>
      </div>
      <div data-nosnippet class="flex text-white h-full" style="width:3px;border-left:1px solid currentColor"></div>
      <div class="flex items-start px-2 w-auto justify-center">
        <a :href="`${proxyUrl}${locale}/beersyoulove`" data-nosnippet :class="['text-white underline uppercase block', `navbar-${locale}`, `home-footer-new-${locale}`]">
          {{ t('footer.beers_love') }}
        </a>
      </div>
      <div data-nosnippet class="flex text-white h-full" style="width:3px;border-left:1px solid currentColor"></div>
      <div class="flex items-start px-2 w-auto justify-center">
        <a href="https://carlsbergmalaysia.com.my/sustainability/social/zero-irresponsible-drinking/" target="_blank" data-nosnippet :class="['text-white underline uppercase block', `navbar-${locale}`, `home-footer-new-${locale}`]">
          {{ t('footer.responsible_drinking') }}
        </a>
      </div>
    </div>
    <div class="flex px-4 md:px-6 pe-1 pt-2">
      <p data-nosnippet :class="['text-white text-center', `home-footer-new-disclaimer-${locale}`, locale === 'ch' ? 'text-[7.5px] min-[400px]:text-[9px] lg:text-[14px]' : 'text-[5.5px] min-[400px]:text-[7px] lg:text-[12px]']">
        {{ t('footer.faq_footer_1') }}
        <br/>
        {{ t('footer.faq_footer_2') }}
      </p>
    </div>
    <div class="flex flex-row w-full gap-2 justify-center items-start">
      <div data-nosnippet :class="['text-white', `home-footer-new-disclaimer-${locale}`, locale === 'ch' ? 'text-[7.5px] min-[400px]:text-[9px] lg:text-[14px]' : 'text-[5.5px] min-[400px]:text-[7px] lg:text-[12px]']">
        {{ t('footer.disclaimer_1') }}
        {{ t('footer.disclaimer_2') }}
      </div>
    </div>
    <div class="flex flex-row w-full gap-2 justify-center items-end py-1" style="height:5dvh">
      <img data-nosnippet src="@/assets/svgs/disclaimer_new.svg" alt="footer" width="100%" class="max-w-[142px]"/>
    </div>
  </footer>
</template>

<style scoped>
/* Add classes like footer-responsive-sizing if they are not in global tailwind */
</style>
