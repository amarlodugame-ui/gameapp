<script setup>
import PageLayout from '../../layouts/PageLayout.vue';
</script>
<template>
        <PageLayout title="Rules" :showBack="true" :backUrl="route('user.promotion')">

        <div  class="promotion-page"style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">

            <div  class="promotion-mian">
                <div  class="promotion-mian__title">
                    <h1 >【Promotion partner】program</h1>
                    <p >This activity is valid for a long time</p>
                </div>
                <div  class="promotion-box ruleHead"><svg 
                        class="svg-icon icon-ruleHead">
                        <use xlink:href="#icon-ruleHead"></use>
                    </svg>
                    <div  class="promotion-title">01</div>
                    <div  class="promotion-txt">
                        <p >There are 6 subordinate levels in inviting friends, if A invites B, then B
                            is a level 1 subordinate of A. If B invites C, then C is a level 1 subordinate of B and also
                            a level 2 subordinate of A. If C invites D, then D is a level 1 subordinate of C, at the
                            same time a level 2 subordinate of B and also a level 3 subordinate of A.</p>
                       
                    </div>
                </div>
                <div  class="promotion-box ruleHead"><svg 
                        class="svg-icon icon-ruleHead">
                        <use xlink:href="#icon-ruleHead"></use>
                    </svg>
                    <div  class="promotion-title">02</div>
                    <div  class="promotion-txt">
                        <p >When inviting friends to register, you must send the invitation link
                            provided or enter the invitation code manually so that your friends become your level 1
                            subordinates.</p>
                    </div>
                </div>
                <div  class="promotion-box ruleHead"><svg 
                        class="svg-icon icon-ruleHead">
                        <use xlink:href="#icon-ruleHead"></use>
                    </svg>
                    <div  class="promotion-title">03</div>
                    <div  class="promotion-txt">
                        <p >The invitee registers via the inviter's invitation code and completes the
                            deposit, shortly after that the commission will be received immediately</p>
                       
                    </div>
                </div>
                <div  class="promotion-box ruleHead"><svg 
                        class="svg-icon icon-ruleHead">
                        <use xlink:href="#icon-ruleHead"></use>
                    </svg>
                    <div  class="promotion-title">04</div>
                    <div  class="promotion-txt">
                        <p >The calculation of yesterday's commission starts every morning at 01:00.
                            After the commission calculation is completed, the commission is rewarded to the wallet and
                            can be viewed through the commission collection record.</p>
                    </div>
                </div>
                <div  class="promotion-box ruleHead"><svg 
                        class="svg-icon icon-ruleHead">
                        <use xlink:href="#icon-ruleHead"></use>
                    </svg>
                    <div  class="promotion-title">05</div>
                    <div  class="promotion-txt">
                        <p >Commission rates vary depending on your agency level on that day<br>Number
                            of Teams: How many downline deposits you have to date. <br>Team Deposits: The total number
                            of deposits made by your downline in one day. <br>Team Deposit: Your downline deposits
                            within one day.</p>
                    </div>
                </div>
                <div  class="promotion-grade">
                    <div  class="promotion-grade-th">
                        <div  class="item">Rebate level</div>
                        <div  class="item">Team Number</div>
                        <div  class="item">Team Betting</div>
                        <div  class="item">Team Deposit</div>
                    </div>
                    <div  class="promotion-grade-tr">
                        <div  class="item">
                            <div  class="icon-LV"><span  class="txt">L0</span></div>
                        </div>
                        <div  class="item">0</div>
                        <div  class="item">0</div>
                        <div  class="item">0</div>
                    </div>
                    <div  class="promotion-grade-tr">
                        <div  class="item">
                            <div  class="icon-LV"><span  class="txt">L1</span></div>
                        </div>
                        <div  class="item">10</div>
                        <div  class="item">500K</div>
                        <div  class="item">100K</div>
                    </div>
                    <div  class="promotion-grade-tr">
                        <div  class="item">
                            <div  class="icon-LV"><span  class="txt">L2</span></div>
                        </div>
                        <div  class="item">15</div>
                        <div  class="item">1,000K</div>
                        <div  class="item">200K</div>
                    </div>
                    <div  class="promotion-grade-tr">
                        <div  class="item">
                            <div  class="icon-LV"><span  class="txt">L3</span></div>
                        </div>
                        <div  class="item">30</div>
                        <div  class="item">2.50M</div>
                        <div  class="item">500K</div>
                    </div>
                    <div  class="promotion-grade-tr">
                        <div  class="item">
                            <div  class="icon-LV"><span  class="txt">L4</span></div>
                        </div>
                        <div  class="item">45</div>
                        <div  class="item">3.50M</div>
                        <div  class="item">700K</div>
                    </div>
                    <div  class="promotion-grade-tr">
                        <div  class="item">
                            <div  class="icon-LV"><span  class="txt">L5</span></div>
                        </div>
                        <div  class="item">50</div>
                        <div  class="item">5M</div>
                        <div  class="item">1,000K</div>
                    </div>
                    <div  class="promotion-grade-tr">
                        <div  class="item">
                            <div  class="icon-LV"><span  class="txt">L6</span></div>
                        </div>
                        <div  class="item">60</div>
                        <div  class="item">10M</div>
                        <div  class="item">2M</div>
                    </div>
                </div>
                <div  class="promotion-box ruleHead"><svg 
                        class="svg-icon icon-ruleHead">
                        <use xlink:href="#icon-ruleHead"></use>
                    </svg>
                    <div  class="promotion-title">06</div>
                    <div  class="promotion-txt">The commission percentage depends on the membership
                        level. The higher the membership level, the higher the bonus percentage. Different game types
                        also have different payout percentages. <p >The commission rate is
                            specifically explained as follows</p>
                        <div  class="txt">View rebate ratio &gt;&gt;</div>
                    </div>
                </div>
                <div  class="promotion-box ruleHead"><svg 
                        class="svg-icon icon-ruleHead">
                        <use xlink:href="#icon-ruleHead"></use>
                    </svg>
                    <div  class="promotion-title">07</div>
                    <div  class="promotion-txt">TOP20 commission rankings will be randomly awarded
                        with a separate bonus</div>
                </div>
                <div  class="promotion-box ruleHead"><svg 
                        class="svg-icon icon-ruleHead">
                        <use xlink:href="#icon-ruleHead"></use>
                    </svg>
                    <div  class="promotion-title">08</div>
                    <div  class="promotion-txt">The final interpretation of this activity belongs to
                        dkwin</div>
                </div>
            </div>
        </div>

    </PageLayout>   
