@extends('admin.layouts.app')

@section('panel')
<div class="container-fluid px-2">
    <div class="row g-2 mb-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header py-2 px-3">
                    <h5 class="card-title mb-0">Round Info</h5>
                </div>
                <div class="card-body py-2 px-3">
                    <div class="browser-list">
                        <div class="browser-item">
                            <div class="browser-info">
                                <span class="browser-name">Period</span>
                            </div>
                            <div class="browser-stats">
                                <span class="browser-percent">{{ $round->period_id }}</span>
                            </div>
                        </div>

                        <div class="browser-item">
                            <div class="browser-info">
                                <span class="browser-name">Type</span>
                            </div>
                            <div class="browser-stats">
                                <span class="browser-percent">{{ $round->game_type }}</span>
                            </div>
                        </div>

                        <div class="browser-item">
                            <div class="browser-info">
                                <span class="browser-name">Result</span>
                            </div>
                            <div class="browser-stats">
                                <span class="browser-percent">
                                    {{ $round->result_number ?? '-' }} |
                                    {{ $round->result_color ?? '-' }} |
                                    {{ $round->result_bigsmall ?? '-' }}
                                </span>
                            </div>
                        </div>

                        <div class="browser-item">
                            <div class="browser-info">
                                <span class="browser-name">Status</span>
                            </div>
                            <div class="browser-stats">
                                <span class="browser-percent">{{ ucfirst($round->status) }}</span>
                            </div>
                        </div>

                        <div class="browser-item">
                            <div class="browser-info">
                                <span class="browser-name">Start</span>
                            </div>
                            <div class="browser-stats">
                                <span class="browser-percent">{{ optional($round->start_time)->format('d M Y h:i:s A') }}</span>
                            </div>
                        </div>

                        <div class="browser-item">
                            <div class="browser-info">
                                <span class="browser-name">End</span>
                            </div>
                            <div class="browser-stats">
                                <span class="browser-percent">{{ optional($round->end_time)->format('d M Y h:i:s A') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header py-2 px-3">
                    <h5 class="card-title mb-0">Real User Impact</h5>
                </div>
                <div class="card-body py-2 px-3">
                    <div class="browser-list">
                        <div class="browser-item">
                            <div class="browser-info">
                                <span class="browser-name">Total Users</span>
                            </div>
                            <div class="browser-stats">
                                <span class="browser-percent">{{ (int) $stats->total_real_users }}</span>
                            </div>
                        </div>

                        <div class="browser-item">
                            <div class="browser-info">
                                <span class="browser-name">Total Bet</span>
                            </div>
                            <div class="browser-stats">
                                <span class="browser-percent">{{ number_format((float) $stats->total_real_bet, 2) }}</span>
                            </div>
                        </div>

                        <div class="browser-item">
                            <div class="browser-info">
                                <span class="browser-name">Win Payout</span>
                            </div>
                            <div class="browser-stats">
                                <span class="browser-percent">{{ number_format((float) $stats->total_real_payout, 2) }}</span>
                            </div>
                        </div>

                        <div class="browser-item">
                            <div class="browser-info">
                                <span class="browser-name">Profit</span>
                            </div>
                            <div class="browser-stats">
                                <span class="browser-percent text-success">
                                    {{ number_format((float) ($stats->total_real_bet - $stats->total_real_payout), 2) }}
                                </span>
                            </div>
                        </div>

                        <div class="browser-item">
                            <div class="browser-info">
                                <span class="browser-name">All Bets</span>
                            </div>
                            <div class="browser-stats">
                                <span class="browser-percent">{{ (int) $stats->total_bets }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header py-2 px-3">
                    <h5 class="card-title mb-0">Demo User Details</h5>
                </div>
                <div class="card-body py-2 px-3">
                    <div class="browser-list">
                        <div class="browser-item">
                            <div class="browser-info">
                                <span class="browser-name">Demo Users</span>
                            </div>
                            <div class="browser-stats">
                                <span class="browser-percent">{{ (int) $stats->total_demo_users }}</span>
                            </div>
                        </div>

                        <div class="browser-item">
                            <div class="browser-info">
                                <span class="browser-name">Demo Bet</span>
                            </div>
                            <div class="browser-stats">
                                <span class="browser-percent">{{ number_format((float) $stats->total_demo_bet, 2) }}</span>
                            </div>
                        </div>

                        <div class="browser-item">
                            <div class="browser-info">
                                <span class="browser-name">Demo Payout</span>
                            </div>
                            <div class="browser-stats">
                                <span class="browser-percent">{{ number_format((float) $stats->total_demo_payout, 2) }}</span>
                            </div>
                        </div>

                        <div class="browser-item">
                            <div class="browser-info">
                                <span class="browser-name">Game Effect</span>
                            </div>
                            <div class="browser-stats">
                                <span class="browser-percent text-warning">No effect</span>
                            </div>
                        </div>

                        <div class="browser-item">
                            <div class="browser-info">
                                <span class="browser-name">All Users</span>
                            </div>
                            <div class="browser-stats">
                                <span class="browser-percent">{{ (int) $stats->total_users }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header py-2 px-3">
                    <h5 class="card-title mb-0">Bet Breakdown</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 compact-table">
                            <thead>
                                <tr>
                                    <th>Bet Type</th>
                                    <th>Value</th>
                                    <th>Real Bet</th>
                                    <th>Demo Bet</th>
                                    <th>Real Payout</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($breakdowns as $item)
                                    <tr>
                                        <td>{{ $item->bet_type }}</td>
                                        <td>{{ $item->bet_value }}</td>
                                        <td>{{ number_format((float) $item->real_amount, 2) }}</td>
                                        <td>{{ number_format((float) $item->demo_amount, 2) }}</td>
                                        <td>{{ number_format((float) $item->real_payout, 2) }}</td>
                                        <td>{{ (int) $item->total_bets }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-3">No data found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header py-2 px-3">
                    <h5 class="card-title mb-0">User Summary</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 compact-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Bets</th>
                                    <th>Total Bet</th>
                                    <th>Payout</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($userSummary as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $item->username }}</div>
                                            <small class="text-muted">{{ $item->name }}</small>
                                        </td>
                                        <td>
                                            @if($item->role == 'demo')
                                                <span class="badge bg-warning">Demo</span>
                                            @else
                                                <span class="badge bg-success">Real</span>
                                            @endif
                                        </td>
                                        <td>{{ (int) $item->total_bets }}</td>
                                        <td>{{ number_format((float) $item->total_amount, 2) }}</td>
                                        <td>{{ number_format((float) $item->total_payout, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-3">No users found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2 px-3">
            <form action="{{ route('admin.wingo.show', $round->id) }}" method="GET">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="username / name / user id / bet value">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label mb-1">Role</label>
                        <select name="role" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="user" @selected(request('role') == 'user')>Real</option>
                            <option value="demo" @selected(request('role') == 'demo')>Demo</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label mb-1">Bet Type</label>
                        <select name="bet_type" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach($betTypes as $type)
                                <option value="{{ $type }}" @selected(request('bet_type') == $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="win" @selected(request('status') == 'win')>Win</option>
                            <option value="lose" @selected(request('status') == 'lose')>Lose</option>
                            <option value="pending" @selected(request('status') == 'pending')>Pending</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-primary btn-sm w-100">Filter</button>
                        <a href="{{ route('admin.wingo.show', $round->id) }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-2 px-3">
            <h5 class="card-title mb-0">Bet List</h5>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 compact-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Bet Type</th>
                            <th>Value</th>
                            <th>Amount</th>
                            <th>Qty</th>
                            <th>Multiplier</th>
                            <th>Payout</th>
                            <th>Status</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bets as $key => $bet)
                            <tr>
                                <td>{{ $bets->firstItem() + $key }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $bet->user->username ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $bet->user->name ?? 'Unknown' }}</small>
                                </td>
                                <td>
                                    @if(($bet->user->role ?? null) == 'demo')
                                        <span class="badge bg-warning">Demo</span>
                                    @else
                                        <span class="badge bg-success">Real</span>
                                    @endif
                                </td>
                                <td>{{ $bet->bet_type }}</td>
                                <td>{{ $bet->bet_value }}</td>
                                <td>{{ number_format((float) $bet->amount, 2) }}</td>
                                <td>{{ $bet->qty }}</td>
                                <td>{{ number_format((float) $bet->multiplier, 2) }}</td>
                                <td>{{ number_format((float) $bet->payout, 2) }}</td>
                                <td>
                                    @if($bet->status == 'win')
                                        <span class="badge bg-success">Win</span>
                                    @elseif($bet->status == 'lose')
                                        <span class="badge bg-danger">Lose</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($bet->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $bet->created_at?->format('d M, h:i:s A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4">No bet found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($bets->hasPages())
        <div class="mt-3">
            {{ $bets->links() }}
        </div>
    @endif
</div>
@endsection

@push('style')
<style>
    .browser-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .browser-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 10px;
        border: 1px solid #eef1f4;
        border-radius: 8px;
        background: #fff;
        gap: 10px;
    }
    .browser-info {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 90px;
    }
    .browser-name {
        font-size: 13px;
        font-weight: 500;
    }
    .browser-percent {
        font-size: 13px;
        font-weight: 600;
        color: #012970;
        text-align: right;
        word-break: break-word;
    }
    .compact-table th,
    .compact-table td {
        padding: 9px 10px;
        vertical-align: middle;
        white-space: nowrap;
    }
    .compact-table td small {
        font-size: 11px;
    }
</style>
@endpush