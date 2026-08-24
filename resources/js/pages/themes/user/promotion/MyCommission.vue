<script setup>
import PageLayout from '../../layouts/PageLayout.vue'
import { router } from '@/user/inertia'
import { ref, computed, watch } from 'vue'
import { route } from 'ziggy-js'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({ date: '' }),
    },
    maxSelectableDate: {
        type: String,
        required: true,
    },
    summary: {
        type: Object,
        default: () => ({
            statusText: 'Pending settlement',
            settlementTime: null,
            numberOfBettors: 0,
            betAmount: '0.00',
            commissionPayout: '0.00',
            date: '',
        }),
    },
    levelWiseTeam: {
        type: Array,
        default: () => [],
    },
})

const showPicker = ref(false)
const minYear = 2022

const getDhakaToday = () => {
    const formatter = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Dhaka',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    })
    return formatter.format(new Date()) 
}

const safeDate = (dateString) => {
    return new Date(`${dateString}T00:00:00`)
}

const todayString = getDhakaToday()

const effectiveMaxDate = computed(() => todayString)

const maxDateObj = computed(() => safeDate(effectiveMaxDate.value))

const normalizeDate = (value) => {
    if (!value) return effectiveMaxDate.value
    return value > effectiveMaxDate.value ? effectiveMaxDate.value : value
}

const date = ref(normalizeDate(props.filters.date))

const initDateParts = (dateString) => {
    const finalDate = normalizeDate(dateString)
    const d = safeDate(finalDate)

    return {
        year: String(d.getFullYear()),
        month: String(d.getMonth() + 1).padStart(2, '0'),
        day: String(d.getDate()).padStart(2, '0'),
    }
}

const initial = initDateParts(date.value)

const selectedYear = ref(initial.year)
const selectedMonth = ref(initial.month)
const selectedDay = ref(initial.day)

const syncPickerFromDate = (dateString) => {
    const p = initDateParts(dateString)
    selectedYear.value = p.year
    selectedMonth.value = p.month
    selectedDay.value = p.day
}

watch(
    () => props.filters.date,
    (newDate) => {
        date.value = normalizeDate(newDate)
        syncPickerFromDate(date.value)
    },
    { immediate: true }
)

const years = computed(() => {
    const arr = []
    for (let y = minYear; y <= maxDateObj.value.getFullYear(); y++) {
        arr.push(String(y))
    }
    return arr
})

const months = computed(() => {
    let endMonth = 12

    if (Number(selectedYear.value) === maxDateObj.value.getFullYear()) {
        endMonth = maxDateObj.value.getMonth() + 1
    }

    const arr = []
    for (let i = 1; i <= endMonth; i++) {
        arr.push(String(i).padStart(2, '0'))
    }
    return arr
})

const days = computed(() => {
    const year = Number(selectedYear.value)
    const month = Number(selectedMonth.value)

    let endDay = new Date(year, month, 0).getDate()

    if (
        year === maxDateObj.value.getFullYear() &&
        month === maxDateObj.value.getMonth() + 1
    ) {
        endDay = maxDateObj.value.getDate()
    }

    const arr = []
    for (let i = 1; i <= endDay; i++) {
        arr.push(String(i).padStart(2, '0'))
    }
    return arr
})

watch(selectedYear, () => {
    if (!months.value.includes(selectedMonth.value)) {
        selectedMonth.value = months.value[months.value.length - 1]
    }
})

watch([selectedYear, selectedMonth], () => {
    if (!days.value.includes(selectedDay.value)) {
        selectedDay.value = days.value[days.value.length - 1]
    }
})

const displayDate = computed(() => date.value || effectiveMaxDate.value)

const chosenDate = computed(() => {
    const picked = `${selectedYear.value}-${selectedMonth.value}-${selectedDay.value}`
    return picked > effectiveMaxDate.value ? effectiveMaxDate.value : picked
})

const openPicker = () => {
    syncPickerFromDate(date.value || effectiveMaxDate.value)
    showPicker.value = true
}

const closePicker = () => {
    showPicker.value = false
}

