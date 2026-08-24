<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@/user/inertia'
import PageLayout from '../../layouts/PageLayout.vue'

const page = usePage()
const walletOptions = computed(() => page.props.walletOptions ?? ['BKASH', 'NAGAD'])

const showPopup = ref(false)
const selectedValue = ref(walletOptions.value[0] ?? 'BKASH')
const type = ref('')
const fullName = ref('')
const account = ref('')

const isFormValid = computed(() => {
  return (
    type.value.trim() !== '' &&
    fullName.value.trim() !== '' &&
    account.value.trim() !== ''
  )
})

const openPopup = () => {
  showPopup.value = true
  document.body.classList.add('van-overflow-hidden')
}

const closePopup = () => {
  showPopup.value = false
  document.body.classList.remove('van-overflow-hidden')
}

const selectOption = (item) => {
  selectedValue.value = item
}

const confirmSelection = () => {
  type.value = selectedValue.value
  closePopup()
}

const saveData = () => {
  if (!isFormValid.value) return

  router.post(route('user.wallet.addWellet.store'), {
    type: type.value,
    full_name: fullName.value,
    account: account.value,
  })
}
</script>

<template>
  <PageLayout class="main" title="Wallet type Payment method" :showBack="true" :backUrl="route('user.wallet.Withdraw')">
    <div class="addtype4_C">
      <div class="addtype4_C-header">E-Wallet</div>

      <div class="addtype4_C-title">Choose type</div>
      <div class="van-cell van-field addtype4-input" @click="openPopup">
        <div class="van-cell__value van-field__value">
          <div class="van-field__body">
            <input v-model="type" type="text" class="van-field__control" readonly placeholder="Please choose"/>
            <div class="van-field__right-icon">&#9662;</div>
          </div>
        </div>
      </div>

      <div class="addtype4_C-title">Full name</div>
      <div class="van-cell van-field addtype4-input">
        <div class="van-cell__value van-field__value">
          <div class="van-field__body">
            <input v-model="fullName" type="text" class="van-field__control" placeholder="Please enter Full name"/>
          </div>
        </div>
      </div>

      <div class="addtype4_C-title">Account</div>
      <div class="van-cell van-field addtype4-input">
        <div class="van-cell__value van-field__value">
          <div class="van-field__body">
            <input v-model="account" type="text" class="van-field__control" placeholder="Please enter Account"/>
          </div>
        </div>
      </div>

      <div class="sumbitBtn" :class="{ disable: !isFormValid }" @click="saveData">
        Save
      </div>
    </div>

    <div v-if="showPopup" class="van-overlay" @click="closePopup"></div>

    <div v-if="showPopup" role="dialog" class="van-popup">
      <div class="van-picker">
        <div class="van-picker__toolbar">
          <button type="button" class="van-picker__cancel" @click="closePopup">
            Cancel
          </button>
          <button type="button" class="van-picker__confirm" @click="confirmSelection">
            Confirm
          </button>
        </div>

        <div class="van-picker__columns">
          <div class="van-picker-column">
            <ul class="van-picker-column__wrapper">
              <li v-for="item in walletOptions" :key="item" role="button" tabindex="0" class="van-picker-column__item" :class="{ 'van-picker-column__item--selected': selectedValue === item }" @click="selectOption(item)">
                <div class="van-ellipsis">{{ item }}</div>
              </li>
            </ul>
          </div>
          <div class="van-picker__mask"></div>
          <div class="van-picker__frame"></div>
        </div>
      </div>
    </div>
  </PageLayout>
</template>

<style scoped>
.addtype4_C {max-width: 480px;margin: 0 auto;min-height: 100vh;background: #fff;padding-bottom: 24px;}
.addtype4_C-header {font-size: 22px;font-weight: 700;padding: 20px 16px 10px;}
.addtype4_C-title {font-size: 14px;font-weight: 600;padding: 16px 16px 8px;color: #333;}
.addtype4-input {margin: 0 16px;background: #f8f8f8;border: 1px solid #ddd;border-radius: 12px;min-height: 48px;display: flex;align-items: center;padding: 0 14px;    width: -webkit-fill-available;}
.van-cell__value,.van-field__value,.van-field__body {width: 100%;}
.van-field__body {display: flex;align-items: center;justify-content: space-between;gap: 10px;}
.van-field__control {width: 100%;border: none;background: transparent;outline: none;font-size: 15px;color: #222;}
.van-field__control::placeholder {color: #999;}
.van-field__right-icon {font-size: 16px;color: #666;cursor: pointer;user-select: none;}
.sumbitBtn {margin: 28px 16px 0;height: 48px;border-radius: 12px;background: #16a34a;color: #fff;display: flex;align-items: center;justify-content: center;font-size: 16px;font-weight: 700;cursor: pointer;transition: 0.2s;}
.sumbitBtn.disable {background: #bdbdbd;cursor: not-allowed;opacity: 0.85;}
.van-overlay {position: fixed;inset: 0;background: rgba(0, 0, 0, 0.45);z-index: 2001;}
.van-popup {position: fixed;left: 0;right: 0;bottom: 0;background: #fff;border-radius: 18px 18px 0 0;z-index: 2002;animation: popupUp 0.22s ease;max-width: 480px;margin: 0 auto;}
@keyframes popupUp {from {transform: translateY(100%);opacity: 0.6;}to {transform: translateY(0);opacity: 1;}}
.van-picker__toolbar {display: flex;justify-content: space-between;align-items: center;padding: 14px 16px;border-bottom: 1px solid #eee;}
.van-picker__cancel,.van-picker__confirm {border: none;background: none;font-size: 15px;cursor: pointer;padding: 8px 6px;}
.van-picker__cancel {color: #888;}
.van-picker__confirm {color: #16a34a;font-weight: 700;}
.van-picker__columns {position: relative;height: 220px;overflow: hidden;}
.van-picker-column__wrapper {list-style: none;padding: 66px 0;}
.van-picker-column{    overflow-y: scroll;}
.van-picker-column__item {height: 44px;display: flex;align-items: center;justify-content: center;font-size: 16px;cursor: pointer;color: #666;transition: 0.15s;}
.van-picker-column__item--selected {color: #111;font-weight: 700;background: #f7f7f7;}
.van-picker__frame {position: absolute;left: 16px;right: 16px;top: 88px;height: 44px;pointer-events: none;border-radius: 10px;}
.van-picker__mask {position: absolute;inset: 0;pointer-events: none;background: linear-gradient(to bottom,rgba(255, 255, 255, 0.95),rgba(255, 255, 255, 0.6) 25%,rgba(255, 255, 255, 0) 40%,rgba(255, 255, 255, 0) 60%,rgba(255, 255, 255, 0.6) 75%,rgba(255, 255, 255, 0.95));}
.message {margin: 14px 16px 0;font-size: 14px;color: #16a34a;}
</style>