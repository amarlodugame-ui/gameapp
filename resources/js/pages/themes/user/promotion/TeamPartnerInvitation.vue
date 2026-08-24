<script setup>
import { computed } from 'vue'
import { usePage } from '@/user/inertia'
import PageLayout from '../../layouts/PageLayout.vue'

const page = usePage()

const histories = computed(() => page.props.histories?.data ?? [])
const links = computed(() => page.props.histories?.links ?? [])
</script>

<template>
  <PageLayout
    class="main"
    title="Invitation record"
    :showBack="true"
    :backUrl="route('user.promotion.team-partner')"
  >
    <div class="withdraw-history-page">
      <div v-if="histories.length" class="rechargeh__container-content">
        <div
          v-for="item in histories"
          :key="item.id"
          class="rechargeh__container-content__item"
        >
          <div class="rechargeh__container-content__item-header">
            <span class="recharge_tit">Reward</span>

            <div class="recharge_right" :class="item.status_class">
              {{ item.status_label }}
            </div>
          </div>

          <div class="van-divider van-divider--hairline divier"></div>

          <div class="rechargeh__container-content__item-body">
            <div>
              <span>Bonus Amount</span>
              <span class="price">৳{{ item.bonus_amount }}</span>
            </div>

            <div>
              <span>Completed Turnover</span>
              <span>৳{{ item.current_turnover }}</span>
            </div>

            <div>
              <span>Status</span>
              <span class="success-text">Claimed</span>
            </div>

            <div>
              <span>Claim Time</span>
              <span>{{ item.created_at }}</span>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="empty__container">
        <svg class="svg-icon icon-empty">
          <use xlink:href="#icon-empty"></use>
        </svg>
        <p>No data</p>
      </div>
    </div>
  </PageLayout>
</template>

<style scoped>
.withdraw-history-page {padding: 15px;background: #f5f6fa;min-height: 100vh;}
.withdraw-filter {display: grid;grid-template-columns: repeat(3, 1fr);gap: 8px;margin-bottom: 15px;}
.withdraw-filter__item {height: 38px;border-radius: 5px;background: #fff;display: flex;align-items: center;justify-content: center;gap: 6px;font-size: 13px;font-weight: 600;color: #567;}
.withdraw-filter__item.active {background: var(--main-color);color: #fff;}
.method-icon {width: 20px;height: 20px;fill: currentColor;}
.method-img {width: 20px;height: 20px;object-fit: contain;}
.rechargeh__container-content {display: flex;flex-direction: column;gap: 12px;}
.rechargeh__container-content__item {background: #fff;border-radius: 12px;padding: 14px;box-shadow: 0 3px 12px rgba(0,0,0,0.06);}
.rechargeh__container-content__item-header {display: flex;justify-content: space-between;align-items: center;}
.recharge_tit {min-width: 75px;height: 28px;line-height: 28px;text-align: center;background: var(--norm_red-color, #fb5b5b);color: #fff;border-radius: 5px;font-size: 14px;}
.recharge_right {font-size: 13px;font-weight: 600;}
.recharge_right.pending,
.recharge_right.processing {color: #ff9800;}
.recharge_right.completed,
.recharge_right.success {color: #4caf50;}
.recharge_right.rejected,
.recharge_right.failed {color: #f44336;}
.divier {margin: 10px 0;}
.rechargeh__container-content__item-body {display: flex;flex-direction: column;gap: 8px;}
.rechargeh__container-content__item-body > div {display: flex;justify-content: space-between;align-items: center;font-size: 13px;color: #666;}
.rechargeh__container-content__item-body span:last-child {text-align: right;color: #333;}
.price {color: var(--norm_secondary-color, #ffb300) !important;font-weight: 700;}
.order {max-width: 60%;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;}
.feedback-box {display: block !important;margin-top: 6px;}
.feedback-box textarea {width: 100%;height: 60px;border: 1px solid #eee;border-radius: 8px;padding: 8px;resize: none;background: #f7f7f7;color: #777;font-size: 12px;}
.empty__container {text-align: center;padding: 45px 0;color: #999;}
</style>