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
  const gateOpen = ref(localStorage.getItem('gate_remembered') !== 'true')

  const closeGate = () => {
    gateOpen.value = false
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
    closeGate
  }
})
