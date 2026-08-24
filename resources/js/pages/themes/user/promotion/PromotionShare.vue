<script setup>
import { computed, onMounted, onBeforeUnmount, ref } from 'vue'
import PageLayout from '../../layouts/PageLayout.vue'
import CopyValue from '../../partials/CopyValue.vue'

const props = defineProps({
  invitationLink: {
    type: String,
    required: true,
  },
  referralCode: {
    type: String,
    required: true,
  },
  qrImage: {
    type: String,
    required: true,
  },
  posters: {
    type: Array,
    default: () => [],
  },
})

const sliderRef = ref(null)
const activeIndex = ref(0)

const posterCount = computed(() => props.posters.length || 0)

const updateActiveSlide = () => {
  const slider = sliderRef.value
  if (!slider) return

  const cardWidth = slider.clientWidth * 0.82 + 20
  const index = Math.round(slider.scrollLeft / cardWidth)
  activeIndex.value = Math.max(0, Math.min(index, posterCount.value - 1))
}

const loadImage = (src) =>
  new Promise((resolve, reject) => {
    const img = new Image()
    img.crossOrigin = 'anonymous'
    img.onload = () => resolve(img)
    img.onerror = reject
    img.src = src
  })

const downloadPoster = async () => {
  try {
    const canvas = document.createElement('canvas')
    const width = 780
    const height = 1340

    canvas.width = width
    canvas.height = height

    const ctx = canvas.getContext('2d')
    if (!ctx) return

    const [
      bgImage,
      bankIcon,
      truckIcon,
      qrCodeImage,
    ] = await Promise.all([
      loadImage('/assets/png/poster-b3c0368e.webp'),
      loadImage('/assets/png/bank-1227ae77.webp'),
      loadImage('/assets/png/trucktick-4f43261f.webp'),
      loadImage(props.qrImage),
    ])

    ctx.drawImage(bgImage, 0, 0, width, height)

    const drawRoundedRect = (x, y, w, h, r, fillStyle, strokeStyle = null, lineWidth = 0) => {
      ctx.beginPath()
      ctx.moveTo(x + r, y)
      ctx.lineTo(x + w - r, y)
      ctx.quadraticCurveTo(x + w, y, x + w, y + r)
      ctx.lineTo(x + w, y + h - r)
      ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h)
      ctx.lineTo(x + r, y + h)
      ctx.quadraticCurveTo(x, y + h, x, y + h - r)
      ctx.lineTo(x, y + r)
      ctx.quadraticCurveTo(x, y, x + r, y)
      ctx.closePath()

      if (fillStyle) {
        ctx.fillStyle = fillStyle
        ctx.fill()
      }

      if (strokeStyle) {
        ctx.lineWidth = lineWidth
        ctx.strokeStyle = strokeStyle
        ctx.stroke()
      }
    }

    const drawCenteredText = (text, x, y, maxWidth, options = {}) => {
      const {
        color = '#ffffff',
        font = '700 28px Roboto',
        shadow = null,
        lineHeight = 34,
      } = options

      ctx.save()
      ctx.fillStyle = color
      ctx.font = font
      ctx.textAlign = 'center'
      ctx.textBaseline = 'middle'

      if (shadow) {
        ctx.shadowColor = shadow.color
        ctx.shadowBlur = shadow.blur || 0
        ctx.shadowOffsetX = shadow.offsetX || 0
        ctx.shadowOffsetY = shadow.offsetY || 0
      }

      const words = String(text).split(' ')
      const lines = []
      let line = ''

      for (const word of words) {
        const testLine = line ? `${line} ${word}` : word
        if (ctx.measureText(testLine).width > maxWidth && line) {
          lines.push(line)
          line = word
        } else {
          line = testLine
        }
      }

      if (line) lines.push(line)

      const totalHeight = (lines.length - 1) * lineHeight
      const startY = y - totalHeight / 2

      lines.forEach((item, index) => {
        ctx.fillText(item, x, startY + index * lineHeight)
      })

      ctx.restore()
    }

    const drawBoxWithIconAndText = (x, y, w, h, icon, text) => {
      drawRoundedRect(x, y, w, h, 14, 'rgba(255,255,255,0.02)', '#fff4c6', 2)

      ctx.drawImage(icon, x + w / 2 - 32, y + 28, 64, 64)

      drawCenteredText(text, x + w / 2, y + 136, w - 20, {
        color: '#ffffff',
        font: '700 24px Roboto',
        lineHeight: 28,
      })
    }

    drawRoundedRect(60, 95, 310, 120, 26, '#f2d57a')
    drawRoundedRect(390, 95, 310, 120, 26, '#f2d57a')

    drawCenteredText('Fair and justice', 215, 155, 260, {
      color: '#f24544',
      font: '700 28px Inter',
      lineHeight: 32,
    })

    drawCenteredText('Open and transparent', 545, 150, 240, {
      color: '#f24544',
      font: '700 28px Inter',
      lineHeight: 32,
    })

    drawCenteredText('Full Odds Bonus Rate', width / 2, 265, 650, {
      color: '#ffffff',
      font: '900 44px Roboto',
      shadow: {
        color: '#0C5895',
        blur: 6,
        offsetX: 0,
        offsetY: 6,
      },
      lineHeight: 48,
    })

    drawBoxWithIconAndText(90, 335, 270, 185, bankIcon, 'Financial security')
    drawBoxWithIconAndText(420, 335, 270, 185, truckIcon, 'Quick withdrawal')

    drawCenteredText('Permanent commission up to', width / 2, 590, 640, {
      color: '#ffffff',
      font: '700 36px Roboto',
      lineHeight: 40,
    })

    drawCenteredText('85%', width / 2, 655, 200, {
      color: '#ffe46c',
      font: '700 50px Roboto',
      lineHeight: 54,
    })

    ctx.drawImage(qrCodeImage, width / 2 - 125, 1060, 250, 250)

    const link = document.createElement('a')
    link.href = canvas.toDataURL('image/png')
    link.download = `invite-poster-${activeIndex.value + 1}-${props.referralCode}.png`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error('Poster download failed:', error)
  }
}

