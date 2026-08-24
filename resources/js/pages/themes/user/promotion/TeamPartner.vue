<script setup>
import PageLayout from '../../layouts/PageLayout.vue'
import { router } from '@/user/inertia'
import { useLocalToast } from '@/composables/useLocalToast'

const props = defineProps({
  invitationLink: {
    type: String,
    required: true,
  },
  invitationCount: {
    type: Number,
    default: 0,
  },
  invitationTotalBonus: {
    type: [String, Number],
    default: '0.00',
  },
  requiredTurnover: {
    type: [String, Number],
    default: '1000.00',
  },
  completedTurnover: {
    type: [String, Number],
    default: '0.00',
  },
  remainingTurnover: {
    type: [String, Number],
    default: '1000.00',
  },
  bonusAmount: {
    type: [String, Number],
    default: '100.00',
  },
  canClaim: {
    type: Boolean,
    default: false,
  },
  alreadyClaimed: {
    type: Boolean,
    default: false,
  },
  rewardHistories: {
    type: Array,
    default: () => [],
  },
})

const { showToast } = useLocalToast()

const copyInvitationLink = async () => {
  try {
    await navigator.clipboard.writeText(props.invitationLink)
    showToast('Invitation link copied.', 'success', 'success')
  } catch (error) {
    showToast('Copy failed.', 'fail', 'warning-o')
  }
}

const claimReward = () => {
  if (props.alreadyClaimed) {
    showToast('You have already claimed this reward.', 'fail', 'warning-o')
    return
  }

  if (!props.canClaim) {
    showToast('Complete ৳1,000 turnover first.', 'fail', 'warning-o')
    return
  }

  router.post(route('user.team.partner.claim'))
}
</script>

<template>
  <PageLayout title="Partner rewards" :showBack="true" :backUrl="route('user.promotion')">
    <div class="partner" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
      <div class="partner-banner">
        <h2>Invite friends to get max rewards</h2>
        <p>৳{{ bonusAmount }}</p>
      </div>

      <div class="partner-main">
        <div class="partner-item">
          <span>Invitation count</span>
          <span class="number">{{ invitationCount }}</span>
        </div>

<!-- <div class="partner-item">
  <span>Required turnover</span>
  <span class="number">৳{{ requiredTurnover }}</span>
</div> -->

<!-- <div class="partner-item">
  <span>Completed turnover</span>
  <span class="count">৳{{ completedTurnover }}</span>
</div> -->

<div class="partner-item">
  <span>Remaining turnover</span>
  <span class="money">৳{{ remainingTurnover }}</span>
</div>

<div class="partner-item">
  <span>Total claimed bonus</span>
  <span class="money">৳{{ invitationTotalBonus }}</span>
</div>

        <div class="partner-entry">
          <div @click="router.visit(route('user.promotion.team-partner.invitation'))" >
            Invitation record
            <svg fill="none" height="36" viewBox="0 0 37 36" width="37" xmlns="http://www.w3.org/2000/svg">
              <path clip-rule="evenodd" d="M11.3008 26.5V24.3644L19.0121 17.5665L11.3008 10.6356V8.5L20.1579 16.4653L21.3008 17.5665L20.1579 18.6677L11.3008 26.5Z" fill="#666666" fill-rule="evenodd" />
              <path clip-rule="evenodd" d="M17.5 26.5V24.3644L25.2113 17.5665L17.5 10.6356V8.5L26.3571 16.4653L27.5 17.5665L26.3571 18.6677L17.5 26.5Z" fill="#666666" fill-rule="evenodd" />
            </svg>
          </div>
        </div>

        <div class="partner-title">Invitation link</div>

        <div class="partner-code">
          <span @click="copyInvitationLink">{{ invitationLink }}</span>

          <span @click="copyInvitationLink">
            <svg class="svg-icon icon-copy">
              <use xlink:href="#icon-copy"></use>
            </svg>
          </span>
        </div>

<div
  class="partner-btn"
  :class="{ disabled: !canClaim }"
  @click="claimReward"
>
  {{
    alreadyClaimed
      ? 'Already Claimed'
      : canClaim
        ? `Claim Reward ৳${bonusAmount}`
        : `Need ৳${remainingTurnover} More Turnover`
  }}
