@extends('admin.layouts.app')

@section('panel')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid px-2">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="mb-0">WinGo {{ strtoupper($type) }}</h3>

        <div class="btn-group">
            <a href="{{ route('admin.wingo.color.details', '30s') }}" class="btn btn-sm btn-outline-primary {{ $type == '30s' ? 'active' : '' }}">30s</a>
            <a href="{{ route('admin.wingo.color.details', '1m') }}" class="btn btn-sm btn-outline-primary {{ $type == '1m' ? 'active' : '' }}">1m</a>
            <a href="{{ route('admin.wingo.color.details', '3m') }}" class="btn btn-sm btn-outline-primary {{ $type == '3m' ? 'active' : '' }}">3m</a>
            <a href="{{ route('admin.wingo.color.details', '5m') }}" class="btn btn-sm btn-outline-primary {{ $type == '5m' ? 'active' : '' }}">5m</a>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <p class="mb-1"><b>Count Down :</b> <span id="countDown">--</span></p>
        </div>
        <div class="col-md-6 text-md-end">
            <p class="mb-1"><b>Current Period Id :</b> <span id="periodId">--</span></p>
        </div>
    </div>

    {{-- Next round preset --}}
    <div class="text-center mb-3">
        <h5 class="text-danger fw-bold">Next Round Prediction : <span id="nextPrediction">NOT SET</span></h5>
        <p class="fw-bold mb-0">Next Round Prediction Form</p>
    </div>

<div class="card p-3 mb-4 shadow-sm">
    <input type="number" id="manualInput" class="form-control mb-3" placeholder="Enter a number from 0-9" min="0" max="9">

    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-primary" id="setPredictionBtn">Set Next Round</button>
        <button type="button" class="btn btn-secondary" id="unsetPredictionBtn">Clear Next Round</button>
    </div>
</div>

    {{-- Bulk future rounds system --}}
    <div class="card p-3 mb-4 shadow-sm">
        <h5 class="mb-3">Future Rounds Prediction</h5>

        <label class="form-label fw-bold">Enter future numbers (comma separated)</label>
        <textarea id="futureResultsInput" class="form-control mb-3" rows="3" placeholder="Example: 2,3,4,5,6,8,1,0"></textarea>

        <div class="d-flex gap-2 flex-wrap mb-2">
            <button class="btn btn-success" id="applyFutureResultsBtn">Apply Future Results</button>
            <button class="btn btn-outline-secondary" id="clearFutureInputBtn">Clear Input</button>
            <button class="btn btn-outline-info" id="reloadFutureBtn">Reload Future Queue</button>
        </div>

        <small class="text-muted">
            এখানে 2,3,4,5 দিলে পরের rounds-এ একে একে বসবে।
        </small>
    </div>

    {{-- Upcoming rounds table --}}
    <div class="card mt-3 shadow-sm mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <strong>Upcoming Queued Rounds</strong>
            <div class="d-flex align-items-center gap-2">
                <label for="queueCount" class="mb-0 small">Show</label>
                <input type="number" id="queueCount" class="form-control form-control-sm" style="width: 90px;">
                <button class="btn btn-sm btn-outline-primary" id="reloadQueueBtn">Load</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Period ID</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Preset</th>
                        <th>Quick Set</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="upcomingRoundsTable">
                    <tr>
                        <td colspan="7" class="text-center">No data available</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Top Stats --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm stat-card border-0">
                <div class="card-body">
                    <h6 class="mb-1 text-muted">Total Bet Amount</h6>
                    <h4 class="mb-0 text-primary" id="totalBetAmount">0.00</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm stat-card border-0">
                <div class="card-body">
                    <h6 class="mb-1 text-muted">Demo Users Total Bet</h6>
                    <h4 class="mb-0 text-warning" id="demoTotalBet">0.00</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm stat-card border-0">
                <div class="card-body">
                    <h6 class="mb-1 text-muted">Real Users Total Bet</h6>
                    <h4 class="mb-0 text-success" id="realTotalBet">0.00</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm stat-card border-0">
                <div class="card-body">
                    <h6 class="mb-1 text-muted">Total Payout</h6>
                    <h4 class="mb-0 text-danger" id="totalPayout">0.00</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3 shadow-sm">
        <div class="card-header bg-light">
            <strong>Number Summary</strong>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Result</th>
                        <th>Number</th>
                        <th>Total Bet</th>
                        <th>Demo Bet</th>
                        <th>Real Bet</th>
                        <th>No. of User</th>
                        <th>Amount to Pay</th>
                    </tr>
                </thead>
                <tbody id="summaryTable"></tbody>
            </table>
        </div>
    </div>

    <h4 class="mt-4">Live Bets</h4>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Value</th>
                        <th>Amount</th>
                        <th>Qty</th>
                        <th>Total</th>
                        <th>Mobile</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody id="liveBetsTable"></tbody>
            </table>
        </div>
    </div>

