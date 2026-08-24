@extends('admin.layouts.app')

@section('panel')
<div class="container-fluid px-2">

    <div class="card mb-3">
        <div class="card-body py-2 px-3">
            <form action="{{ route('admin.wingo.index') }}" method="GET">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label mb-1">Period</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search period id">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label mb-1">Type</label>
                        <select name="game_type" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="30s" @selected(request('game_type') == '30s')>30s</option>
                            <option value="1m" @selected(request('game_type') == '1m')>1m</option>
                            <option value="3m" @selected(request('game_type') == '3m')>3m</option>
                            <option value="5m" @selected(request('game_type') == '5m')>5m</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="running" @selected(request('status') == 'running')>Running</option>
                            <option value="ended" @selected(request('status') == 'ended')>Ended</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label mb-1">From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label mb-1">To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-1 d-flex gap-1">
                        <button class="btn btn-primary btn-sm w-100">Go</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6 col-xl-2">
            <div class="card info-card">
                <div class="card-body py-2 px-3">
                    <small class="text-muted d-block">Rounds</small>
                    <h6 class="mb-0">{{ (int) ($overview->total_rounds ?? 0) }}</h6>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-2">
            <div class="card info-card">
                <div class="card-body py-2 px-3">
                    <small class="text-muted d-block">Real Bet</small>
                    <h6 class="mb-0">{{ number_format((float) ($overview->total_real_bet ?? 0), 2) }}</h6>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-2">
            <div class="card info-card">
                <div class="card-body py-2 px-3">
                    <small class="text-muted d-block">Real Payout</small>
                    <h6 class="mb-0">{{ number_format((float) ($overview->total_real_payout ?? 0), 2) }}</h6>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-2">
            <div class="card info-card">
                <div class="card-body py-2 px-3">
                    <small class="text-muted d-block">Real Profit</small>
                    <h6 class="mb-0 text-success">
                        {{ number_format((float) (($overview->total_real_bet ?? 0) - ($overview->total_real_payout ?? 0)), 2) }}
                    </h6>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-2">
            <div class="card info-card">
                <div class="card-body py-2 px-3">
                    <small class="text-muted d-block">Demo Bet</small>
                    <h6 class="mb-0">{{ number_format((float) ($overview->total_demo_bet ?? 0), 2) }}</h6>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-2">
            <div class="card info-card">
                <div class="card-body py-2 px-3">
                    <small class="text-muted d-block">Demo Users</small>
                    <h6 class="mb-0">{{ (int) ($overview->total_demo_users ?? 0) }}</h6>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-2 px-3">
            <h5 class="card-title mb-0">{{ $pageTitle }}</h5>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 compact-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Period</th>
                            <th>Type</th>
                            <th>Result</th>
                            <th>Total Bet</th>
                            <th>Total Win Payout</th>
                            <th>Total Users</th>
                            <th>Profit</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rounds as $key => $round)
                            <tr>
                                <td>{{ $rounds->firstItem() + $key }}</td>

                                <td>
                                    <div class="fw-semibold">{{ $round->period_id }}</div>
                                    <small class="text-muted">{{ optional($round->start_time)->format('d M Y h:i:s A') }}</small>
                                </td>

                                <td>
                                    <span class="badge bg-primary">{{ $round->game_type }}</span>
                                </td>

                                <td>
                                    <div class="fw-semibold">
                                        {{ $round->result_number ?? '-' }}
                                        |
                                        {{ $round->result_color ?? '-' }}
                                        |
                                        {{ $round->result_bigsmall ?? '-' }}
                                    </div>
                                    <small class="text-muted">{{ ucfirst($round->status) }}</small>
                                </td>

                                <td>
                                    <div class="fw-semibold">{{ number_format((float) $round->total_real_bet, 2) }}</div>
                                    @if((float) $round->total_demo_bet > 0)
                                        <small class="text-warning d-block">Demo: {{ number_format((float) $round->total_demo_bet, 2) }}</small>
                                    @endif
                                </td>

                                <td>
                                    <div class="fw-semibold">{{ number_format((float) $round->total_real_payout, 2) }}</div>
                                    @if((float) $round->total_demo_payout > 0)
                                        <small class="text-warning d-block">Demo: {{ number_format((float) $round->total_demo_payout, 2) }}</small>
                                    @endif
                                </td>

                                <td>
                                    <div class="fw-semibold">{{ (int) $round->total_users }}</div>
                                    <small class="text-muted d-block">
                                        Real: {{ (int) $round->total_real_users }}
                                        @if((int) $round->total_demo_users > 0)
                                            | Demo: {{ (int) $round->total_demo_users }}
                                        @endif
                                    </small>
                                </td>

                                <td>
                                    <div class="fw-semibold {{ (float) $round->real_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format((float) $round->real_profit, 2) }}
                                    </div>
                                    <small class="text-muted d-block">Real only</small>
                                </td>

                                <td>
                                    <a href="{{ route('admin.wingo.show', $round->id) }}" class="btn btn-primary btn-sm">
                                        Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">No round found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($rounds->hasPages())
        <div class="mt-3">
            {{ $rounds->links() }}
        </div>
    @endif
</div>
@endsection

@push('style')
<style>
    .info-card {
        border: 1px solid #eef1f4;
    }
    .compact-table th,
    .compact-table td {
        padding: 10px 12px;
        vertical-align: middle;
        white-space: nowrap;
    }
    .compact-table td small {
        font-size: 11px;
    }
</style>
@endpush