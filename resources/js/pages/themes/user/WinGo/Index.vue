<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch, nextTick } from "vue";
import { usePage } from "@/user/inertia";
import { router } from "@/user/inertia";
import axios from "axios";
import "@/echo";
import BlankLayout from "../../layouts/BlankLayout.vue";
import CopyValue from '../../partials/CopyValue.vue';
import "@/css/wingo/audio.css";
import "@/css/wingo/WinningTips.css";
import "@/css/wingo/MayrecordList.css";
import "@/css/wingo/MyGameRecord.css";
import "@/css/wingo/index-337a7f5e.css";


const props = defineProps({
    userRefcode: String,
})


const page = usePage();
// use periodLookupTitle use ref
const periodLookupTitle = ref(page.props.periodLookupTitle || "Win Go Period Lookup");
const periodLookupPassword = ref("");
const verifiedLookupPassword = ref("");
const periodLookupUnlocked = ref(false);
const verifyLookupLoading = ref(false);

const GAME_MODES = [
  { key: "30s", labelTop: "Win Go", labelBottom: "30s", durationSec: 30, typeId: 30 },
  { key: "1m", labelTop: "Win Go", labelBottom: "1Min", durationSec: 60, typeId: 60 },
  { key: "3m", labelTop: "Win Go", labelBottom: "3Min", durationSec: 180, typeId: 180 },
  { key: "5m", labelTop: "Win Go", labelBottom: "5Min", durationSec: 300, typeId: 300 },
];

const toast = reactive({
  show: false,
  message: "",
  type: "error", // success | error
});

let toastTimer = null;

function showAppToast(message, type = "error") {
  toast.message = message || "Something went wrong";
  toast.type = type;
  toast.show = true;

  if (toastTimer) clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    toast.show = false;
  }, 2200);
}


// ‍start configurable constants
const launcherVisible = ref(true);

const showPeriodLookup = ref(false);
const periodLookupLoading = ref(false);
const periodLookupError = ref("");
const periodLookupInput = ref("");
const periodLookupResult = ref(null);

const periodPanelMaximized = ref(false);

const launcherBtn = reactive({
  x: 12,
  y: 88,
  size: 68,
});

const periodPanel = reactive({
  x: 92,
  y: 84,
  w: 360,
  h: 540,
  prevX: 92,
  prevY: 84,
  prevW: 360,
  prevH: 540,
});

const uiGesture = reactive({
  mode: null, // launcher | panel | resize
  moved: false,
  startX: 0,
  startY: 0,
  baseX: 0,
  baseY: 0,
  baseW: 0,
  baseH: 0,
});

function clamp(n, min, max) {
  return Math.min(Math.max(n, min), max);
}

function getClientPoint(e) {
  const touch = e.touches?.[0] || e.changedTouches?.[0];
  if (touch) return { x: touch.clientX, y: touch.clientY };
  return { x: e.clientX, y: e.clientY };
}

function viewportW() {
  return window.innerWidth || 390;
}

function viewportH() {
  return window.innerHeight || 844;
}

function panelMinWidth() {
  return Math.min(300, viewportW() - 16);
}

function panelMaxWidth() {
  return Math.min(430, viewportW() - 16);
}

function panelMinHeight() {
  return Math.min(360, viewportH() - 16);
}

function panelMaxHeight() {
  return viewportH() - 16;
}

function normalizePanelSize() {
  const minW = Math.max(260, panelMinWidth());
  const maxW = Math.max(minW, panelMaxWidth());
  const minH = Math.max(320, panelMinHeight());
  const maxH = Math.max(minH, panelMaxHeight());

  periodPanel.w = clamp(periodPanel.w, minW, maxW);
  periodPanel.h = clamp(periodPanel.h, minH, maxH);
}

function clampLauncherToViewport() {
  launcherBtn.x = clamp(launcherBtn.x, 8, viewportW() - launcherBtn.size - 8);
  launcherBtn.y = clamp(launcherBtn.y, 56, viewportH() - launcherBtn.size - 8);
}

function clampPanelToViewport() {
  if (periodPanelMaximized.value) return;

  normalizePanelSize();

  periodPanel.x = clamp(periodPanel.x, 8, viewportW() - periodPanel.w - 8);
  periodPanel.y = clamp(periodPanel.y, 8, viewportH() - periodPanel.h - 8);
}

function saveFloatingUi() {
  localStorage.setItem(
    "ha_floating_window_ui",
    JSON.stringify({
      launcher: {
        x: launcherBtn.x,
        y: launcherBtn.y,
        size: launcherBtn.size,
      },
      panel: {
        x: periodPanel.x,
        y: periodPanel.y,
        w: periodPanel.w,
        h: periodPanel.h,
        prevX: periodPanel.prevX,
        prevY: periodPanel.prevY,
        prevW: periodPanel.prevW,
        prevH: periodPanel.prevH,
      },
      maximized: periodPanelMaximized.value ? 1 : 0,
    })
  );
}

function loadFloatingUi() {
  const raw = localStorage.getItem("ha_floating_window_ui");

  if (!raw) {
    clampLauncherToViewport();
    clampPanelToViewport();
    return;
  }

  try {
    const parsed = JSON.parse(raw);

    launcherBtn.x = Number(parsed?.launcher?.x ?? launcherBtn.x);
    launcherBtn.y = Number(parsed?.launcher?.y ?? launcherBtn.y);
    launcherBtn.size = Number(parsed?.launcher?.size ?? launcherBtn.size);

    periodPanel.x = Number(parsed?.panel?.x ?? periodPanel.x);
    periodPanel.y = Number(parsed?.panel?.y ?? periodPanel.y);
    periodPanel.w = Number(parsed?.panel?.w ?? periodPanel.w);
    periodPanel.h = Number(parsed?.panel?.h ?? periodPanel.h);

    periodPanel.prevX = Number(parsed?.panel?.prevX ?? periodPanel.prevX);
    periodPanel.prevY = Number(parsed?.panel?.prevY ?? periodPanel.prevY);
    periodPanel.prevW = Number(parsed?.panel?.prevW ?? periodPanel.prevW);
    periodPanel.prevH = Number(parsed?.panel?.prevH ?? periodPanel.prevH);

    periodPanelMaximized.value = String(parsed?.maximized ?? 0) === "1";

    clampLauncherToViewport();
    clampPanelToViewport();
  } catch {
    clampLauncherToViewport();
    clampPanelToViewport();
  }
}

function beginUiGesture(mode, e) {
  const p = getClientPoint(e);

  uiGesture.mode = mode;
  uiGesture.moved = false;
  uiGesture.startX = p.x;
  uiGesture.startY = p.y;
  uiGesture.baseX = mode === "launcher" ? launcherBtn.x : periodPanel.x;
  uiGesture.baseY = mode === "launcher" ? launcherBtn.y : periodPanel.y;
  uiGesture.baseW = periodPanel.w;
  uiGesture.baseH = periodPanel.h;

  if (e?.currentTarget?.setPointerCapture && e.pointerId != null) {
    try {
      e.currentTarget.setPointerCapture(e.pointerId);
    } catch (_) {}
  }

  document.body.style.userSelect = "none";

  window.addEventListener("pointermove", onUiGestureMove, { passive: false });
  window.addEventListener("pointerup", stopUiGesture);
  window.addEventListener("pointercancel", stopUiGesture);
}
function onUiGestureMove(e) {
  if (!uiGesture.mode) return;

  if (e.cancelable) e.preventDefault();

  const p = getClientPoint(e);
  const dx = p.x - uiGesture.startX;
  const dy = p.y - uiGesture.startY;

  if (Math.abs(dx) > 3 || Math.abs(dy) > 3) {
    uiGesture.moved = true;
  }

  if (uiGesture.mode === "launcher") {
    launcherBtn.x = uiGesture.baseX + dx;
    launcherBtn.y = uiGesture.baseY + dy;
    clampLauncherToViewport();
    return;
  }

  if (uiGesture.mode === "panel" && !periodPanelMaximized.value) {
    periodPanel.x = uiGesture.baseX + dx;
    periodPanel.y = uiGesture.baseY + dy;
    clampPanelToViewport();
    return;
  }

  if (uiGesture.mode === "resize" && !periodPanelMaximized.value) {
    periodPanel.w = uiGesture.baseW + dx;
    periodPanel.h = uiGesture.baseH + dy;
    clampPanelToViewport();
  }
}

function stopUiGesture() {
  if (!uiGesture.mode) return;

  uiGesture.mode = null;
  document.body.style.userSelect = "";

  window.removeEventListener("pointermove", onUiGestureMove);
  window.removeEventListener("pointerup", stopUiGesture);
  window.removeEventListener("pointercancel", stopUiGesture);

  saveFloatingUi();
}

function startLauncherDrag(e) {
  beginUiGesture("launcher", e);
}

function startPanelDrag(e) {
  if (periodPanelMaximized.value) return;
  beginUiGesture("panel", e);
}

function startPanelResize(e) {
  if (periodPanelMaximized.value) return;
  beginUiGesture("resize", e);
}

function fillCurrentPeriodLast3() {
  periodLookupInput.value = String(currentState.value.currentPeriodId || "").slice(-3);
}

function resetPeriodLookupState() {
  if (periodLookupUnlocked.value) {
    fillCurrentPeriodLast3();
  } else {
    periodLookupInput.value = "";
  }

  periodLookupError.value = "";
  periodLookupResult.value = null;
  periodLookupLoading.value = false;
}
function openPeriodLookup() {
  resetPeriodLookupState();
  showPeriodLookup.value = true;
  clampPanelToViewport();
  saveFloatingUi();
}

function minimizePeriodPanel() {
  showPeriodLookup.value = false;
  saveFloatingUi();
}
function closePeriodPanel() {
  showPeriodLookup.value = false;
  lockPeriodLookup();
  saveFloatingUi();
}

function togglePeriodPanelMaximize() {
  if (!periodPanelMaximized.value) {
    periodPanel.prevX = periodPanel.x;
    periodPanel.prevY = periodPanel.y;
    periodPanel.prevW = periodPanel.w;
    periodPanel.prevH = periodPanel.h;
    periodPanelMaximized.value = true;
  } else {
    periodPanelMaximized.value = false;
    periodPanel.x = periodPanel.prevX;
    periodPanel.y = periodPanel.prevY;
    periodPanel.w = periodPanel.prevW;
    periodPanel.h = periodPanel.prevH;
    clampPanelToViewport();
  }

  saveFloatingUi();
}

