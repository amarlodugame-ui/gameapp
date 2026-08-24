<script setup>
import { ref } from "vue";

const show = ref(false);
const message = ref("");
const type = ref("text");

let timer = null;

function open(msg, toastType = "text", duration = 2000) {
  message.value = msg;
  type.value = toastType;
  show.value = true;

  clearTimeout(timer);
  timer = setTimeout(() => {
    show.value = false;
  }, duration);
}

defineExpose({ open });
</script>

<template>
  <transition name="fade">
    <div
      v-if="show"
      class="van-popup van-popup--center van-toast van-toast--middle"
      :class="{
        'van-toast--success': type === 'success',
        'van-toast--fail': type === 'error',
        'van-toast--warning': type === 'warning'
      }"
      style="z-index:4000"
    >
      <!-- ICON -->
      <i
        v-if="type !== 'text'"
        class="van-badge__wrapper van-icon van-toast__icon"
        :class="{
          'van-icon-success': type === 'success',
          'van-icon-fail': type === 'error',
          'van-icon-warning-o': type === 'warning'
        }"
        style="font-size:28px"
      ></i>

      <div class="van-toast__text">
        {{ message }}
      </div>
    </div>
  </transition>
</template>

<style scoped>


</style>