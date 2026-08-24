<script setup>
import { computed } from 'vue';
import { router } from '@/user/inertia';
import AuthLayout from '../../layouts/AuthLayout.vue';
import '@/css/activity/page-wallet-indexvue_vue_type_style_index_0_scoped.css';
import BottomTabbar from '../../partials/BottomTabbar.vue';



</script>

<template>
    <AuthLayout class="main" title="About us" :showBack="true" :backUrl="route('user.account')">

<div class="about-container" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
        <div class="about-container-header-belly"><img alt="" class="" data-origin="/assets/png/aboutBg-2e4b25ca.webp" src="/assets/png/aboutBg-2e4b25ca.webp"></div>
    <div class="about-container-content">
        <div class="about-container-content-item ar-1px-b">
            <div class="about-container-content-item-title"><svg class="svg-icon icon-privacyIcon">
                    <use xlink:href="#icon-privacyIcon"></use>
                </svg><span>Confidentiality Agreement</span></div><i class="van-badge__wrapper van-icon van-icon-arrow"
                style="color: var(--text_color_L2); font-size: 18px;"></i>
        </div>
        <div class="about-container-content-item ar-1px-b">
            <div class="about-container-content-item-title"><svg class="svg-icon icon-riskProtocal">
                    <use xlink:href="#icon-riskProtocal"></use>
                </svg><span>Risk Disclosure Agreement</span></div><i class="van-badge__wrapper van-icon van-icon-arrow"
                style="color: var(--text_color_L2); font-size: 18px;"></i>
        </div>
    </div>
</div>
        
    </AuthLayout>
</template>

<style scoped>
.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.about-container-header{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:end;-webkit-justify-content:flex-end;justify-content:flex-end;width:100%;height:4.93333rem}.about-container-header-belly img{width:100%;height:100%;object-fit:cover}.about-container-content{padding:0 .4rem}.about-container-content-item{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;padding:.66667rem 0 .30667rem;font-size:.4rem}.about-container-content-item svg{width:.90667rem;height:.90667rem;padding-right:.18667rem}.about-container-content-item span{font-size:.4rem;color:var(--text_color_L1)}.about-container-content-item-title{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.about-container-content{padding:.4rem}.about-container-content-item{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;padding:.96rem 0 .30667rem;font-size:.4rem}
</style>