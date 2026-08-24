<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue"
import { useForm, router } from "@/user/inertia"
import axios from "axios"
import AuthLayout from "../layouts/AuthLayout.vue"

import "./../../../../css/user/css/page-login-indexvue_vue_type_script_setup_true_lang.css";
import "./../../../../css/user/css/page-login-indexvue_vue_type_style_index_0_scoped.css";
import "./../../../../css/user/css/page-register-indexvue_vue_type_script_setup_true_lang.css";
import "./../../../../css/user/css/page-register-indexvue_vue_type_style_index_0_scoped.css";
import BlankLayout from "../layouts/BlankLayout.vue";

const props = defineProps({
  pageTitle: String,
  referralCode: {
    type: String,
    default: ""
  }
})

const form = useForm({
  phone_code: "+880",
  phone: "",
//   otp: "",
  password: "",
  password_confirmation: "",
  referral_code: props.referralCode || ""
})

const submit = () => {
  form.clearErrors()

  form.transform((data) => ({
    ...data,
    phone: fullPhoneForBackend.value
  })).post(route('user.register.submit'))
}

const dropdownOpen = ref(false)

const countries = [
  { code: "+880", name: "Bangladesh (বাংলাদেশ)" },
]

const selectCountry = (code) => {
  form.phone_code = code
  dropdownOpen.value = false
}

const showPassword = ref(false)
const showConfirmPassword = ref(false)
const agree = ref(true)

const passwordMismatch = computed(() => {
  return form.password && form.password_confirmation &&
         form.password !== form.password_confirmation
})

const smsLoading = ref(false)
const smsMessage = ref("")
const smsError = ref("")
const countdown = ref(0)
let timer = null

const countdownKey = computed(() => {
  return `register_otp_countdown_${normalizedLocalPhone.value || 'default'}`
})

const normalizedLocalPhone = computed(() => {
  let phone = (form.phone || "").trim()
  phone = phone.replace(/[^\d+]/g, "")

  if (phone.startsWith("+880")) {
    phone = "0" + phone.slice(4)
  } else if (phone.startsWith("880")) {
    phone = "0" + phone.slice(3)
  } else if (/^1[3-9]\d{8}$/.test(phone)) {
    phone = "0" + phone
  }

  return phone
})

const fullPhoneForBackend = computed(() => {
  return normalizedLocalPhone.value
})

const canSendOtp = computed(() => {
  return !smsLoading.value &&
         countdown.value === 0 &&
         /^01[3-9]\d{8}$/.test(normalizedLocalPhone.value)
})

const sendButtonText = computed(() => {
  if (smsLoading.value) return "Sending..."
  if (countdown.value > 0) return `${countdown.value}s`
  return "Send"
})

const startCountdown = (seconds) => {
  countdown.value = seconds

  const expireAt = Date.now() + seconds * 1000
  localStorage.setItem(countdownKey.value, String(expireAt))

  if (timer) clearInterval(timer)

  timer = setInterval(() => {
    const left = Math.max(0, Math.ceil((expireAt - Date.now()) / 1000))
    countdown.value = left

    if (left <= 0) {
      clearInterval(timer)
      timer = null
      localStorage.removeItem(countdownKey.value)
    }
  }, 1000)
}

const restoreCountdown = () => {
  const expireAt = localStorage.getItem(countdownKey.value)
  if (!expireAt) return

  const left = Math.max(0, Math.ceil((parseInt(expireAt) - Date.now()) / 1000))
  if (left > 0) {
    startCountdown(left)
  } else {
    localStorage.removeItem(countdownKey.value)
  }
}

const sendOtp = async () => {
  smsMessage.value = ""
  smsError.value = ""
  form.clearErrors("phone")
//   form.clearErrors("otp")

  if (!/^01[3-9]\d{8}$/.test(normalizedLocalPhone.value)) {
    smsError.value = "Please enter a valid Bangladeshi phone number."
    return
  }

  smsLoading.value = true

  try {
    const { data } = await axios.post(route('user.register.sendOtp'), {
      phone: fullPhoneForBackend.value
    })

    smsMessage.value = data.message || "Verification code sent successfully."
    startCountdown(data.seconds_left || 300)
  } catch (error) {
    const response = error?.response

    if (response?.status === 422) {
      smsError.value = response?.data?.message || "Validation failed."
      if (response?.data?.errors) {
        form.setError(response.data.errors)
      }
    } else if (response?.status === 429) {
      smsError.value = response?.data?.message || "Please wait before requesting again."
      if (response?.data?.seconds_left) {
        startCountdown(response.data.seconds_left)
      }
    } else {
      smsError.value = response?.data?.message || "Unable to send verification code."
    }
  } finally {
    smsLoading.value = false
  }
}

