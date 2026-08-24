<script setup>
import PageLayout from '../../layouts/PageLayout.vue'
import '@/css/accoutn/page-main-StrongBox.css'
import { usePage, router } from '@/user/inertia'
import { computed } from 'vue'

const page = usePage()

const summary = computed(() => page.props.summary || {})
const records = computed(() => page.props.records || [])

const formatBDT = (n) => `৳${Number(n || 0).toFixed(2)}`

const typeText = (item) => {
    if (item.type === 'in') return 'Transfer In'
    if (item.type === 'out' && item.actor === 'user') return 'User Transfer Out'
    if (item.type === 'out' && item.actor === 'admin') return 'Admin Transfer Out'
    if (item.type === 'interest') return 'Interest'
    return 'Record'
}

const actorText = (item) => {
    if (item.actor === 'user') return 'By user'
    if (item.actor === 'system') return 'By system'
    if (item.actor === 'admin') return 'By admin'
    return ''
}
</script>

<template>
    <PageLayout title="Safes history" :showBack="true" :backUrl="route('user.account.strong-box')">
        <div class="StrongBoxRecord__container" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif">
            <div class="StrongBoxRecord__container-overview">
                <div class="StrongBoxRecord__container-overview-detail">
                    <div>
                        <div>{{ formatBDT(summary.total_in || 0) }}</div>
                        <div>Transfer In</div>
                    </div>
                    <div>
                        <div>{{ formatBDT(summary.total_out || 0) }}</div>
                        <div>Transfer Out</div>
                    </div>
                    <div>
                        <div>{{ formatBDT(summary.total_interest || 0) }}</div>
                        <div>Income</div>
                    </div>
                </div>
            </div>

            <div class="StrongBoxRecord__container-detailList" :class="{ empty: !records.length }">
                <div v-if="records.length">
                    <div
                        v-for="item in records"
                        :key="item.id"
                        class="StrongBoxRecord__container-detailItem"
                    >
                        <div class="StrongBoxRecord__container-detailItem-left">
                            <div class="StrongBoxRecord__container-detailItem-title">
                                {{ typeText(item) }}
                            </div>
                            <div class="StrongBoxRecord__container-detailItem-time">
                                {{ item.created_at }}
                            </div>
                            <div class="StrongBoxRecord__container-detailItem-time">
                                {{ actorText(item) }}
                            </div>
                        </div>

                        <div class="StrongBoxRecord__container-detailItem-right">
                            <div
                                class="StrongBoxRecord__container-detailItem-amount"
                                :class="{
                                    green: item.type === 'interest' || item.type === 'in',
                                    red: item.type === 'out'
                                }"
                            >
                                <template v-if="item.type === 'out'">
                                    -{{ formatBDT(item.amount) }}
                                </template>
                                <template v-else>
                                    +{{ formatBDT(item.amount) }}
                                </template>
                            </div>
                            <div class="StrongBoxRecord__container-detailItem-balance">
                                Balance {{ formatBDT(item.balance_after) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else>
                    <div class="empty__container">
                        <svg class="svg-icon icon-empty">
                            <use xlink:href="#icon-empty"></use>
                        </svg>
                        <p>No data</p>
                    </div>
                </div>
            </div>
        </div>
    </PageLayout>
</template>

<style scoped>
.StrongBoxRecord__container-detailItem{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:.32rem;
    border-bottom:1px solid rgba(0,0,0,.05);
    background:#fff;
}
.StrongBoxRecord__container-detailItem-left{
    flex:1;
}
.StrongBoxRecord__container-detailItem-title{
    font-size:.3733rem;
    font-weight:600;
    color:#1f2937;
    margin-bottom:.08rem;
}
.StrongBoxRecord__container-detailItem-time{
    font-size:.2933rem;
    color:#9ca3af;
    line-height:1.6;
}
.StrongBoxRecord__container-detailItem-right{
    text-align:right;
    margin-left:.24rem;
}
.StrongBoxRecord__container-detailItem-amount{
    font-size:.4rem;
    font-weight:700;
    margin-bottom:.08rem;
}
.StrongBoxRecord__container-detailItem-balance{
    font-size:.2933rem;
    color:#9ca3af;
}
.green{
    color:#22c55e;
}
.red{
    color:#ef4444;
}
</style>