</div>

        <div class="partner-title">Reward History</div>

        <div v-if="rewardHistories.length">
          <div
            v-for="item in rewardHistories"
            :key="item.id"
            class="partner-item"
          >
            <span>{{ item.created_at }}</span>
            <span class="money">৳{{ item.bonus_amount }}</span>
          </div>
        </div>

        <div v-else class="partner-item">
          <span>No history found</span>
          <span class="number">0</span>
        </div>

        <div class="partner-rule">
          <div class="partner-rule-title">
            <svg class="svg-icon icon-shuoming img img">
              <use xlink:href="#icon-shuoming"></use>
            </svg>
            <span>Invitation rules</span>
          </div>

          <div class="tip">
            Invite user and complete <span>৳{{ requiredTurnover }}</span> turnover
          </div>

          <div class="partner-rule-table">
            <div class="head">
              <div>When Player A</div>
              <div class="bouns">You get bonus</div>
            </div>

            <div class="item">
              <div class="left">Turnover</div>
              <div class="right">
                <div class="line">
                  <div>
                    <span>Turnover ≥ <span>৳{{ requiredTurnover }}</span></span>
                    <span>One time bonus only</span>
                  </div>
                  <div>৳{{ bonusAmount }}</div>
                </div>
              </div>
            </div>
          </div>

<div class="tip">
  Complete <span>৳{{ requiredTurnover }}</span> turnover to claim reward
</div>

<!-- <div class="redTip">*This reward can be claimed only one time.</div> -->

<div class="partner-rule-text">
  <p>
    You have completed ৳{{ completedTurnover }} turnover.
    Remaining turnover is ৳{{ remainingTurnover }}.
  </p>
  <p>
    If your turnover reaches ৳{{ requiredTurnover }},
    you can claim ৳{{ bonusAmount }} bonus.
  </p>
  <p>
    After claim, the reward will be added to your balance automatically.
  </p>
</div>
        </div>
      </div>
    </div>
  </PageLayout>
