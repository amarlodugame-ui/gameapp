<script setup>
import { computed, ref } from 'vue'
import axios from 'axios'
import { router, usePage } from '@/user/inertia'
import PageLayout from '../../layouts/PageLayout.vue'
import CopyValue from '@/pages/themes/partials/CopyValue.vue'

const page = usePage()

const balance = computed(() => page.props.balance ?? 0)
const methods = computed(() => page.props.methods ?? [])
const channels = computed(() => page.props.channels ?? {})
const quickAmounts = computed(() => page.props.quickAmounts ?? [])
const recentDeposits = computed(() => page.props.recentDeposits ?? [])
const instructions = computed(() => page.props.instructions ?? [])
const commissionMap = computed(() => page.props.commissionMap ?? {})

const selectedMethod = ref(methods.value[0]?.key ?? 'Nagad')
const selectedChannelIndex = ref(0)
const amount = ref('')

const isSubmitting = ref(false)
const submitError = ref('')

const currentChannels = computed(() => channels.value[selectedMethod.value] ?? [])
const selectedChannel = computed(() => currentChannels.value[selectedChannelIndex.value] ?? null)

const getCommissionPercent = (methodKey) => {
  const config = commissionMap.value?.[methodKey]

  if (!config?.active) return 0

  return Number(config?.percent ?? 0)
}

const selectedCommissionPercent = computed(() => {
  return getCommissionPercent(selectedMethod.value)
})

const commissionAmount = computed(() => {
  const depositAmount = Number(amount.value || 0)
  const percent = Number(selectedCommissionPercent.value || 0)

  if (!depositAmount || !percent) return 0

  return (depositAmount * percent) / 100
})

const totalReceiveAmount = computed(() => {
  const depositAmount = Number(amount.value || 0)
  return depositAmount + commissionAmount.value
})

const commissionLabel = computed(() => {
  const percent = selectedCommissionPercent.value
  return percent > 0 ? `Get ${percent}% bonus` : 'Bonus Off'
})
const submitDeposit = async () => {
  submitError.value = ''

  if (!selectedChannel.value) {
    submitError.value = 'Please select a channel.'
    return
  }

  if (!amount.value) {
    submitError.value = 'Please enter deposit amount.'
    return
  }

  const numericAmount = Number(amount.value || 0)
  const minAmount = Number(selectedChannel.value?.min || 0)
  const maxAmount = Number(selectedChannel.value?.max || 0)

  if (numericAmount < minAmount || numericAmount > maxAmount) {
    submitError.value = `Please enter amount between ${minAmount} and ${maxAmount}.`
    return
  }

  try {
    isSubmitting.value = true

    const response = await axios.post(route('user.wallet.recharge.store'), {
      payment_method_currency: selectedMethod.value,
      channel_name: selectedChannel.value?.name ?? '',
      amount: amount.value,
      commission_percent: selectedCommissionPercent.value,
      commission_amount: commissionAmount.value,
      total_receive_amount: totalReceiveAmount.value,
    })

    const data = response.data

    if (data?.redirect_url) {
      window.location.href = data.redirect_url
      return
    }

    router.visit(route('user.wallet.RechargeHistory'))
  } catch (error) {
    if (error.response?.status === 422) {
      const errors = error.response.data?.errors ?? {}
      const firstError = Object.values(errors)[0]

      submitError.value =
        Array.isArray(firstError) ? firstError[0] :
        error.response.data?.message || 'Validation failed.'
    } else {
      submitError.value =
        error.response?.data?.message || 'Something went wrong. Please try again.'
    }
  } finally {
    isSubmitting.value = false
  }
}

const setQuickAmount = (value) => {
  amount.value = value
}

const selectMethod = (key) => {
  selectedMethod.value = key
  selectedChannelIndex.value = 0
  submitError.value = ''
}

