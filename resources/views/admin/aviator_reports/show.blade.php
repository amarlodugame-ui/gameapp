@extends('admin.layouts.app')

@section('panel')
<div class="row mb-3">
    <div class="col-lg-12">
        <div class="card b-radius-10">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h5 class="mb-1">Round #{{ $round->round_no }}</h5>
                    <p class="mb-0">
                        Start: {{ optional($round->betting_starts_at)->format('Y-m-d H:i:s') }} |
                        Crash: {{ number_format((float)$round->crash_multiplier, 2) }}x |
                        Status: {{ ucfirst($round->status) }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('admin.aviator.reports.index', ['include_demo' => request('include_demo', 1)]) }}" class="btn btn-outline-primary">
                        Back
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card b-radius-10">
            <div class="card-body">
                <h6>Total Bet</h6>
                <h4>{{ number_format((float)$summary['total_bet'], 2) }}</h4>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card b-radius-10">
            <div class="card-body">
                <h6>Total Payout</h6>
                <h4>{{ number_format((float)$summary['total_payout'], 2) }}</h4>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card b-radius-10">
            <div class="card-body">
                <h6>Total Users</h6>
                <h4>{{ $summary['total_users'] }}</h4>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card b-radius-10">
            <div class="card-body">
                <h6>Profit</h6>
                <h4 class="{{ $summary['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format((float)$summary['profit'], 2) }}
                </h4>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-xl-2 col-md-4 mb-3">
        <div class="card"><div class="card-body"><strong>Cashed Out:</strong> {{ $summary['cashed_out_count'] }}</div></div>
    </div>
    <div class="col-xl-2 col-md-4 mb-3">
        <div class="card"><div class="card-body"><strong>Lost:</strong> {{ $summary['lost_count'] }}</div></div>
    </div>
    <div class="col-xl-2 col-md-4 mb-3">
        <div class="card"><div class="card-body"><strong>Cancelled:</strong> {{ $summary['cancelled_count'] }}</div></div>
    </div>
    <div class="col-xl-2 col-md-4 mb-3">
        <div class="card"><div class="card-body"><strong>Pending:</strong> {{ $summary['pending_count'] }}</div></div>
    </div>
    <div class="col-xl-2 col-md-4 mb-3">
        <div class="card"><div class="card-body"><strong>Queued:</strong> {{ $summary['queued_count'] }}</div></div>
    </div>
    <div class="col-xl-2 col-md-4 mb-3">
        <div class="card"><div class="card-body"><strong>Auto Cashout:</strong> {{ $summary['auto_cashout_count'] }}</div></div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card"><div class="card-body"><strong>Real Bets:</strong> {{ $summary['real_bets_count'] }}</div></div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card"><div class="card-body"><strong>Demo Bets:</strong> {{ $summary['demo_bets_count'] }}</div></div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card"><div class="card-body"><strong>Slot 1:</strong> {{ $summary['slot_1_count'] }}</div></div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card"><div class="card-body"><strong>Slot 2:</strong> {{ $summary['slot_2_count'] }}</div></div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-lg-12">
        <div class="card b-radius-10">
            <div class="card-body">
                <form method="GET">
                    <div class="row">
                        <div class="col-md-3">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">All</option>
                                @foreach(['queued','pending','cashed_out','lost','cancelled'] as $item)
                                    <option value="{{ $item }}" {{ request('status') === $item ? 'selected' : '' }}>
                                        {{ ucfirst($item) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label>Slot</label>
                            <select name="slot" class="form-control">
                                <option value="">All</option>
                                <option value="1" {{ request('slot') === '1' ? 'selected' : '' }}>1</option>
                                <option value="2" {{ request('slot') === '2' ? 'selected' : '' }}>2</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label>Include Demo</label>
                            <select name="include_demo" class="form-control">
                                <option value="1" {{ request('include_demo', 1) == 1 ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ request('include_demo') === '0' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label>Per Page</label>
                            <select name="per_page" class="form-control">
                                @foreach([10,30,50,100] as $size)
                                    <option value="{{ $size }}" {{ (int)request('per_page',30) === $size ? 'selected' : '' }}>{{ $size }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-primary w-100" type="submit">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card b-radius-10">
            <div class="card-body p-0">
                <div class="table-responsive-md table-responsive">
                    <table class="table table-light style-two">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Bet Key</th>
                                <th>Slot</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Auto Cashout</th>
                                <th>Cashout Multiplier</th>
                                <th>Cashout Amount</th>
                                <th>Placed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bets as $bet)
                                <tr>
                                    <td>{{ $bet->id }}</td>
                                    <td>
                                        {{ optional($bet->user)->name ?? 'N/A' }}<br>
                                        <small>{{ optional($bet->user)->username ?? optional($bet->user)->email }}</small>
                                    </td>
                                    <td>
                                        @if($bet->is_demo)
                                            <span class="badge badge-warning" style="color: red;">Demo</span>
                                        @else
                                            <span class="badge badge-success" style="color: green;">Real</span>
                                        @endif
                                    </td>
                                    <td>{{ $bet->bet_key }}</td>
                                    <td>{{ $bet->slot }}</td>
                                    <td>{{ number_format((float)$bet->amount, 2) }}</td>
                                    <td>
                                        <span class="badge badge-info" style="color: blue;">{{ ucfirst($bet->status) }}</span>
                                    </td>
                                    <td>
                                        {{ $bet->auto_cashout_enabled ? 'Yes' : 'No' }}
                                        @if($bet->auto_cashout_enabled && $bet->auto_cashout_multiplier)
                                            <br><small>{{ number_format((float)$bet->auto_cashout_multiplier, 2) }}x</small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $bet->cashout_multiplier ? number_format((float)$bet->cashout_multiplier, 2) . 'x' : '-' }}
                                    </td>
                                    <td>
                                        {{ $bet->cashout_amount ? number_format((float)$bet->cashout_amount, 2) : '-' }}
                                    </td>
                                    <td>{{ optional($bet->placed_at)->format('Y-m-d H:i:s') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center">No bets found in this round</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($bets->hasPages())
                    <div class="card-footer py-4">
                        {{ $bets->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection