<script setup>
import PageLayout from '../../layouts/PageLayout.vue'
import { router } from '@/user/inertia'

const props = defineProps({
  betRecords: {
    type: Object,
    required: true,
  },
})

const formatMoney = (amount) => {
  return `৳${Number(amount || 0).toFixed(2)}`
}

const formatDate = (value) => {
  if (!value) return '-'

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value

  return date.toLocaleString('en-GB', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  }).replace(',', '')
}

const gameTypeLabel = (type) => {
  const map = {
    '30s': 'Win Go 30 second',
    '1m': 'Win Go 1 minute',
    '3m': 'Win Go 3 minute',
    '5m': 'Win Go 5 minute',
  }

  return map[type] || type || '-'
}

const statusText = (status) => {
  if (status === 'win') return 'Win'
  if (status === 'lose') return 'Lose'
  return 'Pending'
}

const statusClass = (status) => {
  if (status === 'win') return 'color40C592'
  if (status === 'lose') return 'colorFD565C'
  return 'colorFFA82E'
}

const selectLabel = (bet) => {
  const value = String(bet.bet_value || '').replaceAll('_', ' ')
  return value ? value.charAt(0).toUpperCase() + value.slice(1) : '-'
}

const profitLoss = (bet) => {
  return Number(bet.payout || 0) - Number(bet.amount || 0)
}

const profitClass = (bet) => {
  return profitLoss(bet) >= 0 ? 'h4_green' : 'h4_red'
}

const goToPage = (url) => {
  if (!url) return

  router.visit(url, {
    preserveScroll: true,
    preserveState: true,
  })
}
</script>

