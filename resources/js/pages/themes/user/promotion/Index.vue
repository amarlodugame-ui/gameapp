<script setup>
import PageLayout from '../../layouts/PageLayout.vue';
import BottomTabbar from '../../partials/BottomTabbar.vue';
import CopyValue from '../../partials/CopyValue.vue';

import { computed } from 'vue';
import { router } from '@/user/inertia';

import '@/css/promotion/page-promotion-MyInvitation.css'
import '@/css/promotion/page-promotion-TeamReport.css'
import '@/css/promotion/page-promotion-indexvue_vue_type_style_index.css'

const props = defineProps({
    userRefcode: {
        type: [String, Number],
        required: true,
    },
    invitationLink: {
        type: String,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    }
})

const direct = computed(() => props.stats?.direct ?? {
    registerCount: 0,
    depositNumber: 0,
    depositAmount: '0.00',
    firstDepositCount: 0,
})

const team = computed(() => props.stats?.team ?? {
    registerCount: 0,
    depositNumber: 0,
    depositAmount: '0.00',
    firstDepositCount: 0,
})

const promotionData = computed(() => props.stats?.promotionData ?? {
    thisWeekCommission: '0.00',
    totalCommission: '0.00',
    directSubordinateCount: 0,
    teamSubordinateCount: 0,
})

const yesterdayCommission = computed(() => props.stats?.yesterdayCommission ?? '0.00')
const levelWiseTeam = computed(() => props.stats?.levelWiseTeam ?? [])
</script>

<template>
    <PageLayout
        title="Agency"
        rightIcon="icon-subordinate"
        rightIconClass="icon-subordinate"
        :showBack="true" :backUrl="route('user.home')"
        :rightUrl="route('user.promotion.subordinate')"
    >
        <div class="container" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
            <div class="amount">{{ yesterdayCommission }}</div>
            <div class="amount_txt">Yesterday's total commission</div>
            <div class="tip">Upgrade the level to increase commission income</div>

            <div class="info_content">
                <div class="info">
                    <div class="head">
                        <svg class="svg-icon icon-directSubordinates">
                            <use xlink:href="#icon-directSubordinates"></use>
                        </svg>
                        Direct subordinates
                    </div>

                    <div class="line1 r">
                        <div>{{ direct.registerCount }}</div>
                        number of register
                    </div>
                    <div class="line2 r">
                        <div>{{ direct.depositNumber }}</div>
                        Deposit number
                    </div>
                    <div class="line3 r">
                        <div>{{ direct.depositAmount }}</div>
                        Deposit amount
                    </div>
                    <div class="line1 r">
                        <div>{{ direct.firstDepositCount }}</div>
                        Number of people making first deposit
                    </div>
                </div>

                <div class="info">
                    <div class="head u2">
                        <svg class="svg-icon icon-teamSubordinates">
                            <use xlink:href="#icon-teamSubordinates"></use>
                        </svg>
                        Team subordinates
                    </div>

                    <div class="line1">
                        <div>{{ team.registerCount }}</div>
                        number of register
                    </div>
                    <div class="line2">
                        <div>{{ team.depositNumber }}</div>
                        Deposit number
                    </div>
                    <div class="line3">
                        <div>{{ team.depositAmount }}</div>
                        Deposit amount
                    </div>
                    <div class="line1">
                        <div>{{ team.firstDepositCount }}</div>
                        Number of people making first deposit
                    </div>
                </div>
            </div>
        </div>

        <div class="content" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
            <div class="shareBtnContainer" @click="router.visit(route('user.promotion.share'))">
                <button class="shareBtn">Download QR Code</button>
            </div>
            <div class="promote__cell">
                <div class="promote__cell-item">
                    <div class="label"><svg class="svg-icon icon-copy_Code"><use xlink:href="#icon-copy_Code"></use></svg><span>Copy invitation code</span></div>
                    <div class="arrow"><span> <CopyValue :value="invitationLink" icon="icon-copy" successMessage="Invitation Code copied."/></span></div>
                </div>
                <div class="promote__cell-item" @click="router.visit(route('user.promotion.team-report'))">
                    <div class="label">
                        <svg class="svg-icon icon-team_port">
                            <use xlink:href="#icon-team_port"></use>
                        </svg>
                        <span>Subordinate data</span>
                    </div>
                    <div class="arrow">
                        <i class="van-badge__wrapper van-icon van-icon-arrow" style="color: var(--text_color_L1); font-size: 24px;"></i>
                    </div>
                </div>

                <div class="promote__cell-item" @click="router.visit(route('user.promotion.my-commission'))">
                    <div class="label">
                        <svg class="svg-icon icon-commission">
                            <use xlink:href="#icon-commission"></use>
                        </svg>
                        <span>Commission detail</span>
                    </div>
                    <div class="arrow">
                        <i class="van-badge__wrapper van-icon van-icon-arrow" style="color: var(--text_color_L1); font-size: 24px;"></i>
                    </div>
                </div>

                <div class="promote__cell-item" @click="router.visit(route('user.promotion.rule'))">
                    <div class="label">
                        <svg class="svg-icon icon-invite_reg">
                            <use xlink:href="#icon-invite_reg"></use>
                        </svg>
                        <span>Invitation rules</span>
                    </div>
                    <div class="arrow">
                        <i class="van-badge__wrapper van-icon van-icon-arrow" style="color: var(--text_color_L1); font-size: 24px;"></i>
                    </div>
                </div>

                <div class="promote__cell-item" @click="router.visit(route('user.promotion.rebate-ratio'))">
                    <div class="label">
                        <svg class="svg-icon icon-rebateRatio">
                            <use xlink:href="#icon-rebateRatio"></use>
                        </svg>
                        <span>Rebate ratio</span>
                    </div>
                    <div class="arrow">
                        <i class="van-badge__wrapper van-icon van-icon-arrow" style="color: var(--text_color_L1); font-size: 24px;"></i>
                    </div>
                </div>
            </div>

            <div class="commission">
                <div class="commission__title">
                    <svg class="svg-icon icon-promotionData">
                        <use xlink:href="#icon-promotionData"></use>
                    </svg>
                    <span>promotion data</span>
                </div>

                <div class="commission__body">
                    <div>
                        <span>{{ promotionData.thisWeekCommission }}</span>
                        <span>This Week</span>
                    </div>
                    <span></span>
                    <div>
                        <span>{{ promotionData.totalCommission }}</span>
                        <span>Total commission</span>
                    </div>
                </div>

                <div class="commission__body">
                    <div>
                        <span>{{ promotionData.directSubordinateCount }}</span>
                        <span>direct subordinate</span>
                    </div>
                    <span></span>
                    <div>
                        <span>{{ promotionData.teamSubordinateCount }}</span>
                        <span>Total number of subordinates in the team</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- <BottomTabbar /> -->
    </PageLayout>
