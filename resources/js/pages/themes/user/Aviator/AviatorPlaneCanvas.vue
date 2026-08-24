<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";

const props = defineProps({
  round: { type: Object, default: null },
  serverTime: { type: String, default: null },
forcedRoundId: {
  type: [String, Number],
  default: null,
},

forceBettingUntilMs: {
  type: Number,
  default: 0,
},
  staticPlaneSrc: {
    type: String,
    default: "/assets/plane/image/p.png",
  },

  introSrc: {
    type: String,
    default: "",
  },

  partnerLogoLeft: {
    type: String,
    default: "",
  },

  partnerLogoRight: {
    type: String,
    default: "",
  },

  brandTitle: {
    type: String,
    default: "SPRIBE",
  },

  brandSubtitle: {
    type: String,
    default: "Official Game",
  },

  brandSince: {
    type: String,
    default: "Since 2019",
  },

  playerCount: {
    type: [String, Number],
    default: 2916,
  },

  bottomAvatars: {
    type: Array,
    default: () => [],
  },

  showMultiplier: {
    type: Boolean,
    default: true,
  },

  showPartnerCard: {
    type: Boolean,
    default: true,
  },

  showBottomBadge: {
    type: Boolean,
    default: true,
  },

  showEndOverlay: {
    type: Boolean,
    default: true,
  },
});

const randomInt = (min, max) =>
  Math.floor(Math.random() * (max - min + 1)) + min;

const fallbackBottomAvatars = ref([]);
const displayPlayerCount = ref(Number(props.playerCount || 2916));

watch(
  () => props.playerCount,
  (count) => {
    displayPlayerCount.value = Number(count ?? 0);
  },
  { immediate: true }
);

let badgeTimer = null;
const buildFallbackBottomAvatars = () => {
  fallbackBottomAvatars.value = Array.from({ length: 3 }, (_, i) => {
    const idx = ((Number(props.round?.id || 1) + i * 7) % 72) + 1;
    return `/assets/plane/avtar/av-${idx}.png`;
  });
};

const resetPlayerCountForRound = () => {
  displayPlayerCount.value = Number(props.playerCount ?? 0);
};

const startBadgeCounter = () => {
  stopBadgeCounter();
};

const stopBadgeCounter = () => {
  if (badgeTimer) {
    clearInterval(badgeTimer);
    badgeTimer = null;
  }
};

watch(
  () => [props.round?.id, props.round?.round_no],
  ([newId, newRoundNo], [oldId, oldRoundNo] = []) => {
    if (!newId && !newRoundNo) return;

    if (newId !== oldId || newRoundNo !== oldRoundNo) {
      buildFallbackBottomAvatars();
      resetPlayerCountForRound();
    }
  },
  { immediate: true }
);


