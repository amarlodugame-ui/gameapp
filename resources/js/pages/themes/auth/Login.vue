<script setup>
import { ref } from "vue"
import { useForm, router } from "@/user/inertia"
import AuthLayout from "../layouts/AuthLayout.vue"

import '@/css/profile/page-login-indexvue_vue_type_script_setup_true_lang.css';

import "./../../../../css/user/css/page-login-indexvue_vue_type_style_index_0_scoped.css";
import BlankLayout from "../layouts/BlankLayout.vue";

// ================= TAB =================
const tab = ref("phone")


// ================= FORM =================
const form = useForm({
  countryCode: "+880",
  phone: "",
  email: "",
  password: "",
  remember: false,
})

const submit = () => {
  form.post(route('user.login.submit'))
}


// ================= DROPDOWN =================
const dropdownOpen = ref(false)

const countries = [
  { code: "+880", name: "Bangladesh (বাংলাদেশ)" },
  { code: "+91", name: "India" },
  { code: "+92", name: "Pakistan" },
  { code: "+1", name: "USA" },
  { code: "+971", name: "UAE" },
  { code: "+44", name: "UK" },
]

const selectCountry = (code) => {
  form.countryCode = code
  dropdownOpen.value = false
}


// ================= PASSWORD TOGGLE =================
const showPassword = ref(false)

const goRightButton = () => {
  router.visit(route('user.register'))
}
// ================= REMEMBER =================
const toggleRemember = () => {
  form.remember = !form.remember
}
</script>



<template>
    <BlankLayout>

<div class="navbar main main">
    <div class="navbar-fixed">
        <div class="navbar__content">
            <div class="navbar__content-left">  
                <button type="button" class="auth-right-btn" @click="goRightButton"><i class="van-badge__wrapper van-icon van-icon-arrow-left asdfgawefAWE"></i> Register</button>
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

<div class="login__container" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">

    <!-- ================= HEADER ================= -->
    <div class="login__container-heading">
        <h1 class="login__container-heading__title">Log in</h1>
        <div class="login__container-heading__subTitle">
            <div>Please log in with your phone number or email</div>
            <div>If you forget your password, please contact customer service</div>
        </div>
    </div>

    <!-- ================= TAB ================= -->
    <div class="login_container-tab">
        <div class="tab" :class="{ active: tab === 'phone' }" @click="tab = 'phone'">
            <svg class="svg-icon icon-phone">
                <use xlink:href="#icon-phone"></use>
            </svg>
            <div>phone number</div>
        </div>

        <div class="tab" :class="{ active: tab === 'email' }" @click="tab = 'email'">
            <div>
                <svg class="svg-icon icon-email">
                    <use xlink:href="#icon-email"></use>
                </svg>
            </div>
            <div>Email Login</div>
        </div>
    </div>


    <!-- ================= PHONE LOGIN ================= -->
    <div class="login__container-form">

        <div class="tab-content" :class="{ activecontent: tab === 'phone' }">
            <div class="signIn__container">

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
                                <span>{{ form.countryCode }}</span>
                                <i class="van-badge__wrapper van-icon van-icon-arrow-down"></i>
                            </div>

                            <div class="dropdown__list"
                                 :class="{ active: dropdownOpen }">

                                <div v-for="country in countries"
                                     :key="country.code"
                                     class="dropdown__list-item"
                                     :class="{ active: form.countryCode === country.code }"
                                     @click="selectCountry(country.code)">
                                    <span>{{ country.code }}</span> {{ country.name }}
                                </div>

                            </div>
                        </div>

                        <input type="text"
                               name="userNumber"
                               placeholder="Please enter the phone number"
                               v-model="form.phone">
                    </div>
                </div>

                <!-- PASSWORD -->
                <div class="passwordInput__container">
                    <div class="passwordInput__container-label">
                        <svg class="svg-icon icon-editPswIcon passwordInput__container-label__icon">
                            <use xlink:href="#icon-editPswIcon"></use>
                        </svg>
                        <span>Password</span>
                    </div>

                    <div class="passwordInput__container-input">
                        <input :type="showPassword ? 'text' : 'password'"
                               placeholder="Password"
                               maxlength="32"
                               autocomplete="new-password"
                               v-model="form.password">

                       <img
  :src="showPassword
    ? '/assets/png/eyeVisible-09720f5f.webp'
    : '/assets/png/eyeInvisible-821d9d16.webp'"
  class="eye"
  @click="showPassword = !showPassword"
