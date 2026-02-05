/**
 * CSRF Utility to handle token retrieval from meta tags or window object
 */

export const getCsrfToken = () => {
  // 1. Try meta tag (recommended for Symfony + Vue)
  const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  if (metaToken) return metaToken

  // 2. Try window object (if injected directly into template)
  if (window.CSRF_TOKEN) return window.CSRF_TOKEN

  // 3. Try fallback to hidden input (common in older templates)
  const inputToken = document.getElementById('crsfToken')?.value || document.getElementById('csrf_token')?.value
  if (inputToken) return inputToken

  return null
}

/**
 * Example of how to use this in a fetch/axios request:
 * 
 * const response = await fetch('/api/submit', {
 *   method: 'POST',
 *   headers: {
 *     'Content-Type': 'application/json',
 *     'X-CSRF-TOKEN': getCsrfToken()
 *   },
 *   body: JSON.stringify(data)
 * })
 */
