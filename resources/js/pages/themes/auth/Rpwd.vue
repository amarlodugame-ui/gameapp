<script setup>
import { ref, computed } from "vue"
import { useForm, router } from "@/user/inertia"
import AuthLayout from "../layouts/AuthLayout.vue"

import "./../../../../css/user/css/page-login-indexvue_vue_type_script_setup_true_lang.css";
import "./../../../../css/user/css/page-login-indexvue_vue_type_style_index_0_scoped.css";
import "./../../../../css/user/css/page-register-indexvue_vue_type_script_setup_true_lang.css";
import "./../../../../css/user/css/page-register-indexvue_vue_type_style_index_0_scoped.css";


// ================= FORM =================
const form = useForm({
  phone_code: "+880",
  phone: "",
  password: "",
  password_confirmation: "",
  referral_code: ""
})

const submit = () => {
  form.post(route('user.register.submit'))
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
  form.phone_code = code
  dropdownOpen.value = false
}


// ================= PASSWORD TOGGLE =================
const showPassword = ref(false)
const showConfirmPassword = ref(false)


// ================= CHECKBOX =================
const agree = ref(true)


// ================= VALIDATION UI =================
const passwordMismatch = computed(() => {
  return form.password && form.password_confirmation &&
         form.password !== form.password_confirmation
})
</script>

<template>
<AuthLayout
  class="main"
  :showBack="true"
  backUrl="/"
  logoUrl="/assets/logo/main_logo.png"
  langText="EN"
  langIcon="/assets/png/en-4c6eba8e.webp"
>
<div class="resgister__C" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">

    <div class="resgister__C-heading">
        <h1 class="resgister__C-heading__title">Forgot password</h1>
        <div class="resgister__C-heading__subTitle">
            <span>Please retrieve/change your password through your mobile phone number or email</span>
        </div>
    </div>

    <div class="login_container-tab">
        <div class="tab active">
            <svg class="svg-icon icon-phone">
                <use xlink:href="#icon-phone"></use>
            </svg>
            <div>Phone reset</div>
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

                <div class="tip"></div>

        <div   class="passwordInput__container">
            <div  class="passwordInput__container-label"><svg 
                    class="svg-icon icon-editPswIcon passwordInput__container-label__icon passwordInput__container-label__icon">
                    <use xlink:href="#icon-editPswIcon"></use>
                </svg><span >A new password</span></div>
            <div  class="passwordInput__container-input"><input  type="password"
                    placeholder="A new password" maxlength="15" autocomplete="new-password"><img 
                    src="/assets/png/eyeInvisible-821d9d16.webp" class="eye"></div>
        </div>
        <div  class="register__container-tip" style="display: none;">
            <div  class="tipbg"></div><span >The password must be at least 8 digits
                and must contain letters + numbers</span>
        </div>
        <div   class="passwordInput__container">
            <div  class="passwordInput__container-label"><svg 
                    class="svg-icon icon-editPswIcon passwordInput__container-label__icon passwordInput__container-label__icon">
                    <use xlink:href="#icon-editPswIcon"></use>
                </svg><span >Confirm new password</span></div>
            <div  class="passwordInput__container-input"><input  type="password"
                    placeholder="Confirm new password" maxlength="15" autocomplete="new-password"><img
                     src="/assets/png/eyeInvisible-821d9d16.webp" class="eye"></div>
        </div>
        <div  class="register__container-tips" style="display: none;"><span >Entered
                twice the password does not match!</span></div>
        <div   class="verifyInput__container">
            <div  class="verifyInput__container-label"><svg 
                    class="svg-icon icon-verify">
                    <use xlink:href="#icon-verify"></use>
                </svg><span >Verification Code</span></div>
            <div  class="verifyInput__container-input"><input  type="text"
                    placeholder="Please enter the confirmation code" maxlength="6"><button 
                    class=""><span >Send</span></button></div>
            <div  class="verifyInput__container-tip" style="display: none;"><i 
                    class="van-badge__wrapper van-icon van-icon-warning-o"></i><span
                    >Did not receive verification code?</span><span >Contact
                    customer service</span></div>
        </div>
        <div  class="register__container-remember">
            <div  role="checkbox" class="van-checkbox" tabindex="0" aria-checked="false">
                <div class="van-checkbox__icon van-checkbox__icon--round"><i
                        class="van-badge__wrapper van-icon van-icon-success"></i></div><span
                    class="van-checkbox__label">I have read and agree <span >【Privacy
                        Agreement】</span></span>
            </div>
        </div>
        <div  class="register__container-button"><button >Reset</button></div>
            </div>
        </div>
    </div>

</div>

</AuthLayout>
</template>