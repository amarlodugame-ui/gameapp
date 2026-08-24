<script setup>
import { computed } from 'vue'
import { router, usePage } from '@/user/inertia'
import PageLayout from '../../layouts/PageLayout.vue'
import CopyValue from '@/pages/themes/partials/CopyValue.vue'

const page = usePage()

const deposits = computed(() => page.props.deposits?.data ?? [])
const links = computed(() => page.props.deposits?.links ?? [])
const filters = computed(() => page.props.filters ?? { status: 'all', method: 'all' })
const methods = computed(() => page.props.methods ?? [])
const statuses = computed(() => page.props.statuses ?? [])

const changeMethod = (method) => {
  router.get(route('user.wallet.RechargeHistory'), {
    method,
    status: filters.value.status,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const changeStatus = (status) => {
  router.get(route('user.wallet.RechargeHistory'), {
    method: filters.value.method,
    status,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const visitLink = (url) => {
  if (!url) return
  router.visit(url, {
    preserveScroll: true,
    preserveState: true,
  })
}
</script>

<template>
  <PageLayout class="main" title="Deposit history" :showBack="true" :backUrl="route('user.wallet.recharge')">
    <div class="rechargeh__container" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
      <div class="rechargeh__header_box">
        <div class="fun-tabs tabs">
          <div class="fun-tabs__tab-list">
            <div v-for="method in methods" :key="method.key" class="fun-tab-item funtab_item" @click="changeMethod(method.key)">
              <div class="fun-tab-item__wrap">
                <div class="fun-tab-item__label">
                  <div class="tab_item" :class="{ tab_active: filters.method === method.key }">
                    <svg
                        v-if="method.icon_type === 'svg'"
                        class="svg-icon icon-all method-icon"
                      >
                        <use :xlink:href="`#${method.icon}`"></use>
                      </svg>

                      <img
                        v-else
                        :src="method.icon"
                        :alt="method.label"
                        class="method-icon-img"
                      />

                      <span>{{ method.label }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="infiniteScroll">
        <div class="rechargeh__container-content">
          <div v-for="deposit in deposits" :key="deposit.id" class="rechargeh__container-content__item">
            <div class="rechargeh__container-content__item-header">
              <span class="recharge_tit">Deposit</span>
              <div class="recharge_right" :class="deposit.status_class">
                {{ deposit.status_label }}
              </div>
            </div>

            <div role="separator" class="van-divider van-divider--hairline divier"></div>

            <div class="rechargeh__container-content__item-body">
              <div>
                <span>Balance</span>
                <span class="price">৳{{ deposit.amount }}</span>
              </div>
              <div>
                <span>Type</span>
                <span>{{ deposit.channel_name }}</span>
              </div>
              <div>
                <span>Time</span>
                <span>{{ deposit.created_at }}</span>
              </div>
              <div>
                <span>Order number</span>
                <div class="order">
                  <CopyValue :value="deposit.trx_id" success-message="Order number copied." />
                </div>
              </div>
            </div>
          </div>

          <div v-if="!deposits.length" class="rechargeh__container-content__item">No deposit history found.</div>
        </div>

        <div class="infiniteScroll__loading" v-if="links.length > 3">
          <div v-for="(link, index) in links" :key="index" style="display: inline-block; margin: 0 4px; cursor: pointer;" @click="visitLink(link.url)" v-html="link.label"/>
        </div>
      </div>
    </div>
  </PageLayout>
</template>

<style scoped>
.tabs {
    background: none;
    margin-top: 0;
    margin-bottom: 0;
}
.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.rechargeh__container{padding-inline:.32rem;padding-block:0 1.49333rem;font-family:bahnschrift;position:relative}.rechargeh__container .rechargeh__header_box{background:var(--bg_color_L1);position:relative;z-index:99;overflow:hidden;top:1.46667rem}.rechargeh__container .navbar__content-left>.van-icon{font-size:.53333rem}.rechargeh__container .selectorA{color:var(--text_color_L2)}.rechargeh__container-content{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;gap:.26667rem;margin-top:1.63333rem;margin-bottom:.4rem}.rechargeh__container-content__item{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;gap:.21333rem;padding:.32rem .13333rem;border-radius:.13333rem;background-color:var(--darkBg, var(--bg_color_L2))}.rechargeh__container-content__item-header{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between}.rechargeh__container-content__item-header .recharge_tit{min-width:2rem;margin-left:.13333rem;color:#fff;background:var(--norm_green-color);border-radius:.13333rem;font-size:.37333rem;line-height:.66667rem;height:.66667rem;text-align:center}.rechargeh__container-content__item-header .recharge_right{font-size:.34667rem}.rechargeh__container-content__item-header .success{color:var(--norm_green-color)}.rechargeh__container-content__item-header .timeout{color:var(--norm_secondary-color)}.rechargeh__container-content__item-header .check{color:var(--norm_red-color)}.rechargeh__container-content__item-header .representation{color:var(--main-color)}.rechargeh__container-content__item-header .recharge{color:var(--norm_bule-color)}.rechargeh__container-content__item-header .cancel{color:var(--text_color_L2)}.rechargeh__container-content__item-header .fail{color:var(--norm_red-color)}.rechargeh__container-content__item-body{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;gap:.13333rem;font-size:.32rem;color:var(--text_color_L2)}.rechargeh__container-content__item-body .price{color:var(--norm_secondary-color)}.rechargeh__container-content__item-body>div{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin-bottom:.29333rem;padding:0 .26667rem}.rechargeh__container-content__item-body>div span:first-of-type{color:var(--text_color_L2);font-size:.34667rem}.rechargeh__container-content__item-body>div .order{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;font-size:.32rem;color:var(--text_color_L2)}.rechargeh__container-content__item-body>div .order span{margin-right:.21333rem}.rechargeh__container-content__item-body>div .order img{width:.42667rem;height:.42667rem}.rechargeh__container-content__item .report{height:.85333rem;line-height:.85333rem;color:#fff;background-color:var(--main-color);border-radius:.88rem;text-align:center;font-size:.4rem;font-weight:500}.rechargeh__container-content__item .report.report-b{margin-bottom:.13333rem}.rechargeh__container .query_select{margin-top:.26667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.rechargeh__container .query_select .selectorA{font-size:.37333rem}.rechargeh__container .ar-searchbar__selector>div .noSelect{font-size:.37333rem}.rechargeh__container .ar-searchbar__selector-default{font-size:.34667rem}.rechargeh__container .state_box{width:100%;background-color:#ffffff;height:1.17333rem;border-radius:.13333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;margin-bottom:.4rem;margin-top:.4rem}.rechargeh__container .state_box .state_item{width:50%;line-height:1.17333rem;font-size:.34667rem;font-weight:600;color:var(--text_color_L2);text-align:center;background-color:#ffffff;border-radius:.13333rem}.rechargeh__container .state_box .state_item_active{background:var(--main_gradient-color2);box-shadow:var(--BoxShadowColor-20);color:#ffffff}.rechargeh__container .divier{margin:.02667rem 0}.rechargeh__container-empty{margin-top:.66667rem}.rechargeh__container .rechargeh__header{display:-webkit-box;display:-webkit-flex;display:flex;padding:.26667rem;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.rechargeh__container .rechargeh__header span{font-size:.37333rem;color:var(--text_color_L1)}.rechargeh__container .van-field{background:var(--bg_color_L1);border-radius:.66667rem;margin-left:.26667rem}.gameTabBar{margin-bottom:.38667rem;margin-bottom:.26667rem}.gameTabBar .van-tabs__wrap{height:1.06667rem}.gameTabBar .van-tabs__nav--card{height:1.06667rem;margin:0}.gameTabBar .van-tabs__nav--card .van-tab--card{min-width:2.64rem;height:1.06667rem;margin-inline:.06667rem;color:var(--text_color_L2)!important;padding:0;border-radius:.13333rem;background:var(--bgDark-2, var(--bg_color_L2))}.gameTabBar .van-tabs__nav--card .van-tab--card>span{width:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center}.gameTabBar .van-tabs__nav--card .van-tab--card>span img{width:.56rem;height:auto;margin-right:.13333rem}.gameTabBar .van-tabs__nav--card .van-tab--card>span span{font-size:.34667rem}.gameTabBar .van-tabs__nav--card .van-tab--card.van-tab--active{color:#ffffff!important;background:var(--main-color)}.tab_item{height:1.06667rem;margin-inline:.06667rem;padding:0;color:var(--text_color_L2)!important;border-radius:.13333rem;background:var(--bgDark-2, var(--bg_color_L2));display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;padding:0 .10667rem}.tab_item img,.tab_item svg{height:.56rem;width:.56rem;margin-right:.26667rem}.tab_item span{font-size:.32rem}.tab_active{color:var(--text_color_L4)!important;background:var(--main_gradient-color)} .fun-tabs .fun-tab-item{padding:0!important} .fun-tabs{background:transparent;}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.upi_detail_header{z-index:1;min-height:5.12rem;color:#fff;position:relative;padding-bottom:.4rem}.upi_detail_header .navbar-fixed{background-color:transparent!important;position:absolute}.upi_detail_header .navbar-fixed .navbar__content-right{font-size:.37333rem}.upi_detail_header .info{padding:.8rem 0 .37333rem .32rem;width:74%}.upi_detail_header .info .state_txt{font-style:normal;font-weight:700;font-size:.48rem;line-height:.66667rem;margin-bottom:.32rem}.upi_detail_header .info .tip{font-weight:400;font-size:.34667rem;line-height:.4rem}.upi_detail_header .state_img{width:2.13333rem;height:2.13333rem;position:absolute;right:.18667rem;top:1.89333rem}.wait{background:var(--main-color)}.timeout{background:var(--norm_secondary-color)}.cancel{background:var(--norm_red-color)}.appeal{background:var(--main-color)}.success{background:#34be8b}.fail{background:var(--main-color)}.btn_group{width:100%;height:.93333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-align:center;-webkit-align-items:center;position:relative;align-items:center;z-index:100;padding:0 .26667rem}.btn_group div{width:4.53333rem;background:transparent!important;font-size:.37333rem;color:#fff;height:.93333rem;border:.01333rem solid #fff;border-radius:1.06667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin-right:.26667rem}.btn_group .appeal,.btn_group .appeal_admin{border-radius:1.06667rem;text-align:center}.btn_group .appeal img,.btn_group .appeal_admin img{width:.64rem;height:.64rem;margin-right:.13333rem}.btn_group .cancel{line-height:.93333rem;font-weight:400}.btn_group .cancel img{width:.64rem;height:.64rem;margin-right:.13333rem}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.order_info{position:relative;z-index:2;width:9.38667rem;background:var(--bg_color_L2);border-radius:.13333rem;margin:.4rem auto .48rem;padding:.4rem .26667rem}.order_info .title{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin-bottom:.21333rem}.order_info .title img{width:1.33333rem;height:.54667rem;margin-right:.34667rem}.order_info .title span{font-weight:700;font-size:.42667rem;line-height:.53333rem;color:var(--text_color_L1)}.order_info .dir{width:8.26667rem;margin-left:.64rem;height:.01333rem;background-color:var(--saveTextColor-3)}.upi_info{width:9.38667rem;background:var(--bg_color_L2);border-radius:.13333rem;margin:0 auto .48rem;padding:.4rem .26667rem}.upi_info .upi_title{font-size:.37333rem;line-height:.45333rem;color:var(--text_color_L1);margin-bottom:.26667rem;position:relative}.upi_info .upi_title.tit_img,.upi_info .upi_title.tit_video{line-height:.64rem;font-size:.42667rem;font-weight:600}.upi_info .upi_title.tit_img:before{content:"";width:.64rem;height:.64rem;background-color:transparent;background-image:url(@assets/icons/wallet/withdraw/c2c/uploadImg1.webp);background-repeat:no-repeat;background-size:cover;margin-right:.13333rem}.upi_info .upi_title.tit_video:before{content:"";width:.64rem;height:.64rem;background-image:url(@assets/icons/wallet/withdraw/c2c/uploadVideo.webp);background-repeat:no-repeat;background-size:cover;margin-right:.13333rem}.upi_info .upi_title:before{content:"";display:inline-block;width:.08rem;height:.4rem;background:var(--main-color);margin-right:.10667rem;vertical-align:top}.order_info_box{margin-top:.45333rem}.order_info_box .order_info_item{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin-bottom:.4rem}.order_info_box .order_info_item .title{font-size:.34667rem;line-height:.26667rem;color:var(--text_color_L2)}.order_info_box .order_info_item span{font-size:.34667rem;line-height:.26667rem;color:var(--text_color_L1)}.order_info_box .order_info_item .amount{color:var(--norm_secondary-color)}.order_info_box .order_info_item .time{color:var(--text_color_L2)}.order_info_box .order_info_item .order_num{display:-webkit-box;display:-webkit-flex;display:flex;color:var(--text_color_L2);-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.order_info_box .order_info_item .order_num span{margin-right:.26667rem}.order_info_box .order_info_item .order_num img{width:.42667rem;height:.42667rem}.btn_group{margin-top:.8rem}.btn_group div{margin:0 auto .58667rem}.btn_group .appeal,.btn_group .appeal_admin{background:var(--main_gradient-color2);border-radius:1.06667rem;color:var(--text_color_L4);width:8.98667rem;height:.93333rem;line-height:.93333rem;text-align:center;font-weight:700;font-size:.4rem}.btn_group .cancel{width:8.98667rem;height:.93333rem;line-height:.93333rem;border:.01333rem solid var(--main-color);border-radius:1.06667rem;font-weight:400;font-size:.4rem;color:var(--main-color);text-align:center}.divider{width:100%;height:.01333rem;margin:.53333rem 0 .66667rem;position:relative;background-image:-webkit-linear-gradient(left,#c2c2c2 0%,#c2c2c2 40%,transparent 8%);background-image:linear-gradient(to right,#c2c2c2 0%,#c2c2c2 40%,transparent 8%);background-size:.53333rem .01333rem;background-repeat:repeat-x}.divider:before,.divider:after{content:"";width:.56rem;height:.56rem;border-radius:50%;background-color:var(--bg_color_L1);position:absolute}.divider:before{left:-.53333rem;top:-.28rem}html:lang(ar) .divider:before{right:-.53333rem;left:unset}.divider:after{right:-.53333rem;top:-.28rem}html:lang(ar) .divider:after{right:unset;left:-.53333rem}.pay_img{width:100%;height:auto;object-fit:fill}.withdraw_img{width:33%;height:auto;object-fit:fill}

.fun-tabs__tab-list{
      justify-content: space-evenly;
    align-items: center;
}



.rechargeh__container-content{margin-top: 19px;}

.rechargeh__container .rechargeh__header_box {
    position: relative !important;
    top: 9px !important;
    left: 50% !important;
    transform: translateX(-50%) !important;
    z-index: 99 !important;
    background: #f3f4f7 !important;
    box-sizing: border-box !important;
}
.fun-tabs__tab-list {
  display: grid !important;
  grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
  gap: .18667rem !important;
  width: 100% !important;
  align-items: center !important;
}

.fun-tabs .fun-tab-item {
  padding: 0 !important;
  width: 100% !important;
}

.fun-tab-item__wrap,
.fun-tab-item__label {
  width: 100% !important;
}

.tab_item {
  width: 100% !important;
  height: .88rem !important;
  margin: 0 !important;
  padding: 0 .16rem !important;
  border-radius: .10667rem !important;
  background: #ffffff !important;
  color: #1f2937 !important;

  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: .12rem !important;

  font-size: .32rem !important;
  font-weight: 500 !important;
  box-sizing: border-box !important;
}

.tab_active {
  background: #19b96f !important;
  color: #ffffff !important;
}

.tab_item img,
.tab_item svg {
  width: .42667rem !important;
  height: .42667rem !important;
  margin-right: 0 !important;
  flex-shrink: 0 !important;
}

.tab_item svg,
.tab_item svg use {
  fill: currentColor !important;
  color: currentColor !important;
}

.tab_item span {
  font-size: .32rem !important;
  line-height: 1 !important;
  white-space: nowrap !important;
}





</style>