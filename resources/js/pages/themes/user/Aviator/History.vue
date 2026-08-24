<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import axios from "axios";
import PageLayout from '../../layouts/PageLayout.vue'

const props = defineProps({
  initialBets: {
    type: Array,
    default: () => [],
  },
  pagination: {
    type: Object,
    default: () => ({
      current_page: 1,
      last_page: 1,
      next_page_url: null,
    }),
  },
});

const bets = ref([...props.initialBets]);
const currentPage = ref(props.pagination.current_page || 1);
const lastPage = ref(props.pagination.last_page || 1);
const loading = ref(false);

const hasMore = () => currentPage.value < lastPage.value;

const formatTime = (dateTime) => {
  if (!dateTime) return "--:--";
  const d = new Date(dateTime.replace(" ", "T"));
  return d.toLocaleTimeString("en-GB", {
    hour: "2-digit",
    minute: "2-digit",
    hour12: false,
  });
};

const formatDate = (dateTime) => {
  if (!dateTime) return "-- -- --";
  const d = new Date(dateTime.replace(" ", "T"));
  const day = String(d.getDate()).padStart(2, "0");
  const month = String(d.getMonth() + 1).padStart(2, "0");
  const year = String(d.getFullYear()).slice(-2);
  return `${day}-${month}-${year}`;
};

const rowClass = (bet) => {
  return bet.status === "won" || parseFloat(bet.cashout_amount) > 0
    ? "celebrated"
    : "lost";
};

