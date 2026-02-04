import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useSessionStore = defineStore('session', () => {
  const sessionData = ref(JSON.parse(sessionStorage.getItem('vue_session') || '{}'))

  watch(sessionData, (newData) => {
    sessionStorage.setItem('vue_session', JSON.stringify(newData))
  }, { deep: true })

  const setItem = (key, value) => {
    sessionData.value[key] = value
  }

  const getItem = (key) => {
    return sessionData.value[key]
  }

  const removeItem = (key) => {
    delete sessionData.value[key]
  }

  const clear = () => {
    sessionData.value = {}
  }

  // Locale state
  const locale = ref(localStorage.getItem('vue_locale') || 'en')

  watch(locale, (newLocale) => {
    localStorage.setItem('vue_locale', newLocale)
  })

  const setLocale = (newLocale) => {
    locale.value = newLocale
  }

  return {
    sessionData,
    setItem,
    getItem,
    removeItem,
    clear,
    locale,
    setLocale
  }
})
