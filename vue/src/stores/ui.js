import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useUIStore = defineStore('ui', () => {
  // Toast State
  const toast = ref({
    isOpen: false,
    message: '',
    classes: ''
  })

  const showToast = (message, classes = '') => {
    toast.value = {
      isOpen: true,
      message,
      classes
    }
  }

  const hideToast = () => {
    toast.value.isOpen = false
  }

  // Popup State
  const popup = ref({
    isOpen: false,
    formType: 'ty' // 'cvs' or 'ty'
  })

  const openPopup = (formType = 'ty') => {
    popup.value = {
      isOpen: true,
      formType
    }
  }

  const closePopup = () => {
    popup.value.isOpen = false
  }

  // Form State (for AppForm)
  const contestForm = ref({
    isOpen: false,
    image: '',
    fields: []
  })

  const openContestForm = (image, fields) => {
    contestForm.value = {
      isOpen: true,
      image,
      fields
    }
  }

  const closeContestForm = () => {
    contestForm.value.isOpen = false
  }

  // Gate State
  const gateOpen = ref(
    localStorage.getItem('gate_remembered') !== 'true' && 
    sessionStorage.getItem('app.camAppGate') !== 'true'
  )

  const closeGate = () => {
    gateOpen.value = false
  }

  // GWP Form State
  const gwpForm = ref({
    isOpen: false,
    channel: '',
    product: 1
  })

  const openGwpForm = (channel = '', product = 1) => {
    gwpForm.value = { isOpen: true, channel, product }
  }

  const closeGwpForm = () => {
    gwpForm.value.isOpen = false
  }

  // CVS Form State
  const cvsForm = ref({
    isOpen: false,
    channel: ''
  })

  const openCvsForm = (channel = '') => {
    cvsForm.value = { isOpen: true, channel }
  }

  const closeCvsForm = () => {
    cvsForm.value.isOpen = false
  }

  // Campaign Selector State
  const campaignSelector = ref({
    isOpen: sessionStorage.getItem('app.campaignSelectorClosed') !== 'true',
    campaignUrl: 'https://wos.carlsberg.com.my' // Default redirect URL for Button 2
  })

  const openCampaignSelector = (url = '') => {
    campaignSelector.value = { isOpen: true, campaignUrl: url }
  }

  const closeCampaignSelector = () => {
    campaignSelector.value.isOpen = false
    sessionStorage.setItem('app.campaignSelectorClosed', 'true')
  }

  return {
    toast,
    showToast,
    hideToast,
    popup,
    openPopup,
    closePopup,
    contestForm,
    openContestForm,
    closeContestForm,
    gateOpen,
    closeGate,
    gwpForm,
    openGwpForm,
    closeGwpForm,
    cvsForm,
    openCvsForm,
    closeCvsForm,
    campaignSelector,
    openCampaignSelector,
    closeCampaignSelector
  }
})
