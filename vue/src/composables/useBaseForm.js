import { ref, reactive, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { postTo } from '../utils/api'
import postalData from '../data/postal-data.json'

export function useBaseForm(config = {}) {
  const { t, locale } = useI18n()

  const isSubmitting = ref(false)
  const fileUploaded = ref(false)
  const uploadFiles = ref(null)
  const uploadReceiptValue = ref('')
  const filteredPostcodes = ref([])
  const showPostcodeDropdown = ref(false)

  const formData = reactive({
    full_name: '',
    mobile_no: '',
    mobile_prefix: '60',
    email: '',
    national_id: '',
    nric_prefix: 'NRIC',
    receipt_no: '',
    form_code: config.formCode || '',
    channel_name: '',
    recipent_full_name: '',
    recipent_mobile_no: '',
    recipent_mobile_prefix: '60',
    address: '',
    address2: '',
    postcode: '',
    city: '',
    state: '',
    privacy: false,
    tnc: false,
    ...config.initialData
  })

  // NRIC Masking Logic
  watch(() => formData.national_id, (val) => {
    if (formData.nric_prefix === 'NRIC') {
      let cleaned = val.replace(/\D/g, '').substring(0, 12)
      let masked = ''
      if (cleaned.length > 0) {
        masked += cleaned.substring(0, 6)
        if (cleaned.length > 6) {
          masked += '-' + cleaned.substring(6, 8)
          if (cleaned.length > 8) {
            masked += '-' + cleaned.substring(8, 12)
          }
        }
      }
      if (val !== masked) formData.national_id = masked
    }
  })

  // Postcode Lookup Logic
  watch(() => formData.postcode, (val) => {
    if (val.length === 0) {
      formData.city = ''
      formData.state = ''
      filteredPostcodes.value = []
      showPostcodeDropdown.value = false
      return
    }

    if (val.length >= 2) {
      const matches = postalData.filter(o => o.postcode.startsWith(val)).slice(0, 10)
      filteredPostcodes.value = matches
      showPostcodeDropdown.value = matches.length > 0
    } else {
      showPostcodeDropdown.value = false
    }

    const found = postalData.find(o => o.postcode == val)
    if (found) {
      formData.city = found.city
      formData.state = found.state
      if (val.length === 5) showPostcodeDropdown.value = false
    }
  })

  // Sync recipient info with main info by default
  watch(() => formData.mobile_no, (val) => {
    formData.recipent_full_name = formData.full_name
    formData.recipent_mobile_no = val
  })

  watch(() => formData.mobile_prefix, (val) => {
    formData.recipent_mobile_prefix = val
  })

  const selectPostcode = (item) => {
    formData.postcode = item.postcode
    formData.city = item.city
    formData.state = item.state
    showPostcodeDropdown.value = false
  }

  const handleFileChange = async (event) => {
    const file = event.target.files[0]
    if (!file) return

    if (file.size > 15 * 1024 * 1024) {
      alert(locale.value === 'ch' ? '文件大小超过15MB限制。' : 'File size exceeds 15MB limit.')
      event.target.value = ''
      return
    }

    try {
      const arrayBuffer = await file.arrayBuffer()
      const memoryFile = new File([arrayBuffer], file.name, {
        type: file.type,
        lastModified: new Date().getTime()
      })

      fileUploaded.value = true
      uploadFiles.value = memoryFile
      uploadReceiptValue.value = memoryFile.name
    } catch (e) {
      console.error("File read error:", e)
      alert("File system error. Please try again.")
      event.target.value = ''
    }
  }

  const validateNRIC = (nric) => {
    if (formData.nric_prefix !== 'NRIC') return true
    if (nric.length !== 12 && nric.replace(/-/g, '').length !== 12) return false
    
    const cleanNric = nric.replace(/-/g, '')
    const year = parseInt(cleanNric.substring(0, 2))
    const currentYearShort = new Date().getFullYear() % 100
    let birthYear = year > currentYearShort ? 1900 + year : 2000 + year
    const age = new Date().getFullYear() - birthYear
    
    return age >= 21
  }

  const resetForm = () => {
    Object.assign(formData, {
      full_name: '', mobile_no: '', mobile_prefix: '60', email: '',
      national_id: '', nric_prefix: 'NRIC', receipt_no: '',
      recipent_full_name: '', recipent_mobile_no: '', recipent_mobile_prefix: '60',
      address: '', address2: '', postcode: '', city: '', state: '',
      privacy: false, tnc: false,
      ...config.initialData
    })
    fileUploaded.value = false
    uploadFiles.value = null
    uploadReceiptValue.value = ''
  }

  const baseSubmit = async (additionalPayload = {}) => {
    if (isSubmitting.value) return
    
    if (!formData.full_name || !formData.mobile_no || !formData.national_id || !formData.privacy || !formData.tnc) {
      alert(t('form.required_fields'))
      return
    }

    if (!validateNRIC(formData.national_id)) {
      alert(t('form.age_requirement'))
      return
    }

    isSubmitting.value = true
    
    try {
      const payload = new FormData()
      Object.keys(formData).forEach(key => {
        if (key === 'mobile_no') {
          payload.append(key, formData.mobile_prefix + formData[key])
        } else if (key === 'recipent_mobile_no') {
          payload.append(key, formData.recipent_mobile_prefix + formData[key])
        } else {
          payload.append(key, formData[key])
        }
      })

      Object.keys(additionalPayload).forEach(key => {
        payload.append(key, additionalPayload[key])
      })
      
      payload.append('locale', locale.value)
      
      if (uploadFiles.value) {
        payload.append('upload_receipt', uploadFiles.value)
      }

      const result = await postTo('/endpoint/submit', payload)
      return result
    } catch (error) {
      console.error('Submission error:', error)
      throw error
    } finally {
      isSubmitting.value = false
    }
  }

  return {
    formData,
    isSubmitting,
    fileUploaded,
    uploadFiles,
    uploadReceiptValue,
    filteredPostcodes,
    showPostcodeDropdown,
    handleFileChange,
    resetForm,
    validateNRIC,
    selectPostcode,
    baseSubmit,
    t,
    locale
  }
}
