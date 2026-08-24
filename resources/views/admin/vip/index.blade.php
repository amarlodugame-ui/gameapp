@extends('admin.layouts.app')

@section('panel')
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <div class="card custom--card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.vip.analytics.index') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-xl-4 col-lg-4">
                                <label class="form-label">Search user</label>
                                <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Username / name / email / mobile">
                            </div>

                            <div class="col-xl-3 col-lg-3">
                                <label class="form-label">VIP Level</label>
                                <select name="level_id" class="form-control">
                                    @foreach($levels as $level)
                                        <option value="{{ $level->id }}" @selected((int) request('level_id', $selectedLevel->id) === (int) $level->id)>
                                            VIP{{ $level->vip }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-xl-2 col-lg-2">
                                <label class="form-label">Per page</label>
                                <select name="per_page" class="form-control">
                                    @foreach([20, 30, 50, 100] as $size)
                                        <option value="{{ $size }}" @selected((int) request('per_page', 20) === $size)>
                                            {{ $size }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-xl-3 col-lg-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="las la-filter"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach($cards as $card)
            <div class="col-xl col-md-6">
                <div class="card analytics-card analytics-card--{{ $card['class'] }}">
                    <div class="card-body">
                        <div class="analytics-card__icon">
                            <i class="{{ $card['icon'] }}"></i>
                        </div>
                        <div class="analytics-card__content">
                            <span>{{ $card['title'] }}</span>
                            <h3>{{ $card['value'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card custom--card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">VIP{{ $selectedLevel->vip }} User Analysis</h5>
                    <span class="badge bg-primary">Period: {{ $currentPeriod }}</span>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Current VIP</th>
                                    <th>Total EXP</th>
                                    <th>Progress</th>
                                    <th>Monthly Due</th>
                                    <th>Refill</th>
                                    <th>Remaining</th>
                                    <th>Claim Window</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($users as $row)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $row['username'] }}</div>
                                        <small class="text-muted d-block">{{ $row['name'] }}</small>
                                        <small class="text-muted d-block">{{ $row['referral_code'] }}</small>
                                    </td>

                                    <td>
                                        <span class="badge bg-dark">VIP{{ $row['current_vip'] }}</span>
                                    </td>

                                    <td>{{ number_format($row['total_exp'], 2) }}</td>

                                    <td style="min-width: 220px;">
                                        <div class="progress progress--sm mb-2">
                                            <div
                                                class="progress-bar bg-success"
                                                role="progressbar"
                                                style="width: {{ $row['analysis']['progress_percent'] }}%;"
                                            ></div>
                                        </div>
                                        <div class="small text-muted">
                                            {{ number_format($row['analysis']['effective_progress'], 2) }}
                                            /
                                            {{ number_format($selectedLevel->require_exp, 2) }}
                                        </div>
                                    </td>

                                    <td>{{ number_format($row['analysis']['monthly_due_exp'], 2) }}</td>

                                    <td>
                                        @if($row['analysis']['required_bet_now'] > 0)
                                            {{ number_format($row['analysis']['qualified_bet_now'], 2) }}
                                            /
                                            {{ number_format($row['analysis']['required_bet_now'], 2) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="fw-bold text-danger">
                                            {{ number_format($row['analysis']['remaining_due'], 2) }}
                                        </span>
                                    </td>

                                    <td>{{ $row['analysis']['available_from_text'] }}</td>

                                    <td>
                                        <span class="badge bg-{{ $row['analysis']['status_class'] }}">
                                            {{ $row['analysis']['status_text'] }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <a href="{{ route('admin.vip.analytics.show', $row['id']) }}" class="btn btn-sm btn-outline-primary">
                                            Details
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">No users found</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($users->hasPages())
                    <div class="card-footer">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card custom--card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Claims This Month</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($recentClaims as $claim)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold">
                                            {{ optional($claim->user)->username ?: optional($claim->user)->name ?: 'User#'.$claim->user_id }}
                                        </div>
                                        <small class="text-muted d-block">
                                            VIP{{ optional($claim->vipLevel)->vip }} | {{ $claim->claim_period }}
                                        </small>
                                        <small class="text-muted d-block">
                                            {{ optional($claim->claimed_at)->format('Y-m-d H:i') }}
                                        </small>
                                    </div>
                                    <span class="badge bg-success">
                                        {{ number_format((float) $claim->reward_amount, 2) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-4">
                                No claim data
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card custom--card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Selected Level Info</h5>
                </div>
                <div class="card-body">
                    <ul class="analytics-list">
                        <li><span>VIP Level</span><strong>VIP{{ $selectedLevel->vip }}</strong></li>
                        <li><span>Require EXP</span><strong>{{ number_format((float) $selectedLevel->require_exp, 2) }}</strong></li>
                        <li><span>Monthly Claim Rate</span><strong>{{ number_format((float) $selectedLevel->monthly_claim_rate, 2) }}%</strong></li>
                        <li><span>Monthly Money</span><strong>{{ number_format((float) $selectedLevel->monthly_reward_money, 2) }}</strong></li>
                        <li><span>Monthly Love</span><strong>{{ number_format((float) $selectedLevel->monthly_reward_love, 0) }}</strong></li>
                        <li><span>Enabled</span><strong>{{ $selectedLevel->monthly_reward_enabled ? 'Yes' : 'No' }}</strong></li>
                    </ul>
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
.analytics-card{
    border: 0;
    border-radius: 16px;
    color: #fff;
    overflow: hidden;
}
.analytics-card .card-body{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding: 18px 20px;
}
.analytics-card__icon{
    font-size: 34px;
    opacity: .9;
}
.analytics-card__content span{
    display:block;
    font-size: 13px;
    opacity: .9;
}
.analytics-card__content h3{
    margin: 6px 0 0;
    font-size: 26px;
    font-weight: 700;
    color:#fff;
}
.analytics-card--primary{ background: linear-gradient(135deg,#7367f0,#5e50ee); }
.analytics-card--success{ background: linear-gradient(135deg,#28c76f,#1f9d57); }
.analytics-card--info{ background: linear-gradient(135deg,#00cfe8,#009db5); }
.analytics-card--warning{ background: linear-gradient(135deg,#ff9f43,#ff7b00); }
.analytics-card--danger{ background: linear-gradient(135deg,#ea5455,#d63031); }

.progress--sm{
    height: 8px;
    border-radius: 50px;
    background: #edf1f7;
}
.analytics-list{
    list-style: none;
    padding: 0;
    margin: 0;
}
.analytics-list li{
    display:flex;
    justify-content:space-between;
    gap: 16px;
    padding: 10px 0;
    border-bottom: 1px solid #eef2f7;
}
.analytics-list li:last-child{
    border-bottom: 0;
}
</style>
@endpush