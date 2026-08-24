<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { router } from '@/user/inertia'
import AuthLayout from '../../layouts/AuthLayout.vue'
import '@/css/accoutn/page-vip-indexvue_vue_type_script_setup_true_lang.css'
import '@/css/accoutn/page-vip-indexvue_vue_type_style_index_0_scoped.css'
import AvatarImage from '../profile/component/AvatarImage.vue'

const props = defineProps({
  userData: { type: Object, required: true },
  vipLevelsData: { type: Array, required: true },
  benefitsDataProp: { type: Object, required: true },
  rewardHistoryData: { type: Array, required: true },
  rewardStateMap: { type: Object, default: () => ({}) },
  betRuleText: { type: String, default: '' },
  tipText: { type: String, default: '' },
  rulesData: { type: Array, default: () => [] },
})

const user = computed(() => props.userData ?? {})
const vipLevels = computed(() => props.vipLevelsData ?? [])
const benefitsData = computed(() => props.benefitsDataProp ?? {})
const rewardHistoryList = computed(() => props.rewardHistoryData ?? [])
const rewardStates = computed(() => props.rewardStateMap ?? {})
const rulesList = computed(() => props.rulesData ?? [])

function progressPercent(current, target) {
  const currentValue = Number(current || 0)
  const targetValue = Number(target || 0)

  if (!targetValue || targetValue <= 0) return '0%'
  const percent = Math.min((currentValue / targetValue) * 100, 100)
  return `${percent}%`
}
function isVipAchieved(lv) {
  const currentExp = Number(user.value?.exp || 0)
  const requireExp = Number(lv?.requireExp || 0)

  return currentExp >= requireExp
}
const ICON_GOLD = '/assets/png/gold-4a60a059.webp'
const ICON_LVLUP = '/assets/png/1-fd9896f4.webp'
const ICON_MONTH = '/assets/png/2-0a41a908.webp'
const ICON_SAFE = '/assets/png/4-e53b4da2.webp'
const ICON_REBATE = '/assets/png/5-5e6a64b1.webp'

const svgLove = `<svg class="svg-icon icon-love"><use xlink:href="#icon-love"></use></svg>`
const svgSafe = `<svg class="svg-icon icon-weal4"><use xlink:href="#icon-weal4"></use></svg>`
const svgRebate = `<svg class="svg-icon icon-weal5"><use xlink:href="#icon-weal5"></use></svg>`

function iconForIndex(i) {
  if (i === 0) return ICON_LVLUP
  if (i === 1) return ICON_MONTH
  if (i === 2) return ICON_SAFE
  return ICON_REBATE
}

const activeTab = ref(0)
const setTab = (i) => {
  activeTab.value = i
}

const swiperEl = ref(null)
const wrapperEl = ref(null)

const index = ref(0)
const isDown = ref(false)
const startX = ref(0)
const lastX = ref(0)
const baseX = ref(0)

const slideStep = ref(0)
const transitionOn = ref(false)
let ro = null

const historyLimit = ref(10)

const clamp = (n, min, max) => Math.max(min, Math.min(max, n))
const px = (e) => (e.touches && e.touches[0] ? e.touches[0].clientX : e.clientX)

const activeLevelObj = computed(() => {
  return vipLevels.value[index.value] || vipLevels.value[0] || {}
})

const activeVipText = computed(() => {
  return activeLevelObj.value?.vip ? `VIP${activeLevelObj.value.vip}` : 'VIP'
})

const currentBenefits = computed(() => {
  const key = activeLevelObj.value?.vip ? `VIP${activeLevelObj.value.vip}` : null
  if (!key) return []
  return benefitsData.value[key] || []
})

const activeRewardState = computed(() => {
  return rewardStates.value[activeVipText.value] ?? {
    levelUp: {
      showCard: false,
      claimable: false,
      claimed: false,
      buttonText: 'Unavailable',
      buttonClass: 'noActive',
      hint: '',
    },
    monthly: {
      showCard: false,
      claimable: false,
      claimed: false,
      buttonText: 'Unavailable',
      buttonClass: 'noActive',
      hint: '',
      requiredBet: 0,
      qualifiedBet: 0,
      remainingBet: 0,
    },
  }
})

