<script setup>
import AppLayout from "./layouts/AppLayout.vue"
import BottomTabbar from "./partials/BottomTabbar.vue"
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue"
import { router } from "@/user/inertia"
import { route } from "ziggy-js"

import Swiper from "swiper"
import { Autoplay, Pagination } from "swiper/modules"
import "swiper/css"
import "swiper/css/pagination"


const props = defineProps({
  announcements: {
    type: Array,
    default: () => [],
  },
  bonusItems: {
    type: Array,
    default: () => [],
  },
  hasClaimedFirstRechargeBonus: {
    type: Boolean,
    default: false,
  },
  page: {
    type: Object,
    required: true,
  },
})

const pageData = computed(() => props.page || {})
const banners = computed(() => pageData.value.banners || [])
const notice = computed(() => pageData.value.notice || {})
const gameSections = computed(() => pageData.value.game_sections || [])
const winningInformation = computed(() => pageData.value.winning_information || [])
const dailyProfitRank = computed(() => pageData.value.daily_profit_rank || {})
const pwaButton = computed(() => pageData.value.pwa_button || {})
const floatButtons = computed(() => pageData.value.float_buttons || [])
const floatStyle = computed(() => pageData.value.float_style || "")
const firstRecharge = computed(() => pageData.value.first_recharge || {})

const activeTab = ref(pageData.value.default_active_tab || "lottery")

const currentSection = computed(() => {
  return gameSections.value.find(section => section.key === activeTab.value) || null
})

const selectTab = (key) => {
  activeTab.value = key
}

const chunk = (items = [], size = 3) => {
  const result = []
  for (let i = 0; i < items.length; i += size) {
    result.push(items.slice(i, i + size))
  }
  return result
}
const announcementCloseImage = "/assets/png/close-862c6a4d.webp"


const menuRows = computed(() => chunk(gameSections.value, 3))


const currentIndex = ref(0)
const showAnnouncementModal = ref(props.announcements.length > 0)

const currentAnnouncement = computed(() => {
  return props.announcements[currentIndex.value] ?? null
})

const closeAnnouncementModal = () => {
  showAnnouncementModal.value = false
}

const confirmAnnouncement = () => {
  const link = currentAnnouncement.value?.link;

  showAnnouncementModal.value = false;

  if (!link) return;

  if (/^https?:\/\//i.test(link)) {
    window.open(link, "_blank", "noopener,noreferrer");
    return;
  }

  router.visit(link);
};

const showPopup = ref(false)
const noReminderToday = ref(false)

const STORAGE_KEY = "home_popup_hidden_until"

const money = (amount) => {
  return new Intl.NumberFormat("en-BD", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number(amount || 0))
}

const getRouteName = (item = {}) => {
  return item?.route_name || "user.route"
}

const visitRoute = (item = {}) => {
  const routeName = getRouteName(item)
  const routeParams = item?.route_params || {}

  if (item?.url) {
    window.location.href = item.url
    return
  }

  try {
    router.visit(route(routeName, routeParams))
  } catch (error) {
    console.warn(`Route [${routeName}] not found. Please change it from config/home2.php.`)
  }
}

const toggleReminder = () => {
  noReminderToday.value = !noReminderToday.value
}

const closePopup = () => {
  if (noReminderToday.value) {
    const next24Hours = Date.now() + 24 * 60 * 60 * 1000
    localStorage.setItem(STORAGE_KEY, next24Hours.toString())
  } else {
    localStorage.removeItem(STORAGE_KEY)
  }

  showPopup.value = false
}

const goToActivity = () => {
  closePopup()
  visitRoute(firstRecharge.value)
}

const openPwa = () => {
  visitRoute(pwaButton.value)
}
/**
 * Winning information animation
 */
const winnerDisplayCount = computed(() =>
  Number(pageData.value.winning_information_display_count || 8)
)

const winnerInterval = computed(() =>
  Number(pageData.value.winning_information_interval || 2500)
)

const visibleWinners = ref([])
const winnerNextIndex = ref(0)
const winnerRenderSeed = ref(0)
let winnerTimer = null

const makeWinnerNode = (item) => {
  winnerRenderSeed.value += 1

  return {
    ...item,
    __renderKey: `${item.id || item.user_name || "winner"}-${winnerRenderSeed.value}`,
  }
}

const resetWinnerAnimation = () => {
  const list = winningInformation.value || []
  const count = Math.min(winnerDisplayCount.value, list.length)

  visibleWinners.value = list.slice(0, count).map(makeWinnerNode)
  winnerNextIndex.value = count % (list.length || 1)
}

const stepWinnerAnimation = () => {
  const list = winningInformation.value || []

  if (list.length <= winnerDisplayCount.value) {
    return
  }

  const nextItem = list[winnerNextIndex.value]

  visibleWinners.value = [
    ...visibleWinners.value.slice(1),
    makeWinnerNode(nextItem),
  ]

  winnerNextIndex.value = (winnerNextIndex.value + 1) % list.length
}

const startWinnerAnimation = () => {
  stopWinnerAnimation()
  resetWinnerAnimation()

  if (winningInformation.value.length <= winnerDisplayCount.value) {
    return
  }

  winnerTimer = setInterval(() => {
    stepWinnerAnimation()
  }, winnerInterval.value)
}

const stopWinnerAnimation = () => {
  if (winnerTimer) {
    clearInterval(winnerTimer)
    winnerTimer = null
  }
}

const winnerAmountText = (item) => {
  if (item?.amount_text) {
    return item.amount_text
  }

  return `Receive ৳${money(item?.amount || 0)}`
}

const winnerAmountLabel = (item) => {
  return item?.amount_label || "Winning amount"
}

watch(
  [winningInformation, winnerDisplayCount, winnerInterval],
  () => {
    startWinnerAnimation()
  },
  { immediate: true }
)

onMounted(() => {
  if (!gameSections.value.find(section => section.key === activeTab.value)) {
    activeTab.value = pageData.value.default_active_tab || gameSections.value[0]?.key || "lottery"
  }

  if (props.hasClaimedFirstRechargeBonus) {
    showPopup.value = false
  } else {
    const hiddenUntil = localStorage.getItem(STORAGE_KEY)

    if (hiddenUntil && Date.now() < Number(hiddenUntil)) {
      showPopup.value = false
    } else {
      showPopup.value = true
      localStorage.removeItem(STORAGE_KEY)
    }
  }

  startWinnerAnimation()

  if (banners.value.length) {
    new Swiper(".my-swipe", {
      modules: [Autoplay, Pagination],
      slidesPerView: 1,
      loop: banners.value.length > 1,
      autoplay: banners.value.length > 1
        ? {
            delay: 3000,
            disableOnInteraction: false,
          }
        : false,
      pagination: {
        el: ".swiper-pagination",
        clickable: true,
      },
    })
  }
})

onBeforeUnmount(() => {
  stopWinnerAnimation()
})
</script>