onMounted(() => {
  restoreCountdown()
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})
const goRightButton = () => {
  router.visit(route('user.login'))
}
</script>

<template>
  <BlankLayout>

<div class="navbar main main">
    <div class="navbar-fixed">
        <div class="navbar__content">
            <div class="navbar__content-left">  
                <button type="button" class="auth-right-btn" @click="goRightButton"> <i class="van-badge__wrapper van-icon van-icon-arrow-left asdfgawefAWE"></i> Login</button>
            </div>
            <div class="navbar__content-center">
                <div class="headLogo" style="background-image: url('/assets/logo/main_logo.png');"></div>
            </div>
            <div class="navbar__content-right">
                <div class="right"><img class="img" src="/assets/png/en-4c6eba8e.webp"><span class="languageName">EN</span></div>
            </div>
        </div>
    </div>
</div>

<!-- <AuthLayout class="main" :showBack="true" backUrl="/" logoUrl="/assets/logo/main_logo.png" langText="EN" langIcon="/assets/png/en-4c6eba8e.webp"> -->
  <div class="resgister__C" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
    <div class="resgister__C-heading">
      <h1 class="resgister__C-heading__title">Register</h1>
      <div class="resgister__C-heading__subTitle">
        <span>Please register by phone number or email</span>
      </div>
    </div>

    <div class="login_container-tab">
      <div class="tab active">
        <svg class="svg-icon icon-phone">
          <use xlink:href="#icon-phone"></use>
        </svg>
        <div>Register your phone</div>
      </div>
    </div>

    <div class="resgister__C-form">
      <div class="tab-content activecontent">
        <div class="register__container">

          <!-- PHONE -->
          <div class="phoneInput__container">
            <div class="phoneInput__container-label">
              <svg class="svg-icon icon-phone">
                <use xlink:href="#icon-phone"></use>
              </svg>
              <span>Phone number</span>
            </div>

            <div class="phoneInput__container-input">
              <div class="dropdown">
                <div class="dropdown__value" @click="dropdownOpen = !dropdownOpen">
                  <span>{{ form.phone_code }}</span>
                  <i class="van-badge__wrapper van-icon van-icon-arrow-down"></i>
                </div>

                <div class="dropdown__list" :class="{ active: dropdownOpen }">
                  <div
                    v-for="country in countries"
                    :key="country.code"
                    class="dropdown__list-item"
                    :class="{ active: form.phone_code === country.code }"
                    @click="selectCountry(country.code)"
                  >
                    <span>{{ country.code }}</span> {{ country.name }}
                  </div>
                </div>
              </div>

              <input
                type="text"
                name="userNumber"
                placeholder="Please enter the phone number"
                v-model="form.phone"
              >
            </div>
          </div>

<div
  v-if="form.errors.phone || smsError || smsMessage"
  class="register__container-tip"
  :style="{ color: (!form.errors.phone && !smsError && smsMessage) ? 'green' : 'red' }"
>
  {{ form.errors.phone || smsError || smsMessage }}
