import { ref } from 'vue'

const toast = ref({
  show: false,
  message: '',
  type: 'success',
  icon: 'success',
})

let toastTimer = null

export function useLocalToast() {
  const showToast = (
    message,
    type = 'success',
    icon = 'success',
    duration = 2500
  ) => {
    toast.value = {
      show: true,
      message,
      type,
      icon,
    }

    if (toastTimer) {
      clearTimeout(toastTimer)
    }

    toastTimer = setTimeout(() => {
      toast.value.show = false
    }, duration)
  }

  const hideToast = () => {
    toast.value.show = false

    if (toastTimer) {
      clearTimeout(toastTimer)
      toastTimer = null
    }
  }

  return {
    toast,
    showToast,
    hideToast,
  }
}