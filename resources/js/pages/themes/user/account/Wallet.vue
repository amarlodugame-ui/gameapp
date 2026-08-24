<script setup>
import { computed } from 'vue';
import { router } from '@/user/inertia';
import AuthLayout from '../../layouts/AuthLayout.vue';
import '@/css/activity/page-wallet-indexvue_vue_type_style_index_0_scoped.css';
import BottomTabbar from '../../partials/BottomTabbar.vue';

const props = defineProps({
    wallet: {
        type: Object,
        default: () => ({
            total_balance: 0,
            total_withdraw: 0,
            total_deposit: 0,
            main_wallet: 0,
            third_party_wallet: 0,
            main_wallet_percent: 0,
            third_party_wallet_percent: 0,
            ar_game: 0,
            lottery: 0,
        }),
    },
});

const wallet = computed(() => props.wallet || {});

const formatMoney = (amount) => {
    const number = Number(amount || 0);
    return `৳${number.toFixed(2)}`;
};

const formatPlain = (amount) => {
    const number = Number(amount || 0);
    return number.toFixed(2);
};

const mainPercent = computed(() => Number(wallet.value.main_wallet_percent || 0));
const thirdPercent = computed(() => Number(wallet.value.third_party_wallet_percent || 0));

const mainDashArray = computed(() => {
    const circumference = 3140;
    const progress = Math.round((mainPercent.value / 100) * circumference);
    return `${progress}px, ${circumference}px`;
});

const thirdDashArray = computed(() => {
    const circumference = 3140;
    const progress = Math.round((thirdPercent.value / 100) * circumference);
    return `${progress}px, ${circumference}px`;
});

const visitPage = (routeName) => {
    router.visit(route(routeName));
};
</script>

<template>
    <AuthLayout class="main" title="Wallet" :showBack="true" :backUrl="route('user.account')">
        <div class="wallet-container" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
            <div class="wallet-container-header">
                <div class="wallet-container-header-belly">
                    <svg class="svg-icon icon-wallet1">
                        <use xlink:href="#icon-wallet1"></use>
                    </svg>

                    <div>{{ formatMoney(wallet.total_balance) }}</div>
                    <span>Total balance</span>

                    <div>
                        <div>
                            <p class="total">{{ formatPlain(wallet.total_withdraw) }}</p>
                            <p>Total amount</p>
                        </div>
                        <div>
                            <p class="total">{{ formatPlain(wallet.total_deposit) }}</p>
                            <p>Total deposit amount</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="wallet-container-content">
                <div class="container">
                    <div class="progressBars">
                        <div class="progressBarsL">
                            <div class="van-circle">
                                <svg viewBox="0 0 1100 1100">
                                    <path
                                        class="van-circle__layer"
                                        d="M 550 550 m 0, -500 a 500, 500 0 1, 1 0, 1000 a 500, 500 0 1, 1 0, -1000"
                                        style="fill: none; stroke-width: 100px;"
                                    />
                                    <path
                                        d="M 550 550 m 0, -500 a 500, 500 0 1, 1 0, 1000 a 500, 500 0 1, 1 0, -1000"
                                        class="van-circle__hover"
                                        :style="{
                                            strokeWidth: '101px',
                                            strokeLinecap: 'butt',
                                            strokeDasharray: mainDashArray
                                        }"
                                    />
                                </svg>
                                <div class="van-circle__text">{{ mainPercent }}%</div>
                            </div>
                            <h3>{{ formatMoney(wallet.main_wallet) }}</h3>
                            <span>Main wallet</span>
                        </div>

                        <div class="progressBarsR">
                            <div class="van-circle">
                                <svg viewBox="0 0 1100 1100">
                                    <path
                                        class="van-circle__layer"
                                        d="M 550 550 m 0, -500 a 500, 500 0 1, 1 0, 1000 a 500, 500 0 1, 1 0, -1000"
                                        style="fill: none; stroke-width: 100px;"
                                    />
                                    <path
                                        d="M 550 550 m 0, -500 a 500, 500 0 1, 1 0, 1000 a 500, 500 0 1, 1 0, -1000"
                                        class="van-circle__hover"
                                        :style="{
                                            strokeWidth: '101px',
                                            strokeLinecap: 'butt',
                                            strokeDasharray: thirdDashArray
                                        }"
                                    />
                                </svg>
                                <div class="van-circle__text">{{ thirdPercent }}%</div>
                            </div>
                            <h3>{{ formatMoney(wallet.third_party_wallet) }}</h3>
                            <span>3rd party wallet</span>
                        </div>
                    </div>

                    <div class="recycleBtnD">
                        <button class="recycleBtn" type="button">
                            Main wallet transfer
                        </button>
                    </div>

                    <div class="userDetail">
                        <div @click="visitPage('user.wallet.recharge')">
                            <div class="imgD">
                                <img src="/assets/png/rechargeIcon-e515aee4.webp" alt="Deposit">
                            </div>
                            <span>Deposit</span>
                        </div>

                        <div @click="visitPage('user.wallet.withdraw')">
                            <div class="imgD">
                                <img src="/assets/png/widthdrawBlue-80197e64.webp" alt="Withdraw">
                            </div>
                            <span>Withdraw</span>
                        </div>

                        <div @click="visitPage('user.wallet.RechargeHistory')">
                            <div class="imgD">
                                <img src="/assets/png/rechargeHistory-b5a853c0.webp" alt="Deposit history">
                            </div>
                            <span>Deposit history</span>
                        </div>

                        <div @click="visitPage('user.wallet.WithdrawHistory')">
                            <div class="imgD">
                                <img src="/assets/png/withdrawHistory-fb2bafcf.webp" alt="Withdrawal history">
                            </div>
                            <span>Withdrawal history</span>
                        </div>
                    </div>
                </div>

                <div class="gameList">
                    <div class="box">
                        <svg class="svg-icon icon-ARGame">
                            <use xlink:href="#icon-ARGame"></use>
                        </svg>
                        <h3 class="money">{{ formatPlain(wallet.ar_game) }}</h3>
                        <span>ARGame</span>
                    </div>

                    <div class="box">
                        <svg class="svg-icon icon-Lottery">
                            <use xlink:href="#icon-Lottery"></use>
                        </svg>
                        <h3 class="money">{{ formatPlain(wallet.lottery) }}</h3>
                        <span>Lottery</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- <BottomTabbar /> -->
    </AuthLayout>
</template>

<style scoped>
.userDetail > div {
    cursor: pointer;
}
</style>