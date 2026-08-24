<script setup>
import axios from "axios";
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from "vue";
import BlankLayout from "../../layouts/BlankLayout.vue";
import AviatorPlaneCanvas from "./AviatorPlaneCanvas.vue";
import Header from "./Header.vue";
import LiveBetUsers from "./LiveBetUsers.vue";
import { router } from "@/user/inertia";
const props = defineProps({
  initialState: { type: Object, default: () => ({}) },
  userId: { type: Number, required: true },
});

const DEFAULT_GROWTH_TIME_CONSTANT_MS = 5500;
const liveActivePlayerCount = ref(0);
const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

const toMs = (value) => {
  const time = Date.parse(value || "");
  return Number.isFinite(time) ? time : null;
};

const getTimeConstantMs = (round) => {
  const direct = Number(round?.growth_time_constant_ms ?? round?.time_constant_ms);
  if (Number.isFinite(direct) && direct > 0) return Math.round(direct);

  const slow = Number(round?.slow_factor ?? 1);
  const slowClamped = Number.isFinite(slow) ? clamp(slow, 0.2, 10) : 1;

  return Math.round(DEFAULT_GROWTH_TIME_CONSTANT_MS * slowClamped);
};

const calcRoundMultiplier = (round, nowMs) => {
  if (!round) return 1.0;

  if (isInVisualBettingHold(round, nowMs)) {
    return 1.0;
  }

  const phase = round.status || "betting";
  const flyingStartsAt = toMs(round.flying_starts_at);

  if (!flyingStartsAt || nowMs < flyingStartsAt) return 1.0;

  if ((phase === "crashed" || phase === "completed") && round.crash_multiplier != null) {
    return Number(Number(round.crash_multiplier).toFixed(2));
  }

  const timeConstantMs = getTimeConstantMs(round);
  const elapsedMs = Math.max(0, nowMs - flyingStartsAt);
  const flightDurationMs = Number(round?.flight_duration_ms);
  const cappedElapsedMs =
    Number.isFinite(flightDurationMs) && flightDurationMs > 0
      ? Math.min(elapsedMs, flightDurationMs)
      : elapsedMs;

  let raw = Math.exp(cappedElapsedMs / Math.max(1, timeConstantMs));

  if (round.crash_multiplier != null) {
    raw = Math.min(raw, Number(round.crash_multiplier));
  }

  return Number(raw.toFixed(2));
};

const getEffectiveRoundState = (round, nowMs) => {
  if (!round) return "betting";

  if (isInVisualBettingHold(round, nowMs)) {
    return "betting";
  }

  const serverStatus = round.status || "betting";
  if (serverStatus === "crashed" || serverStatus === "completed") {
    return serverStatus;
  }

  const flyingStartsAt = toMs(round.flying_starts_at);
  if (!flyingStartsAt || nowMs < flyingStartsAt) {
    return "betting";
  }

  const flightDurationMs = Number(round?.flight_duration_ms);
  const resultDurationMs = Number(round?.result_duration_ms);
  const crashAtMs =
    Number.isFinite(flightDurationMs) && flightDurationMs > 0
      ? flyingStartsAt + flightDurationMs
      : null;

  if (crashAtMs == null) {
    return "running";
  }

  if (nowMs < crashAtMs) {
    return "running";
  }

  const finishedAtMs =
    Number.isFinite(resultDurationMs) && resultDurationMs >= 0
      ? crashAtMs + resultDurationMs
      : null;

  if (finishedAtMs != null && nowMs >= finishedAtMs) {
    return "completed";
  }

  return "crashed";
};
const state = ref(props.initialState || {});
const loading = ref(false);

const mainTab = ref("bet");
const extraTab = ref("bet");
const showExtraBetSection = ref(true);

const serverEpochMs = ref(null);
const perfAtServerEpoch = ref(0);
const nowMs = ref(Date.now());

const toasts = ref([]);
let toastSeq = 0;

const autoBetRoundLock = reactive({ 1: null, 2: null });
const autoBetBusy = reactive({ 1: false, 2: false });
const autoBetTimer = ref(null);

const savedSound = localStorage.getItem("aviator_sound");
if (savedSound === null) {
  localStorage.setItem("aviator_sound", "on");
}

const soundEnabled = ref(savedSound === null ? true : savedSound === "on");


const previousBetStatuses = ref({});
const displayMultiplierText = ref("1.00");
const visualBettingRoundId = ref(null);
const visualBettingUntilMs = ref(0);

const stopAllSounds = () => {
  Object.values(audio).forEach((item) => {
    if (!item) return;

    item.pause();
    item.currentTime = 0;
  });
};

const toggleSound = async (enable) => {
  soundEnabled.value = enable;
  localStorage.setItem("aviator_sound", enable ? "on" : "off");

  if (enable) {
    await enableSound();

    if (roundState.value === "running") {
      await startBackground();
    }
  } else {
    stopAllSounds();
  }
};
let rafId = 0;
let lastDisplayMultiplierAt = 0;
const DISPLAY_MULTIPLIER_INTERVAL_MS = 120;

const audio = {
  bg: null,
  start: null,
  crash: null,
  cashout: null,
  cashout2: null,
};

const pushToast = (type, message, data = {}) => {
  const id = ++toastSeq;

  toasts.value.push({
    id,
    type,
    message,
    title: data.title || message,
    multiplier: data.multiplier || null,
    amount: data.amount || null,
    leaving: false,
  });

  setTimeout(() => {
    const toast = toasts.value.find((t) => t.id === id);
    if (toast) toast.leaving = true;

    setTimeout(() => {
      toasts.value = toasts.value.filter((t) => t.id !== id);
    }, 300);
  }, 800);
};
const parseNumber = (value, fallback = 0) => {
  const n = Number(String(value ?? "").replace(/[^\d.]/g, ""));
  return Number.isFinite(n) ? n : fallback;
};

const sanitizeDecimal = (value) => {
  return String(value ?? "")
    .replace(/[^0-9.]/g, "")
    .replace(/(\..*?)\..*/g, "$1");
};

const formatMoney = (value) => parseNumber(value, 0).toFixed(2);
const formatMultiplier = (value) => parseNumber(value, 1).toFixed(2);

const updateServerEpoch = (iso) => {
  if (!iso) return;

  const ms = Date.parse(iso);
  if (!Number.isFinite(ms)) return;

  serverEpochMs.value = ms;
  perfAtServerEpoch.value = performance.now();
};

watch(() => state.value?.server_time, updateServerEpoch, { immediate: true });

const getSyncedNowMs = () => {
  if (serverEpochMs.value == null) return Date.now();
  return serverEpochMs.value + (performance.now() - perfAtServerEpoch.value);
};
const isInVisualBettingHold = (round, now) => {
  if (!round?.id) return false;
  return (
    Number(visualBettingRoundId.value) === Number(round.id) &&
    now < visualBettingUntilMs.value
  );
};
const createBetState = (slot) =>
  reactive({
    slot,
    amount: "10.00",
    autoBet: false,
    autoCashOut: false,
    autoCashOutMultiplier: "2.00",

    queued: false,
    awaitingStart: false,
    active: false,
    cashedOut: false,
    lost: false,

    serverBetId: null,
    betId: "",
    cashOutAmount: "0.00",
  });

