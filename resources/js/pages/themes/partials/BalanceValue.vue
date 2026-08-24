<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useLocalToast } from '@/composables/useLocalToast'

const props = defineProps({
  routeUrl: {
    type: String,
    default: '',
  },
  prefix: {
    type: String,
    default: '৳',
  },
  icon: {
    type: String,
    default: 'icon-refreshBalance',
  },
  showIcon: {
    type: Boolean,
    default: true,
  },
  toastSuccess: {
    type: String,
    default: 'Balance updated successfully.',
  },
  toastFailed: {
    type: String,
    default: 'Failed to load balance.',
  },
})

const balance = ref('0.00')
const loading = ref(false)
const { showToast } = useLocalToast()

const formattedBalance = computed(() => `${props.prefix}${balance.value}`)

const fetchBalance = async (showMessage = false) => {
  if (!props.routeUrl || loading.value) return

  loading.value = true

  try {
    const response = await axios.get(props.routeUrl)
    balance.value = response.data.balance ?? '0.00'

    if (showMessage) {
      showToast(props.toastSuccess, 'success', 'success')
    }
  } catch (error) {
    if (showMessage) {
      showToast(props.toastFailed, 'fail', 'warning-o')
    }
    console.error(error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchBalance(false)
})
</script>

<template>
  <p class="totalSavings__container-header__subtitle">
    <span>{{ formattedBalance }}</span>
    <svg v-if="showIcon" class="svg-icon balance-value-icon" :class="{ 'balance-value-icon--loading': loading }" @click="fetchBalance(true)"><use :xlink:href="`#${icon}`"></use></svg>
</p>
</template>

<style scoped>
.balance-value-wrap {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.balance-value-icon {
  cursor: pointer;
}

.balance-value-icon--loading {
  opacity: 0.6;
  pointer-events: none;
}
</style>