<script setup>
import PageLayout from '../../layouts/PageLayout.vue'
import { usePage } from '@/user/inertia'
import { computed } from 'vue'
import '@/css/activity/index-643f5f16.css'
const page = usePage()
const records = computed(() => page.props.records || [])

const money = (amount) => {
    return `৳${Number(amount || 0).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`
}
</script>

<template>
<PageLayout title="Attendance history" :showBack="true" :backUrl="route('user.activity.main.daily-sign-in')">
    <div class="redeem-container-record-itemsBox" style="padding:16px;">
        <div v-if="records.length" class="redeem-container-record-items">
            <div
                v-for="item in records"
                :key="item.id"
                class="redeem-container-record-item ar-1px-b">
                <div class="redeem-container-record-item-left">
                    <h5>Day {{ item.day_no }} bonus claimed</h5>
                    <span>{{ item.claimed_at }}</span>
                    <!-- <small style="display:block;opacity:.7;">Bet: {{ money(item.bet_total) }}</small> -->
                </div>
                <div class="redeem-container-record-item-right">
                    <span>{{ money(item.reward_amount) }}</span>
                </div>
            </div>
        </div>

        <div v-else class="infiniteScroll__loading">
            <div class="empty__container empty">
                <p>No data</p>
            </div>
        </div>
    </div>
</PageLayout>
</template>
<style scoped>
.redeem-container-record-item.ar-1px-b{
    background: #fff;
    padding: 14px 14px;
}
</style>