const mainBet = createBetState(1);
const extraBet = createBetState(2);

const currentRound = computed(() => state.value?.round || null);
const roundState = computed(() => getEffectiveRoundState(currentRound.value, nowMs.value));
const currentRoundId = computed(() => currentRound.value?.id ?? null);

const myBets = computed(() =>
  Array.isArray(state.value?.my_bets) ? state.value.my_bets : []
);

const liveMultiplier = computed(() => calcRoundMultiplier(currentRound.value, nowMs.value));

const showLiveMultiplier = computed(() =>
  ["running", "crashed", "completed"].includes(roundState.value)
);

const visibleBetsBySlot = computed(() => {
  const map = {};

  for (const bet of myBets.value) {
    const slot = Number(bet.slot);

    const isCurrentRoundPending =
      Number(bet.round_id) === Number(currentRoundId.value) &&
      bet.status === "pending";

    const isQueuedUpcoming =
      bet.status === "queued" || bet.is_queued === true;

    const isRecentSettled =
      Number(bet.round_id) === Number(currentRoundId.value) &&
      ["cashed_out", "lost", "cancelled"].includes(bet.status);

    if (!isCurrentRoundPending && !isQueuedUpcoming && !isRecentSettled) {
      continue;
    }

    const priority = isCurrentRoundPending
      ? 4
      : isQueuedUpcoming
      ? 3
      : bet.status === "cashed_out"
      ? 2
      : 1;

    if (!map[slot] || priority > map[slot]._priority) {
      map[slot] = {
        ...bet,
        _priority: priority,
      };
    }
  }

  return map;
});

const setTab = (section, tab) => {
  if (section === "main") mainTab.value = tab;
  if (section === "extra") extraTab.value = tab;
};

const normalizeBetAmount = (bet) => {
  let amount = parseNumber(bet.amount, 10);
  if (amount < 1) amount = 1;
  bet.amount = amount.toFixed(2);
};

const normalizeAutoCashOut = (bet) => {
  let value = parseFloat(bet.autoCashOutMultiplier || 2);

  if (isNaN(value)) value = 2;

  // minimum 2x
  if (value < 2) value = 2;

  bet.autoCashOutMultiplier = value.toFixed(2);
};

const bet_amount_incremental = (bet) => {
  normalizeBetAmount(bet);
  bet.amount = formatMoney(parseNumber(bet.amount, 0) + 10);
};

const bet_amount_decremental = (bet) => {
  normalizeBetAmount(bet);
  bet.amount = formatMoney(Math.max(1, parseNumber(bet.amount, 0) - 10));
};

const select_direct_bet_amount = (bet, amount) => {
  bet.amount = formatMoney(amount);
};

const clearBetRuntime = (bet) => {
  bet.queued = false;
  bet.awaitingStart = false;
  bet.active = false;
  bet.cashedOut = false;
  bet.lost = false;
  bet.serverBetId = null;
  bet.betId = "";
  bet.cashOutAmount = "0.00";
};

const syncOneBet = (bet) => {
  const serverBet = visibleBetsBySlot.value[bet.slot];

  if (!serverBet) {
    clearBetRuntime(bet);
    return;
  }

  bet.serverBetId = serverBet.id;
  bet.betId = serverBet.bet_key;
  bet.amount = formatMoney(serverBet.amount);

if (serverBet.auto_cashout_enabled !== undefined) {
  bet.autoCashOut = !!serverBet.auto_cashout_enabled;
}
  bet.autoCashOutMultiplier =
    serverBet.auto_cashout_multiplier != null
      ? formatMultiplier(serverBet.auto_cashout_multiplier)
      : bet.autoCashOutMultiplier;

  const isQueuedForNext =
    serverBet.status === "queued" || serverBet.is_queued === true;

  const isCurrentPending =
    serverBet.status === "pending" &&
    Number(serverBet.round_id) === Number(currentRoundId.value);

  bet.queued = isQueuedForNext;
  bet.awaitingStart = isCurrentPending && roundState.value === "betting";
  bet.active = isCurrentPending && roundState.value === "running";
  bet.cashedOut = serverBet.status === "cashed_out";
  bet.lost = serverBet.status === "lost";

  if (bet.active) {
    bet.cashOutAmount = formatMoney(
      parseNumber(serverBet.amount, 0) * parseNumber(liveMultiplier.value, 1)
    );
    return;
  }

  if (bet.awaitingStart || bet.queued) {
    bet.cashOutAmount = formatMoney(serverBet.amount || 0);
    return;
  }

  if (bet.cashedOut) {
    bet.cashOutAmount = formatMoney(serverBet.cashout_amount || 0);
    return;
  }

  bet.cashOutAmount = "0.00";
};

const syncLocalState = () => {
  syncOneBet(mainBet);
  syncOneBet(extraBet);
};

watch([liveMultiplier, roundState, myBets], syncLocalState, {
  deep: true,
  immediate: true,
});

const readLaravelErrorMessage = (error) => {
  const data = error?.response?.data;
  if (!data) return "Request failed.";

  if (typeof data.message === "string" && data.message.trim()) {
    return data.message;
  }

  if (data.errors && typeof data.errors === "object") {
    const firstField = Object.keys(data.errors)[0];
    const first = data.errors[firstField]?.[0];
    if (first) return first;
  }

  return "Request failed.";
};

const placeBetRequest = async (bet, options = {}) => {
  const { silent = false } = options;
  // STOP REQUEST
  if (
    bet.autoCashOut &&
    parseFloat(bet.autoCashOutMultiplier) < 2
  ) {
    pushToast("error", "Auto Cash Out minimum is 2.00x");
    return false;
  }

  try {
    loading.value = true;
    normalizeBetAmount(bet);
    normalizeAutoCashOut(bet);

    const { data } = await axios.post("/user/aviator/bet", {
      slot: bet.slot,
      amount: parseNumber(bet.amount, 1),
      auto_cashout_enabled: !!bet.autoCashOut,
auto_cashout_multiplier: bet.autoCashOut
  ? parseNumber(bet.autoCashOutMultiplier, 2)
  : null,
    });

    state.value = data;
    syncLocalState();

    if (!silent) {
      if (data?.bet_action?.mode === "queued") {
        pushToast("info", `Bet queued for next round #${data.bet_action.round_no}`);
      } else {
        pushToast("success", `Bet placed (slot ${bet.slot}).`);
      }
    }

    return true;
  } catch (error) {
    if (!silent) {
      pushToast("error", readLaravelErrorMessage(error));
    }
    return false;
  } finally {
    loading.value = false;
  }
};

const bet_now = async (bet) => {
  await placeBetRequest(bet);
};

const hasEnoughBalanceForBet = (bet) => {
  return parseNumber(state.value?.balance, 0) >= parseNumber(bet.amount, 0);
};

watch(
  () => currentRoundId.value,
  (newRoundId, oldRoundId) => {
    if (!newRoundId || newRoundId === oldRoundId) return;

    autoBetRoundLock[1] = null;
    autoBetRoundLock[2] = null;

    syncLocalState();
  }
);