<template>
  <AppLayout>
    <template v-if="showPopup">
      <div class="van-overlay" style="z-index: 2004; --f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;"></div>
      <div v-if="showPopup && !props.hasClaimedFirstRechargeBonus" role="dialog" tabindex="0" class="van-popup van-popup--center van-dialog firstSaveDialog" style="overflow: initial; z-index: 2004; --f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
        <div class="van-dialog__header">
          <div class="header">
            <div class="title">Extra first deposit bonus</div>
            <div class="tip">Each account can only receive rewards once</div>
          </div>
        </div>

        <div class="van-dialog__content">
          <div class="container">
            <div class="first_list-item" v-for="item in bonusItems" :key="item.id">
              <div class="head">
                <div class="title">
                  First deposit<span>{{ item.deposit_amount }}</span>
                </div>
                <div class="orange">+ ৳{{ money(item.bonus_amount) }}</div>
              </div>

              <div class="description">
                Deposit {{ item.deposit_amount }} for the first time and you will receive {{ money(item.bonus_amount) }} bonus
              </div>

              <div class="foot">
                <div class="progress" style="background: var(--bg_color_L1); height: 16px; border-radius: 16px;">
                  <div
                    class="line"
                    style="background: var(--norm_secondary-color); width: 0%; height: 16px; border-radius: 16px;"
                  ></div>
                  <div class="step">0/{{ item.deposit_amount }}</div>
                </div>

                <div class="btn n2" @click="goToActivity">Deposit</div>
              </div>
            </div>
            <div class="close" @click="closePopup"></div>
          </div>
        </div>

        <div class="footer">
          <div class="active" @click="toggleReminder">
            <div role="checkbox" class="van-checkbox">
              <div
                class="van-checkbox__icon van-checkbox__icon--round"
                :class="{ 'van-checkbox__icon--checked': noReminderToday }"
              >
                <i class="van-badge__wrapper van-icon van-icon-success"></i>
              </div>
            </div>
            No more reminders today
          </div>

          <div class="btn" @click="goToActivity">Activity</div>
        </div>
      </div>
    </template>

<div v-if="showAnnouncementModal && currentAnnouncement" class="announcement-wrapper">
  <div class="van-overlay" style="z-index: 2002;"></div>

  <div
    role="dialog"
    tabindex="0"
    class="van-popup van-popup--center van-dialog prompt-dialog announcement-dialog"
    style="z-index: 2003;"
  >
    <div class="announcement-close" @click="closeAnnouncementModal">
      <img :src="announcementCloseImage" alt="Close">
    </div>

    <div class="van-dialog__content">
      <div class="promptHeader">{{ currentAnnouncement.title }}</div>
      <div class="promptContent" v-html="currentAnnouncement.content"></div>
      <div class="promptBtn" @click="confirmAnnouncement">
        {{ currentAnnouncement.button_text || 'Confirm' }}
      </div>
    </div>

    <div class="van-hairline--top van-dialog__footer"></div>
  </div>
</div>
    <div class="content bluehome" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
        <div class="swiper_box">
          <div class="swiper my-swipe">
            <div class="swiper-wrapper">
              <div
                v-for="banner in banners"
                :key="banner.id || banner.image"
                class="swiper-slide"
                @click="visitRoute(banner)"
              >
                <img :src="banner.image" :alt="banner.title || 'banner'" />
              </div>
            </div>

            <div class="swiper-pagination"></div>
          </div>
        </div>

      <div class="noticeBar__container">
        <svg class="svg-icon icon-noticeBarSpeaker">
          <use xlink:href="#icon-noticeBarSpeaker"></use>
        </svg>

        <div class="noticeBar__container-body">
          <div class="noticeBar__container-body-text">
            {{ notice.text }}
          </div>
        </div>

        <button class="hotIcon" @click="visitRoute(notice)">
          {{ notice.button_text || 'Detail' }}
        </button>
      </div>

      <!--<div class="game_menu">-->
      <!--  <div-->
      <!--    v-for="(row, rowIndex) in menuRows"-->
      <!--    :key="rowIndex"-->
      <!--    :class="`menu_box${rowIndex + 1}`"-->
      <!--  >-->
      <!--    <div-->
      <!--      v-for="section in row"-->
      <!--      :key="section.key"-->
      <!--      class="menu_item"-->
      <!--      :class="{ active: activeTab === section.key }"-->
      <!--      @click="selectTab(section.key)"-->
      <!--    >-->
      <!--      <img :src="section.icon" :alt="section.title">-->
      <!--      <span>{{ section.menu_title || section.title }}</span>-->
      <!--    </div>-->
      <!--  </div>-->
      <!--</div>-->

      <div class="gamesList__container" v-if="currentSection" id="gameList">
        <div class="gamesList__container-title">
          {{ currentSection.title }}
        </div>

        <div class="gameListGrid__container all_container">

          <div
            v-if="currentSection.type === 'popular'"
            class="hot_container hot_all"
          >
            <div class="platform" v-if="currentSection.platform_items?.length">
              <div class="title">
                <span>{{ currentSection.platform_title || 'Platform recommendation' }}</span>
              </div>

              <div class="list">
                <div
                  class="item"
                  v-for="item in currentSection.platform_items"
                  :key="item.id"
                  @click="visitRoute(item)"
                >
                  <img :src="item.image" :alt="item.name || ''">
                </div>
              </div>
            </div>

            <div class="title">{{ currentSection.popular_title || 'Popular' }}</div>

            <div class="popular pupularAll">
              <div class="list">
                <div
                  v-for="item in currentSection.items"
                  :key="item.id"
                  @click="visitRoute(item)"
                >
                  <div class="item">
                    <img :src="item.image" :alt="item.name || ''">
                  </div>

                  <div class="win-odds">
                    <span>{{ item.label || 'RTP' }}</span>
                    <span>{{ item.value }}</span>
                    <div
                      class="win-p"
                      :style="{ width: item.progress || item.value }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div
            v-else-if="currentSection.type === 'lottery'"
            class="lottery_container all_lottery"
          >
            <div
              class="lotterySlotItem"
              v-for="item in currentSection.items"
              :key="item.id"
              @click="visitRoute(item)"
            >
              <img :src="item.image" :alt="item.name">
              <span>{{ item.name }}</span>
              <h4>
                <div>{{ item.line1 }}</div>
                <div>{{ item.line2 }}</div>
              </h4>
            </div>
          </div>

          <div
            v-else-if="currentSection.type === 'vendor-grid'"
            class="onlineGamesItem__container"
          >
            <div
              class="item"
              v-for="item in currentSection.items"
              :key="item.id"
              @click="visitRoute(item)"
            >
              <img class="gameImg" :src="item.image" :alt="item.name || ''">
            </div>
          </div>

          <div
            v-else-if="currentSection.type === 'provider-card'"
            class="all_other"
          >
            <div
              class="lotterySlotItem__container"
              v-for="item in currentSection.items"
              :key="item.id"
              @click="visitRoute(item)"
            >
              <div class="title">
                <svg class="svg-icon" :class="item.icon_class" v-if="item.icon_class">
                  <use :xlink:href="item.icon_href"></use>
                </svg>

                <div class="tit">{{ item.tit }}</div>
                <div class="desc">{{ item.desc }}</div>
              </div>

              <img class="game_img" :src="item.image" :alt="item.desc || ''">
            </div>
          </div>

          <div
            v-else-if="currentSection.type === 'mini-grid'"
            class="minGame_container all_game"
          >
            <div
              class="onlineGamesItem"
              v-for="item in currentSection.items"
              :key="item.id"
              @click="visitRoute(item)"
            >
              <img
                :class="currentSection.image_class || 'min_game_img'"
                :src="item.image"
                :alt="item.name || ''"
              >
            </div>
          </div>

        </div>
      </div>