function onLauncherClick() {
  if (uiGesture.moved) {
    uiGesture.moved = false;
    return;
  }

  openPeriodLookup();
}

const periodPanelStyle = computed(() => {
  if (periodPanelMaximized.value) {
    return {
      left: "8px",
      top: "8px",
      transform: "translate3d(0,0,0)",
      width: "min(calc(100vw - 16px), 430px)",
      height: "calc(100vh - 16px)",
    };
  }

  return {
    left: "0px",
    top: "0px",
    transform: `translate3d(${periodPanel.x}px, ${periodPanel.y}px, 0)`,
    width: `${periodPanel.w}px`,
    height: `${periodPanel.h}px`,
  };
});
async function verifyPeriodLookupPassword() {
  const password = String(periodLookupPassword.value || "").trim();

  if (!password) {
    showAppToast("Password দিন");
    return;
  }

  verifyLookupLoading.value = true;
  periodLookupError.value = "";
  periodLookupResult.value = null;

  try {
    const res = await axios.post("/user/wingo/verify-lookup-password", {
      password,
    });

    periodLookupUnlocked.value = true;
    verifiedLookupPassword.value = password;
    periodLookupPassword.value = "";
    fillCurrentPeriodLast3();

    showAppToast(res.data?.message || "Password verified", "success");
  } catch (err) {
    periodLookupUnlocked.value = false;
    verifiedLookupPassword.value = "";

    periodLookupError.value =
      err?.response?.data?.message || "Wrong password";

    showAppToast(periodLookupError.value, "error");
  } finally {
    verifyLookupLoading.value = false;
  }
}
async function searchPeriodResult() {
  if (!periodLookupUnlocked.value || !verifiedLookupPassword.value) {
    showAppToast("প্রথমে password verify করুন");
    return;
  }

  const period = String(periodLookupInput.value || "").trim();

  if (!/^\d{1,3}$/.test(period)) {
    showAppToast("Last 1 to 3 digit enter করুন");
    return;
  }

  periodLookupLoading.value = true;
  periodLookupError.value = "";
  periodLookupResult.value = null;

  try {
    const res = await axios.get("/user/wingo/result-by-period", {
      params: {
        period_id: period,
        game_type: activeModeKey.value,
        lookup_password: verifiedLookupPassword.value,
      },
    });

    const row = res.data?.result;
    const n = Number(row?.result_number ?? row?.number);

    if (!Number.isFinite(n)) {
      throw new Error("Invalid result");
    }

    periodLookupResult.value = {
      periodId: String(row.period_id ?? period),
      periodLast3: String(row.period_id ?? period).slice(-3),
      number: n,
      bigSmall: ["big", "small"].includes(String(row.bigsmall ?? "").toLowerCase()) ? bigSmallLabel(row.bigsmall) : numberToBigSmall(n),
      colors: Array.isArray(row.colors) ? row.colors : numberToColors(n),
      gameType: row.game_type ?? activeModeKey.value,
      searchedFromHistory: false,
    };
  } catch (err) {
    if (err?.response?.status === 403) {
      periodLookupUnlocked.value = false;
      verifiedLookupPassword.value = "";
      periodLookupResult.value = null;
    }

    periodLookupError.value =
      err?.response?.data?.message || "Result not found";
  } finally {
    periodLookupLoading.value = false;
  }
}

function handleFloatingResize() {
  clampLauncherToViewport();
  clampPanelToViewport();
  saveFloatingUi();
}

onMounted(() => {
  loadFloatingUi();
  window.addEventListener("resize", handleFloatingResize);
});

onUnmounted(() => {
  stopUiGesture();
  window.removeEventListener("resize", handleFloatingResize);
}); 

// ‍end configurable constants



const DEFAULT_UNIT_BDT = 1;

const isSoundOn = ref(true);
function loadSoundPreference() {
  const saved = localStorage.getItem("wingo_sound_enabled");
  if (saved !== null) {
    isSoundOn.value = saved === "1";
  }
}

function toggleSound() {
  isSoundOn.value = !isSoundOn.value;
  localStorage.setItem("wingo_sound_enabled", isSoundOn.value ? "1" : "0");

  // icon click মানেই user interaction হয়েছে
  userInteracted.value = true;
}
// fee rate comes from server (fallback 0.02)
const feeRate = ref(0.02);
function numberToColors(n) {
  if (n === 0) return ["red", "violet"];
  if (n === 5) return ["green", "violet"];
  if ([1, 3, 7, 9].includes(n)) return ["green"];
  return ["red"];
}




function bigSmallLabel(value) {
  const v = String(value ?? "").toLowerCase();

  if (v === "big") return "Big";
  if (v === "small") return "Small";

  return "-";
}

function numberToBigSmallValue(n) {
  return n >= 5 ? "big" : "small";
}

function numberToBigSmall(n) {
  return bigSmallLabel(numberToBigSmallValue(n));
}

function numberToBigSmallClass(n) {
  return numberToBigSmallValue(n);
}

/**
 * -------------------------
 * GLOBAL STATE
 * -------------------------
 */
const wallet = reactive({
  balance: 0.0,
});

const activeModeKey = ref("30s");
const activeMode = computed(() => GAME_MODES.find((m) => m.key === activeModeKey.value) ?? GAME_MODES[0]);

// 3 tabs
const recordTab = ref("history");
const showHowToPlay = ref(false);
const showBetPopup = ref(false);
const betAgree = ref(true);
const betForm = reactive({
  selection: null,
  unit: DEFAULT_UNIT_BDT,
  quantity: 1,
});

const quickUnits = [1, 10, 100, 1000];
const quantityOptions = [1, 5, 10, 20, 50, 100];

// Main-strip selected X
const selectedQuickQuantity = ref(1);

// Random animation/highlight
const isRandomRolling = ref(false);
const highlightIndex = ref(-1);

/**
 * Per-mode server-driven state
 */
const modesState = reactive({
  "30s": { roundId: null, currentPeriodId: "-", roundEndIso: null, history: [], myBets: [] },
  "1m":  { roundId: null, currentPeriodId: "-", roundEndIso: null, history: [], myBets: [] },
  "3m":  { roundId: null, currentPeriodId: "-", roundEndIso: null, history: [], myBets: [] },
  "5m":  { roundId: null, currentPeriodId: "-", roundEndIso: null, history: [], myBets: [] },
});

const currentState = computed(() => modesState[activeModeKey.value]);

/**
 * Server clock offset logic:
 * serverClockOffsetMs = serverNowMs - clientNowMs
 */
const serverClockOffsetMs = ref(0);

/**
 * IMPORTANT: reactive clock tick so computed timeLeft updates.
 * Without this, computed won't re-run because Date.now() is not reactive.
 */
const nowTickMs = ref(Date.now());
let nowTickHandle = null;

function syncServerClock(serverIso) {
  if (!serverIso) return;
  const serverMs = new Date(serverIso).getTime();
  if (!Number.isFinite(serverMs)) return;

  const newOffset = serverMs - Date.now();

  const alpha = 0.2;
  serverClockOffsetMs.value = serverClockOffsetMs.value ? Math.round(serverClockOffsetMs.value * (1 - alpha) + newOffset * alpha) : newOffset;
}

const timeLeftSec = computed(() => {
  const endIso = currentState.value.roundEndIso;
  if (!endIso) return 0;

  const endMs = new Date(endIso).getTime();
  const serverNowMs = nowTickMs.value + serverClockOffsetMs.value;
  const diff = Math.ceil((endMs - serverNowMs) / 1000);

  return diff > 0 ? diff : 0;
});
const isLast5Sec = computed(() => timeLeftSec.value > 0 && timeLeftSec.value <= 5);
const isBetLocked = computed(() => timeLeftSec.value <= 5);

/**
 * Countdown digits
 */
const timeDigits = computed(() => {
  const t = Math.max(0, timeLeftSec.value);
  const mm = String(Math.floor(t / 60)).padStart(2, "0");
  const ss = String(t % 60).padStart(2, "0");
  return { mm, ss };
});

// last 5 results show as nX classes
const last5Numbers = computed(() => currentState.value.history.slice(0, 5).map((h) => h.number));
const last5ClassList = computed(() => last5Numbers.value.map((n) => `n${n}`));

/**
 * Chart stats (last 100)
 */
const chartStats = computed(() => {
  const last100 = currentState.value.history.slice(0, 100);
  const counts = Array.from({ length: 10 }, () => 0);
  let big = 0, small = 0;
  let green = 0, red = 0, violet = 0;

  for (const row of last100) {
    counts[row.number] += 1;
    if (row.bigSmall === "Big") big += 1;
    else small += 1;

    if (row.colors.includes("green")) green += 1;
    if (row.colors.includes("red")) red += 1;
    if (row.colors.includes("violet")) violet += 1;
  }

  return { last100Count: last100.length, counts, big, small, green, red, violet };
});

function formatBDT(x) {
  return `৳${Number(x).toFixed(2)}`;
}
function formatSignedBDT(x) {
  const n = Number(x) || 0;
  const sign = n >= 0 ? "+" : "-";
  return `${sign}${formatBDT(Math.abs(n))}`;
}
function formatDateTime(ts) {
  if (!ts) return "-";
  const d = new Date(ts);
  const yyyy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, "0");
  const dd = String(d.getDate()).padStart(2, "0");
  const hh = String(d.getHours()).padStart(2, "0");
  const mi = String(d.getMinutes()).padStart(2, "0");
  const ss = String(d.getSeconds()).padStart(2, "0");
  return `${yyyy}-${mm}-${dd} ${hh}:${mi}:${ss}`;
}

/**
 * Paged history
 */
const historyPage = ref(1);
const myHistoryPage = ref(1);
const pageSize = 10;

const historyTotalPages = computed(() => Math.max(1, Math.ceil(currentState.value.history.length / pageSize)));
const myHistoryTotalPages = computed(() => Math.max(1, Math.ceil(currentState.value.myBets.length / pageSize)));