const tryAutoBetFor = async (bet) => {
  const roundId = currentRoundId.value;
  if (!roundId) return;
  if (!bet.autoBet) return;
  if (loading.value || autoBetBusy[bet.slot]) return;
  if (!hasEnoughBalanceForBet(bet)) return;

  const serverBet = visibleBetsBySlot.value[bet.slot];

  if (serverBet && (serverBet.status === "queued" || serverBet.is_queued === true)) {
    return;
  }

  if (
    serverBet &&
    serverBet.status === "pending" &&
    Number(serverBet.round_id) === Number(currentRoundId.value)
  ) {
    autoBetRoundLock[bet.slot] = roundId;
    return;
  }

  if (autoBetRoundLock[bet.slot] === roundId && roundState.value === "betting") {
    return;
  }

  autoBetBusy[bet.slot] = true;

  try {
    const ok = await placeBetRequest(bet, { silent: true });
    if (ok) {
      autoBetRoundLock[bet.slot] = roundId;
    }
  } finally {
    autoBetBusy[bet.slot] = false;
  }
};

const startAutoBetLoop = () => {
  if (autoBetTimer.value) return;

  autoBetTimer.value = setInterval(async () => {
    await tryAutoBetFor(mainBet);
    await tryAutoBetFor(extraBet);
  }, 450);
};

const stopAutoBetLoop = () => {
  if (!autoBetTimer.value) return;
  clearInterval(autoBetTimer.value);
  autoBetTimer.value = null;
};

watch(
  () => [mainBet.autoBet, extraBet.autoBet],
  ([mainAuto, extraAuto]) => {
    const any = !!mainAuto || !!extraAuto;
    if (any) startAutoBetLoop();
    else stopAutoBetLoop();
  },
  { immediate: true }
);

const cancel_now = async (bet) => {
  if (loading.value) return;

  try {
    loading.value = true;

    const { data } = await axios.post("/user/aviator/cancel", {
      slot: bet.slot,
    });

    state.value = data;
    syncLocalState();
  } catch (error) {
    pushToast("error", readLaravelErrorMessage(error));
    console.error("Cancel failed", error);
  } finally {
    loading.value = false;
  }
};

const cash_out_now = async (bet) => {
  if (!bet.serverBetId || loading.value) return false;

  try {
    loading.value = true;

    const { data } = await axios.post("/user/aviator/cashout", {
      bet_id: bet.serverBetId,
    });

    state.value = data;
    syncLocalState();
    return true;
  } catch (error) {
    pushToast("error", readLaravelErrorMessage(error));
    console.error("Cash out failed", error);
    return false;
  } finally {
    loading.value = false;
  }
};

const main_incrementor_change = (value) => {
  mainBet.autoCashOutMultiplier = sanitizeDecimal(value);
  normalizeAutoCashOut(mainBet);
};

const extra_incrementor_change = (value) => {
  extraBet.autoCashOutMultiplier = sanitizeDecimal(value);
  normalizeAutoCashOut(extraBet);
};

const hideExtraBetSection = () => {
  if (visibleBetsBySlot.value[2]) return;
  clearBetRuntime(extraBet);
  showExtraBetSection.value = false;
};

const showExtraSection = () => {
  showExtraBetSection.value = true;
};

const initAudio = () => {
  if (typeof Audio === "undefined") return;

  audio.bg = new Audio("/assets/plane/mp3/background.mp3");
  audio.start = new Audio("/assets/plane/mp3/game-start.mp3");
  audio.crash = new Audio("/assets/plane/mp3/plane-crash.mp3");
  audio.cashout = new Audio("/assets/plane/mp3/cashout.mp3");
  audio.cashout2 = new Audio("/assets/plane/mp3/cashout_2.mp3");

  audio.bg.loop = true;
  audio.bg.volume = 0.35;
};

const safelyPlay = async (key, reset = true) => {
  const instance = audio[key];
  if (!soundEnabled.value || !instance) return;

  try {
    if (reset) {
      instance.pause();
      instance.currentTime = 0;
    }
    await instance.play();
  } catch (_) {}
};

const startBackground = async () => {
  if (!soundEnabled.value || !audio.bg) return;

  try {
    if (audio.bg.paused) {
      await audio.bg.play();
    }
  } catch (_) {}
};

const stopBackground = () => {
  if (!audio.bg) return;
  audio.bg.pause();
  audio.bg.currentTime = 0;
};

const enableSound = async () => {
  soundEnabled.value = true;
  await safelyPlay("start");

  if (audio.start) {
    audio.start.pause();
    audio.start.currentTime = 0;
  }
};

watch(
  roundState,
  async (newPhase, oldPhase) => {
    if (oldPhase === "betting" && newPhase === "running") {
      await safelyPlay("start");
      await startBackground();
    }

    if (oldPhase === "running" && newPhase === "crashed") {
      stopBackground();
      await safelyPlay("crash");
    }

    if (newPhase === "completed" || newPhase === "betting") {
      stopBackground();
    }
  },
  { immediate: false }
);

watch(
  myBets,
  (bets) => {
    const next = {};

    bets.forEach((bet) => {
      next[bet.id] = bet.status;

      const previousStatus = previousBetStatuses.value[bet.id];

      if (previousStatus && previousStatus !== bet.status) {
        if (bet.status === "cashed_out") {
          safelyPlay(bet.slot === 2 ? "cashout2" : "cashout");

          const payout = formatMoney(bet.cashout_amount || 0);
          const mult = formatMultiplier(bet.cashout_multiplier || 1);
          pushToast("success", "You have cashed out!", {multiplier: `${mult}x`,amount: payout,});
        }

        if (bet.status === "lost") {
          const lost = formatMoney(bet.amount || 0);
          pushToast("error", `Lost ${lost}`);
        }

        if (bet.status === "cancelled") {
          pushToast("info", "Bet cancelled.");
        }
      }
    });

    previousBetStatuses.value = next;
  },
  { deep: true, immediate: true }
);
const roundHistory = computed(() =>
  Array.isArray(state.value?.history) ? state.value.history : []
);

const showHistoryModal = ref(false);

const toolbarHistory = computed(() => roundHistory.value.slice(0, 8));

const formatHistoryMultiplier = (value) => formatMultiplier(value);

const historyItemClass = (value) => {
  const num = parseNumber(value, 1);

  if (num >= 10) return "history-item huge";
  if (num >= 2) return "history-item mid";
  return "history-item low";
};

