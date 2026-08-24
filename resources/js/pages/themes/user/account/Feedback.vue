<script setup>
import PageLayout from '../../layouts/PageLayout.vue'
</script>

<template>
  <PageLayout title="Feedback" :showBack="true" :backUrl="route('user.account')">
    <div class="feedback-container" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
        <div class="feedback-container-content">
            <div class="feedback-container-content-text">
                <div class="feedback-container-content-text-wrapper"><textarea name="feedback" id="" cols="30" rows="10" placeholder="Welcome to feedback, please give feedback-please describe the problem in detail when providing feedback, preferably attach a screenshot of the problem you encountered, we will immediately process your feedback!"></textarea>
                </div>
            </div>
            <div class="feedback-container-content-describe">
                <h5>Send helpful feedback</h5>
                <h5>Chance to win Mystery Rewards</h5>
                <div><img src="/assets/png/feedbackImg-b7a3bd03.webp" alt="">
                </div>
            </div>
        </div>
        <div class="feedback-container-footer"><button>Submit</button></div>
    </div>
  </PageLayout>
</template>

<style scoped>
.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.feedback-container{margin-top:.32rem}.feedback-container-content{padding:0 .32rem}.feedback-container-content-text{height:9.13333rem;background:var(--bg_color_L2);border-radius:.26667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;padding:.53333rem}.feedback-container-content-text-wrapper{width:100%;height:5.33333rem}.feedback-container-content-text span{font-size:.32rem;color:var(--main-color)}.feedback-container-content-text textarea{border:none;font-size:.32rem;width:100%;height:100%;background:transparent;color:var(--text_color_L1)}.feedback-container-content-text textarea::-webkit-input-placeholder{color:var(--text_color_L3)}.feedback-container-content-text textarea::placeholder{color:var(--text_color_L3)}.feedback-container-content-uploadImg img{width:1.86667rem}.feedback-container-content-describe{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;padding:.97333rem 0 1.12rem}.feedback-container-content-describe h5{font-size:.37333rem;color:var(--darkTextW, var(--text_color_L1));margin-bottom:.2rem}.feedback-container-content-describe img{width:5.86667rem;margin-top:.49333rem}.feedback-container-footer{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;bottom:1.06667rem;width:100%;padding-bottom:.66667rem}.feedback-container-footer button{width:8.98667rem;height:1.06667rem;font-size:.4rem;background:var(--main_gradient-color);border-radius:1.06667rem;color:var(--text_color_L4);border:none}
</style>