const confirmPicker = () => {
    const finalDate = chosenDate.value
    closePicker()

    router.get(route('user.promotion.my-commission'), {
        date: finalDate,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

const selectYear = (year) => {
    selectedYear.value = String(year)
}

const selectMonth = (month) => {
    selectedMonth.value = String(month)
}

const selectDay = (day) => {
    selectedDay.value = String(day)
}
</script>
<template>
    <PageLayout title="Commission Details" :showBack="true" :backUrl="route('user.activity')">
        <div class="myCommission__container" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
            <div>
                <div class="van-sticky">
                    <div class="ar-searchbar">
                        <div class="ar-searchbar-type">
                            <div class="ar-searchbar__selector" @click="openPicker">
                                <div>
                                    <span class="ar-searchbar__selector-default">{{ displayDate }}</span>
                                    <i class="van-badge__wrapper van-icon van-icon-arrow-down"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="showPicker"
                class="van-overlay"
                role="button"
                tabindex="0"
                style="z-index: 2002;"
                @click="closePicker"
            ></div>

            <div
                v-if="showPicker"
                role="dialog"
                tabindex="0"
                class="van-popup van-popup--round van-popup--bottom"
                style="z-index: 2002;"
            >
                <div class="van-picker">
                    <div class="van-picker__toolbar">
                        <button
                            type="button"
                            class="van-picker__cancel van-haptics-feedback"
                            @click="closePicker"
                        >
                            Cancel
                        </button>

                        <div class="van-picker__title van-ellipsis">Choose a date</div>

                        <button
                            type="button"
                            class="van-picker__confirm van-haptics-feedback"
                            @click="confirmPicker"
                        >
                            Confirm
                        </button>
                    </div>

                    <div class="van-picker__columns" style="height: 264px;">
                        <!-- Year -->
                        <div class="van-picker-column">
                            <ul class="van-picker-column__wrapper">
<li
    v-for="year in years"
    :key="year"
    role="button"
    tabindex="0"
    class="van-picker-column__item"
    :class="{
        'van-picker-column__item--selected': year === selectedYear
    }"
    style="height: 44px;"
    @click="selectYear(year)"
>
    <div class="van-ellipsis">{{ year }}</div>
</li>
                            </ul>
                        </div>

                        <!-- Month -->
                        <div class="van-picker-column">
                            <ul class="van-picker-column__wrapper">
                                <li
                                    v-for="month in months"
                                    :key="month"
                                    role="button"
                                    tabindex="0"
                                    class="van-picker-column__item"
                                    :class="{
                                        'van-picker-column__item--selected': month === selectedMonth
                                    }"
                                    style="height: 44px;"
                                    @click="selectMonth(month)"
                                >
                                    <div class="van-ellipsis">{{ month }}</div>
                                </li>
                            </ul>
                        </div>

                        <!-- Day -->
                        <div class="van-picker-column">
                            <ul class="van-picker-column__wrapper">
                                <li
                                    v-for="day in days"
                                    :key="day"
                                    role="button"
                                    tabindex="0"
                                    class="van-picker-column__item"
                                    :class="{
                                        'van-picker-column__item--selected': day === selectedDay
                                    }"
                                    style="height: 44px;"
                                    @click="selectDay(day)"
                                >
                                    <div class="van-ellipsis">{{ day }}</div>
                                </li>
                            </ul>
                        </div>

                        <div class="van-picker__mask" style="background-size: 100% 110px;"></div>
                        <div class="van-hairline-unset--top-bottom van-picker__frame" style="height: 44px;"></div>
                    </div>
                </div>
            </div>

            <div class="myCommission__container-content">
                <div class="myCommission__container-content__item">
                    <div class="myCommission__container-content__item-body">
                        <p class="settle">{{ summary.statusText }}</p>
                        <span class="time">{{ summary.settlementTime || '--' }}</span>
                        <p class="sub">The commission has been automatically credited to your balance</p>

                        <div>
                            <span>Number of bettors</span>
                            <span class="amount grey">{{ summary.numberOfBettors }} People</span>
                        </div>

                        <div>
                            <span>Bet amount</span>
                            <span class="amount grey">{{ summary.betAmount }}</span>
                        </div>

                        <div>
                            <span>Commission payout</span>
                            <span class="amount orange fw">{{ summary.commissionPayout }}</span>
                        </div>

                        <div>
                            <span>date</span>
                            <span class="amount grey">{{ summary.date }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PageLayout>
</template>
<style scoped>
.cg-default.van-button {font-weight: 700;font-size: .4rem}.cg-default.van-button .van-button__icon {height: 100%;display: -webkit-box;display: -webkit-flex;display: flex;-webkit-box-align: center;-webkit-align-items: center;align-items: center}.cg-default.van-button .van-button__icon>img {height: 80%}@media (orientation: landscape) {html.landscape {font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog {z-index: 9999999!important}.myCommission__container {padding-inline:.32rem;padding-block:0 1.49333rem;font-family: bahnschrift}.myCommission__container .ar-searchbar__selector,.myCommission__container .ar-searchbar,.myCommission__container .ar-searchbar-type {width: 100%}.myCommission__container-content {display: -webkit-box;display: -webkit-flex;display: flex;-webkit-box-orient: vertical;-webkit-box-direction: normal;-webkit-flex-direction: column;flex-direction: column;gap: .26667rem;margin-top: .56rem}.myCommission__container-content__item {border-radius: .13333rem;font-size: .34667rem;font-weight: 500;background: var(--bg_color_L2)}.myCommission__container-content__item-header {display: -webkit-box;display: -webkit-flex;display: flex;-webkit-box-pack: justify;-webkit-justify-content: space-between;justify-content: space-between;margin-bottom: .13333rem;color: var(--text_color_L1);font-weight: 400;background: var(--main-color);height: .93333rem;line-height: .93333rem;padding: 0 .13333rem;border-radius: .13333rem .13333rem 0 0}.myCommission__container-content__item-header span {font-weight: 400;font-size: .37333rem;color: var(--textW)}.myCommission__container-content__item-header .detail {width: 3rem;height: .56rem;border: .01333rem solid var(--main-color);border-radius: .26667rem;text-align: center;color: var(--main-color);font-size: .32rem;position: relative}.myCommission__container-content__item-header .detail:after {content: ">";font-size: .4rem;position: absolute;right: .46667rem;top: -.06667rem}.myCommission__container-content__item-body {display: -webkit-box;display: -webkit-flex;display: flex;-webkit-box-orient: vertical;-webkit-box-direction: normal;-webkit-flex-direction: column;flex-direction: column;gap: .2rem;padding: .26667rem .13333rem;color: var(--text_color_L2);font-size: .32rem;font-weight: 0}.myCommission__container-content__item-body>div {display: -webkit-box;display: -webkit-flex;display: flex;-webkit-box-pack: justify;-webkit-justify-content: space-between;justify-content: space-between;background-color: var(--bg_color_L3)}.myCommission__container-content__item-body>div span {height: .8rem;padding: 0 .26667rem;color: var(--text_color_L2);font-size: .32rem;line-height: .8rem}.myCommission__container-content__item-body>div span:first-of-type {-webkit-box-flex: .45;-webkit-flex: .45;flex: .45}.myCommission__container-content__item-body>div .amount {color: var(--norm_secondary-color);font-size: .4rem}.myCommission__container-content__item-body>div .amount.orange {color: var(--norm_secondary-color)}.myCommission__container-content__item-body>div .amount.grey {color: var(--text_color_L1)}.myCommission__container-content__item-body>div .amount.fw {font-weight: 600}.myCommission__container-content__item-body>div .level {color: var(--main-color);font-size: .4rem;font-weight: 700}.myCommission__container {padding-bottom: 0;font-family: Inter,sans-serif}.myCommission__container .navbar .navbar__content .navbar__content-right {display: -webkit-box;display: -webkit-flex;display: flex;-webkit-box-align: center;-webkit-align-items: center;align-items: center}.myCommission__container .navbar .navbar__content .navbar__content-right svg {width: .58667rem;height: .58667rem;margin-right: .06667rem}.myCommission__container .navbar .navbar__content .navbar__content-right span {color: var(--text_color_L2);font-size: .32rem}.myCommission__container-msgWrapper {display: -webkit-box;display: -webkit-flex;display: flex;-webkit-box-orient: vertical;-webkit-box-direction: normal;-webkit-flex-direction: column;flex-direction: column;gap: .32rem;overflow: hidden;overflow-y: scroll}.myCommission__container-msgWrapper__item {padding: .26667rem;border-radius: .06667rem;background: var(--bg_color_L2)}.myCommission__container-msgWrapper__item-title {display: -webkit-box;display: -webkit-flex;display: flex;-webkit-box-align: center;-webkit-align-items: center;align-items: center}.myCommission__container-msgWrapper__item-title svg {width: .64rem;height: .64rem}.myCommission__container-msgWrapper__item-title svg:last-of-type {width: .48rem;height: .48rem;margin-left: auto}.myCommission__container-msgWrapper__item-title span {margin-left: .09333rem;font-size: .4rem;font-weight: 600}.myCommission__container-msgWrapper__item-time {margin-block:.10667rem .37333rem;color: var(--text_color_L2);font-size: .32rem;font-weight: 400}.myCommission__container-msgWrapper__item-content {color: var(--text_color_L2);font-size: .32rem;font-weight: 400;word-break: break-word}.myCommission__container-empty {position: absolute;top: 50%;left: 50%;-webkit-transform: translate(-50%,-50%);transform: translate(-50%,-50%)}.van-toast {word-break: break-word!important}.cg-default.van-button {font-weight: 700;font-size: .4rem}.cg-default.van-button .van-button__icon {height: 100%;display: -webkit-box;display: -webkit-flex;display: flex;-webkit-box-align: center;-webkit-align-items: center;align-items: center}.cg-default.van-button .van-button__icon>img {height: 80%}@media (orientation: landscape) {html.landscape {font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog {z-index: 9999999!important}.icon-LV {height: .61333rem;width: 1.33333rem;background: url(/assets/png/lv-450d4246.webp) no-repeat center center;background-size: cover;position: relative;text-align: center}.icon-LV .txt {display: block;position: absolute;right: 0;bottom: 0;height: .4rem;line-height: .42667rem;width: .69333rem;background: -webkit-linear-gradient(top,#fffba9 0%,#fff670 56.13%,#ffd180 100%);background: linear-gradient(180deg,#fffba9 0%,#fff670 56.13%,#ffd180 100%);-webkit-background-clip: text;-webkit-text-fill-color: transparent;background-clip: text;text-fill-color: transparent}.TeamReportDetail__C {padding: 0 .32rem}.TeamReportDetail__C-head {border-radius: .13333rem .13333rem 0 0;background-color: var(--bg_color_L2);min-height: 3.46667rem;padding: 0 .26667rem}.TeamReportDetail__C-head-top {height: 1.06667rem;border-bottom: .01333rem solid #d6d6d6;display: -webkit-box;display: -webkit-flex;display: flex;font-size: .37333rem;-webkit-box-pack: justify;-webkit-justify-content: space-between;justify-content: space-between;-webkit-box-align: center;-webkit-align-items: center;align-items: center;color: var(--text_color_L1)}.TeamReportDetail__C-head-top-btn {width: 3rem;height: .56rem;line-height: .56rem;border: .01333rem solid #1AB266;border-radius: .26667rem;font-size: .32rem;color: #1AB266;text-align: center}.TeamReportDetail__C-head-detail {padding: .29333rem 0;font-size: .34667rem;color: var(--text_color_L1)}.TeamReportDetail__C-head-detail>div {height: .4rem;display: -webkit-box;display: -webkit-flex;display: flex;-webkit-box-pack: justify;-webkit-justify-content: space-between;justify-content: space-between;-webkit-box-align: center;-webkit-align-items: center;align-items: center}.TeamReportDetail__C-head-detail>div+div {margin-top: .29333rem}.TeamReportDetail__C-head-detail-time span {font-size: .34667rem;color: #ff7f22}.TeamReportDetail__C-head-detail-commission span {color: #ff7f22}.TeamReportDetail__C-img {height: .01333rem;width: 100%;position: relative;border-bottom: .01333rem dashed #525167;border-width: .05333rem}.TeamReportDetail__C-img:after,.TeamReportDetail__C-img:before {content: "";display: block;background: var(--bg_color_L1);width: .56rem;height: .56rem;position: absolute;border-radius: 50%;top: -.28rem}.TeamReportDetail__C-img:before {left: -.28rem}.TeamReportDetail__C-img:after {right: -.28rem}.TeamReportDetail__C-detail {border-radius: 0 0 .13333rem .13333rem;background-color: var(--bg_color_L2);padding: .4rem .32rem}.TeamReportDetail__C-detail .btn {width: 100%;height: .8rem;line-height: .8rem;border: .01333rem solid var(--main-color);color: var(--main-color);text-align: center;border-radius: .8rem}.TeamReportDetail__C-body {background: var(--bg_color_L2);margin-top: .26667rem;border-radius: .13333rem}.TeamReportDetail__C-body .title {font-size: .37333rem;color: var(--text_color_L1);padding: .26667rem .33333rem 0}.TeamReportDetail__C-body .box {padding: .26667rem}.TeamReportDetail__C-body-item {display: -webkit-box;display: -webkit-flex;display: flex;-webkit-box-pack: justify;-webkit-justify-content: space-between;justify-content: space-between;-webkit-box-align: center;-webkit-align-items: center;align-items: center;height: .37333rem;font-size: .34667rem;color: #7e829f;padding-left: .48rem;background-image: url(/assets/png/roundIcon-d4d5ea8b.webp);background-size: .26667rem;background-position: left center;background-repeat: no-repeat;position: relative}html:lang(ar) .TeamReportDetail__C-body-item {background-position: right center}.TeamReportDetail__C-body-item:last-child:before {content: normal}.TeamReportDetail__C-body-item:before {content: "";position: absolute;display: block;height: calc(100% + .4rem);width: 0;border-left: .01333rem dashed var(--main-color);left: .10667rem;top: 50%}html:lang(ar) .TeamReportDetail__C-body-item:before {right: .10667rem;left: auto}.TeamReportDetail__C-body-item+div {margin-top: .4rem}.TeamReportDetail__C-body-item span {color: var(--text_color_L1);font-size: .34667rem}.TeamReportDetail__C-body-item span.meony {color: #ff7f22}.TeamReportDetail__C-body-item span.level {color: var(--main-color)}.TeamReportDetail__C-body-grade {overflow: hidden;margin-bottom: .66667rem}.TeamReportDetail__C-body-grade-th {display: -webkit-box;display: -webkit-flex;display: flex;height: 1.33333rem;color: #fff;font-size: .34667rem;line-height: 1.33333rem;background: var(--sheet_nva_color);text-align: center}.TeamReportDetail__C-body-grade-th .item {-webkit-box-flex: 1;-webkit-flex: 1;flex: 1}.TeamReportDetail__C-body-grade-tr {display: -webkit-box;display: -webkit-flex;display: flex;width: calc(100% + .01333rem);background-color: #fff;margin: 0 -.01333rem -.01333rem;color: var(--text_color_L2);background: var(--bg_color_L2)}.TeamReportDetail__C-body-grade-tr:nth-of-type(odd) {background: var(--sheet_detail_bg_color)}.TeamReportDetail__C-body-grade-tr .item {display: -webkit-box;display: -webkit-flex;display: flex;-webkit-box-align: center;-webkit-align-items: center;align-items: center;-webkit-box-pack: center;-webkit-justify-content: center;justify-content: center;width: 33.3%;height: .93333rem;padding: .06667rem 0;font-size: .34667rem}
.van-picker-column__wrapper {
    max-height: 264px;
    overflow-y: auto;
    pointer-events: auto;
}

.van-picker-column__item {
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    pointer-events: auto;
}

.van-picker-column__item--selected {
    font-weight: 700;
    color: #ee0a24;
}

.van-popup {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    background: #fff;
}

.van-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
}

.myCommission__container-content__item-body>div span:first-of-type {
    -webkit-box-flex: 1.45;
    -webkit-flex: 1.45;
}


</style>