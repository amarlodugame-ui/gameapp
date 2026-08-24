<script setup>
import { computed, ref, watch, onBeforeUnmount } from "vue";

const props = defineProps({
  round: { type: Object, default: null },
  roundState: { type: String, default: "betting" },
  liveMultiplier: { type: Number, default: 1 },
  avatarBasePath: {
    type: String,
    default: "/assets/plane/avtar",
  },
  avatarCount: {
    type: Number,
    default: 72,
  },
});

const emit = defineEmits(["active-count"]);
const betsTab = ref("all");
const usersbetsAllBets = ref([]);
const usersbetsPreviousBets = ref([]);
const usersbetsTopBets = ref([]);

const totalBetsCount = ref(0);
const activeBetsCount = ref(0);
const roundTargetCount = ref(0);

const VISIBLE_ROWS_LIMIT = 30;

const getVisibleRowsLimit = () => VISIBLE_ROWS_LIMIT;


const pendingLiveRows = ref([]);
const feedTimer = ref(null);
const pendingCashoutQueue = ref([]);
const cashoutTimer = ref(null);


const lastPreparedRoundKey = ref(null);
const lastFinalizedRoundKey = ref(null);

const randomInt = (min, max) =>
  Math.floor(Math.random() * (max - min + 1)) + min;

const BETTING_MIN_BETS = 3000;
const BETTING_MAX_BETS = 4000;

const getRoundTargetBetCount = () => {
  return randomInt(BETTING_MIN_BETS, BETTING_MAX_BETS);
};