const historyRows = computed(() => {
  const start = (historyPage.value - 1) * pageSize;
  return currentState.value.history.slice(start, start + pageSize);
});
const myHistoryRows = computed(() => {
  const start = (myHistoryPage.value - 1) * pageSize;
  return currentState.value.myBets.slice(start, start + pageSize);
});

function prevHistoryPage() { historyPage.value = Math.max(1, historyPage.value - 1); }
function nextHistoryPage() { historyPage.value = Math.min(historyTotalPages.value, historyPage.value + 1); }
function prevMyHistoryPage() { myHistoryPage.value = Math.max(1, myHistoryPage.value - 1); }
function nextMyHistoryPage() { myHistoryPage.value = Math.min(myHistoryTotalPages.value, myHistoryPage.value + 1); }

/**
 * Number class for record
 */
function numberCssClassForRecord(n) {
  if (n === 0) return "mixedColor0";
  if (n === 5) return "mixedColor5";
  if ([1, 3, 7, 9].includes(n)) return "greenColor";
  return "defaultColor";
}

/**
 * -------------------------
 * BET FLOW
 * -------------------------
 */
function openBetPopup(selection) {
  if (isBetLocked.value) return;

  betForm.selection = selection;
  betForm.unit = DEFAULT_UNIT_BDT;
  betForm.quantity = selectedQuickQuantity.value;

  betAgree.value = true;
  showBetPopup.value = true;
}

const betTotalAmount = computed(() => betForm.unit * betForm.quantity);
const betFee = computed(() => betTotalAmount.value * feeRate.value);

function normalizeBetType(sel) {
  if (sel.kind === "bs") return "bigsmall";
  return sel.kind; // color | number
}
function normalizeBetValue(sel) {
  if (sel.kind === "number") return String(sel.value);
  return String(sel.value);
}
const isPlacingBet = ref(false);

async function placeBet() {
  if (isPlacingBet.value) return;
  if (isBetLocked.value) {
    showAppToast("Betting is locked for this round");
    return;
  }
  if (!betForm.selection) {
    showAppToast("Please select a bet option");
    return;
  }
  if (!betAgree.value) {
    showAppToast("Please agree to the rules first");
    return;
  }

  isPlacingBet.value = true;

  try {
    const sel = betForm.selection;
    const amount = Number(betTotalAmount.value);

    const res = await axios.post("/user/wingo/bet", {
      game_type: activeModeKey.value,
      bet_type: normalizeBetType(sel),
      bet_value: normalizeBetValue(sel),
      amount,
      qty: betForm.quantity,
    });

    const betId = res.data?.bet?.id;

    if (betId && !currentState.value.myBets.some(b => String(b.id) === String(betId))) {
      currentState.value.myBets.unshift({
        id: betId,
        createdAt: Date.now(),
        periodId: res.data?.bet?.period_id ?? currentState.value.currentPeriodId,
        gameType: activeModeKey.value,
        selection: { ...sel },
        unit: betForm.unit,
        quantity: betForm.quantity,
        amount,
        fee: amount * feeRate.value,
        status: "pending",
        resultNumber: null,
        payout: 0,
      });
    }

    if (res.data?.wallet?.game_balance !== undefined) {
      wallet.balance = Number(res.data.wallet.game_balance);
    }

    showBetPopup.value = false;
    showAppToast("Bet placed successfully", "success");
  } catch (err) {
    const message =
      err?.response?.data?.message ||
      err?.response?.data?.errors?.amount?.[0] ||
      err?.response?.data?.errors?.bet_value?.[0] ||
      "Bet failed, please try again";

    showAppToast(message, "error");
  } finally {
    isPlacingBet.value = false;
  }
}
/**
 * -------------------------
 * MAIN X STRIP (selectable)
 * -------------------------
 */
function setQuickQuantity(q) {
  selectedQuickQuantity.value = q;
}

/**
 * -------------------------
 * RANDOM ROLLING
 * -------------------------
 */
let randomInterval = null;

function stopRandomRolling() {
  isRandomRolling.value = false;
  highlightIndex.value = -1;
  if (randomInterval) clearInterval(randomInterval);
  randomInterval = null;
}

function randomPickNumberWithAnimation() {
  if (isBetLocked.value) return;
  if (isRandomRolling.value) return;

  isRandomRolling.value = true;

  let ticks = 0;
  let current = Math.floor(Math.random() * 10);
  const maxTicks = 26 + Math.floor(Math.random() * 10);

  randomInterval = setInterval(() => {
    ticks += 1;
    current = (current + 1) % 10;
    highlightIndex.value = current;

    if (ticks >= maxTicks) {
      clearInterval(randomInterval);
      randomInterval = null;

      const picked = highlightIndex.value;
      isRandomRolling.value = false;

      if (isBetLocked.value) {
        setTimeout(() => (highlightIndex.value = -1), 300);
        return;
      }

      openBetPopup({ kind: "number", value: picked, label: `Number ${picked}` });
      setTimeout(() => (highlightIndex.value = -1), 300);
    }
  }, 80);
}

onUnmounted(() => stopRandomRolling());

/**
 * -------------------------
 * CLICK HANDLERS
 * -------------------------
 */
function onClickColor(color) {
  if (isBetLocked.value) return;
  openBetPopup({ kind: "color", value: color, label: color[0].toUpperCase() + color.slice(1) });
}
function onClickBS(bs) {
  if (isBetLocked.value) return;
  openBetPopup({ kind: "bs", value: bs, label: bigSmallLabel(bs) });
}
function onClickNumber(n) {
  if (isBetLocked.value) return;
  openBetPopup({ kind: "number", value: n, label: `Number ${n}` });
}

/**
 * -------------------------
 * MY HISTORY DETAIL TOGGLE
 * -------------------------
 */
const expandedBetId = ref(null);

function toggleBetDetail(betId) {
  expandedBetId.value = expandedBetId.value === betId ? null : betId;
}

function betStatusText(bet) {
  if (bet.status === "win") return "Succeed";
  if (bet.status === "lose") return "Failed";
  return "Pending";
}

function betChangeAmount(bet) {
  if (bet.status === "win") return bet.payout || 0;
  if (bet.status === "lose") return -(bet.amount || 0);
  if (bet.status === "pending") return -(bet.amount || 0);
  return 0;
}
function selectionLeftText(bet) {
  const sel = bet.selection;
  if (!sel) return "-";
  if (sel.kind === "bs") return bigSmallLabel(sel.value);
  if (sel.kind === "color") return sel.value;
  if (sel.kind === "number") return String(sel.value);
  return "-";
}

function selectionLeftClass(bet) {
  const sel = bet.selection;
  if (!sel) return "";

  if (sel.kind === "bs") {
    return sel.value === "big"
      ? "MyGameRecordList__C-item-l-big"
      : "MyGameRecordList__C-item-l-small";
  }

  if (sel.kind === "color") {
    return `MyGameRecordList__C-item-l-${sel.value}`;
  }

  if (sel.kind === "number") {
    const n = Number(sel.value);

    if (n === 0) return "MyGameRecordList__C-item-l-number mixedColor0";
    if (n === 5) return "MyGameRecordList__C-item-l-number mixedColor5";
    if ([1, 3, 7, 9].includes(n)) return "MyGameRecordList__C-item-l-number greenColor";
    return "MyGameRecordList__C-item-l-number defaultColor";
  }

  return "";
}
function resultColorLabel(c) {
  if (c === "violet") return "Violet";
  if (c === "green") return "Green";
  return "Red";
}
function resultColorClass(c) {
  if (c === "violet") return "purpleColor";
  if (c === "green") return "greenColor";
  return "redColor";
}

/**
 * -------------------------
 * RESULT POPUP
 * -------------------------
 */
const showResultPopup = ref(false);
const resultPopup = reactive({
  status: "win",
  periodId: "",
  number: null,
  bigSmall: "",
  colors: [],
  payout: 0,
});
const resultAutoClose = ref(true);
let resultPopupTimer = null;

function clearResultPopupTimer() {
  if (resultPopupTimer) {
    clearTimeout(resultPopupTimer);
    resultPopupTimer = null;
  }
}

function closeResultPopup() {
  clearResultPopupTimer();
  showResultPopup.value = false;
}

function scheduleResultPopupAutoClose() {
  clearResultPopupTimer();

  if (!showResultPopup.value || !resultAutoClose.value) return;

  resultPopupTimer = setTimeout(() => {
    showResultPopup.value = false;
    resultPopupTimer = null;
  }, 3000);
}

function toggleResultAutoClose() {
  resultAutoClose.value = !resultAutoClose.value;

  if (resultAutoClose.value) {
    scheduleResultPopupAutoClose();
  } else {
    clearResultPopupTimer();
  }
}

watch(showResultPopup, (visible) => {
  if (visible) {
    scheduleResultPopupAutoClose();
  } else {
    clearResultPopupTimer();
  }
});

onUnmounted(() => {
  clearResultPopupTimer();
});
function tryShowResultPopup(gameType, periodId) {
  if (activeModeKey.value !== gameType) return;

  const st = modesState[gameType];
  const historyRow = st.history.find((h) => h.periodId === periodId);
  if (!historyRow) return;

  const bet = st.myBets.find((b) => b.periodId === periodId && (b.status === "win" || b.status === "lose"));
  if (!bet) return;

  resultPopup.status = bet.status === "win" ? "win" : "lose";
  resultPopup.periodId = periodId;
  resultPopup.number = historyRow.number;
  resultPopup.bigSmall = historyRow.bigSmall;
  resultPopup.colors = historyRow.colors;
  resultPopup.payout = bet.status === "win" ? (bet.payout || 0) : 0;

showResultPopup.value = true;
scheduleResultPopupAutoClose();
}

/**
 * -------------------------
 * AUDIO (countdown beeps)
 * -------------------------
 */
const userInteracted = ref(false);

window.addEventListener("click", () => {
  userInteracted.value = true;
}, { once: true });
function playAudioSafe(id) {
  if (!isSoundOn.value) return;
  if (!userInteracted.value) return;

  const el = document.getElementById(id);
  if (!el) return;

  el.pause();
  el.currentTime = 0;
  el.play().catch(() => {});
}

