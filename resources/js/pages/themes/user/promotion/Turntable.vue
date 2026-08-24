<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import AuthLayout from '../../layouts/AuthLayout.vue'
import AvatarImage from '../profile/component/AvatarImage.vue'

const props = defineProps({
  userName: {
    type: String,
    default: '',
  },
})

const canvasRef = ref(null)

let ctx = null
let rafId = null
let currentAngle = 0
let spinning = false

const images = {
  wheel: null,
  glow: null,
  center: null,
  pointer: null,
  money: null,
}

const prizes = [
  { label: '৳100' },
  { label: '৳85' },
  { label: '৳65' },
  { label: '৳75' },
  { label: '৳80' },
  { label: '৳60' },
  { label: '৳55' },
  { label: '৳50' },
]

const assetPaths = {
  wheel: '/assets/png/turntable-457ba39c.png',
  glow: '/assets/png/gold-5370691e.png',
  center: '/assets/png/start_btn-b559d1bd.png',
  pointer: '/assets/png/select-b04ff1dd.png',
  money: '/assets/png/money2-af0db0eb.png',
}

function loadImage(src) {
  return new Promise((resolve, reject) => {
    const img = new Image()
    img.onload = () => resolve(img)
    img.onerror = () => reject(new Error(`Image load failed: ${src}`))
    img.src = src
  })
}

async function initCanvas() {
  const canvas = canvasRef.value
  if (!canvas) return

  ctx = canvas.getContext('2d')
  if (!ctx) return

  try {
    const [wheel, glow, center, pointer, money] = await Promise.all([
      loadImage(assetPaths.wheel),
      loadImage(assetPaths.glow),
      loadImage(assetPaths.center),
      loadImage(assetPaths.pointer),
      loadImage(assetPaths.money),
    ])

    images.wheel = wheel
    images.glow = glow
    images.center = center
    images.pointer = pointer
    images.money = money

    drawWheel()
  } catch (error) {
    console.error(error)
  }
}

function drawPrizeItems(cx, cy) {
  if (!ctx || !images.money) return

  const slice = (Math.PI * 2) / prizes.length
  const radius = 175

  ctx.save()
  ctx.translate(cx, cy)
  ctx.rotate(currentAngle)

  for (let i = 0; i < prizes.length; i++) {
    const theta = i * slice - Math.PI / 2
    const x = Math.cos(theta) * radius
    const y = Math.sin(theta) * radius

    ctx.save()
    ctx.translate(x, y)

    ctx.rotate(-currentAngle)

    const iconSize = 52
    ctx.drawImage(images.money, -iconSize / 2, -24, iconSize, iconSize)

    ctx.fillStyle = '#f65b57'
    ctx.font = 'bold 28px Roboto, Inter, sans-serif'
    ctx.textAlign = 'center'
    ctx.textBaseline = 'top'
    ctx.fillText(prizes[i].label, 0, 34)

    ctx.restore()
  }

  ctx.restore()
}

function drawWheel() {
  const canvas = canvasRef.value
  if (!canvas || !ctx) return

  const cx = canvas.width / 2
  const cy = 390

  ctx.clearRect(0, 0, canvas.width, canvas.height)

  if (images.glow) {
    ctx.drawImage(images.glow, cx - 220, cy - 220, 440, 440)
  }

  if (images.wheel) {
    ctx.save()
    ctx.translate(cx, cy)
    ctx.rotate(currentAngle)
    ctx.drawImage(images.wheel, -310, -310, 620, 620)
    ctx.restore()
  }
  drawPrizeItems(cx, cy)

if (images.center) {
  ctx.drawImage(images.center, cx - 82, cy - 82, 164, 164)

  ctx.save()

  ctx.fillStyle = '#f25b4f'
  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'

  ctx.font = 'bold 56px Roboto, Inter, sans-serif'
  ctx.fillText('3X', cx, cy - 10)

  ctx.font = '20px Roboto, Inter, sans-serif'
  ctx.fillText('FREE SPIN', cx, cy + 28)

  ctx.restore()
}
  if (images.pointer) {
    ctx.drawImage(images.pointer, cx - 82, cy - 345, 164, 210)
  }
}

