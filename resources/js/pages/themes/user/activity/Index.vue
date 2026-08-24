<script setup>
import AuthLayout from '../../layouts/AuthLayout.vue'
import BottomTabbar from '../../partials/BottomTabbar.vue'
import '@/css/activity/page-activity-indexvue_vue_type_script_setup_true_lang.css'
import { router } from '@/user/inertia'

const props = defineProps({
    activityBanners: {
        type: Array,
        default: () => []
    },
    bonusSummary: {
        type: Object,
        default: () => ({
            today_bonus: 0,
            total_bonus: 0
        })
    },
    topMenus: {
        type: Array,
        default: () => []
    },
    giftItems: {
        type: Array,
        default: () => []
    }
})

const goTo = (url) => {
    if (url) {
        router.visit(url)
    }
}
</script>

<template>
    <AuthLayout class="main" :showBack="false" logoUrl="/assets/logo/logo.png">
        <div class="activity-wrapper" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
            <div class="activity-banner">
                <ul class="activity-bonus">
                    <li>
                        <p>Today's bonus</p>
                        <h3>৳{{ Number(props.bonusSummary.today_bonus).toFixed(2) }}</h3>
                    </li>
                    <li>
                        <p>Total bonus</p>
                        <h3>৳{{ Number(props.bonusSummary.total_bonus).toFixed(2) }}</h3>
                    </li>
                </ul>
                <div class="bonus-button">Bonus details</div>
            </div>

            <div class="activity-panel">
                <div class="activity-panel-header lg4">
                    <div
                        v-for="(item, index) in props.topMenus"
                        :key="index"
                        class="header-item"
                        @click="goTo(item.route)"
                    >
                        <div class="van-badge__wrapper">
                            <div :class="[item.class, 'bgcontainer']"></div>
                        </div>
                        <span>{{ item.title }}</span>
                    </div>
                </div>

                <div class="activity-panel-content">
                    <div
                        v-for="(item, index) in props.giftItems"
                        :key="index"
                        class="content-title"
                        @click="goTo(item.route)"
                    >
                        <img :src="item.image">
                        <div class="content-para">{{ item.title }}</div>
                        <p>{{ item.description }}</p>
                    </div>
                </div>
            </div>

            <div role="feed" class="van-list" aria-busy="false">
                <div class="activitySection__container">
                    <div
                        v-for="(banner, index) in props.activityBanners"
                        :key="index"
                        class="box"
                    >
                        <img
                            :src="banner.image"
                            :class="`act_${index}`"
                        >
                        <div class="box-content">
                            <div class="box-title">{{ banner.title }}</div>
                        </div>
                    </div>
                </div>
                <div class="van-list__finished-text">No more</div>
                <div class="van-list__placeholder"></div>
            </div>
        </div>

        <!-- <BottomTabbar /> -->
    </AuthLayout>
</template>