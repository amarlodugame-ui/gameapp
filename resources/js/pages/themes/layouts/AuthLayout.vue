<!-- (A) AuthLayout (Login/Register/Forgot) → AuthMainHeader ব্যবহার করবে -->
<script setup>
import { useAttrs } from "vue"
import BaseLayout from "./BaseLayout.vue"
import AppHeader from "./components/AppHeader.vue"

const attrs = useAttrs()

const props = defineProps({
  showBack: { type: Boolean, default: true },
  backUrl: { type: String, default: null },
  logoUrl: { type: String, default: "" },
  langText: { type: String, default: "" },
  langIcon: { type: String, default: "" },
})
</script>

<template>
  <BaseLayout>
    <AppHeader
      variant="auth"
      v-bind="attrs"
      :showBack="showBack"
      :backUrl="backUrl"
      :logoUrl="logoUrl"
      :langText="langText"
      :langIcon="langIcon"
    />
    <slot />
  </BaseLayout>
</template>