<script setup>
import { ref, computed, watch } from 'vue'
import PageLayout from '../../layouts/PageLayout.vue'
import '@/css/accoutn/page-main-StrongBox.css'
import { router, usePage } from '@/user/inertia'

const page = usePage()

const summary = computed(() => page.props.summary || {})
const totalAssets = computed(() => Number(summary.value.strong_box_balance || 0))
const availableAmount = computed(() => Number(summary.value.wallet_balance || 0))
const generatedRevenue = computed(() => Number(summary.value.today_interest || 0))
const accumulatedRevenue = computed(() => Number(summary.value.total_interest || 0))

const dailyRatePercent = Number(summary.value.daily_rate_percent ?? 0.10)
const dailyRate = dailyRatePercent / 100
const eachAmount = 100

const estimated24h = computed(() => totalAssets.value * dailyRate)

const quantity = ref('')
const selectedMultiplier = ref(2)

const showDialog = ref(false)
const showPopup = ref(false)
const processingIn = ref(false)
const processingOut = ref(false)

const formatBDT = (n) => `৳${Number(n || 0).toFixed(2)}`

const finalAmount = computed(() => {
  const qty = Number(quantity.value || 0)
  return qty * eachAmount * selectedMultiplier.value
})

const estimatedRevenue = computed(() => {
  return finalAmount.value * dailyRate
})

const isValid = computed(() => {
  if (!quantity.value) return false
  if (finalAmount.value <= 0) return false
  if (finalAmount.value > availableAmount.value) return false
  return true
})

const hasIncomeToday = computed(() => Number(summary.value.today_interest || 0) > 0)
const canTransferOut = computed(() => Number(summary.value.strong_box_balance || 0) > 0)

function openPopup() {
  showPopup.value = true
  document.body.style.overflow = 'hidden'
}

function closePopup() {
  showPopup.value = false
  document.body.style.overflow = ''
}

function selectMultiplier(v) {
  selectedMultiplier.value = v
}

function setAllQuantity() {
  quantity.value = Math.floor(availableAmount.value / (eachAmount * selectedMultiplier.value))
}

function transferIn() {
  if (!isValid.value || processingIn.value) return

  processingIn.value = true

router.post(route('user.account.strong-box.transfer-in'), {
  amount: finalAmount.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      quantity.value = ''
      closePopup()
    },
    onFinish: () => {
      processingIn.value = false
    }
  })
}

function openTransferOutDialog() {
  showDialog.value = true
}

function closeTransferOutDialog() {
  showDialog.value = false
}

function transferOut() {
  if (!canTransferOut.value || processingOut.value) return

  processingOut.value = true

  router.post(route('user.account.strong-box.transfer-out'), {}, {
    preserveScroll: true,
    onSuccess: () => {
      showDialog.value = false
    },
    onFinish: () => {
      processingOut.value = false
    }
  })
}

watch(showPopup, (val) => {
  if (!val) {
    document.body.style.overflow = ''
  }
})
</script>

<template>
<PageLayout title="Safe" :showBack="true" :backUrl="route('user.account')">

