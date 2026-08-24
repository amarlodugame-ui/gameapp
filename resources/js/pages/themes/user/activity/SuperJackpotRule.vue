<script setup>
import PageLayout from '../../layouts/PageLayout.vue'
import '@/css/activity/page-main-SuperJackpot.css'

</script>

<template>
<PageLayout title="Rule" :showBack="true" :backUrl="route('user.activity.main.super-jackpot')">

        <div  class="jackpot-rule"
            style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
            <div  class="rule-bannerMain">
                <div  class="rule-content">
                    <div  class="rule-content-top">
                        <div  class="rule-content-top-right">
                            <h3 >Super Jackpot</h3>
                            <p >When you win the Super Jackpot in the game, you can get additional
                                platform bonuses, and the bonuses will be distributed to you according to the multiple
                                of the winning prize</p>
                        </div>
                    </div>
                    <div  class="rule-content-tip"><svg 
                            class="svg-icon icon-warningTriangle">
                            <use xlink:href="#icon-warningTriangle"></use>
                        </svg>
                        <p >Warning: Please claim all bonuses before the event ends, after the event
                            ends, you will lose the chance to get the bonus</p>
                    </div>
                </div>
            </div>
            <div  class="jackpot-rule-wrap">
                <div  class="title"><svg  class="svg-icon icon-superJackpotRule">
                        <use xlink:href="#icon-superJackpotRule"></use>
                    </svg> Bonus</div>
                <div  class="table-container">
                    <div  class="table-title">
                        <div >Winning rate</div>
                        <div >Bet amount</div>
                        <div >Bonus</div>
                    </div>
                    <div  class="table-content">
                        <div >100X-1999X</div>
                        <div >৳50-৳8000</div>
                        <div >৳3,000.00</div>
                    </div>
                    <div  class="table-content">
                        <div >50X-99X</div>
                        <div >৳50-৳8000</div>
                        <div >৳500.00</div>
                    </div>
                    <div  class="table-content">
                        <div >40X-49X</div>
                        <div >৳50-৳8000</div>
                        <div >৳300.00</div>
                    </div>
                    <div  class="table-content">
                        <div >30X-39X</div>
                        <div >৳50-৳8000</div>
                        <div >৳200.00</div>
                    </div>
                    <div  class="table-content">
                        <div >20X-29X</div>
                        <div >৳50-৳8000</div>
                        <div >৳100.00</div>
                    </div>
                </div>
                <div  class="jackpot-rule-owener"><svg 
                        class="svg-icon icon-rightTriangle">
                        <use xlink:href="#icon-rightTriangle"></use>
                    </svg> All event interpretation rights belong to the platform. If you have any questions, please
                    contact customer service now</div>
                <div  class="jackpot-rule-custom"><svg 
                        class="svg-icon icon-customerPublic">
                        <use xlink:href="#icon-customerPublic"></use>
                    </svg> Contact customer service</div>
            </div>
        </div>
</PageLayout>
</template>