<script setup>
import PageLayout from '../../layouts/PageLayout.vue'
import '@/css/activity/page-activity-DailyTasks.css'
import { usePage } from '@/user/inertia'
import { computed, ref } from 'vue'

const page = usePage()
const activeTab = ref('weekly')

const weeklyRecords = computed(() => page.props.weeklyRecords || [])
const dailyRecords = computed(() => page.props.dailyRecords || [])

const currentRecords = computed(() => {
    return activeTab.value === 'weekly' ? weeklyRecords.value : dailyRecords.value
})

const money = (amount) => {
    return `৳${Number(amount || 0).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`
}
</script>

<template>
    <PageLayout title="Receive history" :showBack="true" :backUrl="route('user.activity.daily-tasks')">
        <div class="task-record-container" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif">
            <div>
                <div class="van-sticky">
                    <div class="head">
                        <button
                            :class="{ active: activeTab === 'weekly' }"
                            @click="activeTab = 'weekly'"
                        >
                            weekly
                        </button>

                        <button
                            :class="{ active: activeTab === 'daily' }"
                            @click="activeTab = 'daily'"
                        >
                            daily
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="currentRecords.length" role="feed" class="van-list" aria-busy="false">
                <div class="record-panel van-clearfix">
                    <div
                        v-for="item in currentRecords"
                        :key="item.id"
                        class="record-item"
                    >
                        <div class="record-item-left">
                            <h4>{{ item.task_title }}</h4>
                            <p>{{ item.claimed_at }}</p>
                            <small>
                                Completed:
                                {{
                                    item.task_key === 'newbie_gift_pack'
                                        ? parseInt(item.completed_amount)
                                        : money(item.completed_amount)
                                }}
                            </small>
                        </div>

                        <div class="record-item-right">
                            <span>{{ money(item.reward_amount) }}</span>
                        </div>
                    </div>
                </div>

                <div class="van-list__placeholder"></div>
            </div>

            <div v-else class="empty__container">
                <svg class="svg-icon icon-empty">
                    <use xlink:href="#icon-empty"></use>
                </svg>
                <p>No data</p>
            </div>
        </div>
    </PageLayout>
</template>

<style>
.record-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-bottom: 1px solid rgba(0, 0, 0, .06);
    background: #fff;
}

.record-item-left h4 {
    margin: 0 0 4px;
    font-size: 14px;
    font-weight: 600;
}

.record-item-left p,
.record-item-left small {
    margin: 0;
    color: #8c8c8c;
    font-size: 12px;
    display: block;
}

.record-item-right span {
    font-size: 15px;
    font-weight: 700;
    color: #22c55e;
}
</style>