const visibleHistoryList = computed(() => {
  return rewardHistoryList.value.slice(0, historyLimit.value)
})

const canViewMoreHistory = computed(() => {
  return rewardHistoryList.value.length > historyLimit.value
})

const translateX = computed(() => {
  const drag = isDown.value ? (lastX.value - startX.value) : 0
  return baseX.value + drag
})

function viewAllHistory() {
  historyLimit.value = rewardHistoryList.value.length
}

function claimLevelUpReward() {
  if (!activeLevelObj.value?.id) return
  if (!activeRewardState.value?.levelUp?.claimable) return
  router.post(route('user.vip.claim-level-up', activeLevelObj.value.id))
}

function claimMonthlyReward() {
  if (!activeLevelObj.value?.id) return
  if (!activeRewardState.value?.monthly?.claimable) return
  router.post(route('user.vip.claim-monthly', activeLevelObj.value.id))
}

function updateSlideStep() {
  const wrap = wrapperEl.value
  if (!wrap) return

  const first = wrap.querySelector('.swiper-slide')
  if (!first) return

  const r = first.getBoundingClientRect()
  const mr = parseFloat(getComputedStyle(first).marginRight || '0') || 0
  slideStep.value = r.width + mr
}

function goTo(i, animate = true) {
  const max = Math.max(vipLevels.value.length - 1, 0)
  index.value = clamp(i, 0, max)

  baseX.value = -(index.value * (slideStep.value || 0))
  transitionOn.value = !!animate

  nextTick(() => {
    const wrap = wrapperEl.value
    if (!wrap) return

    const slides = Array.from(wrap.querySelectorAll('.swiper-slide'))
    slides.forEach((sl, k) => {
      sl.classList.toggle('swiper-slide-active', k === index.value)
      sl.classList.toggle('swiper-slide-next', k === index.value + 1)
    })
  })
}

function down(e) {
  isDown.value = true
  startX.value = px(e)
  lastX.value = startX.value
  transitionOn.value = false
}

function move(e) {
  if (!isDown.value) return
  lastX.value = px(e)
}

function up() {
  if (!isDown.value) return
  isDown.value = false

  const dx = lastX.value - startX.value
  const th = Math.min(90, (slideStep.value || 0) * 0.25)

  if (dx < -th) goTo(index.value + 1, true)
  else if (dx > th) goTo(index.value - 1, true)
  else goTo(index.value, true)
}


onMounted(async () => {
  await nextTick()
  updateSlideStep()

  if (wrapperEl.value) {
    ro = new ResizeObserver(() => {
      updateSlideStep()
      goTo(index.value, false)
    })
    ro.observe(wrapperEl.value)
  }

  goTo(0, false)
})

onBeforeUnmount(() => {
  if (ro) ro.disconnect()
})
onBeforeUnmount(() => {
  if (ro) ro.disconnect()
})

const hasAnyVipAchieved = computed(() => {
  return Number(user.value?.vip || 0) > 0
})

</script>