/>
                    </div>
                </div>

                <!-- REMEMBER -->
                <div>
                    <div role="checkbox"
                         class="van-checkbox"
                         tabindex="0"
                         :aria-checked="form.remember"
                         @click="toggleRemember">

                        <div class="van-checkbox__icon van-checkbox__icon--round"
                             :class="{ 'van-checkbox__icon--checked': form.remember }">
                            <i class="van-badge__wrapper van-icon van-icon-success"></i>
                        </div>

                        <span class="van-checkbox__label">Remember password</span>
                    </div>
                </div>

                <!-- BUTTON -->
                <div class="signIn__container-button">
                    <button class="active"
                            @click="submit"
                            :disabled="form.processing">
                        Log in
                    </button>

                    <button class="register"
                            @click="router.visit(route('user.register'))">
                        Register
                    </button>
                </div>

                <!-- FOOTER SAME AS ORIGINAL -->
                <div class="signIn_footer">
                    <div class="forgetcon" @click="router.visit(route('user.rpwd'))">
                        <svg class="svg-icon icon-clock_b forgetbg">
                            <use xlink:href="#icon-clock_b"></use>
                        </svg>
                        <div class="font24">Forgot password</div>
                    </div>

                    <div class="customcon" @click="router.visit(route('user.promotion.support'))">
                        <svg class="svg-icon icon-serverTicket1 forgetbg">
                            <use xlink:href="#icon-serverTicket1"></use>
                        </svg>
                        <div class="font24">Customer Service</div>
                    </div>
                </div>

            </div>
        </div>


        <!-- ================= EMAIL LOGIN ================= -->

        <div class="tab-content"
             :class="{ activecontent: tab === 'email' }">

            <div class="signIn__container">

                <div class="emailcontainer">
                    <div class="emailinput__container">
                        <div class="emailinput__container-label">
                            <svg class="svg-icon icon-email">
                                <use xlink:href="#icon-email"></use>
                            </svg>
                            <span>Mail</span>
                        </div>

                        <div class="emailinput__container-input">
                            <input type="text"
                                   name="userEmail"
                                   maxlength="250"
                                   placeholder="please input your email"
                                   v-model="form.email">
                        </div>
                    </div>
                </div>

                <div class="passwordInput__container">
                    <div class="passwordInput__container-label">
                        <svg class="svg-icon icon-editPswIcon passwordInput__container-label__icon">
                            <use xlink:href="#icon-editPswIcon"></use>
                        </svg>
                        <span>Password</span>
                    </div>

                    <div class="passwordInput__container-input">
                        <input :type="showPassword ? 'text' : 'password'"
                               placeholder="Password"
                               maxlength="32"
                               autocomplete="new-password"
                               v-model="form.password">

                      <img
  :src="showPassword
    ? '/assets/png/eyeVisible-09720f5f.webp'
    : '/assets/png/eyeInvisible-821d9d16.webp'"
  class="eye"
  @click="showPassword = !showPassword"
/>
                    </div>
                </div>

                <div>
                    <div role="checkbox"
                         class="van-checkbox"
                         tabindex="0"
                         :aria-checked="form.remember"
                         @click="toggleRemember">

                        <div class="van-checkbox__icon van-checkbox__icon--round"
                             :class="{ 'van-checkbox__icon--checked': form.remember }">
                            <i class="van-badge__wrapper van-icon van-icon-success"></i>
                        </div>

                        <span class="van-checkbox__label">Remember password</span>
                    </div>
                </div>

                <div class="signIn__container-button">
                    <button @click="submit"
                            :disabled="form.processing">
                        Log in
                    </button>

                    <button class="register"
                            @click="router.visit(route('user.register'))">
                        Register
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