<div class="luckyWinners__container">
  <h1>Winning information</h1>

  <div class="luckyWinners__container-wrapper">
    <transition-group name="winner-fade" tag="div" class="winner-list-stage">
      <div
        class="luckyWinners__container-wrapper__item"
        v-for="item in visibleWinners"
        :key="item.__renderKey"
        @click="visitRoute(item)"
      >
        <div class="luckyWinners__container-wrapper__item-img">
          <img class="ar-lazyload" :src="item.profile_image" :alt="item.user_name">
        </div>

        <div class="luckyWinners__container-wrapper__item-info">
          <h1>{{ item.user_name }}</h1>
        </div>

        <div class="luckyWinners__container-wrapper__item-winType">
          <img class="ar-lazyload" :src="item.vendor_logo" :alt="item.user_name">
        </div>

        <div class="luckyWinners__container-wrapper__item-winAmount">
          <h1>{{ winnerAmountText(item) }}</h1>
          <span>{{ winnerAmountLabel(item) }}</span>
        </div>
      </div>
    </transition-group>
  </div>
</div>

      <div class="dailyProfitRank">
        <div class="title"><b></b>{{ dailyProfitRank.title || "Today's earnings chart" }}</div>

        <div class="dailyProfitRank__content">
          <div class="dailyProfitRank__content-topThree">
            <div
              class="dailyProfitRank__content-topThree__item"
              v-for="item in dailyProfitRank.top_three || []"
              :key="item.id"
              :style="{ order: item.order, top: item.top }"
              @click="visitRoute(item)"
            >
              <div :style="{ background: `url('${item.border_image}') center center / 100% 100% no-repeat` }">
                <img class="ar-lazyload" :src="item.profile_image" :alt="item.name">
              </div>

              <div>
                <img class="ar-lazyload" :src="item.crown_image" :alt="item.name">
                <img class="ar-lazyload" :src="item.place_image" :alt="item.name">
              </div>

              <span>{{ item.name }}</span>
              <span>{{ item.amount_text }}</span>
            </div>
          </div>

          <div class="dailyProfitRank__content-list">
            <div
              class="dailyProfitRank__content-list__item"
              v-for="item in dailyProfitRank.list || []"
              :key="item.id"
              @click="visitRoute(item)"
            >
              <span class="left-rank">{{ item.rank }}</span>
              <img class="ar-lazyload" :src="item.profile_image" :alt="item.name">
              <span class="middle-name">{{ item.name }}</span>
              <span class="right-box">{{ item.amount_text }}</span>
            </div>
          </div>
        </div>
      </div>

      <!--<div class="btn pwa-btn" @click="openPwa">-->
      <!--  <img-->
      <!--    class="icon"-->
      <!--    :src="pwaButton.icon"-->
      <!--    :alt="pwaButton.text || 'Add to Desktop'"-->
      <!--  >-->
      <!--  <div class="text">{{ pwaButton.text || 'Add to Desktop' }}</div>-->
      <!--</div>-->

      <div class="float" :style="floatStyle">
        <div
          v-for="item in floatButtons"
          :key="item.id || item.class"
          class="float-entry"
          :class="item.class"
          @click="visitRoute(item)"
        ></div>
      </div>
    </div>

    <!-- <BottomTabbar /> -->
  </AppLayout>
</template>

<style scoped>
.luckyWinners__container-wrapper {
  position: relative;
  overflow: hidden;
}

.winner-list-stage {
  position: relative;
  min-height: 80px; /* item height অনুযায়ী adjust করো */
}

.winner-fade-enter-active,
.winner-fade-leave-active {
  transition: all 0.45s ease;
}

.winner-fade-enter-from {
  opacity: 0;
  transform: translateY(18px);
}

.winner-fade-leave-to {
  opacity: 0;
  transform: translateY(-18px);
}

.winner-fade-leave-active {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
}




.winner-fade-enter-active,
.winner-fade-leave-active,
.winner-fade-move {
  transition: all 0.45s ease;
}

.winner-fade-enter-from {
  opacity: 0;
  transform: translateY(18px);
}
#app .noticeBar__container button{color: var(--main-color) !important;}
.winner-fade-leave-to {
  opacity: 0;
  transform: translateY(-18px);
}

.pwa-btn{position: fixed;
    left: 50%;
    bottom: 2.4rem;
    -webkit-transform: translateX(-50%);
    transform: translate(-50%);
    width: 4.45333rem;
    height: 1.06667rem;
    background: var(--downBg, var(--main_gradient-color2));
    border-radius: 1.06667rem;
    display: -webkit-box;
    display: -webkit-flex;
    display: flex;
    -webkit-box-align: center;
    -webkit-align-items: center;
    align-items: center;
    -webkit-box-pack: center;
    -webkit-justify-content: center;
    justify-content: center;
    gap: .13333rem;
    z-index: 2;
    padding: 0 .26667rem;}
