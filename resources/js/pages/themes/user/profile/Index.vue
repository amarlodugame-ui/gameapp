<script setup>
import AuthLayout from '../../layouts/AuthLayout.vue'
import { ref } from 'vue'
import { useForm } from '@/user/inertia'

import '@/css/profile/page-main-GoogleVerify.css'
import '@/css/profile/page-login-indexvue_vue_type_script_setup_true_lang.css'
import { router } from '@/user/inertia'
import AvatarImage from './component/AvatarImage.vue'
import CopyValue from '../../partials/CopyValue.vue'
const props = defineProps({
    userName: String,
    userRefcode: String,
})

const showNicknamePopup = ref(false)

const form = useForm({
    name: props.userName ?? '',
})

const openPopup = () => {
    form.name = props.userName ?? ''
    showNicknamePopup.value = true
    document.body.style.overflow = 'hidden'
}

const closePopup = () => {
    showNicknamePopup.value = false
    document.body.style.overflow = ''
    form.clearErrors()
}

const submitNickname = () => {
    form.post(route('user.profile.update-name'), {
        preserveScroll: true,
        onSuccess: () => {
            closePopup()
        },
    })
}
</script>
<template>
    <AuthLayout title="Settings Center" :showBack="true" :backUrl="route('user.account')">

<div class="userInfo__container setting-page-container"
     style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">

    <div class="userInfo__container-setting-center">
        <div class="userInfo__container-setting-center-header" @click="router.visit(route('user.profile.avatar'))">
            <div class="userInfo__container-content__avatar">
               <AvatarImage />
            </div>
            <div class="userInfo__container-setting-center-header-edit">
                <span>Change avatar</span>
                <i class="van-badge__wrapper van-icon van-icon-arrow" style="color: rgb(136, 136, 136);"></i>
            </div>
        </div>

        <div class="userInfo__container-setting-center-content ar-1px-b" @click="openPopup">
            <h5>Nickname</h5>
            <div>
                <span>{{ userName }}</span>
                <i class="van-badge__wrapper van-icon van-icon-arrow" style="color: rgb(136, 136, 136);"></i>
            </div>
        </div>

        <div class="userInfo__container-setting-center-content">
            <h5>UID</h5>
            <div>
                <CopyValue :value="userRefcode" icon="icon-copy" successMessage="UID copied successfully."/>
            </div>
        </div>
    </div>

    <div class="info-dialog" v-show="showNicknamePopup">
        <div class="van-overlay" style="z-index: 2002;" @click="closePopup"></div>

        <div role="dialog"
             tabindex="0"
             class="van-popup van-popup--center van-dialog"
             style="z-index: 2002;"
             @click.stop>

            <div class="van-dialog__content">
                <div>
                    <div class="info-dialog-header">
                        <span class="info-dialog-header-left"></span>
                        <h5>Change Nickname</h5>
                        <span class="info-dialog-header-right"></span>
                    </div>

                    <div class="info-dialog-content">
                        <div class="info-dialog-content-title">
                            <svg class="svg-icon icon-dialogNickname">
                                <use xlink:href="#icon-dialogNickname"></use>
                            </svg>
                            <span>Nickname</span>
                        </div>

                        <input
                            v-model="form.name"
                            type="text"
                            autocomplete="off"
                            placeholder="Please enter Nickname"
                        >

                        <h4 v-if="form.errors.name">
                            {{ form.errors.name }}
                        </h4>
                    </div>

                    <div class="info-dialog-footer" @click="closePopup">
                        <img src="/assets/png/close-00101b6c.webp">
                    </div>
                </div>
            </div>

            <div class="van-hairline--top van-dialog__footer">
                <button
                    type="button"
                    class="van-button van-button--default van-button--large van-dialog__confirm"
                    @click="submitNickname"
                    :disabled="form.processing"
                >
                    <div class="van-button__content">
                        <span class="van-button__text">
                            {{ form.processing ? 'Saving...' : 'Confirm' }}
                        </span>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="content setting-wrapper">
    <div class="setting-items-wrapper">
        <div class="content-sub_title">
            <div>Security information</div>
        </div>

        <div class="phone_container">
            <div class="phone_container-item" @click="router.visit(route('user.profile.setting-center.login-password'))">
                <div class="phone_container-item-left">
                    <figure class="icon">
                        <svg class="svg-icon icon-editIcon">
                            <use xlink:href="#icon-editIcon"></use>
                        </svg>
                    </figure>
                    <span>Login password</span>
                </div>
                <div class="phone_container-item-right">
                    <span>Edit</span>
                    <i class="van-badge__wrapper van-icon van-icon-arrow" style="color: rgb(136, 136, 136);"></i>
                </div>
            </div>
        </div>

        <div class="setting_container">
            <div class="phone_container-item setting_container_item">
                <div class="phone_container-item-left">
                    <figure class="icon email">
                        <svg class="svg-icon icon-messageIcon">
                            <use xlink:href="#icon-messageIcon"></use>
                        </svg>
                    </figure>
                    <span>Bind mailbox</span>
                </div>
                <div class="phone_container-item-right">
                    <div class="emailright">
                        <div>to bind</div>
                    </div>
                    <i class="van-badge__wrapper van-icon van-icon-arrow" style="color: rgb(136, 136, 136);"></i>
                </div>
            </div>

            <div class="setting_container_item">
                <div>
                    <figure class="icon">
                        <svg class="svg-icon icon-versionUpdateIcon">
                            <use xlink:href="#icon-versionUpdateIcon"></use>
                        </svg>
                    </figure>
                    <span>Updated version</span>
                </div>
                <div>
                    <h5>1.0.9</h5>
                    <i class="van-badge__wrapper van-icon van-icon-arrow" style="color: var(--text_color_L2);"></i>
                </div>
            </div>
        </div>
    </div>
