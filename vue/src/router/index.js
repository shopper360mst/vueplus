import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import i18n from '../i18n'

const campaignCode = import.meta.env.VITE_CAMPAIGN_CODE || 'cny'
const supportedLocales = ['en', 'ch']

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: `/${campaignCode}/en`,
    },
    {
      path: `/${campaignCode}`,
      redirect: `/${campaignCode}/en`,
    },
    {
      path: `/${campaignCode}/:locale`,
      children: [
        {
          path: '',
          name: 'home',
          component: HomeView,
        },
        {
          path: 'about',
          name: 'about',
          component: () => import('../views/AboutView.vue'),
        },
        {
          path: 'check-status',
          name: 'check-status',
          component: () => import('../views/CheckStatusView.vue'),
        },
        {
          path: 'promotions',
          name: 'promotions',
          component: () => import('../views/PromotionView.vue'),
        },
        {
          path: ':channel',
          name: 'channel',
          component: HomeView,
        },
      ],
    },
  ],
})

router.beforeEach((to, from, next) => {
  const locale = to.params.locale

  if (locale && supportedLocales.includes(locale)) {
    if (i18n.global.locale.value !== locale) {
      i18n.global.locale.value = locale
    }
    return next()
  }

  // If we are in a subpath but no valid locale, redirect to en
  if (to.path.startsWith(`/${campaignCode}`)) {
    const remainingPath = to.path.replace(`/${campaignCode}`, '')
    if (!remainingPath || remainingPath === '/') {
       return next(`/${campaignCode}/en`)
    }
  }

  next()
})

export default router