<template>
  <AuthLayout class="main" title="VIP" :showBack="true" :backUrl="route('user.account')">
    <div class="vip" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
      <div class="vip-header">
        <div class="vip-header-wrapper">
          <div class="vip-header-wrapper-avatar">
            <AvatarImage />
          </div>
          <div class="vip-header-wrapper-name">
            <div class="vip-header-wrapper-name-vip" :class="user.vipClass"></div>
            <div class="vip-header-wrapper-name-nickName">
              <h3>{{ user.nickname }}</h3>
            </div>
          </div>
        </div>
      </div>

      <div class="vip-content">
        <div class="vip-content-empirical">
          <div>
            <p class="red">{{ user.exp }} EXP</p>
            <p>My experience</p>
          </div>
          <div>
            <p class="timeTop"><span>{{ user.payoutDays }}</span> Days</p>
            <p>Payout time</p>
          </div>
        </div>

        <div class="vip-content-tip">{{ tipText }}</div>

        <div v-if="vipLevels.length" class="vip-content-card">
          <div class="vip-content-card-item">
            <div ref="swiperEl" class="swiper swiper-initialized swiper-horizontal swiper-ios my-swipe swiper-backface-hidden" @touchstart.passive="down" @touchmove.passive="move" @touchend="up" @mousedown="down" @mousemove="move" @mouseup="up" @mouseleave="up">
              <div ref="wrapperEl" class="swiper-wrapper" :style="{transitionDuration: transitionOn ? '280ms' : '0ms',transform: `translate3d(${translateX}px, 0px, 0px)`,}">
                <div v-for="(lv, i) in vipLevels" :key="lv.vip" class="swiper-slide itemInfo" :class="[lv.levelClass,{'swiper-slide-active': i === index,'swiper-slide-next': i === index + 1,},]" style="margin-right: 20px;">
                  <div class="itemInfo-right">
                    <img :src="lv.rightImg" alt="" />
                  </div>
                  <div class="itemInfo-head">
                    <div><img :src="lv.headImg" alt="" />
                      <h1 :class="lv.levelClass">VIP{{ lv.vip }}</h1>
                        <template v-if="isVipAchieved(lv)">
                          <img class="achive_img" src="/assets/png/HaveReached-bd0aadbf.webp" alt="" />
                          <span class="bgg">Achieved</span>
                        </template>

                        <template v-else>
                          <img src="/assets/png/ununlocked-b5a4c7d0.webp" alt="" />
                          <span>Not open yet</span>
                        </template>
                    </div>
                    <div class="mb30">
                      <p>Upgrading VIP{{ lv.vip }} requires <br />{{ lv.requireExp }}EXP</p>
                    </div>
                    <div class="border" :class="lv.levelClass">{{ betRuleText }}</div>
                  </div>

                  <div class="itemInfo-bottom">
                    <p>VIP{{ lv.vip }}</p>
                    <div>
                      <div class="van-progress" :class="lv.levelClass" :style="{ background: lv.progressBg, height: '8px' }">
                        <span
                          class="van-progress__portion"
                          :style="{
                            width: progressPercent(lv.progressCurrent, lv.progressTarget),
                            background: 'linear-gradient(rgb(255, 252, 231) 0%, rgb(255, 200, 33) 100%)',
                          }"
                        ></span>
                      </div>
                    </div>
                   <div>
  <span class="level" :class="lv.levelClass">
    {{ lv.progressCurrent }}/{{ lv.progressTarget }}
  </span>
  <span>{{ lv.progressText }}</span>