const shouldApplyRoundPayload = (incomingRound) => {
  const currentId = Number(state.value?.round?.id ?? 0);
  const incomingId = Number(incomingRound?.id ?? 0);

  if (!incomingId) return true;
  if (!currentId) return true;

  return incomingId >= currentId;
};
const setupRealtime = () => {
  if (!window.Echo) {
    console.warn("Echo not found. Check Reverb/Echo bootstrap setup.");
    return;
  }

window.Echo.channel("aviator").listen(".aviator.round_state", (e) => {
  if (!shouldApplyRoundPayload(e.round)) return;

  const prevRoundId = Number(state.value?.round?.id ?? 0);
  const nextRoundId = Number(e?.round?.id ?? 0);

  const syncedNow = (() => {
    const serverMs = Date.parse(e?.server_time || "");
    return Number.isFinite(serverMs) ? serverMs : Date.now();
  })();

  if (nextRoundId && nextRoundId !== prevRoundId) {
    const flyingStartsAtMs = toMs(e?.round?.flying_starts_at);

    const minVisualDelayMs = 1200;

    visualBettingRoundId.value = nextRoundId;
    visualBettingUntilMs.value = Math.max(
      syncedNow + minVisualDelayMs,
      flyingStartsAtMs || 0
    );
  }

  state.value = {
    ...state.value,
    server_time: e.server_time,
    round: e.round,
    history: e.history || state.value.history || [],
  };
});

  window.Echo.private(`aviator.user.${props.userId}`).listen(
    ".aviator.user_state",
    (e) => {
      state.value = {
        ...state.value,
        balance: e.balance ?? state.value.balance,
        my_bets: e.my_bets ?? state.value.my_bets,
      };
    }
  );
};
const teardownRealtime = () => {
  if (!window.Echo) return;
  window.Echo.leave("aviator");
  window.Echo.leave(`aviator.user.${props.userId}`);
};

const mainQueuedLabel = computed(() =>
  mainBet.autoBet && mainBet.queued ? "AUTO CANCEL" : "CANCEL"
);

const extraQueuedLabel = computed(() =>
  extraBet.autoBet && extraBet.queued ? "AUTO CANCEL" : "CANCEL"
);

const mainQueuedText = computed(() =>
  // mainBet.autoBet && mainBet.queued ? "Auto queued for next round" : "Waiting for next round"
  mainBet.autoBet && mainBet.queued ? "Auto queued for next round" : ""
);

const extraQueuedText = computed(() =>
  extraBet.autoBet && extraBet.queued ? "Auto queued for next round" : ""
  // extraBet.autoBet && extraBet.queued ? "Auto queued for next round" : "Waiting for next round"
);

const mainCashoutLabel = computed(() =>
  mainBet.autoCashOut ? "AUTO CASH OUT" : "CASH OUT"
);

const extraCashoutLabel = computed(() =>
  extraBet.autoCashOut ? "AUTO CASH OUT" : "CASH OUT"
);

const unlockAudioOnce = async () => {
  if (!soundEnabled.value) return;

  await enableSound();

  if (roundState.value === "running") {
    await startBackground();
  }
};

onMounted(() => {
  initAudio();

  document.addEventListener("click", unlockAudioOnce, { once: true });
  document.addEventListener("touchstart", unlockAudioOnce, { once: true });

  syncLocalState();
  setupRealtime();


  const animate = () => {
    nowMs.value = getSyncedNowMs();

    if (roundState.value === "running") {
      if (nowMs.value - lastDisplayMultiplierAt >= DISPLAY_MULTIPLIER_INTERVAL_MS) {
        displayMultiplierText.value = formatMultiplier(liveMultiplier.value);
        lastDisplayMultiplierAt = nowMs.value;
      }
    } else if (roundState.value === "crashed" || roundState.value === "completed") {
      displayMultiplierText.value = formatMultiplier(
        currentRound.value?.crash_multiplier ?? liveMultiplier.value
      );
    } else {
      displayMultiplierText.value = "1.00";
      lastDisplayMultiplierAt = 0;
    }

    rafId = requestAnimationFrame(animate);
  };

  animate();
});

onBeforeUnmount(() => {
  document.removeEventListener("click", unlockAudioOnce);
  document.removeEventListener("touchstart", unlockAudioOnce);

  teardownRealtime();
  stopAllSounds();
  stopAutoBetLoop();
  cancelAnimationFrame(rafId);
});


const formattedBalance = computed(() => {
  return formatMoney(state.value?.balance ?? 0);
});





</script>

<template>
<div v-if="showHistoryModal" class="history-modal-backdrop" @click.self="showHistoryModal = false">
  <div class="history-modal-card">
    <div class="history-modal-header">
      <div class="history-modal-title">Round History</div>
      <button
        type="button"
        class="history-close-btn"
        @click="showHistoryModal = false"
      >
        ×
      </button>
    </div>

    <div class="history-modal-grid">
      <span
        v-for="item in roundHistory"
        :key="item.id"
        :class="historyItemClass(item.crash_multiplier)"
      >
        {{ formatHistoryMultiplier(item.crash_multiplier) }}x
      </span>
    </div>
  </div>
</div>
  <BlankLayout>
<div class="navbar main">
  <div class="navbar-fixed">
    <div class="navbar__content">
      <!-- <div
        class="navbar__content-left"
        @click="router.visit(route('user.home'))"
        style="cursor: pointer;"
      >
        <i class="van-badge__wrapper van-icon van-icon-arrow-left"></i>
      </div> -->

      <div class="navbar__content-center">
          <i  class="van-badge__wrapper van-icon van-icon-arrow-left" @click="router.visit(route('user.home'))" />
      </div>

      <div class="navbar__content-right">
        <div class="WinGo__C-head-more">
          <div class="balance">{{ formattedBalance }} <span class="currency">BDT</span></div>
            <svg  @click="router.visit(route('user.aviator.history'))"  class="svg-icon icon-team_port adjustIcons"><use xlink:href="#icon-team_port"></use></svg>
          <div v-if="soundEnabled" class="sound-enable" @click="toggleSound(false)"></div>
          <div v-else class="sound-disable" @click="toggleSound(true)"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div
  v-for="t in toasts"
  :key="t.id"
  class="aviator-toast-popup"
>
  <div class="aviator-toast-card" :class="t.type">
    <div class="toast-left">
      <div class="toast-title">{{ t.title || t.message }}</div>
      <div v-if="t.multiplier" class="toast-multiplier">{{ t.multiplier }}</div>
    </div>

    <template v-if="t.amount">
      <div class="toast-divider"></div>

      <div class="toast-right">
        <div class="toast-win-title">Win BDT</div>
        <div class="toast-amount">{{ t.amount }}</div>
      </div>
    </template>

    <button class="toast-close" @click="toasts = toasts.filter(x => x.id !== t.id)">
      ×
    </button>
  </div>
</div>

    <div class="main_layo">
<div class="aviator-toolbar">


  <div class="aviator-toolbar-center">
    <div class="round-history-inline" v-if="toolbarHistory.length">
      <span
        v-for="item in toolbarHistory"
        :key="item.id"
        :class="historyItemClass(item.crash_multiplier)"
      >
        {{ formatHistoryMultiplier(item.crash_multiplier) }}x
      </span>
    </div>
  </div>

  <div class="aviator-toolbar-right">
    <div v-if="showLiveMultiplier" class="live-multiplier-badge">
      {{ displayMultiplierText }}x
    </div>

    <button
      type="button"
      class="history-more-btn"
      @click="showHistoryModal = true"
      aria-label="Open round history"
    >
      ...
    </button>
  </div>
</div>

<AviatorPlaneCanvas
  :key="state.round?.id || state.round?.round_no || 'no-round'"
  :round="state.round"
  :server-time="state.server_time"
  :player-count="liveActivePlayerCount"
  :forced-round-id="visualBettingRoundId"
  :force-betting-until-ms="visualBettingUntilMs"
  :show-multiplier="true"
  :show-end-overlay="true"
  intro-src="/assets/plane/image/aviator-intro-partner.png"