const clearAmount = () => {
  amount.value = ''
}
</script>
<template>
  <PageLayout
    title="Deposit"
    rightText="Deposit history"
    :showBack="true"
    :backUrl="route('user.wingo.game')"
    :rightUrl="route('user.wallet.RechargeHistory')"
  >
    <div class="Recharge__box" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
      <div class="Recharge__container">
        <div class="balanceAssets">
          <div class="balanceAssets__header">
            <div class="balanceAssets__header__left">
              <img src="/assets/png/balance-e39ce400.webp"> Balance
            </div>
          </div>

          <div class="balanceAssets__main">
            <p>{{ Number(balance).toFixed(2) }}</p>
            <img src="/assets/png/refresh-8e0efe26.webp" alt="">
          </div>
        </div>

        <div>
          <div class="Recharge__container-tabcard">
            <div
              v-for="(method, index) in methods"
              :key="index"
              class="Recharge__container-tabcard__items"
              :class="{ active: selectedMethod === method.key }"
              @click="selectMethod(method.key)"
            >
              <div class="centers">
                <div class="Recharge__container-tabcard__top">
                  <img class="img" :src="method.image" :alt="method.label">
                </div>

                <div class="Recharge__container-tabcard__bot">
                  {{ method.label }}
                </div>

                <!-- <div v-if="getCommissionPercent(method.key) > 0" class="bonus-badge"> +{{ getCommissionPercent(method.key) }}%</div> -->

              </div>
            </div>
          </div>
        </div>

        <div class="Recharge__content">
          <div class="Recharge__content-quickInfo boxStyle">
            <div class="Recharge__content-quickInfo__title">
              <div class="title">
                <svg class="svg-icon icon-quickpay2">
                  <use xlink:href="#icon-quickpay2"></use>
                </svg>
                <p>Select channel</p>
              </div>
            </div>

            <div class="rechargeTypes_list">
              <div
                v-for="(channel, index) in currentChannels"
                :key="index"
                class="Recharge__content-quickInfo__item"
                :class="{ item_active: selectedChannelIndex === index }"
                @click="selectedChannelIndex = index"
              >
                <div class="other">
                  <div>{{ channel.name }}</div>
                  <div>
                    Balance:
                    {{ channel.min }} -
                    {{ channel.max >= 1000 ? `${channel.max / 1000}K` : channel.max }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="Recharge__content-paymoney boxStyle">
            <div class="Recharge__content-paymoney__title">
              <svg class="svg-icon icon-saveWallet">
                <use xlink:href="#icon-saveWallet"></use>
              </svg>
              <p>Deposit amount</p>
            </div>

            <div class="Recharge__content-paymoney__money-list">
              <div v-for="(quickAmount, index) in quickAmounts" :key="index" class="Recharge__content-paymoney__money-list__item" @click="setQuickAmount(quickAmount)">
                <div class="amount">
                  <span>৳</span>
                  {{ quickAmount >= 1000 ? `${quickAmount / 1000}K` : quickAmount }}
                </div>
              </div>
            </div>

            <div class="Recharge__content-paymoney__money-input">
              <div class="place-div">৳</div>

              <div class="van-cell van-field amount-input">
                <div class="van-cell__value van-field__value">
                  <div class="van-field__body">
                    <input
                      v-model="amount"
                      type="tel"
                      inputmode="numeric"
                      class="van-field__control"
                      placeholder="Enter your deposit amount"
                      autocomplete="off"
                    >
                  </div>
                </div>
              </div>

              <div class="place-right" @click="clearAmount">
                <img src="/assets/png/clean-82487515.webp" alt="">
              </div>
            </div>
<!-- <div
  v-if="selectedCommissionPercent > 0"
  class="Recharge__content-paymoney__bonus-preview"
>
  <div class="bonus-row">
    <span>Bonus status</span>
    <span>{{ commissionLabel }}</span>
  </div>

  <div class="bonus-row">
    <span>Commission</span>
    <span>{{ selectedCommissionPercent }}%</span>
  </div>

  <div class="bonus-row" v-if="amount">
    <span>Bonus amount</span>
    <span>৳{{ commissionAmount.toFixed(2) }}</span>
  </div>

  <div class="bonus-row total" v-if="amount">
    <span>Total receive</span>
    <span>৳{{ totalReceiveAmount.toFixed(2) }}</span>
  </div>
</div> -->
            <div v-if="submitError" class="submit-error">
              {{ submitError }}
            </div>
          </div>

          <div class="Recharge__content-fixed">
            <div class="Recharge__content-fixed-box">
              <div>
                <p>Recharge Method:</p>
                <h2>{{ selectedChannel?.name ?? 'N/A' }}</h2>
                <p v-if="selectedCommissionPercent > 0">Bonus: {{ selectedCommissionPercent }}%</p>
                <p v-else>Bonus: Off</p>
              </div>

              <div
                class="Recharge__container-rechageBtn rechage_active"
                :class="{ disabledBtn: isSubmitting }"
                @click="submitDeposit"
              >
                {{ isSubmitting ? 'Processing...' : 'Deposit' }}
              </div>
            </div>
          </div>
        </div>

        <div class="Recharge__container-intro">
          <div class="Recharge__container-intro__title">
            <div class="img">
              <svg class="svg-icon icon-shuoming">
                <use xlink:href="#icon-shuoming"></use>
              </svg>
            </div>
            <p>Recharge instructions</p>
          </div>

          <div class="Recharge__container-intro__lists">
            <div class="item">
              <p v-for="(instruction, index) in instructions" :key="index">
                {{ instruction }}
              </p>
            </div>
          </div>
        </div>

        <div class="record__main" v-if="recentDeposits.length">
          <div class="record__main-title">
            <svg class="svg-icon icon-historyHead">
              <use xlink:href="#icon-historyHead"></use>
            </svg>
            <span>Deposit history</span>
          </div>

          <div v-for="deposit in recentDeposits" :key="deposit.id">
            <div class="record__main-info">
              <div class="record__main-info__title flex_between">
                <div class="recharge_text">Deposit</div>

                <div class="flex_between">
                  <div :class="deposit.status_class">
                    {{ deposit.status_label }}
                  </div>
                </div>
              </div>

              <div class="record__main-info__money item flex_between">
                <span>Balance</span>
                <span>৳{{ deposit.amount }}</span>
              </div>

              <div class="record__main-info__type item flex_between">
                <span>Type</span>
                <span>{{ deposit.channel_name }}</span>
              </div>

              <div class="record__main-info__time item flex_between">
                <span>Time</span>
                <span>{{ deposit.created_at }}</span>
              </div>

              <div class="record__main-info__orderNumber item flex_between">
                <span>Order number</span>
                <div>
                  <CopyValue :value="deposit.trx_id" success-message="Order number copied." />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </PageLayout>
</template>

<style scoped>
.bonus-badge {
  margin-top: 6px;
  font-size: 12px;
  color: #fff;
  background: #16a34a;
  padding: 2px 2px;
  border-radius: 20px;
  display: inline-block;
      position: absolute;
    left: 60%;
    top: -5px;
}

.bonus-badge.off {
  background: #94a3b8;
}

.Recharge__content-paymoney__bonus-preview {
  margin-top: 12px;
  padding: 12px;
  border-radius: 10px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}

.bonus-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 14px;
}

.bonus-row:last-child {
  margin-bottom: 0;
}

.bonus-row.total {
  font-weight: 700;
  color: #16a34a;
}

.submit-error {
  margin-top: 12px;
  color: #dc2626;
  font-size: 13px;
}

