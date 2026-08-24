<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import PageLayout from "../../layouts/PageLayout.vue";

const tabs = [
  { key: "today", label: "Today" },
  { key: "yesterday", label: "Yesterday" },
  { key: "month", label: "This month" },
];

const activeTab = ref("today");
const loading = ref(false);
const subordinates = ref([]);

function maskPhone(phone) {
  if (!phone) return "---";
  const value = String(phone);
  if (value.length <= 6) return value;
  return `${value.slice(0, 3)}****${value.slice(-3)}`;
}

async function fetchSubordinates() {
  loading.value = true;

  try {
    const { data } = await axios.get("/user/promotion/subordinate-data", {
      params: {
        range: activeTab.value,
      },
    });

    subordinates.value = Array.isArray(data?.data) ? data.data : [];
  } catch (error) {
    subordinates.value = [];
    console.error("Failed to load subordinates:", error);
  } finally {
    loading.value = false;
  }
}

async function changeTab(key) {
  if (activeTab.value === key) return;
  activeTab.value = key;
  await fetchSubordinates();
}

onMounted(() => {
  fetchSubordinates();
});
</script>

<template>
  <PageLayout
    title="New subordinates"
    :showBack="true"
    :backUrl="route('user.promotion')"
  >
    <div
      class="subordinate__container"
      style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;"
    >
      <div class="subordinate__container-header">
        <div class="van-tabs van-tabs--card footer-tabBar">
          <div class="van-tabs__wrap">
            <div
              role="tablist"
              class="van-tabs__nav van-tabs__nav--card"
              aria-orientation="horizontal"
              style="border-color: transparent; background: transparent;"
            >
              <div
                v-for="tab in tabs"
                :key="tab.key"
                role="tab"
                class="van-tab van-tab--card"
                :class="{ 'van-tab--active': activeTab === tab.key }"
                :tabindex="activeTab === tab.key ? 0 : -1"
                :aria-selected="activeTab === tab.key"
                @click="changeTab(tab.key)"
                style="border-color: transparent;"
              >
                <span class="van-tab__text van-tab__text--ellipsis">
                  {{ tab.label }}
                </span>
              </div>
            </div>
          </div>
          <div class="van-tabs__content"></div>
        </div>
      </div>

      <div class="infiniteScroll">
        <div
          v-if="subordinates.length > 0"
          class="subordinate__container-content"
        >
          <div
            v-for="item in subordinates"
            :key="item.uid"
            class="subordinate__container-content__item"
          >
            <div>
              <span>{{ maskPhone(item.phone) }}</span>
              <span>UID:{{ item.uid }}</span>
            </div>

            <div>
              <span>Direct subordinates</span>
              <span>{{ item.joined_at }}</span>
            </div>
          </div>

          <div class="no-more-text">No more</div>
        </div>

        <div v-else class="infiniteScroll__loading">
          <div v-if="loading" class="empty__container empty">
            <p>Loading...</p>
          </div>

          <div v-else class="empty__container empty">
            <svg class="svg-icon icon-empty">
              <use xlink:href="#icon-empty"></use>
            </svg>
            <p>No data</p>
          </div>
        </div>
      </div>
    </div>
  </PageLayout>
</template>

<style scoped>
.cg-default.van-button {font-weight: 700;font-size: .4rem;}
.cg-default.van-button .van-button__icon {height: 100%;display: flex;align-items: center;}
.cg-default.van-button .van-button__icon > img {height: 80%;}
@media (orientation: landscape) {html.landscape {font-size: 9vh !important;}}

.isAppForcedDownloadEnabled-dialog {z-index: 9999999 !important;}
.subordinate__container {padding-inline: .32rem;padding-bottom: .26667rem;}
.subordinate__container-header {user-select: none;z-index: 100;color: var(--text_color_L1);padding-top: .32rem;}
.subordinate__container-content {display: flex;flex-direction: column;margin-bottom: .4rem;border-radius: .13333rem;background-color: var(--textW);padding: 1.06667rem .33333rem 0;}
.subordinate__container-content__item {padding: .33333rem 0;border-bottom: 1px solid #f1f1f1;}
.subordinate__container-content__item:last-child {border-bottom: none;}
.subordinate__container-content__item > div {color: var(--text_color_L2);font-weight: 400;font-size: .34667rem;display: flex;justify-content: space-between;margin-top: .13333rem;margin-bottom: .26667rem;}
.subordinate__container-content__item > div:nth-of-type(1) > span:nth-of-type(1) {color: var(--text_color_L2);font-weight: 600;}
.subordinate__container-content__item > div:nth-of-type(1) > span:nth-of-type(2) {color: #9ca3af;}
.subordinate__container-content__item > div:nth-of-type(2) > span:nth-of-type(1) {color: var(--text_color_L1);}
.subordinate__container-content__item > div:nth-of-type(2) > span:nth-of-type(2) {color: var(--text_color_L2);}
.van-tabs {background: var(--bgColor-6);z-index: 100;}
.footer-tabBar .van-tabs__nav--card {height: 1.17333rem;margin: 0;display: flex;justify-content: space-between;gap: .26667rem;border-radius: .13333rem;}
.footer-tabBar .van-tabs__nav--card .van-tab--card {height: 100%;padding: 0 .13333rem;background: var(--darkBg, var(--bg_color_L2)) !important;font-weight: 400;font-size: .34667rem;color: var(--text_color_L2) !important;border-radius: .21333rem;display: flex;align-items: center;justify-content: center;cursor: pointer;}
.footer-tabBar .van-tabs__nav--card .van-tab--card > span {display: flex;align-items: center;justify-content: center;white-space: nowrap;}
.footer-tabBar .van-tabs__nav--card .van-tab--card.van-tab--active {color: var(--text_color_L4) !important;background: var(--main_gradient-color) !important;position: relative;}
.footer-tabBar .van-tabs__nav--card .van-tab--card.van-tab--active:after {content: " ";position: absolute;left: 0;bottom: 0;right: 0;height: .01333rem;}
.no-more-text {text-align: center;color: var(--text_color_L1);font-size: .4rem;padding: .6rem 0;font-weight: 600;}
.empty__container {text-align: center;padding: 1rem 0;}
@media screen and (max-width: 500px) {.subordinate__container-header {max-width: none;}}
</style>