const scrollPrev = () => {
  const slider = sliderRef.value
  if (!slider) return
  slider.scrollBy({ left: -slider.clientWidth * 0.82, behavior: 'smooth' })
}

const scrollNext = () => {
  const slider = sliderRef.value
  if (!slider) return
  slider.scrollBy({ left: slider.clientWidth * 0.82, behavior: 'smooth' })
}

onMounted(() => {
  const slider = sliderRef.value
  if (slider) {
    slider.addEventListener('scroll', updateActiveSlide, { passive: true })
  }
})

onBeforeUnmount(() => {
  const slider = sliderRef.value
  if (slider) {
    slider.removeEventListener('scroll', updateActiveSlide)
  }
})
</script>

<template>
  <PageLayout title="Invite" :showBack="true" :backUrl="route('user.account')">
    <div
      class="promotionShare__container"
      style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;"
    >
      <div class="promotionShare__container-tips">
        <p>Please swipe left - right to choose your favorite poster</p>
      </div>

      <div class="promotion-slider-wrap">
        <button
          v-if="posterCount > 1"
          type="button"
          class="promotion-nav promotion-nav-left"
          @click="scrollPrev"
        >
          ‹
        </button>

        <div ref="sliderRef" class="promotion-slider">
          <div
            v-for="(poster, index) in posters"
            :key="poster.id"
            class="promotion-slide"
          >
            <div class="promotionShare__container-swiper" :id="poster.id">
              <div class="sContent">
                <div class="head1">
                  <div>Fair and justice</div>
                  <div>Open and transparent</div>
                </div>

                <div class="head2">Full Odds <span>Bonus</span> Rate</div>

                <div class="head3">
                  <div>
                    <img class="logo" src="/assets/png/bank-1227ae77.webp" alt="">
                    Financial security
                  </div>
                  <div>
                    <img class="logo" src="/assets/png/trucktick-4f43261f.webp" alt="">
                    Quick withdrawal
                  </div>
                </div>

                <div class="head4">Permanent commission up to <span>85%</span></div>
              </div>

              <img
                :src="qrImage"
                alt="QR Code"
                class="promotion-qr-image"
                width="164"
                height="164"
              >
            </div>
          </div>
        </div>

        <button
          v-if="posterCount > 1"
          type="button"
          class="promotion-nav promotion-nav-right"
          @click="scrollNext"
        >
          ›
        </button>
      </div>

      <div v-if="posterCount > 1" class="promotion-dots">
        <span
          v-for="(_, index) in posters"
          :key="index"
          class="promotion-dot"
          :class="{ active: activeIndex === index }"
        ></span>
      </div>

      <div class="promotionShare__container-slogan">
        <p>Invite friends</p>
        <p>Income <span>10 billion</span> Commission</p>
      </div>

      <div class="promotionShare__container-buttons">
        <div class="share" @click="downloadPoster">
          Download Poster
        </div>

        <div class="cpy">
          <CopyValue
            :value="invitationLink"
            icon="icon-copy"
            successMessage="Invitation Link copied."
          >
            Copy invitation link
          </CopyValue>
        </div>
      </div>
    </div>
  </PageLayout>
</template>