.disabledBtn {
  opacity: 0.6;
  pointer-events: none;
}
.item {min-height: 0;}.Recharge__box .Recharge__container-rechageBtn {width: 44%;margin-bottom: 0;cursor: pointer;}.Recharge__box .Recharge__container {margin-top: 10px;}
.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.Recharge__container-tabcard{margin-top:.4rem;margin-bottom:.26667rem;display:grid;grid-template-columns:repeat(4,1fr);grid-gap:.21333rem}.Recharge__container-tabcard .centers{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.Recharge__container-tabcard__items{width:100%;height:2.4rem;background:var(--bgDark-2, var(--bg_color_L2));color:var(--text_color_L2);border-radius:.13333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;position:relative}.Recharge__container-tabcard__items .gift{position:absolute;right:0;top:0;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;background:url(/assets/png/gift-55dc786a.webp) no-repeat center center;background-size:100% 100%;padding:0 .08rem;height:1.06667rem;box-sizing:border-box;min-width:.94667rem}.Recharge__container-tabcard__items .gift span{display:block;color:#fff;font-size:.26667rem;letter-spacing:-.01173rem;text-align:center;padding-top:.24rem;white-space:nowrap}.Recharge__container-tabcard__items .gift span.small{scale:.88}.Recharge__container-tabcard__items.active{background:var(--main_gradient-color);color:var(--text_color_L4)}.Recharge__container-tabcard__items .Recharge__container-tabcard__bot{font-size:.32rem;text-align:center}.Recharge__container-tabcard__items .mgt24{margin-top:.32rem}.Recharge__container-tabcard__top{-webkit-box-flex:0;-webkit-flex:none;flex:none;width:.93333rem;height:.93333rem;margin:.30667rem auto .13333rem}.Recharge__container-tabcard__top img{width:100%;height:100%}.Recharge__container-tabcard__top .lists{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.Recharge__container-tabcard__top .lists svg{width:.56rem;margin-right:.13333rem}.Recharge__container-tabcard__top .lists svg:nth-child(2){width:.44rem;height:.69333rem}.new_upi{margin-top:.4rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;background:var(--bgDark-2, var(--bg_color_L2));border-radius:.26667rem;padding:.26667rem .24rem;position:relative}.new_upi img{width:1.06667rem;height:1.06667rem;margin-right:.4rem}.new_upi .upi_item{color:var(--text_color_L1);display:-webkit-box;display:-webkit-flex;display:flex;height:1.33333rem;width:auto;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column}.new_upi .upi_item .title{font-size:.4rem;margin-bottom:.24rem}.new_upi .upi_item.rns_item{-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center}.new_upi .upi_item.rns_item .title{margin-bottom:0}.new_upi .upi_item .upi_image{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center;background:url(/assets/svg/ArPayBackground-8c36eecc.svg) no-repeat center center;background-size:cover;width:-webkit-fit-content;width:fit-content;height:.61333rem;color:#fff;font-size:.29333rem;position:absolute;right:.13333rem;top:0;padding:.06667rem .26667rem}.new_upi .upi_item .upi_image:before{content:"";display:block;width:.29333rem;height:.29333rem;background:url(/assets/svg/Ar_Gift-f96ba611.svg) no-repeat center center;background-size:cover;margin-right:.13333rem}.upi_active{background:var(--main_gradient-color)}.upi_active .upi_item,.upi_active .upi_item .gift{color:#fff}.ar_wallet{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;height:1.33333rem;border-radius:.13333rem;background:var(--bg_color_L2);margin-bottom:.42667rem;padding:.13333rem .32rem}.ar_wallet .info{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.ar_wallet .info img{width:1.06667rem;height:1.06667rem;margin-right:.13333rem}.ar_wallet .info .tip{color:var(--text_color_L1);font-size:.32rem}.ar_wallet .info .tit{color:var(--text_color_L1)}.ar_wallet .info .tit span{color:#999;font-size:.32rem;font-weight:400}.ar_wallet .info .wallet_amount{color:var(--text_color_L1);font-size:.37333rem;font-weight:700;vertical-align:bottom}.ar_wallet .info .wallet_amount span{color:var(--text_color_L1);font-size:.32rem;font-weight:700}.ar_wallet .info .wallet_amount em{color:var(--text_color_L1);font-size:.32rem;font-weight:400}.ar_wallet .ar_btn{padding:.24rem;border-radius:.13333rem;background:var(--main_gradient-color);color:var(--text_color_L4);font-size:.32rem;width:-webkit-fit-content;width:fit-content;height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-flex:0;-webkit-flex:none;flex:none}.ar_wallet_tip{background:url(/assets/png/hint-c6828dc5.webp) no-repeat 0 .05333rem;background-size:.29333rem .29333rem;padding-left:.34667rem;color:var(--main-color);font-size:.26667rem;font-weight:400}.rule{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;padding:.26667rem;margin-bottom:.4rem;border-radius:.13333rem;background:var(--bg_color_L2);color:var(--text_color_L1)}.rule .left{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;gap:.26667rem;color:var(--text_color_L1)}.rule .left svg{width:.53333rem;height:.53333rem}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.recycleBtnD{width:100%;text-align:center;padding:.4rem 0;max-width:10rem;background:var(--darkBg, var(--bg_color_L2));border-radius:0;margin-bottom:0}.recycleBtnD.c2c{padding-bottom:0}.recycleBtnD button{width:95%;background:var(--button_dis_color);border-radius:.66667rem;border:none;color:var(--text_color_L4);padding:.26667rem 0;font-size:.37333rem;pointer-events:none}.recycleBtnD button.active{pointer-events:auto;background:var(--main_gradient-color)} .van-dialog{overflow:visible} .van-dialog .van-dialog__content{position:relative;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;padding-block:2.66667rem;height:6.4rem}.van-dialog__content img{position:absolute;left:50%;-webkit-transform:translateX(-50%);transform:translate(-50%)}.van-dialog__content img:last-of-type{bottom:-.64rem;width:.73333rem}.van-dialog__content img.succeed{top:-.50667rem;width:3.73333rem}.van-dialog__content img.fail{top:.04267rem;width:2.13333rem}.van-dialog__content-title{margin-bottom:.192rem;color:var(--text_color_L1);font-weight:700;font-size:.48rem;line-height:.48rem;font-family:Poppins}.van-dialog__content-title.title1{margin-bottom:.66667rem}.van-dialog__content-note{margin-bottom:.53333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;font-size:.32rem}.van-dialog__content-note>span:nth-of-type(1){color:var(--text_color_L2)}.van-dialog__content-note>span:nth-of-type(2){color:var(--gray-color-1)}.van-dialog__content-note .red{color:var(--norm_red-color)}.van-dialog__content-btn{height:1.06667rem;width:5.33333rem;line-height:1.06667rem;color:var(--text_color_L4);font-size:.42667rem;font-weight:700;border-radius:1.92rem;background:var(--main_gradient-color)}.arCard{background:var(--bg_color_L2);border-radius:.13333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;padding:.2rem .26667rem;gap:.13333rem;margin-bottom:.4rem}.arCard .left{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;gap:.26667rem;text-align:left}.arCard .left img{width:.74667rem;height:.58667rem;margin-left:.26667rem}.arCard .left>p{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;color:var(--text_color_L1)}.arCard .left>p span{text-align:left}.arCard .left>p span.amount{font-size:.37333rem;font-weight:700}.arCard .right{border-radius:.13333rem;background:var(--main-color);color:#fff;min-height:.85333rem;padding:.13333rem .26667rem;min-width:2rem}.features{background:var(--bg_color_L2);border-radius:.13333rem;padding:.26667rem;text-align:left;color:var(--text_color_L1);margin-bottom:.42667rem}.features .title{font-size:.42667rem;font-weight:600;display:-webkit-box;display:-webkit-flex;display:flex;gap:.26667rem}.features .title svg{width:.58667rem;height:.58667rem}.features>p{font-size:.37333rem;margin:.26667rem 0;line-height:.66667rem}.features>p span{font-size:.37333rem;font-weight:500;margin:0 .13333rem}.features .t1{margin:.26667rem 0}.features .t1,.features .t2{font-size:.37333rem;font-weight:500}.features .t2{color:var(--main_gradient-color)}.features .t3{font-size:.42667rem;font-weight:600;margin:.53333rem 0 .26667rem}.features .toActive{background:var(--main-color);border-radius:.13333rem;color:var(--text_color_L4);padding:.26667rem 0;text-align:center;font-size:.37333rem;font-weight:500}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important} .van-cell:after{border:none}.boxStyle{background:var(--darkBg, var(--bg_color_L2));border-radius:.26667rem;margin-top:.26667rem;padding:.4rem .29333rem .42667rem .26667rem;margin-bottom:.4rem}.reason .title{color:var(--text_color_L1)}.reason textarea{background:var(--bg_color_L3);color:var(--text_color_L2);border:none;border-radius:.21333rem}.reason .row{width:100%;padding:.26667rem}.reason .btn{height:1.06667rem;line-height:1.06667rem;border-radius:.21333rem;background:var(--main_gradient-color);color:var(--text_color_L4);font-size:.42667rem;font-weight:500;margin-top:.42667rem}.Recharge__content-fixed-box{max-width:10rem;margin:0 auto;background:var(--darkBg, var(--bg_color_L2));padding:.26667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;color:var(--darkTextW, var(--text_color_L1))}.Recharge__content-fixed{position:fixed;bottom:0;left:0;right:0;z-index:100}.Recharge__content-fixed p{font-size:.32rem;font-weight:400;margin-bottom:.08rem}.Recharge__content-fixed h2{font-size:.34667rem;font-weight:600;margin-bottom:0}.Recharge__content-paymoney__title{display:-webkit-box;display:-webkit-flex;display:flex;font-weight:600;font-size:.48rem;margin-bottom:.34667rem;color:var(--darkTextW, var(--text_color_L1))}.Recharge__content-paymoney__title .svg-icon,.Recharge__content-paymoney__title img{width:.64rem;height:.64rem;margin-right:.30667rem}.Recharge__content-paymoney__money-list{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-flex-wrap:wrap;flex-wrap:wrap;margin-bottom:.26667rem}.Recharge__content-paymoney__money-list__item{margin-bottom:.26667rem;margin-right:.4rem;width:calc((100% - .8rem) / 3);border:.01333rem solid var(--Dividing-line_color);padding:.16rem 0;border-radius:.13333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;gap:.13333rem;color:var(--main-color);background-color:var(--bg_color_L2)}.Recharge__content-paymoney__money-list__item .amount{width:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;position:relative;font-size:.42667rem;gap:.13333rem}.Recharge__content-paymoney__money-list__item .amount span{position:absolute;left:.10667rem;color:var(--text_color_L3);font-size:.42667rem}html:lang(ar) .Recharge__content-paymoney__money-list__item .amount span{right:.21333rem;left:unset}.Recharge__content-paymoney__money-list__item .amount .usdt{width:.48rem;height:.48rem}.Recharge__content-paymoney__money-list__item .amount .unit{width:.26667rem;height:.53333rem;margin-top:-.26667rem}.Recharge__content-paymoney__money-list__item .arAmount{font-weight:700}.Recharge__content-paymoney__money-list__item .ar_gift{font-size:.26667rem}.Recharge__content-paymoney__money-list__item .ar_gift span{color:#ff5a58}.Recharge__content-paymoney__money-list__item.active{background:var(--main_gradient-color);border-radius:.13333rem;color:var(--text_color_L4);border:none}.Recharge__content-paymoney__money-list__item.active span{color:#fff}.Recharge__content-paymoney__money-list__item.arActive{background:var(--main_gradient-color);color:var(--text_color_L4);border:.01333rem solid var(--bg_color_L3)}.Recharge__content-paymoney__money-list__item.arActive span{color:var(--text_color_L4)}.Recharge__content-paymoney__money-list__item:nth-child(3n){margin-right:0}.Recharge__content-paymoney .recharge_tip{margin-top:.13333rem;color:var(--norm_red-color);font-size:.29333rem}.Recharge__content-paymoney__money-input{position:relative;height:1.17333rem;width:100%;display:-webkit-box;display:-webkit-flex;display:flex;background:var(--bg_color_L1);border-radius:.58667rem}.Recharge__content-paymoney__money-input.radius{border-radius:.13333rem}.Recharge__content-paymoney__money-input .unit{position:absolute;right:1.06667rem;top:.34667rem;font-size:.37333rem;color:var(--text_color_L2);width:-webkit-fit-content;width:fit-content;-webkit-box-flex:0;-webkit-flex:none;flex:none}html:lang(ar) .Recharge__content-paymoney__money-input .unit{right:unset;left:1.06667rem}.Recharge__content-paymoney__money-input .place-div{position:absolute;height:1.17333rem;width:.8rem;color:var(--main-color);font-weight:900;font-size:.56rem;line-height:1.17333rem}html:lang(ar) .Recharge__content-paymoney__money-input .place-div{right:.13333rem}.Recharge__content-paymoney__money-input .place-div:before{content:"";height:.53333rem;position:absolute;border-left:.01333rem solid #bdbdbd;right:0;top:50%;-webkit-transform:translateY(-50%);transform:translateY(-50%)}html:lang(ar) .Recharge__content-paymoney__money-input .place-div:before{right:unset;left:0}.Recharge__content-paymoney__money-input .place-div.usdt{background:url(/assets/png/usdt-40311708.webp) no-repeat center;background-size:.64rem}.Recharge__content-paymoney__money-input .place-div.trx{background:url(/assets/png/trx-8c63cfbf.webp) no-repeat center;background-size:.64rem}.Recharge__content-paymoney__money-input .amount-input{background-color:transparent;margin-left:1.33333rem}.Recharge__content-paymoney__money-input .amount-input input{color:var(--main-color);font-weight:700}.Recharge__content-paymoney__money-input .amount-input input::-webkit-input-placeholder{font-weight:400}.Recharge__content-paymoney__money-input .amount-input input::placeholder{font-weight:400}.Recharge__content-paymoney__money-input .inp{width:70%;height:1.17333rem;background:none;border:none;color:var(--main-color);letter-spacing:.04em;font-weight:700;font-size:.4rem;line-height:1.17333rem;margin-left:1.33333rem;padding-left:.26667rem;padding-bottom:0}.Recharge__content-paymoney__money-input .inp:focus{outline:none}.Recharge__content-paymoney__money-input .place-div{height:1.17333rem;width:1.2rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;color:var(--main-color)}.Recharge__content-paymoney__money-input .place-div img{width:.37333rem;height:.61333rem}.Recharge__content-paymoney__money-input .place-div .place-icon{width:.37333rem;height:.61333rem;position:relative}.Recharge__content-paymoney__money-input .place-div .place-icon .usdt{width:.64rem;height:.64rem;margin-left:-.13333rem}.Recharge__content-paymoney__money-input .place-div:after{position:absolute;content:"";height:.53333rem;display:inline-block;margin:0 .13333rem .02667rem;vertical-align:middle;border-right:.02667rem solid var(--text_color_L3);top:.34667rem;right:-.13333rem}html:lang(ar) .Recharge__content-paymoney__money-input .place-div:after{right:unset;left:-.13333rem}.Recharge__content-paymoney__money-input .ar_all{width:1.33333rem;line-height:1.17333rem;color:var(--main-color);font-size:.37333rem;margin-right:.53333rem}.Recharge__content-paymoney__money-input .place-right{position:absolute;right:.32rem;top:.32rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-align-content:center;align-content:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center}html:lang(ar) .Recharge__content-paymoney__money-input .place-right{right:unset;left:.32rem}.Recharge__content-paymoney__money-input .place-right img{width:.53333rem;height:.53333rem}.Recharge__content-paymoney__money-input+.Recharge__content-paymoney__money-input{margin-top:.26667rem}.Recharge__content-quickInfo{margin-bottom:.53333rem}.Recharge__content-quickInfo__title{width:100%;display:-webkit-box;display:-webkit-flex;display:flex;margin-bottom:.34667rem;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.Recharge__content-quickInfo__title .title{display:-webkit-box;display:-webkit-flex;display:flex;font-weight:400;font-size:.42667rem;color:var(--darkTextW, var(--text_color_L1))}.Recharge__content-quickInfo__title .title .svg-icon{width:.64rem;height:.64rem;margin-right:.30667rem}.Recharge__content-quickInfo .item_active{background:var(--main_gradient-color)}.Recharge__content-quickInfo .item_active div{color:var(--text_color_L4)}.Recharge__content-quickInfo .rechargeTypes_list{display:grid;grid-template-columns:repeat(2,1fr);grid-column-gap:.26667rem}.Recharge__content-quickInfo .numberPay{grid-template-columns:repeat(1,1fr)}.Recharge__content-quickInfo__item{background:var(--bg_color_L3);max-width:98%;border-radius:.26667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;margin-bottom:.26667rem}.Recharge__content-quickInfo__item div{font-size:.37333rem;color:var(--text_color_L2)}.Recharge__content-quickInfo__item .other{padding-left:.34667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;height:100%;padding:.26667rem .34667rem;line-height:1.5}.Recharge__content-quickInfo__item .other .bouns{font-size:.4rem}.Recharge__content-quickInfo__item .usdt_icon{padding:.26667rem 0;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-align:center;-webkit-align-items:center;align-items:center;line-height:1.5}.Recharge__content-quickInfo__item .usdt_icon img{margin-left:.34667rem;width:1.06667rem;height:1.06667rem;margin-right:.34667rem}.Recharge__content-history__title{display:-webkit-box;display:-webkit-flex;display:flex;font-weight:600;font-size:.48rem;margin-bottom:.74667rem}.Recharge__content-history__title img{width:.64rem;height:.64rem;margin-right:.30667rem}.Recharge__content-history__item{padding-bottom:.32rem;border-bottom:.01333rem solid #daddf0;margin-bottom:.34667rem}.Recharge__content-history__item .info1{width:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:end;-webkit-align-items:flex-end;align-items:flex-end;margin-bottom:.29333rem}.Recharge__content-history__item .info1 .left{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:end;-webkit-align-items:flex-end;align-items:flex-end}.Recharge__content-history__item .info1 .left span{margin-right:.24rem}.Recharge__content-history__item .info1 .left span:nth-child(1){width:2.13333rem;height:.53333rem;background:#ff8581;border-radius:.13333rem;color:#fff;line-height:.53333rem;text-align:center}.Recharge__content-history__item .info1 .left span:nth-child(2),.Recharge__content-history__item .info1 .left span:nth-child(3){font-weight:400;font-size:.32rem;line-height:.37333rem;color:#aeb0c6}.Recharge__content-history__item .info1 .right{font-weight:500;font-size:.37333rem;line-height:.45333rem}.Recharge__content-history__item .info1 .danger{color:#f65c5c}.Recharge__content-history__item .info1 .success{color:#49ce9b}.Recharge__content-history__item .info1 .cancle{color:var(--text_color_L2)}.Recharge__content-history__item .info2{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.Recharge__content-history__item .info2 .orderCode{display:-webkit-box;display:-webkit-flex;display:flex;font-weight:400;font-size:.32rem;line-height:.37333rem;color:var(--text_color_L2)}.Recharge__content-history__item .info2 .orderCode img{margin-left:.66667rem;width:.42667rem;height:.42667rem}.Recharge__content-history__item .info2 .money{font-weight:500;font-size:.37333rem;line-height:.45333rem;color:var(--text_color_L1)}.Recharge__content-history__item:last-child{border:none;margin-bottom:0}.Recharge__content-waitPay{padding-bottom:.53333rem}.Recharge__content-waitPay img{display:block;width:1.06667rem;height:1.06667rem;margin:0 auto .18667rem}.Recharge__content-waitPay .wait_text{height:.90667rem;line-height:.45333rem;text-align:center;font-size:.37333rem;color:var(--text_color_L1);margin-bottom:.26667rem}.Recharge__content-waitPay__countdown{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;gap:.16rem;width:100%;margin-bottom:.4rem}.Recharge__content-waitPay__countdown span{display:inline-block;width:.8rem;height:1.06667rem;color:var(--norm_red-color);font-size:.64rem;line-height:1.06667rem;border-radius:.13333rem;background:var(--bg_color_L3);text-align:center}.Recharge__content-waitPay__countdown span:nth-of-type(3){width:.53333rem}.Recharge__content-waitPay .go_pay{width:6.32rem;height:.93333rem;margin:0 auto;background:var(--main_gradient-color);border-radius:1.06667rem;color:#fff;font-size:.4rem;text-align:center;line-height:.93333rem}.Recharge__content .other_bank{margin-bottom:.53333rem}.Recharge__content .other_bank .select_bank_tit{color:var(--text_color_L1);line-height:.64rem;margin-bottom:.26667rem}.Recharge__content .other_bank .select_bank_tit .svg-icon{width:.64rem;height:.64rem;margin-right:.30667rem}.Recharge__content .other_bank .bank_name{background:var(--main_gradient-color);height:1.06667rem;line-height:1.06667rem;color:var(--text_color_L4);padding-left:.26667rem;font-size:.37333rem;border-radius:.16rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.Recharge__content .other_bank .bank_name .right_arrow{margin-right:.26667rem}.Recharge__content .transfer .title{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin-bottom:.29333rem}.Recharge__content .transfer .title img,.Recharge__content .transfer .title svg{width:.64rem;height:.64rem;margin-right:.24rem}.Recharge__content .transfer .title{color:var(--text_color_L1);font-weight:600;font-size:.48rem}.Recharge__content .transfer .transfer_list{width:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.Recharge__content .transfer .transfer_list .item{width:4.26667rem;height:1.6rem;background:#e1e1e1;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;border-radius:.21333rem;font-size:.37333rem;color:var(--text_color_L2)}.Recharge__content .transfer .transfer_list .item img{width:1.68rem;height:.56rem;margin-bottom:.10667rem}.Recharge__content .transfer .transfer_list .transfer_active{background:var(--main_gradient-color);color:#fff}.bank_list{width:100%}.bank_list .bank_item{height:1.17333rem;border-radius:.13333rem;margin-bottom:.21333rem;line-height:1.17333rem;font-size:.4rem;font-style:normal;font-weight:400;padding-left:.26667rem;background-color:var(--bgDark-3, var(--bg_color_L2));display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;color:var(--text_color_L1)}.bank_list .bank_item img{width:.64rem;height:.64rem;margin-right:.13333rem}.bank_list .bank_item_active{background:var(--main_gradient-color);color:var(--text_color_L4);font-weight:700}.Recharge__container-rechageBtn{font-size:.4rem;z-index:88;background:var(--button_dis_color);min-width:2.08rem;height:1.17333rem;line-height:1.17333rem;color:var(--text_color_L2);text-align:center;border-radius:.13333rem;padding:0 .13333rem}.rechage_active{background:var(--main_gradient-color);color:var(--text_color_L4)}.showAmountError{padding:.8rem .4rem .4rem;width:100%}.showAmountError .title1{font-size:.64rem;font-weight:600;text-align:center;margin-bottom:.26667rem}.showAmountError .title2{font-size:.37333rem;text-align:center;line-height:.48rem}.showAmountError .title2.red{color:var(--main_gradient-color)}.showAmountError .button{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;margin-top:.26667rem}.showAmountError .button>div{width:calc((100% - .26667rem)/2);height:1.28rem;line-height:1.28rem;border-radius:.66667rem;font-size:.4rem;text-align:center}.showAmountError .button>div.clance{border:.01333rem solid var(--main-color);color:var(--main-color)}.showAmountError .button>div.goBuy{background:var(--main-color);color:#fff}.arupiAmount .arupi-header.title1{background:var(--main-color);color:var(--text_color_L4);padding:.26667rem 0;margin:0 -.32rem}.arupiAmount .title1{font-size:.37333rem;font-weight:600;text-align:center;margin-bottom:.26667rem;color:var(--text_color_L1)}.arupiAmount .title2{padding-top:.26667rem;font-size:.37333rem;text-align:center;line-height:.48rem;color:var(--text_color_L1);margin-bottom:.26667rem}.arupiAmount ul{display:grid;grid-template-columns:repeat(2,1fr);-webkit-flex-wrap:wrap;flex-wrap:wrap;gap:.13333rem .26667rem}.arupiAmount ul li{border:.01333rem solid var(--Dividing-line_color);padding:.16rem 0;border-radius:.13333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;color:var(--main-color);background-color:var(--bg_color_L3);font-size:.37333rem}.arupiAmount ul li.active{background:var(--main_gradient-color);color:var(--text_color_L4);font-weight:700;border:none}.arupiAmount .button{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between}.arupiAmount .button.column{-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column}.arupiAmount .button.column div{width:100%;margin-top:.26667rem}.arupiAmount .button>div{width:calc((100% - .26667rem)/2);height:1.14667rem;border-radius:.26667rem;font-size:.4rem;text-align:center;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column}.arupiAmount .button>div.clance{border:.01333rem solid var(--main-color);color:var(--main-color)}.arupiAmount .button>div.clance h3{font-weight:700}.arupiAmount .button>div.clance p{line-height:.50667rem;font-size:.32rem}.arupiAmount .button>div.goBuy{background:var(--main-color);color:#fff}.arupiAmount .button>div.goBuy h3{font-weight:700}.arupiAmount .button>div.goBuy p{line-height:.50667rem;font-size:.32rem}.arupiAmount .text{font-size:.32rem;color:var(--text_color_L2);text-align:center;margin-top:.26667rem}.arupiAmount .text span{color:var(--main-color);padding:0 .16rem}.toActive{background:var(--main-color);border-radius:.13333rem;color:var(--text_color_L4);padding:.26667rem 0;text-align:center;font-size:.37333rem;font-weight:500}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.balanceAssets{width:100%;height:3.46667rem;background-image:var(--main_gradient-color);background-repeat:no-repeat;background-size:100%;background-position:top;border-radius:.26667rem;color:var(--text_color_L4);padding:.32rem;position:relative}html:lang(ar) .balanceAssets{background-image:var(--main_gradient-color)}.balanceAssets__header{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;height:.53333rem}.balanceAssets__header__left{font-weight:400;font-size:.34667rem}.balanceAssets__header__left img{margin-right:.21333rem}.balanceAssets__header__left{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.balanceAssets__header__left img{width:.4rem;height:.4rem;margin-right:.21333rem}.balanceAssets__main{height:.73333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin-top:.13333rem;font-weight:700;font-size:.64rem}.balanceAssets__main p{margin-left:.26667rem}.balanceAssets__main img{width:.58667rem;height:.37333rem;margin-left:.24rem}.balanceAssets__tip img{width:.66667rem;height:.42667rem;position:absolute;bottom:.34667rem;left:.38667rem}html:lang(ar) .balanceAssets__tip img{left:unset;right:.38667rem}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.count_timer{background:none;display:inline-block;text-align:left!important;min-width:1.06667rem!important}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.record__main{margin:.26667rem 0}.record__main-title{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;height:.66667rem;margin-bottom:.50667rem}.record__main-title .svg-icon{width:.64rem;height:.64rem;margin-right:.18667rem}.record__main-title span{font-weight:600;font-size:.42667rem;color:var(--darkTextW, var(--text_color_L1))}.record__main-info{padding:.32rem .26667rem .4rem;background-color:var(--darkBg, var(--bg_color_L2));border-radius:.13333rem;margin-bottom:.26667rem}.record__main-info__title{padding-bottom:.32rem;border-bottom:.01333rem solid var(--Dividing-line_color);margin-bottom:.26667rem;color:var(--darkTextW, var(--text_color_L1))}.record__main-info__title .recharge_text{width:2rem;height:.66667rem;line-height:.66667rem;text-align:center;color:#fff;background:var(--norm_green-color);border-radius:.13333rem;font-size:.37333rem}.record__main-info__title div{font-size:.34667rem}.record__main-info__title div .success{color:var(--norm_green-color)}.record__main-info__title div .danger{color:var(--norm_secondary-color)}.record__main-info__title div .rechargeFail{color:var(--norm_red-color)}.record__main-info__title div .timeout{color:#f6a50b}.record__main-info__title div .check{color:#59c042}.record__main-info__title div .representation{color:var(--main-color)}.record__main-info__title div .recharge{color:#598ff9}.record__main-info__title div .cancel{color:var(--text_color_L2)}.record__main-info__title div .fail{color:var(--main-color)}.record__main-info__title div img{width:.48rem;height:.48rem}.record__main-info__money span:first-child{font-size:.34667rem;color:var(--text_color_L2)}.record__main-info__money span:last-child{font-size:.4rem;color:var(--norm_secondary-color)}.record__main-info__type span:last-child,.record__main-info__time span:last-child{font-size:.34667rem;color:var(--text_color_L2)}.record__main-info__type div,.record__main-info__time div{font-size:.32rem;color:#aeb0c6}.record__main-info__type div img,.record__main-info__time div img{width:.42667rem;height:.42667rem;margin-left:.16rem}.record__main-info__orderNumber div{font-size:.32rem;color:#aeb0c6}.record__main-info__orderNumber div img{width:.42667rem;height:.42667rem;margin-left:.16rem}.record__main-info .item{margin-bottom:.32rem}.record__main-info .item span:first-child{font-size:.34667rem;color:var(--text_color_L2)}.record__main-info .item:last-child{margin-bottom:0}.record__main-info .report{height:.85333rem;line-height:.85333rem;color:#fff;background-color:var(--main-color);border-radius:.88rem;text-align:center;font-size:.4rem;font-weight:500}.record__main-info .report.report-b{margin-bottom:.13333rem}.record__main-more{width:8.8rem;height:.93333rem;border:.01333rem solid var(--main-color);border-radius:1.06667rem;margin:.4rem auto;text-align:center;line-height:.93333rem;font-size:.4rem;color:var(--text_color_L4);background:var(--main_gradient-color)}.record__main .rechargeh__header{display:-webkit-box;display:-webkit-flex;display:flex;padding:.26667rem;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.record__main .rechargeh__header span{font-size:.37333rem;color:var(--text_color_L1)}.record__main .van-field{background:var(--bg_color_L1);border-radius:.66667rem;margin-left:.26667rem}.flex_between{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important} .van-action-sheet{max-height:90%} .van-action-sheet__header{background:-webkit-linear-gradient(top,#ff6c6b 0%,#ff4a4a 68.59%);background:linear-gradient(180deg,#ff6c6b 0%,#ff4a4a 68.59%);color:#ffffff} .van-action-sheet__close{top:1.5%}#third_content{height:90vh}.Recharge__box{position:relative;width:100%;font-family:Inter,sans-serif;overflow:hidden;padding-bottom:1.6rem}.Recharge__box .Recharge__container{position:relative;width:100%;padding:0 .32rem .32rem}.Recharge__box .Recharge__container .title{font-weight:400;font-size:.34667rem}.Recharge__box .Recharge__container-balanceAssets{width:100%;height:3.46667rem;background-image:url(/assets/png/TotalAssetsBg-81d648d4.webp);background-repeat:no-repeat;background-size:100%;background-position:center;border-radius:.26667rem;color:#ffffff;padding:.38667rem .33333rem 0;position:relative}.Recharge__box .Recharge__container-balanceAssets__header{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;height:.53333rem}.Recharge__box .Recharge__container-balanceAssets__header__left{font-weight:400;font-size:.34667rem}.Recharge__box .Recharge__container-balanceAssets__header__left img{margin-right:.21333rem}.Recharge__box .Recharge__container-balanceAssets__header__left{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.Recharge__box .Recharge__container-balanceAssets__header__left img{width:.48rem;height:.48rem;margin-right:.21333rem}.Recharge__box .Recharge__container-balanceAssets__main{height:.73333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin:.21333rem .37333rem .37333rem 0;font-weight:700;font-size:.64rem;text-shadow:0 .01333rem 0 rgba(0,0,0,.12)}.Recharge__box .Recharge__container-balanceAssets__main img{width:.58667rem;height:.37333rem;margin-left:.24rem}.Recharge__box .Recharge__container-balanceAssets__tip img{width:.66667rem;height:.42667rem;position:absolute;bottom:.34667rem;left:.38667rem}.Recharge__box .Recharge__container-paymoney{background:var(--bg_color_L2);border-radius:.26667rem;margin-top:.26667rem;padding:.4rem .29333rem .42667rem .26667rem}.Recharge__box .Recharge__container-paymoney__title{display:-webkit-box;display:-webkit-flex;display:flex;font-weight:600;font-size:.48rem;margin-bottom:.34667rem}.Recharge__box .Recharge__container-paymoney__title img{width:.64rem;height:.64rem;margin-right:.30667rem}.Recharge__box .Recharge__container-paymoney__money-list{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-flex-wrap:wrap;flex-wrap:wrap;margin-bottom:.26667rem}.Recharge__box .Recharge__container-paymoney__money-list__item{margin-bottom:.26667rem;margin-right:.4rem;width:2.66667rem;height:.93333rem;color:#ff5a58;border:.01333rem solid #dfdede;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center}.Recharge__box .Recharge__container-paymoney__money-list__item.active{background:var(--main_gradient-color);border-radius:.13333rem;color:#ffffff;border:none}.Recharge__box .Recharge__container-paymoney__money-input{display:-webkit-box;display:-webkit-flex;display:flex;position:relative}.Recharge__box .Recharge__container-paymoney__money-input .inp{height:1.17333rem;-webkit-box-flex:1;-webkit-flex:1;flex:1;background:#f2f2f2;border-radius:.8rem;position:relative;padding-left:1.49333rem;padding-right:1.06667rem;color:var(--main-color);border:none;font-weight:700}.Recharge__box .Recharge__container-paymoney__money-input .place-div{position:absolute;height:1.17333rem;width:1.2rem}.Recharge__box .Recharge__container-paymoney__money-input .place-div img{width:.37333rem;height:.61333rem;margin-top:.28rem;margin-left:.53333rem}.Recharge__box .Recharge__container-paymoney__money-input .place-div .place-icon{width:.37333rem;height:.61333rem;position:relative}.Recharge__box .Recharge__container-paymoney__money-input .place-div:after{position:absolute;content:"";height:.53333rem;display:inline-block;margin:0 .13333rem .02667rem;vertical-align:middle;border-right:.02667rem solid #bdbdbd;top:.34667rem;right:-.13333rem}.Recharge__box .Recharge__container-paymoney__money-input .place-right{position:absolute;right:.32rem;top:.32rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-align-content:center;align-content:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center}.Recharge__box .Recharge__container-paymoney__money-input .place-right img{width:.53333rem;height:.53333rem}.Recharge__box .Recharge__container-rechageBtn{font-size:.4rem;z-index:88;background:var(--button_dis_color);width:100%;max-width:10rem;height:.93333rem;line-height:.93333rem;color:#ffffff;text-align:center;border-radius:.66667rem;margin-bottom:.4rem}.Recharge__box .forbidden_tip{color:#1AB266;font-size:.32rem;font-style:normal;font-weight:400;line-height:.48rem;margin-bottom:.53333rem;text-align:center}.Recharge__box .forbidden1,.Recharge__box .forbidden2{width:100%;color:var(--text_color_L2);font-size:.29333rem;font-style:normal;font-weight:400;line-height:.29333rem;padding:.13333rem 0;text-align:center}.Recharge__box .forbidden3{width:100%;color:var(--text_color_L2);font-size:.32rem;font-style:normal;font-weight:400;line-height:.48rem;padding-top:.53333rem;margin-bottom:.53333rem;text-align:center}.Recharge__box .rechage_active{background:var(--main-color);color:var(--text_color_L4)}@media screen and (max-width: 500px){.Recharge__box{position:relative;width:100%;font-family:Inter,sans-serif;overflow:hidden}.Recharge__box .Recharge__container-rechageBtn{max-width:none}}.cancen_model_cnt{font-size:.32rem;text-align:center;color:var(--text_color_L2)} .van-dialog{width:9.33333rem;border-radius:.26667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;overflow:hidden;background:var(--main_gradient-color);padding:0 .32rem .32rem} .van-dialog__content{position:relative;width:100%} .van-dialog__content .promptHeader{text-align:center;height:1.17333rem;line-height:1.17333rem;color:var(--text_color_L4);font-weight:700;font-size:.48rem;position:relative} .van-dialog__content .promptHeader:before{content:"";position:absolute;left:0;top:.53333rem;display:inline-block;width:1.46667rem;height:.02667rem;margin-right:.13333rem;background:-webkit-linear-gradient(right,#FFF -2.73%,rgba(255,255,255,0) 91.36%);background:linear-gradient(-90deg,#FFF -2.73%,rgba(255,255,255,0) 91.36%)}html:lang(ar) .van-dialog__content .promptHeader:before{left:.26667rem;right:unset} .van-dialog__content .qrcodeHeader{background:none} .van-dialog__content .qrcodeHeader:before{width:1.06667rem;height:.02667rem;background:-webkit-linear-gradient(left,#FFFFFF -2.73%,rgba(255,255,255,0) 91.36%);background:linear-gradient(90deg,#FFFFFF -2.73%,rgba(255,255,255,0) 91.36%);-webkit-transform:rotate(180deg);transform:rotate(180deg);margin-bottom:.13333rem;margin-right:1.33333rem} .van-dialog__content .promptHeader:after{content:"";position:absolute;right:0;top:.53333rem;display:inline-block;width:1.46667rem;height:.02667rem;margin-left:.13333rem;background:-webkit-linear-gradient(left,#FFF -2.73%,rgba(255,255,255,0) 91.36%);background:linear-gradient(90deg,#FFF -2.73%,rgba(255,255,255,0) 91.36%)}html:lang(ar) .van-dialog__content .promptHeader:after{left:unset;right:.26667rem} .van-dialog__content .qrcodeHeader:after{width:1.06667rem;height:.02667rem;background:-webkit-linear-gradient(left,#FFFFFF -2.73%,rgba(255,255,255,0) 91.36%);background:linear-gradient(90deg,#FFFFFF -2.73%,rgba(255,255,255,0) 91.36%);margin-bottom:.13333rem;margin-left:1.33333rem} .van-dialog__content .promptContent{padding:.4rem .26667rem} .van-dialog.qrcodeDialog{background:-webkit-linear-gradient(top,#FF6C6B 0%,#FF4A4A 74.07%);background:linear-gradient(180deg,#FF6C6B 0%,#FF4A4A 74.07%);padding:0 .26667rem .26667rem} .van-dialog.qrcodeDialog .promptContent{height:8rem;background:#fff;box-shadow:0 .05333rem .05333rem #e53636;border-radius:.26667rem} .van-dialog.qrcodeDialog .qrcodeFooter{width:100%;padding:.53333rem 0} .van-dialog.qrcodeDialog .qrcodeFooter div{height:.93333rem;border-radius:.93333rem;text-align:center;line-height:.93333rem} .van-dialog.qrcodeDialog .qrcodeFooter div:nth-child(1){background-color:#f13f39;color:#fff;margin-bottom:.4rem} .van-dialog.qrcodeDialog .qrcodeFooter div:nth-child(2){color:#f13f39;border:.01333rem solid #f13f39}.promptTip{margin-bottom:.26667rem;width:100%;color:var(--text_color_L4);font-size:.32rem;font-style:normal;font-weight:500;line-height:.44rem}.promptTip p{margin-bottom:.13333rem}.promptBox{width:100%;padding:.32rem;margin:auto;border-radius:.26667rem;border:.02667rem solid var(--main-color)}.promptBox h3{color:var(--text_color_L1);font-size:.37333rem;font-weight:600}.promptBox p{color:var(--main-color);font-size:.32rem;font-style:normal;font-weight:400;margin-bottom:.13333rem}.promptContent{width:100%;padding:.32rem;margin:auto;border-radius:.26667rem;background:var(--bg_color_L2)}.promptContent .input_item{margin-bottom:.53333rem}.promptContent .input_item div{color:var(--text_color_L1);font-size:.37333rem;font-family:Inter;font-weight:500;line-height:.48rem;margin-bottom:.13333rem}.promptContent .input_item input{width:100%;background:var(--bg_color_L1);height:1.06667rem;color:var(--text_color_L1);font-size:.32rem;line-height:1.06667rem;padding:.29333rem;border:none;border-radius:.13333rem}.promptContent .input_item input::-webkit-input-placeholder{color:#b0b0b0;font-size:.32rem;line-height:1.06667rem}.promptContent .input_item input::placeholder{color:#b0b0b0;font-size:.32rem;line-height:1.06667rem}.promptContent .tip{color:var(--norm_red-color);font-size:.29333rem;font-style:normal;font-weight:400}.footer_btn{padding-top:.48rem;width:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-align-content:center;align-content:center;margin-bottom:.45333rem}.footer_btn div{width:100%;height:.93333rem;border-radius:.93333rem;text-align:center;line-height:.93333rem}.footer_btn div:nth-child(1){background:var(--main_gradient-color);color:#fff;margin-bottom:.32rem}.footer_btn div:nth-child(2){color:var(--main-color);border:.01333rem solid var(--main-color)} .arupiAmount-dialog{background:var(--bg_color_L3)!important}
</style>