/>
<div class="bet-controls">
        <div class="bet-control double-bet" id="main_bet_section">
          <div class="controls">
            <div
              class="bet-box-action"
              id="add_extra_bet_section_btn"
              v-show="!showExtraBetSection"
              @click="showExtraSection"
            >
              <span class="material-symbols-outlined text-success action-icon">
                <i class="fa-solid fa-circle-plus"></i>
              </span>
            </div>

            <div class="navigation">
              <input id="bet_type_main" type="hidden" value="0" />
              <div class="navigation-switcher">
                <div
                  class="slider bet-btn"
                  :class="{ active: mainTab === 'bet' }"
                  @click="setTab('main', 'bet')"
                >
                  Bet
                </div>
                <div
                  class="slider auto-btn"
                  :class="{ active: mainTab === 'auto' }"
                  @click="setTab('main', 'auto')"
                >
                  Auto
                </div>
                <span class="active-line"></span>
              </div>
            </div>

            <div class="first-row auto-game-feature">
              <div class="bet-block">
                <div class="spinner">
                  <div class="input">
                    <input
                      id="main_bet_amount"
                      v-model="mainBet.amount"
                      type="text"
                      class="main_bet_amount"
                      @input="mainBet.amount = sanitizeDecimal(mainBet.amount)"
                      @blur="normalizeBetAmount(mainBet)"
                    />
                  </div>
                  <div class="qty-buttons">
                    <button
                      class="minus"
                      id="main_minus_btn"
                      type="button"
                      @click="bet_amount_decremental(mainBet)"
                    >
                      <span class="material-symbols-outlined action-icon">
                        <i class="fa-solid fa-minus"></i>
                      </span>
                    </button>
                    <button
                      class="plus"
                      id="main_plus_btn"
                      type="button"
                      @click="bet_amount_incremental(mainBet)"
                    >
                      <span class="material-symbols-outlined action-icon">
                        <i class="fa-solid fa-plus"></i>
                      </span>
                    </button>
                  </div>
                </div>

                <div class="bets-opt-list">
                  <button class="btn btn-secondary btn-sm bet-opt main_amount_btn" type="button" @click="select_direct_bet_amount(mainBet, 100)"> <span class="amt">100</span><span class="currency">৳</span></button>
                  <button class="btn btn-secondary btn-sm bet-opt main_amount_btn" type="button" @click="select_direct_bet_amount(mainBet, 200)"> <span class="amt">200</span><span class="currency">৳</span></button>
                  <button class="btn btn-secondary btn-sm bet-opt main_amount_btn" type="button" @click="select_direct_bet_amount(mainBet, 500)"> <span class="amt">500</span><span class="currency">৳</span></button>
                  <button class="btn btn-secondary btn-sm bet-opt main_amount_btn" type="button" @click="select_direct_bet_amount(mainBet, 1000)"> <span class="amt">1000</span><span class="currency">৳</span></button>
                </div>
              </div>

              <div
                class="buttons-block"
                id="main_bet_button"
                v-show="!mainBet.queued && !mainBet.awaitingStart && !mainBet.active">
                <button class="btn btn-success bet font-family-title ng-star-inserted main_bet_btn" type="button" id="main_bet_now" :disabled="loading" @click="bet_now(mainBet)">
                  <label class="font-family-title label">Bet <br> {{  mainBet.amount }} BDT</label>
                </button>
              </div>

              <div
                class="buttons-block"
                id="main_cancel_button"
                v-show="mainBet.queued || mainBet.awaitingStart"
              >
                <div class="btn-tooltip f-14 mb-1" id="main_waiting">
                  {{ mainBet.awaitingStart ? " " : mainQueuedText }}
                  <!-- {{ mainBet.awaitingStart ? "Bet placed for current round" : mainQueuedText }} -->
                </div>
                <button
                  class="btn btn-danger bet font-family-title height-70 ng-star-inserted main_bet_btn"
                  type="button"
                  id="main_cancel_now"
                  :disabled="loading"
                  @click="cancel_now(mainBet)"
                >
                  <label class="font-family-title label">
                    {{ mainBet.awaitingStart ? "CANCEL" : mainQueuedLabel }}
                  </label>
                  <div class="font-family-title label">
                    {{ mainBet.amount }}
                  </div>
                </button>
              </div>

              <div class="buttons-block" id="main_cashout_button" v-show="mainBet.active">
                <input type="hidden" name="main_bet_id" id="main_bet_id" :value="mainBet.betId" />
                <button
                  class="btn btn-warning bet font-family-title ng-star-inserted"
                  type="button"
                  :disabled="loading"
                  @click="cash_out_now(mainBet)"
                >
                  <label class="font-family-title label">{{ mainCashoutLabel }}</label>
                  <div class="font-family-title label" id="main_cash_out_amount">
                    {{ mainBet.cashOutAmount }}
                  </div>
                </button>
              </div>
            </div>

            <div class="second-row" :class="{ show: mainTab === 'auto' }">
              <div class="cashout-block m-0">
                <div class="cash-out-switcher">
                  <div class="form-check form-switch lg-switch d-flex align-items-center pe-5">
                    <label class="form-check-label f-12 me-1" for="main_auto_bet">
                      Auto Bet
                    </label>
                    <input
                      class="form-check-input m-0"
                      type="checkbox"
                      role="bet"
                      id="main_auto_bet"
                      v-model="mainBet.autoBet"
                    />
                  </div>

                  <div class="form-check form-switch lg-switch d-flex align-items-center">
                    <label class="form-check-label f-12 me-1" for="main_checkout">
                      Auto Cash Out
                    </label>
                    <input
                      class="form-check-input m-0"
                      type="checkbox"
                      role="cashout"
                      id="main_checkout"
                      v-model="mainBet.autoCashOut"
                    />
                  </div>
                </div>

                <div class="cashout-spinner-wrapper">
                  <div class="cashout-spinner" :class="{ disabled: !mainBet.autoCashOut }">
                    <div class="spinner small">
                      <div class="input full-width">
                        <input class="f-16 font-weight-bold" :disabled="!mainBet.autoCashOut" type="text" v-model="mainBet.autoCashOutMultiplier" id="main_incrementor" @input="mainBet.autoCashOutMultiplier = sanitizeDecimal(mainBet.autoCashOutMultiplier)" @change="main_incrementor_change(mainBet.autoCashOutMultiplier)" @blur="normalizeAutoCashOut(mainBet)"/>
                      </div>

                      <div class="text text-x" @click="mainBet.autoCashOut = false">
                        <span class="material-symbols-outlined action-icon">
                          <i class="fa-solid fa-xmark"></i>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div
          class="bet-control double-bet"
          id="extra_bet_section"
          v-show="showExtraBetSection"
        >
          <div class="controls">
            <div class="bet-box-action" id="remove_extra_section_btn" @click="hideExtraBetSection">
              <span class="material-symbols-outlined action-icon">
                <i class="fa-solid fa-circle-minus"></i>
              </span>
            </div>

            <div class="navigation">
              <input id="bet_type_extra" type="hidden" value="0" />
              <div class="navigation-switcher">
                <div
                  class="slider bet-btn"
                  :class="{ active: extraTab === 'bet' }"
                  @click="setTab('extra', 'bet')"
                >
                  Bet
                </div>
                <div
                  class="slider auto-btn"
                  :class="{ active: extraTab === 'auto' }"
                  @click="setTab('extra', 'auto')"
                >
                  Auto
                </div>
                <span class="active-line"></span>
              </div>
            </div>

            <div class="first-row auto-game-feature">
              <div class="bet-block">
                <div class="spinner">
                  <div class="input">
                    <input
                      id="extra_bet_amount"
                      v-model="extraBet.amount"
                      type="text"
                      class="extra_bet_amount"
                      @input="extraBet.amount = sanitizeDecimal(extraBet.amount)"
                      @blur="normalizeBetAmount(extraBet)"
                    />
                  </div>

                  <div class="qty-buttons">
                    <button
                      class="minus"
                      id="extra_minus_btn"
                      type="button"
                      @click="bet_amount_decremental(extraBet)"
                    >
                      <span class="material-symbols-outlined action-icon">
                        <i class="fa-solid fa-minus"></i>
                      </span>
                    </button>

                    <button
                      class="plus"
                      id="extra_plus_btn"
                      type="button"
                      @click="bet_amount_incremental(extraBet)"
                    >
                      <span class="material-symbols-outlined action-icon">
                        <i class="fa-solid fa-plus"></i>
                      </span>
                    </button>
                  </div>
                </div>

                <div class="bets-opt-list">
                  <button class="btn btn-secondary btn-sm bet-opt extra_amount_btn" type="button" @click="select_direct_bet_amount(extraBet, 100)">
                    <span class="amt">100</span><span class="currency">৳</span>
                  </button>
                  <button class="btn btn-secondary btn-sm bet-opt extra_amount_btn" type="button" @click="select_direct_bet_amount(extraBet, 200)">
                    <span class="amt">200</span><span class="currency">৳</span>
                  </button>
                  <button class="btn btn-secondary btn-sm bet-opt extra_amount_btn" type="button" @click="select_direct_bet_amount(extraBet, 500)">
                    <span class="amt">500</span><span class="currency">৳</span>
                  </button>
                  <button class="btn btn-secondary btn-sm bet-opt extra_amount_btn" type="button" @click="select_direct_bet_amount(extraBet, 1000)">
                    <span class="amt">1000</span><span class="currency">৳</span>
                  </button>
                </div>
              </div>

              <div class="buttons-block" id="extra_bet_button" v-show="!extraBet.queued && !extraBet.awaitingStart && !extraBet.active" >
                <button class="btn btn-success bet font-family-title ng-star-inserted main_bet_btn" id="extra_bet_now" type="button" :disabled="loading" @click="bet_now(extraBet)">
                  <label class="font-family-title label">Bet <br> {{  extraBet.amount }} BDT</label>
                </button>
              </div>

              <div class="buttons-block" id="extra_cancel_button" v-show="extraBet.queued || extraBet.awaitingStart">
                <div class="btn-tooltip f-14 mb-1" id="extra_waiting">
                  {{ extraBet.awaitingStart ? " " : extraQueuedText }}
                  <!-- {{ extraBet.awaitingStart ? "Bet placed for current round" : extraQueuedText }} -->
                </div>
                <button class="btn btn-danger bet font-family-title height-70 ng-star-inserted main_bet_btn" id="extra_cancel_now" type="button" :disabled="loading" @click="cancel_now(extraBet)">
                  <label class="font-family-title label">
                    {{ extraBet.awaitingStart ? "CANCEL" : extraQueuedLabel }}
                  </label>
                  <div class="font-family-title label">
                    {{ extraBet.amount }}
                  </div>
                </button>
              </div>

              <div class="buttons-block" id="extra_cashout_button" v-show="extraBet.active">
                <input type="hidden" name="extra_bet_id" id="extra_bet_id" :value="extraBet.betId"/>
                <button class="btn btn-warning bet font-family-title ng-star-inserted" type="button" :disabled="loading" @click="cash_out_now(extraBet)">
                  <label class="font-family-title label">{{ extraCashoutLabel }}</label>
                  <div class="font-family-title label" id="extra_cash_out_amount">
                    {{ extraBet.cashOutAmount }}
                  </div>
                </button>
              </div>
            </div>

            <div class="second-row" :class="{ show: extraTab === 'auto' }">
              <div class="cashout-block m-0">
                <div class="cash-out-switcher">
                  <div class="form-check form-switch lg-switch d-flex align-items-center pe-5">
                    <label class="form-check-label f-12 me-1" for="extra_auto_bet">
                      Auto Bet
                    </label>
                    <input class="form-check-input m-0" type="checkbox" role="bet" id="extra_auto_bet" v-model="extraBet.autoBet"/>
                  </div>

                  <div class="form-check form-switch lg-switch d-flex align-items-center">
                    <label class="form-check-label f-12 me-1" for="extra_checkout">Auto Cash Out</label>
                    <input class="form-check-input" type="checkbox" role="cashout" id="extra_checkout" v-model="extraBet.autoCashOut"/>
                  </div>
                </div>

                <div class="cashout-spinner-wrapper">
                  <div class="cashout-spinner" :class="{ disabled: !extraBet.autoCashOut }">
                    <div class="spinner small">
                      <div class="input full-width">
                        <input class="f-16 font-weight-bold" type="text" :disabled="!extraBet.autoCashOut" v-model="extraBet.autoCashOutMultiplier" id="extra_incrementor" @input="extraBet.autoCashOutMultiplier = sanitizeDecimal(extraBet.autoCashOutMultiplier)" @change="extra_incrementor_change(extraBet.autoCashOutMultiplier)" @blur="normalizeAutoCashOut(extraBet)"/>
                      </div>

                      <div class="text text-x" @click="extraBet.autoCashOut = false">
                        <span class="material-symbols-outlined action-icon">
                          <i class="fa-solid fa-xmark"></i>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
