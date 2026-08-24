<script setup>
import { ref } from 'vue';
import { router } from '@/user/inertia';
import PageLayout from '../../layouts/PageLayout.vue';
import '@/css/accoutn/page-home-Messages.css';

const props = defineProps({
    messages: {
        type: Array,
        default: () => [],
    },
});

const showDeletePopup = ref(false);
const selectedId = ref(null);

function openDelete(id) {
    selectedId.value = id;
    showDeletePopup.value = true;
}

function closeDelete() {
    showDeletePopup.value = false;
    selectedId.value = null;
}

function confirmDelete() {
    if (!selectedId.value) return;

    router.delete(route('user.messages.delete', selectedId.value), {
        preserveScroll: true,
        onSuccess: () => {
            closeDelete();
        },
    });
}
</script>

<template>
    <PageLayout
        title="Notification"
        :showBack="true"
        :backUrl="route('user.account')"
    >
        <div
            class="sysMessage__container"
            style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif"
        >
            <div class="infiniteScroll">
                <div
                    v-for="msg in messages"
                    :key="msg.id"
                    class="sysMessage__container-msgWrapper__item"
                >
                    <div class="sysMessage__container-msgWrapper__item-title">
                        <div>
                            <svg class="svg-icon icon-notification svg svg">
                                <use xlink:href="#icon-notification"></use>
                            </svg>
                            <span>{{ msg.title }}</span>
                        </div>

                        <svg
                            class="svg-icon icon-messageGarbage svg svg"
                            @click="openDelete(msg.id)"
                        >
                            <use xlink:href="#icon-messageGarbage"></use>
                        </svg>
                    </div>

                    <div class="sysMessage__container-msgWrapper__item-time">
                        {{ msg.time }}
                    </div>

                    <div class="sysMessage__container-msgWrapper__item-content">
                        {{ msg.content }}
                    </div>
                </div>

                <div class="infiniteScroll__loading">
                    <div v-if="messages.length === 0">No messages</div>
                    <div v-else>No more</div>
                </div>
            </div>
        </div>

        <div
            class="van-overlay"
            style="z-index: 2002"
            :style="{ display: showDeletePopup ? 'block' : 'none' }"
            @click="closeDelete"
        ></div>

        <div
            role="dialog"
            tabindex="0"
            class="van-popup van-popup--center van-dialog"
            aria-labelledby="Warning"
            style="z-index: 2003"
            :style="{ display: showDeletePopup ? 'block' : 'none' }"
        >
            <div class="van-dialog__header">Warning</div>

            <div class="van-dialog__content">
                <div class="van-dialog__message van-dialog__message--has-title">
                    Are you sure to delete this message?
                </div>
            </div>

            <div class="van-hairline--top van-dialog__footer">
                <button
                    type="button"
                    class="van-button van-button--default van-button--large van-dialog__cancel"
                    @click="closeDelete"
                >
                    <div class="van-button__content">
                        <span class="van-button__text">Cancel</span>
                    </div>
                </button>

                <button
                    type="button"
                    class="van-button van-button--default van-button--large van-dialog__confirm van-hairline--left"
                    @click="confirmDelete"
                >
                    <div class="van-button__content">
                        <span class="van-button__text">Confirm</span>
                    </div>
                </button>
            </div>
        </div>
    </PageLayout>
</template>
