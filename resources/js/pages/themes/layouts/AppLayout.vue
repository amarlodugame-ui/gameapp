<!-- (B) AppLayout (Home) → HomeHeader ব্যবহার করবে -->

<script setup>
import BaseLayout from "./BaseLayout.vue"
import AppHeader from "./components/AppHeader.vue"
</script>

<template>
  <BaseLayout>
    <AppHeader
      variant="home"
      logoUrl="/assets/logo/logo_white.png"
    />
    <slot />
  </BaseLayout>
</template>