<div class="StrongBox__container" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">

    <div class="StrongBox__container-dailyRate">
        Interest rate {{ dailyRatePercent }}%
    </div>

    <div class="TotalAssets__container">
        <div class="TotalAssets__container-header">
            <div class="TotalAssets__container-header__left">
                <svg class="svg-icon icon-vault"><use xlink:href="#icon-vault"></use></svg>
            </div>
            <div class="TotalAssets__container-header__right">
                <svg class="svg-icon icon-safeIcon"><use xlink:href="#icon-safeIcon"></use></svg>
                Financial security
            </div>
        </div>

        <div class="TotalAssets__container-main">
            {{ formatBDT(totalAssets) }}
        </div>

        <div class="TotalAssets__container-tip">
            24-hour estimated revenue
            <span>{{ formatBDT(estimated24h) }}</span>
        </div>
    </div>

    <div class="StrongBox__container-income">
        <div class="StrongBox__container-income-header">
            <div class="StrongBox__container-income-header-left">
                <div class="StrongBox__container-income-header-left-num">
                    {{ formatBDT(generatedRevenue) }}
                </div>
                <div class="StrongBox__container-income-header-left-text">
                    Generated revenue
                </div>
                <div class="StrongBox__container-income-header-left-myrale">
                    My interest rate {{ dailyRatePercent }}%
                </div>
            </div>

            <div class="StrongBox__container-income-header-right">
                <div class="StrongBox__container-income-header-right-num">
                    {{ formatBDT(accumulatedRevenue) }}
                </div>
                <div class="StrongBox__container-income-header-right-text">
                    Accumulated revenue
                </div>
            </div>
        </div>

        <div class="StrongBox__container-income-buttom">
            <div @click="openTransferOutDialog">Transfer Out</div>
            <div @click="openPopup">Transfer In</div>
        </div>

        <div class="StrongBox__container-income-tip">
            <svg width="30" height="30" viewBox="0 0 30 30" fill="none"
                xmlns="http://www.w3.org/2000/svg" class="StrongBox__container-errorTip">
                <path d="M15 27.5C18.4517 27.5 21.5768 26.1009 23.8388 23.8388C26.1009 21.5768 27.5 18.4517 27.5 15C27.5 11.5482 26.1009 8.42325 23.8388 6.16116C21.5768 3.89911 18.4517 2.5 15 2.5C11.5482 2.5 8.42325 3.89911 6.16116 6.16116C3.89911 8.42325 2.5 11.5482 2.5 15C2.5 18.4517 3.89911 21.5768 6.16116 23.8388C8.42325 26.1009 11.5482 27.5 15 27.5Z"
                    stroke="#FE6868" stroke-linejoin="round"></path>
                <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M15 23.125C15.8629 23.125 16.5625 22.4254 16.5625 21.5625C16.5625 20.6996 15.8629 20 15 20C14.1371 20 13.4375 20.6996 13.4375 21.5625C13.4375 22.4254 14.1371 23.125 15 23.125Z"
                    fill="#FF7172"></path>
                <path d="M15 7.5V17.5" stroke="#FE6868" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
            Funds are safe and secure, and can be transferred at any time
        </div>

        <div class="StrongBox__container-income-godetail">
            Learn about safes
            <img src="/assets/png/nextIcon-4b99d075.webp" alt="">
        </div>
    </div>

    <div class="StrongBox__container-record">
        <svg class="svg-icon icon-historyHead">
            <use xlink:href="#icon-historyHead"></use>
        </svg>
        Historical records
    </div>

    <div class="StrongBox__container-allRecord" @click="router.visit(route('user.account.strong-box.record'))">
        All history
    </div>

    <div class="dialog" :class="{ inactive: !showDialog }">
        <div class="dialog__container">
            <div class="dialog__container-img">
                <img src="/assets/png/tip-0498e3f9.webp">
            </div>
            <div class="dialog__container-title">
                <h1>{{ hasIncomeToday ? 'Transfer out' : 'No income' }}</h1>
            </div>
            <div class="dialog__container-content">
                <div class="tipText" v-if="hasIncomeToday">
                    Today's interest has been generated. Confirm transfer out?
                </div>
                <div class="tipText" v-else>
                    No income at the moment, want to transfer out
                </div>
            </div>
            <div class="dialog__container-footer">
                <button @click="transferOut" :disabled="processingOut || !canTransferOut">
                    {{ processingOut ? 'Processing...' : 'Transfer Out' }}
                </button>
                <button class="cancel-btn-ex" @click="closeTransferOutDialog">Cancel</button>
            </div>
        </div>
        <div class="dialog__outside" @click="closeTransferOutDialog"></div>
    </div>

    <div v-show="showPopup" class="van-overlay" @click="closePopup"></div>

    <div v-show="showPopup" class="van-popup van-popup--round van-popup--bottom">
        <div class="popup__header">Transfer In</div>

        <div class="popup__amount">
            <div>Each amount</div>
            <div>{{ eachAmount }}</div>
        </div>

        <div class="popup__container">
            <div class="van-cell van-field popup__container-input">
                <div class="van-cell__value van-field__value">
                    <div class="van-field__body">
                        <input
                            type="tel"
                            inputmode="numeric"
                            class="van-field__control"
                            v-model="quantity"
                            placeholder="Please enterQuantity"
                        >
                        <div class="van-field__button">
                            <div class="popup__container-allBtn" @click="setAllQuantity">
                                All
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="popup__container-errorTip" v-show="quantity && !isValid">
                Insufficient amount available
            </div>

            <div class="popup__container-multiple">
                <div
                    v-for="m in [2,5,10,50,100,200,500,1000]"
                    :key="m"
                    :class="{ active: selectedMultiplier === m }"
                    @click="selectMultiplier(m)"
                >
                    {{ m }}x
                </div>
            </div>

            <div class="popup__container-numLine">
                <div>Amount available</div>
                <div class="popup__container-numLine-num refreshBalance">
                    <span>{{ formatBDT(availableAmount) }}</span>
                </div>
            </div>

            <div class="popup__container-numLine">
                <div>Amount transferred</div>
                <div class="popup__container-numLine-num red">
                    {{ formatBDT(finalAmount) }}
                </div>
            </div>

            <div class="betTip">
                24-hour estimated revenue
                <span>{{ formatBDT(estimatedRevenue) }}</span>
            </div>
        </div>

        <div
            class="popup__container-sumbit"
            :class="{ disabled: !isValid || processingIn }"
            @click="transferIn"
        >
            {{ processingIn ? 'Processing...' : 'Transfer In' }}
        </div>
    </div>

</div>

</PageLayout>
</template>

<style scoped>
.dialog__container-footer {
    display: initial;
    width: 100%;
    margin-top: .8rem;
    padding: 0 .26667rem;
}
.dialog__container-footer button {
    width: 100%;
}
.cancel-btn-ex{
    width: 100%;
    height: 1.06667rem;
    color: var(--main-color);
    font-size: .42667rem;
    text-align: center;
    border-radius: 9rem;
    border: .01333rem solid var(--main-color);
    background: transparent;
}
.dialog__container-img{
    margin-top: 0;
}
.dialog__container{
    min-height: 8.36667rem;
}
.van-popup--bottom{
    z-index: 9;
}
.StrongBox__container-income-buttom>div {
    margin-right: 10px;
}
</style>