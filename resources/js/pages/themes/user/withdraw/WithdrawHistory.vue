<script setup>
import { computed } from 'vue'
import { router, usePage } from '@/user/inertia'
import PageLayout from '../../layouts/PageLayout.vue'
import CopyValue from '@/pages/themes/partials/CopyValue.vue'

const page = usePage()

const withdraws = computed(() => page.props.withdraws?.data ?? [])
const links = computed(() => page.props.withdraws?.links ?? [])
const filters = computed(() => page.props.filters ?? { method: 'all' })

const methods = [
  { key: 'all', label: 'All', type: 'svg', icon: 'icon-all' },
  { key: 'ewallet', label: 'E-Wallet', type: 'img', icon: 'https://ossimg.dkwinpicture.com/dkwin/payNameIcon/WithBeforeImgIcon2_20250425191537f3he.png' },
  { key: 'usdt', label: 'USDT', type: 'img', icon: 'https://ossimg.dkwinpicture.com/dkwin/payNameIcon/WithBeforeImgIcon2_20250425191258bw3r.png' },
]

const changeMethod = (method) => {
  router.get(route('user.wallet.WithdrawHistory'), { method }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const visitLink = (url) => {
  if (!url) return
  router.visit(url, {
    preserveState: true,
    preserveScroll: true,
  })
}
</script>

<template>
  <PageLayout class="main" title="Withdrawal history" :showBack="true" :backUrl="route('user.wallet.Withdraw')">
    <div class="withdraw-history-page">
      
      <div class="withdraw-filter">
        <div
          v-for="method in methods"
          :key="method.key"
          class="withdraw-filter__item"
          :class="{ active: filters.method === method.key }"
          @click="changeMethod(method.key)"
        >
          <svg v-if="method.type === 'svg'" class="svg-icon method-icon">
            <use :xlink:href="`#${method.icon}`"></use>
          </svg>

          <img v-else :src="method.icon" class="method-img" :alt="method.label">

          <span>{{ method.label }}</span>
        </div>
      </div>

      <div v-if="withdraws.length" class="rechargeh__container-content">
        <div
          v-for="item in withdraws"
          :key="item.id"
          class="rechargeh__container-content__item"
        >
          <div class="rechargeh__container-content__item-header">
            <span class="recharge_tit">Withdraw</span>
            <div class="recharge_right" :class="item.status_class">
              {{ item.status_label }}
            </div>
          </div>

          <div class="van-divider van-divider--hairline divier"></div>

          <div class="rechargeh__container-content__item-body">
            <div>
              <span>Amount</span>
              <span class="price">৳{{ item.amount }}</span>
            </div>
<!-- 
            <div>
              <span>Received</span>
              <span>৳{{ item.net_amount }}</span>
            </div> -->

            <div>
              <span>Type</span>
              <span>{{ item.wallet_type }}</span>
            </div>

            <!-- <div>
              <span>Wallet</span>
              <span>{{ item.wallet_number }}</span>
            </div> -->

            <div>
              <span>Time</span>
              <span>{{ item.created_at }}</span>
            </div>

            <div>
              <span>Order number</span>
              <div class="order">
                <CopyValue :value="item.trx_id" success-message="Order number copied." />
              </div>
            </div>

            <div v-if="item.feedback" class="feedback-box">
              <textarea readonly :value="item.feedback"></textarea>
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

      <div class="infiniteScroll__loading" v-if="links.length > 3">
        <div
          v-for="(link, index) in links"
          :key="index"
          @click="visitLink(link.url)"
          v-html="link.label"
        />
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
.empty__container svg {width: 80px;height: 80px;}
.infiniteScroll__loading {text-align: center;padding: 15px 0;}
.infiniteScroll__loading div {display: inline-block;margin: 0 4px;padding: 6px 10px;background: #fff;border-radius: 6px;cursor: pointer;}
</style>