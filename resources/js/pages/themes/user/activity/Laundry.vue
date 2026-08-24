<script setup>
import { ref, computed, nextTick } from 'vue'
import { Link, useForm } from '@/user/inertia'
import PageLayout from '../../layouts/PageLayout.vue'
import '@/css/activity/page-main-Laundry.css'

const props = defineProps({
  summary: {
    type: Object,
    required: true,
  },
  recentRecords: {
    type: Array,
    default: () => [],
  },
})

const activeTab = ref('all')
const showDialog = ref(false)
const tabRefs = ref([])

const form = useForm({})

const tabs = [
  { key: 'all', label: 'All', icon: 'icon-all' },
  { key: 'lottery', label: 'Lottery', icon: 'icon-lottery' },
  { key: 'casino', label: 'Casino', icon: 'icon-video' },
  { key: 'rummy', label: 'Rummy', icon: 'icon-chess' },
  { key: 'slots', label: 'Slots', icon: 'icon-slot' },
]

const currentTitle = computed(() => {
  return tabs.find(tab => tab.key === activeTab.value)?.label ?? 'All'
})

const currentData = computed(() => {
  return props.summary?.by_tab?.[activeTab.value] ?? {
    today_total_bet: 0,
    claimable: 0,
    rate_percent: 0.01,
    last_claim_at: null,
  }
})

function formatMoney(value) {
  return Number(value ?? 0).toFixed(2)
}

function openDialog() {
  showDialog.value = true
}

function closeDialog() {
  showDialog.value = false
}

async function selectTab(key, index) {
  activeTab.value = key
  await nextTick()
  tabRefs.value[index]?.scrollIntoView({
    behavior: 'smooth',
    inline: 'center',
    block: 'nearest',
  })
}

function claimRebate() {
  if (!props.summary?.can_claim_now || form.processing) return

  form.post(route('user.activity.main.laundry.claim'), {
    preserveScroll: true,
    onSuccess: () => {
      showDialog.value = true
    },
  })
}
</script>

<template>
  <PageLayout title="Rebate" :showBack="true" :backUrl="route('user.activity')">
    <div class="Laundry-page">
      <div class="fun-tabs tabs" activeclassname="tab_active">
        <div class="fun-tabs__tab-list">
          <div
            v-for="(tab, index) in tabs"
            :key="tab.key"
            ref="tabRefs"
            class="fun-tab-item funtab_item"
            :class="{ activeClassName: activeTab === tab.key }"
            :style="activeTab === tab.key ? 'color: rgb(22, 119, 255);' : ''"
            @click="selectTab(tab.key, index)"
          >
            <div class="fun-tab-item__wrap">
              <div class="fun-tab-item__label">
                <div class="tab_item" :class="{ tab_active: activeTab === tab.key }">
                  <svg class="svg-icon" :class="tab.icon">
                    <use :xlink:href="'#' + tab.icon"></use>
                  </svg>
                  <span>{{ tab.label }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="laundry-page_container">
        <div class="laundry-page_box">
          <div class="title">{{ currentTitle }}-Total betting rebate</div>

          <div class="lab">
            <svg class="svg-icon icon-rebateRealTime">
              <use xlink:href="#icon-rebateRealTime"></use>
            </svg>
            Real-time count
          </div>

          <div class="number">
            <svg class="svg-icon icon-rebate">
              <use xlink:href="#icon-rebate"></use>
            </svg>
            {{ formatMoney(currentData.claimable) }}
          </div>

          <div class="txt">
            Rebate rate: {{ Number(currentData.rate_percent).toFixed(2) }}%
          </div>

          <div class="c-row">
            <div class="item">
              <div>
                <p class="tit">Unclaimed total bet</p>
                <span class="num">{{ formatMoney(currentData.today_total_bet) }}</span>
              </div>
              <div>
                <p class="tit">Last rebate</p>
                <span class="num red">{{ formatMoney(summary.today_claimed) }}</span>
              </div>
            </div>

            <div class="item">
              <p class="tit">Total rebate</p>
              <span class="num">{{ formatMoney(summary.total_claimed) }}</span>
            </div>
          </div>

          <!-- <div class="tip">
            You can claim anytime. Only bets placed after the last claim are counted.
          </div> -->

          <!-- <div v-if="summary.last_claim_at" class="tip">
            Last claim time: {{ summary.last_claim_at }}
          </div> -->

<button
  class="rebate-btn"
  :class="{
    'rebate-btn--disabled': !summary.can_claim_now || form.processing,
    'rebate-btn--loading': form.processing
  }"
  :disabled="!summary.can_claim_now || form.processing"
  @click="claimRebate"
