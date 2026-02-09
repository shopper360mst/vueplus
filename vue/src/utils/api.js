import DataHelper from "@shopper360mst/data_helper"
import { getCsrfToken } from './csrf'

const connector = new DataHelper()
connector.setTimeout(10000)

/**
 * API Utility for Vue using DataHelper
 */

/**
 * GET request helper
 * @param {string} endpoint 
 * @param {object} params 
 * @returns {Promise<any>}
 */
export const get = async (endpoint, params = {}) => {
  const token = getCsrfToken()
  connector.setHeaders({
    'Authorization': `Bearer ${token || ''}`
  })
  return connector.get(endpoint, params)
}

/**
 * POST request helper (postTo)
 * @param {string} endpoint 
 * @param {object|FormData} payload 
 * @param {string} cmsId - Optional parameter matching the Alpine helper signature
 * @returns {Promise<any>}
 */
export const postTo = async (endpoint, payload, cmsId = null) => {
  const token = getCsrfToken()
  if (token) {
    alert(`CSRF Token Found: ${token.substring(0, 8)}...`)
  } else {
    alert('CSRF Token NOT Found!')
  }

  connector.setHeaders({
    'Authorization': `Bearer ${token || ''}`
  })

  return connector.postTo(endpoint, payload, cmsId)
}
