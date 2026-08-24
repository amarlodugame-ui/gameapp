<!-- (C) PageLayout (Other pages) → PageHeaderWhite ব্যবহার করবে(C) PageLayout (Other pages) → PageHeaderWhite ব্যবহার করবে -->

<script setup>
import BaseLayout from "./BaseLayout.vue"
import AppHeader from "./components/AppHeader.vue"
import { useAttrs } from "vue"

const attrs = useAttrs()

defineProps({
  title: { type: String, default: "" },
  rightText: { type: String, default: "" },
  rightIcon: { type: String, default: "" },
  rightIconClass: { type: String, default: "" },
  rightUrl: { type: String, default: "" },
  showBack: { type: Boolean, default: true },
  backUrl: { type: String, default: null },
})
</script>

<template>
  <BaseLayout>
    <AppHeader variant="white" :title="title" :rightText="rightText" :rightIcon="rightIcon" :rightIconClass="rightIconClass" :rightUrl="rightUrl" :showBack="showBack" :backUrl="backUrl"/>
    <div class="pt-16" v-bind="attrs">
      <slot />
    </div>
  </BaseLayout>
</template>