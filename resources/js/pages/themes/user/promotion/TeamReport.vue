<script setup>
import PageLayout from '../../layouts/PageLayout.vue'
import { router } from '@/user/inertia'
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import '@/css/promotion/page-promotion-MyInvitation.css'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({})
    },
    summary: {
        type: Object,
        default: () => ({})
    },
    subordinates: {
        type: Array,
        default: () => []
    }
})

const tierOptions = [
    { label: 'All', value: 'all' },
    { label: 'Tier 1', value: '1' },
    { label: 'Tier 2', value: '2' },
    { label: 'Tier 3', value: '3' },
    { label: 'Tier 4', value: '4' },
    { label: 'Tier 5', value: '5' },
    { label: 'Tier 6', value: '6' },
]

const PAGE_SIZE = 20
const visibleCount = ref(PAGE_SIZE)
const loadMoreRef = ref(null)
let observer = null

const visibleSubordinates = computed(() => {
    return props.subordinates.slice(0, visibleCount.value)
})

const hasMore = computed(() => {
    return visibleCount.value < props.subordinates.length
})

const loadMore = () => {
    if (!hasMore.value) return
    visibleCount.value += PAGE_SIZE
}

const setupObserver = async () => {
    await nextTick()

    if (observer) {
        observer.disconnect()
        observer = null
    }

    if (!loadMoreRef.value) return

    observer = new IntersectionObserver((entries) => {
        const entry = entries[0]
        if (entry?.isIntersecting) {
            loadMore()
        }
    }, {
        root: null,
        rootMargin: '120px',
        threshold: 0.1,
    })

    observer.observe(loadMoreRef.value)
}

const getMaxSelectableDate = () => {
    const d = new Date()
    d.setDate(d.getDate() - 1)
    d.setHours(0, 0, 0, 0)
    return d
}