function easeOutCubic(t) {
  return 1 - Math.pow(1 - t, 3)
}

function normalizeAngle(angle) {
  const full = Math.PI * 2
  return ((angle % full) + full) % full
}

function startSpin() {
  if (spinning || !ctx) return

  spinning = true

  const targetIndex = Math.floor(Math.random() * prizes.length)
  const slice = (Math.PI * 2) / prizes.length

  const startAngle = currentAngle
  const currentNormalized = normalizeAngle(currentAngle)

  const finalNormalized = normalizeAngle(Math.PI * 2 - targetIndex * slice)

  let delta = finalNormalized - currentNormalized
  if (delta < 0) delta += Math.PI * 2

  const totalRotation = Math.PI * 2 * 6 + delta
  const duration = 4500
  const startTime = performance.now()

  if (rafId) cancelAnimationFrame(rafId)

  const animate = (now) => {
    const progress = Math.min((now - startTime) / duration, 1)
    const eased = easeOutCubic(progress)

    currentAngle = startAngle + totalRotation * eased
    drawWheel()

    if (progress < 1) {
      rafId = requestAnimationFrame(animate)
    } else {
      spinning = false
      rafId = null
      currentAngle = startAngle + totalRotation
      drawWheel()
    }
  }

  rafId = requestAnimationFrame(animate)
}

onMounted(() => {
  initCanvas()
})

onBeforeUnmount(() => {
  if (rafId) cancelAnimationFrame(rafId)
})
</script>

<template>
  <AuthLayout
    class="main"
    title="Invite Wheel"
    :showBack="true"
    :backUrl="route('user.home')"
  >
    <div
      class="ar_turntable_page"
      style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;"
    >
      <div class="my_account">my amount(71:59:24)</div>

      <div class="turntable_content">
        <span class="scroll_num">৳959.10</span>
        <div class="cash_btn">CASH OUT</div>

        <div class="turntable_all">
          <div id="turntable_canvas">
            <canvas
              ref="canvasRef"
              width="728"
              height="1000"
              @click="startSpin"
              style="width: 364px; height: 500px; touch-action: none; cursor: pointer; display: block;"
            ></canvas>
            
          </div>
          <div class="turn_bottom"></div>
        </div>

        <div class="turntable_record">
          <div class="invite_btn mx-auto">INVITE FRIENDS TO GET SPIN</div>
          <div class="tip">Only ৳40.90 left to get prize ৳1,000.00</div>

          <div class="record_list">
            <div class="title">Record</div>

            <div class="record_item flex flex-row flex-between">
              <div class="record_item_left flex">
                <AvatarImage />
                <span>{{ props.userName }}</span>
              </div>

              <div class="record_item_right flex flex-column">
                <div class="record_amount">৳959.10</div>
                <div class="time">2026-02-23 13:21:52</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="dialog inactive">
        <div class="dialog__container" role="dialog" tabindex="0">
          <div class="dialog__container-img">
            <img alt="" src="/assets/png/tip-0498e3f9.webp" />
          </div>

          <div class="dialog__container-title">
            <h1>Insufficient amount</h1>
          </div>

          <div class="dialog__container-content">
            <div class="amount_no_tip">You need ৳40.90 more to cash out</div>
          </div>

          <div class="dialog__container-footer">
            <button>INVITE FRIENDS TO GET SPIN</button>
            <button>OK</button>
          </div>
        </div>

        <div class="dialog__outside"></div>
      </div>
    </div>
  </AuthLayout>
