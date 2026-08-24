<script setup>
import PageLayout from '../../layouts/PageLayout.vue'
import '@/css/accoutn/index-18dce474.css'
import { computed } from 'vue'
import { router } from '@/user/inertia'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({ range: 'today' }),
    },
    tabs: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({ total_bet: 0 }),
    },
    items: {
        type: Array,
        default: () => [],
    },
})

const activeRange = computed(() => props.filters?.range || 'today')

const formatMoney = (value) => {
    return `৳${Number(value || 0).toFixed(2)}`
}

const changeRange = (range) => {
    router.get(route('user.account.game-stats'), { range }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    })
}
</script>

<template>
<PageLayout title="Game Statistics" :showBack="true" :backUrl="route('user.account')">
    <div class="gamestats-container" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
        <div class="gamestats-container-header">
            <div class="van-tabs van-tabs--card">
                <div class="van-tabs__wrap">
                    <div role="tablist" class="van-tabs__nav van-tabs__nav--card" aria-orientation="horizontal">
                        <div
                            v-for="tab in tabs"
                            :key="tab.key"
                            role="tab"
                            class="van-tab van-tab--card"
                            :class="{ 'van-tab--active': activeRange === tab.key }"
                            tabindex="0"
                            :aria-selected="activeRange === tab.key"
                            @click="changeRange(tab.key)"
                        >
                            <span class="van-tab__text van-tab__text--ellipsis">{{ tab.label }}</span>
                        </div>
                    </div>
                </div>
                <div class="van-tabs__content"></div>
            </div>
        </div>

        <div class="gamestats-container-banner">
            <h1>{{ formatMoney(summary.total_bet) }}</h1>
            <span>Total bet</span>
        </div>

        <div class="gamestats-container-list-wrapper">
            <div class="gamestats-container-items">
                <div v-for="item in items" :key="item.key" class="gamestats-container-item">
                    <h1>
                        <img :src="item.icon" :data-origin="item.icon" alt="">
                        <span>{{ item.label }}</span>
                    </h1>

                    <div class="gamestats-container-item-content">
                        <img
                            alt=""
                            class=""
                            data-origin="/assets/png/gameStatsSteps-fde7a66f.webp"
                            src="/assets/png/gameStatsSteps-fde7a66f.webp"
                        >

                        <div class="gamestats-container-item-content-list">
                            <div>
                                <h4>Total bet</h4>
                                <h5>{{ formatMoney(item.total_bet) }}</h5>
                            </div>
                            <div>
                                <h4>Number of bets</h4>
                                <h5>{{ item.number_of_bets }}</h5>
                            </div>
                            <div>
                                <h4>Winning amount</h4>
                                <span>{{ formatMoney(item.winning_amount) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</PageLayout>
</template>