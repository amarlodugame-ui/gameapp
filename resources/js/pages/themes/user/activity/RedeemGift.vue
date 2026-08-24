<script setup>
import PageLayout from '../../layouts/PageLayout.vue'
import '@/css/activity/index-643f5f16.css'
import { useForm, usePage } from '@/user/inertia'
import { computed } from 'vue'

const page = usePage()
const histories = computed(() => page.props.histories || [])
const form = useForm({
    code: '',
})

const submitRedeem = () => {
    form.post(route('user.activity.main.redeem-gift.submit'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('code')
        },
    })
}
</script>

<template>
    <PageLayout title="Gift" :showBack="true" :backUrl="route('user.account')">
        <div class="redeem-container" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif">
            <div class="redeem-container-header">
                <div class="redeem-container-header-belly">
                    <img alt="" src="/assets/png/gift-a8f321e1.webp" />
                </div>
            </div>

            <div class="redeem-container-content">
                <div class="redeem-container-receive">
                    <p>Hi</p>
                    <p>We have a gift for you</p>
                    <h4>Please enter the gift code below</h4>

                    <form @submit.prevent="submitRedeem">
                        <input v-model="form.code" type="text" auto-complete="new-password" autocomplete="off" placeholder="Please enter gift code"/>
                        <button type="submit" :disabled="form.processing"> {{ form.processing ? 'Processing...' : 'Receive' }}</button>
                    </form>

                    <div v-if="form.errors.code" class="text-danger mt-2">
                        {{ form.errors.code }}
                    </div>
                </div>

                <div class="redeem-container-record">
                    <div class="redeem-container-record-title">
                        <svg class="svg-icon icon-giftHistory"> <use xlink:href="#icon-giftHistory"></use>
                        </svg>
                        <span>History</span>
                    </div>

                    <div class="redeem-container-record-itemsBox">
                        <div v-if="histories.length" class="infiniteScroll" id="gift-history-list">
                            <div class="redeem-container-record-items">
                                <div v-for="item in histories" :key="item.id" class="redeem-container-record-item ar-1px-b">
                                    <div class="redeem-container-record-item-left">
                                        <h5>Successfully received</h5>
                                        <span>{{ item.redeemed_at }}</span>
                                    </div>

                                    <div class="redeem-container-record-item-right">
                                        <img src="/assets/png/gold-b5be1e1b.png" />
                                      <span>{{ Math.floor(Number(item.reward_amount || 0)).toLocaleString('en-US') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="infiniteScroll__loading">
                                <div>No more</div>
                            </div>
                        </div>

                        <div v-else class="infiniteScroll" id="gift-history-empty">
                            <div class="infiniteScroll__loading">
                                <div class="empty__container empty">
                                    <svg class="svg-icon icon-empty">
                                        <use xlink:href="#icon-empty"></use>
                                    </svg>
                                    <p>No data</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PageLayout>
</template>

<style>
.redeem-container-header {
    height: 100%;
}
.text-danger {
    color: #ff4d4f;
    font-size: 13px;
}
</style>