const loadMore = async () => {
  if (loading.value || !hasMore()) return;

  loading.value = true;

  try {
    const nextPage = currentPage.value + 1;

    const { data } = await axios.get(route("user.aviator.history"), {
      params: { page: nextPage },
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    if (data?.data?.length) {
      bets.value.push(...data.data);
      currentPage.value = data.current_page;
      lastPage.value = data.last_page;
    }
  } catch (error) {
    console.error("Failed to load bet history:", error);
  } finally {
    loading.value = false;
  }
};

const handleScroll = () => {
  const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
  const windowHeight = window.innerHeight;
  const fullHeight = document.documentElement.scrollHeight;

  if (scrollTop + windowHeight >= fullHeight - 180) {
    loadMore();
  }
};

onMounted(() => {
  window.addEventListener("scroll", handleScroll, { passive: true });
});

onBeforeUnmount(() => {
  window.removeEventListener("scroll", handleScroll);
});
</script>

<template>
  <PageLayout
    title="My bet history"
    :showBack="true"
    :backUrl="route('user.aviator.index')"
  >
    <div class="bet-history-page">
      <div class="history-card">
        <div class="modal-header-like">
          <span class="title-text text-uppercase">My bet history</span>
        </div>

        <div class="modal-body-like">
          <div class="bet-list">
            <div class="legend d-flex align-items-center">
              <div class="date pl-1">Date</div>
              <div class="bet">Bet BDT   X</div>
              <!-- <div class="multiplier pl-2"></div> -->
              <div class="cash-out pl-1 pr-2">Cash out BDT</div>
              <div class="tools"></div>
            </div>

            <div class="bet-list-body">
              <div
                v-for="bet in bets"
                :key="bet.id"
                class="bet-item d-flex align-items-center"
                :class="rowClass(bet)"
              >
                <div class="date pl-1">
                  <div style="font-size: 12px;">{{ formatTime(bet.placed_at) }} <br> {{ formatDate(bet.placed_at) }}</div>
                  <!-- <div></div> -->
                </div>

                <div class="bet d-flex">
                  <div  style="display: flex; ">{{ bet.amount }}      <span class="bubble-multiplier" style="margin-left: 6px;">  {{ bet.cashout_multiplier ? `${bet.cashout_multiplier}x` : "—" }}</span></div>
                </div>

                <!-- <div class="multiplier d-flex align-items-center pl-2">
                  <div class=" font-weight-bold">
                  
                  </div>
                </div> -->

                <div class="cash-out pr-2">
                  <span>
                    {{ parseFloat(bet.cashout_amount) > 0 ? bet.cashout_amount : "0.00" }}
                  </span>
                </div>

                <div class="d-flex align-items-center tools pr-1">
                  <div class="tool-circle success-icon" />
                </div>
              </div>

              <div v-if="loading" class="loading-text">
                Loading...
              </div>

              <div v-else-if="!hasMore() && bets.length" class="loading-text end-text">
                No more history
              </div>

              <div v-if="!bets.length && !loading" class="empty-state">
                No bet history found
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </PageLayout>
</template>

<style scoped>
.bet-history-page {padding: 10px 8px 18px;background: #0f1115;min-height: 100%;min-height: 100vh;}
.history-card {width: 100%;border-radius: 10px;overflow: hidden;background: #1a1c22;border: 1px solid #2a2d36;box-shadow: 0 4px 16px rgba(0, 0, 0, 0.32);}
.modal-header-like {background: linear-gradient(180deg, #2b2e35 0%, #23262d 100%);color: #f3f3f3;font-size: 13px;font-weight: 700;letter-spacing: 0.6px;padding: 10px 12px;border-bottom: 1px solid #343844;}
.title-text {display: inline-block;}
.modal-body-like {padding: 8px 6px 10px;background: #17191f;}
.bet-list {width: 100%;}
.legend {color: #8d919b;font-size: 12px;line-height: 1;padding: 4px 0 8px;user-select: none;}
.legend .date,
.bet-item .date {width: 92px;min-width: 92px;}
.legend .bet,
.bet-item .bet {width: 78px;min-width: 78px;}
.legend .multiplier,
.bet-item .multiplier {width: 76px;min-width: 76px;}
.legend .cash-out,
.bet-item .cash-out {flex: 1;min-width: 88px;text-align: right;}
.legend .tools,
.bet-item .tools {width: 46px;min-width: 46px;justify-content: flex-end;}
.bet-list-body {display: flex;flex-direction: column;gap: 6px;}
.bet-item {min-height: 44px;border-radius: 8px;padding: 6px 0;font-size: 13px;color: #ffffff;background: linear-gradient(180deg, #18420f 0%, #12350c 100%);border: 1px solid #54b000;box-shadow: inset 0 0 0 1px rgba(165, 255, 0, 0.06);}
.bet-item.celebrated {background: linear-gradient(180deg, #15470c 0%, #0f3409 100%);border-color: #69c600;}
.bet-item.lost {background: linear-gradient(180deg, #2c1717 0%, #231111 100%);border-color: #5a2b2b;}
.bet-item .date {color: #f4f7ee;font-size: 12px;line-height: 1.05;}
.bet-item .date > div:first-child {font-size: 18px;line-height: 1;margin-bottom: 2px;font-weight: 500;}
.bet-item .bet,
.bet-item .cash-out {color: #ffffff;font-size: 15px;font-weight: 700;}
.bet-item .cash-out {padding-right: 6px;}
.bubble-multiplier {display: inline-flex;align-items: center;justify-content: center;min-width: 52px;padding: 3px 8px;border-radius: 999px;background: #10181f;color: #34b4ff;font-size: 13px;line-height: 1;box-shadow: inset 0 0 0 1px rgba(52, 180, 255, 0.18);}
.tool-circle {width: 16px;height: 16px;border-radius: 50%;position: relative;flex: 0 0 16px;}
.success-icon {border: 1px solid #6ad200;background: rgba(64, 114, 15, 0.18);}
.success-icon::before {content: "";position: absolute;left: 4px;top: 3px;width: 6px;height: 3px;border-left: 2px solid #7ef000;border-bottom: 2px solid #7ef000;transform: rotate(-45deg);}
.share-icon {border: 1px solid #8c949f;background: rgba(255, 255, 255, 0.04);}
.share-icon::before {content: "";position: absolute;width: 6px;height: 6px;border-top: 2px solid #aab1bb;border-right: 2px solid #aab1bb;right: 3px;top: 3px;transform: rotate(45deg);}
.loading-text,
.empty-state {text-align: center;color: #969ca6;padding: 14px 10px 8px;font-size: 13px;}
.end-text {opacity: 0.85;}
.text-uppercase {text-transform: uppercase;}
.d-flex {display: flex;}
.align-items-center {align-items: center;}
.font-weight-bold {font-weight: 700;}
.pl-1 {padding-left: 8px;}
.pl-2 {padding-left: 10px;}
.pr-1 {padding-right: 8px;}
.pr-2 {padding-right: 10px;}
.ml-1 {margin-left: 6px;}
#app{
  background:  #0f1115;
}
</style>