const randomFloat = (min, max, decimals = 2) => {
  const factor = Math.pow(10, decimals);
  return Math.round((Math.random() * (max - min) + min) * factor) / factor;
};
const pickTargetCashoutMultiplier = () => {
  const r = Math.random();
  if (r < 0.45) return randomFloat(1.08, 1.80, 2);
  if (r < 0.78) return randomFloat(1.81, 3.50, 2);
  if (r < 0.94) return randomFloat(3.51, 8.00, 2);
  return randomFloat(8.01, 25.00, 2);
};
const formatCompactMoney = (value) => {
  const n = Number(value || 0);
  return n.toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const formatMultiplier = (value) => Number(value || 1).toFixed(2);

const sampleNames = [
  "Rahim", "Karim", "Sabbir", "Jony", "Rasel", "Hasan", "Nayeem", "Tanmoy",
  "Bappy", "Rifat", "Shuvo", "Milon", "Imran", "Sakib", "Arif", "Sohel",
  "Robin", "Rakib", "Nadim", "Biplob", "Pavel", "Rony", "Mahi", "Ayan",
];

const maskName = (name) => {
  if (!name || name.length < 3) return name;
  return `${name.slice(0, 1)}***${name.slice(-1)}`;
};

const makeusersbetsPlayerName = () => {
  const name = sampleNames[randomInt(0, sampleNames.length - 1)];
  const suffix = randomInt(1, 9);
  return maskName(`${name}${suffix}`);
};

const makeAvatarPath = () => {
  const index = randomInt(1, props.avatarCount);
  return `${props.avatarBasePath}/av-${index}.png`;
};


const STORAGE_PREFIX = "aviator_live_bets_round_";

const getStorageKey = () => {
  const key = currentRoundKey.value;
  return key ? `${STORAGE_PREFIX}${key}` : null;
};

const cleanupOldRoundStorage = () => {
  const currentKey = getStorageKey();

  Object.keys(localStorage).forEach((key) => {
    if (key.startsWith(STORAGE_PREFIX) && key !== currentKey) {
      localStorage.removeItem(key);
    }
  });
};

const saveRoundSnapshot = () => {
  const storageKey = getStorageKey();
  if (!storageKey) return;

  const payload = {
    roundKey: currentRoundKey.value,
    roundState: props.roundState,

    totalBetsCount: totalBetsCount.value,
    activeBetsCount: activeBetsCount.value,
    roundTargetCount: roundTargetCount.value,

    usersbetsAllBets: usersbetsAllBets.value.slice(0, VISIBLE_ROWS_LIMIT),
    usersbetsPreviousBets: usersbetsPreviousBets.value.slice(0, 30),
    usersbetsTopBets: usersbetsTopBets.value.slice(0, 12),

    savedAt: Date.now(),
  };

  try {
    localStorage.setItem(storageKey, JSON.stringify(payload));
    cleanupOldRoundStorage();
  } catch (error) {
    console.warn("Live bets storage failed", error);
  }
};

const loadRoundSnapshot = () => {
  const storageKey = getStorageKey();
  if (!storageKey) return false;

  try {
    const raw = localStorage.getItem(storageKey);
    if (!raw) return false;

    const payload = JSON.parse(raw);

    if (String(payload.roundKey) !== String(currentRoundKey.value)) {
      return false;
    }

    totalBetsCount.value = Number(payload.totalBetsCount || 0);
    activeBetsCount.value = Number(payload.activeBetsCount || 0);
    roundTargetCount.value = Number(payload.roundTargetCount || getRoundTargetBetCount());

    usersbetsAllBets.value = Array.isArray(payload.usersbetsAllBets)
      ? payload.usersbetsAllBets
      : [];

    usersbetsPreviousBets.value = Array.isArray(payload.usersbetsPreviousBets)
      ? payload.usersbetsPreviousBets
      : [];

    usersbetsTopBets.value = Array.isArray(payload.usersbetsTopBets)
      ? payload.usersbetsTopBets
      : [];

    syncTopFromAll();

    return true;
  } catch (error) {
    console.warn("Live bets restore failed", error);
    return false;
  }
};


const currentRoundKey = computed(() => {
  return props.round?.id || props.round?.round_no || null;
});

const safeLiveCap = computed(() => {
  const live = Number(props.liveMultiplier || 1);
  return Math.max(1.01, Number(live.toFixed(2)));
});

const safeCrashCap = computed(() => {
  const crash = Number(props.round?.crash_multiplier || props.liveMultiplier || 1);
  return Math.max(1.01, Number(crash.toFixed(2)));
});

const syncTopFromAll = () => {
  usersbetsTopBets.value = [...usersbetsAllBets.value]
    .filter((row) => row.status === "cashed_out")
    .sort((a, b) => b.win_amount - a.win_amount)
    .slice(0, 12);
};

const makeLiveRow = () => {
  const betAmount = randomFloat(120, 9500, 2);

  return {
    id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    avatar: makeAvatarPath(),
    player: makeusersbetsPlayerName(),
    bet_amount: betAmount,
    multiplier: 0,
    win_amount: 0,
    status: "pending", // pending | cashed_out | lost
    target_cashout_multiplier: pickTargetCashoutMultiplier(),
  };
};
const generateLivePool = ({
  count = 14,
} = {}) => {
  const rows = [];

  for (let i = 0; i < count; i++) {
    rows.push(makeLiveRow());
  }

  return rows.sort((a, b) => b.bet_amount - a.bet_amount);
};

const generateFinalizedRows = ({
  count = 12,
  crashMultiplier = 1.5,
} = {}) => {
  const crash = Math.max(1.05, Number(crashMultiplier || 1.5));
  const rows = [];

  for (let i = 0; i < count; i++) {
    const betAmount = randomFloat(120, 9500, 2);
    const shouldLose = Math.random() < 0.30;

    let multiplier = 0;
    let status = "cashed_out";
    let winAmount = 0;

    if (shouldLose) {
      status = "lost";
      multiplier = 0;
      winAmount = 0;
    } else {
      multiplier = randomFloat(1.01, Math.max(1.01, crash - 0.01), 2);
      winAmount = Number((betAmount * multiplier).toFixed(2));
    }

    rows.push({
      id: `${Date.now()}-${i}-${Math.random().toString(36).slice(2, 8)}`,
      avatar: makeAvatarPath(),
      player: makeusersbetsPlayerName(),
      bet_amount: betAmount,
      multiplier,
      win_amount: winAmount,
      status,
      target_cashout_multiplier: multiplier || randomFloat(1.10, Math.max(1.10, crash - 0.01), 2),
    });
  }

  return rows.sort((a, b) => b.bet_amount - a.bet_amount);
};

const stopFeed = () => {
  if (feedTimer.value) {
    clearInterval(feedTimer.value);
    feedTimer.value = null;
  }
};
const stopCashoutFeed = () => {
  if (cashoutTimer.value) {
    clearInterval(cashoutTimer.value);
    cashoutTimer.value = null;
  }

  pendingCashoutQueue.value = [];
};

const startCashoutFeed = () => {
  if (cashoutTimer.value) return;

  cashoutTimer.value = setInterval(() => {
    if (props.roundState !== "running") {
      stopCashoutFeed();
      return;
    }

    if (activeBetsCount.value <= 0) {
      stopCashoutFeed();
      return;
    }

    // যারা cashout করবে, count থেকে কমবে
    const cashoutCount = Math.min(activeBetsCount.value, randomInt(25, 90));
    activeBetsCount.value -= cashoutCount;

    const cap = safeLiveCap.value;

    // visible ৩০টার মধ্যে কয়েকটা cashout দেখাবে
    let changed = 0;

    usersbetsAllBets.value = usersbetsAllBets.value.map((row) => {
      if (changed >= 3) return row;
      if (row.status !== "pending") return row;

      changed++;

      const multiplier = Number(
        Math.min(row.target_cashout_multiplier || cap, cap).toFixed(2)
      );

      return {
        ...row,
        status: "cashed_out",
        multiplier,
        win_amount: Number((Number(row.bet_amount) * multiplier).toFixed(2)),
      };
    });

    syncTopFromAll();
    saveRoundSnapshot();
  }, 500);
};
const appendOneOrMoreRows = () => {
  if (!roundTargetCount.value) return;

  if (totalBetsCount.value >= roundTargetCount.value) {
    stopFeed();
    return;
  }

  let addCount = 1;

  if (props.roundState === "betting") {
    addCount = randomInt(180, 350);
  } else if (props.roundState === "running") {
    addCount = randomInt(20, 60);
  }

  const remaining = roundTargetCount.value - totalBetsCount.value;
  const finalAdd = Math.min(addCount, remaining);

  totalBetsCount.value += finalAdd;
  activeBetsCount.value += finalAdd;

  // table এ শুধু ৩০টা row রাখবে
  const visibleAdd = props.roundState === "betting" ? randomInt(2, 5) : randomInt(1, 3);

  for (let i = 0; i < visibleAdd; i++) {
    usersbetsAllBets.value.unshift(makeLiveRow());
  }

  usersbetsAllBets.value = usersbetsAllBets.value
    .slice(0, VISIBLE_ROWS_LIMIT)
    .sort((a, b) => b.bet_amount - a.bet_amount);

  syncTopFromAll();

  if (props.roundState === "running") {
    refreshLiveRowsToCurrentCap();
  }

  saveRoundSnapshot();
};
const startLiveFeed = () => {
  stopFeed();

  const delay = props.roundState === "betting" ? 220 : 350;

  feedTimer.value = setInterval(() => {
    if (props.roundState === "crashed" || props.roundState === "completed") {
      stopFeed();
      return;
    }

    appendOneOrMoreRows();

    if (totalBetsCount.value >= roundTargetCount.value) {
      stopFeed();
    }
  }, delay);
};

const rebuildusersbetsRoundBets = () => {
  stopFeed();
  stopCashoutFeed();

  usersbetsAllBets.value = [];
  usersbetsPreviousBets.value = [];
  usersbetsTopBets.value = [];

  roundTargetCount.value = randomInt(2200, 3500);

  totalBetsCount.value = randomInt(300, 700);
  activeBetsCount.value = totalBetsCount.value;

  for (let i = 0; i < VISIBLE_ROWS_LIMIT; i++) {
    usersbetsAllBets.value.push(makeLiveRow());
  }

  usersbetsAllBets.value = usersbetsAllBets.value.sort(
    (a, b) => b.bet_amount - a.bet_amount
  );

  syncTopFromAll();
  startLiveFeed();
  saveRoundSnapshot();
};

const refreshLiveRowsToCurrentCap = () => {
  if (props.roundState !== "running") return;
  if (activeBetsCount.value <= 0) return;

  startCashoutFeed();
};
const finalizeusersbetsRoundBets = () => {
  stopFeed();
  stopCashoutFeed();

  const finalized = usersbetsAllBets.value.map((row) => {
    if (row.status === "cashed_out") {
      return row;
    }

    return {
      ...row,
      status: "lost",
      multiplier: 0,
      win_amount: 0,
    };
  });

  const extraFinalized = generateFinalizedRows({
    count: Math.max(0, randomInt(2, 6)),
    crashMultiplier: safeCrashCap.value,
  });

  usersbetsPreviousBets.value = [...finalized, ...extraFinalized]
    .sort((a, b) => b.bet_amount - a.bet_amount)
    .slice(0, 16);

  usersbetsTopBets.value = [...usersbetsPreviousBets.value]
    .filter((row) => row.status === "cashed_out")
    .sort((a, b) => b.win_amount - a.win_amount)
    .slice(0, 12);

  saveRoundSnapshot();
};

const usersbetsCurrentTabRows = computed(() => {
  if (betsTab.value === "previous") return usersbetsPreviousBets.value;
  if (betsTab.value === "top") return usersbetsTopBets.value;
  return usersbetsAllBets.value;
});

const usersbetsTotalWinAmount = computed(() => {
  return usersbetsCurrentTabRows.value.reduce(
    (sum, row) => sum + Number(row.win_amount || 0),
    0
  );
});

const activePlayersCount = computed(() => {
  return activeBetsCount.value;
});
watch(
  activePlayersCount,
  (count) => {
    emit("active-count", count);
  },
  { immediate: true }
);
watch(
  () => [props.round?.id, props.round?.round_no],
  ([newRoundId, newRoundNo], [oldRoundId, oldRoundNo] = []) => {
    if (!newRoundId && !newRoundNo) return;

    if (newRoundId !== oldRoundId || newRoundNo !== oldRoundNo) {
      betsTab.value = "all";
      lastFinalizedRoundKey.value = null;

      const restored = loadRoundSnapshot();

      if (restored) {
        lastPreparedRoundKey.value = currentRoundKey.value;

        if (props.roundState === "betting") {
          startLiveFeed();
        }

        if (props.roundState === "running") {
          refreshLiveRowsToCurrentCap();
          startLiveFeed();
        }

        return;
      }

      usersbetsAllBets.value = [];
      usersbetsPreviousBets.value = [];
      usersbetsTopBets.value = [];
      pendingLiveRows.value = [];

      rebuildusersbetsRoundBets();
      lastPreparedRoundKey.value = currentRoundKey.value;
    }
  },
  { immediate: true }
);
watch(
  () => props.liveMultiplier,
  () => {
    if (props.roundState === "running") {
      refreshLiveRowsToCurrentCap();
    }
  }
);

onBeforeUnmount(() => {
  stopFeed();
  stopCashoutFeed();
});
</script>

<template>
  <div class="aviator-usersbets-board">
    <div class="aviator-usersbets-tabs">
      <button
        type="button"
        class="usersbets-tab-btn"
        :class="{ active: betsTab === 'all' }"
        @click="betsTab = 'all'"
      >
        All Bets
      </button>

      <button
        type="button"
        class="usersbets-tab-btn"
        :class="{ active: betsTab === 'previous' }"
        @click="betsTab = 'previous'"
      >
        Previous
      </button>

      <button
        type="button"
        class="usersbets-tab-btn"
        :class="{ active: betsTab === 'top' }"
        @click="betsTab = 'top'"
      >
        Top
      </button>
    </div>

    <div class="aviator-usersbets-summary">
      <div class="usersbets-summary-left">
        <div class="usersbets-summary-label">
         {{ Number(totalBetsCount).toLocaleString() }} Bets
        </div>
      </div>

      <div class="usersbets-summary-right">
        <div class="usersbets-summary-total">
          {{ formatCompactMoney(usersbetsTotalWinAmount) }}
        </div>
        <div class="usersbets-summary-subtitle">Total win BDT</div>
      </div>
    </div>

    <div class="usersbets-progress-line">
      <div
        class="usersbets-progress-fill"
        :style="{
          width: `${Math.min(100, Math.max(6, (usersbetsCurrentTabRows.length / 18) * 100))}%`
        }"
      ></div>
    </div>