</template>
<style scoped>  
.container{overflow: hidden;}
.content{width: -webkit-fill-available;}
.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.container{position:relative;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;height:auto;padding-top:.6rem;background-image:url(/assets/png/promotionbg-1203267e.webp),var(--main_gradient-color);background-repeat:no-repeat;background-position:center;background-size:cover,100% 100%}.container:after{content:"";display:block;width:100%;height:1.86667rem;position:absolute;background-color:var(--bg_color_L1);bottom:-.02667rem;z-index:1}.container .amount{font-weight:500;font-size:.64rem;line-height:.64rem;color:var(--text_color_L4);margin-bottom:.21333rem}.container .amount_txt{height:.64rem;min-width:3.41333rem;background:var(--bg_color_L2);color:var(--main-color);border-radius:.66667rem;line-height:.64rem;text-align:center;font-size:.34667rem;margin-bottom:.13333rem;white-space:nowrap;padding:0 .33333rem}.container .tip{font-size:.29333rem;margin-bottom:.26667rem;color:var(--text_color_L4)}.container .info_content{display:-webkit-box;display:-webkit-flex;display:flex;width:9.33333rem;min-height:5.06667rem;background-color:var(--darkBg, var(--bg_color_L2));border-radius:.26667rem;overflow:hidden;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;position:relative;z-index:10}.container .info_content .info{-webkit-box-flex:1;-webkit-flex:1;flex:1;padding-bottom:.2rem}.container .info_content .info .head{height:1.06667rem;line-height:1.06667rem;background-color:var(--sheet_nva_color);color:#fff;font-size:.34667rem;padding-left:.21333rem;margin-bottom:.2rem;background-repeat:no-repeat;background-position:.26667rem center}.container .info_content .info .head.u2{border-left:.02667rem solid var(--darkBg, var(--bg_color_L2))}.container .info_content .info .head svg{width:.64rem;height:.64rem}.container .info_content .info .line1,.container .info_content .info .line2,.container .info_content .info .line3{color:var(--text_color_L1);font-size:.29333rem;text-align:center;padding-top:.2rem}.container .info_content .info .line1>div,.container .info_content .info .line2>div,.container .info_content .info .line3>div{font-size:.37333rem}.container .info_content .info .line1.r,.container .info_content .info .line2.r,.container .info_content .info .line3.r{border-right:.01333rem solid var(--bg_color_L3)}.container .info_content .info .line1>div{color:var(--text_color_L1)}.container .info_content .info .line2>div{color:var(--norm_green-color)}.container .info_content .info .line3>div{color:var(--norm_secondary-color)}
.invite-link-copy {display: flex;align-items: center;gap: 8px;}
.invite-link-text {max-width: 180px;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;color: var(--text_color_L1);font-size: 14px;}
span.copy-value-text {
    display: none !important;
}
</style>