<style scoped>
span.copy-value-text {
    font-size: 11px;
}
.promotion-slider-wrap {position: relative;}
.promotion-slider {display: flex;gap: 20px;overflow-x: auto;scroll-snap-type: x mandatory;padding: 0 16px;-ms-overflow-style: none;scrollbar-width: none;}
.promotion-slider::-webkit-scrollbar {display: none;}
.promotion-slide {flex: 0 0 82%;scroll-snap-align: center;}
.promotion-qr-image {height: 164px;padding-bottom: 10px;width: 164px;display: flex;object-fit: contain;margin: auto;}
.promotion-nav {position: absolute;top: 50%;transform: translateY(-50%);z-index: 3;width: 32px;height: 32px;border: 0;border-radius: 50%;cursor: pointer;background: rgba(0, 0, 0, 0.45);color: #fff;}
.promotion-nav-left {left: 4px;}
.promotion-nav-right {right: 4px;}
.promotion-dots {display: flex;justify-content: center;gap: 8px;margin-top: 12px;}
.promotion-dot {width: 8px;height: 8px;border-radius: 50%;background: #d0d0d0;}
.promotion-dot.active {background: #4f5d75;}
.promotionShare__container-buttons {display: flex;gap: 12px;}
.share {flex: 1;display: flex;align-items: center;justify-content: center;cursor: pointer;}
.promotionShare__container-buttons :deep(.copy-value),
.promotionShare__container-buttons :deep(.cpy) {flex: 1;}
.invite-code-box,
.invite-link-box {margin-top: 12px;padding: 10px 12px;border-radius: 12px;background: rgba(255, 255, 255, 0.12);word-break: break-all;}
.invite-label {font-size: 12px;margin-bottom: 4px;opacity: 0.9;}
.invite-value {font-size: 13px;line-height: 1.4;}
span.copy-value-text {font-size: 11px !important;}
.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.promotionShare__container{padding-block:0 1.49333rem}.promotionShare__container .van-nav-bar{background-color:#f6f6f6}.promotionShare__container .van-nav-bar .van-nav-bar__content .van-nav-bar__left .van-icon,.promotionShare__container .van-nav-bar .van-nav-bar__content .van-nav-bar__title{color:var(--text_color_L1)}.promotionShare__container-tips{margin-block:.53333rem;color:var(--text_color_L2);font-size:.37333rem;text-align:center}.promotionShare__container .swiper{width:100%}.promotionShare__container .swiper-wrapper .swiper-slide{width:6.93333rem;height:11.46667rem;text-align:center}.promotionShare__container-slogan{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;margin-block:1.06667rem .98667rem;font-size:.42667rem}.promotionShare__container-slogan p{color:var(--text_color_L1)}.promotionShare__container-slogan p:first-of-type{margin-right:.72rem}.promotionShare__container-slogan p span{color:var(--norm_red-color);font-weight:700}.promotionShare__container-buttons{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;gap:.37333rem}.promotionShare__container-buttons div{width:8.8rem;height:.93333rem;color:var(--text_color_L4);font-size:.4rem;text-align:center;line-height:.93333rem;border-radius:9rem}.promotionShare__container-buttons div.share{background:var(--main_gradient-color)}.promotionShare__container-buttons div.cpy{color:var(--main-color);border:.01333rem solid var(--main-color);display:flex;align-items:center;justify-content:center}.promotionShare__container-swiper{position:relative;background:url(/assets/png/poster-b3c0368e.webp) no-repeat center;background-size:100% 100%;height:100%}.promotionShare__container-swiper .sContent{padding:.53333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column}.promotionShare__container-swiper .sContent .logo{width:-webkit-fit-content;width:fit-content;height:.50667rem}.promotionShare__container-swiper .sContent .head1{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-justify-content:space-around;justify-content:space-around;-webkit-box-align:stretch;-webkit-align-items:stretch;align-items:stretch;margin-top:.29333rem;height:-webkit-fit-content;height:fit-content}.promotionShare__container-swiper .sContent .head1>div{width:50%;background:url(/assets/png/bg1-d6d02e9f.webp) no-repeat center;background-size:100% 100%;font-family:Inter;font-style:normal;font-weight:700;font-size:.34667rem;line-height:.41333rem;color:#f24544;padding:.08rem .13333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center}.promotionShare__container-swiper .sContent .head2{color:#fff;text-shadow:0 .02667rem 0 #4EA9FF,0 .08rem .13333rem #0C5895;font-family:Roboto;font-size:.53333rem;font-style:normal;font-weight:900;margin-top:.16rem}.promotionShare__container-swiper .sContent .head2>span{color:red!important}.promotionShare__container-swiper .sContent .head3{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-justify-content:space-around;justify-content:space-around;margin-top:.26667rem}.promotionShare__container-swiper .sContent .head3>div{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;line-height:.42667rem;border:.01333rem solid #fff4c6;border-radius:.13333rem;min-width:2.4rem;min-height:1.6rem;gap:.08rem;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;color:#fff;font-family:Roboto;font-size:.29333rem}.promotionShare__container-swiper .sContent .head3>div>img{width:.8rem;height:.8rem}.promotionShare__container-swiper .sContent .head4{color:#fff;font-family:Roboto;font-style:normal;font-weight:700;font-size:.42667rem;line-height:.53333rem;margin-top:.26667rem}.promotionShare__container-swiper .sContent .head4 span{letter-spacing:.01333rem;color:#ffe46c}.promotionShare__container-swiper canvas{position:absolute;bottom:.2rem;left:50%;margin-left:-1.2rem;z-index:22;width:2.42667rem!important;height:2.42667rem!important}
</style>