<LiveBetUsers
  :key="state.round?.id || state.round?.round_no || 'no-round'"
  :round="state.round"
  :round-state="roundState"
  :live-multiplier="liveMultiplier"
  @active-count="liveActivePlayerCount = $event"
/>
    </div>
  </BlankLayout>
</template>


<style scoped>
.aviator-toolbar {display: flex;align-items: center;justify-content: space-between;gap: 12px;}
.live-multiplier-badge {min-width: 65px;text-align: center;padding: 6px 12px;border-radius: 999px;background: rgba(240, 11, 62, 0.12);color: #f0062c;font-weight: 800;letter-spacing: 0.4px;}
.bet-status-note {margin-top: 8px;color: #d7d7d7;font-size: 12px;line-height: 1.4;min-height: 18px;}

.material-symbols-outlined.action-icon {display: inline-flex;align-items: center;justify-content: center;font-family: inherit !important;line-height: 1;}
.material-symbols-outlined.action-icon i {font-size: inherit;line-height: 1;}
.bet-box-action,.text.text-x {cursor: pointer;}
.cashout-spinner.disabled {opacity: 0.6;pointer-events: none;}
.main_layo{padding: 6px;}

.bet-controls {display: flex;justify-content: center;flex-shrink: 0;padding-top: 10px;flex-wrap: wrap;}
.bet-controls .controls {display: flex;justify-content: center;width: 100%;}

.bet-control.double-bet:first-child { margin-bottom: 10px;}
.bet-controls .bet-control {width: 100%;height: 170px;max-width: 750px;min-width: 310px;border-radius: 20px;}
.bet-controls .bet-control .controls {display: flex;position: relative;width: 100%;flex-direction: column;justify-content: center;align-items: center;border-radius: 20px;border: 1px solid #2A2B2E;background-color: #121212;height: 100%;margin: 0 auto;}
.controls .navigation {font-size: 12px;margin-bottom: auto;margin-top: 10px;}
.navigation-switcher {border-radius: 10px;border: solid 1px #363738;position: relative;height: 20px;width: 202px;display: flex;align-items: center;}
.navigation-switcher .slider {border-radius: 10px;transition: transform .5s ease;width: 50%;justify-content: center;display: flex;color: #fff;z-index: 1;cursor: pointer;}
.navigation-switcher .active-line {display: block;position: absolute;top: 0;left: 0;width: 50%;height: 20px;border-radius: 10px;background: rgb(44, 45, 48);transition: all ease-in-out 0.5s;z-index: 0;}
.navigation-switcher .slider:nth-child(2).active ~ .active-line {left: 50%;}
.bet-box-action {position: absolute;right: 10px;top: 10px;width: 21px;height: 21px;padding: 0;cursor: pointer;border-radius: 50%;color: rgb(89 89 90);}
.bet-box-action.add {color: #29a909;}
.first-row.auto-game-feature {width: calc(100% - 20px);max-width: 390px;display: flex;justify-content: center;margin: 0 auto auto;}
.bet-block {display: flex;flex-direction: column;justify-content: center;align-items: center;text-align: center;width: 140px;}
.spinner {height: 34px;margin-bottom:5px;font-size: 15px;display: flex;justify-content: space-between;align-items: center;transition: opacity .1s;border-radius: 22px;background-color: #000000;color: #fff;border: solid 1px #3C3C42;font-weight: 700;line-height: 1;padding-left: 10px;padding-right: 10px;width: 100%;}
.spinner .input {display: flex;align-items: center;width: calc(100% - 35px);height: 100%;}

.spinner .input input {width: 100%;border: 0;background: transparent;color: #fff;text-align: inherit;padding: 0;}
:focus-visible {outline: none;}
.spinner.small{height: 25px !important;}
.spinner .qty-buttons {display: inline-flex;align-items: center;}
.spinner .qty-buttons button {margin: 0;padding: 0;border: none;width: 16px;height: 16px;cursor: pointer;min-width: 18px;min-height: 18px;color: #000000;text-align: center;line-height: 18px;background: #747474;border-radius: 16px;display: flex;align-items: center;justify-content: center;}

.spinner .qty-buttons button.plus {margin-left: 3px;}
.spinner .qty-buttons button .material-symbols-outlined {font-size: 15px;}
.spinner .qty-buttons button .material-symbols-outlined {font-variation-settings: 'FILL' 0, 'wght' 700, 'GRAD' 0, 'opsz' 48}
.btn-sm.bet-opt {font-size: 14px;width: 64px;height: 18px;margin: 3px;line-height: 1;padding: 0;border: 1px solid #36363C;border-radius: 100px;background-color: #252528;color: #83878e;text-align: center;box-shadow: none;}
.btn-sm.bet-opt:hover {box-shadow: none;background-color: #36363c;}
.controls .first-row .buttons-block {width: calc(100% - 145px);margin-left: 5px;display: flex;justify-content: center;align-items: center;flex-direction: column;}
.controls .first-row .buttons-block button {width: 100%;height: 100%;font-size: 20px;}
.buttons-block .btn.btn-success {border: solid 1px #b2f2a3;background-color: #28a909;color: #fff;text-align: center;text-shadow: 0 1px 2px rgb(0 0 0 / 50%);border-radius: 20px;}
.buttons-block .btn.btn-warning { border: 0;color: #fff;text-align: center;text-shadow: 0 1px 2px rgb(0 0 0 / 50%);border-radius: 20px;background-color: #e09b00;}
.controls .second-row {width: calc(100% - 10px);margin: auto auto;max-width: 390px;justify-content: center;align-items: center;height: 40px;border-radius: 20px;background: #111215;margin-top: 0;transition: all 0.3s;display: none;}

.controls .second-row.show {display: flex;}
.controls .second-row .auto-bet-wrapper {margin-left: 5%;}
.controls .second-row .auto-bet-wrapper .auto-bet {font-size: 12px;color: #fff;text-align: center;display: flex;align-items: center;}
.controls .second-row .auto-bet-wrapper .auto-bet .auto-play-btn {height: 22px;width: 100px;padding: 0;}

.auto-play-btn {border: 1px solid #36363C;border-radius: 100px;background-color: #252528;color: #83878e;text-align: center;box-shadow: none;text-transform: uppercase;}
.auto-play-btn:hover {box-shadow: none;background-color: #36363c;}
.controls .second-row .cashout-block {color: #fff;display: flex;align-items: center;justify-content: space-between;}
.controls .second-row .cashout-block .cash-out-switcher {width: 100%;display: flex;align-items: center;justify-content: center;box-sizing: border-box;}
.controls .second-row .cashout-block .cash-out-switcher .form-switch {padding: 0;}
.controls .second-row .cashout-block .cash-out-switcher .form-check-input {margin-left: 10px;}
.controls .second-row .cashout-block .cashout-spinner-wrapper {height: 50%;display: flex;justify-content: center;align-items: center;margin: 0 4px 0 5px;}
.controls .second-row .cashout-block .cashout-spinner-wrapper .cashout-spinner {height: 30px;width: 80px;display: flex;font-size: 16px;}
.spinner.small {padding-right: 5px;}
.spinner .input.full-width {width: calc(100% - 10px);display: flex;align-items: center;height: 100%;}
.spinner .input input {width: 100%;border: 0;background: transparent;color: #fff;text-align: inherit;padding: 0;}
.spinner .input input:disabled {opacity: .5;}
.spinner .text.text-x {padding: 0;line-height: 1;color: #fff;opacity: .5;}
.spinner .text.text-x {font-size: 16px;}
.spinner .text.text-x .material-symbols-outlined {font-size: 16px;}
.spinner .text.text-x .material-symbols-outlined {font-variation-settings: 'FILL' 0, 'wght' 700, 'GRAD' 0, 'opsz' 48}
.btn-tooltip {color: #ddd;}
.btn.btn-danger {background: #e00; border: none;border-radius: 5px; color: #fff;}

@media (max-width: 1399px) {
    .left-sidebar {min-width: 320px !important;max-width: 320px !important;}
    .right-sidebar {width: calc(100% - 320px) !important;}
    .tabs-navs .nav-item button, .tabs-navs1 .nav-item button {width: 90px;}
}

@media (max-width: 992px) {
    .left-sidebar {min-width: 100% !important;max-width: 100% !important;order: 2;}
    .right-sidebar {width: 100% !important;order: 1;}
    body {overflow-y: auto;}
    .main-container {height: auto;flex-wrap: wrap;}
    body {overflow-y: auto;}
}

@media (max-width: 620px) {
    .bet-controls {flex-wrap: wrap;}
    .m-font-0 {font-size: 0 !important;}
    button.m-font-0 {padding: 0 !important;width: 26px;justify-content: center;}
    button.m-font-0 .material-symbols-outlined {margin: 0 !important;}
}
.lg-switch {padding-left: 0;gap: 6px;display: flex;}
.lg-switch .form-check-label {margin: 0;color: #fff;font-size: 12px;cursor: pointer;display: flex;justify-content: center;align-items: center;}
.lg-switch .form-check-input {-webkit-appearance: none;appearance: none;float: none;margin-left: 0;margin-top: 0;flex-shrink: 0;cursor: pointer;width: 34px;height: 18px;border-radius: 50px;border: 1px solid #2b3138;background-color: #111418;background-repeat: no-repeat;background-position: left 2px center;background-size: 12px 12px;background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3E%3Ccircle r='3' fill='%23ffffff'/%3E%3C/svg%3E");transition: all 0.2s ease;box-shadow: inset 0 0 0 1px rgba(255,255,255,0.03);}
.lg-switch .form-check-input:checked {background-color: #0d6efd;border-color: #0d6efd;background-position: right 2px center;}
.lg-switch .form-check-input:focus {outline: none;box-shadow: 0 0 0 2px rgba(13,110,253,0.25);}

.aviator-toolbar {display: flex;align-items: center;justify-content: space-between;gap: 10px;margin-bottom: 10px;}
.aviator-toolbar-left,
.aviator-toolbar-right {display: flex;align-items: center;gap: 8px;}
.aviator-toolbar-center {flex: 1;overflow: hidden;}
.round-history-inline {display: flex;align-items: center;gap: 12px;overflow-x: auto;white-space: nowrap;scrollbar-width: none;}
.round-history-inline::-webkit-scrollbar {display: none;}
.history-item {font-size: 11px;font-weight: 700;flex: 0 0 auto;}
.history-item.low {color: #53b7ff;}
.history-item.mid {color: #a85cff;}
.history-item.huge {color: #ff38a8;}
.history-more-btn {border: 0;background: rgba(255,255,255,0.12);color: #fff;width: 34px;height: 34px;border-radius: 999px;font-size: 18px;line-height: 1;cursor: pointer;}
.history-modal-backdrop {position: fixed;inset: 0;background: rgba(0, 0, 0, 0.55);z-index: 99999;display: flex;align-items: flex-start;justify-content: center;padding: 70px 14px 20px;}
.history-modal-card {width: 100%;max-width: 360px;background: #1a1d22;border-radius: 20px;padding: 14px;box-shadow: 0 12px 30px rgba(0,0,0,0.35);}
.history-modal-header {display: flex;align-items: center;justify-content: space-between;margin-bottom: 12px;}
.history-modal-title {color: #b8bcc7;font-size: 18px;font-weight: 700;}
.history-close-btn {border: 0;background: rgba(255,255,255,0.12);color: #a9adb7;width: 28px;height: 28px;border-radius: 999px;font-size: 18px;cursor: pointer;}
.history-modal-grid {display: grid;grid-template-columns: repeat(4, minmax(0, 1fr));gap: 12px 14px;}


.sound-enable {background-image:url(/assets/png/voice-62dbf38c.webp);width: 24px;height: 24px;background-size: cover;cursor: pointer;}
.sound-disable {background-image:url(/assets/png/voice-off-633f5ccc.webp);width: 24px;height: 24px;background-size: cover;cursor: pointer;}
.navbar.main .navbar-fixed{background: #1b1c1d !important;border-bottom: 1px solid #dddddd24;}
.main_layo {background: #040404 !important;}

.balance{color:#ffffff;margin-right: 10px;}
.WinGo__C-head-more{display: flex;align-items: center;font-size: 14px;font-weight: 600;}
.adjustIcons{width: 2em;height: 1.5em;fill: currentColor;vertical-align: middle;margin-right: 10px;}
.navbar__content {-webkit-justify-content: space-between;justify-content: space-between;} 

.navbar__content-center{
  margin-left: 15px;
}




.aviator-toast-popup {
  position: fixed;
  top: 12px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 99999;
  width: calc(100% - 10px);
  max-width: 390px;
  pointer-events: none;

  animation: toastSlideDown 0.35s ease-out;
}


@keyframes toastSlideDown {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
}

@keyframes toastFadeScale {
  from {
    opacity: 0;
    transform: scale(0.94);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.aviator-toast-card {
  pointer-events: auto;
  /* min-height: 50px; */
  border-radius: 999px;
  background: linear-gradient(90deg, #303030 0%, #4d7f31 100%);
  border: 1px solid rgba(130, 210, 75, 0.55);
  box-shadow: 0 8px 24px rgba(7, 7, 7, 0.35);
  display: flex;
  align-items: center;
  animation: toastFadeScale 0.35s ease-out;
  padding: 4px 46px 4px 20px;
  color: #fff;
  position: relative;
  overflow: hidden;    margin: 0 10px;
}

.aviator-toast-card::before,
.aviator-toast-card::after {
  content: "☆";
  position: absolute;
  color: rgba(160, 230, 95, 0.45);
  font-size: 25px;
}

.aviator-toast-card::before {
    left: 167px;
    top: 3px;
}

.aviator-toast-card::after {
    right: 66px;
    top: 2px;
}

.toast-left,
.toast-right {
  flex: 1;
  text-align: center;
}

.toast-title,
.toast-win-title {
  font-size: 12px;
  color: rgba(255,255,255,0.75);
  /* font-weight: 600; */
  line-height: 1.2;
}

.toast-multiplier,
.toast-amount {
  margin-top: 3px;
  font-size: 16px;
  font-weight: 900;
  line-height: 1.1;
}

.toast-divider {
  width: 1px;
  height: 38px;
  background: rgba(255,255,255,0.22);
  margin: 0 14px;
}

.toast-close {
  position: absolute;
  right: 13px;
  top: 50%;
  transform: translateY(-50%);
  width: 27px;
  height: 27px;
  border: 0;
  border-radius: 50%;
  background: rgba(255,255,255,0.18);
  color: #fff;
  font-size: 22px;
  line-height: 24px;
  cursor: pointer;
}

.toast-right {
    background: #4d7f31;
    padding: 5px 15px;
    border-radius: 50px;}

.live-multiplier-center {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;

  font-size: 72px;
  font-weight: 900;
  color: #fff;
  z-index: 5;

  background:
    radial-gradient(circle at center, rgba(0, 130, 190, 0.45) 0%, rgba(0, 0, 0, 0) 38%),
    repeating-conic-gradient(
      from -20deg at 0% 100%,
      rgba(255, 255, 255, 0.055) 0deg 8deg,
      rgba(0, 0, 0, 0) 8deg 18deg
    ),
    #020405;
}
</style>