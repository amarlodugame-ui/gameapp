<script setup>
import PageLayout from '../../layouts/PageLayout.vue'
import { router, usePage } from '@/user/inertia'
import { computed } from 'vue'

const page = usePage()

const summary = computed(() => page.props.summary || {
    streak: 0,
    today_bet: 0,
    current_required: 0,
    cards: [],
    claimed_days: [],
})

const money = (amount) => {
    return `৳${Number(amount || 0).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`
}

const claimBonus = () => {
    router.post(route('user.main.daily-sign-in.claim'), {}, {
        preserveScroll: true,
    })
}
</script>

<template>
<PageLayout title="Attendance" :showBack="true" :backUrl="route('user.activity')">
    <div class="dailySignIn__container" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
        <div class="dailySignIn__container-hero">
            <div class="dailySignIn__container-hero__header">
                <h1>Attendance bonus</h1>
                <p>Get rewards based on consecutive bet days</p>
                <div>
                    Attended consecutively
                    <span>{{ summary.streak }}</span>
                    Day
                </div>
                <p>Today accumulated bet</p>
                <h1>{{ money(summary.today_bet) }}</h1>
            </div>

            <div class="dailySignIn__container-hero__footer">
                <button @click="router.visit(route('user.activity.main.daily-sign-in.rules'))">Game Rules</button>
                <button @click="router.visit(route('user.activity.main.daily-sign-in.record'))">Attendance history</button>
            </div>
        </div>

        <div class="dailySignIn__container-content">
            <div class="dailySignIn__container-content__wrapper">
<div
    v-for="card in summary.cards"
    :key="card.day_no"
    class="dailySignIn__container-content__wrapper-block signin-card"
    :class="[
        card.status === 'claimable' ? 'card-claimable' : '',
        card.status === 'claimed' ? 'card-claimed' : '',
        card.status === 'passed' ? 'card-passed' : '',
        card.status === 'locked' ? 'card-locked' : '',
    ]"
>
    <template v-if="card.day_no < 7">
        <div class="dailySignIn__container-content__wrapper-block__header">
            <img src="/assets/png/SignInTop-2fa51663.webp">
            <span>{{ money(card.reward) }}</span>
        </div>

        <img src="/assets/png/coin-294b6998.webp">
        <span>{{ card.day_no }} Day</span>
    </template>

    <template v-else>
        <span class="lastImage">
            <img src="/assets/png/day7Bg-c8619dfc.webp">
        </span>
        <div>
            <span>{{ money(card.reward) }}</span>
            <span>{{ card.day_no }} Day</span>
        </div>
    </template>
</div>
            </div>

            <div class="dailySignIn__container-content__footer">
                <button @click="claimBonus">Attendance</button>
            </div>
        </div>
    </div>
</PageLayout>
</template>
<style scoped>
.signin-card{
    border-radius: 14px;
    transition: .25s ease;
}

/* Ready */
.card-claimable{
    background: linear-gradient(180deg, #fff7d6 0%, #ffe58f 100%);
    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.25);
}

/* Claimed */
.card-claimed{
    background: linear-gradient(180deg, #dcfce7 0%, #86efac 100%);
    box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.18);
    opacity: .95;
}

/* Missed */
.card-passed{
    background: linear-gradient(180deg, #e5e7eb 0%, #cbd5e1 100%);
    box-shadow: 0 0 0 1px rgba(148, 163, 184, 0.18);
}

/* Locked */
/* .card-locked{
    background: linear-gradient(180deg, #eef2ff 0%, #dbeafe 100%);
    box-shadow: 0 0 0 1px rgba(96, 165, 250, 0.15);
} */
</style>