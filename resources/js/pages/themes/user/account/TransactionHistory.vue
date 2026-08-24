<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import { router } from '@/user/inertia'
import PageLayout from '../../layouts/PageLayout.vue'

const props = defineProps({
  transactions: Object,
})

const allTransactions = ref(props.transactions?.data || [])
const nextPage = ref(props.transactions?.next_page_url || null)
const loading = ref(false)

const selectedType = ref('all')
const selectedDate = ref('')

const showTypePicker = ref(false)
const showDatePicker = ref(false)

const tempSelectedType = ref('all')
const tempSelectedYear = ref('')
const tempSelectedMonth = ref('')
const tempSelectedDay = ref('')

const today = new Date()
const currentYear = today.getFullYear()
const currentMonth = today.getMonth() + 1
const currentDay = today.getDate()
const todayString = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(currentDay).padStart(2, '0')}`

const formatMoney = (amount) => {
  return `৳${Number(amount || 0).toFixed(2)}`
}

const formatSignedMoney = (trxType, amount) => {
  const value = Number(amount || 0).toFixed(2)
  return trxType === '-' ? `-৳${value}` : `+৳${value}`
}

const formatDate = (value) => {
  if (!value) return '-'

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value

  return date.toLocaleString('en-GB', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  }).replace(',', '')
}

const replaceUnderscores = (value) => {
  if (!value) return '-'
  return String(value).replace(/_/g, ' ')
}

const amountClass = (transaction) => {
  return transaction.trx_type === '-' ? 'red' : 'green'
}

const typeOptions = computed(() => {
  const unique = [...new Set(allTransactions.value.map(item => item.remark).filter(Boolean))]
  return ['all', ...unique]
})

const selectedTypeLabel = computed(() => {
  return selectedType.value === 'all'
    ? 'All'
    : replaceUnderscores(selectedType.value)
})

const selectedDateLabel = computed(() => {
  return selectedDate.value || 'Choose a date'
})

const yearOptions = computed(() => {
  const years = []
  for (let year = 2022; year <= currentYear; year++) {
    years.push(String(year))
  }
  return years
})

const monthOptions = computed(() => {
  if (!tempSelectedYear.value) return []

  let maxMonth = 12

  if (Number(tempSelectedYear.value) === currentYear) {
    maxMonth = currentMonth
  }

  const months = []
  for (let month = 1; month <= maxMonth; month++) {
    months.push(String(month).padStart(2, '0'))
  }
  return months
})

const dayOptions = computed(() => {
  if (!tempSelectedYear.value || !tempSelectedMonth.value) return []

  const year = Number(tempSelectedYear.value)
  const month = Number(tempSelectedMonth.value)

  let maxDay = new Date(year, month, 0).getDate()

  if (year === currentYear && month === currentMonth) {
    maxDay = currentDay
  }

  const days = []
  for (let day = 1; day <= maxDay; day++) {
    days.push(String(day).padStart(2, '0'))
  }
  return days
})

const filteredTransactions = computed(() => {
  let items = [...allTransactions.value]

  if (selectedType.value !== 'all') {
    items = items.filter(item => item.remark === selectedType.value)
  }

  if (selectedDate.value) {
    items = items.filter(item => {
      const createdAt = item.created_at ? String(item.created_at).slice(0, 10) : ''
      return createdAt === selectedDate.value
    })
  }

  return items
})

const openTypePicker = () => {
  tempSelectedType.value = selectedType.value
  showTypePicker.value = true
}

const cancelTypePicker = () => {
  showTypePicker.value = false
}

const confirmTypePicker = () => {
  selectedType.value = tempSelectedType.value
  showTypePicker.value = false
}

const openDatePicker = () => {
  if (selectedDate.value) {
    const [year, month, day] = selectedDate.value.split('-')
    tempSelectedYear.value = year
    tempSelectedMonth.value = month
    tempSelectedDay.value = day
  } else {
    tempSelectedYear.value = String(currentYear)
    tempSelectedMonth.value = String(currentMonth).padStart(2, '0')
    tempSelectedDay.value = String(currentDay).padStart(2, '0')
  }

  showDatePicker.value = true
}

const cancelDatePicker = () => {
  showDatePicker.value = false
}

const confirmDatePicker = () => {
  if (!tempSelectedYear.value || !tempSelectedMonth.value || !tempSelectedDay.value) return

  const finalDate = `${tempSelectedYear.value}-${tempSelectedMonth.value}-${tempSelectedDay.value}`

  selectedDate.value = finalDate > todayString ? todayString : finalDate
  showDatePicker.value = false
}

const clearDateFilter = () => {
  selectedDate.value = ''
  showDatePicker.value = false
}

const selectTypeTemp = (option) => {
  tempSelectedType.value = option
}

const selectTempYear = (year) => {
  tempSelectedYear.value = year

  if (!monthOptions.value.includes(tempSelectedMonth.value)) {
    tempSelectedMonth.value = monthOptions.value[monthOptions.value.length - 1] || ''
  }

  if (!dayOptions.value.includes(tempSelectedDay.value)) {
    tempSelectedDay.value = dayOptions.value[dayOptions.value.length - 1] || ''
  }
}

const selectTempMonth = (month) => {
  tempSelectedMonth.value = month

  if (!dayOptions.value.includes(tempSelectedDay.value)) {
    tempSelectedDay.value = dayOptions.value[dayOptions.value.length - 1] || ''
  }
}

const selectTempDay = (day) => {
  tempSelectedDay.value = day
}

const loadMore = () => {
  if (!nextPage.value || loading.value) return

  loading.value = true

  router.get(nextPage.value, {}, {
    preserveState: true,
    preserveScroll: true,
    only: ['transactions'],
    onSuccess: (page) => {
      const newTransactions = page.props.transactions?.data || []
      allTransactions.value = [...allTransactions.value, ...newTransactions]
      nextPage.value = page.props.transactions?.next_page_url || null
    },
    onFinish: () => {
      loading.value = false
    }
  })
}

const handleScroll = () => {
  const scrollTop = window.scrollY || window.pageYOffset
  const windowHeight = window.innerHeight
  const fullHeight = document.documentElement.scrollHeight

  if (scrollTop + windowHeight >= fullHeight - 200) {
    loadMore()
  }
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <PageLayout
    title="Transaction history"
    :showBack="true"
    :backUrl="route('user.account')"
  >
    <div class="transRecord__container">
      <div style="height: 65px;">
        <div class="ar">
          <div class="ar-searchbar">
            <div class="ar-searchbar__selector" @click="openTypePicker">
              <div>
                <span class="ar-searchbar__selector-default">
                  {{ selectedTypeLabel }}
                </span>
                <i class="van-badge__wrapper van-icon van-icon-arrow-down"></i>
              </div>
            </div>

            <div class="ar-searchbar__selector" @click="openDatePicker">
              <div>
                <span class="ar-searchbar__selector-default">
                  {{ selectedDateLabel }}
                </span>
                <i class="van-badge__wrapper van-icon van-icon-arrow-down"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="showTypePicker || showDatePicker"
        class="van-overlay"
        role="button"
        tabindex="0"
        style="z-index: 2002; display: block;"
        @click="showTypePicker = false; showDatePicker = false"
      ></div>

      <div
        v-if="showTypePicker"
        role="dialog"
        tabindex="0"
        class="van-popup van-popup--round van-popup--bottom"
        style="z-index: 2004;"
      >
        <div class="van-picker">
          <div class="van-picker__toolbar">
            <button
              type="button"
              class="van-picker__cancel van-haptics-feedback"
              @click="cancelTypePicker"
            >
              Cancel
            </button>
            <button
              type="button"
              class="van-picker__confirm van-haptics-feedback"
              @click="confirmTypePicker"
            >
              Confirm
            </button>
          </div>

          <div class="van-picker__columns" style="height: 264px;">
            <div class="van-picker-column" style="width: 100%;">
              <ul class="van-picker-column__wrapper picker-list">
                <li
                  v-for="option in typeOptions"
                  :key="option"
                  role="button"
                  tabindex="0"
                  class="van-picker-column__item"
                  :class="{ 'van-picker-column__item--selected': tempSelectedType === option }"
                  style="height: 44px;"
                  @click="selectTypeTemp(option)"
                >
                  <div class="van-ellipsis">
                    {{ option === 'all' ? 'All' : replaceUnderscores(option) }}
                  </div>
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
        class="van-popup van-popup--round van-popup--bottom"
        style="z-index: 2004;"
      >
        <div class="van-picker">
          <div class="van-picker__toolbar">
            <button
              type="button"
              class="van-picker__cancel van-haptics-feedback"
              @click="cancelDatePicker"
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

          <div class="van-picker__columns picker-columns-3" style="height: 264px;">
            <div class="van-picker-column">
              <ul class="van-picker-column__wrapper picker-list">
                <li
                  v-for="year in yearOptions"
                  :key="year"
                  role="button"
                  tabindex="0"
                  class="van-picker-column__item"
                  :class="{ 'van-picker-column__item--selected': tempSelectedYear === year }"
                  style="height: 44px;"
                  @click="selectTempYear(year)"
                >
                  <div class="van-ellipsis">{{ year }}</div>
                </li>
              </ul>
            </div>

            <div class="van-picker-column">
              <ul class="van-picker-column__wrapper picker-list">
                <li
                  v-for="month in monthOptions"
                  :key="month"
                  role="button"
                  tabindex="0"
                  class="van-picker-column__item"
                  :class="{ 'van-picker-column__item--selected': tempSelectedMonth === month }"
                  style="height: 44px;"
                  @click="selectTempMonth(month)"
                >
                  <div class="van-ellipsis">{{ month }}</div>
                </li>
              </ul>
            </div>

            <div class="van-picker-column">
              <ul class="van-picker-column__wrapper picker-list">
                <li
                  v-for="day in dayOptions"
                  :key="day"
                  role="button"
                  tabindex="0"
                  class="van-picker-column__item"
                  :class="{ 'van-picker-column__item--selected': tempSelectedDay === day }"
                  style="height: 44px;"
                  @click="selectTempDay(day)"
                >
                  <div class="van-ellipsis">{{ day }}</div>
                </li>
              </ul>
            </div>

            <div class="van-picker__mask" style="background-size: 100% 110px;"></div>
            <div class="van-hairline-unset--top-bottom van-picker__frame" style="height: 44px;"></div>
          </div>

          <div style="padding: 10px 16px 16px;">
            <button
              type="button"
              class="van-picker__cancel van-haptics-feedback"
              style="width: 100%;"
              @click="clearDateFilter"
            >
              Clear Date
            </button>
          </div>
        </div>
      </div>

      <div class="infiniteScroll">
        <div class="transRecord__container-content">
          <div
            v-for="transaction in filteredTransactions"
            :key="transaction.id"
            class="transRecord__container-content__item"
          >
            <div class="transRecord__container-content__card">
              <div class="transRecord__container-content__card-top">
                <h3>{{ replaceUnderscores(transaction.remark) }}</h3>
              </div>

              <div class="transRecord__container-content__card-mid">
                <div class="line">
                  <div class="left">Detail</div>
                  <div class="right">{{ replaceUnderscores(transaction.remark) }}</div>
                </div>

                <div class="line">
                  <div class="left">Time</div>
                  <div class="right">{{ formatDate(transaction.created_at) }}</div>
                </div>

                <div class="line">
                  <div class="left">Amount</div>
                  <div class="right" :class="amountClass(transaction)">
                    {{ formatSignedMoney(transaction.trx_type, transaction.amount) }}
                  </div>
                </div>
              </div>

              <div class="transRecord__container-content__card-bot">
                <textarea class="textarea" readonly>{{ transaction.details || 'No remark' }}</textarea>
              </div>
            </div>
          </div>
        </div>

        <div v-if="loading" class="infiniteScroll__loading">
          Loading...
        </div>

        <div v-else-if="!nextPage" class="infiniteScroll__loading">
          No more transactions to load
        </div>
      </div>
    </div>
  </PageLayout>
</template>

<style scoped>
.picker-columns-3 {
  display: flex;
  position: relative;
}

.picker-columns-3 .van-picker-column {
  flex: 1;
}

.picker-list {
  max-height: 264px;
  overflow-y: auto;
  padding: 0;
  margin: 0;
  list-style: none;
  scrollbar-width: none;
}

.picker-list::-webkit-scrollbar {
  display: none;
}

.van-picker-column__item {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #c0c4cc;
  font-size: 18px;
  cursor: pointer;
  user-select: none;
}

.van-picker-column__item--selected {
  color: #5b6785;
  font-weight: 700;
}

.van-picker__title {
  font-weight: 700;
}
.van-picker-column{    padding: 60px 0;}
.van-toast {word-break: break-word!important}
.cg-default.van-button {font-weight: 700;font-size: .4rem}
.cg-default.van-button .van-button__icon {height: 100%;display: -webkit-box;display: -webkit-flex;display: flex;-webkit-box-align: center;-webkit-align-items: center;align-items: center}
.cg-default.van-button .van-button__icon>img {height: 80%}
@media (orientation: landscape) {html.landscape {font-size:9vh!important}}
.isAppForcedDownloadEnabled-dialog {z-index: 9999999!important}
.transRecord__container {padding-inline:.32rem;padding-block:0 1.49333rem;font-family: bahnschrift}
.transRecord__container .ar {padding-top: .26667rem;background: var(--bg_color_L1)}
.transRecord__container-content__card {background: var(--bg_color_L2);border-radius: .13333rem;margin-bottom: .34667rem}
.transRecord__container-content__card-top {background: var(--light-main_gradient-color, var(--bg_color_L3));height: 1.17333rem;display: -webkit-box;display: -webkit-flex;display: flex;-webkit-box-align: center;-webkit-align-items: center;align-items: center;border-radius: .13333rem .13333rem 0 0;padding-left: .26667rem}
.transRecord__container-content__card-top h3 {font-family: Inter;font-style: normal;font-weight: 700;font-size: .4rem;color: var(--text_white, var(--text_color_L1))}
.transRecord__container-content__card-mid {padding: .26667rem .13333rem}
.transRecord__container-content__card-mid .line {position: relative;background-color: var(--bg_color_L1);height: .8rem;padding: .21333rem .26667rem;display: -webkit-box;display: -webkit-flex;display: flex;-webkit-box-align: center;-webkit-align-items: center;align-items: center;-webkit-box-pack: justify;-webkit-justify-content: space-between;justify-content: space-between;font-size: .32rem;margin-bottom: .13333rem;border-radius: .08rem}
.transRecord__container-content__card-mid .line .left {color: var(--text_color_L2);z-index: 1}
.transRecord__container-content__card-mid .line .right {color: var(--text_color_L2)}
.transRecord__container-content__card-mid .line .red {color: var(--norm_red-color)}
.transRecord__container-content__card-mid .line .green {color: var(--norm_green-color)}
.transRecord__container-content__card-mid .line:last-child .right {font-size: .48rem}
.transRecord__container-content__card-bot {padding: 0 .13333rem;display: -webkit-box;display: -webkit-flex;display: flex}
.transRecord__container-content__card-bot .textarea {-webkit-box-flex: 1;-webkit-flex: 1;flex: 1;height: 1.6rem;border: .01333rem solid var(--bg_color_L3);border-radius: .13333rem;padding-top: .26667rem;padding-left: .28rem;resize: none;margin-bottom: .29333rem;font-size: .29333rem;background-color: var(--bg_color_L2);color: var(--text_color_L2)}
.transRecord__container-content__card-bot .textarea::-webkit-input-placeholder {color: var(--text_color_L2);font-size: .29333rem}
.transRecord__container-content__card-bot .textarea::placeholder {color: var(--text_color_L2);font-size: .29333rem}
.transRecord__container-empty {margin-top: .66667rem}
</style>