</div>
          <!-- PASSWORD -->
          <div class="passwordInput__container">
            <div class="passwordInput__container-label">
              <svg class="svg-icon icon-editPswIcon passwordInput__container-label__icon">
                <use xlink:href="#icon-editPswIcon"></use>
              </svg>
              <span>Set password</span>
            </div>

            <div class="passwordInput__container-input">
              <input
                :type="showPassword ? 'text' : 'password'"
                placeholder="Set password"
                maxlength="30"
                autocomplete="new-password"
                v-model="form.password"
              >
              <img
                :src="showPassword ? '/assets/png/eyeVisible-09720f5f.webp' : '/assets/png/eyeInvisible-821d9d16.webp'"
                class="eye"
                @click="showPassword = !showPassword"
              />
            </div>
          </div>

          <div class="register__container-tip" v-show="form.password.length > 0 && form.password.length < 8">
            <div class="tipbg"></div>
            <span>The password must be at least 8 characters and contain letters + numbers</span>
          </div>
          <div class="register__container-tip" v-if="form.errors.password">{{ form.errors.password }}</div>

          <!-- CONFIRM PASSWORD -->
          <div class="passwordInput__container">
            <div class="passwordInput__container-label">
              <svg class="svg-icon icon-editPswIcon passwordInput__container-label__icon">
                <use xlink:href="#icon-editPswIcon"></use>
              </svg>
              <span>Confirm password</span>
            </div>

            <div class="passwordInput__container-input">
              <input
                :type="showConfirmPassword ? 'text' : 'password'"
                placeholder="Confirm password"
                maxlength="30"
                autocomplete="new-password"
                v-model="form.password_confirmation"
              >
              <img
                :src="showConfirmPassword ? '/assets/png/eyeVisible-09720f5f.webp' : '/assets/png/eyeInvisible-821d9d16.webp'"
                class="eye"
                @click="showConfirmPassword = !showConfirmPassword"
              />
            </div>
          </div>

          <div class="register__container-tips" v-show="passwordMismatch">
            <span>Entered twice the password does not match!</span>
          </div>

          <!-- REFERRAL -->
          <div class="register__container-invitation">
            <div class="register__container-invitation__label">
              <svg class="svg-icon icon-invitation">
                <use xlink:href="#icon-invitation"></use>
              </svg>
              <span>Invite code</span>
            </div>
            <div class="register__container-invitation__input">
              <input
                type="text"
                autocomplete="off"
                placeholder="Please enter the invitation code"
                maxlength="20"
                v-model="form.referral_code"
              >
            </div>
          </div>
          <div class="register__container-tip" v-if="form.errors.referral_code">{{ form.errors.referral_code }}</div>

          <!-- OTP -->
          <!--<div class="verifyInput__container">-->
          <!--  <div class="verifyInput__container-label" style="margin-top: 10px;">-->
          <!--    <svg class="svg-icon icon-verify">-->
          <!--      <use xlink:href="#icon-verify"></use>-->
          <!--    </svg>-->
          <!--    <span>Verification Code</span>-->
          <!--  </div>-->

          <!--  <div class="verifyInput__container-input">-->
          <!--    <input-->
          <!--      type="text"-->
          <!--      placeholder="Please enter the confirmation code"-->
          <!--      maxlength="6"-->
          <!--      v-model="form.otp"-->
          <!--    >-->
          <!--    <button-->
          <!--      type="button"-->
          <!--      :disabled="!canSendOtp"-->
          <!--      @click="sendOtp"-->
          <!--    >-->
          <!--      <span>{{ sendButtonText }}</span>-->
          <!--    </button>-->
          <!--  </div>-->

          <!--  <div class="verifyInput__container-tip" v-if="countdown > 0">-->
          <!--    <span>Resend available in {{ countdown }} seconds</span>-->
          <!--  </div>-->

          <!--  <div class="register__container-tip" v-if="form.errors.otp">{{ form.errors.otp }}</div>-->
          <!--</div>-->

          <!-- AGREEMENT -->
          <div class="register__container-remember">
            <div role="checkbox" class="van-checkbox" tabindex="0" :aria-checked="agree" @click="agree = !agree">
              <div class="van-checkbox__icon van-checkbox__icon--round" :class="{ 'van-checkbox__icon--checked': agree }">
                <i class="van-badge__wrapper van-icon van-icon-success"></i>
              </div>
              <span class="van-checkbox__label">I have read and agree <span>【Privacy Agreement】</span></span>
            </div>
          </div>

          <!-- BUTTON -->
          <div class="register__container-button">
            <button
              @click="submit"
              :disabled="form.processing || passwordMismatch || !agree"
            >
              Register
            </button>

            <button class="login" type="button" @click="router.visit('/user/login')">
              <div class="account">I have an account</div>
              <div class="loginin">Login</div>
            </button>
          </div>

        </div>
      </div>
    </div>
  </div>
<!-- </AuthLayout> -->
 </BlankLayout>
</template>
<style scoped>

.auth-right-btn {
    height: 32px;
    padding: 0;
    border: none;
    border-radius: 999px;
    background: transparent;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;    display: flex;
    justify-content: center;
    align-items: center;
}
.asdfgawefAWE{
    font-size: 16px !important;
    margin-right: 5px !important;
}
</style>