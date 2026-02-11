import DataHelper from "@shopper360mst/data_helper"
import axios from 'axios'
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
  const headers = {
    'Authorization': `Bearer ${token || ''}`
  }
  return connector.getWithParamFrom(endpoint, params, headers)
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

  const headers = {
    'Authorization': `Bearer ${token || ''}`
  }

  // If payload is FormData, we bypass DataHelper.postTo because it calls JSON.stringify(),
  // which would break the FormData/File upload.
  if (payload instanceof FormData) {
    return axios.post(endpoint, payload, {
      headers,
      timeout: 10000
    })
  }

  return connector.postTo(endpoint, payload, headers)
}