</template>
<style scoped>
.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}@media (min-width: 501px){.popuer-gift{max-width:10rem;left:50%;-webkit-transform:translateX(-50%);transform:translate(-50%)}}.popuer-gift{position:fixed;top:0;left:50%;width:100%;height:100%;background:rgb(0,0,0);z-index:999;-webkit-transform:translateX(-50%);transform:translate(-50%)}.popuer-gift .icon_return_01{width:.64rem;height:.64rem;font-size:.64rem;position:absolute;top:2%;left:3%;color:#fff}.popuer-gift .gift_content{padding-top:5.06667rem;width:100%}.popuer-gift .gift_content .title{min-width:4.53333rem;text-align:center;margin-bottom:1.36rem}.popuer-gift .gift_content .title span{font-size:.58667rem;font-weight:700;background:-webkit-linear-gradient(top,#fffa7b 22.55%,#fffb5e 44.98%,#ffe770 59.26%,#ffb06a 73.53%);background:linear-gradient(180deg,#fffa7b 22.55%,#fffb5e 44.98%,#ffe770 59.26%,#ffb06a 73.53%);background-clip:text;-webkit-background-clip:text;-webkit-text-fill-color:transparent;position:relative}.popuer-gift .gift_content .title span:after{content:"";position:absolute;left:-1.25333rem;top:.13333rem;display:block;width:1.17333rem;height:.61333rem;background:url(/assets/png/icon_left-cd69f196.webp) no-repeat center center;background-size:1.17333rem .61333rem}.popuer-gift .gift_content .title span:before{content:"";position:absolute;right:-1.25333rem;top:.13333rem;display:block;width:1.17333rem;height:.61333rem;background:url(/assets/png/icon_right-e0e7c4d8.webp) no-repeat center center;background-size:1.17333rem .61333rem}.popuer-gift .gift_content .gift_list{padding:0 1.46667rem;display:grid;grid-template-columns:repeat(2,1fr);grid-gap:1.30667rem}.popuer-gift .gift_content .gift_list .gift_item{width:2.88rem;height:2.88rem;position:relative}.popuer-gift .gift_content .gift_list .gift_item .item_f{width:2.88rem;height:2.88rem;position:relative;z-index:1}.popuer-gift .gift_content .gift_list .gift_item .active_item{width:5.52rem;height:5.78667rem;margin:-1.33333rem 0 0 -1.33333rem;position:relative;z-index:1}.popuer-gift .gift_content .gift_list .gift_item .amount{position:absolute;z-index:2;left:.54667rem;top:2.88rem;width:2.4rem;height:.64rem;border-radius:.66667rem;border:.02667rem solid var(--main-color);background:rgb(63,8,4);text-align:center;font-size:.37333rem;font-weight:600;line-height:.64rem;margin:-.24rem auto 0}.popuer-gift .gift_content .gift_list .gift_item .amount span{background:-webkit-linear-gradient(top,#FFEB88,#FFB62F);background:linear-gradient(180deg,#FFEB88,#FFB62F);background-clip:text;-webkit-background-clip:text;-webkit-text-fill-color:transparent}.popuer-gift .gift_content .tip{text-align:center;font-size:.37333rem;font-weight:500;line-height:normal;padding-top:.8rem;color:#fff}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.scroll_num{font-size:.90667rem;font-weight:500;line-height:1.17333rem;background:-webkit-linear-gradient(top,#FFFA7B 22.55%,#FFFB5E 44.98%,#FFE770 59.26%,#FFB06A 73.53%);background:linear-gradient(180deg,#FFFA7B 22.55%,#FFFB5E 44.98%,#FFE770 59.26%,#FFB06A 73.53%);background-clip:text;-webkit-background-clip:text;-webkit-text-fill-color:transparent}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important} .van-dialog{width:8.29333rem;height:12.4rem;border-radius:.2rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;overflow:hidden} .van-dialog__content{position:relative;width:100%} .van-dialog__footer{z-index:100;position:fixed;bottom:.4rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-flex:1;-webkit-flex-grow:1;flex-grow:1;gap:.26667rem;width:90%} .van-dialog .van-button__text{color:#fff;width:80%;height:.93333rem;text-align:center;line-height:.93333rem;background:var(--main_gradient-color);border-radius:1.06667rem;z-index:100;font-weight:700;font-size:.42667rem;font-family:Inter;font-style:normal;letter-spacing:.06667rem}.header{width:100%;height:1.17333rem;line-height:1.17333rem;color:#fff;font-weight:700;font-size:.48rem;text-align:center;background:var(--main_gradient-color)}.content{height:9.33333rem;padding:.4rem .26667rem;overflow-y:auto}.content .duan p{font-size:.37333rem!important}
.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.withDialog .success_txt{width:5.33333rem;margin:0 auto;color:var(--text_color_L2, #848694);text-align:center;font-size:.37333rem;font-weight:400;line-height:.48rem}.success-tips{margin:-3.2rem auto 0}.success-tips .ar_icon{width:7.74667rem;height:3.72rem}.success-tips .ar_icon svg{width:100%;height:100%}.success-tips .text{font-size:.48rem}.foot_btn{width:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;grid-gap:.32rem}.foot_btn .btn{width:3.82667rem;height:1.17333rem;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center;font-size:.37333rem;line-height:1.17333rem;text-align:center}.foot_btn .subBtn2{border-radius:.66667rem;border:.02667rem solid var(--main-color, var(--main-color));color:var(--main-color)}.foot_btn .subBtn{border-radius:.66667rem;background:var(--main_gradient-color, linear-gradient(180deg, var(--main-color) 0%, var(--main-color) 100%));color:var(--text_color_L4, #FFF);font-size:.37333rem;font-weight:600}.cashOutDialog .dialog-content{padding:.32rem}.cash_balance{text-align:center;padding:.18667rem 0}.cash_balance .add{color:var(--text_color_L1, #383a4c);font-size:.42667rem;font-weight:400;line-height:.37333rem;margin-bottom:.32rem}.cash_balance .add span{color:var(--norm_orange_color, #f6af0b);font-size:.42667rem;font-weight:600;line-height:.37333rem}.cash_balance .play_code{color:var(--text_color_L2, #848694);font-size:.37333rem;font-weight:400;line-height:.48rem}
.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.ar_turntable_page{background-image:url(/assets/png/turntable_bg-1b521166.webp);background-size:100% 16.85333rem;background-repeat:no-repeat;background-position:center top;background-color:#bf222c;width:100%;min-height:100vh;position:relative}.ar_turntable_page .head-main{position:relative;background:var(--main_gradient-color)!important;color:var(--text_color_L4, #fff);z-index:2}.ar_turntable_page .right_btn{grid-gap:.32rem}.ar_turntable_page .right_btn .nva_help,.ar_turntable_page .right_btn .icon_history{width:.64rem;height:.64rem}.ar_turntable_page .my_account{text-align:center;padding-top:.42667rem;color:var(--text_color_L4, #fff);font-size:.37333rem;font-weight:500}.ar_turntable_page .turntable_content{position:relative;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center}.ar_turntable_page .turntable_content .scroll_num{display:inline-block;margin:0 auto}.ar_turntable_page .turntable_content .amount{position:absolute;top:.26667rem;left:50%;-webkit-transform:translateX(-50%);transform:translate(-50%);z-index:3;font-size:.90667rem;font-weight:500;line-height:1.17333rem;background:-webkit-linear-gradient(top,#fffa7b 22.55%,#fffb5e 44.98%,#ffe770 59.26%,#ffb06a 73.53%);background:linear-gradient(180deg,#fffa7b 22.55%,#fffb5e 44.98%,#ffe770 59.26%,#ffb06a 73.53%);background-clip:text;-webkit-background-clip:text;-webkit-text-fill-color:transparent}.ar_turntable_page .turntable_content .cash_btn{position:absolute;top:1.41333rem;left:50%;-webkit-transform:translateX(-50%);transform:translate(-50%);min-width:3.69333rem;height:1.01333rem;border-radius:.66667rem;background:#fd8b01;box-shadow:0 -.05333rem .4rem #fff9 inset;color:#fff;text-align:center;text-shadow:0 .02667rem .02667rem rgba(88,58,8,.5);font-size:.42667rem;font-weight:700;line-height:1.01333rem;z-index:4}.ar_turntable_page .turntable_content .turntable_all{width:100%;height:14.34667rem;margin-top:-1.6rem;margin-bottom:.50667rem;position:relative;z-index:3}.ar_turntable_page .turntable_content .turntable_all .turn_bottom{position:absolute;bottom:0;left:0;width:100%;height:3.72rem;background:url(/assets/png/turn_bottom-3ba54e70.webp) no-repeat center center;background-size:100% 3.72rem;z-index:2}.ar_turntable_page .turntable_content #turntable_canvas{width:8.8rem;height:12.08rem;position:relative;margin:0 auto;z-index:1}.ar_turntable_page .turntable_record{width:100%;padding-bottom:.26667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.ar_turntable_page .turntable_record .tip{width:100%;color:#fff;text-align:center;font-size:.32rem;font-weight:500;line-height:.42667rem;margin-bottom:.48rem;padding:0 .4rem}.ar_turntable_page .turntable_record .record_list{width:100%;padding:0 .4rem 1.33333rem}.ar_turntable_page .turntable_record .record_list .title{color:#fff;font-size:.42667rem;font-style:normal;font-weight:700;line-height:.42667rem}.ar_turntable_page .turntable_record .record_list .record_item{height:1.36rem;padding:.32rem 0;border-bottom:.01333rem solid #e43f49;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.ar_turntable_page .turntable_record .record_list .record_item .record_item_left{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.ar_turntable_page .turntable_record .record_list .record_item .record_item_left img{width:.85333rem;height:.85333rem;margin-right:.16rem;border-radius:50%}.ar_turntable_page .turntable_record .record_list .record_item .record_item_left span{color:#fff;font-size:.34667rem;font-weight:400;line-height:.42667rem}.ar_turntable_page .turntable_record .record_list .record_item .record_item_right .record_amount{color:#ffdc2f;text-align:right;font-size:.34667rem;font-weight:500}.ar_turntable_page .turntable_record .record_list .record_item .record_item_right .time{color:#fffc;text-align:right;font-size:.32rem;font-weight:400}.ar_turntable_page .turntable_record .record_list .record_item:last-child{border-bottom:none}.ar_turntable_page .invite_btn{display:inline-block;color:#fff;text-align:center;text-shadow:0 .02667rem .02667rem rgba(88,58,8,.5);font-size:.37333rem;font-weight:500;line-height:1.01333rem;border-radius:.66667rem;background:#fd8b01;box-shadow:0 -.05333rem .4rem #fff9 inset;height:1.01333rem;padding:0 .16rem;margin:0 auto .32rem}.ar_turntable_page .amount_no_tip{padding:.66667rem 0;color:var(--text_color_L1, #383a4c);text-align:center;font-size:.37333rem;font-weight:400;line-height:.48rem}.nav_bar_head{width:100%;height:1.17333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;background:var(--main_gradient-color);padding:0 .4rem;position:relative}.nav_bar_head .left_icon{font-size:.64rem;color:var(--text_color_L4, #fff)}.nav_bar_head .title{color:var(--text_color_L4, #FFF);text-align:center;font-size:.48rem;font-weight:600;display:inline-block;position:absolute;left:50%;-webkit-transform:translateX(-50%);transform:translate(-50%)}.nav_bar_head .rithe_icon{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.nav_bar_head .rithe_icon svg{width:.64rem;height:.64rem;margin-right:.32rem}.nav_bar_head .rithe_icon svg:last-child{margin-right:0}
.ar_turntable_page .turntable_content #turntable_canvas{
        width: 10.1rem; margin: 3.1rem auto;
}
</style>