>
  <span class="rebate-btn__text">
    {{ form.processing ? 'Processing...' : summary.can_claim_now ? 'One-Click Rebate' : 'No New Bet Available' }}
  </span>
</button>
        </div>

        <div class="laundry-page_list">
          <div class="title">Rebate history</div>

          <Link
            :href="route('user.activity.main.laundry.record')"
            class="van-button van-button--primary van-button--normal van-button--plain van-button--block van-button--round all-record"
          >
            <div class="van-button__content">
              <span class="van-button__text">All history</span>
            </div>
          </Link>

          <div v-if="recentRecords.length" class="history-wrap">
            <div v-for="item in recentRecords" :key="item.id" class="history-item">
              <div>
                <p class="history-date">{{ item.claim_date }}</p>
                <span class="history-sub">
                  Bet: {{ formatMoney(item.total_bet_amount) }}
                </span>
                <div class="history-time" v-if="item.created_at">
                  {{ item.created_at }}
                </div>
              </div>
              <strong class="history-amount">+{{ formatMoney(item.rebate_amount) }}</strong>
            </div>
          </div>

          <div v-else class="empty__container empty">
            <svg class="svg-icon icon-empty">
              <use xlink:href="#icon-empty"></use>
            </svg>
            <p>No rebate history</p>
          </div>
        </div>
      </div>

      <div class="dialog" :class="{ inactive: !showDialog }">
        <div class="dialog__container" role="dialog">
          <div class="dialog__container-img">
            <img src="/assets/png/succeed-83674414.webp" alt="Success">
          </div>

          <div class="dialog__container-title">
            <h1>{{ currentTitle }}-Betting rebate</h1>
          </div>

          <div class="dialog__container-content">
            <div class="Laundry-Con">
              <div class="Laundry-Con_tip">One-Click Rebate successful</div>
              <div class="Laundry-Con_txt">
                Rebate amount:
                <span class="number">{{ formatMoney(summary.today_claimed) }}</span>
              </div>
            </div>
          </div>

          <div class="dialog__container-footer">
            <button @click="closeDialog">Confirm</button>
          </div>
        </div>

        <div class="dialog__outside" @click="closeDialog"></div>
      </div>
    </div>
  </PageLayout>
</template>

<style scoped>
.item {min-height: auto;}
.fun-tabs__tab-list {display: flex;overflow-x: auto;white-space: nowrap;scroll-behavior: smooth;}
.fun-tabs__tab-list::-webkit-scrollbar {display: none;}
.fun-tab-item {flex: 0 0 auto;}
.dialog__container-footer {margin-top: 0.8rem;}
.history-wrap {margin-top: 12px;}
.history-item {display: flex;justify-content: space-between;align-items: center;padding: 12px;border-radius: 12px;background: #fff;margin-bottom: 10px;}
.history-date {font-weight: 600;margin: 0;}
.history-sub {font-size: 12px;color: #666;}
.history-time {font-size: 12px;color: #999;margin-top: 4px;}
.history-amount {color: #1677ff;}
.tabs {margin-top: 0;}
 .adsfgreert{margin: auto;display: flex;justify-content: center;align-items: center;}
 .rebate-btn__text {display: inline-flex;align-items: center;gap: 6px;text-transform: capitalize;}
.rebate-btn{width: 100%;max-width: 320px;height: 41px;margin: 18px auto 0;display: flex;justify-content: center;align-items: center;border: none;outline: none;border-radius: 8px;background: var(--light-main_gradient-color, var(--bg_color_L2));color: #fff;font-size: 15px;font-weight: 700;letter-spacing: 0.3px;cursor: pointer;transition: all 0.25s ease;}


.rebate-btn--disabled {background: #cfd8e3;color: #7d8a99;box-shadow: none;cursor: not-allowed;}
.rebate-btn--loading {position: relative;pointer-events: none;}

@keyframes rebate-spin {to {transform: rotate(360deg);}}
</style>