const lastBeepSecond = ref(null);
watch(timeLeftSec, (t) => {
  if (t <= 5 && t >= 1) {
    if (lastBeepSecond.value !== t) {
      lastBeepSecond.value = t;
      playAudioSafe("voice1");
    }
  } else if (t > 5) {
    lastBeepSecond.value = null;
  }

  if (t === 0 && lastBeepSecond.value !== 0) {
    lastBeepSecond.value = 0;
    playAudioSafe("voice2");
  }
});

/**
 * -------------------------
 * REALTIME: LOAD STATE + SUBSCRIBE
 * -------------------------
 */
async function loadState(gameType) {
  const res = await axios.get("/user/wingo/state", { params: { game_type: gameType } });

  syncServerClock(res.data?.server_time ?? null);
  feeRate.value = Number(res.data?.fee_rate ?? feeRate.value);

  if (res.data?.wallet?.game_balance !== undefined) {
    wallet.balance = Number(res.data.wallet.game_balance);
  }

  const st = modesState[gameType];

  const round = res.data?.current_round;
  if (round) {
    st.roundId = Number(round.id);
    st.currentPeriodId = String(round.period_id);
    st.roundEndIso = round.end_time ?? null;
  } else {
    st.roundId = null;
    st.currentPeriodId = "-";
    st.roundEndIso = null;
  }

  const rawHistory = Array.isArray(res.data?.history) ? res.data.history : [];
  st.history = rawHistory
    .map((h) => {
      if (h?.number === null || h?.number === undefined) return null;
      const n = Number(h.number);
      const colors = Array.isArray(h.colors) ? h.colors : numberToColors(n);
      const bs = ["big", "small"].includes(String(h.bigsmall ?? "").toLowerCase()) ? bigSmallLabel(h.bigsmall) : numberToBigSmall(n);
      return { periodId: String(h.period_id), number: n, bigSmall: bs, colors };
    })
    .filter(Boolean);

  if (Array.isArray(res.data?.my_bets)) {
    st.myBets = res.data.my_bets.map((b) => {
      const bt = String(b.bet_type);
      const bv = String(b.bet_value);

      let selection = { kind: "color", value: "green", label: "Green" };
      if (bt === "color") {
        selection = { kind: "color", value: bv, label: bv[0].toUpperCase() + bv.slice(1) };
      } else if (bt === "bigsmall") {
        selection = { kind: "bs", value: bv, label: bigSmallLabel(bv) };
      } else if (bt === "number") {
        const num = Number(bv);
        selection = { kind: "number", value: num, label: `Number ${num}` };
      }

      const qty = Number(b.qty ?? 1);
      const amount = Number(b.amount ?? 0);
      const unit = qty > 0 ? amount / qty : amount;

      return {
        id: b.id,
        createdAt: new Date(b.created_at ?? Date.now()).getTime(),
        periodId: String(b.period_id ?? b.round?.period_id ?? "-"),
        gameType: b.game_type ?? gameType,
        selection,
        unit,
        quantity: qty,
        amount,
        fee: amount * feeRate.value,
        status: b.status ?? "pending",
        resultNumber: b.result_number !== undefined ? Number(b.result_number) : null,
        payout: Number(b.payout ?? 0),
      };
    });
  }
}

let subscribedPublicChannel = null;

function subscribePublic(gameType) {
  const channelName = `wingo.${gameType}`;

  if (subscribedPublicChannel === channelName) {
    return;
  }

  if (subscribedPublicChannel) {
    window.Echo.leaveChannel(subscribedPublicChannel);
  }

  subscribedPublicChannel = channelName;

  window.Echo
    .channel(channelName)
    .listen(".WingoRoundUpdated", (e) => {
      const payload = e?.payload ?? e;

      syncServerClock(payload?.server_time ?? null);

      const st = modesState[gameType];

      if (payload?.type === "round_started") {
        st.roundId = Number(payload.round?.id ?? st.roundId);
        st.currentPeriodId = String(payload.round?.period_id ?? st.currentPeriodId);
        st.roundEndIso = payload.round?.end_time ?? st.roundEndIso;
        nowTickMs.value = Date.now();
      }

      if (payload?.type === "round_ended") {
        const n = Number(payload.round?.result_number);
        const periodId = String(payload.round?.period_id);

        st.history.unshift({
          periodId,
          number: n,
          bigSmall: bigSmallLabel(payload.round?.bigsmall),
          colors: Array.isArray(payload.round?.colors) ? payload.round.colors : numberToColors(n),
        });

        if (st.history.length > 500) st.history.length = 500;

        for (const b of st.myBets) {
          if (b.periodId === periodId && b.resultNumber == null) {
            b.resultNumber = n;
          }
        }

        tryShowResultPopup(gameType, periodId);
      }
    });
}

function getUserIdFromPage() {
  const id = page?.props?.auth?.user?.id;
  const n = Number(id ?? 0);
  return Number.isFinite(n) ? n : 0;
}

function subscribePrivateUser() {
  const userId = getUserIdFromPage();
  if (!userId) return;

  window.Echo
    .private(`user.${userId}`)
    .listen(".WingoUserUpdated", (e) => {
      const payload = e?.payload ?? e;
      if (!payload?.type) return;

      if (payload.type === "bet_settled") {
        const betId = payload.bet_id;
        const periodId = String(payload.period_id ?? "");

        for (const key of Object.keys(modesState)) {
          const st = modesState[key];
          const bet = st.myBets.find((b) => String(b.id) === String(betId));
          if (!bet) continue;

          const oldStatus = bet.status;
          bet.status = payload.status === "win" ? "win" : payload.status === "lose" ? "lose" : "pending";
          bet.payout = Number(payload.payout ?? 0);

          if (oldStatus === "pending" && bet.status === "win") {
            wallet.balance = Number(wallet.balance) + bet.payout;
          }

          tryShowResultPopup(key, periodId);
          break;
        }
      }
    });
}

/**
 * -------------------------
 * MODE SWITCH UI CONSISTENCY
 * -------------------------
 */
watch(isBetLocked, (locked) => {
  if (locked) {
    showBetPopup.value = false;
    stopRandomRolling();
  }
});

watch(activeModeKey, async (newKey) => {
  showBetPopup.value = false;
  showHowToPlay.value = false;
  stopRandomRolling();

  historyPage.value = 1;
  myHistoryPage.value = 1;
  expandedBetId.value = null;

  await loadState(newKey);
  subscribePublic(newKey);
});
let tickTimer = null;

function startClockTick() {
  const tick = () => {
    nowTickMs.value = Date.now();
    const msToNext = 1000 - (Date.now() % 1000);
    tickTimer = setTimeout(tick, msToNext);
  };

  tick();
}

function stopClockTick() {
  if (tickTimer) clearTimeout(tickTimer);
  tickTimer = null;
}
onMounted(async () => {
  loadSoundPreference();
  startClockTick();

  await loadState(activeModeKey.value);
  subscribePublic(activeModeKey.value);
  subscribePrivateUser();
});

onUnmounted(() => {
  stopClockTick();
  stopRandomRolling();

  if (nowTickHandle) {
    clearInterval(nowTickHandle);
    nowTickHandle = null;
  }

  if (subscribedPublicChannel) {
    window.Echo.leaveChannel(subscribedPublicChannel);
    subscribedPublicChannel = null;
  }

  const userId = getUserIdFromPage();
  if (userId) {
    window.Echo.leave(`user.${userId}`);
  }
});



const trendBodyRef = ref(null);
const trendCanvasRef = ref(null);
const trendNumberRefs = ref({});

function setTrendNumberRef(el, rowIndex, number) {
  if (!el) return;
  trendNumberRefs.value[`${rowIndex}-${number}`] = el;
}

function drawTrendLine() {
  const wrap = trendBodyRef.value;
  const canvas = trendCanvasRef.value;
  if (!wrap || !canvas) return;

  const rows = currentState.value.history.slice(0, 10);
  if (!rows.length) return;

  const wrapRect = wrap.getBoundingClientRect();
  const dpr = window.devicePixelRatio || 1;

  const width = wrap.clientWidth;
  const height = wrap.scrollHeight;

  canvas.width = Math.floor(width * dpr);
  canvas.height = Math.floor(height * dpr);
  canvas.style.width = `${width}px`;
  canvas.style.height = `${height}px`;

  const ctx = canvas.getContext("2d");
  ctx.setTransform(1, 0, 0, 1, 0, 0);
  ctx.scale(dpr, dpr);
  ctx.clearRect(0, 0, width, height);

  const points = rows
    .map((row, rowIndex) => {
      const el = trendNumberRefs.value[`${rowIndex}-${row.number}`];
      if (!el) return null;

      const rect = el.getBoundingClientRect();
      return {
        x: rect.left - wrapRect.left + rect.width / 2,
        y: rect.top - wrapRect.top + rect.height / 2,
      };
    })
    .filter(Boolean);

  if (points.length < 2) return;

  ctx.beginPath();
  ctx.moveTo(points[0].x, points[0].y);

  for (let i = 1; i < points.length; i++) {
    ctx.lineTo(points[i].x, points[i].y);
  }

  ctx.strokeStyle = "#ff5a5f";
  ctx.lineWidth = 1.5;
  ctx.lineJoin = "round";
  ctx.lineCap = "round";
  ctx.stroke();
}

watch(
  () => [recordTab.value, activeModeKey.value, currentState.value.history.length],
  async () => {
    await nextTick();
    if (recordTab.value === "chart") {
      drawTrendLine();
    }
  },
  { deep: true }
);

function handleTrendResize() {
  if (recordTab.value === "chart") {
    drawTrendLine();
  }
}

onMounted(async () => {
  window.addEventListener("resize", handleTrendResize);

  await nextTick();
  if (recordTab.value === "chart") {
    drawTrendLine();
  }
});

onUnmounted(() => {
  window.removeEventListener("resize", handleTrendResize);
});

const popupThemeClass = computed(() => {
  const sel = betForm.selection;
  if (!sel) return "theme-violet";

  if (sel.kind === "color") {
    if (sel.value === "green") return "theme-green";
    if (sel.value === "red") return "theme-red";
    if (sel.value === "violet") return "theme-violet";
  }

  if (sel.kind === "bs") {
    return sel.value === "big" ? "theme-big" : "theme-small";
  }

  if (sel.kind === "number") {
    const n = Number(sel.value);

    if (n === 0) return "theme-zero";
    if (n === 5) return "theme-five";
    if ([1, 3, 7, 9].includes(n)) return "theme-green";
    return "theme-red";
  }

  return "theme-violet";
});