<template>
  <PageLayout title="Bet history" :showBack="true" :backUrl="route('user.account')">
    <div class="bet-container" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
      <div class="bet-container-sticky">
        <div class="van-sticky">
          <div style="background: var(--bg_color_L1);">
            <div class="fun-tabs tabs" is-auto-load="true" activeclassname="tab_active">
              <div
                class="fun-tabs__tab-list"
                style="transition-timing-function: cubic-bezier(0.25, 0.46, 0.45, 0.94); transition-duration: 360ms; transform: translate3d(0px, 0px, 0px); display: flex;"
              >
                <div class="fun-tab-item funtab_item activeClassName" style="color: rgb(22, 119, 255);">
                  <div class="fun-tab-item__wrap">
                    <div class="fun-tab-item__label">
                      <div class="tab_item tab_active">
                        <svg class="svg-icon icon-lottery">
                          <use xlink:href="#icon-lottery"></use>
                        </svg>
                        <span>Lottery</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="fun-tab-item funtab_item">
                  <div class="fun-tab-item__wrap">
                    <div class="fun-tab-item__label">
                      <div class="tab_item">
                        <svg class="svg-icon icon-video">
                          <use xlink:href="#icon-video"></use>
                        </svg>
                        <span>Casino</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="fun-tab-item funtab_item">
                  <div class="fun-tab-item__wrap">
                    <div class="fun-tab-item__label">
                      <div class="tab_item">
                        <svg class="svg-icon icon-fish">
                          <use xlink:href="#icon-fish"></use>
                        </svg>
                        <span>Fishing</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="fun-tab-item funtab_item">
                  <div class="fun-tab-item__wrap">
                    <div class="fun-tab-item__label">
                      <div class="tab_item">
                        <svg class="svg-icon icon-chess">
                          <use xlink:href="#icon-chess"></use>
                        </svg>
                        <span>Rummy</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="fun-tab-item funtab_item">
                  <div class="fun-tab-item__wrap">
                    <div class="fun-tab-item__label">
                      <div class="tab_item">
                        <svg class="svg-icon icon-flash">
                          <use xlink:href="#icon-flash"></use>
                        </svg>
                        <span>Original</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="fun-tab-item funtab_item">
                  <div class="fun-tab-item__wrap">
                    <div class="fun-tab-item__label">
                      <div class="tab_item">
                        <svg class="svg-icon icon-slot">
                          <use xlink:href="#icon-slot"></use>
                        </svg>
                        <span>Slots</span>
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

            <div class="bet-container-searchBar">
              <div class="ar-searchbar">
                <div class="ar-searchbar__selector">
                  <div>
                    <span class="ar-searchbar__selector-default">Win Go</span>
                    <i class="van-badge__wrapper van-icon van-icon-arrow-down"></i>
                  </div>
                </div>

                <div class="ar-searchbar__selector">
                  <div>
                    <span class="ar-searchbar__selector-default">Choose a date</span>
                    <i class="van-badge__wrapper van-icon van-icon-arrow-down"></i>
                  </div>
                </div>
              </div>
            </div>

            <div></div>
          </div>
        </div>
      </div>

      <div class="bet-content__box">
        <div class="infiniteScroll ptr--top" id="refresh8c878f0d4dcd43aea2f6442a43c1aaeb">
          <div class="bet-container-items">
            <div class="bet-container-lottery">
              <div v-if="!betRecords.data.length" class="empty-history">
                No bet records found.
              </div>

              <div
                v-for="bet in betRecords.data"
                :key="bet.id"
                class="bet-container-lottery-items"
              >
                <div class="bet-container-lottery-card">
                  <div class="bet-container-lottery-card-header ar-1px-b">
                    <h1>
                      <h2>Win Go</h2>
                      <span :class="statusClass(bet.status)">
                        {{ statusText(bet.status) }}
                      </span>
                    </h1>
                    <p>{{ formatDate(bet.created_at) }}</p>
                  </div>

                  <div class="bet-container-lottery-card-info">
                    <ul>
                      <li>
                        <span>
                          <svg class="svg-icon icon-round">
                            <use xlink:href="#icon-round"></use>
                          </svg>
                          <h2>Type</h2>
                        </span>
                        <span>{{ gameTypeLabel(bet.game_type) }}</span>
                      </li>

                      <li>
                        <span>
                          <svg class="svg-icon icon-round">
                            <use xlink:href="#icon-round"></use>
                          </svg>
                          <h2>Period</h2>
                        </span>
                        <span>{{ bet.round_id }}</span>
                      </li>

                      <li>
                        <span>
                          <svg class="svg-icon icon-round">
                            <use xlink:href="#icon-round"></use>
                          </svg>
                          <h2>Order number</h2>
                        </span>
                        <span>{{ bet.id }}</span>
                      </li>

                      <li>
                        <span>
                          <svg class="svg-icon icon-round">
                            <use xlink:href="#icon-round"></use>
                          </svg>
                          <h2>Select</h2>
                        </span>
                        <p>{{ selectLabel(bet) }}</p>
                      </li>

                      <li>
                        <span>
                          <svg class="svg-icon icon-round">
                            <use xlink:href="#icon-round"></use>
                          </svg>
                          <h2>Total bet</h2>
                        </span>
                        <span>{{ formatMoney(bet.amount) }}</span>
                      </li>
                    </ul>
                  </div>
                </div>

                <!-- <img src="/assets/png/moonBar-8a510a24.png" alt="separator"> -->

                <div class="bet-container-lottery-note">
                  <div class="bet-container-lottery-note-result">
                    <div>
                      <h1>
                        <svg class="svg-icon icon-round">
                          <use xlink:href="#icon-round"></use>
                        </svg>
                        Bet status
                      </h1>

                      <h2>
                        <svg class="svg-icon icon-round">
                          <use xlink:href="#icon-round"></use>
                        </svg>
                        <span :class="statusClass(bet.status)">
                          {{ statusText(bet.status) }}
                        </span>
                      </h2>
                    </div>
                  </div>

                  <div class="bet-container-lottery-note-box">
                    <div>
                      <div class="bet-container-lottery-note-box-para">
                        <h3>{{ formatMoney(bet.amount) }}</h3>
                        <span>Bet amount</span>
                      </div>
                    </div>

                    <div>
                      <div class="bet-container-lottery-note-box-para">
                        <h3>{{ formatMoney(bet.payout) }}</h3>
                        <span>Winnings</span>
                      </div>
                    </div>

                    <div>
                      <div class="bet-container-lottery-note-box-para">
                        <h3>{{ bet.qty }}</h3>
                        <span>Qty</span>
                      </div>
                    </div>

                    <div>
                      <div class="bet-container-lottery-note-box-para">
                        <h4 :class="profitClass(bet)">
                          {{ formatMoney(profitLoss(bet)) }}
                        </h4>
                        <span>Profit/loss</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="betRecords.last_page > 1" class="history-pagination">
                <button
                  class="page-btn"
                  :disabled="!betRecords.prev_page_url"
                  @click="goToPage(betRecords.prev_page_url)"
                >
                  Prev
                </button>

                <span class="page-text">
                  {{ betRecords.current_page }} / {{ betRecords.last_page }}
                </span>

                <button
                  class="page-btn"
                  :disabled="!betRecords.next_page_url"
                  @click="goToPage(betRecords.next_page_url)"
                >
                  Next
                </button>
              </div>
            </div>
          </div>

          <div class="infiniteScroll__loading">
            <div>No more</div>
          </div>
        </div>
      </div>
    </div>
  </PageLayout>