</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="empty__container">
          <svg class="svg-icon icon-empty"><use xlink:href="#icon-empty"></use></svg>
          <p>No VIP level data</p>
        </div>

        <div class="vip-content-weal">
          <div class="slide">
            <div class="vip-content-weal-head ar-1px-b">
              <svg class="svg-icon icon-diamond"><use xlink:href="#icon-diamond"></use></svg>
              <h1>{{ activeVipText }} Benefits level</h1>
            </div>

            <div v-for="(item, idx) in currentBenefits" :key="idx" class="vip-content-weal-con">
              <div><img :src="iconForIndex(idx)" alt="" /></div>
              <div>
                <h2>{{ item.title }}</h2>
                <span>{{ item.desc }}</span>
              </div>
              <div v-if="item.type === 'money_love'">
                <p><img :src="ICON_GOLD" alt="" /> {{ item.right1 }}</p>
                <p v-html="`${svgLove} ${item.right2}`"></p>
              </div>
              <div v-else-if="item.type === 'percent_safe'"><p class="max" v-html="`${svgSafe}${item.right}`"></p></div>
              <div v-else>
                <p class="max" v-html="`${svgRebate}${item.right}`"></p>
              </div>
            </div>
          </div>
        </div>

        <div  v-if="hasAnyVipAchieved"  class="vip-content-myWelfare">
          <div class="slideMy">
            <div class="vip-content-myWelfare-head ar-1px-b">
              <svg class="svg-icon icon-crown"><use xlink:href="#icon-crown"></use></svg>
              <h1>My benefits</h1>
            </div>

            <div class="vip-content-myWelfare-con">
              <div v-if="activeRewardState.levelUp.showCard" class="cards">
                <div class="card">
                  <div class="card-head">
                    <img src="/assets/png/welfare1-eee87ee1.webp">
                    <div class="card-head-mon">
                      <p><img src="/assets/png/gold-4a60a059.webp">{{ activeLevelObj.levelUpRewardMoney }}</p>
                      <p><img src="/assets/png/love2-83aaa90e.webp">{{ activeLevelObj.levelUpRewardLove }}</p>
                    </div>
                  </div>
                  <div class="card-bottom">
                    <h1>Level up rewards</h1>
                    <span>Each account can only receive 1 time</span>
                    <div v-if="activeRewardState.levelUp.hint" class="viewD">{{ activeRewardState.levelUp.hint }}</div>
                  </div>
                  <button :class="activeRewardState.levelUp.buttonClass"@click="claimLevelUpReward">{{ activeRewardState.levelUp.buttonText }}</button>
                </div>
              </div>

              <div v-if="activeRewardState.monthly.showCard" class="cards">
                <div class="card">
                  <div class="card-head">
                    <img src="/assets/png/welfare1-eee87ee1.webp">
                    <div class="card-head-mon">
                      <p><img src="/assets/png/gold-4a60a059.webp">{{ activeLevelObj.monthlyRewardMoney }}</p>
                      <p><img src="/assets/png/love2-83aaa90e.webp">{{ activeLevelObj.monthlyRewardLove }}</p>
                    </div>
                  </div>

                  <div class="card-bottom">
                    <h1>Monthly Salary rewards</h1>
                    <span>Each account can only receive 1 time per month</span>
                    <!-- <div v-if="activeRewardState.monthly.requiredBet > 0" class="viewD">Re-bet: {{ activeRewardState.monthly.qualifiedBet }} / {{ activeRewardState.monthly.requiredBet }}</div> -->
                    <!-- <div v-if="activeRewardState.monthly.hint" class="viewD">{{ activeRewardState.monthly.hint }}</div> -->
                  </div>
                </div>
                <button :class="activeRewardState.monthly.buttonClass" @click="claimMonthlyReward">{{ activeRewardState.monthly.buttonText }}</button>
              </div>

              <div class="cards">
                <div class="card">
                  <div class="card-head tilt">
                    <img src="/assets/png/welfare4-5642a4c8.webp">
                    <div class="card-head-mon">
                      <p><img src="/assets/png/wallet1-8be3e605.webp">{{ activeLevelObj.safeRate }}%</p>
                    </div>
                  </div>
                  <div class="card-bottom">
                    <h1>Safe</h1>
                    <span>Increase the extra income of the safe</span>
                  </div>
                </div>
              </div>

              <div class="cards">
                <div class="card">
                  <div class="card-head tilt">
                    <img src="/assets/png/welfare5-8b250748.webp">
                    <div class="card-head-mon">
                      <p><img src="/assets/png/wallet1-8be3e605.webp">{{ activeLevelObj.rebateRate }}%</p>
                    </div>
                  </div>
                  <div class="card-bottom">
                    <h1>Rebate rate</h1>
                    <span>Increase income of rebate</span>
                    <div class="viewD">Check the details</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="vip-content-recordVsrule">
          <div class="vip-content-recordVsrule-head">
            <button :class="{ active: activeTab === 0 }" @click="setTab(0)">History</button>
            <button :class="{ active: activeTab === 1 }" @click="setTab(1)">Rules</button>
          </div>

          <div class="vip-content-recordVsrule-con" :style="{ display: activeTab === 0 ? 'block' : 'none' }">
            <template v-if="visibleHistoryList.length">
              <div v-for="item in visibleHistoryList" :key="item.id" class="item ar-1px-b">
                <template v-if="item.historyType === 'exp'">
                  <div class="item-left">
                    <span class="blue">{{ item.statusText || 'Experience Bonus' }}</span> 
                    <span>{{ item.title || 'Betting EXP' }}</span>
                    <span>{{ item.rewardedAt }}</span>
                  </div>
                  <div class="item-right">
                    <span></span>
                    <span></span>
                    <span class="green">{{ item.expAmount }} EXP</span>
                  </div>
                </template>

                <template v-else-if="item.historyType === 'vip_level'">
                  <div class="item-left">
                    <span class="yellow">{{ item.statusText || 'VIP level' }}</span>
                    <span>{{ item.title }}</span>
                    <span>{{ item.rewardedAt }}</span>
                  </div>
                </template>

                <template v-else>
                  <div class="item-left">
                    <span :class="item.statusClass || 'green'">{{ item.statusText || 'Successfully received' }}</span>  <br>
                    <span>{{ item.title }}</span> <br>
                    <span>{{ item.rewardedAt }}</span>
                  </div>

                  <div class="item-right">
                    <p v-if="Number(item.rewardAmount) > 0"><img src="/assets/png/gold-4a60a059.webp">{{ item.rewardAmount }}</p>
                    <p v-if="Number(item.rewardLove) > 0"><svg class="svg-icon icon-love" style="    margin-right: 10px;"><use xlink:href="#icon-love"></use></svg>   {{ item.rewardLove }}</p>
                  </div>
                </template>
              </div>
              <button v-if="canViewMoreHistory" @click="viewAllHistory">View All</button>
            </template>

            <template v-else>
              <div class="empty__container">
                <svg class="svg-icon icon-empty"><use xlink:href="#icon-empty"></use></svg>
                <p>No data</p>
              </div>
            </template>
          </div>

          <div class="vip-content-recordVsrule-con" :style="{ display: activeTab === 1 ? 'block' : 'none' }">
            <div class="con-content">
              <div class="con-content__title">
                <h1>VIP privileges</h1>
                <p>VIP rule description</p>
              </div>

              <div class="con-content__rules">
                <div v-for="(rule, ruleIndex) in rulesList" :key="ruleIndex" class="con-content__rules-item ruleHead">
                  <svg class="svg-icon icon-ruleHead"><use xlink:href="#icon-ruleHead"></use></svg>
                  <div class="con-content__rules-item__title">{{ rule.title }}</div>
                  <div class="con-content__rules-item__titleRight"></div>
                  <p>{{ rule.description }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthLayout>
</template>





<style scoped>
.vip-content-recordVsrule-con .item>div {display: inline-block;}
.item {min-height: auto;padding: 6px 0;}
.claim_reword{background: var(--main_gradient-color);color: var(--bg_color_L2);text-align: center;line-height: .93333rem;font-size: .4rem;letter-spacing: .016rem;border-radius: 1.33333rem;margin: 0 auto;}
.vip-content-myWelfare-con .cards .card {width: 4rem;}
.vip-content-myWelfare-con .cards .card-head-mon {width: -webkit-fill-available;}
.view-all-btn {width: -webkit-fill-available;}.vip-content-empirical>div{width: 4rem;margin-right: 5px;}.view-all-btn { width: -webkit-fill-available; }.vip-content-empirical>div{width: 4rem;margin-right: 5px;}.vip-history-list {display: flex;flex-direction: column;gap: 10px;}.vip-history-item {background: #fff;padding: 12px;margin: 10px 0;}.vip-history-item-top {display: flex;align-items: center;justify-content: space-between;gap: 10px;}.vip-history-item-left h4 {margin: 0;font-size: 14px;font-weight: 700;color: #333;}.vip-history-item-left p {margin: 4px 0 0;font-size: 12px;color: #999;}.vip-history-item-right {font-size: 15px;font-weight: 700;color: #18b660;}.vip-history-item-bottom {display: flex;justify-content: space-between;gap: 10px;margin-top: 10px;font-size: 12px;color: #666;flex-wrap: wrap;}
.vip-content-card .itemInfo-bottom>div:last-of-type{margin-top: 10px;}
.achive_img{width: .4rem;margin-right: .06667rem;}
.vip-header {height: 3.0rem;color: #fff;display: block;display: block;display: block;}.vip-content-empirical{right: 35px;}

</style>