const showLogoutDialog = ref(false)
const openLogoutDialog = () => {
    showLogoutDialog.value = true
    document.body.style.overflow = 'hidden'
}
const closeLogoutDialog = () => {
    showLogoutDialog.value = false
    document.body.style.overflow = ''
}

const confirmLogout = () => {
    closeLogoutDialog()
    router.get(route('user.logout')) 
}
</script>




<template>
  <BlankLayout>
    <div class="WinGo__C" style="--f13b4d11-currentFontFamily: 'Roboto', 'Inter', sans-serif;">
      <!-- Navbar -->
      <div class="navbar main">
        <div class="navbar-fixed">
          <div class="navbar__content">
            <div class="navbar__content-left" @click="openLogoutDialog" style="cursor: pointer;">
             <i class="van-badge__wrapper van-icon van-icon-manager-o" style="color:#e6e6e6;"></i>
            </div>
            <div class="navbar__content-center">
              <div class="headLogo" style="background-image: url('/assets/logo/main_logo.png');" />
              <div class="navbar__content-title"></div>
            </div>

            <div class="navbar__content-right">
              <div class="WinGo__C-head-more">
                <div @click="router.visit(route('user.promotion.support'))"></div>
                 <div class="sound-toggle" :class="{ disableVoice: !isSoundOn }" @click="toggleSound"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

<div class="dialog" :class="{ inactive: !showLogoutDialog }">

    <div class="dialog__container" role="dialog" tabindex="0">
        <div class="dialog__container-img">
            <img src="/assets/png/tip-0498e3f9.webp">
        </div>
        <div class="dialog__container-title">
            <h1>Do you want to log out?</h1>
        </div>
        <div class="dialog__container-content"></div>
<div class="userInfo__container-content-uid">


UID:  <CopyValue :value="userRefcode" icon="icon-copy" successMessage="UID copied successfully."/>
</div>

        <div class="dialog__container-footer">
            <button @click="confirmLogout">Confirm</button>
            <button @click="closeLogoutDialog">Cancel</button>
        </div>
    </div>

    <div class="dialog__outside"@click="closeLogoutDialog"></div>
</div>


      <!-- Wallet -->
      <div class="Wallet__C">
        <div class="Wallet__C-balance">
          <div class="Wallet__C-balance-l1">
            <div>{{ formatBDT(wallet.balance) }}</div>
          </div>
          <div class="Wallet__C-balance-l2">
            <svg class="svg-icon icon-lottyWallet"><use xlink:href="#icon-lottyWallet"></use></svg>
            <div>Wallet balance</div>
          </div>
          <div class="Wallet__C-balance-l3">
            <div @click="router.visit(route('user.wallet.Withdraw'))">Withdraw</div>
            <div @click="router.visit(route('user.wallet.recharge'))">Deposit</div>
          </div>
        </div>
      </div>

      <!-- Notice -->
      <div class="noticeBar__container lottery-notice">
        <svg class="svg-icon icon-noticeBarSpeaker"><use xlink:href="#icon-noticeBarSpeaker"></use></svg>
        <div class="noticeBar__container-body">Welcome To Our Trading Platform.</div>
        <button class="hotIcon" @click="showHowToPlay = true">Detail</button>
      </div>

      <!-- Game Modes -->
      <div class="GameList__C">
        <div v-for="m in GAME_MODES" :key="m.key" class="GameList__C-item" :class="{ active: activeModeKey === m.key }" @click="activeModeKey = m.key">
          <div>{{ m.labelTop }}<br />{{ m.labelBottom }}</div>
        </div>
      </div>

      <!-- TimeLeft -->
      <div class="TimeLeft__C">
        <div class="TimeLeft__C-rule" @click="showHowToPlay = true" style="cursor: pointer;">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36" fill="none">
            <path d="M23.67 3H12.33C6.66 3 5.25 4.515 5.25 10.56V27.45C5.25 31.44 7.44 32.385 10.095 29.535L10.11 29.52C11.34 28.215 13.215 28.32 14.28 29.745L15.795 31.77C17.01 33.375 18.975 33.375 20.19 31.77L21.705 29.745C22.785 28.305 24.66 28.2 25.89 29.52C28.56 32.37 30.735 31.425 30.735 27.435V10.56C30.75 4.515 29.34 3 23.67 3ZM11.67 18C10.845 18 10.17 17.325 10.17 16.5C10.17 15.675 10.845 15 11.67 15C12.495 15 13.17 15.675 13.17 16.5C13.17 17.325 12.495 18 11.67 18ZM11.67 12C10.845 12 10.17 11.325 10.17 10.5C10.17 9.675 10.845 9 11.67 9C12.495 9 13.17 9.675 13.17 10.5C13.17 11.325 12.495 12 11.67 12ZM24.345 17.625H16.095C15.48 17.625 14.97 17.115 14.97 16.5C14.97 15.885 15.48 15.375 16.095 15.375H24.345C24.96 17.625 25.47 17.115 25.47 16.5C25.47 17.115 24.96 17.625 24.345 17.625ZM24.345 11.625H16.095C15.48 11.625 14.97 11.115 14.97 10.5C14.97 9.885 15.48 9.375 16.095 9.375H24.345C24.96 9.375 25.47 9.885 25.47 10.5C25.47 11.115 24.96 11.625 24.345 11.625Z" fill="currentColor"></path>
          </svg>
          How to play
        </div>

        <div class="TimeLeft__C-name">{{ activeMode.labelTop }} {{ activeMode.labelBottom }}</div>

        <!-- last 5 results -->
        <div class="TimeLeft__C-num">
          <div v-for="(cls, idx) in last5ClassList" :key="idx" :class="cls"></div>
          <div v-if="last5ClassList.length < 5" v-for="k in 5 - last5ClassList.length" :key="'e' + k" class="n0"></div>
        </div>

        <div class="TimeLeft__C-id">{{ currentState.currentPeriodId }}</div>
        <div class="TimeLeft__C-text">Time remaining</div>

        <div class="TimeLeft__C-time">
          <div>{{ timeDigits.mm[0] }}</div>
          <div>{{ timeDigits.mm[1] }}</div>
          <div>:</div>
          <div>{{ timeDigits.ss[0] }}</div>
          <div>{{ timeDigits.ss[1] }}</div>
        </div>
      </div>

      <!-- Betting -->
      <div class="Betting__C" :voicetype="1" :typeid="activeMode.typeId" :class="{ disableVoice: isLast5Sec }">
        <div class="Betting__C-mark" v-show="isLast5Sec">
          <div>{{ timeDigits.ss[0] }}</div>
          <div>{{ timeDigits.ss[1] }}</div>
        </div>

        <div class="Betting__C-head">
          <div class="Betting__C-head-g" @click="onClickColor('green')" style="cursor: pointer;">Green</div>
          <div class="Betting__C-head-p" @click="onClickColor('violet')" style="cursor: pointer;">Violet</div>
          <div class="Betting__C-head-r" @click="onClickColor('red')" style="cursor: pointer;">Red</div>
        </div>

        <!-- Numbers 0..9 -->
<div class="Betting__C-numC">
  <div
    v-for="n in 10"
    :key="n - 1"
    :class="['Betting__C-numC-item' + (n - 1), { __rollHighlight: highlightIndex === n - 1 }]"
    @click="onClickNumber(n - 1)"
    style="cursor: pointer;"
  ></div>
</div>

        <!-- MAIN X STRIP -->
        <div class="Betting__C-multiple">
          <div class="Betting__C-multiple-l" @click="randomPickNumberWithAnimation" style="cursor: pointer;">{{ isRandomRolling ? "Random..." : "Random" }}</div>
          <div v-for="q in quantityOptions" :key="'q-' + q" class="Betting__C-multiple-r" :class="{ active: selectedQuickQuantity === q }" @click="setQuickQuantity(q)" style="cursor: pointer;">X{{ q }}</div>
        </div>

        <div class="Betting__C-foot">
          <div class="Betting__C-foot-b" @click="onClickBS('big')" style="cursor: pointer;">Big</div>
          <div class="Betting__C-foot-s" @click="onClickBS('small')" style="cursor: pointer;">Small</div>
        </div>
      </div>

      <!-- Record Tabs -->
      <div class="RecordNav__C">
        <div :class="{ active: recordTab === 'history' }" @click="recordTab = 'history'">Game history</div>
        <div :class="{ active: recordTab === 'chart' }" @click="recordTab = 'chart'">Chart</div>
        <div :class="{ active: recordTab === 'my' }" @click="recordTab = 'my'">My history</div>
      </div>

      <!-- Game History -->
      <div v-show="recordTab === 'history'" class="GameRecord__C game-record">
        <div class="GameRecord__C-head">
          <div class="van-row">
            <div class="van-col van-col--9">Period</div>
            <div class="van-col van-col--5">Number</div>
            <div class="van-col van-col--5">Big Small</div>
            <div class="van-col van-col--5">Color</div>
          </div>
        </div>

        <div class="GameRecord__C-body">
          <div v-for="row in historyRows" :key="row.periodId" class="van-row">
            <div class="van-col van-col--9">{{ row.periodId }}</div>

            <div class="van-col van-col--5 numcenter">
              <div class="GameRecord__C-body-num" :class="numberCssClassForRecord(row.number)">{{ row.number }}</div>
            </div>

            <div class="van-col van-col--5"><span>{{ row.bigSmall }}</span></div>

            <div class="van-col van-col--5">
              <div class="GameRecord__C-origin">
                <div v-for="c in row.colors" :key="row.periodId + '-' + c" class="GameRecord__C-origin-I" :class="c"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="GameRecord__C-foot">
          <div class="GameRecord__C-foot-previous" :class="{ disabled: historyPage === 1 }" @click="historyPage === 1 ? null : prevHistoryPage()">
            <i class="van-badge__wrapper van-icon van-icon-arrow-left GameRecord__C-icon" style="font-size: 20px;"></i>
          </div>

          <div class="GameRecord__C-foot-page">{{ historyPage }}/{{ historyTotalPages }}</div>

          <div class="GameRecord__C-foot-next" :class="{ disabled: historyPage === historyTotalPages }" @click="historyPage === historyTotalPages ? null : nextHistoryPage()">
            <i class="van-badge__wrapper van-icon van-icon-arrow GameRecord__C-icon" style="font-size: 20px;"></i>
          </div>
        </div>
      </div>

      <!-- Chart -->
      <div v-show="recordTab === 'chart'" class="Trend__C game-record">
        <div class="Trend__C-head">
          <div class="van-row">
            <div class="van-col van-col--8">Period</div>
            <div class="van-col van-col--16">Number</div>
          </div>
        </div>

        <div class="Trend__C-body1">
          <div class="Trend__C-body1-line">Statistic (last 100 Periods)</div>
          <div class="Trend__C-body1-line lottery">
            <div>Winning number</div>
          </div>
        </div>

