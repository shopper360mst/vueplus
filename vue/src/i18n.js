import { createI18n } from 'vue-i18n'

// Support satellite file system (json/yaml)
const translationFiles = import.meta.glob('../translations/*.{json,yaml,yml}', { eager: true })

const messages = {}

// Process the globbed files
for (const path in translationFiles) {
  const matched = path.match(/\/translations\/(.*)\.(json|yaml|yml)$/i)
  if (matched && matched.length > 1) {
    const locale = matched[1]
    messages[locale] = translationFiles[path].default || translationFiles[path]
  }
}

const i18n = createI18n({
  legacy: false,
  locale: 'en',
  fallbackLocale: 'en',
  messages
})

export default i18n
