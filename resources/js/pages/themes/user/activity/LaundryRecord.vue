<script setup>
import { ref, computed, nextTick } from 'vue'
import PageLayout from '../../layouts/PageLayout.vue'
import '@/css/activity/page-main-Laundry.css'

const props = defineProps({
  records: {
    type: Array,
    default: () => [],
  },
})

const activeTab = ref('all')
const tabRefs = ref([])

const tabs = [
  { key: 'all', label: 'All', icon: 'icon-all' },
  { key: 'lottery', label: 'Lottery', icon: 'icon-lottery' },
  { key: 'casino', label: 'Casino', icon: 'icon-video' },
  { key: 'rummy', label: 'Rummy', icon: 'icon-chess' },
  { key: 'slots', label: 'Slots', icon: 'icon-slot' },
]

function formatMoney(value) {
  return Number(value ?? 0).toFixed(2)
}

async function selectTab(key, index) {
  activeTab.value = key
  await nextTick()
  tabRefs.value[index]?.scrollIntoView({
    behavior: 'smooth',
    inline: 'center',
    block: 'nearest'
  })
}

const filteredRecords = computed(() => {
  if (activeTab.value === 'all') {
    return props.records
  }

  // claim daily total থেকে হচ্ছে, তাই all record সব tab-এও দেখানো হলো
  return props.records.filter(item => item.tab === activeTab.value || item.tab === 'all')
})
</script>

<template>
  <PageLayout
    title="Laundry Record"
    :showBack="true"
    :backUrl="route('user.activity.main.laundry')"
  >
    <div class="Laundry-Record">

      <div class="bet-container-sticky">
        <div class="van-sticky">
          <div style="background-color: rgb(247, 248, 255)">
            <div class="fun-tabs tabs" activeclassname="tab_active">
              <div class="fun-tabs__tab-list">

                <div
                  v-for="(tab, index) in tabs"
                  :key="tab.key"
                  ref="tabRefs"
                  class="fun-tab-item funtab_item"
                  :class="{ activeClassName: activeTab === tab.key }"
                  :style="activeTab === tab.key ? 'color: rgb(22, 119, 255)' : ''"
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

                <div
                  class="fun-tabs__active-line"
                  style="transition: 300ms; width: 0px; height: 3px; transform: translate3d(52px, 0px, 0px); background-color: rgb(22, 119, 255);"
                ></div>

              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="list">
        <div v-if="filteredRecords.length" class="record-list">
          <div v-for="item in filteredRecords" :key="item.id" class="record-card">
            <div class="record-head">
              <strong>{{ item.claim_date }}</strong>
              <strong class="amount">+{{ formatMoney(item.rebate_amount) }}</strong>
            </div>

            <div class="record-body">
              <p>Total Bet: {{ formatMoney(item.total_bet_amount) }}</p>
              <p>Rate: {{ Number(item.rebate_rate_percent).toFixed(2) }}%</p>
              <p>Type: {{ item.tab }}</p>
              <p>Claim Time: {{ item.created_at }}</p>
            </div>
          </div>
        </div>

        <div v-else class="empty__container empty">
          <svg class="svg-icon icon-empty">
            <use xlink:href="#icon-empty"></use>
          </svg>
          <p>No data</p>
        </div>
      </div>

    </div>
  </PageLayout>
</template>

<style scoped>
.tabs {margin-top: 0;}
.fun-tabs__tab-list {display: flex;overflow-x: auto;white-space: nowrap;scroll-behavior: smooth;}
.fun-tabs__tab-list::-webkit-scrollbar {display: none;}
.fun-tab-item {flex: 0 0 auto;}
.record-card {background: #fff;border-radius: 14px;padding: 14px;margin-bottom: 12px;}
.record-head {display: flex;justify-content: space-between;align-items: center;margin-bottom: 10px;}
.record-body p {margin: 4px 0;font-size: 13px;color: #666;}
.amount {color: #1677ff;}
</style>