@extends('admin.layouts.app')

@section('panel')
<div class="container-fluid">

    <div class="row mb-3">
        <div class="col-12">
            <div class="card custom-card">
                <div class="card-header">
                    <h5 class="mb-0">{{ $pageTitle }}</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.users.refferalusersAllData') }}">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label>Referral Code</label>
                                <input type="text" name="referral_code" class="form-control" value="{{ $referralCode ?? '' }}" placeholder="Enter referral code" required>
                            </div>
                            <div class="col-md-3">
                                <label>Date From</label>
                                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label>Date To</label>
                                <input type="date" name="date_to" class="form-control" value="{{ $dateTo ?? '' }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Search</button>
                            </div>
                        </div>
                    </form>

                    @if(session('error'))
                        <div class="alert alert-danger mt-3 mb-0">{{ session('error') }}</div>
                    @endif

                    @if(!empty($referralCode) && !$rootUser)
                        <div class="alert alert-danger mt-3 mb-0">Referral user not found</div>
                    @endif

                    @if(empty($referralCode))
                        <div class="alert alert-info mt-3 mb-0">
                            প্রথমে referral code দিয়ে search করুন। search না করা পর্যন্ত কিছু show করবে না।
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($rootUser && $result)
        <div class="row mb-3">
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <h5 class="mb-0">Referral Owner Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row gy-2">
                            <div class="col-md-3"><strong>Name:</strong> {{ $rootUser->name }}</div>
                            <div class="col-md-3"><strong>Username:</strong> {{ $rootUser->username }}</div>
                            <div class="col-md-3"><strong>Email:</strong> {{ $rootUser->email }}</div>
                            <div class="col-md-3"><strong>Referral Code:</strong> {{ $rootUser->referral_code }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-primary mb-3">
             <strong>All Time</strong> data দেখাবে
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card summary-card">
            <div class="card-body">
                <h6>Own Total Deposit / Withdraw</h6>
                <div><strong>Deposit:</strong> {{ $result['summary']['own_deposit_amount_all_time'] }}</div>
                <small>Count: {{ $result['summary']['own_deposit_count_all_time'] }}</small>
                <hr class="my-2">
                <div><strong>Withdraw:</strong> {{ $result['summary']['own_withdraw_amount_all_time'] }}</div>
                <small>Count: {{ $result['summary']['own_withdraw_count_all_time'] }}</small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card summary-card">
            <div class="card-body">
                <h6>Direct (Level 1) Deposit / Withdraw</h6>
                <div><strong>Deposit:</strong> {{ $result['summary']['direct_deposit_amount_all_time'] }}</div>
                <small>Count: {{ $result['summary']['direct_deposit_count_all_time'] }}</small>
                <hr class="my-2">
                <div><strong>Withdraw:</strong> {{ $result['summary']['direct_withdraw_amount_all_time'] }}</div>
                <small>Count: {{ $result['summary']['direct_withdraw_count_all_time'] }}</small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card summary-card">
            <div class="card-body">
                <h6>Team (Level 2-6) Deposit / Withdraw</h6>
                <div><strong>Deposit:</strong> {{ $result['summary']['team_2to6_deposit_amount_all_time'] }}</div>
                <small>Count: {{ $result['summary']['team_2to6_deposit_count_all_time'] }}</small>
                <hr class="my-2">
                <div><strong>Withdraw:</strong> {{ $result['summary']['team_2to6_withdraw_amount_all_time'] }}</div>
                <small>Count: {{ $result['summary']['team_2to6_withdraw_count_all_time'] }}</small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card summary-card">
            <div class="card-body">
                <h6>Direct (Level 1) 3+ Deposit Members</h6>
                <h3>{{ $result['summary']['direct_three_deposit_members_all_time'] }}</h3>
                <small>Out of {{ $result['summary']['direct_total_members_all_time'] }} direct members</small>
            </div>
        </div>
    </div>
</div>
    <div class="col-12">
        <div class="alert alert-primary mb-3">
          <strong>All Time</strong> end
        </div>
    </div>
        
        

        <div class="row mb-3">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card summary-card">
                    <div class="card-body">
                        <h6>Total Team</h6>
                        <h3>{{ $result['summary']['team_count'] }}</h3>
                        <small>Direct: {{ $result['summary']['direct_count'] }}</small>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card summary-card">
                    <div class="card-body">
                        <h6>Wingo Bet</h6>
                        <h3>{{ $result['summary']['wingo_bet_amount'] }}</h3>
                        <small>Count: {{ $result['summary']['wingo_bet_count'] }}</small>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card summary-card">
                    <div class="card-body">
                        <h6>Aviator Bet</h6>
                        <h3>{{ $result['summary']['aviator_bet_amount'] }}</h3>
                        <small>Count: {{ $result['summary']['aviator_bet_count'] }}</small>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card summary-card">
                    <div class="card-body">
                        <h6>Commission</h6>
                        <h3>{{ $result['summary']['commission_amount'] }}</h3>
                        <small>From team</small>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6 mb-3">
                <div class="card summary-card">
                    <div class="card-body">
                        <h6>Deposit Amount</h6>
                        <h3>{{ $result['summary']['deposit_amount'] }}</h3>
                        <small>Deposit Count: {{ $result['summary']['deposit_count'] }}</small>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6 mb-3">
                <div class="card summary-card">
                    <div class="card-body">
                        <h6>Depositing Users</h6>
                        <h3>{{ $result['summary']['deposit_user_count'] }}</h3>
                        <!--<small>At least 1 successful deposit</small>-->
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-12 mb-3">
                <div class="card summary-card">
                    <div class="card-body">
                        <h6>Date Filter</h6>
                        <h5 class="mb-0">
                            {{ $dateFrom ?: 'Start' }} - {{ $dateTo ?: 'Today' }}
                        </h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <h5 class="mb-0">Level Wise Summary (6 Level)</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Level</th>
                                    <th>Total User</th>
                                    <th>Wingo Bet Count</th>
                                    <th>Wingo Bet Amount</th>
                                    <th>Aviator Bet Count</th>
                                    <th>Aviator Bet Amount</th>
                                    <th>Depositing Users</th>
                                    <th>Deposit Count</th>
                                    <th>Deposit Amount</th>
                                    <th>Commission</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($result['levelSummary'] as $row)
                                    <tr>
                                        <td>{{ $row['level'] }}</td>
                                        <td>{{ $row['user_count'] }}</td>
                                        <td>{{ $row['wingo_bet_count'] }}</td>
                                        <td>{{ $row['wingo_bet_amount'] }}</td>
                                        <td>{{ $row['aviator_bet_count'] }}</td>
                                        <td>{{ $row['aviator_bet_amount'] }}</td>
                                        <td>{{ $row['deposit_user_count'] }}</td>
                                        <td>{{ $row['deposit_count'] }}</td>
                                        <td>{{ $row['deposit_amount'] }}</td>
                                        <td>{{ $row['commission_amount'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">No data found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="bg-light">
                                    <th>Total</th>
                                    <th>{{ $result['summary']['team_count'] }}</th>
                                    <th>{{ $result['summary']['wingo_bet_count'] }}</th>
                                    <th>{{ $result['summary']['wingo_bet_amount'] }}</th>
                                    <th>{{ $result['summary']['aviator_bet_count'] }}</th>
                                    <th>{{ $result['summary']['aviator_bet_amount'] }}</th>
                                    <th>{{ $result['summary']['deposit_user_count'] }}</th>
                                    <th>{{ $result['summary']['deposit_count'] }}</th>
                                    <th>{{ $result['summary']['deposit_amount'] }}</th>
                                    <th>{{ $result['summary']['commission_amount'] }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <h5 class="mb-0">User Wise Details</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Level</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Referral Code</th>
                                    <th>Register Date</th>
                                    <th>Wingo Bet Count</th>
                                    <th>Wingo Bet Amount</th>
                                    <th>Aviator Bet Count</th>
                                    <th>Aviator Bet Amount</th>
                                    <th>Deposit Count</th>
                                    <th>Deposit Amount</th>
                                    <th>Commission</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($result['details'] as $key => $user)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>
                                            <span class="badge badge-primary">Level {{ $user->level }}</span>
                                        </td>
                                        <td>
                                            {{ $user->name }}<br>
                                            <small>{{ $user->email }}</small>
                                        </td>
                                        <td>{{ $user->username }}</td>
                                        <td>{{ $user->referral_code }}</td>
                                        <td>{{ \Carbon\Carbon::parse($user->created_at)->format('d M Y h:i A') }}</td>
                                        <td>{{ $user->wingo_bet_count }}</td>
                                        <td>{{ $user->wingo_bet_amount }}</td>
                                        <td>{{ $user->aviator_bet_count }}</td>
                                        <td>{{ $user->aviator_bet_amount }}</td>
                                        <td>{{ $user->deposit_count }}</td>
                                        <td>{{ $user->deposit_amount }}</td>
                                        <td>{{ $user->commission_amount }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center">No referral users found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="bg-light">
                                    <th colspan="6" class="text-end">Grand Total</th>
                                    <th>{{ $result['summary']['wingo_bet_count'] }}</th>
                                    <th>{{ $result['summary']['wingo_bet_amount'] }}</th>
                                    <th>{{ $result['summary']['aviator_bet_count'] }}</th>
                                    <th>{{ $result['summary']['aviator_bet_amount'] }}</th>
                                    <th>{{ $result['summary']['deposit_count'] }}</th>
                                    <th>{{ $result['summary']['deposit_amount'] }}</th>
                                    <th>{{ $result['summary']['commission_amount'] }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection

@push('style')
<style>
    .summary-card{
        border: 1px solid #e9ecef;
        border-radius: 10px;
    }
    .summary-card .card-body{
        padding: 18px;
    }
    .summary-card h6{
        font-size: 14px;
        color: #6c757d;
        margin-bottom: 8px;
    }
    .summary-card h3{
        font-size: 24px;
        margin-bottom: 4px;
        font-weight: 700;
    }
    .custom-card{
        border-radius: 10px;
    }
    .table th, .table td{
        vertical-align: middle !important;
        white-space: nowrap;
    }
    .badge-primary {
        background-color: #007bff;
        color: #fff;
        border-radius: 0.25rem;
        border: 0.5px solid #007bff;
        padding: 0.25em 0.4em;  
    }
</style>
@endpush