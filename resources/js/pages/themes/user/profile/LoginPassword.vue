<script setup>
import { reactive, computed, watch } from 'vue';
import { useForm, usePage } from '@/user/inertia';
import { useLocalToast } from '@/composables/useLocalToast';
import PageLayout from '../../layouts/PageLayout.vue';
import Css from '../account/Css.vue';
import '@/css/profile/page-main-GoogleVerify.css';
import '@/css/profile/page-login-indexvue_vue_type_script_setup_true_lang.css';

const page = usePage();
const { showToast } = useLocalToast();

const form = useForm({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
});

const show = reactive({
    current: false,
    newp: false,
    confirm: false,
});

const passwordsMismatch = computed(() => {
    return (
        form.new_password_confirmation.length > 0 &&
        form.new_password !== form.new_password_confirmation
    );
});

const submit = () => {
    if (passwordsMismatch.value) {
        showToast('Entered twice the password does not match!', 'error', 'cross');
        return;
    }

    form.post(route('user.login-password.update'), {
        preserveScroll: true,

        onSuccess: () => {
            form.reset();
            form.clearErrors();

            showToast('Login password updated successfully.', 'success', 'success');
        },

        onError: (errors) => {
            if (errors.current_password) {
                showToast(errors.current_password, 'error', 'cross');
            } else if (errors.new_password) {
                showToast(errors.new_password, 'error', 'cross');
            } else if (errors.new_password_confirmation) {
                showToast(errors.new_password_confirmation, 'error', 'cross');
            } else {
                showToast('Something went wrong.', 'error', 'cross');
            }
        },
    });
};

watch(
    () => page.props.flash?.success,
    (message) => {
        if (message) {
            showToast(message, 'success', 'success');
        }
    }
);

watch(
    () => page.props.flash?.error,
    (message) => {
        if (message) {
            showToast(message, 'error', 'cross');
        }
    }
);
</script>

<template>
    <Css />
    <PageLayout
        title="Change login password"
        :showBack="true"
        :backUrl="route('user.profile.setting-center')"
    >
        <div
            class="LoginP-container"
            style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;"
        >
            <div class="LoginP-container-form">
                <div class="passwordInput__container">
                    <div class="passwordInput__container-label">
                        <svg class="svg-icon icon-editPswIcon passwordInput__container-label__icon">
                            <use xlink:href="#icon-editPswIcon"></use>
                        </svg>
                        <span>Login password</span>
                    </div>

                    <div class="passwordInput__container-input">
                        <input
                            v-model="form.current_password"
                            :type="show.current ? 'text' : 'password'"
                            placeholder="Login password"
                            maxlength="32"
                            autocomplete="current-password"
                        />
                        <img
                            :src="show.current ? '/assets/png/eyeVisible.webp' : '/assets/png/eyeInvisible-821d9d16.webp'"
                            class="eye"
                            @click="show.current = !show.current"
                        />
                    </div>

                    <div v-if="form.errors.current_password" class="input-error">
                        {{ form.errors.current_password }}
                    </div>
                </div>

                <div class="passwordInput__container">
                    <div class="passwordInput__container-label">
                        <svg class="svg-icon icon-editPswIcon passwordInput__container-label__icon">
                            <use xlink:href="#icon-editPswIcon"></use>
                        </svg>
                        <span>New login password</span>
                    </div>

                    <div class="passwordInput__container-input">
                        <input
                            v-model="form.new_password"
                            :type="show.newp ? 'text' : 'password'"
                            placeholder="New login password"
                            maxlength="15"
                            autocomplete="new-password"
                        />
                        <img
                            :src="show.newp ? '/assets/png/eyeVisible.webp' : '/assets/png/eyeInvisible-821d9d16.webp'"
                            class="eye"
                            @click="show.newp = !show.newp"
                        />
                    </div>

                    <div v-if="form.errors.new_password" class="input-error">
                        {{ form.errors.new_password }}
                    </div>
                </div>

                <div class="passwordInput__container mgb48">
                    <div class="passwordInput__container-label">
                        <svg class="svg-icon icon-editPswIcon passwordInput__container-label__icon">
                            <use xlink:href="#icon-editPswIcon"></use>
                        </svg>
                        <span>Confirm new password</span>
                    </div>

                    <div class="passwordInput__container-input">
                        <input
                            v-model="form.new_password_confirmation"
                            :type="show.confirm ? 'text' : 'password'"
                            placeholder="Confirm new password"
                            maxlength="15"
                            autocomplete="new-password"
                        />
                        <img
                            :src="show.confirm ? '/assets/png/eyeVisible.webp' : '/assets/png/eyeInvisible-821d9d16.webp'"
                            class="eye"
                            @click="show.confirm = !show.confirm"
                        />
                    </div>

                    <div v-if="form.errors.new_password_confirmation" class="input-error">
                        {{ form.errors.new_password_confirmation }}
                    </div>
                </div>

                <div class="LoginP-container-tips" v-show="passwordsMismatch">
                    <span>Entered twice the password does not match!</span>
                </div>

                <div class="LoginP-container-remember">
                    <span>Forgot original login password</span>
                    <i
                        class="van-badge__wrapper van-icon van-icon-arrow"
                        style="color: var(--text_color_L2);"
                    ></i>
                </div>

                <div class="LoginP-container-button">
                    <button
                        type="button"
                        :disabled="form.processing || passwordsMismatch"
                        @click="submit"
                    >
                        {{ form.processing ? 'Saving...' : 'Save changes' }}
                    </button>
                </div>
            </div>
        </div>
    </PageLayout>