<div class="aviator-usersbets-table">
  <div class="aviator-usersbets-head">
    <div>Player</div>
    <div>Bet BDT</div>
    <div>X</div>
    <div>Win BDT</div>
  </div>

  <div class="aviator-usersbets-body">
    <div
     v-for="row in usersbetsCurrentTabRows.slice(0, getVisibleRowsLimit())"
      :key="row.id"
      class="aviator-usersbets-row"
      :class="{
        lost: row.status === 'lost',
        pending: row.status === 'pending',
        cashed: row.status === 'cashed_out'
      }"
    >
      <div class="usersbets-player-col">
        <div class="usersbets-avatar">
          <img :src="row.avatar" alt="avatar" />
        </div>
        <div class="usersbets-player-name">{{ row.player }}</div>
      </div>

      <div class="usersbets-bet-amount">{{ formatCompactMoney(row.bet_amount) }}</div>

      <div class="usersbets-x-multiplier">
        {{ row.status === "pending" || row.status === "lost" ? "-" : formatMultiplier(row.multiplier) + "x" }}
      </div>

      <div class="usersbets-win-amount">
        {{ row.status === "cashed_out" ? formatCompactMoney(row.win_amount) : "0.00" }}
      </div>
    </div>
  </div>
</div>
  </div>
</template>