</template>
<style scoped>
@charset "UTF-8";.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.header{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;height:1.06667rem;line-height:.73333rem;padding:.46667rem .4rem 0}.header .cancel{color:var(--text_color_L2)}.header .confirm{color:var(--main_gradient-color)}.list{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-flex-wrap:wrap;flex-wrap:wrap;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;padding:.46667rem .32rem .18667rem}.list .item{width:calc(50% - .28rem);display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;padding:0 .26667rem;height:1.33333rem;background:var(--bg_color_L3);border-radius:.26667rem;margin-bottom:.26667rem;font-size:.37333rem;color:var(--text_color_L2)}.list .item>div{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.list .item .img{display:block;width:1.06667rem;height:1.06667rem;margin-right:.26667rem}.list .item.active{background:var(--main_gradient-color);color:var(--text_color_L4)}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.bet-container-lottery-items{margin-bottom:.32rem}.bet-container-lottery-items img{display:block;width:100%;height:.58667rem}.bet-container-lottery-card{background:var(--darkBg, var(--bg_color_L2));width:100%;border-top-left-radius:.13333rem;border-top-right-radius:.13333rem;padding:.34667rem .32rem}.bet-container-lottery-card-header{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;padding-bottom:.16rem}.bet-container-lottery-card-header h1{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.bet-container-lottery-card-header h1 h2{font-weight:700;font-size:.4rem;color:var(--darkTextW, var(--text_color_L1));padding-bottom:.10667rem}.bet-container-lottery-card-header h1 span{font-size:.37333rem;color:#e98613}.bet-container-lottery-card-header h1 span.color40C592{color:#40c592}.bet-container-lottery-card-header p{font-size:.29333rem;color:var(--text_color_L2)}.bet-container-lottery-card-info{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:start;-webkit-align-items:flex-start;align-items:flex-start;margin-top:.33333rem;min-height:4rem}.bet-container-lottery-card-info.typeFXOSO{min-height:3.33333rem}.bet-container-lottery-card-info img{width:.26667rem;height:3.86667rem;margin-right:.34667rem}.bet-container-lottery-card-info img.typeFXOSO{height:3.2rem}.bet-container-lottery-card-info-chessimg{height:4.09333rem!important}.bet-container-lottery-card-info ul{width:calc(100% - .4rem);display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-flex-wrap:wrap;flex-wrap:wrap}.bet-container-lottery-card-info ul li{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;font-size:.32rem;color:var(--text_color_L3);padding-bottom:.26667rem;width:100%;height:-webkit-fit-content;height:fit-content;position:relative}.bet-container-lottery-card-info ul li:first-child:after{display:none}.bet-container-lottery-card-info ul li:after{content:"";position:absolute;border-left:.01333rem dashed var(--darkLight, var(--main-color));height:.10667rem;top:-.14667rem;left:.14667rem}html:lang(ar) .bet-container-lottery-card-info ul li:after{left:unset;right:.10667rem}.bet-container-lottery-card-info ul li:last-of-type{padding-bottom:0!important;-webkit-box-align:start;-webkit-align-items:flex-start;align-items:flex-start}.bet-container-lottery-card-info ul li:last-of-type h2{-webkit-box-flex:0;-webkit-flex:none;flex:none;width:-webkit-fit-content;width:fit-content}.bet-container-lottery-card-info ul li:last-of-type p{height:-webkit-fit-content;height:fit-content;text-align:right}.bet-container-lottery-card-info ul li span{color:#595959}.bet-container-lottery-card-info ul li span.success{color:var(--norm_green-color)}.bet-container-lottery-card-info ul li span.fail{color:var(--norm_red-color)}.bet-container-lottery-card-info ul li h2 svg{margin-right:.13333rem}.bet-container-lottery-card-info ul li h3{display:inline-block;text-align:center;width:1.33333rem;height:.66667rem;line-height:.66667rem;color:var(--textW);border-radius:.13333rem;margin-left:.16rem;background:var(--norm_green-color)}.bet-container-lottery-card-info ul li p{color:var(--main-color);height:.66667rem;display:inline-block;line-height:.66667rem;text-align:center;border-radius:.66667rem;font-weight:700;font-size:.4rem;width:80%;word-wrap:break-word}.bet-container-lottery-card-info ul li .betcontent{word-wrap:break-word}.bet{background-color:var(--darkBg, var(--bg_color_L2))}.bet .li{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;min-height:.66667rem;line-height:.66667rem;padding-left:.32rem}.bet .li.betNum{-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-flex-wrap:wrap;flex-wrap:wrap}.bet .li.betNum .lab{color:var(--text_color_L1);font-size:.37333rem;width:100%}.bet .li.betNum .lab .txt{font-size:.37333rem;color:var(--text_color_L2)}.bet .li.betNum .lab .betList{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-flex-wrap:wrap;flex-wrap:wrap;width:100%}.bet .li.betNum .lab .betList.select{max-height:5.66667rem;overflow-y:auto}.bet .li.betNum .lab .betList>span{display:inline-block;text-align:center;height:.64rem;line-height:.64rem;width:.64rem;color:var(--text_color_L1);background-color:var(--bg_color_L3);border-radius:.64rem;font-size:.32rem;margin-right:.2rem;margin-bottom:.2rem}.bet .li.betNum .lab .betList>span.active{background-color:var(--main_gradient-color);color:var(--textW)}.bet .li.betNum .lab .betList>span{width:auto;padding:0 .16rem;border-radius:.16rem}.bet .li.betNum .lab:after{top:.26667rem}.bet .li.betNum .lab:before{top:.50667rem;height:97%}.bet .li .lab{color:var(--text_color_L2);position:relative}.bet .li .sub{color:var(--text_color_L1);font-size:.32rem}.bet .li .sub>img{width:.4rem;vertical-align:-.06667rem}.bet .li .sub.success{color:var(--bgcolor-22)}.bet .li .sub.fail{color:var(--main-color)}.bet-container-lottery-note-box{background-color:var(--darkBg, var(--bg_color_L2));display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;padding:.26667rem;-webkit-flex-wrap:wrap;flex-wrap:wrap}.bet-container-lottery-note-box-para{font-size:.32rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.bet-container-lottery-note-box-para h3{font-size:.37333rem;color:var(--text_color_L2)}.bet-container-lottery-note-box-para h4{font-size:.37333rem;color:var(--norm_green-color)}.bet-container-lottery-note-box-para .h4_green{color:var(--norm_green-color)}.bet-container-lottery-note-box-para .h4_red{color:var(--norm_red-color)}.bet-container-lottery-note-box>div{width:48%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;margin:.13333rem 0;background:var(--bg_color_L3);border-radius:.13333rem;height:1.6rem}.bet-container-lottery-note-box>div.last{width:100%}.bet-container-lottery-note-box>div span{font-size:.32rem;color:var(--text_color_L2);display:inline-block;margin-top:.16rem}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.bet-container-lottery-items{margin-bottom:.32rem}.bet-container-lottery-items img{display:block;width:100%;height:.58667rem}.bet-container-lottery-card{background:var(--darkBg, var(--bg_color_L2));width:100%;border-top-left-radius:.13333rem;border-top-right-radius:.13333rem;padding:.34667rem .32rem}.bet-container-lottery-card-header{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;padding-bottom:.16rem}.bet-container-lottery-card-header h1{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.bet-container-lottery-card-header h1 h2{font-weight:700;font-size:.4rem;color:var(--darkTextW, var(--text_color_L1));padding-bottom:.10667rem}.bet-container-lottery-card-header h1 span{font-size:.37333rem;color:#e98613}.bet-container-lottery-card-header h1 span.color40C592{color:#40c592}.bet-container-lottery-card-header p{font-size:.29333rem;color:var(--text_color_L2)}.bet-container-lottery-card-info{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:start;-webkit-align-items:flex-start;align-items:flex-start;margin-top:.33333rem;min-height:3.2rem}.bet-container-lottery-card-info img{width:.26667rem;height:3.06667rem;margin-top:.13333rem;margin-right:.34667rem}.bet-container-lottery-card-info-chessimg{height:4.09333rem!important}.bet-container-lottery-card-info ul{width:calc(100% - .4rem);display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-flex-wrap:wrap;flex-wrap:wrap}.bet-container-lottery-card-info ul li{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;font-size:.32rem;color:var(--text_color_L3);padding-bottom:.26667rem;position:relative;width:100%;height:-webkit-fit-content;height:fit-content}.bet-container-lottery-card-info ul li:last-of-type{padding-bottom:0!important;-webkit-box-align:start;-webkit-align-items:flex-start;align-items:flex-start}.bet-container-lottery-card-info ul li:last-of-type h2{-webkit-box-flex:0;-webkit-flex:none;flex:none;width:-webkit-fit-content;width:fit-content}.bet-container-lottery-card-info ul li:last-of-type p{height:-webkit-fit-content;height:fit-content;text-align:right}.bet-container-lottery-card-info ul li span{color:#595959}.bet-container-lottery-card-info ul li span.success{color:var(--norm_green-color)}.bet-container-lottery-card-info ul li span.fail{color:var(--norm_red-color)}.bet-container-lottery-card-info ul li h3{display:inline-block;text-align:center;width:1.33333rem;height:.66667rem;line-height:.66667rem;color:var(--textW);border-radius:.13333rem;margin-left:.16rem;background:var(--norm_green-color)}.bet-container-lottery-card-info ul li p{color:var(--main-color);height:.66667rem;display:inline-block;line-height:.66667rem;text-align:center;border-radius:.66667rem;font-weight:700;font-size:.4rem;width:80%;word-wrap:break-word}.bet-container-lottery-card-info ul li .betcontent{word-wrap:break-word}.bet-container-lottery-card-info ul .type{margin-top:.13333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-flex-wrap:wrap;flex-wrap:wrap;gap:.09333rem}.bet-container-lottery-card-info ul .type>span{background:var(--bgDark-3, var(--bg_color_L3));width:calc(33.3333333333% - .09333rem);display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;text-align:center;color:var(--text_color_L2);font-size:.29333rem;border-radius:.13333rem;padding:.13333rem 0;margin-bottom:.06667rem}.bet{background-color:var(--darkBg, var(--bg_color_L2))}.bet .li{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;min-height:.66667rem;line-height:.66667rem;padding-left:1.06667rem}.bet .li.betNum{-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-flex-wrap:wrap;flex-wrap:wrap}.bet .li.betNum .lab{color:var(--text_color_L1);font-size:.37333rem;width:100%}.bet .li.betNum .lab .txt{font-size:.37333rem;color:var(--text_color_L2)}.bet .li.betNum .lab .betList{width:100%}.bet .li.betNum .lab .betList.select{max-height:5.66667rem;overflow-y:auto;display:-webkit-box;display:-webkit-flex;display:flex}.bet .li.betNum .lab .betList.select .num>div{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin-bottom:.13333rem}.bet .li.betNum .lab .betList.select .num>div h6{width:1.46667rem;color:var(--text_color_L2)}.bet .li.betNum .lab .betList.select .num>div .n span{border-radius:.08rem;background:var(--bg_color_L3);width:.50667rem;height:.48rem;display:inline-block;margin-right:.13333rem;text-align:center;line-height:.48rem;color:var(--text_color_L1);font-size:.29333rem}.bet .li.betNum .lab .betList>span{display:inline-block;text-align:center;height:.64rem;line-height:.64rem;width:.64rem;color:var(--text_color_L1);background-color:var(--bg_color_L3);border-radius:.64rem;font-size:.32rem;margin-right:.2rem;margin-bottom:.2rem}.bet .li.betNum .lab .betList>span.active{background-color:var(--main_gradient-color);color:var(--textW)}.bet .li.betNum .lab .betList>span{width:auto;padding:0 .16rem;border-radius:.16rem}.bet .li.betNum .lab:after{top:.26667rem}.bet .li.betNum .lab:before{top:.50667rem;height:97%}.bet .li .lab{color:var(--text_color_L2);position:relative}.bet .li .lab:after{content:"";display:block;background:url(/assets/png/before_cire-0951deab.png) no-repeat center center;background-size:cover;height:.26667rem;width:.26667rem;position:absolute;top:50%;-webkit-transform:translate(0,-50%);transform:translateY(-50%);left:-.53333rem}html:lang(ar) .bet .li .lab:after{left:unset;right:-.53333rem}.bet .li .sub{color:var(--text_color_L1);font-size:.32rem}.bet .li .sub>img{width:.4rem;vertical-align:-.06667rem}.bet .li .sub.success{color:var(--bgcolor-22)}.bet .li .sub.fail{color:var(--main-color)}.bet-container-lottery-note-box{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;padding:.26667rem;-webkit-flex-wrap:wrap;flex-wrap:wrap}.bet-container-lottery-note-box-para{font-size:.32rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.bet-container-lottery-note-box-para h3{font-size:.37333rem;color:var(--text_color_L2)}.bet-container-lottery-note-box-para h4{font-size:.37333rem;color:var(--norm_green-color)}.bet-container-lottery-note-box-para .h4_green{color:var(--norm_green-color)}.bet-container-lottery-note-box-para .h4_red{color:var(--norm_red-color)}.bet-container-lottery-note-box>div{width:48%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;margin:.13333rem 0;background:var(--bg_color_L3);border-radius:.13333rem;height:1.6rem}.bet-container-lottery-note-box>div.last{width:100%}.bet-container-lottery-note-box>div span{font-size:.32rem;color:var(--text_color_L2);display:inline-block;margin-top:.16rem}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.circle{width:.53333rem;height:.53333rem;border-radius:50%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;color:var(--bg_color_L2, #fff);font-size:.32rem}.red{background-color:#fd5358}.blue-purple{background-color:#7b8eff}.orange{background-color:#fbb84d}.green{background-color:#3ac967}.light-blue{background-color:#54b0fd}.medium-blue{background-color:#7fadd1}.teal{background-color:#2ad4c5}.orange-red{background-color:#fd8654}.brown{background-color:#b7805f}.purple{background-color:#c36fff}.blue{background-color:var(--main-color)}.colorE98613{color:#e98613!important}.color40C592{color:#40c592!important}.moto-items{margin-bottom:.32rem}.moto-items img{display:block;width:100%;height:.58667rem}.moto-items .moto-card{background:var(--bg_color_L2);width:100%;border-top-left-radius:.13333rem;border-top-right-radius:.13333rem;padding:.34667rem .32rem}.moto-items .moto-card .moto-card-header{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;padding-bottom:.16rem}.moto-items .moto-card .moto-card-header h1{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.moto-items .moto-card .moto-card-header h1 h2{font-weight:700;font-size:.4rem;color:var(--text_color_L1);padding-bottom:.10667rem}.moto-items .moto-card .moto-card-header h1 span{font-size:.37333rem;color:#e98613}.moto-items .moto-card .moto-card-header p{font-size:.29333rem;color:var(--text_color_L2)}.moto-items .moto-card-info{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin-top:.33333rem}.moto-items .moto-card-info ul{width:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column}.moto-items .moto-card-info ul li{-webkit-box-flex:1;-webkit-flex:1;flex:1;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;font-size:.32rem;color:var(--text_color_L2);padding-bottom:.13333rem;position:relative;gap:.13333rem}.moto-items .moto-card-info ul li:first-child:after{display:none}.moto-items .moto-card-info ul li svg{z-index:10;width:.26667rem;height:.26667rem}.moto-items .moto-card-info ul li:after{content:"";position:absolute;border-left:.01333rem dashed var(--darkLight, var(--main-color));height:100%;top:-50%;left:.12rem;z-index:1}html:lang(ar) .moto-items .moto-card-info ul li:after{left:unset;right:.10667rem}.moto-items .moto-card-info ul li:last-of-type{padding-bottom:0!important}.moto-items .moto-card-info ul li span{display:-webkit-box;display:-webkit-flex;display:flex;gap:.13333rem;color:var(--text_color_L1)}.moto-items .moto-card-info ul li span:last-child{max-width:5.81333rem;word-break:break-all}.moto-items .moto-card-info ul li h2{width:-webkit-fit-content;width:fit-content;-webkit-box-flex:0;-webkit-flex:none;flex:none;display:-webkit-box;display:-webkit-flex;display:flex;gap:.13333rem}.moto-items .moto-card-info ul li h3{display:inline-block;text-align:center;width:1.33333rem;height:.66667rem;line-height:.66667rem;color:var(--text_color_L1);border-radius:.13333rem;margin-left:.16rem;background:var(--norm_green-color)}.moto-items .moto-card-info ul li div{color:var(--main-color);height:.66667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;text-align:center;font-weight:700;font-size:.4rem;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}.moto-items .moto-card-info ul li div>div{margin-right:.13333rem}.moto-items .moto-card-info ul li div>div:last-child{margin-right:0}.moto-items .moto-card-info .moto_select{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;gap:.13333rem}.moto-items .moto-card-info .moto_select .position-text{font-size:.32rem;color:var(--text_color_L2)}.moto-items .moto-card-info .moto_select .small-circle{width:.4rem;height:.4rem;font-size:.26667rem;text-align:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;color:var(--text_color_L4)}.moto-items .moto-note{background:var(--bg_color_L2);border-bottom-left-radius:.13333rem;border-bottom-right-radius:.13333rem;padding:0 .32rem .34667rem}.moto-items .moto-note .moto-note-result{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:stretch;-webkit-align-items:stretch;align-items:stretch;padding-top:.13333rem}.moto-items .moto-note .moto-note-result .tt_1{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:center;-webkit-align-items:center;align-items:center;gap:.13333rem;margin-bottom:.26667rem}.moto-items .moto-note .moto-note-result .tt_1 svg{width:.26667rem;height:.26667rem}.moto-items .moto-note .moto-note-result .moto_result{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;gap:.08rem}.moto-items .moto-note .moto-note-box{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;padding:.26667rem 0 0;-webkit-flex-wrap:wrap;flex-wrap:wrap}.moto-items .moto-note .moto-note-box .moto-note-box-para{font-size:.32rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.moto-items .moto-note .moto-note-box .moto-note-box-para h3{font-size:.37333rem;color:var(--text_color_L2)}.moto-items .moto-note .moto-note-box .moto-note-box-para h4{font-size:.37333rem;color:var(--norm_green-color)}.moto-items .moto-note .moto-note-box .moto-note-box-para .h4_green{color:var(--norm_green-color)}.moto-items .moto-note .moto-note-box .moto-note-box-para .h4_red{color:var(--norm_red-color)}.moto-items .moto-note .moto-note-box>div{width:48%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;margin:.13333rem 0;background:var(--bg_color_L3);border-radius:.13333rem;height:1.6rem}.moto-items .moto-note .moto-note-box>div span{font-size:.32rem;color:var(--text_color_L2);display:inline-block;margin-top:.16rem}:root{--van-tabs-card-height: auto}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.bet-container{overflow:hidden;padding:0 .32rem}.bet-container .van-nav-bar{background-color:#f6f6f6}.bet-container .van-nav-bar .van-nav-bar__content .van-nav-bar__left .van-icon{color:var(--text_color_L1)}.bet-container .bet-container__header{background-color:#f6f6f6;position:relative}.bet-container-searchBar{margin-top:-.13333rem;padding-bottom:.24rem}.bet-container .tabs{background:var(--bg_color_L1)}.bet-container .tab_item{width:2.53333rem;height:1.33333rem;margin-inline:.06667rem;padding:0;color:var(--text_color_L2)!important;border-radius:.13333rem;background:var(--bg_color_L2);display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center}.bet-container .tab_item svg{height:.66667rem;width:.66667rem}.bet-container .tab_item span{font-size:.32rem}.bet-container .tab_active{color:var(--text_color_L4)!important;background:var(--main_gradient-color)}.bet-container .fun-tabs .fun-tab-item{padding:.18667rem 0}.bet-container-tabBar{padding-inline:.32rem}.bet-container-tabBar .van-tabs__nav--card{height:1.86667rem;margin:0}.bet-container-tabBar .van-tabs__nav--card .van-tab--card{width:2.53333rem;height:1.33333rem;margin-inline:.06667rem;padding:0;color:var(--text_color_L2)!important;border-radius:.13333rem;background:var(--bgDark-2, var(--bg_color_L2))}.bet-container-tabBar .van-tabs__nav--card .van-tab--card>span{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;width:100%;height:100%}.bet-container-tabBar .van-tabs__nav--card .van-tab--card>span img{width:.66667rem;height:auto}.bet-container-tabBar .van-tabs__nav--card .van-tab--card>span span{font-size:.32rem}.bet-container-tabBar .van-tabs__nav--card .van-tab--card.van-tab--active{color:var(--text_color_L4)!important;background:var(--main_gradient-color)}.bet-container-items{padding-bottom:.32rem;margin:0 auto}.bet-container .colorE98613{color:#e98613!important}.bet-container .color40C592{color:#40c592!important}.bet-container .colorF95959{color:var(--norm_red-color)!important}.bet-container .color2AAB79{color:var(--norm_green-color)!important}.bet-container-lottery-items{margin-bottom:.32rem}.bet-container-lottery-items img{display:block;width:100%;height:.58667rem}.bet-container-lottery .electric-card{border-radius:.13333rem!important;padding:.34667rem .32rem!important}.bet-container-lottery-card{background:var(--bg_color_L2);width:100%;border-top-left-radius:.13333rem;border-top-right-radius:.13333rem;padding:.34667rem .32rem}.bet-container-lottery-card-header{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;padding-bottom:.16rem}.bet-container-lottery-card-header h1{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.bet-container-lottery-card-header h1 h2{font-weight:700;font-size:.4rem;color:var(--text_color_L1);padding-bottom:.10667rem}.bet-container-lottery-card-header h1 span{font-size:.37333rem;color:#e98613}.bet-container-lottery-card-header p{font-size:.29333rem;color:var(--text_color_L2)}.bet-container-lottery-card-info{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin-top:.33333rem}.bet-container-lottery-card-info-chessimg{height:4.09333rem!important}.bet-container-lottery-card-info ul{width:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column}.bet-container-lottery-card-info ul li{-webkit-box-flex:1;-webkit-flex:1;flex:1;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;font-size:.32rem;color:var(--text_color_L2);padding-bottom:.13333rem;position:relative;gap:.13333rem}.bet-container-lottery-card-info ul li:first-child:after{display:none}.bet-container-lottery-card-info ul li svg{z-index:10;width:.26667rem;height:.26667rem}.bet-container-lottery-card-info ul li:after{content:"";position:absolute;border-left:.01333rem dashed var(--darkLight, var(--main-color));height:100%;top:-50%;left:.12rem;z-index:1}html:lang(ar) .bet-container-lottery-card-info ul li:after{left:unset;right:.10667rem}.bet-container-lottery-card-info ul li:last-of-type{padding-bottom:0!important}.bet-container-lottery-card-info ul li span{display:-webkit-box;display:-webkit-flex;display:flex;gap:.13333rem;color:var(--text_color_L1)}.bet-container-lottery-card-info ul li span:last-child{max-width:5.81333rem;word-break:break-all}.bet-container-lottery-card-info ul li h2{width:-webkit-fit-content;width:fit-content;-webkit-box-flex:0;-webkit-flex:none;flex:none;display:-webkit-box;display:-webkit-flex;display:flex;gap:.13333rem}.bet-container-lottery-card-info ul li h3{display:inline-block;text-align:center;width:1.33333rem;height:.66667rem;line-height:.66667rem;color:var(--text_color_L1);border-radius:.13333rem;margin-left:.16rem;background:var(--norm_green-color)}.bet-container-lottery-card-info ul li div{color:var(--main-color);height:.66667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;text-align:center;font-weight:700;font-size:.4rem;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}.bet-container-lottery-card-info ul li div>div{margin-right:.13333rem}.bet-container-lottery-card-info ul li div>div:last-child{margin-right:0}.bet-container-lottery-note{background:var(--bg_color_L2);border-bottom-left-radius:.13333rem;border-bottom-right-radius:.13333rem;padding:0 .32rem .34667rem}.bet-container-lottery-note-box{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;padding:.26667rem 0 0;-webkit-flex-wrap:wrap;flex-wrap:wrap}.bet-container-lottery-note-box-para{font-size:.32rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.bet-container-lottery-note-box-para h3{font-size:.37333rem;color:var(--text_color_L2)}.bet-container-lottery-note-box-para h4{font-size:.37333rem;color:var(--norm_green-color)}.bet-container-lottery-note-box-para .h4_green{color:var(--norm_green-color)}.bet-container-lottery-note-box-para .h4_red{color:var(--norm_red-color)}.bet-container-lottery-note-box>div{width:48%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;margin:.13333rem 0;background:var(--bg_color_L3);border-radius:.13333rem;height:1.6rem}.bet-container-lottery-note-box>div span{font-size:.32rem;color:var(--text_color_L2);display:inline-block;margin-top:.16rem}.bet-container-lottery-note-result{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:stretch;-webkit-align-items:stretch;align-items:stretch;padding-top:.13333rem}.bet-container-lottery-note-result-sportimg{height:5.46667rem!important}.bet-container-lottery-note-result div{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;font-size:.37333rem;color:var(--text_color_L1)}.bet-container-lottery-note-result div h1,.bet-container-lottery-note-result div h2{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:center;-webkit-align-items:center;align-items:center;gap:.13333rem}.bet-container-lottery-note-result div svg{width:.26667rem;height:.26667rem}.bet-container-lottery-note-result div h1{padding-bottom:.56rem}.bet-container-lottery-note-result div h2{color:var(--text_color_L3);position:relative}.bet-container-lottery-note-result div h2:after{content:"";position:absolute;border-left:.01333rem dashed var(--darkLight, var(--main-color));height:100%;top:-80%;left:.12rem}html:lang(ar) .bet-container-lottery-note-result div h2:after{left:unset;right:.17333rem}.bet-container-lottery-note-result div h2 p{color:#e93333;width:.66667rem;height:.66667rem;display:inline-block;line-height:.66667rem;text-align:center;border-radius:.66667rem;background:url(/assets/png/numberBG-5096ec20.png) no-repeat right/contain;font-weight:700;font-size:.4rem}.bet-container-lottery-note-result div h2 .n1{background-image:url(/assets/png/n1-584b8878.png);border-radius:0}.bet-container-lottery-note-result div h2 .n2{background-image:url(/assets/png/n2-447499dc.png);border-radius:0}.bet-container-lottery-note-result div h2 .n3{background-image:url(/assets/png/n3-1432a6bd.png);border-radius:0}.bet-container-lottery-note-result div h2 .n4{background-image:url(/assets/png/n4-9d453819.png);border-radius:0}.bet-container-lottery-note-result div h2 .n5{background-image:url(/assets/png/n5-09b70e91.png);border-radius:0}.bet-container-lottery-note-result div h2 .n6{background-image:url(/assets/png/n6-b68c6bb6.png);border-radius:0}.bet-container-lottery-note-result div h2 span{display:inline-block;text-align:center;min-width:1.33333rem;height:.66667rem;line-height:.61333rem;color:#fff;border-radius:.13333rem;margin-left:.16rem}.bet-container-lottery-note-result div h2 .bigClass{background:var(--norm_secondary-color)}.bet-container-lottery-note-result div h2 .small{background:var(--norm_bule-color)}.bet-container-lottery-note-result div h2 .BG-FCCC52{background:var(--lotterybtnColor)}.bet-container-lottery-note-result div h2 .BG-49CE9B{background:var(--norm_green-color)}.bet-container-lottery-note-result div h2 .BG-C57AFF{background:var(--norm_Purple-color)}.bet-container-lottery-note-result div ul{margin-top:-.16rem}.bet-container-lottery-note-result div ul li{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:start;-webkit-align-items:flex-start;align-items:flex-start}.bet-container-lottery-note-result div ul li h3{font-size:.37333rem;color:var(--text_color_L1)}.bet-container-lottery-note-result div ul li p{font-size:.32rem;color:var(--text_color_L3);padding-top:.17333rem;padding-bottom:.29333rem}.bet-container-lottery-note-result div ul li:last-of-type p{padding-bottom:0!important}.red{background:#fb4e4e}.green{background:#49ce9b}.violet{background:#c57aff}.red_violet{background-image:-webkit-linear-gradient(top left,#fb4e4e 50%,#eb43dd 0);background-image:linear-gradient(to bottom right,#fb4e4e 50%,#eb43dd 0)}.green_violet{background-image:-webkit-linear-gradient(top left,#40ad72 50%,#eb43dd 0);background-image:linear-gradient(to bottom right,#40ad72 50%,#eb43dd 0)}.big_binguo,.small_binguo,.sum_binguo{font-size:.32rem!important;min-width:.56rem!important;height:.56rem!important;border-radius:.10667rem!important;line-height:.56rem!important;padding:0 .05333rem;display:block!important}.big_binguo{color:var(--textW)!important;background-color:var(--LotteryBingoTextColor)!important}.small_binguo{color:var(--LotteryBingoTextColor)!important;background-color:var(--textW)!important}.sum_binguo{color:var(--LotteryBingoTextColor)!important;background-color:var(--colorText-25)!important}.binguo_select{width:.56rem!important;height:.56rem!important;border-radius:50%;border:.01333rem solid var(--LotteryBingoTextColor);color:var(--LotteryBingoTextColor)!important;text-align:center!important;font-size:.4rem;font-style:normal;font-weight:400;line-height:.56rem!important;display:block!important}.binguo_result{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal!important;-webkit-box-direction:normal!important;-webkit-flex-direction:row!important;flex-direction:row!important;-webkit-box-align:center;-webkit-align-items:center;align-items:center;gap:.13333rem;position:relative}.binguo_result:after{content:"";position:absolute;border-left:.01333rem dashed var(--darkLight, var(--main-color));height:100%;top:-80%;left:.17333rem}html:lang(ar) .binguo_result:after{left:unset;right:.17333rem}.binguo_result div{width:.56rem!important;height:.56rem!important;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.binguo_result .result_item{margin-right:.10667rem;border-radius:50%;border:.01333rem solid var(--text_color_L1);color:var(--text_color_L1)!important;font-size:.4rem}.binguo_result .binguo_sum{background:var(--norm_red-color);color:var(--LotteryBingoTextColor);border-radius:.10667rem}.electric-orderNo{-webkit-box-pack:end;-webkit-justify-content:flex-end;justify-content:flex-end;text-align:right;width:100%;word-break:break-all}

.empty-history {
  text-align: center;
  padding: 24px 12px;
  color: #999;
}

.history-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-top: 16px;
}

.page-btn {
  border: 0;
  padding: 8px 14px;
  border-radius: 8px;
  cursor: pointer;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-text {
  font-size: 14px;
}

.colorFD565C {
  color: #fd565c !important;
}

.colorFFA82E {
  color: #ffa82e !important;
}

.h4_red {
  color: #fd565c !important;
}
</style>