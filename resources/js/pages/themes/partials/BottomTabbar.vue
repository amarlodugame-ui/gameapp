<script setup>
import { router } from '@/user/inertia'

const isActive = (routeName) => router.isActive(routeName)
</script>
<template>
<div class="tabbar__container" style="--6ab3f23e-getInvitedWheelImgUrl: url(https://ossimg.crhhh.com/bdtgame/tab/wheel.png); --f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
    <div class="tabbar__container-item" :class="{ active: isActive('user.home') }" @click="router.visit(route('user.home'))"><svg class="svg-icon icon-home">
            <use xlink:href="#icon-home"></use>
        </svg><span>Home</span>
      </div>
    <div class="tabbar__container-item" @click="router.visit(route('user.activity'))" :class="{ active: isActive('user.activity') }"><svg class="svg-icon icon-activity">
            <use xlink:href="#icon-activity"></use>
        </svg><span>Activity</span></div>
    <div class="tabbar__container-item" @click="router.visit(route('user.promotion'))" :class="{ active: isActive('user.promotion') }"><svg class="svg-icon icon-promotion">
            <use xlink:href="#icon-promotion">

            </use>
        </svg>
        <div class="promotionBg"></div><span>Promotion</span>
    </div>
    <div class="tabbar__container-item" @click="router.visit(route('user.account.wallet'))" :class="{ active: isActive('user.account.wallet') }"><svg class="svg-icon icon-wallet">
            <use xlink:href="#icon-wallet"></use>
        </svg><span>Wallet</span></div>
    <div class="tabbar__container-item" @click="router.visit(route('user.account'))" :class="{ active: isActive('user.account') }"><svg class="svg-icon icon-main">
            <use xlink:href="#icon-main"></use>
        </svg><span>Account</span></div>
</div>
</template>