<div class="Trend__C-body2" ref="trendBodyRef">
  <canvas ref="trendCanvasRef" class="line-canvas"></canvas>

  <div v-for="(row, idx) in currentState.history.slice(0, 10)" :key="'trend-' + row.periodId" :issuenumber="row.periodId" :number="row.number" :colour="row.colors.join(',')" :rowid="idx">
    <div class="van-row">
      <div class="van-col van-col--11">
        <div class="Trend__C-body2-IssueNumber">{{ row.periodId }}</div>
      </div>

      <div class="van-col van-col--13">
        <div class="Trend__C-body2-Num">
          <div v-for="n in 10" :key="row.periodId + '-n-' + (n - 1)" :ref="el => setTrendNumberRef(el, idx, n - 1)" :class="['Trend__C-body2-Num-item', row.number === (n - 1) ? `action${n - 1}` : '']">{{ n - 1 }}</div>
          <div class="Trend__C-body2-Num-BS" :class="{ isB: row.bigSmall === 'Big' }">
  {{ row.bigSmall === "Big" ? "B" : "S" }}
</div>
        </div>
      </div>
    </div>
  </div>
</div>
      </div>

      <!-- My History -->
      <div v-show="recordTab === 'my'" class="MyGameRecord__C game-record">
        <div class="MyGameRecord__C-head">
          <div class="MyGameRecord__C-head-moreB">Detail
            <svg class="svg-icon icon-rightCircle"><use xlink:href="#icon-rightCircle"></use></svg>
          </div>
        </div>

        <div class="MyGameRecord__C-body">
          <div class="MyGameRecordList__C">
            <div v-for="bet in myHistoryRows" :key="bet.id">
              <div class="MyGameRecordList__C-item" @click="toggleBetDetail(bet.id)" style="cursor: pointer;">
                <div class="MyGameRecordList__C-item-l" :class="selectionLeftClass(bet)">
                  {{ selectionLeftText(bet) }}
                </div>

                <div class="MyGameRecordList__C-item-m">
                  <div class="MyGameRecordList__C-item-m-top">{{ bet.periodId }}</div>
                  <div class="MyGameRecordList__C-item-m-bottom">{{ formatDateTime(bet.createdAt) }}</div>
                </div>

<div
  v-if="bet.status === 'win' || bet.status === 'lose'"
  class="MyGameRecordList__C-item-r"
  :class="{ success: bet.status === 'win' }"
>
  <div :class="{ success: bet.status === 'win' }">
    {{ betStatusText(bet) }}
  </div>
  <span>{{ formatSignedBDT(betChangeAmount(bet)) }}</span>
</div>
              </div>

              <div class="MyGameRecordList__C-detail" v-show="expandedBetId === bet.id">
                <div class="MyGameRecordList__C-detail-text">Details</div>

                <div class="MyGameRecordList__C-detail-line">
                  <span>Period</span>
                  <div>{{ bet.periodId }}</div>
                </div>

                <div class="MyGameRecordList__C-detail-line">
                  <span>Purchase amount</span>
                  <div>{{ formatBDT(bet.amount) }}</div>
                </div>

                <div class="MyGameRecordList__C-detail-line">
                  <span>Quantity</span>
                  <div>{{ bet.quantity }}</div>
                </div>

                <div class="MyGameRecordList__C-detail-line">
                  <span>Amount after tax</span>
                  <div class="red">{{ formatBDT(bet.amount - bet.fee) }}</div>
                </div>

                <div class="MyGameRecordList__C-detail-line">
                  <span>Tax</span>
                  <div>{{ formatBDT(bet.fee) }}</div>
                </div>

                <div class="MyGameRecordList__C-detail-line">
                  <span>Result</span>
                  <div>
                    <template v-if="bet.resultNumber !== null && bet.resultNumber !== undefined">
                      <div class="MyGameRecordList__C-inlineB">{{ bet.resultNumber }}</div>
                      <div v-for="c in numberToColors(bet.resultNumber)" :key="String(bet.id) + '-c-' + c" class="MyGameRecordList__C-inlineB" :class="resultColorClass(c)">{{ resultColorLabel(c) }}</div>
                      <div class="MyGameRecordList__C-inlineB" :class="numberToBigSmallClass(bet.resultNumber)">{{ numberToBigSmall(bet.resultNumber) }}</div>
                    </template>

                    <template v-else>
                      <div class="MyGameRecordList__C-inlineB">-</div>
                    </template>
                  </div>
                </div>

                <div class="MyGameRecordList__C-detail-line">
                  <span>Select</span>
                  <div>{{ bet.selection?.label ?? "-" }}</div>
                </div>

                <div class="MyGameRecordList__C-detail-line">
                  <span>Status</span>
                  <div :class="{ red: bet.status === 'lose' }">{{ betStatusText(bet) }}</div>
                </div>

                <div class="MyGameRecordList__C-detail-line">
                  <span>Win/lose</span>
                  <div :class="{ red: bet.status === 'lose' }">{{ formatSignedBDT(betChangeAmount(bet)) }}</div>
                </div>

                <div class="MyGameRecordList__C-detail-line">
                  <span>Order time</span>
                  <div>{{ formatDateTime(bet.createdAt) }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="MyGameRecord__C-foot">
          <div class="MyGameRecord__C-foot-previous" :class="{ disabled: myHistoryPage === 1 }" @click="myHistoryPage === 1 ? null : prevMyHistoryPage()">
            <i class="van-badge__wrapper van-icon van-icon-arrow-left MyGameRecord__C-icon" style="font-size: 20px;"></i>
          </div>

          <div class="MyGameRecord__C-foot-page">{{ myHistoryPage }}/{{ myHistoryTotalPages }}</div>

          <div class="MyGameRecord__C-foot-next" :class="{ disabled: myHistoryPage === myHistoryTotalPages }" @click="myHistoryPage === myHistoryTotalPages ? null : nextMyHistoryPage()">
            <i class="van-badge__wrapper van-icon van-icon-arrow MyGameRecord__C-icon" style="font-size: 20px;"></i>
          </div>
        </div>
      </div>

      <!-- How to play Popup -->
      <div class="van-overlay" style="z-index: 4000;" v-show="showHowToPlay" @click="showHowToPlay = false"></div>
      <div v-show="showHowToPlay" role="dialog" tabindex="0" class="van-popup van-popup--round van-popup--center" style="z-index: 4001;">
        <div class="TimeLeft__C-PreSale">
          <div class="TimeLeft__C-PreSale-head">How to play</div>
          <div class="TimeLeft__C-PreSale-body">
            <div>
              <p>1 issue = {{ activeMode.durationSec }} seconds. Countdown ends → auto draw (server).</p>
              <p>Service fee: {{ (feeRate * 100).toFixed(0) }}%</p>
              <p>Green: 1,3,7,9 → x2 ; 5 → x1.5</p>
              <p>Red: 2,4,6,8 → x2 ; 0 → x1.5</p>
              <p>Violet: 0 or 5 → x4.5</p>
              <p>Number exact → x9</p>
              <p>Big(5-9) / Small(0-4) → x2</p>
            </div>
          </div>
          <div class="TimeLeft__C-PreSale-foot">
            <div class="TimeLeft__C-PreSale-foot-btn" @click="showHowToPlay = false">Close</div>
          </div>
        </div>
      </div>

      <!-- Betting Popup -->
      <div class="van-overlay" style="z-index: 4000;" v-show="showBetPopup" @click="showBetPopup = false"></div>

<div v-show="showBetPopup" role="dialog" tabindex="0" class="van-popup van-popup--round van-popup--bottom" style="z-index: 4001;">
  <div class="Betting__Popup-12">
    <div class="Betting__Popup-head" :class="popupThemeClass">
      <div class="Betting__Popup-head-title">{{ activeMode.labelTop }} {{ activeMode.labelBottom }}</div>

      <div class="Betting__Popup-head-selectName">
        <span>Select</span>
        <span class="popup-select-tag">
          {{ betForm.selection?.label ?? "-" }}
        </span>
      </div>
    </div>

    <div class="Betting__Popup-body">
      <div class="Betting__Popup-body-line">
        Balance
        <div class="Betting__Popup-body-line-list">
          <div
            v-for="u in quickUnits"
            :key="u"
            class="Betting__Popup-body-line-item"
            :class="betForm.unit === u ? popupThemeClass : ''"
            @click="betForm.unit = u"
          >
            {{ u }}
          </div>
        </div>
      </div>

      <div class="Betting__Popup-body-line">
        Quantity
        <div class="Betting__Popup-body-line-btnL">
          <div class="Betting__Popup-btn" :class="popupThemeClass" @click="betForm.quantity = Math.max(1, betForm.quantity - 1)">-</div>

          <div class="van-cell van-field Betting__Popup-input">
            <div class="van-cell__value van-field__value">
              <div class="van-field__body">
                <input
                  type="tel"
                  inputmode="numeric"
                  class="van-field__control"
                  v-model.number="betForm.quantity"
                  @blur="betForm.quantity = Math.max(1, Number(betForm.quantity || 1))"
                />
              </div>
            </div>
          </div>

          <div class="Betting__Popup-btn" :class="popupThemeClass" @click="betForm.quantity = betForm.quantity + 1">+</div>
        </div>
      </div>

      <div class="Betting__Popup-body-line">
        <div></div>
        <div class="Betting__Popup-body-line-list">
          <div
            v-for="q in quantityOptions"
            :key="'popup-q-' + q"
            class="Betting__Popup-body-line-item"
            :class="betForm.quantity === q ? popupThemeClass : ''"
            @click="betForm.quantity = q"
          >
            X{{ q }}
          </div>
        </div>
      </div>

      <div class="Betting__Popup-body-line">
        <span class="Betting__Popup-agree" :class="{ active: betAgree }" @click="betAgree = !betAgree">I agree</span>
        <span class="Betting__Popup-preSaleShow" @click="showHowToPlay = true">《Pre-sale rules》</span>
      </div>
    </div>

    <div class="Betting__Popup-foot">
      <div class="Betting__Popup-foot-c" @click="showBetPopup = false">Cancel</div>
      <div class="Betting__Popup-foot-s" :class="popupThemeClass" @click="placeBet">
        Total amount {{ formatBDT(betTotalAmount) }}
      </div>
    </div>
  </div>
</div>
      <!-- Result Popup Overlay -->
      <div class="van-overlay" style="z-index: 5000;" v-show="showResultPopup" @click="showResultPopup = false"></div>

      <!-- Result Popup -->
      <div class="WinningTip__C" style="z-index: 5001;" v-show="showResultPopup">
        <div class="WinningTip__C-body" v-if="resultPopup.status === 'win'">
          <div class="WinningTip__C-body-l1">Congratulations</div>
          <div class="WinningTip__C-body-l2">
            Lottery results
            <div v-for="c in resultPopup.colors" :key="'w-' + c">{{ resultColorLabel(c) }}</div>
            <div class="WinningNum">{{ resultPopup.number }}</div>
            <div>{{ resultPopup.bigSmall }}</div>
          </div>

          <div class="WinningTip__C-body-l3">
            <div class="head">Bonus</div>
            <div class="bonus">{{ formatBDT(resultPopup.payout) }}</div>
            <div class="gameDetail">Period: {{ resultPopup.periodId }}</div>
          </div>
<div class="WinningTip__C-body-l4" @click="toggleResultAutoClose" style="cursor: pointer;">
  <div class="acitveBtn" :class="{ active: resultAutoClose }"></div>
  3 seconds auto close
</div>

<div class="closeBtn" @click="closeResultPopup"></div>
        </div>

        <div class="WinningTip__C-body isL" v-else>
          <div class="WinningTip__C-body-l1 isL">Sorry</div>

          <div class="WinningTip__C-body-l2">
            Lottery results
            <div v-for="c in resultPopup.colors" :key="'l-' + c">{{ resultColorLabel(c) }}</div>
            <div class="WinningNum">{{ resultPopup.number }}</div>
            <div>{{ resultPopup.bigSmall }}</div>
          </div>

          <div class="WinningTip__C-body-l3">
            <div class="isLose">Lose</div>
            <div class="gameDetail">Period: {{ resultPopup.periodId }}</div>
          </div>

<div class="WinningTip__C-body-l4" @click="toggleResultAutoClose" style="cursor: pointer;">
  <div class="acitveBtn" :class="{ active: resultAutoClose }"></div>
  3 seconds auto close
</div>

<div class="closeBtn" @click="closeResultPopup"></div>
        </div>
      </div>
      <!-- audio -->
      <audio id="voice1"><source src="/assets/mp3/di1-0f3d86cb.mp3" type="audio/mpeg" /></audio>
      <audio id="voice2"><source src="/assets/mp3/di2-ad9aa8fb.mp3" type="audio/mpeg" /></audio>
    </div>
    <div v-if="toast.show" class="app-toast" :class="toast.type">
  {{ toast.message }}
</div>




<!-- draggable messenger bubble -->
<!--<div-->
<!--  v-if="launcherVisible"-->
<!--  class="haBubble"-->
<!--  :style="{-->
<!--    left: `${launcherBtn.x}px`,-->
<!--    top: `${launcherBtn.y}px`,-->
<!--    width: `${launcherBtn.size}px`,-->
<!--    height: `${launcherBtn.size}px`,-->
<!--  }"-->
<!--  @pointerdown.stop="startLauncherDrag"-->
<!--  @click="onLauncherClick"-->
<!-->-->
<!--  <div class="haBubble__ring">-->
<!--    <img src="/assets/logo/ha_logo.jpg" alt="HA Bubble" />-->
<!--  </div>-->
<!--</div>-->