</div>

{{-- Popup Toast --}}
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1080">
    <div id="liveToast" class="toast align-items-center border-0 text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="liveToastBody">Message</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const gameType = "{{ $type }}";
    const token = document.querySelector('meta[name="csrf-token"]').content;

    const routes = {
        liveDataBase: "{{ route('admin.wingo.color.liveData', ['type' => '__TYPE__']) }}",
        setManual: "{{ route('admin.wingo.color.setManual') }}",
        unsetManual: "{{ route('admin.wingo.color.unsetManual') }}",
        setFutureResults: "{{ route('admin.wingo.color.setFutureResults') }}",
        setRoundResult: "{{ route('admin.wingo.color.setRoundResult') }}",
        clearFutureRound: "{{ route('admin.wingo.color.clearFutureRound') }}"
    };

    let endAt = null;
    let serverNow = null;
    let countdownInterval = null;

    function numberToResult(n) {
        const map = {
            0: "Red + Violet",
            1: "Green",
            2: "Red",
            3: "Green",
            4: "Red",
            5: "Green + Violet",
            6: "Red",
            7: "Green",
            8: "Red",
            9: "Green"
        };
        return map[n] || "";
    }

    function formatUnix(ts) {
        if (!ts) return '-';
        const d = new Date(ts * 1000);
        return d.toLocaleString();
    }

    function showPopup(message, type = 'success') {
        const toastEl = document.getElementById('liveToast');
        const toastBody = document.getElementById('liveToastBody');

        toastBody.textContent = message || 'Done';
        toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info');

        if (type === 'success') toastEl.classList.add('bg-success');
        else if (type === 'error') toastEl.classList.add('bg-danger');
        else if (type === 'warning') toastEl.classList.add('bg-warning');
        else toastEl.classList.add('bg-info');

        bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 2500 }).show();
    }

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": token,
                "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify(body)
        });

        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            throw new Error(data.message || "Request failed");
        }

        return data;
    }