const formatDate = (d) => {
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${y}-${m}-${day}`
}

const yesterdayString = () => formatDate(getMaxSelectableDate())

const normalizeDate = (value) => {
    const maxDate = getMaxSelectableDate()
    const fallback = yesterdayString()

    if (!value) return fallback

    const parsed = new Date(`${value}T00:00:00`)
    if (isNaN(parsed.getTime())) return fallback

    if (parsed > maxDate) return fallback

    return value
}

const search = ref(props.filters?.search || '')
const date = ref(normalizeDate(props.filters?.date))
const tier = ref(String(props.filters?.tier || 'all'))

const showTierPicker = ref(false)
const showDatePicker = ref(false)

const tempTier = ref(String(tier.value))

const initialDateParts = normalizeDate(date.value).split('-')
const tempYear = ref(initialDateParts[0])
const tempMonth = ref(initialDateParts[1])
const tempDay = ref(initialDateParts[2])

const years = computed(() => {
    const maxDate = getMaxSelectableDate()
    const maxYear = maxDate.getFullYear()
    const arr = []

    for (let y = 2022; y <= maxYear; y++) {
        arr.push(String(y))
    }

    return arr
})

const months = computed(() => {
    const maxDate = getMaxSelectableDate()
    const maxYear = maxDate.getFullYear()
    const maxMonthOfMaxYear = maxDate.getMonth() + 1

    let maxMonth = 12

    if (Number(tempYear.value) === maxYear) {
        maxMonth = maxMonthOfMaxYear
    }

    return Array.from({ length: maxMonth }, (_, i) =>
        String(i + 1).padStart(2, '0')
    )
})

const days = computed(() => {
    const year = Number(tempYear.value)
    const month = Number(tempMonth.value)
    const totalDays = new Date(year, month, 0).getDate()

    const maxDate = getMaxSelectableDate()
    const maxYear = maxDate.getFullYear()
    const maxMonth = maxDate.getMonth() + 1
    const maxDay = maxDate.getDate()

    let lastDay = totalDays

    if (year === maxYear && month === maxMonth) {
        lastDay = maxDay
    }

    return Array.from({ length: lastDay }, (_, i) =>
        String(i + 1).padStart(2, '0')
    )
})

const selectedTierLabel = computed(() => {
    const found = tierOptions.find(item => String(item.value) === String(tier.value))
    return found ? found.label : 'All'
})

const filterData = () => {
    router.get(route('user.promotion.team-report'), {
        search: search.value,
        date: date.value,
        tier: tier.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onSuccess: () => {
            visibleCount.value = PAGE_SIZE
            setupObserver()
        }
    })
}

const money = (amount) => {
    return new Intl.NumberFormat('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(amount || 0))
}

const openTierPicker = () => {
    tempTier.value = String(tier.value || 'all')
    showTierPicker.value = true
}

const closeTierPicker = () => {
    showTierPicker.value = false
}

const selectTier = (value) => {
    tempTier.value = String(value)
}

const confirmTierPicker = () => {
    tier.value = String(tempTier.value)
    showTierPicker.value = false
    filterData()
}

const openDatePicker = () => {
    const safeDate = normalizeDate(date.value)
    const parts = safeDate.split('-')

    tempYear.value = parts[0]
    tempMonth.value = parts[1]
    tempDay.value = parts[2]
    showDatePicker.value = true
}

const closeDatePicker = () => {
    showDatePicker.value = false
}

const confirmDatePicker = () => {
    const selected = new Date(
        Number(tempYear.value),
        Number(tempMonth.value) - 1,
        Number(tempDay.value)
    )

    const maxDate = getMaxSelectableDate()
    maxDate.setHours(23, 59, 59, 999)

    if (selected > maxDate) {
        return
    }

    date.value = `${tempYear.value}-${tempMonth.value}-${tempDay.value}`
    showDatePicker.value = false
    filterData()
}

const closeAllPickers = () => {
    showTierPicker.value = false
    showDatePicker.value = false
}

const copyText = async (text) => {
    try {
        await navigator.clipboard.writeText(String(text))
    } catch (e) {
        console.error(e)
    }
}

watch([tempYear, tempMonth], () => {
    const maxDate = getMaxSelectableDate()
    const maxYear = maxDate.getFullYear()
    const maxMonth = maxDate.getMonth() + 1
    const maxSelectableDay = maxDate.getDate()

    if (Number(tempYear.value) === maxYear && Number(tempMonth.value) > maxMonth) {
        tempMonth.value = String(maxMonth).padStart(2, '0')
    }

    let maxDay = new Date(Number(tempYear.value), Number(tempMonth.value), 0).getDate()

    if (
        Number(tempYear.value) === maxYear &&
        Number(tempMonth.value) === maxMonth
    ) {
        maxDay = maxSelectableDay
    }

    if (Number(tempDay.value) > maxDay) {
        tempDay.value = String(maxDay).padStart(2, '0')
    }
})

watch(() => props.subordinates, async () => {
    visibleCount.value = PAGE_SIZE
    await setupObserver()
}, { deep: true })

onMounted(() => {
    setupObserver()
})

onBeforeUnmount(() => {
    if (observer) observer.disconnect()
})
</script>

<template>
    <PageLayout title="Subordinate data" :showBack="true" :backUrl="route('user.activity')">

        <div
            v-if="showTierPicker || showDatePicker"
            class="van-overlay"
            role="button"
            tabindex="0"
            style="z-index: 2002;"
            @click="closeAllPickers"
        ></div>

        <div
            v-if="showTierPicker"
            role="dialog"
            tabindex="0"
            class="van-popup van-popup--round van-popup--bottom custom-bottom-popup"
            style="z-index: 2003;"
        >
            <div class="van-picker">
                <div class="van-picker__toolbar">
                    <button
                        type="button"
                        class="van-picker__cancel van-haptics-feedback"
                        @click="closeTierPicker"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="van-picker__confirm van-haptics-feedback"
                        @click="confirmTierPicker"
                    >
                        Confirm
                    </button>
                </div>

                <div class="van-picker__columns custom-picker-body" style="height: 264px;">
                    <div class="van-picker-column custom-scroll-column">
                        <ul class="van-picker-column__wrapper custom-scroll-list">
                            <li
                                v-for="item in tierOptions"
                                :key="item.value"
                                role="button"
                                tabindex="0"
                                class="van-picker-column__item"
                                :class="{ 'van-picker-column__item--selected': String(tempTier) === String(item.value) }"
                                style="height: 44px;"
                                @click="selectTier(item.value)"
                            >
                                <div class="van-ellipsis">{{ item.label }}</div>
                            </li>
                        </ul>
                    </div>

                    <div class="van-picker__mask" style="background-size: 100% 110px;"></div>
                    <div class="van-hairline-unset--top-bottom van-picker__frame" style="height: 44px;"></div>
                </div>
            </div>
        </div>

        <div
            v-if="showDatePicker"
            role="dialog"
            tabindex="0"
            class="van-popup van-popup--round van-popup--bottom custom-bottom-popup"
            style="z-index: 2008;"
        >
            <div class="van-picker">
                <div class="van-picker__toolbar">
                    <button
                        type="button"
                        class="van-picker__cancel van-haptics-feedback"
                        @click="closeDatePicker"
                    >
                        Cancel
                    </button>

                    <div class="van-picker__title van-ellipsis">Choose a date</div>

                    <button
                        type="button"
                        class="van-picker__confirm van-haptics-feedback"
                        @click="confirmDatePicker"
                    >
                        Confirm
                    </button>
                </div>

                <div class="van-picker__columns custom-date-columns" style="height: 264px;">
                    <div class="van-picker-column custom-scroll-column">
                        <ul class="van-picker-column__wrapper custom-scroll-list">
                            <li
                                v-for="year in years"
                                :key="year"
                                role="button"
                                tabindex="0"
                                class="van-picker-column__item"
                                :class="{ 'van-picker-column__item--selected': tempYear === year }"
                                style="height: 44px;"
                                @click="tempYear = year"
                            >
                                <div class="van-ellipsis">{{ year }}</div>
                            </li>
                        </ul>
                    </div>

                    <div class="van-picker-column custom-scroll-column">
                        <ul class="van-picker-column__wrapper custom-scroll-list">
                            <li
                                v-for="month in months"
                                :key="month"
                                role="button"
                                tabindex="0"
                                class="van-picker-column__item"
                                :class="{ 'van-picker-column__item--selected': tempMonth === month }"
                                style="height: 44px;"
                                @click="tempMonth = month"
                            >
                                <div class="van-ellipsis">{{ month }}</div>
                            </li>
                        </ul>
                    </div>

                    <div class="van-picker-column custom-scroll-column">
                        <ul class="van-picker-column__wrapper custom-scroll-list">
                            <li
                                v-for="dayItem in days"
                                :key="dayItem"
                                role="button"
                                tabindex="0"
                                class="van-picker-column__item"
                                :class="{ 'van-picker-column__item--selected': tempDay === dayItem }"
                                style="height: 44px;"
                                @click="tempDay = dayItem"
                            >
                                <div class="van-ellipsis">{{ dayItem }}</div>
                            </li>
                        </ul>
                    </div>

                    <div class="van-picker__mask" style="background-size: 100% 110px;"></div>
                    <div class="van-hairline-unset--top-bottom van-picker__frame" style="height: 44px;"></div>
                </div>
            </div>
        </div>

        <div class="TeamReport__C" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
            <div class="TeamReport__C-head">
                <div class="TeamReport__C-head-fixed">
                    <div class="searchbar-container">
                        <input
                            v-model="search"
                            @keyup.enter="filterData"
                            type="text"
                            auto-complete="new-password"
                            autocomplete="off"
                            class="searchbar-container__searchbar"
                            placeholder="Search subordinate UID"
                            maxlength="30"
                        >
                        <svg class="svg-icon icon-searchBtn searchIcon" @click="filterData">
                            <use xlink:href="#icon-searchBtn"></use>
                        </svg>
                    </div>

                    <div class="TeamReport__C-head-line2 custom-filter-row">
                        <div class="custom-picker-trigger" @click="openTierPicker">
                            <span class="default">{{ selectedTierLabel }}</span>
                            <i class="van-badge__wrapper van-icon van-icon-arrow-down"></i>
                        </div>

                        <div class="custom-picker-trigger" @click="openDatePicker">
                            <span class="default">{{ date }}</span>
                            <i class="van-badge__wrapper van-icon van-icon-arrow-down"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="TeamReport__C-body">
                <div class="header-container">
                    <div>
                        <div class="num">{{ summary.deposit_number || 0 }}</div>
                        <div>Deposit number</div>
                    </div>
                    <div>
                        <div class="num">{{ money(summary.deposit_amount) }}</div>
                        <div>Deposit amount</div>
                    </div>
                    <div>
                        <div class="num">{{ summary.number_of_bettors || 0 }}</div>
                        <div>Number of bettors</div>
                    </div>
                    <div>
                        <div class="num">{{ money(summary.total_bet) }}</div>
                        <div>Total bet</div>
                    </div>
                    <div>
                        <div class="num">{{ summary.first_deposit_people || 0 }}</div>
                        <div>Number of people making first deposit</div>
                    </div>
                    <div>
                        <div class="num">{{ money(summary.first_deposit_amount) }}</div>
                        <div>First deposit amount</div>
                    </div>
                    <div>
                        <div class="num">{{ money(summary.commission) }}</div>
                        <div>Commission</div>
                    </div>
                </div>

                <div class="infiniteScroll" id="refresh7fe60e45fb0943b9b372e0209ccd2d81">
                    <div v-if="subordinates.length">
                        <div
                            v-for="item in visibleSubordinates"
                            :key="item.uid"
                            class="commission-card"
                        >
                            <div class="card-top">
                                <div class="uid">
                                    UID:{{ item.uid }}
                                    <span class="copy-icon" @click="copyText(item.uid)">
                                        <svg class="svg-icon copy-value-icon">
                                            <use xlink:href="#icon-copy"></use>
                                        </svg>
                                    </span>
                                </div>
                            </div>

                            <div class="card-line"></div>

                            <div class="card-row">
                                <span class="label">Level</span>
                                <span class="value">{{ item.level ?? '-' }}</span>
                            </div>

                            <div class="card-row">
                                <span class="label">Deposit amount</span>
                                <span class="value highlight">{{ money(item.deposit_amount) }}</span>
                            </div>

                            <div class="card-row">
                                <span class="label">Bet amount</span>
                                <span class="value highlight">{{ money(item.total_bet) }}</span>
                            </div>

                            <div class="card-row">
                                <span class="label">Commission</span>
                                <span class="value highlight">{{ money(item.commission) }}</span>
                            </div>

                            <div class="card-row">
                                <span class="label">Time</span>
                                <span class="value muted">{{ item.time }}</span>
                            </div>
                        </div>
                    </div>

<div
    v-if="hasMore"
    ref="loadMoreRef"
    class="infiniteScroll__loading"
    style="padding: 16px; text-align: center;"
>
    Loading more...
</div>


                    <div v-else class="infiniteScroll__loading">
                        <div class="empty__container">
                            <svg class="svg-icon icon-empty">
                                <use xlink:href="#icon-empty"></use>
                            </svg>
                            <p>No data</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PageLayout>
</template>

<style scoped>
.custom-filter-row {display: flex;gap: 10px;align-items: center;margin-top: 10px;}
.custom-picker-trigger {min-width: 120px;height: 36px;padding: 0 12px;border-radius: 10px;background: #f7f8fa;display: flex;align-items: center;justify-content: space-between;gap: 8px;cursor: pointer;}
.custom-bottom-popup {position: fixed;left: 0;right: 0;bottom: 0;background: #fff;border-top-left-radius: 18px;border-top-right-radius: 18px;overflow: hidden;animation: slideUp .2s ease;}
@keyframes slideUp {from {transform: translateY(100%);}to {transform: translateY(0);}}
.custom-picker-body,
.custom-date-columns {position: relative;display: grid;grid-template-columns: 1fr;}
.custom-date-columns {grid-template-columns: 1fr 1fr 1fr;}
.custom-scroll-column {height: 264px;overflow-y: auto;-webkit-overflow-scrolling: touch;}
.custom-scroll-list {margin: 0;padding: 110px 0;list-style: none;}
.van-picker__mask,
.van-picker__frame {pointer-events: none;}
.van-picker-column__item {display: flex;align-items: center;justify-content: center;color: #666;cursor: pointer;}
.van-picker-column__item--selected {color: #111;font-weight: 700;}
.van-overlay {position: fixed;inset: 0;background: rgba(0, 0, 0, .45);}
.van-picker__toolbar {height: 48px;padding: 0 14px;display: flex;align-items: center;justify-content: space-between;border-bottom: 1px solid #f1f1f1;}
.van-picker__cancel,
.van-picker__confirm {background: transparent;border: none;font-size: 14px;cursor: pointer;}
.van-picker__cancel {color: #999;}
.van-picker__confirm {color: #1989fa;font-weight: 600;}
.van-picker__title {font-size: 14px;font-weight: 600;}
.commission-card {background: #fff;border-radius: 14px;padding: 14px;margin-bottom: 12px;}
.card-top {display: flex;justify-content: space-between;align-items: center;}
.uid {font-weight: 700;display: flex;align-items: center;gap: 6px;}
.card-line {height: 1px;background: #eee;margin: 12px 0;}
.card-row {display: flex;align-items: center;justify-content: space-between;padding: 6px 0;}
.label {color: #777;}
.value {font-weight: 600;}
.highlight {color: #ff7a00;}
.muted {color: #999;}
.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.TeamReport__C{padding:0 .32rem}.TeamReport__C-head{height:2.4rem}.TeamReport__C-head-fixed{position:fixed;top:.93333rem;width:100%;left:50%;-webkit-transform:translateX(-50%);transform:translate(-50%);max-width:10rem;background-color:var(--bg_color_L1);z-index:9;padding:.13333rem .32rem}.TeamReport__C-head-line2{height:1.06667rem;width:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;color:var(--text_color_L2);font-size:.37333rem;margin-top:.26667rem}.TeamReport__C-head-line2>div{border-radius:.13333rem;line-height:1.06667rem;width:calc(100% - .13333rem);padding:0 .26667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;background-color:var(--bg_color_L2)}.TeamReport__C-head-line2>div+div{margin-left:.26667rem}.TeamReport__C-head-line2 .default{color:var(--text_color_L1)}.TeamReport__C-body{margin-top:.53333rem;color:var(--text_color_L2)}.TeamReport__C-body-item{background:var(--darkBg, var(--bg_color_L2));box-shadow:var(--BoxShadowColor-9);border-radius:.13333rem;padding:0 .26667rem}.TeamReport__C-body-item-head{height:.93333rem;padding-top:.13333rem;border-bottom:.01333rem solid var(--Dividing-line_color);display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;font-size:.42667rem;color:var(--darkTextW, var(--text_color_L1))}.TeamReport__C-body-item-head .title{min-width:2.66667rem}.TeamReport__C-body-item-head img{width:.4rem}.TeamReport__C-body-item-detail{padding:.29333rem 0;color:666;font-size:.34667rem}.TeamReport__C-body-item-detail>div{height:.4rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.TeamReport__C-body-item-detail>div+div{margin-top:.29333rem}.TeamReport__C-body-item-detail-time span{font-size:.32rem;color:var(--text_color_L3)}.TeamReport__C-body-item-detail-commission span{color:var(--norm_secondary-color)}.TeamReport__C-body-item+div{margin-top:.32rem}.TeamReport__C-body .header-container{min-height:2.66667rem;background-color:var(--bg_color_L3);color:var(--text_color_L1);font-size:.32rem;display:-webkit-box;display:-webkit-flex;display:flex;padding:.53333rem 0;margin-bottom:.4rem;border-radius:.26667rem;-webkit-flex-wrap:wrap;flex-wrap:wrap;gap:.64rem 0}.TeamReport__C-body .header-container>div{width:50%;text-align:center;color:var(--text_color_L1)}.TeamReport__C-body .header-container>div:nth-of-type(odd):before{display:none}.TeamReport__C-body .header-container>div>div:last-of-type{color:var(--text_color_L2)}.TeamReport__C-body .header-container>div+div{position:relative;margin-bottom:.13333rem}.TeamReport__C-body .header-container>div+div:before{content:"";display:block;position:absolute;border-right:.01333rem solid var(--text_color_L3);height:100%;width:0;left:0;top:0}.TeamReport__C-body .header-container .num{height:.56rem;font-size:.48rem;font-weight:700;margin-bottom:.08rem}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.TeamReportDetail__C{padding:0 .32rem}.TeamReportDetail__C-head{border-radius:.13333rem .13333rem 0 0;background-color:#fff;height:3.46667rem;padding:0 .26667rem}.TeamReportDetail__C-head-top{height:1.06667rem;border-bottom:.01333rem solid #d6d6d6;display:-webkit-box;display:-webkit-flex;display:flex;font-size:.37333rem;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;color:#333}.TeamReportDetail__C-head-top-btn{width:3rem;height:.56rem;line-height:.56rem;border:.01333rem solid var(--main-color);border-radius:.26667rem;font-size:.32rem;color:var(--main-color);text-align:center}.TeamReportDetail__C-head-detail{padding:.29333rem 0;color:666;font-size:.34667rem;color:#666}.TeamReportDetail__C-head-detail>div{height:.4rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.TeamReportDetail__C-head-detail>div+div{margin-top:.29333rem}.TeamReportDetail__C-head-detail-time span{font-size:.32rem;color:#aeb0c6}.TeamReportDetail__C-head-detail-commission span{color:#ff7f22}.TeamReportDetail__C-img{height:.56rem;width:100%;background-image:url(/assets/png/moonBar-f80ac733.webp);background-repeat:no-repeat;background-size:cover}.TeamReportDetail__C-detail{border-radius:0 0 .13333rem .13333rem;background-color:#fff;height:3.73333rem;padding:.4rem .32rem}.TeamReportDetail__C-detail-item{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;height:.37333rem;font-size:.34667rem;color:#7e829f;padding-left:.48rem;background-image:url(/assets/png/roundIcon-d4d5ea8b.webp);background-size:.26667rem;background-position:left center;background-repeat:no-repeat;position:relative}.TeamReportDetail__C-detail-item:last-child:before{content:normal}.TeamReportDetail__C-detail-item:before{content:"";position:absolute;display:block;height:calc(100% + .4rem);width:0;border-left:.01333rem dashed #ff7172;left:.10667rem;top:50%}.TeamReportDetail__C-detail-item+div{margin-top:.4rem}.TeamReportDetail__C-detail-item span{color:#333}
</style>