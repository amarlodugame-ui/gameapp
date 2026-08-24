<script setup>
import { ref } from 'vue'
import PageLayout from '../../layouts/PageLayout.vue'
import '@/css/activity/page-main-SuperJackpot.css'
import { router } from '@/user/inertia'
const showDialog = ref(false)

function openDialog() {
  showDialog.value = true
}

function closeDialog() {
  showDialog.value = false
}
</script>

<template>
<PageLayout
  title="Super Jackpot"
  :showBack="true"
  :backUrl="route('user.activity')"
>

<div class="Xg-page" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">

  <div class="Xg-info">
    <div>
      <h3 class="tit">Super Jackpot</h3>
      <p class="tip">
        When you get the Super Jackpot in 【Slots】Can get 1 additional bonus
      </p>
      <p class="txt">
        The reward is valid for 30 day, and you will not be able to claim
        it after it expires!
      </p>
    </div>
  </div>

  <div class="Xg-page-wrap">

    <!-- Receive batch button -->
    <div class="receive-all no-receive" @click="openDialog">
      <svg class="svg-icon icon-super_no icon icon">
        <use xlink:href="#icon-super_no"></use>
      </svg>
      <span>Receive in batches</span>
    </div>

    <!-- Tabs -->
<ul class="tab">

  <!-- Rule -->
  <li
    class="tab-item"
    @click="router.visit(route('user.activity.main.super-jackpot.rule'))"
  >
    <svg class="svg-icon icon-rule">
      <use xlink:href="#icon-rule"></use>
    </svg>
    <span>Rule</span>
  </li>

  <!-- Winning star -->
  <li
    class="tab-item"
    @click="router.visit(route('user.activity.main.super-jackpot.star'))"
  >
    <svg class="svg-icon icon-winningStar">
      <use xlink:href="#icon-winningStar"></use>
    </svg>
    <span>Winning star</span>
  </li>

</ul>

    <!-- Content -->
    <div class="infiniteScroll">
      <div class="Xg-list"></div>

      <div class="infiniteScroll__loading">
        <div class="empty__container">
          <svg class="svg-icon icon-empty">
            <use xlink:href="#icon-empty"></use>
          </svg>
          <p>You don't have a big jackpot yet, let's bet</p>
        </div>
      </div>
    </div>

    <!-- Go Bet -->
    
</div>
<div
@click="router.visit(route('user.activity'))"
  class="go-bet"
>
  Go bet
</div>

  <!-- Dialog -->
  <div class="dialog" :class="{ inactive: !showDialog }">

    <div class="dialog__container" role="dialog" tabindex="0">

      <div class="dialog__container-img">
        <img
          class="succed"
          src="/assets/png/succeed-83674414.webp"
          alt=""
        >
      </div>

      <div class="dialog__container-title">
        <h1>Successfully received!</h1>
      </div>

      <div class="dialog__container-content">
        <div class="Laundry-Con">
          <div class="Laundry-Con_tip">
            Mega Jackpot Bonus!
          </div>
        </div>
      </div>

      <div class="dialog__container-footer">
        <button @click="closeDialog">OK</button>
      </div>

    </div>

    <div class="dialog__outside" @click="closeDialog"></div>

  </div>

</div>
</PageLayout>
</template>

<style>
.tab-link {
  display: flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
  color: inherit;
}

.dialog__container-footer {
  margin-top: .8rem;
}
</style>