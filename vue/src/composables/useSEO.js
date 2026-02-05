import { watchEffect } from 'vue'
import { useI18n } from 'vue-i18n'

export function useSEO(titleKey, descriptionKey) {
  const { t, locale } = useI18n()

  watchEffect(() => {
    // Update Document Title
    if (titleKey) {
      document.title = t(titleKey)
    }

    // Update Meta Description
    if (descriptionKey) {
      let metaDescription = document.querySelector('meta[name="description"]')
      if (!metaDescription) {
        metaDescription = document.createElement('meta')
        metaDescription.name = 'description'
        document.head.appendChild(metaDescription)
      }
      metaDescription.setAttribute('content', t(descriptionKey))
    }

    // Update HTML Lang attribute
    document.documentElement.lang = locale.value
  })
}