async function loadLive() {
    try {
        const activeEl = document.activeElement;
        const isEditingFutureInput =
            activeEl &&
            activeEl.classList &&
            activeEl.classList.contains('future-number-input');

        const queueCount = Math.max(1, parseInt(document.getElementById('queueCount').value || '0', 10));
        const url = routes.liveDataBase.replace('__TYPE__', gameType) + `?queue_count=${queueCount}`;

        const res = await fetch(url, {
            cache: "no-store",
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        });

        if (!res.ok) {
            throw new Error(`HTTP error ${res.status}`);
        }

        const data = await res.json();

        if (!data.success) {
            console.error(data.message || 'Live data failed');
            return;
        }

        document.getElementById("periodId").textContent = data.round?.period_id ?? '--';

        endAt = data.round?.end_time ? (data.round.end_time * 1000) : null;
        serverNow = data.round?.server_time ? (data.round.server_time * 1000) : null;

        if (data.next_prediction !== null && data.next_prediction !== undefined) {
            document.getElementById("nextPrediction").textContent = data.next_prediction;
            document.getElementById("manualInput").value = data.next_prediction;
        } else {
            document.getElementById("nextPrediction").textContent = "NOT SET";
            document.getElementById("manualInput").value = "";
        }

        // typing চললে queued table rerender করবে না
        if (!isEditingFutureInput) {
            renderUpcomingRounds(data.upcoming_rounds || []);
        }

        renderTotals(data.totals || {});
        renderSummary(data.summary || {});
        renderLiveBets(data.live_bets || []);
    } catch (error) {
        console.error("loadLive() failed:", error);
    }
}
    function renderTotals(totals) {
        document.getElementById("totalBetAmount").textContent = parseFloat(totals.total_bet_amount || 0).toFixed(2);
        document.getElementById("demoTotalBet").textContent = parseFloat(totals.demo_total_bet || 0).toFixed(2);
        document.getElementById("realTotalBet").textContent = parseFloat(totals.real_total_bet || 0).toFixed(2);
        document.getElementById("totalPayout").textContent = parseFloat(totals.total_payout || 0).toFixed(2);
    }

    function renderSummary(summary) {
        const tbody = document.getElementById("summaryTable");
        tbody.innerHTML = "";

        for (let i = 0; i <= 9; i++) {
            const row = summary[i] || summary[String(i)] || {};
            const bet = parseFloat(row.total_amount || 0);
            const demoBet = parseFloat(row.demo_total_amount || 0);
            const realBet = parseFloat(row.real_total_amount || 0);
            const payout = parseFloat(row.total_payout || 0);

            tbody.innerHTML += `
                <tr>
                    <td>${numberToResult(i)}</td>
                    <td>${i}</td>
                    <td>${bet.toFixed(2)}</td>
                    <td>${demoBet.toFixed(2)}</td>
                    <td>${realBet.toFixed(2)}</td>
                    <td>${row.total_users || 0}</td>
                    <td>${payout.toFixed(2)}</td>
                </tr>
            `;
        }
    }

    function renderLiveBets(list) {
        const tbody = document.getElementById("liveBetsTable");
        tbody.innerHTML = "";

        if (!list.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center">No data available</td>
                </tr>
            `;
            return;
        }

        list.forEach(b => {
            const amount = parseFloat(b.amount || 0);
            const qty = parseFloat(b.qty || 1);
            const total = amount * qty;

            tbody.innerHTML += `
                <tr>
                    <td>${b.user_id ?? '-'}</td>
                    <td>${b.user?.username ?? '-'}</td>
                    <td>
                        <span class="badge ${b.user?.role === 'demo' ? 'bg-warning text-dark' : 'bg-success'}">
                            ${b.user?.role ?? '-'}
                        </span>
                    </td>
                    <td>${b.bet_value ?? '-'}</td>
                    <td>${amount.toFixed(2)}</td>
                    <td>${qty}</td>
                    <td>${total.toFixed(2)}</td>
                    <td>${b.user?.phone ?? '-'}</td>
                    <td>${parseFloat(b.user?.game_balance || 0).toFixed(2)}</td>
                </tr>
            `;
        });
    }

    function renderUpcomingRounds(rounds) {
        const tbody = document.getElementById("upcomingRoundsTable");
        tbody.innerHTML = "";

        if (!rounds.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center">No queued rounds available</td>
                </tr>
            `;
            return;
        }

        rounds.forEach((r, index) => {
            tbody.innerHTML += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${r.period_id ?? '-'}</td>
                    <td>${formatUnix(r.start_time)}</td>
                    <td>${formatUnix(r.end_time)}</td>
                    <td>
                        <span class="badge ${r.preset_number !== null ? 'bg-success' : 'bg-secondary'}">
                            ${r.preset_number !== null ? r.preset_number : 'NOT SET'}
                        </span>
                    </td>
                    <td style="min-width:140px;">
                        <input
                            type="number"
                            min="0"
                            max="9"
                            class="form-control form-control-sm future-number-input"
                            data-round-id="${r.id}"
                            value="${r.preset_number !== null ? r.preset_number : ''}"
                            placeholder="0-9"
                        >
                    </td>
                    <td style="min-width:160px;">
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary set-row-btn" data-round-id="${r.id}">
                                Set
                            </button>
                            <button class="btn btn-sm btn-outline-danger clear-row-btn" data-round-id="${r.id}">
                                Clear
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        bindRowActions();
    }

    function bindRowActions() {
        document.querySelectorAll('.set-row-btn').forEach(btn => {
            btn.addEventListener('click', async function () {
                const roundId = this.dataset.roundId;
                const input = document.querySelector(`.future-number-input[data-round-id="${roundId}"]`);
                const number = input ? input.value : '';

                if (number === '' || Number(number) < 0 || Number(number) > 9) {
                    showPopup("Please enter a valid number from 0 to 9", "warning");
                    return;
                }

                try {
                    const data = await postJson(routes.setRoundResult, {
                        round_id: roundId,
                        number: Number(number)
                    });

                    showPopup(data.message || "Round updated successfully", "success");
                    loadLive();
                } catch (error) {
                    showPopup(error.message || "Something went wrong", "error");
                }
            });
        });

        document.querySelectorAll('.clear-row-btn').forEach(btn => {
            btn.addEventListener('click', async function () {
                const roundId = this.dataset.roundId;

                try {
                    const data = await postJson(routes.clearFutureRound, {
                        round_id: roundId
                    });

                    showPopup(data.message || "Round cleared successfully", "success");
                    loadLive();
                } catch (error) {
                    showPopup(error.message || "Something went wrong", "error");
                }
            });
        });
    }

    function startCountdown() {
        if (countdownInterval) clearInterval(countdownInterval);

        countdownInterval = setInterval(() => {
            if (!endAt || !serverNow) {
                document.getElementById("countDown").textContent = '--';
                return;
            }

            let diff = Math.max(0, endAt - serverNow);
            let secs = Math.floor(diff / 1000);

            let m = String(Math.floor(secs / 60)).padStart(2, '0');
            let s = String(secs % 60).padStart(2, '0');

            document.getElementById("countDown").textContent = `${m}:${s}`;
            serverNow += 1000;

            if (secs <= 0) {
                loadLive();
            }
        }, 1000);
    }

    document.getElementById("setPredictionBtn").addEventListener("click", async () => {
        const number = document.getElementById("manualInput").value;

        if (number === "" || Number(number) < 0 || Number(number) > 9) {
            showPopup("Please enter a valid number from 0 to 9", "warning");
            return;
        }

        try {
            const data = await postJson(routes.setManual, {
                game_type: gameType,
                number: Number(number)
            });

            showPopup(data.message || "Next round updated", "success");
            loadLive();
        } catch (error) {
            showPopup(error.message || "Something went wrong", "error");
        }
    });

    document.getElementById("unsetPredictionBtn").addEventListener("click", async () => {
        try {
            const data = await postJson(routes.unsetManual, {
                game_type: gameType
            });

            showPopup(data.message || "Next round cleared", "success");
            loadLive();
        } catch (error) {
            showPopup(error.message || "Something went wrong", "error");
        }
    });

    document.getElementById("applyFutureResultsBtn").addEventListener("click", async () => {
        const raw = document.getElementById("futureResultsInput").value.trim();

        if (!raw) {
            showPopup("Please enter comma separated numbers", "warning");
            return;
        }

        const parts = raw.split(',')
            .map(v => v.trim())
            .filter(v => v !== '');

        if (!parts.length) {
            showPopup("Please enter valid numbers", "warning");
            return;
        }

        const results = [];

        for (const p of parts) {
            if (!/^\d+$/.test(p)) {
                showPopup(`Invalid value: ${p}`, "warning");
                return;
            }

            const n = Number(p);

            if (n < 0 || n > 9) {
                showPopup(`Number must be between 0 and 9: ${p}`, "warning");
                return;
            }

            results.push(n);
        }

        try {
            const data = await postJson(routes.setFutureResults, {
                game_type: gameType,
                results: results
            });

            showPopup(data.message || "Future results applied successfully", "success");
            loadLive();
        } catch (error) {
            showPopup(error.message || "Something went wrong", "error");
        }
    });

    document.getElementById("clearFutureInputBtn").addEventListener("click", () => {
        document.getElementById("futureResultsInput").value = '';
    });

    document.getElementById("reloadFutureBtn").addEventListener("click", () => {
        loadLive();
    });

    document.getElementById("reloadQueueBtn").addEventListener("click", () => {
        loadLive();
    });

    loadLive();
    startCountdown();
    setInterval(loadLive, 4000);
</script>
@endsection

@push('style')
<style>
    .table td,
    .table th {
        vertical-align: middle;
        white-space: nowrap;
    }

    .card {
        border-radius: 10px;
    }

    .stat-card {
        min-height: 100px;
    }

    #countDown {
        color: #dc3545;
        font-weight: 700;
    }

    #nextPrediction {
        color: #198754;
    }

    .btn-group .active {
        color: #fff !important;
        background: #0d6efd !important;
        border-color: #0d6efd !important;
    }
</style>
@endpush

@push('script')
@endpush