</template>
<style scoped>
.item {min-height: auto;}
.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.icon-LV{height:.61333rem;width:1.33333rem;background:url(/assets/png/lv-450d4246.webp) no-repeat center center;background-size:cover;position:relative;text-align:center}.icon-LV .txt{display:block;position:absolute;right:0;bottom:0;height:.4rem;line-height:.42667rem;width:.69333rem;background:-webkit-linear-gradient(top,#fffba9 0%,#fff670 56.13%,#ffd180 100%);background:linear-gradient(180deg,#fffba9 0%,#fff670 56.13%,#ffd180 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;text-fill-color:transparent}.promotion-page{padding-inline:.34667rem;padding-block:0 1.49333rem;font-family:bahnschrift}.promotion-page .promotion-mian{padding-top:.4rem}.promotion-page .promotion-mian .promotion-mian__title{text-align:center;margin-bottom:.66667rem}.promotion-page .promotion-mian .promotion-mian__title h1{margin-bottom:.2rem;color:var(--main-color);font-size:.48rem;line-height:.48rem;font-weight:600}.promotion-page .promotion-mian .promotion-mian__title p{color:var(--text_color_L2);font-size:.37333rem}.promotion-page .promotion-mian .promotion-box{position:relative;padding:.57333rem .24rem .33333rem;border-radius:.21333rem;background:var(--bg_color_L2);margin-bottom:.66667rem}.promotion-page .promotion-mian .promotion-box .promotion-title{position:absolute;top:.13333rem;left:50%;-webkit-transform:translateX(-50%);transform:translate(-50%);width:2.13333rem;height:.53333rem;color:var(--text_color_L1);font-size:.32rem;text-align:center;line-height:.53333rem;z-index:1}.promotion-page .promotion-mian .promotion-box .promotion-txt{color:var(--text_color_L2);font-size:.32rem;letter-spacing:.01333rem;line-height:.50667rem;padding-top:.46667rem}.promotion-page .promotion-mian .promotion-box .promotion-txt .txt{color:var(--norm_red-color)}.promotion-page .promotion-mian .promotion-box:nth-of-type(6){margin-bottom:0;border-bottom:none}.promotion-page .promotion-mian .promotion-box:nth-of-type(6) .promotion-txt{padding-bottom:1.06667rem}.promotion-page .promotion-mian .promotion-grade{overflow:hidden;margin-bottom:.66667rem}.promotion-page .promotion-mian .promotion-grade-th{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;height:1.33333rem;color:#fff;font-size:.34667rem;line-height:.34667rem;background:var(--light-main-color, var(--sheet_nva_color));border-radius:.21333rem .21333rem 0 0;text-align:center}.promotion-page .promotion-mian .promotion-grade-th .item{-webkit-box-flex:1;-webkit-flex:1;flex:1}.promotion-page .promotion-mian .promotion-grade-tr{display:-webkit-box;display:-webkit-flex;display:flex;width:calc(100% + .01333rem);background-color:var(--bgDark-3, var(--bg_color_L2));margin:0 -.01333rem;color:var(--text_color_L2)}.promotion-page .promotion-mian .promotion-grade-tr:nth-child(odd){background:var(--sheet_detail_bg_color)}.promotion-page .promotion-mian .promotion-grade-tr .item{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;width:33.3%;height:.93333rem;padding:.06667rem 0;font-size:.34667rem;margin-bottom:-.01333rem;margin-right:-.01333rem}.promotion-page .promotion-mian .promotion-content{width:100%;margin-bottom:.53333rem;overflow:hidden}.promotion-page .promotion-mian .promotion-content_title{position:relative;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin-bottom:.28rem;font-size:.37333rem;font-weight:600}.promotion-page .promotion-mian .promotion-content_title:before{content:"";display:block;width:.08rem;height:.4rem;margin-right:.22667rem;background-color:var(--main-color)}
</style>