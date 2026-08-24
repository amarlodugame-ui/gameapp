<script setup>
import PageLayout from '../../layouts/PageLayout.vue'
import { usePage } from '@/user/inertia'
import { computed } from 'vue'

const page = usePage()
const levels = computed(() => page.props.levels || [])

const money = (amount) => {
    return `৳${Number(amount || 0).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`
}
</script>

<template>
    <PageLayout
        title="Game Rules"
        :showBack="true"
        :backUrl="route('user.activity.main.daily-sign-in')"
    >
        <div class="dailySignInRules__container" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif">
            <div class="dailySignInRules__container-hero">
                <div class="dailySignInRules__container-hero__wrapper">
                    <div class="dailySignInRules__container-hero__wrapper-titlebox">
                        <div class="dailySignInRules__container-hero__wrapper-title"> Continuous attendance</div>
                        <div class="dailySignInRules__container-hero__wrapper-title"> Accumulated amount</div>
                        <div class="dailySignInRules__container-hero__wrapper-title"> Attendance bonus</div>
                    </div>
                    <ul>
                        <li v-for="item in levels" :key="item.day_no">
                            <div>{{ item.day_no }}</div>
                            <div>{{ money(item.required_bet) }}</div>
                            <div>{{ money(item.reward) }}</div>
                        </li>
                    </ul>
                </div>
            </div>


            <div class="rule ruleHead">
                <svg class="svg-icon icon-ruleHead">
                    <use xlink:href="#icon-ruleHead"></use>
                </svg>
                <div class="head">Rules</div>
                <div>
                    <div>
                        The higher the number of consecutive login days, the
                        more rewards you get, up to 7 consecutive days
                    </div>
                </div>
                <div>
                    <div>During the activity, please check once a day</div>
                </div>
                <div>
                    <div>
                        Players with no deposit history cannot claim the bonus
                    </div>
                </div>
                <div>
                    <div>Deposit requirements must be met from day one</div>
                </div>
                <div>
                    <div>
                        The platform reserves the right to final interpretation
                        of this activity
                    </div>
                </div>
                <div>
                    <div>
                        When you encounter problems, please contact customer
                        service
                    </div>
                </div>
            </div>
        </div>
    </PageLayout>
</template>
