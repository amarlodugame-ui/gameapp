@extends('admin.layouts.app')

@section('panel')
<div class="container-fluid p-0">

    {{-- Referral Settlement Runner --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <h5 class="mb-1">Referral Commission Settlement</h5>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" id="runSettlementBtn">
                            <span class="btn-text">
                                <i class="bi bi-play-circle me-1"></i> Run Settlement
                            </span>
                            <span class="btn-loading d-none">
                                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                Processing...
                            </span>
                        </button>
                        <small id="settlementCooldownText" class="text-muted d-block mt-2"></small>
                    </div>
                </div>

                <div class="card-body p-0">
                    {{-- Response box --}}
                    <div id="settlementResponseBox" class="d-none">
                        <div class="border rounded-3 p-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Settlement Response</h6>
                                <span id="settlementStatusBadge" class="badge bg-secondary">N/A</span>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <div class="border rounded-3 p-3 bg-white h-100">
                                        <small class="text-muted d-block">Message</small>
                                        <div class="fw-semibold" id="resMessage">-</div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="border rounded-3 p-3 bg-white h-100">
                                        <small class="text-muted d-block">Cutoff</small>
                                        <div class="fw-semibold" id="resCutoff">-</div>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="border rounded-3 p-3 bg-white h-100">
                                        <small class="text-muted d-block">Processed Users</small>
                                        <div class="fw-bold fs-5" id="resUsers">0</div>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="border rounded-3 p-3 bg-white h-100">
                                        <small class="text-muted d-block">Processed Logs</small>
                                        <div class="fw-bold fs-5" id="resLogs">0</div>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="border rounded-3 p-3 bg-white h-100">
                                        <small class="text-muted d-block">Total Settled</small>
                                        <div class="fw-bold fs-5 text-success" id="resTotal">0.00000000</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 d-none">
                                <small class="text-muted d-block mb-2">Raw Response</small>
                                <pre id="rawSettlementResponse" class="bg-dark text-white rounded-3 p-3 mb-0" style="font-size: 13px; max-height: 260px; overflow:auto;">{}</pre>
                            </div>
                        </div>
                    </div>

                    {{-- Error box --}}
                    <div id="settlementErrorBox" class="d-none mt-3">
                        <div class="alert alert-danger mb-0">
                            <strong>Error:</strong>
                            <span id="settlementErrorText">Something went wrong.</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>


    {{-- Top cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 mt-2">
            <div class="card widget-stat h-100 p-0">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-3 fw-bold">{{ number_format($stats['users']->total_users) }}</div>
                            <div class="text-muted">Total Users</div>
                            <small class="text-success">Today +{{ number_format($stats['users']->today_new_users) }}</small>
                        </div>
                        <div class="widget-stat-icon primary">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>


@push('script')
<script>
    (function () {
        const btn = document.getElementById('runSettlementBtn');
        const responseBox = document.getElementById('settlementResponseBox');
        const errorBox = document.getElementById('settlementErrorBox');
        const cooldownText = document.getElementById('settlementCooldownText');

        const statusBadge = document.getElementById('settlementStatusBadge');
        const resMessage = document.getElementById('resMessage');
        const resCutoff = document.getElementById('resCutoff');
        const resUsers = document.getElementById('resUsers');
        const resLogs = document.getElementById('resLogs');
        const resTotal = document.getElementById('resTotal');
        const rawResponse = document.getElementById('rawSettlementResponse');
        const errorText = document.getElementById('settlementErrorText');

        const btnText = btn.querySelector('.btn-text');
        const btnLoading = btn.querySelector('.btn-loading');

        const SESSION_KEY_COOLDOWN = 'referral_settlement_cooldown_until';
        const SESSION_KEY_RUNNING = 'referral_settlement_running';
        const COOLDOWN_SECONDS = 300; // 5 minutes

        let countdownInterval = null;

        function setLoading(state) {
            btn.disabled = state;
            btnText.classList.toggle('d-none', state);
            btnLoading.classList.toggle('d-none', !state);
        }

        function showError(message) {
            errorBox.classList.remove('d-none');
            responseBox.classList.add('d-none');
            errorText.textContent = message || 'Something went wrong.';
        }

        function showResponse(data, isErrorStatus = false) {
            errorBox.classList.add('d-none');
            responseBox.classList.remove('d-none');

            const status = data.status || 'unknown';

            statusBadge.textContent = status.toUpperCase();
            statusBadge.className = 'badge ' + (
                status === 'success'
                    ? 'bg-success'
                    : status === 'locked'
                        ? 'bg-warning text-dark'
                        : status === 'cooldown'
                            ? 'bg-info text-dark'
                            : 'bg-danger'
            );

            resMessage.textContent = data.message || '-';
            resCutoff.textContent = data.cutoff || '-';
            resUsers.textContent = data.processed_users ?? 0;
            resLogs.textContent = data.processed_logs ?? 0;
            resTotal.textContent = data.total_settled ?? '0.00000000';
            rawResponse.textContent = JSON.stringify(data, null, 2);

            if (isErrorStatus && !data.message) {
                showError('Server returned an unexpected response.');
            }
        }

        function formatTime(seconds) {
            const min = Math.floor(seconds / 60);
            const sec = seconds % 60;
            return `${min}:${String(sec).padStart(2, '0')}`;
        }

        function getCooldownRemaining() {
            const cooldownUntil = parseInt(sessionStorage.getItem(SESSION_KEY_COOLDOWN) || '0', 10);
            const now = Date.now();

            if (!cooldownUntil || cooldownUntil <= now) {
                sessionStorage.removeItem(SESSION_KEY_COOLDOWN);
                return 0;
            }

            return Math.ceil((cooldownUntil - now) / 1000);
        }

        function startCooldown(seconds = COOLDOWN_SECONDS) {
            const cooldownUntil = Date.now() + (seconds * 1000);
            sessionStorage.setItem(SESSION_KEY_COOLDOWN, cooldownUntil);
            updateCooldownUI();
        }

        function clearRunningState() {
            sessionStorage.removeItem(SESSION_KEY_RUNNING);
        }

        function setRunningState() {
            sessionStorage.setItem(SESSION_KEY_RUNNING, '1');
        }

        function isRunning() {
            return sessionStorage.getItem(SESSION_KEY_RUNNING) === '1';
        }

        function updateCooldownUI() {
            const remaining = getCooldownRemaining();

            if (countdownInterval) {
                clearInterval(countdownInterval);
                countdownInterval = null;
            }

            if (isRunning()) {
                btn.disabled = true;
                cooldownText.textContent = 'Settlement request is currently processing...';
                return;
            }

            if (remaining > 0) {
                btn.disabled = true;
                cooldownText.textContent = `You can run settlement again after ${formatTime(remaining)} minutes.`;

                countdownInterval = setInterval(() => {
                    const left = getCooldownRemaining();

                    if (left <= 0) {
                        clearInterval(countdownInterval);
                        countdownInterval = null;
                        btn.disabled = false;
                        cooldownText.textContent = 'You can run settlement now.';
                        return;
                    }

                    cooldownText.textContent = `You can run settlement again after ${formatTime(left)} minutes.`;
                }, 1000);
            } else {
                btn.disabled = false;
                cooldownText.textContent = 'You can run settlement now.';
            }
        }

        btn.addEventListener('click', function () {
            const remaining = getCooldownRemaining();

            if (isRunning()) {
                showError('Settlement is already processing in this tab.');
                updateCooldownUI();
                return;
            }

            if (remaining > 0) {
                showError(`Please wait ${formatTime(remaining)} before running again.`);
                updateCooldownUI();
                return;
            }

            setRunningState();
            setLoading(true);
            errorBox.classList.add('d-none');
            cooldownText.textContent = 'Settlement request is currently processing...';

            fetch(@json(route('admin.cron.referral-settlement')), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async (response) => {
                let data = {};

                try {
                    data = await response.json();
                } catch (e) {}

                if (!response.ok) {
                    showResponse(data, true);
                    return;
                }

                showResponse(data);
                startCooldown(COOLDOWN_SECONDS);
            })
            .catch(() => {
                showError('Network error or server is unreachable.');
            })
            .finally(() => {
                clearRunningState();
                setLoading(false);
                updateCooldownUI();
            });
        });

        window.addEventListener('beforeunload', function () {
            clearRunningState();
        });

        updateCooldownUI();
    })();
</script>
@endpush












        <div class="col-xl-3 col-md-6 mt-2">
            <div class="card widget-stat h-100 p-0">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-3 fw-bold">{{ number_format((float)$stats['deposits']->success_amount, 2) }}</div>
                            <div class="text-muted">Successful Deposits</div>
                            <small class="text-success">Today {{ number_format((float)$stats['deposits']->today_success_amount, 2) }}</small>
                        </div>
                        <div class="widget-stat-icon success">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mt-2">
            <div class="card widget-stat h-100 p-0">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-3 fw-bold">{{ number_format((float)$stats['withdraws']->pending_amount, 2) }}</div>
                            <div class="text-muted">Pending Withdraw Amount</div>
                            <small class="text-warning">{{ number_format($stats['withdraws']->pending_count) }} requests</small>
                        </div>
                        <div class="widget-stat-icon warning">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mt-2">
            <div class="card widget-stat h-100 p-0">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-3 fw-bold">{{ number_format((float)$stats['bets']->gross_revenue, 2) }}</div>
                            <div class="text-muted">Gross Revenue</div>
                            <small class="text-info">Bet - Payout</small>
                        </div>
                        <div class="widget-stat-icon info">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Today cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 mt-2">
            <div class="card h-100">
                <div class="card-body py-2 px-3">
                    <div class="fw-bold mb-1">Today Bet Amount</div>
                    <div class="fs-4">{{ number_format((float)$stats['bets']->today_bet_amount, 2) }}</div>
                    <small class="text-muted">{{ number_format($stats['bets']->today_bets) }} bets</small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mt-2">
            <div class="card h-100">
                <div class="card-body py-2 px-3">
                    <div class="fw-bold mb-1">Today Completed Withdraw</div>
                    <div class="fs-4">{{ number_format((float)$stats['withdraws']->today_completed_amount, 2) }}</div>
                    <small class="text-muted">Completed payout today</small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mt-2">
            <div class="card h-100">
                <div class="card-body py-2 px-3">
                    <div class="fw-bold mb-1">Today Gift Redeemed</div>
                    <div class="fs-4">{{ number_format((float)$stats['giftRedemptions']->today_redeemed_amount, 2) }}</div>
                    <small class="text-muted">{{ number_format($stats['giftRedemptions']->total_redemptions) }} total redemptions</small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mt-2">
            <div class="card h-100">
                <div class="card-body py-2 px-3">
                    <div class="fw-bold mb-1">Running Rounds</div>
                    <div class="fs-4">{{ number_format($stats['rounds']->running_rounds) }}</div>
                    <small class="text-muted">Today {{ number_format($stats['rounds']->today_rounds) }} rounds</small>
                </div>
            </div>
        </div>
    </div>

{{-- Summary cards --}}
<div class="row g-3 mb-4">

    {{-- User Summary --}}
    <div class="col-12">
        <h5 class="mt-3 mb-0">User & Wallet Summary</h5>
    </div>

    <div class="col-xl-3 col-md-6 mt-2">
        <div class="card widget-stat h-100 p-0">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-3 fw-bold">{{ number_format($stats['users']->real_users) }}</div>
                        <div class="text-muted">Real Users</div>
                    </div>
                    <div class="widget-stat-icon primary">
                        <i class="bi bi-person-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mt-2">
        <div class="card widget-stat h-100 p-0">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-3 fw-bold">{{ number_format($stats['users']->demo_users) }}</div>
                        <div class="text-muted">Demo Users</div>
                    </div>
                    <div class="widget-stat-icon warning">
                        <i class="bi bi-person-badge"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mt-2">
        <div class="card widget-stat h-100 p-0">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-3 fw-bold">{{ number_format($stats['users']->active_users) }}</div>
                        <div class="text-muted">Active Users</div>
                    </div>
                    <div class="widget-stat-icon success">
                        <i class="bi bi-person-fill-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mt-2">
        <div class="card widget-stat h-100 p-0">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-3 fw-bold">{{ number_format($stats['users']->blocked_users) }}</div>
                        <div class="text-muted">Blocked Users</div>
                    </div>
                    <div class="widget-stat-icon danger">
                        <i class="bi bi-person-x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mt-2">
        <div class="card widget-stat h-100 p-0">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-4 fw-bold">{{ number_format((float)$stats['users']->total_balance, 2) }}</div>
                        <div class="text-muted">Main Balance</div>
                    </div>
                    <div class="widget-stat-icon info">
                        <i class="bi bi-wallet"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mt-2">
        <div class="card widget-stat h-100 p-0">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-4 fw-bold">{{ number_format((float)$stats['walletTotal'], 2) }}</div>
                        <div class="text-muted">Total Wallet Liability</div>
                    </div>
                    <div class="widget-stat-icon success">
                        <i class="bi bi-safe2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Deposit Summary --}}
    <div class="col-12 mt-3">
        <h5 class="mt-3 mb-0">Deposit Summary</h5>
    </div>

    <div class="col-xl-3 col-md-6 mt-2">
        <div class="card widget-stat h-100 p-0">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-3 fw-bold">{{ number_format($stats['deposits']->total_deposits) }}</div>
                        <div class="text-muted">Total Deposits</div>
                    </div>
                    <div class="widget-stat-icon primary">
                        <i class="bi bi-bank"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mt-2">
        <div class="card widget-stat h-100 p-0">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-4 fw-bold">{{ number_format((float)$stats['deposits']->pending_amount, 2) }}</div>
                        <div class="text-muted">Pending Deposits</div>
                        <small class="text-warning">{{ number_format($stats['deposits']->pending_count) }} requests</small>
                    </div>
                    <div class="widget-stat-icon warning">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mt-2">
        <div class="card widget-stat h-100 p-0">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-4 fw-bold">{{ number_format((float)$stats['deposits']->awaiting_amount, 2) }}</div>
                        <div class="text-muted">Awaiting Payment</div>
                        <small class="text-info">{{ number_format($stats['deposits']->awaiting_count) }} requests</small>
                    </div>
                    <div class="widget-stat-icon info">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mt-2">
        <div class="card widget-stat h-100 p-0">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-4 fw-bold">{{ number_format((float)$stats['deposits']->rejected_amount, 2) }}</div>
                        <div class="text-muted">Rejected Deposits</div>
                        <small class="text-danger">{{ number_format($stats['deposits']->rejected_count) }} requests</small>
                    </div>
                    <div class="widget-stat-icon danger">
                        <i class="bi bi-x-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Withdraw Summary --}}
    <div class="col-12 mt-3">
        <h5 class="mt-3 mb-0">Withdraw Summary</h5>
    </div>

    <div class="col-xl-3 col-md-6 mt-2">
        <div class="card widget-stat h-100 p-0">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-3 fw-bold">{{ number_format($stats['withdraws']->total_withdraws) }}</div>
                        <div class="text-muted">Total Withdraws</div>
                    </div>
                    <div class="widget-stat-icon primary">
                        <i class="bi bi-cash"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mt-2">
        <div class="card widget-stat h-100 p-0">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-4 fw-bold">{{ number_format((float)$stats['withdraws']->pending_amount, 2) }}</div>
                        <div class="text-muted">Pending Withdraw</div>
                        <small class="text-warning">{{ number_format($stats['withdraws']->pending_count) }} requests</small>
                    </div>
                    <div class="widget-stat-icon warning">
                        <i class="bi bi-hourglass"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mt-2">
        <div class="card widget-stat h-100 p-0">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-4 fw-bold">{{ number_format((float)$stats['withdraws']->processing_amount, 2) }}</div>
                        <div class="text-muted">Processing Withdraw</div>
                        <small class="text-info">{{ number_format($stats['withdraws']->processing_count) }} requests</small>
                    </div>
                    <div class="widget-stat-icon info">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mt-2">
        <div class="card widget-stat h-100 p-0">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-4 fw-bold">{{ number_format((float)$stats['withdraws']->rejected_amount, 2) }}</div>
                        <div class="text-muted">Rejected Withdraw</div>
                        <small class="text-danger">{{ number_format($stats['withdraws']->rejected_count) }} requests</small>
                    </div>
                    <div class="widget-stat-icon danger">
                        <i class="bi bi-slash-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Game & Gift Summary --}}
    <div class="col-12 mt-3">
        <h5 class="mt-3 mb-0">Game & Gift Summary</h5>
    </div>

    <div class="col-xl-3 col-md-6 mt-2">
        <div class="card widget-stat h-100 p-0">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-3 fw-bold">{{ number_format($stats['bets']->total_bets) }}</div>
                        <div class="text-muted">Total Bets</div>
                    </div>
                    <div class="widget-stat-icon primary">
                        <i class="bi bi-dice-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mt-2">
        <div class="card widget-stat h-100 p-0">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-4 fw-bold">{{ number_format((float)$stats['bets']->total_bet_amount, 2) }}</div>
                        <div class="text-muted">Total Bet Amount</div>
                    </div>
                    <div class="widget-stat-icon success">
                        <i class="bi bi-coin"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mt-2">
        <div class="card widget-stat h-100 p-0">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-4 fw-bold">{{ number_format((float)$stats['giftCodes']->total_codes) }}</div>
                        <div class="text-muted">Gift Codes</div>
                        <small class="text-muted">{{ number_format($stats['giftCodes']->available_codes) }} available</small>
                    </div>
                    <div class="widget-stat-icon warning">
                        <i class="bi bi-gift"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mt-2">
        <div class="card widget-stat h-100 p-0">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-4 fw-bold">{{ number_format((float)$stats['giftRedemptions']->total_redeemed_amount, 2) }}</div>
                        <div class="text-muted">Redeemed Amount</div>
                        <small class="text-info">{{ number_format($stats['giftRedemptions']->total_redemptions) }} redemptions</small>
                    </div>
                    <div class="widget-stat-icon info">
                        <i class="bi bi-ticket-perforated"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>


</div>
@endsection

@push('script')
@endpush