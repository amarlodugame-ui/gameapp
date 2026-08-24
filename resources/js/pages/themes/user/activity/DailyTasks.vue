<script setup>
import PageLayout from '../../layouts/PageLayout.vue'
import '@/css/activity/page-activity-DailyTasks.css'
import { router, usePage } from '@/user/inertia'
import { computed } from 'vue'

const page = usePage()

const tasks = computed(() => page.props.tasks || [])

const money = (amount) => {
    return `৳${Number(amount || 0).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`
}

const progressText = (task) => {
    if (task.progress_mode === 'count') {
        return `${parseInt(task.progress || 0)}/${parseInt(task.required_amount || 0)}`
    }

    return `${parseInt(task.progress || 0)}/${parseInt(task.required_amount || 0)}`
}

const claimTask = (taskKey) => {
    router.post(route('user.activity.daily-tasks.claim'), {
        task_key: taskKey,
    }, {
        preserveScroll: true,
    })
}

const goActivity = () => {
    router.visit(route('user.activity'))
}
</script>

<template>
<PageLayout title="Activity" rightText="Collection record" rightIcon="icon-watchCollection" rightIconClass="icon-watchCollection" :showBack="true" :backUrl="route('user.activity')" :rightUrl="route('user.activity.daily-tasks.record')">
    <div class="dailySignIn__container" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
    <div class="task-banner">
        <div >
            <p >
            <div class="banner-title">Activity Award</div>
            <div class="banner-content">
                <div >Complete weekly/daily tasks to receive rich rewards</div>
                <div >Weekly rewards cannot be accumulated to the next week, and daily rewards cannot be accumulated to the next day.</div>
            </div>
            </p>
        </div>
    </div>

        <div class="task-panel">
            <div class="task-item" v-for="task in tasks" :key="task.key">
                <div class="task-item-header">
                    <div class="hearder-status" :class="task.type === 'newbie' ? 'new' : 'day'"> {{ task.type === 'newbie' ? 'Newbie gift pack' : 'Daily mission' }}</div>
                    <span :class="task.status === 'claimed' ? 'headerSuccess' : (task.status === 'claimable' ? 'headerWarning' : 'headerGray')">
                        {{task.status === 'claimed' ? 'Received' : task.status === 'claimable' ? 'Completed' : 'Unfinished'}}</span>
                </div>

                <div class="task-item-type">
                    <div class="type-title" :class="task.type === 'newbie' ? 'new' : (task.type === 'bingo' ? 'weeklyType4' : 'weeklyType3')">
                        <svg class="svg-icon" :class="task.type === 'newbie' ? 'icon-actNewGift' : (task.type === 'bingo' ? 'icon-weeklyType4' : 'icon-weeklyType3')">
                            <use :xlink:href="task.type === 'newbie' ? '#icon-actNewGift' : (task.type === 'bingo' ? '#icon-weeklyType4' : '#icon-weeklyType3')"></use>
                        </svg>
                        <div>{{ task.title }}</div>
                    </div>
                    <div class="type-tip">{{ progressText(task) }}</div>
                </div>

                <div class="task-item-description">{{ task.description }}</div>
                <div class="task-item-bottom">
                    <div>Award amount</div>
                    <div class="bottom-title">
                        <svg class="svg-icon icon-activityWallet">
                            <use xlink:href="#icon-activityWallet"></use>
                        </svg>
                        <span>{{ money(task.reward_amount) }}</span>
                    </div>
                </div>

                <button v-if="task.status === 'claimable'" class="btn btnOther status2" @click="claimTask(task.key)"> Receive</button>
                <button v-else-if="task.status === 'claimed'" class="btn btnOther status3" disabled> Received</button>
                <button v-else class="btn btnOther status1" @click="goActivity"> to complete</button>
            </div>
        </div>
    </div>
</PageLayout>
</template>

<style scoped>
svg.svg-icon.icon-watchCollection.icon-watchCollection {
    width: .64rem;
    height: .64rem;
    margin-right: .13333rem;
}

.headerSuccess {
    color: #22c55e;
}

.headerWarning {
    color: #f59e0b;
}

.dailySignIn__container .task-panel .task-item .btnOther.status2 {
    width: -webkit-fill-available;
    border: none;
}
.dailySignIn__container .task-panel .task-item .btnOther.status3{
    width: -webkit-fill-available;
    border: none;
}

.dailySignIn__container .task-panel .task-item .btnOther.status1 {
    width: -webkit-fill-available;
}
</style>