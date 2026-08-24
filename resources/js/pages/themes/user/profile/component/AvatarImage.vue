<script setup>
import { computed } from 'vue'
import { usePage } from '@/user/inertia'

const props = defineProps({
  src: { type: String, default: null },
  size: { type: [Number, String], default: 40 },
  rounded: { type: Boolean, default: true },
})

const page = usePage()

const finalSrc = computed(() => {
  if (props.src) return props.src

  const id = page.props.auth.user?.image
  return id ? `/assets/profile/${id}.webp` : '/assets/profile/1.webp'
})
</script>

<template>
  <img :src="finalSrc" alt="avatar" />
</template>