</template>
<style scoped>
@charset "UTF-8";.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.partner-banner{min-height:3.01333rem;margin-bottom:.4rem;position:relative;padding-left:6rem;padding-top:.53333rem;padding-bottom:.13333rem;background:url(/assets/png/teamPartnerBg-bf3ba57d.webp);background-size:cover}.partner-banner-box{left:0;top:0;width:3.94667rem!important}.partner-banner img{position:absolute;width:100%;height:100%;left:0;top:0}.partner-banner h2{color:#1e2637;font-family:Inter;font-size:.34667rem;font-style:normal;font-weight:700;line-height:normal}.partner-banner p{min-width:3.21333rem;max-width:80%;height:.66667rem;-webkit-flex-shrink:0;flex-shrink:0;border-radius:.8rem;background:#FEAA57;color:#fff;font-size:.45333rem;margin-top:.26667rem;line-height:.66667rem;text-align:center;position:relative;z-index:2}.partner-main{padding:0 .32rem}.partner-item{height:1.17333rem;border-radius:.26667rem;background:var(--bg_color_L2);display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;margin-bottom:.2rem;font-size:.37333rem;padding:.26667rem}.partner-item span{color:var(--text_color_L2)}.partner-item span.number{color:var(--text_color_L1)}.partner-item span.count{color:var(--norm_green-color)}.partner-item span.money{color:var(--norm_red-color);font-size:.45333rem;font-weight:bolder}.partner-entry{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;font-size:.37333rem;color:var(--text_color_L1);padding:.4rem}.partner-entry div{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.partner-entry svg{width:.48rem;height:.48rem}.partner-title{color:var(--text_color_L1);display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;font-size:.42667rem;font-weight:700;line-height:.53333rem;text-align:left;margin-bottom:.2rem;height:.53333rem}.partner-title:before{content:"";display:inline-block;width:.08rem;height:.4rem;background:var(--main-color);margin-right:.13333rem}.partner-link{height:1.17333rem;background:var(--main_gradient-color);border-radius:.66667rem;line-height:.53333rem;padding:0 .26667rem;margin-bottom:.53333rem;font-size:.37333rem;color:var(--text_color_L1);text-align:center;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center}.partner-code{height:1.17333rem;background:var(--bg_color_L2);border-radius:.66667rem;line-height:1.17333rem;padding:0 .53333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;position:relative;margin-bottom:.53333rem;color:var(--text_color_L1);font-size:.37333rem}.partner-code:after{display:block;content:"";width:0;height:0;border-left:.58667rem solid var(--bg_color_L2);border-bottom:1.17333rem solid transparent;border-top:0 solid transparent;position:absolute;right:2.18667rem;top:-.01333rem}.partner-code:before{content:"";position:absolute;width:2.73333rem;height:1.17333rem;right:0;background:var(--main-color);border-radius:0 .66667rem .66667rem 0;z-index:0}.partner-code span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;-webkit-box-flex:1;-webkit-flex:1;flex:1}.partner-code span:first-of-type{-webkit-box-flex:0;-webkit-flex:none;flex:none;display:block;width:5.86667rem}.partner-code span:last-child{position:relative;z-index:1;display:inline-block;padding:0 .13333rem;-webkit-box-flex:0;-webkit-flex:none;flex:none}.partner-code svg{color:var(--text_color_L4);width:.64rem;height:.64rem;vertical-align:middle}.partner-btn{height:.93333rem;border-radius:.66667rem;text-align:center;line-height:.93333rem;font-size:.42667rem;color:#fff;background:var(--main_gradient-color);margin-bottom:.93333rem}.partner-rule{background:var(--bg_color_L2);border-radius:.13333rem;padding:.26667rem}.partner-rule-title{color:var(--text_color_L1);display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;font-size:.42667rem;font-weight:700;line-height:.53333rem;text-align:left;margin-bottom:.2rem;height:.53333rem}.partner-rule-title .img{width:.64rem;height:.64rem}.partner-rule-text{border-radius:.13333rem;padding:.4rem}.partner-rule-table{border-radius:.21333rem;overflow:hidden;border:.01333rem solid var(--bg_color_L3)}.partner-rule-table .head{background:var(--sheet_nva_color);color:#fff;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;line-height:.37333rem;height:.8rem;text-align:center;font-size:.37333rem;font-weight:500}.partner-rule-table .head>div{width:6rem}.partner-rule-table .head>div.bouns{width:2.8rem;position:relative}.partner-rule-table .head>div.bouns:before{content:"";height:.45333rem;position:absolute;left:0;top:50%;-webkit-transform:translateY(-50%);transform:translateY(-50%);border-left:.01333rem solid var(--Dividing-line_color)}.partner-rule-table .item{display:-webkit-box;display:-webkit-flex;display:flex;height:-webkit-fit-content;height:fit-content}.partner-rule-table .item .left{color:var(--text_color_L1);border-right:.01333rem solid var(--bg_color_L3);width:1.33333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;text-align:center}.partner-rule-table .item .right{width:7.46667rem;height:-webkit-fit-content;height:fit-content}.partner-rule-table .item .right .line{min-height:1.17333rem;font-size:.32rem;display:-webkit-box;display:-webkit-flex;display:flex}.partner-rule-table .item .right .line:nth-child(2n){background:var(--bg_color_L3)}.partner-rule-table .item .right .line>div{padding:.13333rem 0}.partner-rule-table .item .right .line>div:first-of-type{width:4.65333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;color:var(--text_color_L2);-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column}.partner-rule-table .item .right .line>div:last-of-type{width:2.8rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;border-left:.01333rem solid var(--bg_color_L3);color:var(--norm_red-color)}.partner-rule-table .item+.item{border-top:.01333rem solid var(--bg_color_L3)}.partner-rule p{color:var(--text_color_L2);font-size:.34667rem;line-height:.45333rem;margin-bottom:.4rem;padding-left:.34667rem;position:relative}.partner-rule p:before{content:"";display:block;position:absolute;left:0;top:.13333rem;height:.16rem;width:.16rem;-webkit-box-flex:0;-webkit-flex:none;flex:none;border-radius:.02667rem;background:var(--main-color);-webkit-transform:rotate(45deg);transform:rotate(45deg)}.partner-rule .tip{color:var(--text_color_L1);font-size:.37333rem;min-height:.53333rem;line-height:.53333rem;margin-top:.34667rem;margin-bottom:.21333rem}.partner-rule .tip span{color:var(--norm_red-color)}.partner-rule .redTip{font-size:.32rem;min-height:.53333rem;line-height:.53333rem;color:var(--norm_red-color);font-weight:600;margin-top:.18667rem;margin-bottom:.32rem}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.invitation{padding:0 .16rem}.invitation ul{margin-block:.32rem}.invitation-item{height:-webkit-fit-content;height:fit-content;padding:.4rem .26667rem;background:var(--bg_color_L2);border-radius:.16rem;color:var(--text_color_L2);font-size:.32rem;font-weight:400;margin-bottom:.2rem}.invitation-item:last-child{margin-bottom:0}.invitation-item{line-height:.37333rem}.invitation-item p{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;margin-bottom:.45333rem}.invitation-item p:last-child{margin-bottom:0}.invitation-item .uid{color:var(--text_color_L2);font-size:.4rem;font-style:normal;font-weight:400;line-height:.37333rem}.invitation-item .money{color:var(--norm_red-color);font-size:.37333rem;font-weight:500}.invitation-item .money.turnover{color:var(--norm_secondary-color)}.invitation-item .name{color:var(--text_color_L1);font-size:.4rem;font-style:normal;font-weight:400;line-height:.37333rem}.invitation-item .status{color:var(--norm_red-color);font-size:.37333rem;font-style:normal;font-weight:400;line-height:.42667rem}.invitation-item .status.isRecharge{color:var(--norm_green-color)}
.item {
    min-height: 1rem;}.item .right {
    right: -0.73333rem;}
</style>