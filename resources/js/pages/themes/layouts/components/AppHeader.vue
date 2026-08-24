<script setup>
import { router } from "@/user/inertia"
import { useAttrs } from "vue"

const attrs = useAttrs()

const props = defineProps({
  /* variant: home | auth | white */
  variant: { type: String, default: "white" },

  title: { type: String, default: "" },

  rightText: { type: String, default: "" },
  rightIcon: { type: String, default: "" },   // ✅ NEW
  rightUrl: { type: String, default: "" },    // ✅ OPTIONAL CLICK
  rightIconClass: { type: String, default: "" }, // ✅ NEW


  showBack: { type: Boolean, default: false },
  backUrl: { type: String, default: null },

  logoUrl: { type: String, default: "" },

  langText: { type: String, default: "" },
  langIcon: { type: String, default: "" },
})

function goBack() {
  if (!props.showBack) return
  if (props.backUrl) router.visit(props.backUrl)
  else window.history.back()
}

function goRight() {
  if (props.rightUrl) {
    router.visit(props.rightUrl)
  }
}
</script>

<template>
  <!-- <div class="dailySignIn__container"> -->
    

  <div :class="['navbar', variant === 'white' && 'white', attrs.class]">
    <div class="navbar-fixed">
      <div class="navbar__content">

        <!-- LEFT -->
        <div class="navbar__content-left">
          <i
            v-if="showBack"
            class="van-badge__wrapper van-icon van-icon-arrow-left"
            @click="goBack"
          />

          <!-- Home Logo -->
          <img
            v-if="variant === 'home' && logoUrl"
            :src="logoUrl"
            style="height:20px"
          />
        </div>

        <!-- CENTER -->
        <div class="navbar__content-center">

          <!-- Auth Logo -->
          <div
            v-if="variant === 'auth' && logoUrl"
            class="headLogo"
            :style="{ backgroundImage: `url('${logoUrl}')` }"
          />

          <!-- Title -->
          <div v-if="title" class="navbar__content-title">
            {{ title }}
          </div>

        </div>

        <!-- RIGHT -->
        <div class="navbar__content-right">

          <!-- Auth Language -->
          <div v-if="variant === 'auth' && langText" class="right">
            <img class="img" :src="langIcon" />
            <span class="languageName">{{ langText }}</span>
          </div>

          <!-- White Right Text + Icon -->

          
        <div
          v-if="rightText || rightIcon"
          class="title navi-record"
          @click="goRight"
          style="display:flex; align-items:center; gap:6px; cursor:pointer;"
        >
          <svg
            v-if="rightIcon"
            :class="['svg-icon', rightIcon, rightIconClass]"
          >
            <use :xlink:href="`#${rightIcon}`"></use>
          </svg>

          <span v-if="rightText">{{ rightText }}</span>
        </div>

             
          <!-- Home Icons -->
          <div v-if="variant === 'home'" class="content__right">
            <svg @click="router.visit(route('user.account.messages'))"  class="svg-icon icon-notification"><use xlink:href="#icon-notification"></use></svg>
            <svg class="svg-icon icon-server" @click="router.visit(route('user.promotion.support'))">
              <use xlink:href="#icon-server"></use>
            </svg>
          </div>

        </div>

      </div>
    </div>
  </div>
    <!-- </div> -->
</template>

<style scoped>
.content__right svg {
    color: var(--main-color);
}
</style>