</div>

</AuthLayout>
</template>

<style scoped>
.container{
    overflow:hidden;
}
.content{
    width:-webkit-fill-available;
}
.info-dialog-content{
    margin-left:10px;
}
.userInfo__container-setting-center{
    z-index: 999 !important;
}
.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.userInfo__container{width:100%;height:5.6rem;padding-bottom:2.90667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:end;-webkit-align-items:flex-end;align-items:flex-end;border-radius:0 0 .8rem .8rem;background:var(--light-main_gradient-color, var(--bg_color_L2))}.userInfo__container-nav{color:#fff;font-size:.48rem;position:fixed;top:0;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:center;-webkit-align-items:center;align-items:center;padding:.26667rem .4rem;width:100%;background:var(--main_gradient-color);z-index:99}.userInfo__container-nav span{-webkit-box-flex:1;-webkit-flex:1;flex:1}.userInfo__container-nav-title{text-align:center;color:var(--text_color_L1)}.userInfo__container-setting-center{width:9.36rem;background:var(--bg_color_L3);border-radius:.26667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;padding:.29333rem .4rem;position:relative;top:4.93333rem;z-index:9999}.userInfo__container-setting-center-header{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.userInfo__container-setting-center-header-edit{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:end;-webkit-justify-content:flex-end;justify-content:flex-end;color:var(--text_color_L2)}.userInfo__container-setting-center-header-edit span{font-size:.34667rem;padding-right:.34667rem}.userInfo__container-setting-center-content{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;font-size:.37333rem;color:var(--text_color_L1);padding:.62667rem 0}.userInfo__container-setting-center-content div{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.userInfo__container-setting-center-content h5{color:var(--text_color_L2)}.userInfo__container-setting-center-content span{-webkit-padding-end:.16rem;padding-inline-end:.16rem}.userInfo__container-setting-center-content svg{width:.45333rem;color:var(--main-color)}.userInfo__container .n0{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/0-78e1ab02.webp);background-size:1.33333rem .58667rem}.userInfo__container .n1{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/1-953c5909.webp);background-size:1.33333rem .58667rem}.userInfo__container .n2{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/2-c9b115fb.webp);background-size:1.33333rem .58667rem}.userInfo__container .n3{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/3-fb0c8c43.webp);background-size:1.33333rem .58667rem}.userInfo__container .n4{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/4-3c5b4bba.webp);background-size:1.33333rem .58667rem}.userInfo__container .n5{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/5-5e49bae2.webp);background-size:1.33333rem .58667rem}.userInfo__container .n6{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/6-4ee4b170.webp);background-size:1.33333rem .58667rem}.userInfo__container .n7{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/7-907655eb.webp);background-size:1.33333rem .58667rem}.userInfo__container .n8{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/8-fc7f2447.webp);background-size:1.33333rem .58667rem}.userInfo__container .n9{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/9-5a25583e.webp);background-size:1.33333rem .58667rem}.userInfo__container .n10{width:1.33333rem;height:.58667rem;margin:0 .13333rem;background-image:url(/assets/png/10-b06f6562.webp);background-size:1.33333rem .58667rem}.userInfo__container .userInfo_dialog_content{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center;background:var(--bg_color_L2);border-radius:.06667rem;padding:.13333rem}.userInfo__container .userInfo_dialog_content input{height:1.17333rem;width:80%;border:none;border-radius:.8rem;background:var(--bg_color_L1);font-size:.37333rem;padding:0 .53333rem;color:var(--text_color_L1)}.userInfo__container .userInfo_dialog_content input::-webkit-input-placeholder{color:var(--text_color_L3)}.userInfo__container .userInfo_dialog_content input::placeholder{color:var(--text_color_L3)}.userInfo__container-content{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;width:100%;padding:0 .45333rem;color:#fff}.userInfo__container-content-wrapper{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start}.userInfo__container-content__avatar{width:1.86667rem;height:1.86667rem;border-radius:50%;margin-right:.2rem;overflow:hidden}.userInfo__container-content__avatar img{width:100%;height:100%;object-fit:cover}.userInfo__container-content__name{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-justify-content:space-around;justify-content:space-around}.userInfo__container-content-nickname{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:center;-webkit-align-items:center;align-items:center;font-size:.4rem;color:#fff}.userInfo__container-content-nickname img{width:1.33333rem;margin:0 .13333rem}.userInfo__container-content-nickname .editPencil{width:.4rem}.userInfo__container-content-uid{width:3.06667rem;border-radius:.53333rem;padding:.06667rem 0;font-size:.29333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-justify-content:space-around;justify-content:space-around;background-color:var(--norm_secondary-color)}.userInfo__container-content-uid img{width:.32rem}.userInfo__container-content-logintime{font-size:.29333rem;color:#fff}.userInfo__container-content-right{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.userInfo__container-content-right img{width:.88rem}.userInfo__container-content-right span{font-size:.32rem;color:#ffee87}.userInfo__container-content-right h5{width:2.13333rem;height:.53333rem;border-radius:.26667rem;text-align:center;font-size:.34667rem}.userInfo__container .info-dialog-content-title{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin-bottom:.32rem;-webkit-column-gap:.21333rem;column-gap:.21333rem}.userInfo__container .info-dialog-content-title svg{width:.64rem;height:.64rem}.userInfo__container .info-dialog-content-title span{font-size:.42667rem;color:var(--text_color_L1)}.userInfo__container .info-dialog-content-title img{width:.64rem;margin-right:.2rem}.userInfo__container .info-dialog-content input{width:8.13333rem;height:1.17333rem;background:var(--bg_color_L1);border-radius:.8rem;border:none;padding-left:.6rem;color:var(--text_color_L1)}.userInfo__container .info-dialog-content input::-webkit-input-placeholder{color:var(--text_color_L3)}.userInfo__container .info-dialog-content input::placeholder{color:var(--text_color_L3)}.userInfo__container .info-dialog-content h4{font-size:.32rem;color:var(--norm_red-color);padding-left:.6rem;padding-top:.29333rem}.userInfo__container .info-dialog-content-avatar-list{padding-top:1.2rem}.userInfo__container .info-dialog-content-avatar-list img{width:2.66667rem}.setting-page-container{height:5.17333rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center} .van-icon{font-size:.37333rem}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.van-nav-bar{background:transparent}.van-nav-bar__title,.van-nav-bar__arrow{color:var(--textW)}.icon{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;width:1.06667rem;height:1.06667rem;border-radius:.26667rem}.icon.email svg{min-height:.93333rem}.icon svg{min-width:1.33333rem;min-height:1.33333rem;color:var(--main-color)}.content{position:relative;top:.32rem;padding-top:2.4rem;-webkit-padding-after:.13333rem;padding-block-end:.13333rem}.content .cg-default{margin-top:1.13333rem}.content .content-sub_title{margin-top:.4rem}.content .content-sub_title>div{font-size:.42667rem;color:var(--darkTextW, var(--text_color_L1));font-weight:700;padding-left:.26667rem;margin-bottom:.26667rem;border-left:.08rem solid var(--main-color)}.content .clearbutton{width:7.73333rem;height:1.06667rem;color:var(--textW);font-size:.48rem;font-weight:700;letter-spacing:.05333rem;border-radius:1.06667rem;border:none;background:var(--main_gradient-color)}.content .clearbutton.disable-btn{background:var(--main_gradient-color);box-shadow:var(--BoxShadowColor-3)}.setting_container .setting_container_item{height:1.86667rem;background:var(--bg_color_L2);border-radius:.26667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;padding:.34667rem .28rem;margin-bottom:.4rem}.setting_container .setting_container_item:last-of-type div .icon svg{min-width:.93333rem;min-height:.93333rem}.setting_container .setting_container_item div{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.setting_container .setting_container_item div img{max-width:1.06667rem}.setting_container .setting_container_item div:last-of-type span{color:var(--text_color_L2)}.setting_container .setting_container_item span{font-size:.37333rem;color:var(--text_color_L1);padding:0 .34667rem}.setting_container .setting_container_item span.green{color:var(--norm_green-color)}.setting_container .setting_container_item h5{display:inline-block;font-size:.34667rem;color:var(--text_color_L2);margin-right:.45333rem}.phone_container{background:var(--bg_color_L2);border-radius:.26667rem;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;padding:0 .28rem;margin-bottom:.4rem}.phone_container-item{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;padding:.4rem 0}.phone_container-item-left{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.phone_container-item-left span{font-size:.37333rem;color:var(--text_color_L1);padding-left:.33333rem}.phone_container-item-right{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:end;-webkit-justify-content:flex-end;justify-content:flex-end;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.phone_container-item-right span{font-size:.37333rem;color:var(--text_color_L2);padding-right:.24rem}.phone_container-item-right .phoneright,.phone_container-item-right .emailright{padding-right:.21333rem}.phone_container-item-right .phoneright>div,.phone_container-item-right .emailright>div{text-align:center}.phone_container-item-right .phoneright>div:nth-child(1),.phone_container-item-right .emailright>div:nth-child(1){color:var(--text_color_L2);font-size:.34667rem}.phone_container-item-right .phoneright>div:nth-child(2),.phone_container-item-right .emailright>div:nth-child(2){color:var(--main-color);font-size:.34667rem} .van-icon{font-size:.37333rem}.delAllRq{width:9.06667rem;height:.93333rem;line-height:.93333rem;background-image:var(--main_gradient-color);color:var(--textW);border-radius:.46667rem;margin:.8rem auto;font-size:.4rem;text-align:center} .dialog__container-footer{-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;height:-webkit-fit-content;height:fit-content;margin-top:1.06667rem} .dialog__container-footer .dialogBtn{width:6.66667rem;height:1.06667rem;-webkit-box-flex:0;-webkit-flex:none;flex:none;border-radius:.53333rem;line-height:1.06667rem;background:var(--main_gradient-color);color:var(--textW);font-size:.42667rem;font-weight:700;text-align:center} .dialog__container-footer .dialogBtn+.dialogBtn{border:.01333rem solid var(--main-color);color:var(--main-color);background:var(--bg_color_L2);text-shadow:none;box-shadow:none;font-weight:400} .userInfo__container-content__avatar{border-width:.05333rem}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.bind-container{width:100%}.bind-container .email-container{padding:1.2rem .32rem 4rem}.bind-container .bind-button{margin-top:7.69333rem}.bind-container .bind-button button{width:8.98667rem;height:1.06667rem;background:var(--main_gradient-color);font-size:.4rem;font-weight:700;color:var(--text_color_L4);border-radius:1.06667rem;border:none}.Laundry-Con_tip{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.Laundry-Con_tip .reward{color:var(--text_color_L2);font-size:.4rem}.Laundry-Con_tip .money{color:var(--main-color);font-size:.4rem}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.LoginP-container{height:100%;padding-inline:.53333rem;overflow-y:auto;white-space:nowrap;-webkit-overflow-scrolling:touch}.LoginP-container-tips{margin-top:-.26667rem;margin-bottom:.16rem;padding-left:.22667rem;color:var(--main-color);font-size:.32rem}.LoginP-container-form{position:relative;margin-top:1.33333rem;overflow:hidden}.LoginP-container-form .passwordInput__container{margin-bottom:.69333rem}.LoginP-container-form .mgb48{margin-bottom:.64rem!important}.LoginP-container-remember{-webkit-box-pack:end;-webkit-justify-content:flex-end;justify-content:flex-end;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;font-size:.34667rem;line-height:.34667rem}.LoginP-container-remember span{color:var(--text_color_L2)}.LoginP-container-button{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;width:100%;margin-top:1.77333rem}.LoginP-container-button button{width:7.73333rem;height:1.06667rem;color:var(--text_color_L4);font-size:.48rem;font-weight:700;letter-spacing:.05333rem;border-radius:1.06667rem;border:none;background:var(--main_gradient-color)}.van-toast{word-break:break-word!important}.cg-default.van-button{font-weight:700;font-size:.4rem}.cg-default.van-button .van-button__icon{height:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.cg-default.van-button .van-button__icon>img{height:80%}@media (orientation: landscape){html.landscape{font-size:9vh!important}}.isAppForcedDownloadEnabled-dialog{z-index:9999999!important}.updateP-container{height:100%;padding-inline:.53333rem;overflow-y:scroll;white-space:nowrap;-webkit-overflow-scrolling:touch;position:relative;margin-top:1.33333rem;overflow:hidden}.updateP-container-label img{width:.36rem;height:.36rem;margin-right:.08rem}.updateP-container-button{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;-webkit-box-align:center;-webkit-align-items:center;align-items:center;width:100%;margin-top:1.77333rem}.updateP-container-button button{width:7.73333rem;height:1.06667rem;color:var(--text_color_L4);font-size:.48rem;font-weight:700;letter-spacing:.05333rem;border-radius:1.06667rem;border:none;background:var(--main_gradient-color)}.updateP-container-tips{margin-top:.32rem;padding-left:.22667rem;color:var(--main-color);font-size:.32rem}.Laundry-Con_tip{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.Laundry-Con_tip .reward{color:var(--text_color_L2);font-size:.4rem}.Laundry-Con_tip .money{color:var(--main-color);font-size:.4rem}
</style>