<!-- floating mobile popup -->
<div
  v-show="showPeriodLookup"
  class="haFloatPanel"
  :class="{ maximized: periodPanelMaximized }"
  :style="periodPanelStyle"
  @click.stop
>
  <div class="haFloatPanel__header" @pointerdown.stop="startPanelDrag">
    <div class="haFloatPanel__grab"></div>

    <div class="haFloatPanel__actions">
      <button class="haFloatPanel__action" @click.stop="minimizePeriodPanel">−</button>
      <button class="haFloatPanel__action" @click.stop="togglePeriodPanelMaximize">
        <svg v-if="!periodPanelMaximized" viewBox="0 0 24 24" fill="none">
          <rect x="6" y="6" width="12" height="12" rx="2" stroke="currentColor" stroke-width="2"/>
        </svg>
        <svg v-else viewBox="0 0 24 24" fill="none">
          <rect x="5" y="8" width="10" height="10" rx="2" stroke="currentColor" stroke-width="2"/>
          <path d="M10 8V6C10 4.895 10.895 4 12 4H16C17.105 4 18 4.895 18 6V10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
      <button class="haFloatPanel__action closex" @click.stop="closePeriodPanel">×</button>
    </div>
  </div>

<div class="haFloatPanel__body">
  <div class="periodLookup__C-logo">
    <img src="/assets/logo/ha_logo.jpg" alt="Logo" />
  </div>

  <div class="periodLookup__C-title" v-html="periodLookupTitle"></div>

  <template v-if="!periodLookupUnlocked">
    <div class="periodLookup__C-row fancy">
      <div class="periodLookup__C-row-label">PASSWORD :-</div>
      <div class="periodLookup__C-row-value">
        <input
          v-model.trim="periodLookupPassword"
          type="password"
          placeholder="Enter password"
          @keyup.enter="verifyPeriodLookupPassword"
        />
      </div>
    </div>

    <div class="periodLookup__C-row fancy result-row">
      <div class="periodLookup__C-row-label">STATUS :-</div>

      <div
        class="periodLookup__C-row-value result-value is-error"
        v-if="periodLookupError"
      >
        {{ periodLookupError }}
      </div>

      <div
        class="periodLookup__C-row-value result-value is-loading"
        v-else-if="verifyLookupLoading"
      >
        Verifying...
      </div>

      <div class="periodLookup__C-row-value result-value dim" v-else>
        ....
      </div>
    </div>

    <button
      class="periodLookup__C-btn"
      @click="verifyPeriodLookupPassword"
      :disabled="verifyLookupLoading"
    >
      VERIFY
    </button>
  </template>

  <template v-else>
    <div class="periodLookup__C-row fancy">
      <div class="periodLookup__C-row-label">PERIOD NO :-</div>
      <div class="periodLookup__C-row-value">
        <input
          v-model.trim="periodLookupInput"
          type="text"
          inputmode="numeric"
          maxlength="3"
          placeholder="335"
          @input="periodLookupInput = String(periodLookupInput).replace(/\D/g, '').slice(-3)"
          @keyup.enter="searchPeriodResult"
        />
      </div>
    </div>

    <div class="periodLookup__C-row fancy result-row">
      <div class="periodLookup__C-row-label">SEARCH RESULT :-</div>

      <div class="periodLookup__C-row-value result-value" v-if="periodLookupResult">
        <div class="periodLookup__C-result-badge">
          {{ periodLookupResult.number }} ( {{ periodLookupResult.bigSmall }} )
        </div>
      </div>

      <div class="periodLookup__C-row-value result-value is-error" v-else-if="periodLookupError">
        {{ periodLookupError }}
      </div>

      <div class="periodLookup__C-row-value result-value is-loading" v-else-if="periodLookupLoading">
        Searching...
      </div>

      <div class="periodLookup__C-row-value result-value dim" v-else>
        - - -
      </div>

      <div
        v-if="periodLookupResult"
        class="periodLookup__C-result-dot"
        :class="periodLookupResult.colors?.includes('green')
          ? 'green'
          : periodLookupResult.colors?.includes('violet')
          ? 'violet'
          : 'red'"
      ></div>
    </div>

    <button
      class="periodLookup__C-btn"
      @click="searchPeriodResult"
      :disabled="periodLookupLoading"
    >
      RESULT
    </button>
  </template>

<div class="periodLookup__C-note"><span class="periodLookup__C-note-check">
  ✅</span><span> সম্পূর্ণ কার্যকারিতা ও সঠিক ফলাফল পেতে<br> অবশ্যই আপনার ডিভাইস আমাদের সিস্টেমের সাথে সিঙ্ক্রোনাইজড থাকতে হবে<br> 
  অন্যথায়, ফলাফল দেখাতে সমস্যা হতে পারে </span></div>

</div>
  
  <button
    v-if="!periodPanelMaximized"
    class="haFloatPanel__resize"
    @pointerdown.stop="startPanelResize"
    aria-label="Resize panel"
    type="button"
  ></button>
</div>



  </BlankLayout>
</template>

<style scoped>


.dialog__container-img{margin-top:0px !important;}
.dialog__container-footer{margin:0px !important;}
.userInfo__container-content-uid {
    height: 40px;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 20px;
    font-size: 20px;
}



