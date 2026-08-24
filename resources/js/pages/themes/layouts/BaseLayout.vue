<script setup>
import { usePage, router } from '@/user/inertia'
import { computed, watch, ref, onMounted, onBeforeUnmount } from 'vue'
import "../../../../css/user/user.css"
import SvgSprite from "./components/SvgSprite.vue"
import { useLocalToast } from '@/composables/useLocalToast'
// import DevToolsGuard from './components/DevToolsGuard.vue'

const page = usePage()
const flashToast = computed(() => page.props.flash?.toast)

const { toast: localToast, showToast } = useLocalToast()

watch(
  flashToast,
  (newToast) => {
    if (newToast && newToast.message) {
      showToast(
        newToast.message,
        newToast.type ?? 'success',
        newToast.icon ?? null,
        3000
      )
    }
  },
  { immediate: true }
)

const isLoading = ref(false)

let removeStart
let removeFinish
let removeError
let removeInvalid

onMounted(() => {
  removeStart = router.on('start', () => {
    isLoading.value = true
  })

  removeFinish = router.on('finish', () => {
    isLoading.value = false
  })

  removeError = router.on('error', () => {
    isLoading.value = false
  })

  removeInvalid = router.on('invalid', () => {
    isLoading.value = false
  })
})

onBeforeUnmount(() => {
  removeStart?.()
  removeFinish?.()
  removeError?.()
  removeInvalid?.()
})
</script>

<template>
  <SvgSprite />

  <!--<div v-show="isLoading" class="ar-loading-view" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">-->
  <!--  <div class="loading-animat">-->
  <!--    <div class="logo-loader">-->
  <!--      <img src="/assets/logo/logo_white.png" alt="Logo" class="loader-logo"/>-->
  <!--      <svg class="orbit-svg" viewBox="0 0 160 160" aria-hidden="true">-->
  <!--        <g class="orbit-spin">-->
  <!--          <g class="orbit-offset">-->
  <!--            <circle class="orbit-arc" cx="80" cy="80" r="58"/>-->
  <!--            <circle class="orbit-dot" cx="57.3" cy="26.6" r="4" />-->
  <!--          </g>-->
  <!--        </g>-->
  <!--      </svg>-->
  <!--    </div>-->
  <!--  </div>-->
  <!--</div>-->

  <slot />

  <div v-if="localToast.show" class="van-popup van-popup--center van-toast van-toast--middle" :class="'van-toast--' + (localToast.type ?? 'success')" style="z-index: 2001;">
    <i v-if="localToast.icon" class="van-icon van-toast__icon" :class="'van-icon-' + localToast.icon"></i>
    <div class="van-toast__text">{{ localToast.message }}</div>
  </div>
</template>

<style scoped>
.ar-loading-view {
  position: fixed;
  inset: 0;
  width: 100vw;
  height: 100vh;
  z-index: 9999;
  background-color: #0000004d;
  display: flex;
  align-items: center;
  justify-content: center;
}

.loading-animat {
  width: 190px;
  height: 190px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.logo-loader {
  width: 160px;
  height: 160px;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.loader-logo {
  width: 88px;
  height: auto;
  object-fit: contain;
  display: block;
  position: relative;
  z-index: 3;
}

.orbit-svg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  z-index: 2;
  overflow: visible;
}

.orbit-spin {
  transform-origin: 80px 80px;
  animation: orbitSpin 1.4s linear infinite;
}

.orbit-offset {
  transform-origin: 80px 80px;
  transform: rotate(-118deg);
}

.orbit-arc {
  fill: none;
  stroke: #1AB266;
  stroke-width: 3.5;
  stroke-linecap: round;
  stroke-dasharray: 250 120;
}

.orbit-dot {
  fill: #1AB266;
}

@keyframes orbitSpin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>