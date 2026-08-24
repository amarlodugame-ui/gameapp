<script setup>
import PageLayout from '../../layouts/PageLayout.vue'
import { router } from '@/user/inertia'

const props = defineProps({
    commissions: Array,
    totalDeposit: Number,
})

const claimBonus = (id) => {
    router.post(route('user.activity.firstRecharge.claim', id))
}

const money = (amount) => {
    return new Intl.NumberFormat('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount || 0)
}
</script>

<template>
<PageLayout title="First deposit bonus" :showBack="true" :backUrl="route('user.activity')">

    <div class="first_list" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
        <div class="first_list-item" v-for="item in commissions" :key="item.id">
            <div class="head">
                <div class="title">First deposit <span>{{ money(item.deposit_amount) }}</span></div>
                <div class="orange">+ ৳{{ money(item.bonus_amount) }}</div>
            </div>

            <div class="description">
                Deposit {{ money(item.deposit_amount) }} and you will receive {{ money(item.bonus_amount) }} bonus
            </div>

            <div class="foot">
                <div class="progress" style="background: var(--bg_color_L1); height: 16px; border-radius: 16px;">
                    <div
                        class="line"
                        :style="{
                            background: 'var(--norm_secondary-color)',
                            width: item.progress_percent + '%',
                            height: '16px',
                            borderRadius: '16px'
                        }"
                    ></div>
                    <div class="step">{{ money(item.progress_amount) }}/{{ money(item.deposit_amount) }}</div>
                </div>

                <button
                    v-if="item.can_claim"
                    class="btn n1"
                    type="button"
                    @click="claimBonus(item.id)"
                >
                    Receive
                </button>

                <button
                    v-else-if="item.claimed"
                    class="btn n3"
                    type="button"
                    disabled
                >
                    Claimed
                </button>

                <button
                    v-else
                    class="btn n2"
                    type="button"
                    @click="router.visit(route('user.wallet.recharge'))"
                >
                    Deposit
                </button>
            </div>
        </div>
    </div>

    <div class="rule ruleHead" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
        <svg class="svg-icon icon-ruleHead">
            <use xlink:href="#icon-ruleHead"></use>
        </svg>
        <div class="head">Activity Rules</div>
        <div>
            <div>
                Exclusive for the first recharge of the account. There is only one chance per slab.
                Only the highest currently eligible slab can be claimed.
            </div>
        </div>
        <div><div>Lower or higher slabs cannot be claimed manually unless they become the current eligible slab;</div></div>
        <div><div>Rewards can only be claimed manually on IOS, Android, H5, and PC;</div></div>
        <div>
            <div>
                The bonus (excluding the principal) given in this event requires 1 times the coding
                turnover (i.e. valid bets) before it can be withdrawn.
            </div>
        </div>
        <div>
            <div>
                This event is limited to normal human operations by the account owner.
            </div>
        </div>
        <div>
            <div>
                In order to avoid differences in text understanding, the platform reserves the right
                of final interpretation of this event.
            </div>
        </div>
    </div>

</PageLayout>
</template>

<style scoped>
.first_list-item .foot .btn.n1 {
    background: var(--main_gradient-color);
    border: none;
}

.rule {
    width: calc(100% - .64rem) !important;
}

.first_list-item {
    background: var(--bg_color_L2) !important;
}

.first_list-item .foot .btn.n3 {
    color: #000;
}
</style>