.haBubble {position: fixed;z-index: 6000;border-radius: 50%;padding: 4px;background: radial-gradient(circle at 30% 30%, #3a3a3a 0%, #0d0d0d 65%, #000 100%);box-shadow:0 10px 24px rgba(0, 0, 0, 0.36),inset 0 1px 0 rgba(255, 255, 255, 0.12);cursor: grab;touch-action: none;user-select: none;}
.haBubble:active {cursor: grabbing;}
.haBubble__ring {width: 100%;height: 100%;border-radius: 50%;padding: 4px;background: linear-gradient(145deg, #595959 0%, #141414 55%, #000 100%);box-shadow:inset 0 0 0 1px rgba(255,255,255,0.08),inset 0 -3px 8px rgba(0,0,0,0.45);overflow: hidden;}
.haBubble__ring img {width: 100%;height: 100%;border-radius: 50%;object-fit: cover;display: block;}
.haFloatPanel {position: fixed;z-index: 6001;border-radius: 22px;overflow: hidden;background: #000;border: 1px solid rgba(255,255,255,0.08);box-shadow:0 18px 50px rgba(0, 0, 0, 0.42),inset 0 1px 0 rgba(255,255,255,0.05);display: flex;flex-direction: column;min-width: 280px;min-height: 320px;max-width: calc(100vw - 16px);max-height: calc(100vh - 16px);}
.haFloatPanel.maximized {border-radius: 18px;}
.haFloatPanel__header {position: relative;height: 46px;flex: 0 0 46px;background: linear-gradient(180deg, rgba(35,35,39,0.96) 0%, rgba(20,20,23,0.94) 100%);backdrop-filter: blur(10px);display: flex;align-items: center;justify-content: center;cursor: grab;user-select: none;}
.haFloatPanel__header:active {cursor: grabbing;}
.haFloatPanel__grab {width: 56px;height: 5px;border-radius: 999px;background: #2d7dff;box-shadow: 0 0 10px rgba(45,125,255,0.45);}
.haFloatPanel__actions {position: absolute;right: 10px;top: 50%;transform: translateY(-50%);display: flex;align-items: center;gap: 8px;}
.haFloatPanel__action {width: 30px;height: 30px;border: 0;outline: 0;border-radius: 999px;background: rgba(255,255,255,0.08);color: #fff;display: flex;align-items: center;justify-content: center;font-size: 20px;cursor: pointer;padding: 0;}
.haFloatPanel__action svg {width: 16px;height: 16px;}
.haFloatPanel__action.closex {background: rgba(255,255,255,0.14);}
.haFloatPanel__body {flex: 1 1 auto;overflow: auto;padding: 16px 12px 14px;scrollbar-width: none;}
.haFloatPanel__body::-webkit-scrollbar {display: none;}
.haFloatPanel__resize {position: absolute;right: 2px;bottom: 2px;width: 24px;height: 24px;border: 0;background: transparent;cursor: nwse-resize;}
.haFloatPanel__resize::before {content: "";position: absolute;right: 6px;bottom: 6px;width: 12px;height: 12px;border-right: 2px solid rgba(255,255,255,0.55);border-bottom: 2px solid rgba(255,255,255,0.55);border-bottom-right-radius: 2px;}
.periodLookup__C-logo {/*width: 128px;height: 128px; */margin: 8px auto 10px;display: flex;align-items: center;justify-content: center;}
.periodLookup__C-logo img {display: block;    height: 100px;width: 100px;border-radius: 9999999px;}
.periodLookup__C-title {color: #fff;font-size: 18px;line-height: 1.22;font-weight: 700;text-align: left;text-transform: uppercase;margin: 0 0 30px;}
.periodLookup__C-row.fancy {display: flex;align-items: center;gap: 12px;margin-bottom: 20px;position: relative;}
.periodLookup__C-row-label {/*min-width: 146px; */color: #fff;font-size: 15px;line-height: 1;white-space: nowrap;}
.periodLookup__C-row-value {flex: 1;min-width: 0;color: #fff;font-size: 24px;font-weight: 500;}
.periodLookup__C-row-value input {width: 100%;border: 0;outline: 0;background: transparent;border-bottom: 3px solid rgba(255,255,255,0.78);color: #fff;font-size: 22px;font-weight: 500;padding: 0 0 8px;border-radius: 0;letter-spacing: 1px;}
.periodLookup__C-row-value input::placeholder {color: rgba(255,255,255,0.72);}
.result-row {margin-bottom: 28px;padding-right: 34px;}
.result-value {font-size: 22px;color: #fff;font-weight: 500;}
.result-value.dim {opacity: 0.5;}
.result-value.is-error {color: #ff9898;font-size: 18px;}
.result-value.is-loading {color: rgba(255,255,255,0.82);font-size: 18px;}
.periodLookup__C-result-dot {position: absolute;right: 6px;top: 50%;transform: translateY(-50%);width: 22px;height: 22px;border-radius: 50%;box-shadow: 0 0 0 2px rgba(255,255,255,0.05);}
.periodLookup__C-result-dot.red {background: #ff4d4f;}
.periodLookup__C-result-dot.green {background: #58d45d;}
.periodLookup__C-result-dot.violet {background: #b05cff;}
.periodLookup__C-btn {width: 159px;height: 53px;margin: 0 auto 24px;border: 0;border-radius: 40px;background: linear-gradient(180deg, #0d63bc 0%, #024a99 100%);color: #fff;font-size: 25px;font-weight: 500;display: flex;align-items: center;justify-content: center;box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18), 0 8px 18px rgba(2, 74, 153, 0.22);}
.periodLookup__C-note {display: flex;align-items: flex-start;gap: 8px;color: #fff;font-size: 12px;line-height: 1.35;border-top: 1px solid rgba(255,255,255,0.14);padding-top: 10px;}
.periodLookup__C-note-check {flex: 0 0 auto;font-size: 17px;line-height: 1;}

.haBubble {
  position: fixed;
  left: 0;
  top: 0;
  z-index: 6000;
  border-radius: 50%;
  padding: 4px;
  background: radial-gradient(circle at 30% 30%, #3a3a3a 0%, #0d0d0d 65%, #000 100%);
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.36), inset 0 1px 0 rgba(255, 255, 255, 0.12);
  cursor: grab;
  touch-action: none;
  user-select: none;
  will-change: transform;
  transform: translate3d(0,0,0);
  backface-visibility: hidden;
}

.haFloatPanel {
  position: fixed;
  left: 0;
  top: 0;
  z-index: 6001;
  border-radius: 22px;
  overflow: hidden;
  background: #000;
  border: 1px solid rgba(255,255,255,0.08);
  box-shadow: 0 18px 50px rgba(0, 0, 0, 0.42), inset 0 1px 0 rgba(255,255,255,0.05);
  display: flex;
  flex-direction: column;
  min-width: 280px;
  min-height: 320px;
  max-width: calc(100vw - 16px);
  max-height: calc(100vh - 16px);
  will-change: transform, width, height;
  transform: translate3d(0,0,0);
  backface-visibility: hidden;
}

.haFloatPanel__header {
  position: relative;
  height: 46px;
  flex: 0 0 46px;
  background: linear-gradient(180deg, rgba(35,35,39,0.96) 0%, rgba(20,20,23,0.94) 100%);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: grab;
  user-select: none;
  touch-action: none;
}

/* .periodLookup__C-result-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 46px;
  padding: 10px 16px;
  border-radius: 16px;
  font-size: 22px;
  font-weight: 800;
  line-height: 1;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.18), 0 8px 20px rgba(0,0,0,0.22);
} */

.periodLookup__C-result-meta {
  margin-top: 8px;
  font-size: 14px;
  line-height: 1.35;
  color: rgba(255,255,255,0.74);
}


.__rollHighlight { animation: __softBounce 0.18s ease-in-out infinite alternate; filter: brightness(1.15); transform: translateY(-1px); }
@keyframes __softBounce { from { transform: translateY(0px) scale(1); opacity: 0.85; } to { transform: translateY(-3px) scale(1.03); opacity: 1; } }
.Trend__C-body2 {position: relative;}
.Trend__C-body2 .line-canvas {position: absolute;inset: 0;width: 100%;height: 100%;z-index: 1;pointer-events: none;}
.Trend__C-body2-Num {position: relative;z-index: 2;}
.MyGameRecordList__C-item-l {display: flex;align-items: center;justify-content: center;}
.MyGameRecordList__C-item-l-number {width: 36px;height: 36px;display: inline-flex;align-items: center;justify-content: center;color: #fff;font-weight: 700;line-height: 1;}
.MyGameRecordList__C-item-l-number.greenColor {background: #18b660;}
.MyGameRecordList__C-item-l-number.defaultColor {background: #ff4d4f;}
.MyGameRecordList__C-item-l-number.mixedColor0 {background: linear-gradient(135deg, #ff4d4f 50%, #b05cff 50%);}
.MyGameRecordList__C-item-l-number.mixedColor5 {background: linear-gradient(135deg, #18b660 50%, #b05cff 50%);}
.Betting__C-numC {display: grid !important;grid-template-columns: repeat(5, minmax(0, 1fr)) !important;gap: 12px;justify-items: center;}
.Betting__C-numC>div {height: -webkit-fill-available;}
.app-toast {position: fixed;left: 50%;top: 50%;transform: translateX(-50%);z-index: 9999;min-width: 220px;max-width: 80%;padding: 12px 16px;border-radius: 12px;color: #fff;text-align: center;font-size: 14px;box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);animation: toastFadeIn 0.2s ease;}
.app-toast.success {background: #000000b5;}
.app-toast.error {background:#000000b5;}
@keyframes toastFadeIn {from {opacity: 0;transform: translateX(-50%) translateY(-8px);}to {opacity: 1;transform: translateX(-50%) translateY(0);}}
.van-overlay {background: rgba(0, 0, 0, 0.25);}
.theme-green {background: linear-gradient(90deg, #3faa70 0%, #47ba7c 100%) !important;color: #fff !important;}
.theme-red {background: linear-gradient(90deg, #fc5050 0%, #ff646c 100%) !important;color: #fff !important;}
.theme-violet {background: #b05cff !important;color: #fff !important;}
.theme-big {background: #f2a24a !important;color: #fff !important;}
.theme-small {background: linear-gradient(90deg, #6ca6f3 0%, #87bcf5 100%) !important;color: #fff !important;}
.theme-zero {background: linear-gradient(to bottom right, #ff4d4f 50%, #b05cff 0) !important;color: #fff !important;}
.theme-five {background: linear-gradient(to bottom right, #18b660 50%, #b05cff 0) !important;color: #fff !important;}

.Betting__Popup-body-line-btnL {display: flex;align-items: center;gap: 8px;}
.Betting__Popup-input {flex: 1;position: relative;z-index: 1;}
.Betting__Popup-btn {display: flex;align-items: center;justify-content: center;position: relative;z-index: 2;pointer-events: auto;cursor: pointer;user-select: none;}

.navbar-fixed{z-index: 9999999999999 !important;}
.navbar__content-right {
    position: absolute;
    right: -5px;
}
</style>
