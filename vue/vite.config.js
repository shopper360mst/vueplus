import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig(() => {
  const base = '/'

  return {
    base: base,
    plugins: [
      tailwindcss(),
      vue(),
    ],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
        'translations': fileURLToPath(new URL('./translations', import.meta.url))
      },
    },
    server: {
      proxy: {
        '/endpoint': {
          target: 'http://localhost:9998',
          changeOrigin: true,
          secure: false,
        },
        '/api': {
          target: 'http://localhost:9998',
          changeOrigin: true,
          secure: false,
        },
      },
    },
  }
})