const DEFAULT_GROWTH_TIME_CONSTANT_MS = 5500;
const DEFAULT_VISUAL_MAX_MULTIPLIER = 12;

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

  if (isForcedBetting(round, nowMs)) {
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

const calcBettingProgress = (round, nowMs) => {
  if (!round) return 0;

  const start = toMs(round.betting_starts_at);
  const end = toMs(round.flying_starts_at);

  if (!start || !end || end <= start) return 0;

  return clamp((nowMs - start) / (end - start), 0, 1);
};

const calcFlightProgress = (round, nowMs) => {
  if (!round) return 0;

  const effectivePhase = getEffectiveRoundState(round, nowMs);
  if (effectivePhase === "betting") return 0;

  const multiplier = calcRoundMultiplier(round, nowMs);
  if (multiplier <= 1) return 0;

  const visualMax = Number(round?.visual_max_multiplier);
  const resolvedVisualMax =
    Number.isFinite(visualMax) && visualMax > 1
      ? visualMax
      : DEFAULT_VISUAL_MAX_MULTIPLIER;

  const progress = Math.log(Math.max(1, multiplier)) / Math.log(resolvedVisualMax);
  return clamp(progress, 0, 1);
};

const getEffectiveRoundState = (round, nowMs) => {
  if (!round) return "betting";

  if (isForcedBetting(round, nowMs)) {
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
    Number.isFinite(resultDurationMs) && resultDurationMs >= 0 ? crashAtMs + resultDurationMs : null;

  if (finishedAtMs != null && nowMs >= finishedAtMs) {
    return "completed";
  }

  return "crashed";
};

const stageBoard = ref(null);
const canvasEl = ref(null);
const planeEl = ref(null);
const multiplierEl = ref(null);
const introBarEl = ref(null);

let ctx = null;
let rafId = 0;
let resizeTimer = null;

let serverEpochMs = null;
let perfAtServerEpoch = 0;

const width = ref(350);
const height = ref(242);
const dpr = ref(1);

const livePhase = ref("betting");
const bettingLeftSec = ref(0);

const showBettingLoader = computed(() => {
  return !!props.round && livePhase.value === "betting";
});
let liveMultiplierRaw = 1;
let introProgressRaw = 0;
let flightProgressRaw = 0;
let lastHudPaintAt = 0;

const MULTIPLIER_UI_INTERVAL_MS = 90;

const formatMultiplier = (value) => Number(value || 1).toFixed(2);
const lerp = (a, b, t) => (1 - t) * a + b * t;

const setServerEpoch = () => {
  if (!props.serverTime) return;
  const ms = Date.parse(props.serverTime);
  if (!Number.isFinite(ms)) return;
  serverEpochMs = ms;
  perfAtServerEpoch = performance.now();
};
const isForcedBetting = (round, nowMs) => {
  if (!round?.id) return false;

  return (
    Number(props.forcedRoundId) === Number(round.id) &&
    Number(props.forceBettingUntilMs || 0) > nowMs
  );
};
watch(() => props.serverTime, setServerEpoch, { immediate: true });


watch(
  () => livePhase.value,
  () => {
    startBadgeCounter();
  },
  { immediate: true }
);
const phase = computed(() => livePhase.value);

const shouldShowMultiplier = computed(() => {
  return props.showMultiplier && livePhase.value === "running";
});

const showIntro = computed(() => {
  return !!props.round && livePhase.value === "betting" && props.showPartnerCard;
});

const showResultState = computed(() => {
  return props.showEndOverlay && ["crashed", "completed"].includes(livePhase.value);
});

const layout = computed(() => {
  const w = width.value;
  const h = height.value;

  return {
    xStart: 14,
    yStart: h - 14,
    xEnd: w - 34,
    yEnd: 42,
    controlX: w * 0.60,
    planeWidth: 78,
    planeHeight: 38,
  };
});

const readyPoint = () => {
  return {
    x: 20,
    y: height.value - 18,
  };
};

const getCurvePoints = () => {
  const l = layout.value;
  return {
    p0: { x: l.xStart, y: l.yStart },
    p1: { x: l.controlX, y: height.value - 16 },
    p2: { x: l.xEnd, y: l.yEnd },
  };
};

const getQuadraticPoint = (t, p0, p1, p2) => {
  const mt = 1 - t;
  return {
    x: mt * mt * p0.x + 2 * mt * t * p1.x + t * t * p2.x,
    y: mt * mt * p0.y + 2 * mt * t * p1.y + t * t * p2.y,
  };
};

const sampleQuadratic = (tStart, tEnd, steps = 22) => {
  const { p0, p1, p2 } = getCurvePoints();
  const points = [];

  for (let i = 0; i <= steps; i++) {
    const localT = i / steps;
    const t = lerp(tStart, tEnd, localT);
    points.push(getQuadraticPoint(t, p0, p1, p2));
  }

  return points;
};

const getFrameNowMs = (rafTime) => {
  if (serverEpochMs != null) {
    return serverEpochMs + (rafTime - perfAtServerEpoch);
  }
  return Date.now();
};

const setPhase = (value) => {
  if (livePhase.value !== value) livePhase.value = value;
};

const updateFrameState = (nowMs) => {
  const round = props.round;

if (!round) {
  setPhase("betting");
  liveMultiplierRaw = 1;
  introProgressRaw = 0;
  flightProgressRaw = 0;
  bettingLeftSec.value = 0;
  return readyPoint();
}

  const effectivePhase = getEffectiveRoundState(round, nowMs);
  setPhase(effectivePhase);

if (effectivePhase === "betting") {
  liveMultiplierRaw = 1;
  introProgressRaw = calcBettingProgress(round, nowMs);
  flightProgressRaw = 0;

  const flyingStartsAt = toMs(round?.flying_starts_at);
  bettingLeftSec.value = flyingStartsAt
    ? Math.max(0, Math.ceil((flyingStartsAt - nowMs) / 1000))
    : 0;

  return readyPoint();
}
  liveMultiplierRaw = calcRoundMultiplier(round, nowMs);
  introProgressRaw = 1;

  const baseProgress = calcFlightProgress(round, nowMs);
  const smoothPow = effectivePhase === "running" ? 1.45 : 1.2;
  const smoothProgress = 1 - Math.pow(1 - clamp(baseProgress, 0, 1), smoothPow);
  flightProgressRaw = smoothProgress;

  const { p0, p1, p2 } = getCurvePoints();
  return getQuadraticPoint(smoothProgress, p0, p1, p2);
};

const drawCurveAndFill = (progress) => {
  const p = clamp(progress, 0, 1);
  if (p <= 0) return;

  const points = sampleQuadratic(0, p, 18);
  if (!points.length) return;

  const startPoint = points[0];
  const endPoint = points[points.length - 1];
  const baselineY = height.value;

  ctx.beginPath();
  ctx.moveTo(startPoint.x, startPoint.y);
  for (let i = 1; i < points.length; i++) {
    ctx.lineTo(points[i].x, points[i].y);
  }
  ctx.lineWidth = 5;
  ctx.strokeStyle = "#F00B3E";
  ctx.stroke();
  ctx.closePath();

  ctx.beginPath();
  ctx.moveTo(startPoint.x, startPoint.y);
  for (let i = 1; i < points.length; i++) {
    ctx.lineTo(points[i].x, points[i].y);
  }
  ctx.lineTo(endPoint.x, baselineY);
  ctx.lineTo(startPoint.x, baselineY);
  ctx.closePath();

  ctx.fillStyle = "rgba(104, 1, 14, 0.45)";
  ctx.fill();
};

const paintHud = (nowMs) => {
  if (introBarEl.value) {
    introBarEl.value.style.width = `${Math.max(0, Math.min(100, introProgressRaw * 100))}%`;
  }

  if (!multiplierEl.value) return;
  if (nowMs - lastHudPaintAt < MULTIPLIER_UI_INTERVAL_MS) return;

  multiplierEl.value.textContent = `${formatMultiplier(liveMultiplierRaw)}x`;
  lastHudPaintAt = nowMs;
};

const paintPlane = (point) => {
  if (!planeEl.value || !point) return;

  const l = layout.value;
  const x = point.x - l.planeWidth * 0.22;
  const y = point.y - l.planeHeight * 0.9;

  planeEl.value.style.width = `${l.planeWidth}px`;
  planeEl.value.style.height = `${l.planeHeight}px`;
  planeEl.value.style.transform = `translate3d(${x}px, ${y}px, 0)`;
};

const drawScene = (time, nowMs) => {
  if (!ctx) return;

  const point = updateFrameState(nowMs);
  ctx.clearRect(0, 0, width.value, height.value);

  if (livePhase.value !== "betting") {
    drawCurveAndFill(flightProgressRaw);
  }

  paintPlane(point);
  paintHud(nowMs);
};

const render = (time = 0) => {
  const nowMs = getFrameNowMs(time);
  drawScene(time, nowMs);
  rafId = requestAnimationFrame(render);
};

const setupCanvas = async () => {
  await nextTick();

  if (!stageBoard.value || !canvasEl.value) return;

  const rect = stageBoard.value.getBoundingClientRect();

  width.value = rect.width || 350;
  height.value = rect.height || 242;
  dpr.value = Math.min(window.devicePixelRatio || 1, 2);

  canvasEl.value.width = Math.round(width.value * dpr.value);
  canvasEl.value.height = Math.round(height.value * dpr.value);
  canvasEl.value.style.width = `${width.value}px`;
  canvasEl.value.style.height = `${height.value}px`;

  ctx = canvasEl.value.getContext("2d");
  ctx.setTransform(dpr.value, 0, 0, dpr.value, 0, 0);
  ctx.imageSmoothingEnabled = true;

  paintPlane(readyPoint());
};

const onResize = () => {
  clearTimeout(resizeTimer);
  resizeTimer = setTimeout(async () => {
    await setupCanvas();
  }, 120);
};

onMounted(async () => {
  buildFallbackBottomAvatars();
  resetPlayerCountForRound();
  await setupCanvas();
  render();
  startBadgeCounter();
  window.addEventListener("resize", onResize);
});
onBeforeUnmount(() => {
  stopBadgeCounter();
  cancelAnimationFrame(rafId);
  clearTimeout(resizeTimer);
  window.removeEventListener("resize", onResize);
});
</script>

<template>
  <div class="stage-board aviator-plane-stage" ref="stageBoard">
    <div class="stage-rays" :class="{ spinning: phase === 'running' }"></div>

<div v-if="showIntro" class="intro-overlay">
  <div class="intro-logos">
    <img
      v-if="partnerLogoLeft"
      :src="partnerLogoLeft"
      class="partner-logo partner-logo-left"
      alt="partner-left"
    />
    <img
      v-if="partnerLogoRight"
      :src="partnerLogoRight"
      class="partner-logo partner-logo-right"
      alt="partner-right"
    />
  </div>

  <img v-if="introSrc" :src="introSrc" class="intro-image intro_image" alt="intro" />

  <div class="intro-progress">
    <div ref="introBarEl" class="intro-progress-bar"></div>
  </div>

  <div class="betting-loader-title">Taking Bets</div>
  <div class="betting-loader-subtitle">
    Round starts in {{ bettingLeftSec }}s
  </div>

  <!-- <div class="brand-card">
    <div class="brand-currency">$</div>
    <div class="brand-name">{{ brandTitle }}</div>
    <div class="brand-subtitle">
      {{ brandSubtitle }} <span class="brand-check">✔</span>
    </div>
    <div class="brand-since">{{ brandSince }}</div>
  </div> -->
</div>

    <div v-if="shouldShowMultiplier" ref="multiplierEl" class="plane-multiplier">
      1.00x
    </div>

    <div v-if="showResultState" class="result-overlay">
      <div class="result-title">FLEW AWAY!</div>
      <div class="result-multiplier">{{ formatMultiplier(liveMultiplierRaw) }}x</div>
    </div>

    <canvas ref="canvasEl" id="myCanvas"></canvas>

    <img
      ref="planeEl"
      :src="staticPlaneSrc"
      class="plane-dom"
      alt="plane"
      draggable="false"
    />

<div v-if="showBottomBadge" class="players-badge">
  <div class="players-avatars">
    <template v-if="bottomAvatars.length">
      <div
        v-for="(avatar, index) in bottomAvatars.slice(0, 3)"
        :key="index"
        class="avatar"
        :style="{ left: `${index * 15}px`, zIndex: 5 - index }"
      >
        <img :src="avatar" alt="avatar" />
      </div>
    </template>

    <template v-else>
      <div
        v-for="(avatar, index) in fallbackBottomAvatars"
        :key="'fallback-' + index"
        class="avatar"
        :style="{ left: `${index * 15}px`, zIndex: 5 - index }"
      >
        <img :src="avatar" alt="avatar" />
      </div>
    </template>
  </div>

  <div class="players-count">{{ Number(displayPlayerCount).toLocaleString() }}</div>
</div>
  </div>
</template>


<style scoped>
.aviator-plane-stage {position: relative;overflow: hidden;width: 350px;height: 242px;min-height: unset;border-radius: 10px;box-sizing: border-box;background: #000;border:1px solid #2a2b2e ;}
.stage-glow {position: absolute;inset: 0;z-index: 2;pointer-events: none;border-radius: 22px;background: radial-gradient(circle at 46% 42%,rgba(122, 48, 255, 0.38) 0%,rgba(76, 0, 130, 0.18) 18%,rgba(0, 0, 0, 0) 52%);}
#myCanvas {position: absolute;inset: 0;z-index: 3;display: block;width: 100%;height: 100%;border-radius: 22px;}
.plane-dom {position: absolute;left: 0;top: 0;z-index: 12;pointer-events: none;user-select: none;will-change: transform;transform-origin: center center;backface-visibility: hidden;}
.plane-multiplier {position: absolute;left: 50%;top: 45%;transform: translate(-50%, -50%);z-index: 26;color: #fff;font-size: 68px;font-weight: 900;line-height: 1;letter-spacing: -1px;user-select: none;pointer-events: none;text-shadow:0 0 12px rgba(255, 255, 255, 0.12),0 0 30px rgba(122, 48, 255, 0.20);}
.result-overlay {position: absolute;inset: 0;z-index: 8;pointer-events: none;border-radius: 22px;background: rgba(0, 0, 0, 0.05);}
.intro-overlay {position: absolute;inset: 0;z-index: 25;display: flex;flex-direction: column;align-items: center;justify-content: center;pointer-events: none;border-radius: 22px;}
.intro-logos {display: flex;align-items: center;justify-content: center;gap: 28px;margin-bottom: 10px;}
.partner-logo {display: block;max-height: 42px;max-width: 110px;object-fit: contain;}
.intro-progress {width: 170px;height: 6px;border-radius: 999px;overflow: hidden;background: rgba(255, 255, 255, 0.18);margin-bottom: 12px;}
.intro-progress-bar {width: 0%;height: 100%;border-radius: 999px;background: linear-gradient(90deg, #ff0055 0%, #ff256f 100%);}
.brand-card {min-width: 112px;padding: 10px 12px 8px;border-radius: 12px;text-align: center;border: 1px solid rgba(147, 255, 54, 0.32);background:linear-gradient(180deg, rgba(60, 100, 18, 0.92), rgba(25, 38, 8, 0.96));box-shadow:inset 0 1px 0 rgba(255, 255, 255, 0.10),0 8px 20px rgba(0, 0, 0, 0.28);}
.brand-currency {width: 24px;height: 24px;margin: 0 auto 4px;border-radius: 999px;display: flex;align-items: center;justify-content: center;color: #d9ffb8;font-weight: 800;background: rgba(255, 255, 255, 0.08);}
.brand-name {color: #fff;font-size: 16px;font-weight: 900;line-height: 1.1;}
.brand-subtitle {margin-top: 6px;font-size: 12px;color: #89ff57;display: inline-flex;align-items: center;gap: 4px;padding: 2px 6px;border-radius: 999px;background: rgba(0, 0, 0, 0.18);}
.brand-check {font-size: 11px;}
.brand-since {margin-top: 5px;color: rgba(255, 255, 255, 0.78);font-size: 10px;}
.players-badge {position: absolute;right: 6px;bottom: 6px;z-index: 30;height: 30px;display: flex;align-items: center;border-radius: 999px;padding: 0 12px 0 8px;background: rgba(40, 40, 40, 0.92);border: 1px solid rgba(255, 255, 255, 0.08);box-shadow: 0 4px 12px rgba(0, 0, 0, 0.24);}
.players-avatars {position: relative;width: 54px;height: 24px;}
.avatar {position: absolute;top: 0;width: 20px;height: 20px;border-radius: 999px;overflow: hidden;border: 2px solid #7cff2d;background: #1d1d1d;box-sizing: border-box;}
.avatar img {width: 100%;height: 100%;object-fit: cover;}
.avatar-fallback {display: flex;align-items: center;justify-content: center;color: #fff;font-size: 11px;font-weight: 700;}
.players-count {color: #fff;font-size: 14px;}
@media (max-width: 991px) {.plane-multiplier {font-size: 52px;}}
.result-overlay {position: absolute;inset: 0;z-index: 24;display: flex;flex-direction: column;align-items: center;justify-content: center;border-radius: 22px;background: rgba(0, 0, 0, 0.05);pointer-events: none;}
.result-title {color: #fff;font-size: 22px;font-weight: 500;margin-bottom: 10px;letter-spacing: 0.4px;}
.result-multiplier {color: #f00028;font-size: 68px;font-weight: 900;line-height: 1;text-shadow: 0 0 12px rgba(240, 0, 40, 0.18);}
.stage-board,
.aviator-plane-stage { width: 100%; display: block; position: relative; }
#myCanvas { width: 100% !important; height: 100% !important; display: block; }
.rotateimage { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.stage-rays {
  position: absolute;
  left: -203%;
  bottom: -200%;
  width: 405%;
  height: 405%;
  z-index: 1;
  pointer-events: none;
  border-radius: 50%;
  background: url('/assets/plane/image/bg-rotate-old.svg') center center / contain no-repeat;
  will-change: transform;
  transform: rotate(0deg);
  transform-origin: center center;
  animation: none;
}

.stage-rays.spinning {
  animation: aviatorRaysSpin 15s linear infinite;
}

@keyframes aviatorRaysSpin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.betting-loader-overlay {
  position: absolute;
  inset: 0;
  z-index: 6;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
}

.betting-loader-box {
  min-width: 170px;
  padding: 14px 18px;
  border-radius: 16px;
  background: rgba(8, 12, 24, 0.72);
  backdrop-filter: blur(6px);
  text-align: center;
  color: #fff;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.28);
}

.betting-loader-spinner {
  width: 34px;
  height: 34px;
  margin: 0 auto 10px;
  border-radius: 50%;
  border: 3px solid rgba(255, 255, 255, 0.2);
  border-top-color: #ffffff;
  animation: bettingSpin 0.8s linear infinite;
}

.betting-loader-title {
  font-size: 15px;
  font-weight: 700;
  margin-bottom: 4px;
}

.betting-loader-subtitle {
  font-size: 12px;
  opacity: 0.9;
}

@keyframes bettingSpin {
  to {
    transform: rotate(360deg);
  }
}

.betting-loader-title {
  margin-top: 10px;
  text-align: center;
  color: #fff;
  font-size: 15px;
  font-weight: 700;
  line-height: 1.2;
}

.betting-loader-subtitle {
  margin-top: 4px;
  text-align: center;
  color: rgba(255, 255, 255, 0.88);
  font-size: 12px;
  line-height: 1.2;
}
.plane-multiplier {
  position: absolute;
  left: 50%;
  top: 45%;
  transform: translate(-50%, -50%);
  z-index: 26;

  min-width: 440px;
  min-height: 220px;

  display: flex;
  align-items: center;
  justify-content: center;

  color: #fff;
  font-size: 68px;
  font-weight: 900;
  line-height: 1;
  letter-spacing: -1px;

  user-select: none;
  pointer-events: none;

  background: radial-gradient(circle at center, rgba(0, 140, 210, 0.55) 0%, rgba(0, 0, 0, 0) 58%);

  /* text-shadow:
    0 0 12px rgba(255,255,255,0.18),
    0 0 30px rgba(0,140,210,0.35); */
}
.intro_image{width: 200px;}
</style>