</template>

<style scoped>
.eye {
    cursor: pointer;
}

.input-error {
    margin-top: 6px;
    font-size: 12px;
    color: #ff4d4f;
}

button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<style scoped>
.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.userInfo__container{width:100%;height:5.6rem;padding-bottom:2.90667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:end;-webkit-align-items:flex-end;align-items:flex-end;border-radius:0 0 .8rem .8rem;background:var(--light-main_gradient-color, var(--bg_color_L2))}.userInfo__container-nav{color:#fff;font-size:.48rem;position:fixed;top:0;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:center;-webkit-align-items:center;align-items:center;padding:.26667rem .4rem;width:100%;background:var(--main_gradient-color);z-index:99}.userInfo__container-nav span{-webkit-box-flex:1;-webkit-flex:1;flex:1}.userInfo__container-nav-title{text-align:center;color:var(--text_color_L1)}.userInfo__container-setting-center{width:9.36rem;background:var(--bg_color_L3);border-radius:.26667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;padding:.29333rem .4rem;position:relative;top:4.93333rem;z-index:9999}.userInfo__container-setting-center-header{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.userInfo__container-setting-center-header-edit{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:end;-webkit-justify-content:flex-end;justify-content:flex-end;color:var(--text_color_L2)}.userInfo__container-setting-center-header-edit span{font-size:.34667rem;padding-right:.34667rem}.userInfo__container-setting-center-content{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;font-size:.37333rem;color:var(--text_color_L1);padding:.62667rem 0}.userInfo__container-setting-center-content div{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.userInfo__container-setting-center-content h5{color:var(--text_color_L2)}.userInfo__container-setting-center-content span{-webkit-padding-end:.16rem;padding-inline-end:.16rem}.userInfo__container-setting-center-content svg{width:.45333rem;color:var(--main-color)}.userInfo__container .n0{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/0-78e1ab02.webp);background-size:1.33333rem .58667rem}.userInfo__container .n1{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/1-953c5909.webp);background-size:1.33333rem .58667rem}.userInfo__container .n2{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/2-c9b115fb.webp);background-size:1.33333rem .58667rem}.userInfo__container .n3{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/3-fb0c8c43.webp);background-size:1.33333rem .58667rem}.userInfo__container .n4{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/4-3c5b4bba.webp);background-size:1.33333rem .58667rem}.userInfo__container .n5{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/5-5e49bae2.webp);background-size:1.33333rem .58667rem}.userInfo__container .n6{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/6-4ee4b170.webp);background-size:1.33333rem .58667rem}.userInfo__container .n7{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/7-907655eb.webp);background-size:1.33333rem .58667rem}.userInfo__container .n8{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/8-fc7f2447.webp);background-size:1.33333rem .58667rem}.userInfo__container .n9{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/9-5a25583e.webp);background-size:1.33333rem .58667rem}.userInfo__container .n10{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/10-b06f6562.webp);background-size:1.33333rem .58667rem}.userInfo__container .userInfo_dialog_content{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center;background:var(--bg_color_L2);border-radius:.06667rem;padding:.13333rem}.userInfo__container .userInfo_dialog_content input{height:1.17333rem;width:80%;border:none;border-radius:.8rem;background:var(--bg_color_L1);font-size:.37333rem;padding:0 .53333rem;color:var(--text_color_L1)}.userInfo__container .userInfo_dialog_content input::-webkit-input-placeholder{color:var(--text_color_L3)}.userInfo__container .userInfo_dialog_content input::placeholder{color:var(--text_color_L3)}.userInfo__container-content{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;width:100%;padding:0 .45333rem;color:#fff}.userInfo__container-content-wrapper{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start}.userInfo__container-content__avatar{width:1.86667rem;height:1.86667rem;border-radius:50%;margin-right:.2rem;overflow:hidden}.userInfo__container-content__avatar img{width:100%;height:100%;object-fit:cover}.userInfo__container-content__name{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-justify-content:space-around;justify-content:space-around}.userInfo__container-content-nickname{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:center;-webkit-align-items:center;align-items:center;font-size:.4rem;color:#fff}.userInfo__container-content-nickname img{width:1.33333rem;margin:0 .13333rem}.userInfo__container-content-nickname .editPencil{width:.4rem}.userInfo__container-content-uid{width:3.06667rem;border-radius:.53333rem;padding:.06667rem 0;font-size:.29333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-justify-content:space-around;justify-content:space-around;background-color:var(--norm_secondary-color)}.userInfo__container-content-uid img{width:.32rem}.userInfo__container-content-logintime{font-size:.29333rem;color:#fff}.userInfo__container-content-right{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.userInfo__container-content-right img{width:.88rem}.userInfo__container-content-right span{font-size:.32rem;color:#ffee87}.userInfo__container-content-right h5{width:2.13333rem;height:.53333rem;border-radius:.26667rem;text-align:center;font-size:.34667rem}.userInfo__container .info-dialog-content-title{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin-bottom:.32rem;-webkit-column-gap:.21333rem;column-gap:.21333rem}.userInfo__container .info-dialog-content-title svg{width:.64rem;height:.64rem}.userInfo__container .info-dialog-content-title span{font-size:.42667rem;color:var(--text_color_L1)}.userInfo__container .info-dialog-content-title img{width:.64rem;margin-right:.2rem}.userInfo__container .info-dialog-content input{width:8.13333rem;height:1.17333rem;background:var(--bg_color_L1);border-radius:.8rem;border:none;padding-left:.6rem;color:var(--text_color_L1)}.userInfo__container .info-dialog-content input::-webkit-input-placeholder{color:var(--text_color_L3)}.userInfo__container .info-dialog-content input::placeholder{color:var(--text_color_L3)}.userInfo__container .info-dialog-content h4{font-size:.32rem;color:var(--norm_red-color);padding-left:.6rem;padding-top:.29333rem}.userInfo__container .info-dialog-content-avatar-list{padding-top:1.2rem}.userInfo__container .info-dialog-content-avatar-list img{width:2.66667rem}.setting-page-container{height:5.17333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center} .van-icon{font-size:.37333rem}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.van-nav-bar{background:transparent}.van-nav-bar__title,.van-nav-bar__arrow{color:var(--textW)}.icon{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;width:1.06667rem;height:1.06667rem;border-radius:.26667rem}.icon.email svg{min-height:.93333rem}.icon svg{min-width:1.33333rem;min-height:1.33333rem;color:var(--main-color)}.content{position:relative;top:.32rem;padding-top:2.4rem;-webkit-padding-after:.13333rem;padding-block-end:.13333rem}.content .cg-default{margin-top:1.13333rem}.content .content-sub_title{margin-top:.4rem}.content .content-sub_title>div{font-size:.42667rem;color:var(--darkTextW, var(--text_color_L1));font-weight:700;padding-left:.26667rem;margin-bottom:.26667rem;border-left:.08rem solid var(--main-color)}.content .clearbutton{width:7.73333rem;height:1.06667rem;color:var(--textW);font-size:.48rem;font-weight:700;letter-spacing:.05333rem;border-radius:1.06667rem;border:none;background:var(--main_gradient-color)}.content .clearbutton.disable-btn{background:var(--main_gradient-color);box-shadow:var(--BoxShadowColor-3)}.setting_container .setting_container_item{height:1.86667rem;background:var(--bg_color_L2);border-radius:.26667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;padding:.34667rem .28rem;margin-bottom:.4rem}.setting_container .setting_container_item:last-of-type div .icon svg{min-width:.93333rem;min-height:.93333rem}.setting_container .setting_container_item div{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.setting_container .setting_container_item div img{max-width:1.06667rem}.setting_container .setting_container_item div:last-of-type span{color:var(--text_color_L2)}.setting_container .setting_container_item span{font-size:.37333rem;color:var(--text_color_L1);padding:0 .34667rem}.setting_container .setting_container_item span.green{color:var(--norm_green-color)}.setting_container .setting_container_item h5{display:inline-block;font-size:.34667rem;color:var(--text_color_L2);margin-right:.45333rem}.phone_container{background:var(--bg_color_L2);border-radius:.26667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;padding:0 .28rem;margin-bottom:.4rem}.phone_container-item{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;padding:.4rem 0}.phone_container-item-left{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.phone_container-item-left span{font-size:.37333rem;color:var(--text_color_L1);padding-left:.33333rem}.phone_container-item-right{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:end;-webkit-justify-content:flex-end;justify-content:flex-end;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.phone_container-item-right span{font-size:.37333rem;color:var(--text_color_L2);padding-right:.24rem}.phone_container-item-right .phoneright,.phone_container-item-right .emailright{padding-right:.21333rem}.phone_container-item-right .phoneright>div,.phone_container-item-right .emailright>div{text-align:center}.phone_container-item-right .phoneright>div:nth-child(1),.phone_container-item-right .emailright>div:nth-child(1){color:var(--text_color_L2);font-size:.34667rem}.phone_container-item-right .phoneright>div:nth-child(2),.phone_container-item-right .emailright>div:nth-child(2){color:var(--main-color);font-size:.34667rem} .van-icon{font-size:.37333rem}.delAllRq{width:9.06667rem;height:.93333rem;line-height:.93333rem;background-image:var(--main_gradient-color);color:var(--textW);border-radius:.46667rem;margin:.8rem auto;font-size:.4rem;text-align:center} .dialog__container-footer{-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;height:-webkit-fit-content;height:fit-content;margin-top:1.06667rem} .dialog__container-footer .dialogBtn{width:6.66667rem;height:1.06667rem;-webkit-box-flex:0;-webkit-flex:none;flex:none;border-radius:.53333rem;line-height:1.06667rem;background:var(--main_gradient-color);color:var(--textW);font-size:.42667rem;font-weight:700;text-align:center} .dialog__container-footer .dialogBtn+.dialogBtn{border:.01333rem solid var(--main-color);color:var(--main-color);background:var(--bg_color_L2);text-shadow:none;box-shadow:none;font-weight:400} .userInfo__container-content__avatar{border-width:.05333rem}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.bind-container{width:100%}.bind-container .email-container{padding:1.2rem .32rem 4rem}.bind-container .bind-button{margin-top:7.69333rem}.bind-container .bind-button button{width:8.98667rem;height:1.06667rem;background:var(--main_gradient-color);font-size:.4rem;font-weight:700;color:var(--text_color_L4);border-radius:1.06667rem;border:none}.Laundry-Con_tip{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.Laundry-Con_tip .reward{color:var(--text_color_L2);font-size:.4rem}.Laundry-Con_tip .money{color:var(--main-color);font-size:.4rem}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.LoginP-container{height:100%;padding-inline:.53333rem;overflow-y:auto;white-space:nowrap;-webkit-overflow-scrolling:touch}.LoginP-container-tips{margin-top:-.26667rem;margin-bottom:.16rem;padding-left:.22667rem;color:var(--main-color);font-size:.32rem}.LoginP-container-form{position:relative;margin-top:1.33333rem;overflow:hidden}.LoginP-container-form .passwordInput__container{margin-bottom:.69333rem}.LoginP-container-form .mgb48{margin-bottom:.64rem!important}.LoginP-container-remember{-webkit-box-pack:end;-webkit-justify-content:flex-end;justify-content:flex-end;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;font-size:.34667rem;line-height:.34667rem}.LoginP-container-remember span{color:var(--text_color_L2)}.LoginP-container-button{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;width:100%;margin-top:1.77333rem}.LoginP-container-button button{width:7.73333rem;height:1.06667rem;color:var(--text_color_L4);font-size:.48rem;font-weight:700;letter-spacing:.05333rem;border-radius:1.06667rem;border:none;background:var(--main_gradient-color)}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.updateP-container{height:100%;padding-inline:.53333rem;overflow-y:scroll;white-space:nowrap;-webkit-overflow-scrolling:touch;position:relative;margin-top:1.33333rem;overflow:hidden}.updateP-container-label img{width:.36rem;height:.36rem;margin-right:.08rem}.updateP-container-button{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;width:100%;margin-top:1.77333rem}.updateP-container-button button{width:7.73333rem;height:1.06667rem;color:var(--text_color_L4);font-size:.48rem;font-weight:700;letter-spacing:.05333rem;border-radius:1.06667rem;border:none;background:var(--main_gradient-color)}.updateP-container-tips{margin-top:.32rem;padding-left:.22667rem;color:var(--main-color);font-size:.32rem}.Laundry-Con_tip{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.Laundry-Con_tip .reward{color:var(--text_color_L2);font-size:.4rem}.Laundry-Con_tip .money{color:var(--main-color);font-size:.4rem}

</style>
