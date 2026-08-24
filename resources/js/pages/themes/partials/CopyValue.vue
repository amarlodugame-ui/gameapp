<script setup>
import { computed } from 'vue'
import { usePage } from '@/user/inertia'
import { useLocalToast } from '@/composables/useLocalToast'

const props = defineProps({
  value: {
    type: [String, Number],
    default: null,
  },
  field: {
    type: String,
    default: 'userRefcode',
  },
  icon: {
    type: String,
    default: 'icon-copy',
  },
  showIcon: {
    type: Boolean,
    default: true,
  },
  successMessage: {
    type: String,
    default: 'Copied successfully.',
  },
  failedMessage: {
    type: String,
    default: 'Copy failed.',
  },
})

const page = usePage()
const { showToast } = useLocalToast()

const resolvedValue = computed(() => {
  if (props.value !== null && props.value !== undefined && props.value !== '') {
    return props.value
  }

  return page.props[props.field] ?? ''
})

const copyText = async () => {
  try {
    const text = String(resolvedValue.value ?? '').trim()

    if (!text) {
      showToast(props.failedMessage, 'fail', 'warning-o')
      return
    }

    await navigator.clipboard.writeText(text)
    showToast(props.successMessage, 'success', 'success')
  } catch (error) {
    showToast(props.failedMessage, 'fail', 'warning-o')
    console.error(error)
  }
}
</script>

<template>
  <span class="copy-value-wrap">
    <span class="copy-value-text" @click="copyText">
      {{ resolvedValue }}
    </span>

    <svg
      v-if="showIcon"
      class="svg-icon copy-value-icon"
      @click="copyText"
    >
      <use :xlink:href="`#${icon}`"></use>
    </svg>
  </span>
</template>

<style scoped>
.copy-value-wrap {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.copy-value-text,
.copy-value-icon {
  cursor: pointer;
}
</style>