.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.luckyWinners__container>h1{position:relative;margin-bottom:.34667rem;padding-left:.26667rem;font-size:.48rem;font-weight:700;color:var(--darkTextW, var(--text_color_L1))}.luckyWinners__container>h1:before{content:"";position:absolute;top:50%;left:0;-webkit-transform:translateY(-50%);transform:translateY(-50%);width:.08rem;height:.4rem;background:var(--main-color)}.luckyWinners__container-wrapper{height:8.66667rem;overflow:hidden}.luckyWinners__container-wrapper__item{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;width:100%;height:1.6rem;margin-bottom:.13333rem;padding:.26667rem .24rem;border-radius:.13333rem;background:var(--darkBg, var(--bg_color_L2))}.luckyWinners__container-wrapper__item-img{margin-right:.17333rem}.luckyWinners__container-wrapper__item-img img{width:1.06667rem;height:1.06667rem;border-radius:50%}.luckyWinners__container-wrapper__item-info{width:2.24rem;margin-right:.21333rem}.luckyWinners__container-wrapper__item-info h1{color:var(--darkTextW, var(--text_color_L1));font-size:.32rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:100%}.luckyWinners__container-wrapper__item-info span{color:var(--text_color_L2);font-size:.29333rem}.luckyWinners__container-wrapper__item-winType{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;width:1.68rem;height:1.09333rem;margin-right:.22667rem;position:relative}.luckyWinners__container-wrapper__item-winType img{width:100%;height:100%;background:var(--main_gradient-color2);border-radius:.21333rem;box-shadow:var(--BoxShadowColor-20);object-fit:cover}.luckyWinners__container-wrapper__item-winType span{display:inline-block;width:.74667rem;font-weight:700;font-size:.21333rem;color:#fff;position:absolute;left:.2rem;top:.16rem;word-break:keep-all}.luckyWinners__container-wrapper__item-winAmount{text-align:center;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column}.luckyWinners__container-wrapper__item-winAmount h1{color:var(--text_color_L1);font-size:.34667rem;font-weight:600;margin-bottom:.13333rem}.luckyWinners__container-wrapper__item-winAmount span{color:var(--text_color_L2);font-size:.32rem}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.mainContainer{height:auto;margin:.13333rem}.mainContainer_profitContainer{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;position:relative;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;height:1.68rem;border-radius:.13333rem;background:var(--bg_color_L3);margin-top:.13333rem}.mainContainer_profitContainer_medal{display:-webkit-box;display:-webkit-flex;display:flex;width:1.2rem;height:1.38667rem;margin-left:.2rem}.mainContainer_profitContainer_medal img{width:.96rem;height:1.33333rem}.mainContainer_profitContainer_medal span{margin-top:-.13333rem;width:.96rem;height:.96rem}.mainContainer_profitContainer_medal .numberMedal{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;width:.96rem;height:.96rem;background:url(./imgs/MedalNumber.webp) no-repeat center center;margin-top:.2rem;color:#7b8fae;font-family:Jost;font-size:.48rem;font-weight:600}.mainContainer_profitContainer_img{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;margin-left:.26667rem;width:1.17333rem;height:1.17333rem;border-radius:50%;border:.04rem solid rgba(215,143,67,.1)}.mainContainer_profitContainer_img img{padding:.06667rem;width:1.17333rem;height:1.17333rem;border-radius:50%}.mainContainer_profitContainer_title{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;height:1.33333rem;padding:.2rem;color:var(--text_color_L1, #dce0e6);font-family:Inter;font-size:.34667rem;font-weight:500;letter-spacing:-.00693rem}.mainContainer_profitContainer_amount{position:relative;padding:.2rem;z-index:1;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;width:100%;-webkit-box-pack:end;-webkit-justify-content:flex-end;justify-content:flex-end;color:var(--Red-400, #fea800);font-size:.37333rem;font-weight:500;font-family:Inter}.mainContainer_profitContainer_bg{width:3.2rem;height:1.73333rem;position:absolute;top:-.02667rem;right:-.05333rem;z-index:0}.mainContainer_profitContainer_bg svg{width:100%;height:100%;fill:var(--bg_color_L1)}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.originalmainContainer{display:-webkit-box;display:-webkit-flex;display:flex;height:.96rem;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-align:center;-webkit-align-items:center;align-items:center;padding:0 .21333rem;background:var(--bg_color_L2)}.originalmainContainer .backSvg{background-image:url(/assets/svg/backButton-98e18ca5.svg);background-repeat:no-repeat;background-position:center;width:1.12rem;height:100%}.originalmainContainer_title{display:-webkit-box;display:-webkit-flex;display:flex;padding:.13333rem;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;font-family:Inter;font-size:.48rem;font-style:italic;font-weight:700;letter-spacing:-.0048rem;background:var(--Text-effects, linear-gradient(180deg, #F5F6FF -2.72%, rgba(245, 246, 255, 0) 219.64%));background-clip:text;-webkit-background-clip:text;-webkit-text-fill-color:transparent}.luckyWinners__container{margin-top:.53333rem}.GameContainer{height:auto;padding:.26667rem}.GameContainer_games{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-flex-wrap:wrap;flex-wrap:wrap;height:auto;gap:.21333rem}.GameContainer_games>div{position:relative;width:calc((100% - .42667rem) / 3);height:auto}.GameContainer_games>div img{width:100%;height:auto}.WinningContainer{display:-webkit-box;display:-webkit-flex;display:flex;padding:.26667rem;-webkit-box-align:center;-webkit-align-items:center;align-items:center;background:var(--Text-effects, linear-gradient(180deg, #F5F6FF -2.72%, rgba(245, 246, 255, 0) 219.64%));background-clip:text;-webkit-background-clip:text;-webkit-text-fill-color:transparent;letter-spacing:-.0048rem;font-family:Inter;font-size:.48rem;font-style:italic;font-weight:700;margin-top:-.2rem}.WinningContainer:before{content:"";display:block;-webkit-box-flex:0;-webkit-flex:none;flex:none;width:.33333rem;height:.34667rem;background-image:url(/assets/svg/Group20225-a3b6092b.svg);background-position:left;background-repeat:no-repeat}.profitRanking{padding:.09333rem;border-radius:.16rem;background:var(--bg_color_L2);-webkit-backdrop-filter:blur(1.09333rem);backdrop-filter:blur(1.09333rem)}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.header{padding:.13333rem .21333rem;background:#141b26}.header>div{display:-webkit-box;justify-content:center; display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.header>div .searchIcon{background-image:url(/assets/svg/search-e8c2aae9.svg);background-repeat:no-repeat;background-position:center;width:.64rem;height:.64rem}.header>div.l2{height:1.22667rem;display:-webkit-box;display:-webkit-flex;display:flex}.header>div.l2 .inputDom{height:100%;background-color:#4c607f1f;border-radius:.21333rem 0 0 .21333rem;-webkit-box-flex:1;-webkit-flex:1;flex:1;background-image:url(/assets/svg/search-e8c2aae9.svg);background-repeat:no-repeat;background-position:.26667rem center;padding-left:1.06667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.header>div.l2 .inputDom input{height:.53333rem;width:100%;outline:0;border:0;font-size:.4rem;background:transparent;padding:0 .32rem;color:#dbe3ed;border-left:.01333rem solid #48586d}.header>div.l2 .inputDom input::-webkit-input-placeholder{color:#48586d}.header>div.l2 .inputDom input::placeholder{color:#48586d}.header>div.l2 .close{-webkit-box-flex:0;-webkit-flex:none;flex:none;width:1.6rem;line-height:1.22667rem;background-color:#4c607f1f;height:100%;color:#48586d;text-align:center;border-radius:0 .21333rem .21333rem 0;position:relative}.header>div.l2 .close:before{content:"";position:absolute;width:.53333rem;height:100%;top:0;left:0;background:-webkit-linear-gradient(25deg,transparent 0 40%,#141b26 40% 60%,transparent 60% 100%),-webkit-linear-gradient(155deg,transparent 0 40%,#141b26 40% 60%,transparent 60% 100%);background:linear-gradient(65deg,transparent 0 40%,#141b26 40% 60%,transparent 60% 100%),linear-gradient(-65deg,transparent 0 40%,#141b26 40% 60%,transparent 60% 100%);background-size:.42667rem 50%;background-repeat:no-repeat;background-position:0 bottom,0 top}.header .backSvg{background-image:url(/assets/svg/backButton-eadf8686.svg);background-repeat:no-repeat;background-position:center;width:1.12rem;height:.69333rem}.header_title{padding:.13333rem;font-family:Inter;font-size:.48rem;font-style:italic;font-weight:700;letter-spacing:-.0048rem;background:-webkit-linear-gradient(top,#f5f6ff -2.72%,rgba(245,246,255,0) 219.64%);background:linear-gradient(180deg,#f5f6ff -2.72%,rgba(245,246,255,0) 219.64%);background-clip:text;-webkit-background-clip:text;-webkit-text-fill-color:transparent;-webkit-box-flex:1;-webkit-flex:1;flex:1}
.svg-icon{width:1em;height:1em;fill:var(--svgHoticon);vertical-align:middle}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.dialog{position:fixed;top:0;left:0;right:0;bottom:0;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;background:var(--bgcolor-32);-webkit-transition:opacity .15s ease-in-out,visibility .15s ease-in-out;transition:opacity .15s ease-in-out,visibility .15s ease-in-out;z-index:2004}.dialog.active{opacity:1;visibility:visible}.dialog.active .dialog__container{-webkit-transform:scale(1);transform:scale(1);opacity:1}.dialog.inactive{opacity:0;visibility:hidden}.dialog.inactive .dialog__container{-webkit-transform:scale(.3);transform:scale(.3);opacity:0}.dialog__container{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;width:8rem;min-height:6.66667rem;border-radius:.26667rem;background:var(--bg_color_L2);-webkit-transition:opacity .3s,-webkit-transform .3s ease-in-out;transition:opacity .3s,-webkit-transform .3s ease-in-out;transition:opacity .3s,transform .3s ease-in-out;transition:opacity .3s,transform .3s ease-in-out,-webkit-transform .3s ease-in-out;z-index:2003;padding:.53333rem .26667rem;border-radius:20px}.dialog__container-img{width:3.73333rem;height:2.4rem;margin-top:-1.06667rem}.dialog__container-img img{width:100%;height:100%}.dialog__container-title{margin-top:.34667rem;font-size:.48rem;font-weight:700;color:var(--text_color_L1)}.dialog__container-content{margin-top:.24rem;color:var(--text_color_L2);font-size:.32rem;font-weight:400}.dialog__container-footer{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;gap:.28rem;width:100%;height:1.06667rem;margin-top:2rem;padding:0 .26667rem}.dialog__container-footer button{width:3.12rem;height:1.06667rem;color:var(--main-color);font-size:.42667rem;text-align:center;border-radius:9rem;border:.01333rem solid var(--main-color);background:transparent}.dialog__container-footer button:last-of-type{width:4.34667rem;color:var(--text_color_L4);background:var(--main_gradient-color)}.dialog__outside{position:fixed;top:0;left:0;right:0;bottom:0;cursor:default;z-index:2002;background:rgba(0,0,0,.6)}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.point{width:.13333rem;height:.13333rem;background-color:var(--norm_red-color);position:relative;border-radius:50%}.point-flicker:after{background-color:var(--norm_red-color)}.point-flicker:before{background-color:#00a8fd33}.point-flicker:before,.point-flicker:after{content:"";width:.21333rem;height:.21333rem;position:absolute;left:50%;top:50%;margin-left:-.10667rem;margin-top:-.10667rem;border-radius:50%;-webkit-animation:warn-c10b67fb 1.5s ease-out 0s infinite;animation:warn-c10b67fb 1.5s ease-out 0s infinite}@-webkit-keyframes warn-c10b67fb{0%{-webkit-transform:scale(.5);transform:scale(.5);opacity:1}30%{opacity:1}to{-webkit-transform:scale(1.4);transform:scale(1.4);opacity:0}}@keyframes warn-c10b67fb{0%{-webkit-transform:scale(.5);transform:scale(.5);opacity:1}30%{opacity:1}to{-webkit-transform:scale(1.4);transform:scale(1.4);opacity:0}}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.dailyProfitRank{color:var(--text_color_L1);margin-top:.53333rem}.dailyProfitRank .title{height:1.12rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;font-size:.48rem;font-weight:500;margin-bottom:1.6rem}.dailyProfitRank .title b{display:block;height:.34667rem;width:.10667rem;border-radius:.05333rem;background-color:var(--main-color);-webkit-margin-end:.13333rem;margin-inline-end:.13333rem}.dailyProfitRank__content{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.dailyProfitRank__content-topThree{position:relative;display:grid;grid-template-columns:repeat(3,1fr);justify-items:center;width:100%;height:3.33333rem;z-index:1;background:url(/assets/png/DailyProfitRankStage-aa468a0a.webp) no-repeat center center/100% 100%}.dailyProfitRank__content-topThree__item{position:relative;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;width:3rem;height:3.46667rem}.dailyProfitRank__content-topThree__item:nth-child(2){right:.10667rem}.dailyProfitRank__content-topThree__item:nth-child(3){left:.16rem}.dailyProfitRank__content-topThree__item>div:first-of-type{position:relative;display:grid;place-items:center;width:1.6rem;min-width:1.6rem;height:1.6rem;min-height:1.6rem;border-radius:50%;overflow:hidden}.dailyProfitRank__content-topThree__item>div:first-of-type>img{width:calc(100% - .06667rem);height:calc(100% - .06667rem);border-radius:50%}.dailyProfitRank__content-topThree__item>div:last-of-type{position:absolute;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;width:1.78667rem;height:1.82667rem}.dailyProfitRank__content-topThree__item>div:last-of-type img:first-of-type{position:relative;top:-.33333rem;left:-.25333rem;width:1.14667rem}.dailyProfitRank__content-topThree__item>div:last-of-type img:last-of-type{width:1.78667rem;height:.42667rem}.dailyProfitRank__content-topThree__item>span{color:#fff;padding:0 .13333rem;text-align:center}.dailyProfitRank__content-topThree__item>span:first-of-type{margin-block:18% 8%;font-size:.32rem;width:2.66667rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.dailyProfitRank__content-topThree__item>span:last-of-type{width:2.66667rem;height:.66667rem;padding:.17333rem .18667rem;font-size:.32rem;line-height:.32rem;text-align:center;border-radius:9rem;background:var(--bgDark, rgba(255, 255, 255, .3))}.dailyProfitRank__content-list{position:relative;z-index:2;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;gap:.21333rem;width:100%}.dailyProfitRank__content-list__item{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;width:100%;height:1.36rem;padding:.14667rem .25333rem .14667rem .44rem;background:var(--darkBg, var(--bg_color_L2));border-radius:.13333rem}.dailyProfitRank__content-list__item span.left-rank{margin-right:.41333rem;color:var(--text_color_L2);font-size:.48rem;font-weight:500}.dailyProfitRank__content-list__item span.middle-name{color:var(--text_color_L2);font-size:.32rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:2.66667rem}.dailyProfitRank__content-list__item span.right-box{line-height:.66667rem;width:3.46667rem;height:.66667rem;margin-left:auto;color:#fff;font-size:.4rem;text-align:center;border-radius:9rem;background:var(--main_gradient-color)}.dailyProfitRank__content-list__item img{width:1.06667rem;height:1.06667rem;margin-right:.17333rem;border-radius:50%}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.swiper_box{position:relative;padding:0 .34667rem}.swiper_box .swiper-button{padding-top:.26667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.swiper_box .swiper-button span{position:relative;display:inline-block;width:.10667rem;height:.10667rem;border-radius:.10667rem;background-color:#656565;margin:0 .2rem;-webkit-transition:all .5s ease;transition:all .5s ease}.swiper_box .swiper-button .active{background-color:var(--main-color);width:.26667rem;height:.10667rem;-webkit-transition:all .3s ease;transition:all .3s ease}.swiper_box .my-swipe{height:4.26667rem;box-sizing:border-box}.swiper_box .my-swipe img{width:100%;height:100%;border-radius:.26667rem;-webkit-filter:var(--BoxShadowColor-35);filter:var(--BoxShadowColor-35)}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.hot_container .platform,.hot_container .popular{margin:.34667rem 0}.hot_container .platform .list,.hot_container .popular .list{display:grid;grid-template-columns:repeat(3,1fr);grid-gap:.18667rem}.hot_container .platform .list>div,.hot_container .popular .list>div{width:2.88rem;height:2.88rem}.hot_container .platform .list .item,.hot_container .popular .list .item{position:relative;background:var(--main_gradient-color2);box-shadow:var(--BoxShadowColor-20),var(--BoxShadowColor-25);border-radius:.21333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin-bottom:.16rem}.hot_container .platform .list .item img,.hot_container .popular .list .item img{width:2.66667rem;height:2.66667rem;border-radius:.21333rem}.hot_container .platform .list .item .hot_bage,.hot_container .popular .list .item .hot_bage{position:absolute;right:-.08rem;top:-.10667rem;width:.50667rem;height:.48rem}.hot_container .platform .list .win-odds,.hot_container .popular .list .win-odds{display:-webkit-box;display:-webkit-flex;display:flex;height:.48rem;background:rgba(0,0,0,.1);color:#333;width:100%;border-radius:.13333rem;font-size:.29333rem;overflow:hidden;margin-top:.08rem;margin-bottom:.13333rem;position:relative}.hot_container .platform .list .win-odds span,.hot_container .popular .list .win-odds span{position:relative;z-index:1;display:block;height:100%;-webkit-box-flex:1;-webkit-flex:1;flex:1;line-height:.53333rem}.hot_container .platform .list .win-odds span:first-child,.hot_container .popular .list .win-odds span:first-child{padding-left:.13333rem}.hot_container .platform .list .win-odds span:last-child,.hot_container .popular .list .win-odds span:last-child{text-align:right;padding-right:.13333rem}.hot_container .platform .list .win-odds .win-p,.hot_container .popular .list .win-odds .win-p{background:var(--darkLight, var(--main-color));position:absolute;left:0;height:100%}html:lang(ar) .hot_container .platform .list .win-odds .win-p,html:lang(ar) .hot_container .popular .list .win-odds .win-p{left:unset;right:0}.hot_container .pupularAll .list>div{width:2.88rem;height:auto}.hot_container .pupularAll .list .item{width:2.88rem;height:2.88rem}.hot_container .pupularAll .list .item img{width:2.66667rem;height:2.66667rem}.hot_container .title{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin-bottom:.32rem;color:#333;font-size:.4rem;font-weight:600}.hot_container .title:before{content:"";display:block;width:.08rem;height:.4rem;background:var(--main-color);margin-right:.13333rem}.hot_container .look_all{width:100%;height:1.06667rem;border:.01333rem solid #1AB266;border-radius:.53333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center;background-color:#f7f8ff;font-size:.34667rem;color:#1AB266}.hot_container .look_all img{width:.64rem;height:.64rem;margin-right:.18667rem}.gameRec,.hotGames{width:.48rem;height:.48rem;margin-right:.16rem} .hotGames g path{fill:#fe6868}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.minGame_container{display:grid;grid-template-columns:repeat(2,1fr);grid-gap:.45333rem;margin-bottom:.4rem}.onlineGamesItem{position:relative;width:3.25333rem;height:3.25333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center;background:var(--main_gradient-color2);grid-gap:.18667rem}.onlineGamesItem .fish_img{width:3.2rem;height:3.2rem}.onlineGamesItem .min_game_img{width:3.25333rem;height:3.25333rem}.all_game{grid-template-columns:repeat(3,1fr);grid-gap:.34667rem}.all_game .onlineGamesItem{    width: 2.6rem;height:2.88rem;border-radius:.26667rem}.all_game .onlineGamesItem .fish_img{width:2.82667rem;height:2.82667rem;border-radius:.26667rem}.all_game .onlineGamesItem .min_game_img{width:2.88rem;height:2.88rem}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.lotterySlotItem{position:relative;width:100%;height:2.26667rem;text-align:end;background:var(--main_gradient-color2);box-shadow:var(--BoxShadowColor-20),var(--BoxShadowColor-25);margin-bottom:.26667rem;border-radius:.53333rem}.lotterySlotItem img{height:100%}.lotterySlotItem span{position:absolute;top:.26667rem;left:.4rem;color:var(--text_color_black, var(--text_color_L4));white-space:break-spaces;font-weight:700;font-size:.46667rem}html:lang(ar) .lotterySlotItem span{left:unset;right:.4rem}.lotterySlotItem h4{color:var(--text_color_black, var(--text_color_L4));font-weight:400;font-size:.29333rem;position:absolute;bottom:.26667rem;left:.4rem;white-space:pre-wrap;text-align:left;line-height:.46667rem}html:lang(ar) .lotterySlotItem h4{left:unset;right:.4rem}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.lotterySlotItem__container{position:relative;width:100%;height:2.66667rem;border-radius:.53333rem;background:var(--main_gradient-color2);box-shadow:var(--BoxShadowColor-20),var(--BoxShadowColor-25);display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;color:var(--text_color_black, var(--text_color_L4));margin-bottom:.26667rem}.lotterySlotItem__container .title{margin-left:.48rem;margin-top:.29333rem;position:relative;height:100%}.lotterySlotItem__container .title svg{position:absolute;width:1.33333rem;height:.8rem;top:.13333rem;left:0}.lotterySlotItem__container .title .tit{padding-top:.93333rem;font-size:.37333rem;font-weight:700;margin-bottom:.17333rem;position:relative;text-indent:.18667rem}.lotterySlotItem__container .title .tit:before{content:"";position:absolute;display:inline-block;left:0;top:1.01333rem;width:.08rem;height:.34667rem;background:var(--text_color_black, var(--text_color_L4))}.lotterySlotItem__container .title .desc{font-size:.29333rem}.lotterySlotItem__container .game_img{height:2.66667rem;max-width:4rem;object-fit:contain}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.onlineGamesItem__container{display:grid;grid-template-columns:repeat(3,1fr);grid-gap:.21333rem;margin-bottom:.82667rem}.onlineGamesItem__container .item{position:relative;    height: 3.73333rem;background:var(--main_gradient-color2);border-radius:.26667rem}.onlineGamesItem__container .item .gameImg{width: 2.96rem;
    height: 3.73333rem;border-radius:.26667rem}.allGame{grid-template-columns:repeat(3,1fr)}.allGame .item,.allGame .item .gameImg{width:2.96rem;height:3.73333rem}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.WinRate{width:100%;min-height:1.70667rem;margin-bottom:.4rem}.WinRate img{width:100%;height:100%;object-fit:cover}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.gameListGrid__container{position:-webkit-sticky;position:sticky;top:2.45333rem;display:-webkit-inline-box;display:-webkit-inline-flex;display:inline-flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;width:7.3852rem;height:-webkit-min-content;height:min-content;padding-left:.26667rem}.gameListGrid__container-gameRec{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin-bottom:.22173rem;font-size:.32rem;font-weight:700}.gameListGrid__container-gameRec svg{width:.48387rem;height:.48387rem;margin-right:.11147rem}.gameListGrid__container-gameRec .hotGames g path{fill:#fe6868}.gameListGrid__container-wrapper{display:grid;gap:.2rem .26667rem;padding-left:.13333rem}.gameListGrid__container-wrapper__item{position:relative;width:3.28rem;height:2.13333rem}.gameListGrid__container-wrapper__item img{width:100%;height:100%}.gameListGrid__container-wrapper__item .hotBadge{position:absolute;top:-.10667rem;right:-.10667rem;width:.51653rem;height:.48013rem}.gameListGrid__container-wrapper__item.hot{width:2.21747rem;height:2.21747rem}.look_all{width:100%;height:1.06667rem;border:.01333rem solid #1AB266;border-radius:.53333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center;background-color:#f7f8ff;font-size:.34667rem;color:#1AB266}.look_all img{width:.64rem;height:.64rem;margin-right:.18667rem}.all_container{width:100%;padding-left:0}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.gamesList__container{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center}.gamesList__container-title{margin-top:.34667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin-bottom:.32rem;color:var(--main-color);font-size:.4rem;font-weight:600}.gamesList__container-title:before{content:"";display:block;width:.08rem;height:.4rem;margin-right:.13333rem;background:var(--main-color)}.gamesList__container-list{display:grid;grid-template-columns:repeat(3,1fr);justify-items:center;gap:.41333rem .26667rem}.gamesList__container-list__item{width:2.88rem;min-height:2.13333rem;max-height:3.46667rem;background:-webkit-linear-gradient(325.68deg,#50a1f2 12.08%,#85daff 85.02%);background:linear-gradient(124.32deg,#50a1f2 12.08%,#85daff 85.02%);border-radius:.53333rem;position:relative}.gamesList__container-list__item .lottery_text{color:#fff;font-size:.4rem;font-weight:9.33333rem;position:absolute;top:.37333rem;left:.26667rem}html:lang(ar) .gamesList__container-list__item .lottery_text{left:unset;right:.26667rem}.gamesList__container-list__item .item_default{width:100%;height:100%;border-radius:.53333rem;object-fit:fill!important}.gamesList__container-list__item .item_img{height:2rem;object-fit:cover;position:absolute;border-radius:.53333rem;left:.26667rem}html:lang(ar) .gamesList__container-list__item .item_img{left:unset;right:.29333rem}.gamesList__container-list__item .lottery_img{max-width:2rem;height:2rem;object-fit:cover;position:absolute;border-radius:.53333rem;right:.26667rem}html:lang(ar) .gamesList__container-list__item .lottery_img{right:unset;left:.29333rem}.gamesList__container-list__item .logo_img{position:absolute;top:.13333rem;right:.37333rem;width:1.30667rem}html:lang(ar) .gamesList__container-list__item .logo_img{left:unset;right:.37333rem}.gamesList__container-other{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin-top:.26667rem;color:#999;font-size:.32rem;font-weight:500;cursor:pointer}.gamesList__container-other__item{width:100%;height:2.66667rem}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.float{position:fixed;bottom:4rem;right:.4rem;touch-action:none;z-index:1000}.float-entry{width:1.49333rem;height:1.49333rem;border-radius:50%;margin-bottom:.08rem}.reBenefit{background:var(--d9cf7d89-reBenefitBgUrl) no-repeat center/cover;position:relative}.reBenefit .downText{position:absolute;text-align:center;z-index:1;left:0;bottom:0;display:-webkit-inline-box;display:-webkit-inline-flex;display:inline-flex;width:1.46667rem;height:.36rem;padding:.02667rem .06667rem;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center;gap:.13333rem;border-radius:.93333rem;border:.01333rem solid #ffecca;background:#5a0b5d;box-shadow:.01333rem .02667rem .01333rem #fffbff4d inset}.reBenefit .downText .time{background:-webkit-linear-gradient(top,#fff 0%,#ffda2f 100%);background:linear-gradient(180deg,#fff 0%,#ffda2f 100%);background-clip:text;-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-align:center;text-shadow:0 .02667rem 0 rgba(0,0,0,.25);font-family:Poppins;font-size:.29333rem;font-style:normal;font-weight:600;line-height:.30667rem;letter-spacing:-.00667rem}.rewardCenter{background:var(--d9cf7d89-rewardCenterBgUrl) no-repeat center/cover}.turntable-lottery{background:url(/assets/png/turntable_icon-9b14cf81.webp) no-repeat center/cover}.turntable{background:var(--d9cf7d89-bigTurntableBgUrl) no-repeat center/cover}.big-turntable{background:var(--d9cf7d89-homeBigTurntableBgUrl) no-repeat center/cover}.turntable-telegram{background:var(--d9cf7d89-telegramBgUrl) no-repeat center/cover}.changlongEnter{background:var(--d9cf7d89-changlongIconUrl) no-repeat center/cover}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.btn .icon{width:.64rem;height:.64rem}.btn .text{color:#fff;text-shadow:0 .02667rem .01333rem #E04846;font-family:Inter;font-size:.32rem;font-style:normal;font-weight:700;line-height:.53333rem;letter-spacing:.0128rem;word-wrap:break-word;width:-webkit-fit-content;width:fit-content}.btn .bonus-con{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:start;-webkit-align-items:flex-start;align-items:flex-start;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;gap:.04rem}.btn .bonus-con .text{font-weight:500;margin-top:0;font-size:.29333rem;line-height:1}.btn .bonus-con .bonus{font-size:.32rem;font-style:normal;font-weight:600;line-height:.56rem;letter-spacing:.00747rem;color:#fff;text-shadow:0 .02667rem .01333rem #E04846;line-height:1}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.game_menu{width:100%;padding-top:.4rem}.game_menu .menu_box1{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin-bottom:.4rem}.game_menu .menu_box1 div{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;border-radius:.26667rem;position:relative}.game_menu .menu_box1 div span{font-size:.4rem;line-height:.48rem;font-weight:700;color:var(--text_color_black, var(--text_color_L4));text-align:.24rem}.game_menu .menu_box1 div img{margin-top:-.42667rem;margin-bottom:.13333rem}.game_menu .menu_box1 div:nth-child(1){width:3.30667rem;height:2.74667rem;background:url(/assets/png/hot_bg-cdfcf84a.webp) no-repeat center center}.game_menu .menu_box1 div:nth-child(1) img{width:2.6rem;height:2.16rem}.game_menu .menu_box1 div:nth-child(2),.game_menu .menu_box1 div:nth-child(3){width:2.50667rem;height:2.74667rem}.game_menu .menu_box1 div:nth-child(2) img,.game_menu .menu_box1 div:nth-child(3) img{width:2.33333rem;height:2.16rem}.game_menu .menu_box1 div:nth-child(2){background:url(/assets/png/lottery_bg-1edd950a.webp) no-repeat center center}.game_menu .menu_box1 div:nth-child(3){background:url(/assets/png/electronic_bg-3178ba77.webp) no-repeat center center}.game_menu .menu_box1 div:after{content:"";position:absolute;top:50%;left:50%;-webkit-transform:translate(-50%,-50%) scale(0);transform:translate(-50%,-50%) scale(0);width:2.66667rem;height:2.66667rem;border-radius:50%;background-color:#ffffff80;opacity:.5;pointer-events:none;-webkit-transition:opacity 1s ease-in,-webkit-transform .5s ease-out;transition:opacity 1s ease-in,-webkit-transform .5s ease-out;transition:transform .5s ease-out,opacity 1s ease-in;transition:transform .5s ease-out,opacity 1s ease-in,-webkit-transform .5s ease-out}.game_menu .menu_box1 div:hover:after{-webkit-transform:translate(-50%,-50%) scale(1);transform:translate(-50%,-50%) scale(1);opacity:0;-webkit-transition:opacity .5s ease-out,-webkit-transform .5s ease-out;transition:opacity .5s ease-out,-webkit-transform .5s ease-out;transition:transform .5s ease-out,opacity .5s ease-out;transition:transform .5s ease-out,opacity .5s ease-out,-webkit-transform .5s ease-out}.game_menu .menu_box2{width:100%;height:2.32rem;background:url(/assets/png/third_bg-dfa325e7.webp) no-repeat center center;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;border-radius:.26667rem;margin-bottom:.4rem}.game_menu .menu_box2 div{width:33.33%;height:2.32rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;position:relative}.game_menu .menu_box2 div span{font-size:.34667rem;font-weight:600;color:var(--text_color_black, var(--text_color_L4));margin-top:.05333rem}.game_menu .menu_box2 div img{width:1.66667rem;height:1.46667rem;margin-top:.05333rem}.game_menu .menu_box2 .live_menu:before{content:"";position:absolute;display:inline-block;width:.01333rem;height:1.81333rem;background:rgba(255,255,255,.2588235294);left:0;top:.26667rem}html:lang(ar) .game_menu .menu_box2 .live_menu:before{left:unset;right:0}.game_menu .menu_box2 .live_menu:after{content:"";position:absolute;display:inline-block;width:.01333rem;height:1.81333rem;background:rgba(255,255,255,.2588235294);right:0;top:.26667rem}html:lang(ar) .game_menu .menu_box2 .live_menu:after{right:unset;left:0}.game_menu .menu_box3{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between}.game_menu .menu_box3 div{width:4.48rem;height:1.76rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:row;-webkit-justify-content:row;justify-content:row;border-radius:.26667rem;position:relative}.game_menu .menu_box3 div img{width:1.66667rem;height:1.28rem;margin:.24rem auto .24rem .26667rem}.game_menu .menu_box3 div span{font-weight:700;font-size:.37333rem;line-height:.48rem;color:var(--text_color_black, var(--text_color_L4));margin-top:.96rem;margin-right:.37333rem}.game_menu .menu_box3 div:nth-child(1){background:url(/assets/png/fish_bg-bf0b060b.webp) no-repeat center center}.game_menu .menu_box3 div:nth-child(2){background:url(/assets/png/game_mini_bg-c04fcbbd.webp) no-repeat center center}.game_menu .menu_box3 div:after{content:"";position:absolute;top:50%;left:50%;-webkit-transform:translate(-50%,-50%) scale(0);transform:translate(-50%,-50%) scale(0);width:2.66667rem;height:2.66667rem;border-radius:50%;background-color:#ffffff80;opacity:.5;pointer-events:none;-webkit-transition:opacity 1s ease-in,-webkit-transform .5s ease-out;transition:opacity 1s ease-in,-webkit-transform .5s ease-out;transition:transform .5s ease-out,opacity 1s ease-in;transition:transform .5s ease-out,opacity 1s ease-in,-webkit-transform .5s ease-out}.game_menu .menu_box3 div:hover:after{-webkit-transform:translate(-50%,-50%) scale(1);transform:translate(-50%,-50%) scale(1);opacity:0;-webkit-transition:opacity .5s ease-out,-webkit-transform .5s ease-out;transition:opacity .5s ease-out,-webkit-transform .5s ease-out;transition:transform .5s ease-out,opacity .5s ease-out;transition:transform .5s ease-out,opacity .5s ease-out,-webkit-transform .5s ease-out}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.bluehome .swiper_box{padding:0;margin:.26667rem 0}.content{font-family:bahnschrift;gap:.13333rem;padding-bottom:3.73333rem}.content__right{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-align:center;-webkit-align-items:center;align-items:center;gap:.2rem}.content__right svg{position:relative}.content__right .message{position:relative}.content__right .message svg{width:.74667rem;height:.74667rem;color:var(--main-color)}.content__right .point{position:absolute;top:-.02667rem;right:0}.content__right .down{font-size:.53333rem;margin-top:-.13333rem;color:var(--main-color)}.navbar__content-left img{height:80%}.navbar__content-right svg{width:.64rem}.van-nav-bar{background:#f7f8ff}.van-nav-bar .van-nav-bar__content .van-nav-bar__left img{height:100%}.van-nav-bar .van-nav-bar__content .van-nav-bar__right svg{width:.64rem}.customer{position:fixed;bottom:2.4rem;right:.4rem;width:1.49333rem;height:1.49333rem;border-radius:50%;z-index:99}.customer img{width:100%;height:100%;border-radius:50%} .van-dialog{width:8.29333rem;height:12.4rem;border-radius:.2rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;overflow:hidden} .van-dialog__content{position:relative;width:100%} .van-dialog__content .promptHeader{background:var(--main_gradient-color);text-align:center;height:1.17333rem;line-height:1.17333rem;color:var(--text_color_black, var(--text_color_L4));font-weight:700;font-size:.48rem} .van-dialog__content .promptContent{height:9.33333rem;padding:.4rem .26667rem;overflow-y:auto} .van-dialog__content .promptContent img{max-width:7.73333rem} .van-dialog .promptBtn{margin:.26667rem auto;width:4rem;height:1.06667rem;border-radius:1.06667rem;background:var(--main_gradient-color);line-height:1.06667rem;text-align:center;color:var(--text_color_black, var(--text_color_L4))}.message_svg{color:#4781ff}.Laundry-Con .Laundry-Con_tip{font-size:.48rem;font-weight:700;color:#151515}.Laundry-Con .Landty-Con-tips{font-size:.32rem;font-weight:400;color:#666;margin-top:.42667rem}.bluehome .noticeBar__container{margin-top:.26667rem}.bluehome .swiper_box{padding:0}



.hot_container .pupularAll .list .item {
    min-height: 2.8rem;}

.item {
    min-height: 2.9rem;}

.slot-grid {display: grid;gap: 12px;margin-top: 12px;grid-template-columns: repeat(2, minmax(0, 1fr));}.elec, .elec img {height: -webkit-fill-available;}.slot_bg {width: 4.2333rem;}
.btnPawDoen {position: fixed;left: 50%;bottom: 2.4rem;-webkit-transform: translateX(-50%);transform: translate(-50%);width: 4.45333rem;height: 1.06667rem;background: var(--downBg, var(--main_gradient-color2));border-radius: 1.06667rem;display: -webkit-box;display: -webkit-flex;display: flex;-webkit-box-align: center;-webkit-align-items: center;align-items: center;-webkit-box-pack: center;-webkit-justify-content: center;justify-content: center;gap: .13333rem;z-index: 2;padding: 0 .26667rem;}
.swiper_box {margin-bottom: 12px;}.top-swiper {width: 100%;overflow: hidden;border-radius: 12px;touch-action: pan-y;}.top-swiper .swiper-wrapper {display: flex;will-change: transform;}.top-swiper-slide {flex: 0 0 100%;width: 100% !important;margin-right: 0 !important;}.top-swiper-slide img {width: 100%;display: block;border-radius: 12px;}
.top-swiper-dots {display: flex;justify-content: center;align-items: center;gap: 8px;margin-top: 10px;}.top-swiper-dots .dot {width: 8px;height: 8px;border-radius: 999px;background: rgba(119, 119, 119, 0.35);transition: all 0.3s ease;cursor: pointer;}.top-swiper-dots .dot.active {width: 20px;background: #000000;}
.first_list-item .foot .btn.n2 {
  height: 20px !important;
  cursor: pointer;
}

.first_list-item .head .title {
  font-size: .37333rem;
  color: var(--text_color_L1);
  font-family: sans-serif;
  -webkit-text-stroke: aliceblue;
  font-weight: 400;
}

.firstSaveDialog .van-dialog__header {
  padding-top: 0;
  width: -webkit-fill-available;
}

.first_list-item .foot .btn.n1 {
  background: var(--main_gradient-color);
  height: 20px;
}

.footer {
  width: -webkit-fill-available;
}

.close {
  bottom: -3.1rem;
  cursor: pointer;
}.lottery-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.lottery-grid-item {
  width: 100%;
}

.lottery-grid-item img {
  width: 100%;
  display: block;
  border-radius: 12px;
}


.announcement-close {
  position: absolute;
  top: .2rem;
  right: .2rem;
  width: .72rem;
  height: .72rem;
  z-index: 30;
  cursor: pointer;
}

.announcement-close img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: contain;
}
</style>