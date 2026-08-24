<script setup>
import { ref, computed } from 'vue'
import PageLayout from '../../layouts/PageLayout.vue'

const activeTab = ref('lottery')

/* Tabs */
const tabs = [
  { key: 'lottery', label: 'Lottery', icon: 'icon-lottery' },
  { key: 'casino', label: 'Casino', icon: 'icon-video' },
  { key: 'sports', label: 'Sports', icon: 'icon-sport' },
  { key: 'rummy', label: 'Rummy', icon: 'icon-chess' },
  { key: 'slots', label: 'Slots', icon: 'icon-slot' },
]

/* Rebate Data */
const rebateData = {

  casino: [
    { title:'L0', list:[0.3,0.09,0.027,0.0081,0.00243,0.000729]},
    { title:'L1', list:[0.35,0.1225,0.042875,0.015006,0.005252,0.001838]},
    { title:'L2', list:[0.375,0.140625,0.052734,0.019775,0.007416,0.002781]},
    { title:'L3', list:[0.4,0.16,0.064,0.0256,0.01024,0.004096]},
    { title:'L4', list:[0.425,0.180625,0.076766,0.032625,0.013866,0.005893]},
    { title:'L5', list:[0.45,0.2025,0.091125,0.041006,0.018453,0.008304]},
    { title:'L6', list:[0.5,0.25,0.125,0.0625,0.03125,0.015625]},
  ],

  rummy: [
    { title:'L0', list:[0.6,0.18,0.054,0.0162,0.00486,0.001458]},
    { title:'L1', list:[0.7,0.245,0.08575,0.030012,0.010504,0.003677]},
    { title:'L2', list:[0.75,0.28125,0.105469,0.039551,0.014832,0.005562]},
    { title:'L3', list:[0.8,0.32,0.128,0.0512,0.02048,0.008192]},
    { title:'L4', list:[0.85,0.36125,0.153531,0.065251,0.027732,0.011786]},
    { title:'L5', list:[0.9,0.405,0.18225,0.082013,0.036906,0.016608]},
    { title:'L6', list:[1,0.5,0.25,0.125,0.0625,0.03125]},
  ]
}

/* Same Structure Assign */
rebateData.sports = rebateData.casino
rebateData.slots = rebateData.casino
rebateData.lottery = rebateData.rummy

const currentLevels = computed(() => rebateData[activeTab.value])
</script>

<template>
<PageLayout title="Rebate Ratio" :showBack="true" :backUrl="route('user.promotion')">

<div class="x-page" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">

<!-- Tabs -->
<div class="bet-container-sticky">
<div class="van-sticky">
<div>
<div class="fun-tabs tabs" activeclassname="tab_active">

<div class="fun-tabs__tab-list">

<div
  v-for="tab in tabs"
  :key="tab.key"
  class="fun-tab-item funtab_item"
  :class="{ activeClassName: activeTab === tab.key }"
  @click="activeTab = tab.key"
>
  <div class="fun-tab-item__wrap">
    <div class="fun-tab-item__label">
      <div class="tab_item" :class="{ tab_active: activeTab === tab.key }">
        <svg class="svg-icon" :class="tab.icon">
          <use :xlink:href="'#'+tab.icon"></use>
        </svg>
        <span>{{ tab.label }}</span>
      </div>
    </div>
  </div>
</div>

<div class="fun-tabs__active-line"></div>

</div>
</div>
</div>
</div>
</div>

<!-- x-page-list (Structure same, dynamic data) -->
<div class="x-page-list">

<div
  v-for="level in currentLevels"
  :key="level.title"
  class="item"
>

<div class="title">
  Rebate level <span>{{ level.title }}</span>
</div>

<div class="box">

<div
  v-for="(percent,index) in level.list"
  :key="index"
  class="li"
>
  <svg class="svg-icon icon-round img img">
    <use xlink:href="#icon-round"></use>
  </svg>

  <div>
    <span class="sum">
      {{ index+1 }} level lower level commission rebate
    </span>
    <span class="num">
      {{ percent }}%
    </span>
  </div>
</div>

</div>
</div>

</div>

</div>
</PageLayout>
</template>
<style scoped>
.fun-tabs__tab-list {display: flex;overflow-x: auto;white-space: nowrap;scrollbar-width: none; }
.tabs{margin-top:0;}
.fun-tabs__tab-list::-webkit-scrollbar {display: none; 
}.fun-tabs__tab-list {scroll-behavior: smooth;
}.fun-tabs {overflow-x: auto;
}.fun-tabs {overflow-x: auto;-ms-overflow-style: none;  scrollbar-width: none;     }
.fun-tabs::-webkit-scrollbar {display: none;          }
.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.x-page{overflow:hidden;padding:0 .32rem}.x-page .tabs{background:var(--bg_color_L1);margin-left:-.13333rem;width:calc(100% + .13333rem)}.x-page .tab_item{width:2.53333rem;height:1.33333rem;margin-inline:.06667rem;padding:0;color:var(--text_color_L2);border-radius:.21333rem;background:var(--bg_color_L2);display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center}.x-page .tab_item svg{height:.66667rem;width:.66667rem}.x-page .tab_item span{font-size:.32rem}.x-page .tab_item.tab_active{color:var(--text_color_L4);background:var(--main_gradient-color)}.x-page :v-deep(.fun-tabs .fun-tab-item){padding:.18667rem 0}.x-page-list{padding-bottom:.53333rem;margin-top:.26667rem}.x-page-list .item{background-color:var(--bg_color_L2);margin-bottom:.26667rem;padding:.26667rem;border-radius:.26667rem}.x-page-list .item .title{font-size:.37333rem;color:var(--text_color_L1);height:.8rem;line-height:.8rem;display:-webkit-box;display:-webkit-flex;display:flex}.x-page-list .item .title span{display:block;width:.6rem;margin-left:.13333rem;font-weight:800;font-size:.53333rem;color:var(--main-color);font-style:italic}.x-page-list .item .box .li{height:.66667rem;line-height:.66667rem;position:relative;padding-left:.53333rem}.x-page-list .item .box .li:first-child:after{display:none}.x-page-list .item .box .li:after{content:"";position:absolute;border-left:.01333rem dashed var(--darkLight, var(--main-color));height:.2rem;top:-.10667rem;left:.10667rem}html:lang(ar) .x-page-list .item .box .li:after{left:unset;right:.10667rem}.x-page-list .item .box .li .img{position:absolute;left:0;top:50%;-webkit-transform:translate(0,-50%);transform:translateY(-50%);display:block;width:.26667rem;height:.26667rem}html:lang(ar) .x-page-list .item .box .li .img{left:unset;right:0}.x-page-list .item .box .li>div{width:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between}.x-page-list .item .box .li>div .sum{color:var(--text_color_L2);font-size:.34667rem}.x-page-list .item .box .li>div .num{font-size:.34667rem;color:var(--text_color_L1)}
</style>