<style scoped>
.aviator-usersbets-board {margin-top: 14px;border-radius: 18px;overflow: hidden;background: #1b1c1d;border: 1px solid rgba(255, 255, 255, 0.06);box-shadow: 0 8px 24px rgba(0, 0, 0, 0.28);}
.aviator-usersbets-tabs {display: grid;grid-template-columns: repeat(3, 1fr);gap: 8px;padding: 12px;background: rgba(255, 255, 255, 0.02);}
.usersbets-tab-btn {border: 0;outline: 0;height: 34px;border-radius: 999px;background: #1a1d22;color: #d8d8d8;font-size: 13px;font-weight: 600;}
.usersbets-tab-btn.active {background: #2c2d30;color: #fff;}
.aviator-usersbets-summary {display: flex;align-items: center;justify-content: space-between;gap: 12px;padding: 10px 14px 8px;color: #fff;}
.usersbets-summary-label {font-size: 14px;font-weight: 700;}
.usersbets-summary-total {text-align: right;font-size: 18px;font-weight: 800;color: #dce8ff;}
.usersbets-summary-subtitle {text-align: right;font-size: 12px;color: #b6bcc8;}
.usersbets-progress-line {height: 6px;margin: 0 14px 10px;border-radius: 999px;background: rgba(255, 255, 255, 0.08);overflow: hidden;}
.usersbets-progress-fill {height: 100%;border-radius: 999px;background: linear-gradient(90deg, #8fff00 0%, #5ab500 100%);}
.aviator-usersbets-table {padding: 0 10px 10px;}
.aviator-usersbets-head,
.aviator-usersbets-row {display: grid;grid-template-columns: 1.6fr 1fr 0.7fr 1fr;align-items: center;gap: 8px;}
.aviator-usersbets-head {padding: 8px 8px 10px;color: #8e97a6;font-size: 12px;}
.aviator-usersbets-row {min-height: 48px;margin-bottom: 8px;padding: 8px;border-radius: 16px;color: #fff;transition: transform .18s ease, opacity .18s ease;}
.aviator-usersbets-row.cashed {background: linear-gradient(0deg,rgba(66,127,0,.3) 0%,rgba(66,127,0,.3) 100%),#101112;}
.aviator-usersbets-row.pending {background: linear-gradient(0deg,rgba(52,72,100,.22) 0%,rgba(52,72,100,.22) 100%),#101112;}
.aviator-usersbets-row.lost {background: #101112;}
.usersbets-player-col {display: flex;align-items: center;gap: 8px;min-width: 0;}
.usersbets-avatar {width: 30px;height: 30px;border-radius: 999px;overflow: hidden;flex: 0 0 30px;border: 2px solid rgba(255, 255, 255, 0.18);}
.usersbets-avatar img {width: 100%;height: 100%;object-fit: cover;}
.usersbets-player-name {overflow: hidden;white-space: nowrap;text-overflow: ellipsis;font-size: 14px;font-weight: 600;}
.usersbets-bet-amount,
.usersbets-win-amount {font-size: 14px;font-weight: 700;color: #dbe2d0;}
.usersbets-x-multiplier {font-size: 14px;font-weight: 800;color: #b154ff;}
@media (max-width: 480px) {
  .aviator-usersbets-head,
  .aviator-usersbets-row {grid-template-columns: 1.4fr 0.95fr 0.65fr 0.95fr;gap: 6px;}
  .usersbets-player-name,
  .usersbets-bet-amount,
  .usersbets-x-multiplier,
  .usersbets-win-amount {font-size: 13px;}
}
.aviator-usersbets-table {padding: 0 10px 10px;}
.aviator-usersbets-head {display: grid;grid-template-columns: 1.6fr 1fr 0.7fr 1fr;align-items: center;gap: 8px;padding: 8px 8px 10px;color: #8e97a6;font-size: 12px;}
.aviator-usersbets-body {max-height: 320px;overflow-y: auto;overflow-x: hidden;padding-right: 4px;}
.aviator-usersbets-row {display: grid;grid-template-columns: 1.6fr 1fr 0.7fr 1fr;align-items: center;gap: 8px;min-height: 30px;margin-bottom: 8px;padding: 2px;border-radius: 16px;color: #fff;transition: transform .18s ease, opacity .18s ease;}
.aviator-usersbets-body::-webkit-scrollbar {width: 6px;}
.aviator-usersbets-body::-webkit-scrollbar-track {background: transparent;}
.aviator-usersbets-body::-webkit-scrollbar-thumb {background: rgba(255, 255, 255, 0.18);border-radius: 999px;}
.aviator-usersbets-body::-webkit-scrollbar-thumb:hover {background: rgba(255, 255, 255, 0.28);}
</style>