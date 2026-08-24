@extends('admin.layouts.app')

@section('panel')
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <div class="card custom--card">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h4 class="mb-1">{{ $user->username ?: $user->name ?: 'User#'.$user->id }}</h4>
                        <div class="text-muted">{{ $user->email ?: '-' }}</div>
                        <div class="text-muted">{{ $user->mobile ?: '-' }}</div>
                    </div>

                    <div class="d-flex flex-wrap gap-3">
                        <div class="mini-stat">
                            <span>Current VIP</span>
                            <h5>VIP{{ $currentVip }}</h5>
                        </div>
                        <div class="mini-stat">
                            <span>Total EXP</span>
                            <h5>{{ number_format($totalExp, 2) }}</h5>
                        </div>
                        <div class="mini-stat">
                            <span>Balance</span>
                            <h5>{{ number_format((float) $user->balance, 2) }}</h5>
                        </div>
                        <div class="mini-stat">
                            <span>Period</span>
                            <h5>{{ $currentPeriod }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card custom--card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">All VIP Level Analysis</h5>
                    <a href="{{ route('admin.vip.analytics.index') }}" class="btn btn-sm btn-outline-primary">Back</a>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>VIP</th>
                                    <th>Require EXP</th>
                                    <th>Level Up</th>
                                    <th>Status</th>
                                    <th>Due</th>
                                    <th>Refill</th>
                                    <th>Remaining</th>
                                    <th>Available</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($levelRows as $row)
                                <tr>
                                    <td>
                                        <span class="badge bg-dark">VIP{{ $row['vip'] }}</span>
                                    </td>

                                    <td>{{ number_format($row['require_exp'], 2) }}</td>

                                    <td>
                                        @if($row['level_up_claimed'])
                                            <span class="badge bg-success">Claimed</span>
                                        @else
                                            <span class="badge bg-secondary">Not claimed</span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge bg-{{ $row['snapshot']['status_class'] }}">
                                            {{ $row['snapshot']['status_text'] }}
                                        </span>
                                    </td>

                                    <td>{{ number_format($row['snapshot']['monthly_due_exp'], 2) }}</td>

                                    <td>
                                        @if($row['snapshot']['required_bet_now'] > 0)
                                            {{ number_format($row['snapshot']['qualified_bet_now'], 2) }}
                                            /
                                            {{ number_format($row['snapshot']['required_bet_now'], 2) }}
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td>{{ number_format($row['snapshot']['remaining_due'], 2) }}</td>

                                    <td>{{ $row['snapshot']['available_from_text'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card custom--card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Monthly Claims</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($monthlyClaims as $claim)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold">VIP{{ optional($claim->vipLevel)->vip }}</div>
                                        <small class="text-muted d-block">{{ $claim->claim_period }}</small>
                                        <small class="text-muted d-block">{{ optional($claim->claimed_at)->format('Y-m-d H:i') }}</small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-success">{{ number_format((float) $claim->reward_amount, 2) }}</div>
                                        <small class="text-muted">Love: {{ number_format((float) $claim->reward_love, 0) }}</small>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-4">
                                No monthly claim history
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('style')
<style>
.custom--card{
    border: 0;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(18, 38, 63, .08);
}
.mini-stat{
    min-width: 140px;
    padding: 10px 14px;
    border-radius: 12px;
    background: #f8faff;
}
.mini-stat span{
    display:block;
    font-size: 12px;
    color: #6c757d;
}
.mini-stat h5{
    margin: 6px 0 0;
    font-size: 20px;
    font-weight: 700;
}
</style>
@endpush